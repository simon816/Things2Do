<?php
if (!defined("THINGS2DO")){die("Unauthorized access");}

include "$root/application/database.php";
include "$root/application/positioningfuncs.php";

class Suggest {
    function __construct() {
        $this->db=new MYSQL_DB();
    }

    public function staticPlaces($lat,$lon) {
        $bounding_box = getBoundingBox($lat,$lon,30);
        $this->db->select('*','places');
        $this->db->where('lat', '>=', $bounding_box[0]);
        $this->db->where('lat', '<=', $bounding_box[1]);
        $this->db->where('lon', '>=', $bounding_box[2]);
        $this->db->where('lon', '<=', $bounding_box[3]);
        $result = $this->db->_();
        return $result;
    }

    public function makeSuggestion($city,$lat,$lon) {
        $places = $this->staticPlaces($lat,$lon);
        $b = array();
        foreach($places as $place) {
            $c = (object)array();
            $place=(object)$place;
            $place->distance = calculateDistance($lat,$lon,$place->lat,$place->lon);
            $place->distance_adjusted = ($place->distance/1000) / 30;
            unset($place->source);
            unset($place->expiry);
            $place->score = NULL;

            $data = (object) array();
            // add events
            $id = $place->id;	
            $query = $this->db->select('*', 'events')->where('placeid','=',$id)->_();
            
            if(count($query) > 0) {
                @$events = $query;
                @$data->events = $events;
            }
            
            // limit images

            $images = explode(",",$place->images);
            unset($place->images);
            if(count($images ) > 0) {
                
                $image = $images[0];
                $place->image = $image;
            }

        // add products
            $hasProducts = array(6,7,8);
            if(in_array($place->type,$hasProducts)) {
                //$data->product = $this->getAProduct($place->type);
            }

            $c = $place;
            $c->data = $data;
            $b[] = $c;

        }
        $places = $b;


        return $b;
    }
}
?>
