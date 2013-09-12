<?php
if (!defined("THINGS2DO")){die("Unauthorized access");}

class AlcAPI {
    private $url;
    private $api_key;
    private $ch;

    public function __construct($ch=null) {
        $this->url="http://access.alchemyapi.com";
        $this->api_key=getkey('alchemy');
        $this->multi_request=Array();
        $this->ch=$ch;
    }
    private function make_query($url, $text, $data=Array()) {
        $q=str_replace('&amp;', '&', http_build_query(array_merge($data, Array("apikey"=>$this->api_key, "text"=>$text, "outputMode"=>"json"))));
        return $this->url.$url."?".$q;
    }
    private function get_json($body, $format=null) {
        $json=json_decode($body, true);
        $output=Array();
        if ($format) {
            foreach ($format as $key=>$value) {
                if ($key===0) {
                    $output=$json[$value];
                }
                else {
                    $output[$json[$key]]=$json[$value];
                }
            }
        }
        else {
            $output=$json;
        }
        return $output;
    }
    public function add_request($type, $text) {
        $url = null;
        if ($type == "keywords") {
            $url=$this->make_query("/calls/text/TextGetRankedKeywords", $text);
            $fmt=Array(0=>"keywords");
        }
        elseif ($type == "category") {
            $url=$this->make_query("/calls/text/TextGetCategory", $text);
            $fmt=Array("category"=>"score");
        }
        if ($url == null) {
            throw new Exception("Failed to add request to alchemyapi");
        }
        array_push($this->multi_request, Array("url"=>$url, "fmt"=>$fmt, "type"=>$type));
    }
    public function run_request() {
        if ($this->ch==null) {
            $upstreamCurl=false;
            $this->ch=curl_init();
            curl_setopt_array($this->ch, Array(CURLOPT_RETURNTRANSFER=>true, CURLOPT_CONNECTTIMEOUT=>2));
        }
        else {
            $upstreamCurl=true;
        }
        $out_arr=Array();
        foreach ($this->multi_request as $inf) {
            curl_setopt($this->ch, CURLOPT_URL, $inf["url"]);
            $out_arr[$inf["type"]]=$this->get_json(curl_exec($this->ch), $inf["fmt"]);
        }
        if ($upstreamCurl==false) {
            curl_close($this->ch);
        }
        return $out_arr;
    }
    public static function Categories2Type($categories) {
        $out=Array();
        // Switch case vs Array lookup, I chose Switch for readability although array is faster
        foreach ($categories as $catname=>$score) {
            switch($catname) {
                case "arts_entertainment":
                    $key=TYPE_CINEMA;
                    break;
                case "gaming":
                    $key=TYPE_VIDEOGAMES;
                    break;
                case "sports":
                    $key=TYPE_STADIUM;
                    break;
                default:
                    $key=TYPE_NULL;
            }
            $out[$key]=(float)$score;
        }
        return $out;
    }
}
?>