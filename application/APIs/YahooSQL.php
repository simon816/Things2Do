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
    public function get_results($jsonarray=null) {
        if (!$jsonarray){$jsonarray=$this->json;}
        $output=Array();
        if (isset($jsonarray['query'])) {
            $query=$jsonarray['query'];
            $results=$query['results'];
            if (isset($results['yctCategories'])) {
                foreach ($results['yctCategories']['yctCategory'] as $category) {
                    $category=$category;
                    if (isset($category['content'])) {
                        $output[$$category['content']]=(float)$category['score'];
                    }
                    else {
                        if (isset($output["tmp"])) {
                            $output[$category]=(float)$output["tmp"];
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
    public function do_contentanalysis_query($value) {
        $this->build_select_query("contentanalysis.analyze", "text", $value);
        $this->get_json();
        return $this->get_results();
    }
}
?>