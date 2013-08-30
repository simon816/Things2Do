<?php
if (!defined("THINGS2DO")){die("Unauthorized access");}
class Condition {
    public $weather;
    public $_time;
    public $position;
    
    function __construct(){}
}

class Position {
    public $lat;
    public $lon;

    function __construct($lat, $lon) {
        $this->lat = $lat;
        $this->lon = $lon;
    }
}
class FakeWeather {
    public $precipitation = 25;
    public $temperature = 15;
    public $type = "cloud";
}

function getResults($class, $root) {

    include "$root/application/searchdecoder.php";
    include "$root/application/suggest.php";
	include "$root/application/oldscore.php";

	$conditions = new Condition();
	$conditions->_time = (float)date("G", time()) + ((float)date("i", time()))/60;
	$conditions->position = new Position($class->location[0],$class->location[1]);
    $conditions->weather=new FakeWeather(); //weather not yet implemented
	//get string category list -  $categories from searchdecoder.php, parameters (searchstring)
	$searchstringresults = new Search($class->query);
	$categories = $searchstringresults->categoryanalysis;
	//get results from apis - $activities from activities.php, parameters ()
    $suggest=new Suggest();
	$activities = $suggest->makeSuggestion('', $conditions->position->lat, $conditions->position->lon);
	//calculate score - $activities from score.php, parameters, (activities, conditions, categories)
	$activities = calculateScores($activities, $conditions, $categories);
	//order results
	if ($activities != -1)
	{
		usort($activities, function($a, $b) {
				if( $a->score < $b->score) {
					return 1;
				} else {
					return 0;
				}
			});
	}
	//return results
	return $activities;

}
?>