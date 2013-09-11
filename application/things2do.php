<?php
if (!defined("THINGS2DO")){die("Unauthorized access");}

include "$root/application/APIs/YahooSQL.php";
include "$root/application/APIs/geolocation.php";
include "$root/application/APIs/alcemyapi.php";
include "$root/application/weather.php";

class things2do {
    // The master class
    public $query;
    public $analysis;
    public $location;
    public $category;
    private $root;
    private $curl;
    public function __construct($root) {
        $this->root=$root;
        $this->loadConfig();
        $this->YQL=new YahooSQL($this->curl);
        $this->GEOLocation=new LocationManager($this->curl);
        $this->alc=new AlcAPI($this->curl);
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
        //$this->dostuff();
        $output=$this->useOldAlgorithm();
        /*
        $output=Array();
        // Example data <
        array_push($output, Array(
            "url" => "http://things2do.ws",
            "title" => "Test Things 2 Do",
            "score" => 1,
            "type" => TYPE_CINEMA,
            "postcode" => "POSTCODE",
            "image" => "/assets/images/moviepostersample.png",
            "data" => Array()
        ));
        // />
        */
        echo json_encode($output);
        exit;
    }
    protected function dostuff() {
        // use yahoo content analysis
        $this->analysis=$this->YQL->do_contentanalysis_query($this->query);
        // use location
        $this->location=$this->GEOLocation->try_all_methods();
        // use alchemyapi
        $this->alc->add_request("keywords", $this->query);
        $this->alc->add_request("category", $this->query);
        $r=$this->alc->run_request();
        $this->category=$r["category"];
        $this->keywords=$r["keywords"];
    }

    private function useOldAlgorithm() {
        // until a full rewrite is complete this will be the method
        include "$this->root/application/oldsearch.php";
        $this->location=Array(52.483056,-1.893611);
        $this->weather=getWeather($this->curl, $this->root, $this->location);
        return getResults($this, $this->root);
    }

    function __destruct() {
        curl_close($this->curl);
    }
}
?>