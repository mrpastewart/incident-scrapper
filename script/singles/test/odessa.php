<?php
/**
 * Created by PhpStorm.
 * User: LucasWang
 * Date: 7/8/15
 * Time: 10:02 AM
 */
$url = "http://www.ofc424.com/incidents";
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

$page = preg_replace("@.*run-log-info-list@", "", $page);
$page = preg_replace("@<div class=.pages..*@", "", $page);

$lines = explode("\n", $page);

foreach ($lines as $line) {
    if (preg_match("@<div class=.date.>@", $line)) {
        $ctr = 1;

        $line = preg_replace("@.*<div class=.date.>@", "", $line);
        $line = preg_replace("@</div>.*@", "", $line);
        $line = preg_replace("@,@", "", $line);
        list($dow, $month, $day, $year, $junk, $time_portion) = explode(" ", $line);
        continue;
    }

    if (preg_match("@<dt>Nature:</dt>@", $line) && $ctr == 1) {
        $ctr++;
        continue;
    }

    if (preg_match("@<dd>@", $line) && $ctr == 2) {
        $ctr++;

        $address = "";

        $line = preg_replace("@.*<dd>@", "", $line);
        $description = preg_replace("@</dd>.*@", "", $line);
//echo "description> $description\n";
        continue;
    }

    if (preg_match("@<dt>Address:</dt>|<dt>Location:</dt>|<dt>City:</dt>@", $line) && $ctr > 2) {
        if (preg_match("@<dd>", $line)) {
            $line = preg_replace("@.*<dd>@", "", $line);
            $line = preg_replace("@</dd>.*@", "", $line);
            $line = preg_replace("@<br.*@", "", $line);

            if (strlen($address) > 0) { $address .= ", "; }

            $address .= trim($line);
//echo "A> " . trim($line) . "\n";
//echo "address> $address\n";
        }

        $ctr = 4;
        continue;
    }

    if (! preg_match("@<dd>@", $line) && preg_match("@</dd>@", $line) && $ctr == 4) {
        $ctr++;

        if (strlen($address) > 0) {
            $address .= ", ";
        }

        $line = preg_replace("@.*<dd>@", "", $line);
        $line = preg_replace("@<br.*@", "", $line);
        $line = preg_replace("@</dd.*@", "", $line);
        $line = preg_replace("@</div.*@", "", $line);

        $address .= trim($line);
//echo "B> " . trim($line) . "\n";
//echo "address> $address\n";
        continue;
    }

    if (preg_match("@<dd>@", $line) && $ctr == 5) {
        $ctr++;
        continue;
    }
    /*
        if ($ctr == 6) {
            $ctr++;
            $line = preg_replace("@<br/>.*", "", $line);
            $line = trim($line);

            if (strlen($address) > 0) {
                $address .= ", ";
            }

    echo "E> $line\n";
            $address .= $line;
            continue;
        }
    */
    if (preg_match("@<div class=.sep2.@", $line) && $ctr > 3 && $ctr < 9) {
        $ctr = 9;
    }

    if ($ctr != 9) {
        continue;
    }

    $ctr++;

    $address = trim(preg_replace("@, *$@", "", $address));

    if (strlen($address) < 1) {
        continue;
    }

    if ($month == "January") { $month = "01"; }
    if ($month == "February") { $month = "02"; }
    if ($month == "March") { $month = "03"; }
    if ($month == "April") { $month = "04"; }
    if ($month == "May") { $month = "05"; }
    if ($month == "June") { $month = "06"; }
    if ($month == "July") { $month = "07"; }
    if ($month == "August") { $month = "08"; }
    if ($month == "September") { $month = "09"; }
    if ($month == "October") { $month = "10"; }
    if ($month == "November") { $month = "11"; }
    if ($month == "December") { $month = "12"; }

    $date = "$year-$month-$day";
    $hrMinSec = $time_portion;
    $unixValue = strtotime($date) + strtotime($hrMinSec);
    $timestamp = date("l, F d, Y", strtotime($date));
    $timestamp = "$timestamp $hrMinSec -0800";

    $incident = [
        "State" => "DE",
        "City" => "Odessa",
        "County" => "New Castle",
        "Incident" => "none",
        "Description" => $description,
        "Unit" => "none",
        "latlng" => "none",
        "Primary Dispatcher #" => "Odessa Fire Company",
        "Source" => $url,
        "Logo" => "http://www.ofc424.com/images/bg-logo.png",
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
    "agencyName" => "odessa-DE"
];

array_push($incidentList,$generalInfo);
?>