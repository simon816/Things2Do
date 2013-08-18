<?php
include "$root/application/APIs/YahooSQL.php";
include "$root/application/APIs/geobytes.php";
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
        $this->YQL=new YahooSQL();
        $this->GEOLocation=new LocationManager();
        $this->alc=new AlcAPI();
        $qname='q';
        $qtype=QUERY_MODE=="GET"?$_GET:$_POST;
        $this->query=isset($qtype[$qname])?$qtype[$qname]:"";
    }
    private function loadConfig() {
        include "$this->root/config/main.php";
        include "$this->root/config/types.php";
    }
    public function suggestToUser() {
    }
    protected function dostuff() {
        // use yahoo content analysis
        $this->analysis=$this->YQL->do_contentanalysis_query($this->query);
        // use location
        $this->location=$this->GEOLocation->try_all_methods();
        // use alchemyapi
        $this->category=$this->alc->get_category($this->query);
        $this->keywords=$this->alc->get_keywords($this->query);
        var_dump($this->categorytoid($this->category, $this->analysis, $this->keywords));
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
                echo "got ".$categories[$catname]."<br>";
                return $categories[$catname];
            }
            echo "got nothing<br>";
            return 0;
        }
        $output=Array('categories'=>Array(), "keywords"=>Array());
        if ($analysis['categories']) {
            foreach ($analysis['categories'] as $cat=>$score) {
                echo "handling $cat ";
                $output['categories'][convID($categories, $cat)]=$score;
            }
        }
        if ($category) {
            if (!isset($category['unknown'])) {
                $cat=key($category);
                echo "handling $cat ";
                $id=convID($categories, $cat);
                if (isset($output['categories'][$id])) {
                    echo "found dupe. ";
                    if ($output['categories'][$id] < $category[$cat]) {
                        echo "dupe is lower<br>";
                        $output['categories'][$id]=$category[$cat];
                    }
                    else {
                        echo "dupe is higher<br>";
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
}
?>