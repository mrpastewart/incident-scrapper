<?php
/**
 * Created by PhpStorm.
 * User: Srivatsav
 * Date: 7/6/2015
 * Time: 1:27 PM
 */


//
//	Initialize curl
//


$curlWorking = true;
$parseWorking = true;
$incidentList = [];

$ch = curl_init();

curl_setopt($ch, CURLOPT_VERBOSE, 0);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)");
curl_setopt($ch, CURLOPT_POST, 0);

//
//	Retrieve page
//

curl_setopt($ch, CURLOPT_URL, "http://www.ycdes.org/webcad/");
$page = curl_exec($ch);

if (strlen($page) < 2000) {
    die();
}
/*
$page = ereg_replace(".*<table ", "", $page);
$page = ereg_replace("</table>.*", "", $page);
*/
$lines = explode("\n", $page);

foreach ($lines as $line) {
    if (!preg_match("@<td><@", $line)) {
        continue;
    }

    $line = preg_replace("@</td><td>@", "COLSEP", $line);
    $line = preg_replace("@&nbsp;@", " ", $line);

//echo "\n*** $line\n";

    list($junk, $timestamp, $box, $description, $address1, $address2, $address3, $cross_street, $cross_street, $cross_street, $intersection, $location) = explode("COLSEP", $line);

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
    //echo $time;
    //sleep(30);
    list($hour, $minute) = explode(":", $time);
    list($month, $day, $year) = explode("-", $date);
    $timestamp = "$year-$month-$day $time";

    $date = "$year-$month-$day";
    $hrMinSec = "$hour:$minute";
    $unixValue = strtotime($date) + strtotime($hrMinSec);
    $timestamp = date("l, F d, Y", strtotime($date));
    $timestamp = "$timestamp $hrMinSec -0800";

    $address = $address1 . $address2 . $address3;

    $incident = [
        "State" => "PA",
        "City" => "none",
        "County" => "York County",
        "Incident" => "none",
        "Description" => $description,
        "Unit" => "none",
        "latlng" => "none",
        "Primary Dispatcher #" => "York County 911",
        "Source" => "http://www.ycdes.org/webcad/",
        "Logo" => "none",
        "Address" => $address,
        "Timestamp" => $timestamp,
        "Epoch" => $unixValue,
    ];
    array_push($incidentList, $incident);

    /*echo "parsed: \n";
    echo "\ttimestamp: $timestamp\n\tbox: $box\n\tdesc = $description\n";
    echo "\taddress = $address\n";
    echo "\tlocation = $location\n";*/
}
$generalInfo = [
    "curlWorking" => $curlWorking,
    "parseWorking" => $parseWorking,
    "agencyName" => "York_County"
];
array_push($incidentList, $generalInfo);
//var_dump($incidentList);
