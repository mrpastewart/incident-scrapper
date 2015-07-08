<?php
/**
 * Created by PhpStorm.
 * User: LucasWang
 * Date: 7/7/15
 * Time: 11:16 AM
 */
$url = "http://www.urbanavfd.org/uvfd-wp/category/cad/";
$curlWorking = true;
$parseWorking = true;
$incidentList = [];
$state = "MD";

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

$page = preg_replace("@.*Archive for CAD@", "", $page);
$page = preg_replace("@Older posts.*@", "", $page);

$lines = explode("\n", $page);

foreach ($lines as $line) {
    if (preg_match("@Posted on@", $line)) {
        $ctr = 1;

        $line = preg_replace("@</span></a><span class=.byline.>.*@", "", $line);
        $line = preg_replace("@.*title=.@", "", $line);
        $line = preg_replace("@. rel=.bookmark.><span class=.timestamp updated.>@", " ", $line);

        list($time_portion, $ampm_portion, $date_portion) = preg_split("@ @", $line);
        continue;
    }

    if (preg_match("@<div class=.post-entry.>@", $line) && $ctr == 1) {
        $ctr++;
        $s = "";
        continue;
    }

    if (preg_match("@</div>@", $line) && $ctr == 2) {
        $ctr++;
        continue;
    }

    if ($ctr == 2) {
        $s .= " $line";
    }

    if ($ctr != 3) {
        continue;
    }

    $ctr++;

    $s = preg_replace("@<p>@", " ", $s);
    $s = preg_replace("@</p>@", " ", $s);

    $s = trim($s);

    $s = html_entity_decode($s);
    $s = preg_replace("@ +@", " ", $s);

    $description = $s;

    list($month, $day, $year) = explode("/", $date_portion);

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

    $incident = [
        "State" => "TX",
        "City" => "Urbana",
        "County" => "Frederick ",
        "Incident" => "none",
        "Description" => $description,
        "Unit" => "none",
        "latlng" => "none",
        "Primary Dispatcher #" => "Urbana Volunteer Fire & Rescue",
        "Source" => $url,
        "Logo" => "http://www.urbanavfd.org/uvfd-wp/wp-content/uploads/2013/10/uvfd-logo7.png",
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
    "agencyName" => "urbana-MD"
];

array_push($incidentList,$generalInfo);
?>