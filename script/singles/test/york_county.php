<?php
/**
 * Created by PhpStorm.
 * User: LucasWang
 * Date: 7/7/2015
 * Time: 1:27 PM
 */
$url = "http://www.ycdes.org/webcad/";
$curlWorking = true;
$parseWorking = true;
$incidentList = [];
$state = "PA";
//
//	Initialize curl
//

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

if(curl_getinfo($ch,CURLINFO_HTTP_CODE) != 200) {
    $curlWorking = false;
}

/*
$page = preg_replace("@.*<table ", "", $page);
$page = preg_replace("@</table>.*", "", $page);
*/
$lines = explode("\n", $page);

foreach ($lines as $line) {
    if (!preg_match("@<td><@", $line)) {
        continue;
    }

    $line = preg_replace("@</td><td>@", "COLSEP", $line);
    $line = preg_replace("@&nbsp;@", " ", $line);

//echo "\n*** $line\n";

    list($junk, $timestamp, $box, $description, $address1, $address2, $address3, $cross_street, $cross_street, $cross_street, $location) = preg_split("@COLSEP@", $line);

    $address1 = preg_replace("@^ +@", "", $address1);
    $address2 = preg_replace("@^ +@", "", $address2);
    $address3 = preg_replace("@^ +@", "", $address3);

    $address1 = preg_replace("@ +$@", "", $address1);
    $address2 = preg_replace("@ +$@", "", $address2);
    $address3 = preg_replace("@ +$@", "", $address3);

    $location = preg_replace("@</td.*@", "", $location);

    if (strlen($address1) > 0) {
        $address2 = " " . $address2;
    }
    if (strlen($address3) > 0) {
        $address2 = $address2 . " ";
    }

    $time = preg_replace("@.* @", "", $timestamp);
    $date = preg_replace("@ .*@", "", $timestamp);

//	list($day, $month, $year) = split("-", $date);
    list($month, $day, $year) = preg_split("@-@", $date);

    $address = $address1 . $address2 . $address3;

    $date = "$year-$month-$day";
    $hrMinSec = $time;
    $unixValue = strtotime($date) + strtotime($hrMinSec);
    $timestamp = date("l, F d, Y", strtotime($date));
    $timestamp = "$timestamp $hrMinSec -0800";

    $incident = [
        "State" => "PA",
        "City" => "none",
        "County" => "York",
        "Incident" => "none",
        "Description" => $description,
        "Unit" => "none",
        "latlng" => "none",
        "Primary Dispatcher #" => "York County 911 - Live Incident Status",
        "Source" => $url,
        "Logo" => "http://www.ycdes.org/webcad/images/livestatuslogo.jpg",
        "Address" => $address,
        "Timestamp" => $timestamp,
        "Epoch" => $unixValue,
    ];

    array_push($incidentList,$incident);
    echo "       $timestamp:  $description  $address\n";
}
$generalInfo = [
    "curlWorking" => $curlWorking,
    "parseWorking" => $parseWorking,
    "agencyName" => "york_county-PA"
];

array_push($incidentList,$generalInfo);
?>