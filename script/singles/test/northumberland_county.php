<?php
/**
 * Created by PhpStorm.
 * User: LucasWang
 * Date: 7/7/15
 * Time: 11:45 PM
 */
$url = "https://cad.norrycopa.net/Pages/Public/LiveIncidents.aspx";
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

$page = preg_replace("@AFA TEST.*@", "", $page);
$page = preg_replace("@.*lblPageRefreshed@", "", $page);

$lines = explode("\n", $page);

foreach ($lines as $line) {
    if (!(preg_match("@<td class@", $line) || preg_match("@tdLocation@", $line) || preg_match("@dxgvDataRow_Glass@", $line))) {
        continue;
    }

    $line = preg_replace("@&nbsp;@", " ", $line);
    $line = html_entity_decode($line);

    if (preg_match("@dxgvDataRow_Glass@", $line)) {
        $ctr = 1;
        continue;
    }

    if (preg_match("@<td class@", $line)) {
        if ($ctr == 1) {
            $ctr++;

            $date_time = preg_replace("@ *</td>.*@", "", $line);
            $date_time = preg_replace("@.*>@", "", $date_time);

            continue;
        }

        if ($ctr == 2) {
            $ctr++;

            $description = preg_replace("@ *</td>.*@", "", $line);
            $description = preg_replace("@.*\">@", "", $description);

            continue;
        }
    }

    if (!(preg_match("@tdLocation@", $line) && $ctr == 3)) {
        continue;
    }

    $address = preg_replace("@&nbsp;</td>.*@", "", $line);
    $address = preg_replace("@</td>.*@", "", $address);
    $address = preg_replace("@<br>@i", "; ", $address);
    $address = preg_replace("@ *; *@i", "; ", $address);
    $address = preg_replace("@.*>@", "", $address);

    list($date_portion, $time_portion) = explode(" ", $date_time);

    list($month, $day, $year) = explode("/", $date_portion);
    list($hour, $minute, $second) = explode(":", $time_portion);

    $date = "$year-$month-$day";
    $hrMinSec = "$hour:$minute";
    $unixValue = strtotime($date) + strtotime($hrMinSec);
    $timestamp = date("l, F d, Y", strtotime($date));
    $timestamp = "$timestamp $hrMinSec -0800";

    $incident = [
        "State" => "DE",
        "City" => "none",
        "County" => "Northumberland",
        "Incident" => "none",
        "Description" => $description,
        "Unit" => "none",
        "latlng" => "none",
        "Primary Dispatcher #" => "Northumberland County 911",
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