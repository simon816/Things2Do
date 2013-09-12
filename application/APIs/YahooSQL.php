<?php
if (!defined("THINGS2DO")){die("Unauthorized access");}

class YahooSQL {
    private $api_version;
    private $url;
    private $ch;
    public function __construct($ch=null) {
        $this->api_version=1;
        $this->url="http://query.yahooapis.com/v$this->api_version/public/yql";
        $this->ch=$ch;
    }
    private function build_select_query($from, $key, $value) {
        $this->query=urlencode("select * from $from where $key=\"$value\"");
        return $this->query;
    }
    public function get_json($query=null) {
        if ($this->ch==null) {
            $upstreamCurl=false;
            $this->ch=curl_init();
            curl_setopt_array($this->ch, Array(CURLOPT_RETURNTRANSFER=>true, CURLOPT_CONNECTTIMEOUT=>2));
        }
        else {
            $upstreamCurl=true;
        }
        if (!$query) {$query=$this->query;}
        curl_setopt($this->ch, CURLOPT_URL, "$this->url?q=$query&format=json");
        $jsonstring=curl_exec($this->ch);
        if ($upstreamCurl==false) {
            curl_close($this->ch);
        }
        $this->json=json_decode($jsonstring, true);
        return $this->json;
    }
    public function get_results($jsonarray=null, $toID=false) {
        if (!$jsonarray){$jsonarray=$this->json;}
        $output=Array();
        if (isset($jsonarray['query'])) {
            $query=$jsonarray['query'];
            $results=$query['results'];
            if (isset($results['yctCategories'])) {
                foreach ($results['yctCategories']['yctCategory'] as $category) {
                    $category=$category;
                    if (isset($category['content'])) {
                        $key=$category['content'];
                        if($toID==true){$key=$this->Category2Type($key);}
                        if (isset($output[$key])){if($output[$key]>(float)$category['score']){continue;}}
                        $output[$key]=(float)$category['score'];
                    }
                    else {
                        if (isset($output["tmp"])) {
                            $key=$category;
                            if($toID==true){$key=$this->Category2Type($key);}
                            $output[$key]=(float)$output["tmp"];
                            unset($output["tmp"]);
                        }
                        else {
                            $output["tmp"]=$category;
                        }
                    }
                    
                }
            }
        }
        return $output;
    }
    public function do_contentanalysis_query($value, $toID=false) {
        $this->build_select_query("contentanalysis.analyze", "text", $value);
        $this->get_json();
        return $this->get_results(null, $toID);
    }
        public static function Category2Type($category) {
        // Switch case vs Array lookup, I chose Switch for readability although array is faster
        switch ($category) {
            // Order - Most unique -> most general
            case "Bowling":
                return TYPE_BOWLING;
            case "Video Games":
                return TYPE_VIDEOGAMES;
            case "Books & Publishing":
                return TYPE_BOOKSHOP;
            case "Food & Cooking":
            case "Food Safety":
            case "Recipes":
            case "Dining & Nightlife":
                return TYPE_FOOD;
            case "Visual Arts":
                return TYPE_ARTGALLERY;
            case "Music":
                return TYPE_RECORDSHOP;
            case "Animals":
            case "Pets":
                return TYPE_ZOO;
                //return TYPE_AQUARIUM;
            case "Plants":
            case "Environment":
                return TYPE_PARK;
            case "American Football":
                return TYPE_STADIUM;
            case "Shopping":
            case "Technology & Electronics":
                return TYPE_MALL;
            case "Society & Culture":
                return TYPE_MUSEUM;
            case "Sports & Recreation":
                return TYPE_BEACH;
            case "Arts & Entertainment":
            case "Movies":
            case "Media":
                return TYPE_CINEMA;
            default:
                return TYPE_NULL;
        }
        
    }
}
?>