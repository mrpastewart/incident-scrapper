<?php
/**
 * Created by PhpStorm.
 * User: LucasWang
 * Date: 7/1/15
 * Time: 11:57 AM
 */

$curlWorking = true;
$parseWorking = true;
$incidentList = [];
$state = "IL";

$table = "chicago_fire";
$url = "http://www.radioman911.com/pages/CAD/tests/jsontest1.php";
$email_url = "http://www.radioman911.com/pages/CAD/FFincidents2.html";
$agency_name = "Chicago Area Fire Alerts";

//
//	Initialize curl
//

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

$lines = explode("</tr>", $page);

foreach ($lines as $line) {
    $line = preg_replace("@ style='color:red;'@", "" , $line);
    $line = preg_replace("@.*</center></td><td>@", "" , $line);
    $line = preg_replace("@<td>@", "" , $line);

    list($date, $time, $city, $d, $description1, $description2, $address) = split("</td>", $line);

    $date = trim($date);
    $time = trim($time);
    $city = trim($city);
    $description1 = trim($description1);
    $description2 = trim($description2);
    $address = trim($address);

    if (strlen($description1) == 0) {
        continue;
    }

    list($month, $day) = split("-", $date);

    $year = date("Y");

    $t = mktime($hour, $minute, $second, $month, $day, $year);

    $now = time();

    if ($t > $now) {
        $year = $year - 1;
    }


    $hrMinSec = $time;
    $date = "$year/$month/$day";
    $unixValue = strtotime($date) + strtotime($hrMinSec);
    $timestamp = date("l, F d, Y", strtotime($date));
    $timestamp = "$timestamp $hrMinSec -0800";


    if (strlen($description1) > 0 && strlen($description2) > 0) {
        $description = "$description1, $description2";
    } else if (strlen($description1) == 0) {
        $description = $description2;
    } else {
        $description = $description1;
    }

    $address .= ", $city";

    $incident = [
        "State" => "IL",
        "City" => $city,
        "County" => "Cook",
        "Incident" => $standardIncident,
        "Description" => $description,
        "Unit" => "none",
        "latlng" => "none",
        "Primary Dispatcher #" => "Bowers Fire Co.",
        "Source" => $url,
        "Logo" => "none",
        "Address" => $address,
        "Timestamp" => $timestamp,
        "Epoch" => $unixValue,
    ];
    array_push($incidentList,$incident);


    echo "        $timestamp:  $description   $address\n";

}
$generalInfo = [
    "curlWorking" => $curlWorking,
    "parseWorking" => $parseWorking,
    "agencyName" => "chicagoFire-IL"
];

array_push($incidentList,$generalInfo);
var_dump($incidentList);
?>