<?php
if (!defined("THINGS2DO")){die("Unauthorized access");}

function _get_dist($from_x, $from_y, $to_x, $to_y) {
    return ($from_x - $to_x) * ($from_x - $to_x) + ($from_y - $to_y) * ($from_y - $to_y);
}

function getWeather($curl, $root, $location) {
    $lat=$location[0];
    $lon=$location[1];
    include "$root/application/APIs/metoffice.php";
    $met=new MetOfficeAPI($curl);
    $locations=$met->getLocationList();
    $closeLat = $locations[0]['latitude'];
    $closeLon = $locations[0]['longitude'];
    $closeDist = _get_dist($lat,$lon, $closeLat, $closeLon);
    $dLat = $closeLat - $lat;
    $dLon = $closeLon - $lon;
    if ($closeLat - $lat > 180) $dLon = 360 - ($closeLat - $lat);
    $id = 0;
    foreach ($locations as $location) {
        $tempx = $location['latitude'];
        $tempy = $location['longitude'];
        $dLat = $tempx - $lat;
        $dLon = $tempy - $lon;
        $tempDist=$dLat * $dLat + $dLon * $dLon;
        if ($tempDist < $closeDist) {
            $closeLat = $tempx;
            $closeLon = $tempy;
            $closeDist = $tempDist;
            $id = $location['id'];
        }
    }
    $weather=$met->getWeather($id);
    $rep=$weather['SiteRep']['DV']['Location']['Period'][0]['Rep'][0];
    switch ((int)$rep['W']) {
        case 0:
        case 1:
            $type="sun";
            break;
        case 2:
        case 3:
        case 4:
        case 5:
        case 6:
        case 7:
        case 8:
            $type="cloud";
            break;
        default:
            $type="rain";
    }
    return Array("precipitation"=>(int)$rep['Pp'], "temperature"=>(int)$rep['F'], "type"=>$type);
}
?>