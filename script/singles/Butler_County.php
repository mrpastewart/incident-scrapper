<?php
/**
 * Created by PhpStorm.
 * User: LucasWang
 * Date: 6/25/15
 * Time: 9:15 AM
 */
//Script return values
$curlWorking = true;
$parseWorking = true;
$state = "PA";
$incidentList = [];
//


//
$stan = array(
    "Strike Team" => "crime",
    "Aircraft Emergency" => "misc",
    "Aircraft Crash" => "misc",
    "Explosion" => "fire",
    "Pipeline Emergency" => "fire",
    "Transformer Explosion" => "fire",
    "Structure Fire" => "fire",
    "Vegetation Fire" => "fire",
    "Confirmed Structure Fire" => "fire",
    "Confirmed Vegetation Fire" => "fire",
    "Commercial Fire" => "fire",
    "Residential Fire" => "fire",
    "Working Commercial Fire" => "fire",
    "Working Residential Fire" => "fire",
    "Vehicle Fire" => "fire",
    "Full Assignment" => "fire",
    "Gas Main" => "hazmat",
    "Hazardous Condition" => "hazmat",
    "Bomb Threat" => "hazmat",
    "Hazmat Response" => "hazmat",
    "Multi Casualty" => "ems",
    "Flood Warning" => "weather",
    "Tornado Warning" => "weather",
    "Tsunami Warning" => "weather",
    "Rescue" => "rescue",
    "Cliff Rescue" => "rescue",
    "Confined Space" => "rescue",
    "Rope Rescue" => "rescue",
    "Technical Rescue" => "rescue",
    "Trench Rescue" => "rescue",
    "Water Rescue" => "rescue",
    "Expanded Traffic Collision" => "traffic",
    "Traffic Collision" => "traffic",
    "Traffic Collision Involving Train" => "traffic",
    "Wires Arcing" => "misc",
    "FIRE / UNKOWN" => "fire",
    "ROAD CLOSED" => "misc",
    "Wires Down" => "misc");
if(array_key_exists($str, $stan))
    $standardIncident =  $stan[$str];
$standardIncident =  "none";

//
//	Initialize curl
//
$url = "http://ems.co.butler.pa.us/publicwebcad/Summary.aspx";
//$url = "http://ems.co.butler.pa.us/publicwebcad.aspx";

$ch = curl_init();

curl_setopt($ch, CURLOPT_VERBOSE, 0);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)");
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

$page = preg_replace("@.*>Jurisdiction</th><th@", "", $page);
$page = preg_replace("@</table>.*@", "", $page);

$lines = explode("\n", $page);

$inTable = false;
$index = 0;
while($index < sizeof($lines) && !$inTable)
{
    if(preg_match("@id=\"gridIncidents\"@",$lines[$index]))
        $inTable = true;
    $index++;
}
//foreach ($lines as $line) {
for($i = $index; $i <sizeof($lines);$i++) {


    if (!preg_match("@</td><td style=@", $lines[$i]) || !$inTable) {
        continue;
    }
    $lines[$i] = preg_replace("/ +/", " ", $lines[$i]);

    list($f1, $f2, $f3, $f4, $f5, $f6, $f7) = explode("</td>", $lines[$i]);

    $date = preg_replace("/.*>/", "", $f2);
    $time = preg_replace("/.*>/", "", $f3);

    $description = trim(preg_replace("/.*>/", "", $f4));
    $address = trim(preg_replace("/.*>/", "", $f5));
    $community = trim(preg_replace("/.*>/", "", $f6));

    if (strlen($community) > 0) {
        $address .= ", $community";
    }

    $jurisdiction = trim(preg_replace("/.*>/", "", $f7));

    if (strlen($jurisdiction) > 0) {
        $address .= ", $jurisdiction";
    }

    list($month, $day, $year) = explode("/", $date);
    list($time_portion, $ampm_portion) = explode(" ", $time);
    list($hour, $minute) = explode(":", $time_portion);

    if ("$ampm_portion" == "PM") {
        if ($hour < 12) {
            $hour += 12;
        }
    } else if ($hour == 12) {
        $hour = 0;
    }

    $date = "$year-$month-$day";
    $hrMinSec = "$hour:$minute";
    $unixValue = strtotime($date) + strtotime($hrMinSec);
    $timestamp = date("l, F d, Y", strtotime($date));
    $timestamp = "$timestamp $hrMinSec -0800";
    //compiles the data in array
    if ($description != '' && $address != '')
        $incident = [
            "State" => $state,
            "City" => $community,
            "County" => "Butler County",
            "Incident" => $standardIncident,
            "Description" => $description,
            "Unit" => "none",
            "latlng" => "none",
            "Primary Dispatcher #" => "Butler County Emergency Services",
            "Source" => "http://ems.co.butler.pa.us/publicwebcad/Summary.aspx",
            "Logo" => "http://ems.co.butler.pa.us/publicwebcad/Images/PublicWebCADLogo.gif",
            "Address" => $address,
            "Timestamp" => $timestamp,
            "Unix Value" => $unixValue,
            "General"=>"none",
            "Number"=>"none"
        ];
    array_push($incidentList,$incident);
}
if(!$inTable)
    $parseWorking = false;
//return statements
$generalInfo = [
    "curlWorking" => $curlWorking,
    "parseWorking" => $parseWorking,
    "agencyName" => "butler_county"
];
array_push($incidentList,$generalInfo);


var_dump($incidentList);
return $incidentList
?>