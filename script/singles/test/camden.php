<?php
/**
 * Created by PhpStorm.
 * User: LucasWang
 * Date: 7/7/15
 * Time: 12:09 PM
 */
$url = "http://www.cwfc41.com/incidents";
$curlWorking = true;
$parseWorking = true;
$incidentList = [];
$state = "WY";

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

$page = preg_replace("@.*>Live Run Log<@", "", $page);
$page = preg_replace("@<div class=.paging.*@", "", $page);

$lines = explode("\n", $page);

foreach ($lines as $line) {
    if (preg_match("@<h3 class.*innerpg@", $line)) {
        $ctr = 2;

        $line = preg_replace("@.*headings.>@", "", $line);
        $line = preg_replace("@</h3>@", "", $line);

        $line = trim(preg_replace("@,@", "", $line));
        list($dow, $month, $day, $year, $junk, $hour, $minute) = preg_split("@[ :]@", $line);

        continue;
    }

    if (preg_match("@Nature:@", $line) && $ctr == 2) {
        $ctr++;
        $line = preg_replace("@.*Nature: *@", "", $line);
        $description = preg_replace("@.*strong> *@", "", $line);
        $description = preg_replace("@<br/>.*@", "", $description);
        $address = "";
        continue;
    }

    if (preg_match("@Location:|Address:|City:@", $line) && $ctr == 3) {
        $line = preg_replace("@.*Location: *@", "", $line);
        $line = preg_replace("@.*Address: *@", "", $line);
        $line = preg_replace("@.*City: *@", "", $line);

        $line = preg_replace("@.*strong> *@", "", $line);
        $line = preg_replace("@<br/>.*@", "", $line);

        if (strlen($address) > 0) {
            $address .= "; ";
        }

        $address .= $line;
        continue;
    }

    if (preg_match("@</p>@", $line) && $ctr == 3) {
        $ctr++;
    }

    if ($ctr != 4) {
        continue;
    }

    $ctr++;

    $address = preg_replace("@  +@", " ", $address);

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

    $timestamp = "$year-$month-$day $hour:$minute";
    $address = trim($address);

    $incident = [
        "State" => "TX",
        "City" => "Austin",
        "County" => "Travis",
        "Incident" => $standardIncident,
        "Description" => $description,
        "Unit" => "none",
        "latlng" => "none",
        "Primary Dispatcher #" => "Travis County FD",
        "Source" => $url,
        "Logo" => "https://pbs.twimg.com/profile_images/3158256826/f9f34aa109a26aa107a2a9edb85a201b_normal.jpeg",
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
    "agencyName" => "austin-TX"
];

array_push($incidentList,$generalInfo);
?>