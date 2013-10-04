<?php
if (!defined("THINGS2DO")){die("Unauthorized access");}

include "$root/application/APIs/YahooSQL.php";
include "$root/application/APIs/geolocation.php";
include "$root/application/APIs/alcemyapi.php";
include "$root/application/APIs/metoffice.php";
include "$root/application/database.php";

class things2do {
    // The master class

    public $query;
    public $location;
    public $searchTypes;

    private $YQL;
    private $GEOLocation;
    private $alc;
    private $met;

    private $root;
    private $curl;
    private $db;

    public function __construct($root) {
        $this->root=$root;
        $this->loadConfig();
        $this->YQL=new YahooSQL($this->curl);
        $this->GEOLocation=new LocationManager($this->curl);
        $this->alc=new AlcAPI($this->curl);
        $this->met=new MetOfficeAPI($this->curl);
        $qname='q';
        $qtype=QUERY_MODE=="GET"?$_GET:$_POST;
        $this->query=isset($qtype[$qname])?$qtype[$qname]:"";
        $this->db=new MYSQL_DB();
    }
    private function loadConfig() {
        include "$this->root/config/main.php";
        include "$this->root/config/types.php";
        $this->curl=curl_init();
        curl_setopt_array($this->curl, Array(
            CURLOPT_RETURNTRANSFER=>true,
            CURLOPT_FORBID_REUSE=>false,
            CURLOPT_FRESH_CONNECT=>false,
            CURLOPT_CONNECTTIMEOUT=>2,
            CURLOPT_SSL_VERIFYPEER=>false
        ));
    }
    public function suggestToUser() {
        if (!$this->query) {
            throw new Exception("No query entered");
        }
        if ($this->query=="@test") {
            echo json_encode(array(
                Array(
                "url" => "http://things2do.ws",
                "title" => "Test Things 2 Do",
                "score" => 1,
                "distance"=>0,
                "type" => TYPE_CINEMA,
                "postcode" => "POSTCODE",
                "images" => "/assets/images/moviepostersample.png",
                "data" => Array()
                )
            ));
        exit;
        }
        //$this->interpretSearch();
        //$this->getLocation();
        //$this->getWeather();
        //$this->getThings();
        $output=$this->useOldAlgorithm();
        echo json_encode($output);
        exit;
    }
    private function interpretSearch() {
        // use yahoo content analysis
        $analysis=$this->YQL->do_contentanalysis_query($this->query, true);
        // use alchemyapi
        $this->alc->add_request("category", $this->query);
        $alc_res=$this->alc->run_request();
        $categories=AlcAPI::Categories2Type($alc_res["category"]);
        $this->searchTypes=$this->merge($analysis, $categories);
        if (!$this->searchTypes) {
            // Unable to detect phrase
            $res=$this->db->select("type, score", "keywords")->where("word", "in ('".str_replace(" ", "','", $this->query)."')", "")->_();
            foreach ($res as $match) {
                $this->searchTypes[(int)$match['type']]=(float)$match['score'];
            }
        }
        if (!$this->searchTypes) {
            // Cannot work out what category the query is
            $this->searchTypes=$this->getTypesBasedOnTime(2);
        }
    }
    private function getLocation() {
        $this->location=$this->GEOLocation->try_all_methods();
    }
    private function getWeather() {
        $list=$this->db->select("*", "metLocList")->_();
        if (!$list) {
            $list=$this->met->getLocationList();
            $this->db->insert_batch("metLocList", $list)->_();
        }
        $id=$this->met->getNearestLocation($this->location[0], $this->location[1], $list);
        $weather=$this->met->getWeather($id);
        $rep=$weather['SiteRep']['DV']['Location']['Period'][0]['Rep'][0];
        switch ((int)$rep['W']) {
            case 0:
            case 1:
                $type="sun";
                break;
            case 2:
            case 3:
            case 4:
            case 5:
            case 6:
            case 7:
            case 8:
                $type="cloud";
                break;
            default:
                $type="rain";
        }
        $this->weather=Array("precipitation"=>(int)$rep['Pp'], "temperature"=>(int)$rep['F'], "type"=>$type);
    }
    private function getThings() {
        include "$this->root/application/findthings.php";
        $things=new Activities($this->db, $this->root, $this->curl);
        $things->setLocation($this->location);
        $things->setTypes($this->searchTypes);
        $this->suggestions=$things->getSuggestions();
    }
    private function merge($analysis, $categories) {
        $new=Array();
        foreach($analysis as $id=>$score) {
            if (isset($categories[$id])) {
                if ($categories[$id] > $score) {
                    $score = $categories[$id];
                }
                unset($categories[$id]);
            }
            $new[$id]=$score;
        }
        array_merge($new, $categories);
        unset($new[TYPE_NULL]);
        return $new;
    }
    private function getTypesBasedOnTime($maxtypes=1) {
        $feelings=$this->db->select("*", "timefeel")->_();
        $hour=(float)date("G", time()) + ((float)date("i", time()))/60;
        if ($hour>=3&&$hour<=9)$name="mornfeel";
        elseif ($hour>=9&&$hour<=15)$name="dayfeel";
        elseif ($hour>=15&&$hour<=20)$name="evefeel";
        else $name="nightfeel";
        $arr=Array();
        foreach($feelings as $timefeel) {
            $arr[]=array($timefeel['type']=>(float)$timefeel[$name]);
        }
        usort($arr, function($a,$b) {
            if (array_values($a)[0] < array_values($b)[0]) {
                return 1;
            } else {
                return 0;
            }
        });
        $r=Array();
        foreach (array_splice($arr, 0, $maxtypes) as $kv) {
            $id=array_keys($kv)[0];
            $r[$id]=$kv[$id];
        }
        return $r;
    }

    private function useOldAlgorithm() {
        // until a full rewrite is complete this will be the method
        include "$this->root/application/oldsearch.php";
        $this->location=Array(52.483056,-1.893611);
        $this->getWeather();
        return getResults($this, $this->root);
    }

    function __destruct() {
        curl_close($this->curl);
    }
}
?>