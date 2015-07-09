<?php
/**
 * Created by PhpStorm.
 * User: Srivatsav
 * Date: 7/7/2015
 * Time: 8:04 PM
 */

$url = "http://webapp.montcopa.org/eoc/cadinfo/livecad.asp?print=yes";
$agency = "Montgomery County, Pennsylvania";
$curlWorking = true;
$parseWorking = true;
$incidentList = [];



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

echo "Retrieving $url\n";
$page = curl_exec($ch);
echo "Received " . strlen($page) . " bytes\n";

if (strlen($page) < 200) {
    die();
}

$currentTime = time();

$page = preg_replace("@.*every 4 minutes@", "", $page);
$page = preg_replace("@Print this list.*@", "", $page);
$page = preg_replace("@</tr>@", "\n", $page);

$lines = explode("\n", $page);

foreach ($lines as $line) {
    if (!preg_match("@<tr>@", $line) || preg_match("@Incident Location@", $line)) {
        continue;
    }

    $line = html_entity_decode($line);

    if (preg_match("@type=Traffic@", $line)) {
        list($f1, $f2, $x, $f3, $f4, $f5, $f6, $f7) = explode("</td>", $line);
    } else {
        list($f1, $f2, $f3, $f4, $f5, $f6, $f7) = explode("</td>", $line);
    }

    $description = preg_replace("@</font>.*@", "", $f2);
    $description = preg_replace("@.*'[0-9]+'>@", "", $description);

    $address = preg_replace("@</a>.*@", "", $f3);
    $address = preg_replace("@.*[0-9]+'>@", "", $address);

    $city = preg_replace("@</font>.*@", "", $f4);
    $city = preg_replace("@.*'[0-9]+'>@", "", $city);

    if (strlen($city) > 0) {
        $address .= ", $city";
    }

    $address = preg_replace("@<br>@", " ", $address);

    $date_time = preg_replace("@</font>.*@", "", $f5);
    $date_time = preg_replace("@.*'[0-9]+'>@", "", $date_time);
    $date_time = preg_replace("@<br>@", " ", $date_time);
    list($date, $time) = explode(" ", $date_time);
    list($hour, $minute, $second) = explode(":", $time);
    $hrMinSec = "$hour:$minute";
    $unixValue = strtotime($date) + strtotime($hrMinSec);
    $timestamp = date("l, F d, Y", strtotime($date));
    $timestamp = "$timestamp $hrMinSec -0800";


    $incident = [
        "State" => "PA",
        "City" => "none",
        "County" => "Montgomery County",
        "Incident" => "none",
        "Description" => $description,
        "Unit" => "none",
        "latlng" => "none",
        "Primary Dispatcher #" => "Emergency Operations Center in Eagleville",
        "Source" => $url,
        "Logo" => "none",
        "Address" => $address,
        "Timestamp" => $timestamp,
        "Epoch" => $unixValue,
    ];
    array_push($incidentList, $incident);

    /*echo "parsed: \n";
    echo "\tdate: $date_time\n";
    echo "\taddress: $address\n";
    echo "\tdescription: $description\n";*/
}
$generalInfo = [
    "curlWorking" => $curlWorking,
    "parseWorking" => $parseWorking,
    "agencyName" => "Montgomery"
];
array_push($incidentList, $generalInfo);
var_dump($incidentList);
?>