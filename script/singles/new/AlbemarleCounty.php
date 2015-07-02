<?php
/**
 * Created by PhpStorm.
 * User: LucasWang
 * Date: 6/25/15
 * Time: 9:00 AM
 */

//Script return values
    $curlWorking = true;
    $state = "VA";
    $incidentList = [];

//
//	Initialize curl
//
$url = "http://warhammer.mcc.virginia.edu/fids/fids.php?display=PLAIN";

$ch = curl_init();

curl_setopt($ch, CURLOPT_VERBOSE, 0);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_TIMEOUT, 270);
curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)");
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch, CURLOPT_POST, 0);

//
//	Retrieve page
//

curl_setopt($ch, CURLOPT_URL, $url);

$page = curl_exec($ch);

if(curl_getinfo($ch,CURLINFO_HTTP_CODE) != 200)
{
    $curlWorking = false;

}


$currentTime = time();

$page = preg_replace("@.*In<br>Service@", "", $page);
$page = preg_replace("@Any number in.*@", "", $page);

$lines = explode("\n", $page);

foreach ($lines as $line) {
    if (preg_match("@<tr>@", $line)) {
        $ctr = 1;
        continue;
    }

    if (preg_match("@<td align=@", $line) && $ctr == 1) {
        $ctr++;
        $line = preg_replace("@</font.*@", "", $line);
        $date = preg_replace("@.*>@", "", $line);

        if (strlen($date) < 1) {
            $ctr = 99;
        }

        continue;
    }

    if (preg_match("@<td align=@", $line) && $ctr == 2) {
        $ctr++;
        continue;
    }

    if (preg_match("@<td align=@", $line) && $ctr == 3) {
        $ctr++;
        continue;
    }

    if (preg_match("@<td align=@", $line) && $ctr == 4) {
        $ctr++;
        $line = preg_replace("@</font.*@", "", $line);
        $address = preg_replace("@.*=new>@", "", $line);
        continue;
    }

    if (preg_match("@<td bgcolor@", $line) && $ctr == 5) {
        $ctr++;
        $line = preg_replace("@</font.*@", "", $line);
        $description = preg_replace("@.*>@", "", $line);
        continue;
    }

    if (preg_match("@<td align=@", $line) && $ctr == 6) {
        $ctr++;
        $line = preg_replace("@</font.*@", "", $line);
        $time = preg_replace("@.*>@", "", $line);
        continue;
    }

    if ($ctr != 7) {
        continue;
    }

    $ctr = 8;

    $line = html_entity_decode($line);
    $line = preg_replace("@ +@", " ", $line);

    list($month, $day, $year) = explode("/", $date);

    list($time_portion, $ampm_portion) = explode(" ", $time);

    $hrMinSec = $time;
    $date = "$year-$month-$day";
    $unixValue = strtotime($date) + strtotime($hrMinSec);
    $timestamp = date("l, F d, Y", strtotime($date));
    $timestamp = "$timestamp $hrMinSec -0800";

//
//Filter for standardizing descriptions
//
    $stan = array(
        //List of knowns
);
    if(array_key_exists($description, $stan))
        $standardIncident =  $stan[$description];
    else
        $standardIncident = "unknown";

    if ($description != '' && $address != '')
        $incident = [
            "State" => $state,
            "City" => "none",
            "County" => "Albemarle County",
            "Incident" => $standardIncident,
            "Description" => $description,
            "Unit" => "none",
            "latlng" => "none",
            "Primary Dispatcher #" => "Fire Incident Display System",
            "Source" => "http://warhammer.mcc.virginia.edu/fids/fids.php?display=PLAIN",
            "Logo" => "http://warhammer.mcc.virginia.edu/fids/images/coaseal2.gif",
            "Address" => $address,
            "Timestamp" => $timestamp,
            "Unix Value" => $unixValue,
        ];
    array_push($incidentList,$incident);
}
$generalInfo = [
    "curlWorking" => $curlWorking,
    "agencyName" => "albemarle_county"
];
array_push($incidentList,$generalInfo);

var_dump($incidentList);
?>