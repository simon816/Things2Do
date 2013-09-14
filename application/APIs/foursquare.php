<?php
if (!defined("THINGS2DO")){die("Unauthorized access");}

class FourSquareAPI {
    private $ch;
    private $key;
    private $id;
    private $url;
    private $location;
    private $categories = Array();
    function __construct($ch=null) {
        $this->ch=$ch;
        $this->key=getkey('foursquare');
        $this->id=getid('foursquare');
        $this->url="https://api.foursquare.com/v2/venues/";
    }
    public function setCategories($categories) {
        foreach ($categories as $c) {
            $this->categories[$c['catname']]=$c['catid'];
        }
    }
    private function get_json($url, $data=array(), $returnobj="venues") {
        $data['client_secret']=$this->key;
        $data['client_id']=$this->id;
        $data['v']="20130914"; // API Confirmed working at this date
        $url=$this->url."$url?".http_build_query($data);
        if ($this->ch==null) {
            $upstreamCurl=false;
            $this->ch=curl_init();
            curl_setopt_array($this->ch, Array(CURLOPT_RETURNTRANSFER=>true, CURLOPT_CONNECTTIMEOUT=>2));
        }
        else {
            $upstreamCurl=true;
        }
        curl_setopt($this->ch, CURLOPT_URL, $url);
        $json=curl_exec($this->ch);
        if ($upstreamCurl==false) {
            curl_close($this->ch);
        }
        $json=json_decode($json, true);
        if ($json) {
            $meta=$json['meta'];
            if ($meta['code']!=200) {
                throw new Exception("[".$meta["code"]."] FourSquare API Error: (".$meta["errorType"].") ".$meta["errorDetail"]);
            }
        }
        //echo "GET ".$url."<br>";
        return $json['response'][$returnobj];
    }
    private function search($categories=array(), $query="") {
        if (!$this->location){
            throw new Exception("Location not specified");
        }
        $data=array(
            "ll"=>implode(",", $this->location),
            "intent"=>"checkin",
            "radius"=>SEARCH_RADIUS*1000,
            "categoryId"=>implode(",", $categories),
            "query"=>$query,
            "limit"=>50
        );
        return $this->get_json("search", $data);
    }
    public function setLocation($loc) {
        $this->location=$loc;
    }
    public function get($key) {
        if (!isset($this->categories[$key])) {
            return $this->search(array(), $key);
        }
        return $this->search(array($this->categories[$key]));
    }
    public function getMulti($keys) {
        return $this->search(array_map(function($a){return $this->categories[$a];}, $keys));
    }
    public function getCategoryList() {
        $c=$this->get_json("categories", array(), "categories");
        function walk($categoryarr, &$outarr, $parent=null) {
            foreach($categoryarr as $category) {
                if (isset($category['categories'])) {
                    if ($category['categories']) {
                        $inner=$category['categories'];
                        walk($inner, $outarr, $category['id']);
                    }
                    unset($category['categories']);
                }
                $category['parent']=$parent;
                $outarr[]=$category;
            }
        }
        $out=Array();
        walk($c, $out);
        var_dump($out);
        return $out;
    }
}
?>