<?php
/**
 * Created by PhpStorm.
 * User: LucasWang
 * Date: 7/7/15
 * Time: 9:09 PM
 */
$url = "http://www.evfd75.com/incidents";
$curlWorking = true;
$parseWorking = true;
$incidentList = [];
$state = "DE";
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

if(curl_getinfo($ch,CURLINFO_HTTP_CODE) != 200) {
    $curlWorking = false;
}

$currentTime = time();

$page = preg_replace("@.*Live Run Log@", "", $page);
$page = preg_replace("@<div class=.paging.*@", "", $page);

$lines = explode("\n", $page);

foreach ($lines as $line) {
    if (preg_match("@<center><big>@", $line)) {
        $ctr = 1;

        $line = preg_replace("@.*<center><big>@", "", $line);
        $line = preg_replace("@</big>.*@", "", $line);
        $line = preg_replace("@,@", "", $line);
        list($dow, $month, $day, $year, $junk, $time_portion) = preg_split("@ @", $line);
        continue;
    }

    if (preg_match("@<strong>Nature:@", $line) && $ctr == 1) {
        $ctr++;
        continue;
    }

    if (preg_match("@<td class=@", $line) && $ctr == 2) {
        continue;
    }

    if ($ctr == 2) {
        $ctr++;
        $description = trim($line);
        $address = "";
    }

    if (preg_match("@[ \t]+</center>@", $line)) {
        $ctr = 5;
    }

    if (preg_match("@<strong>City:|<strong>Location:@", $line) && $ctr >= 3) {
        $ctr = 4;
        continue;
    }

    if (preg_match("@</td>@", $line) && $ctr == 4) {
        $ctr = 3;
        continue;
    }

    if (preg_match("@<td class=@", $line) && $ctr == 4) {
        continue;
    }

    if ($ctr == 4) {
        if (strlen($address) > 0) {
            $address .= "; ";
        }

        $line = preg_replace("@<br/>@", "", $line);

        $address .= trim($line);
    }

    if ($ctr != 5) {
        continue;
    }

    $ctr++;

    if ($month == "January") {
        $month = "01";
    }
    if ($month == "February") {
        $month = "02";
    }
    if ($month == "March") {
        $month = "03";
    }
    if ($month == "April") {
        $month = "04";
    }
    if ($month == "May") {
        $month = "05";
    }
    if ($month == "June") {
        $month = "06";
    }
    if ($month == "July") {
        $month = "07";
    }
    if ($month == "August") {
        $month = "08";
    }
    if ($month == "September") {
        $month = "09";
    }
    if ($month == "October") {
        $month = "10";
    }
    if ($month == "November") {
        $month = "11";
    }
    if ($month == "December") {
        $month = "12";
    }

    $date = "$year-$month-$day";
    $hrMinSec = $time_portion;
    $unixValue = strtotime($date) + strtotime($hrMinSec);
    $timestamp = date("l, F d, Y", strtotime($date));
    $timestamp = "$timestamp $hrMinSec -0800";

    $incident = [
        "State" => $state,
        "City" => "none",
        "County" => "Sussex",
        "Incident" => "none",
        "Description" => $description,
        "Unit" => "none",
        "latlng" => "none",
        "Primary Dispatcher #" => "Ellendale Fire Co.",
        "Source" => $url,
        "Logo" => "http://www.evfd75.com/images/layout/banner.jpg",
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
    "agencyName" => "ellendale-DE"
];

array_push($incidentList,$generalInfo);
?>