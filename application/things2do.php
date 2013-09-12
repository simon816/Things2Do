<?php
if (!defined("THINGS2DO")){die("Unauthorized access");}

include "$root/application/APIs/YahooSQL.php";
include "$root/application/APIs/geolocation.php";
include "$root/application/APIs/alcemyapi.php";
include "$root/application/APIs/metoffice.php";

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
    }
    private function loadConfig() {
        include "$this->root/config/main.php";
        include "$this->root/config/types.php";
        $this->curl=curl_init();
        curl_setopt_array($this->curl, Array(
            CURLOPT_RETURNTRANSFER=>true,
            CURLOPT_FORBID_REUSE=>false,
            CURLOPT_FRESH_CONNECT=>false,
            CURLOPT_CONNECTTIMEOUT=>2
        ));
    }
    public function suggestToUser() {
        if (!$this->query) {
            throw new Exception("No query entered");
        }
        //$this->interpretSearch();
        //$this->getLocation();
        //$this->getWeather();
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
    }
    private function getLocation() {
        $this->location=$this->GEOLocation->try_all_methods();
    }
    private function getWeather() {
        $id=$this->met->getNearestLocation($this->location[0], $this->location[1]);
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