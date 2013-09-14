<?php
if (!defined("THINGS2DO")){die("Unauthorized access");}

class Activities {

    private $db;
    private $ch;
    private $location;
    private $types;
    private $root;

    function __construct($db, $root, $ch=null) {
        $this->root=$root;
        include "$root/application/positioningfuncs.php";
        $this->db=$db;
        $this->ch=$ch;
    }

    public function setLocation($location) {
        $this->location=$location;
    }
    public function setTypes($types) {
        $this->types=$types;
    }
    public function getSuggestions() {
        if (!$this->location||!$this->types) {
            throw new Exception("Missing variable(s) location and/or types");
        }
        $places=$this->getCachedPlaces();
        if(count($places)<24) {
            $places=$this->getNewPlaces(true);
            $this->cachePlaces($places);
        }
        $outplaces=array();
        foreach ($places as $place) {
            $place['distance'] = calculateDistance($this->location[0],$this->location[1],$place['lat'],$place['lon'])/1000;
            unset($place['source']);
            unset($place['expiry']);
            $outplaces[]=$place;
        }
        return $outplaces;
        
    }

    private function getCachedPlaces() {
        $bounding_box = getBoundingBox($this->location[0],$this->location[1],SEARCH_RADIUS);
        $this->db->select('*','places')
          ->where('lat', '>=', $bounding_box[0])
          ->where('lat', '<=', $bounding_box[1])
          ->where('lon', '>=', $bounding_box[2])
          ->where('lon', '<=', $bounding_box[3]);
        return $this->db->_();
    }
    private function getNewPlaces($typelimit=false) {
        include_once "$this->root/application/APIs/foursquare.php";
        $fs=new FourSquareAPI($this->ch);
        $c=$this->db->select("catname, catid", "fscategories")->_();
        if (!$c) {
            $list=$fs->getCategoryList();
            $c=array();
            foreach ($list as $cat) {
                $d=Array("catname"=>$cat["name"], "catid"=>$cat["id"], "pluralname"=>$cat["pluralName"], "parentid"=>$cat["parent"]);
                $this->db->insert('fscategories', $d);
                $c[]=$d;
            }
        }
        $fs->setCategories($c);
        $fs->setLocation($this->location);
        $places=array();
        $typearray=Array(
            TYPE_BEACH=>"Beach",
            TYPE_CINEMA=>"Movie Theater",
            TYPE_FOOD=>"Food",
            TYPE_STADIUM=>"Stadium",
            TYPE_MALL=>"Mall",
            TYPE_RECORDSHOP=>"Record Shop",
            TYPE_BOOKSHOP=>"Bookstore",
            TYPE_VIDEOGAMES=>"Video Game Store",
            TYPE_AQUARIUM=>"Aquarium",
            TYPE_MUSEUM=>"Museum",
            TYPE_ZOO=>"Zoo",
            TYPE_BOWLING=>"Bowling Alley",
            TYPE_WATERPARK=>"Water Park",
            TYPE_ARTGALLERY=>"Art Gallery",
            TYPE_THEMEPARK=>"Theme Park",
            TYPE_PARK=>"Park",
            TYPE_SCENICPOINT=>"Scenic Lookout"
        );
        if ($typelimit==true) {
            // reduces the amount of requests sent to FourSquare api
            foreach ($typearray as $type=>$text) {
                if(!isset($this->types[$type])) {
                    unset($typearray[$type]);
                }
            }
        }
        foreach ($typearray as $type=>$catname) {
            $places=array_merge($places, $this->formatPlace($fs->get($catname, true), $type));
        }
        return $places;
    }
    private function formatPlace($data, $TYPE=TYPE_NULL) {
        $places=array();
        foreach($data as $placedata) {
            $place=array();
            $place['title'] = $placedata['name'];
            if ($placedata['images']) {
                $place['images']=implode(",", $placedata['images']);
            }
            else {
                $place['images']=null;
            }
            $place['type'] = $TYPE;
            $place['url'] = $placedata['canonicalUrl'];
            $place['lat'] = $placedata['location']['lat'];
            $place['lon'] = $placedata['location']['lng'];
            $place['sourceid'] = $placedata['id'];
            if(isset($placedata['location']['postalCode'])) {
                $place['postcode'] = $placedata['location']['postalCode'];
            }
            $places[] = $place;
        }
        return $places;
    }
    private function cachePlaces($places) {
        foreach($places as $place) {
            $place['fetchdate'] =  date("Y-m-d H:i:s");
            $place['expiry'] = date("Y-m-d H:i:s",(time()+(3600*7)));
            $query = $this->db->select("*", "places")->where("sourceid", "=", $place["sourceid"])->_();
            if(count($query) > 0) {
                $this->db->update('places',$place)->where("sourceid", "=", $place["sourceid"])->_();
            }
            else {
                $this->db->insert('places',$place)->_();
            }
        }
    }
}
?>