<?php
if (!defined("THINGS2DO")){die("Unauthorized access");}

class LocationManager {
    public $lat;
    public $lon;
    private $url;
    private $apikey;
    private $ch;
    function __construct($ch=null) {
        $this->url="http://api.ipinfodb.com/v3/ip-city/";
        $this->apikey=getkey('ipinfodb');
        $this->ch=$ch;
    }
    public function try_all_methods() {
        $this->get_geolocation();
        if ($this->lat==null||$this->lon==null) {
            $this->get_web_location();
        }
        return Array($this->lat, $this->lon);
    }
    public function get_geolocation() {
        $qtype=LAT_LON_MODE=="GET"?$_GET:$_POST;
        if (isset($qtype["lat"])) {
            $this->lat=$qtype["lat"];
        }
        if (isset($qtype["lon"])) {
            $this->lon=$qtype["lon"];
        }
    }
    public function get_web_location() {
        if ($this->ch==null) {
            $upstreamCurl=false;
            $this->ch=curl_init();
            curl_setopt_array($this->ch, Array(CURLOPT_RETURNTRANSFER=>true, CURLOPT_CONNECTTIMEOUT=>2));
        }
        else {
            $upstreamCurl=true;
        }
        $ip=$_SERVER["REMOTE_ADDR"];
        if($_SERVER["SERVER_NAME"]=="localhost"||$_SERVER["SERVER_NAME"]=="127.0.0.1") {
            $ip=rtrim(file_get_contents("http://things2do.ws/extip.php"));
        }
        curl_setopt($this->ch, CURLOPT_URL, $this->url."?key=$this->apikey&format=json&ip=$ip");
        $json=json_decode(curl_exec($this->ch), true);
        if ($upstreamCurl==false) {
            curl_close($this->ch);
        }
        $this->lat=$json['latitude'];
        $this->lon=$json['longitude'];
    }
}
?>