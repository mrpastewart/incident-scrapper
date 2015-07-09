<?php
/**
 * Created by PhpStorm.
 * User: LucasWang
 * Date: 7/8/15
 * Time: 2:07 PM
 */
$url = "http://68.236.194.223:7780/firstresponder/wc911/reclist.html";
$curlWorking = true;
$parseWorking = true;
$incidentList = [];
$state = "NJ";
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

if(curl_getinfo($ch,CURLINFO_HTTP_CODE) != 200) {
    $curlWorking = false;
}

$currentTime = time();

$page = preg_replace("@.*>TIME<@", "", $page);
$page = preg_replace("@</TABLE>.*@", "", $page);

$lines = explode("\n", $page);

foreach ($lines as $line) {
    if (!preg_match("@^<TR @", $line)) {
        continue;
    }

    list($f1, $f2, $f3, $f4, $f5, $f6, $f7) = preg_split("@</TD>@", $line);

    $description = preg_replace("@.*<TD>@", "", $f2);

    $additional = preg_replace("@.*<TD>@", "", $f3);
    $additional = preg_replace("@&nbsp.*@", "", $additional);

    if (strlen($additional) > 0) {
        $description .= " - $additional";
    }

    $city = preg_replace("@.*<TD>@", "", $f4);
    $city = preg_replace("@&nbsp.*@", "", $city);

    $address = preg_replace("@.*<TD>@", "", $f5);
    $address = preg_replace("@&nbsp.*@", "", $address);

    if (strlen($city) > 0) {
        $address .= ", $city";
    }

    $date_time = preg_replace("@.*<TD>@", "", $f6);
    $date_time = preg_replace("@&nbsp.*@", "", $date_time);
    $date_time = preg_replace("@/@", " ", $date_time);

    list($month, $day, $time) = preg_split("@ @", $date_time);

    $hour = preg_replace("@..$@", "", $time);
    $minute = preg_replace("@^..@", "", $time);

    $year = date("Y");

    $t = mktime($hour, $minute, $second, $month, $day, $year);

    $now = time();

    if ($t > $now) {
        $year = $year - 1;
    }

    $date = "$year-$month-$day";
    $hrMinSec = "$hour:$minute";
    $unixValue = strtotime($date) + strtotime($hrMinSec);
    $timestamp = date("l, F d, Y", strtotime($date));
    $timestamp = "$timestamp $hrMinSec -0800";

    $incident = [
        "State" => "Nj",
        "City" => "none",
        "County" => "Warren",
        "Incident" => "none",
        "Description" => $description,
        "Unit" => "none",
        "latlng" => "none",
        "Primary Dispatcher #" => "Warren County New Jersey",
        "Source" => $url,
        "Logo" => "none",
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
    "agencyName" => "magnolia-DE"
];

array_push($incidentList,$generalInfo);
?>