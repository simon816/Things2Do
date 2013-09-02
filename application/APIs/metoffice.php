<?php
if (!defined("THINGS2DO")){die("Unauthorized access");}

class MetOfficeAPI {
    private $url;
    private $apikey;
    private $ch;

    public function __construct($ch=null) {
        $this->url="http://datapoint.metoffice.gov.uk/public/data/val/wxfcs/all/json/";
        $this->apikey=getkey('metoffice');
        $this->ch=$ch;
    }
    public function getLocationList() {
        $data=$this->get_json("sitelist");
        return $data['Locations']['Location'];
    }
    public function getWeather($locid, $res="3hourly") {
        $data=$this->get_json("$locid", Array("res"=>$res));
        return $data;
    }
    private function get_json($url, $data=Array()) {
        $data['key']=$this->apikey;
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
        return json_decode(mb_convert_encoding($json, "UTF-8"), true);
    }
}
?>