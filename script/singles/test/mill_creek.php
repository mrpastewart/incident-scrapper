<?php
/**
 * Created by PhpStorm.
 * User: LucasWang
 * Date: 7/7/15
 * Time: 11:39 PM
 */
$url = "http://www.millcreekfireco.org/incidents";
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

if (strlen($page) < 200) {
    die();
}

$currentTime = time();

$page = preg_replace("@.*run-log-info-list@", "", $page);
$page = preg_replace("@<div class=.pages..*@", "", $page);

$lines = explode("\n", $page);

foreach ($lines as $line) {
    if (preg_match("@<em class=.date.>@", $line)) {
        $ctr = 1;

        $line = preg_replace("@.*<em class=.date.>@", "", $line);
        $line = preg_replace("@</em>.*@", "", $line);
        $line = preg_replace("@,@", "", $line);
        list($dow, $month, $day, $year, $junk, $time_portion) = explode(" ", $line);
        continue;
    }

    if (preg_match("@<dt>Nature:</dt>@", $line) && $ctr == 1) {
        $ctr++;

        $address = "";

        if (preg_match("@<dt>Location:</dt>@", $line)) {
            $address = preg_replace("@.*<dt>Location.*<dd>@", "", $line);
            $address = preg_replace("@</dd>.*@", "", $address);
            $address = preg_replace("@<br/>.*@", "", $address);

            $line = preg_replace("@<dt>Location:.*@", "", $line);
        }

        $line = preg_replace("@.*<dd>@", "", $line);
        $description = preg_replace("@</dd>.*@", "", $line);
        $description = preg_replace("@<br/>.*@", "", $description);

        continue;
    }

    if (preg_match("@<dt>Address:</dt>|<dt>City:</dt>@", $line) && $ctr == 2) {
        $ctr++;

        if (!(preg_match("@<dd>@", $line) && !preg_match("@</dd>@", $line))) {
            $ctr++;
        }

        $line = preg_replace("@.*<dd>@", "", $line);
        $line = preg_replace("@</dd>.*@", "", $line);
        $line = preg_replace("@<br/>.*@", "", $line);

        if (strlen($address) > 0) {
            $address .= ", ";
        }

        $address .= trim($line);

        continue;
    }

    if (preg_match("@</dd>@", $line) && $ctr == 3) {
        $line = preg_replace("@</dd>.*@", "", $line);

        if (strlen($address) > 0) {
            $address .= ", ";
        }

        $address .= trim($line);

        $ctr++;
    }

    if ($ctr != 4) {
        continue;
    }

    $ctr++;

    $address = trim(preg_replace("@, *$@", "", $address));

    if (strlen($address) < 1) {
        continue;
    }

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
        "State" => "DE",
        "City" => "Wilmington",
        "County" => "New Castle",
        "Incident" => "none",
        "Description" => $description,
        "Unit" => "none",
        "latlng" => "none",
        "Primary Dispatcher #" => "Mill Creek Fire Company",
        "Source" => $url,
        "Logo" => "http://www.millcreekfireco.org/images/promo-img.jpg",
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
    "agencyName" => "mill_creek-DE"
];

array_push($incidentList,$generalInfo);
?>