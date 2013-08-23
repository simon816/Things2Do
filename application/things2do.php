<?php
if (!defined("THINGS2DO")){die("Unauthorized access");}

include "$root/application/APIs/YahooSQL.php";
include "$root/application/APIs/geolocation.php";
include "$root/application/APIs/alcemyapi.php";

class things2do {
    // The master class
    public $query;
    public $analysis;
    public $location;
    public $category;
    private $root;
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
        $this->dostuff();
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
        $this->ids=$this->categorytoid($this->category, $this->analysis, $this->keywords);
    }
    private function categorytoid($category, $analysis, $keywords) {
        //var_dump(array($category, $analysis, $keywords));
        $categories=Array(
            "Hobbies & Personal Activities" => 1,
            "Shopping" => 2,
            "Food & Cooking" => 3,
            "Food Safety" => 4,
            "Public Health" => 5,
            "Arts & Entertainment" => 6,
            "arts_entertainment" => 6,
            "Movies" => 7,
            "Media" => 8,
            "Video Games" => 9,
            "gaming" => 9,
            "Books & Publishing" => 10,
            "Family Health" => 11,
            "Parenting" => 12,
            "Family & Relationships" => 13,
            "Arts & Entertainment Events" => 14,
            "Sports & Recreation" => 15,
            "sports" => 15,
            "recreation" => 16,
            "Jewelry & Watches" => 17
        );
        function convID($categories, $catname) {
            if (isset($categories[$catname])) {
                //echo "got ".$categories[$catname]."<br>";
                return $categories[$catname];
            }
            //echo "got nothing<br>";
            return 0;
        }
        $output=Array('categories'=>Array(), "keywords"=>Array());
        if ($analysis['categories']) {
            foreach ($analysis['categories'] as $cat=>$score) {
                //echo "handling $cat ";
                $output['categories'][convID($categories, $cat)]=$score;
            }
        }
        if ($category) {
            if (!isset($category['unknown'])) {
                $cat=key($category);
                //echo "handling $cat ";
                $id=convID($categories, $cat);
                if (isset($output['categories'][$id])) {
                    //echo "found dupe. ";
                    if ($output['categories'][$id] < $category[$cat]) {
                        //echo "dupe is lower<br>";
                        $output['categories'][$id]=$category[$cat];
                    }
                    else {
                        //echo "dupe is higher<br>";
                    }
                }
                else {
                    $output['categories'][$id]=(float)$category[$cat];
                }
            }
        }
        foreach ($keywords as $pack) {
            $output['keywords'][$pack['text']]=(float)$pack['relevance'];
        }
        return $output;
    }
    function __destruct() {
        curl_close($this->curl);
    }
}
?>