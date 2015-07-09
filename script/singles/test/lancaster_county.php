<?php
/**
 * Created by PhpStorm.
 * User: LucasWang
 * Date: 7/7/15
 * Time: 12:40 PM
 */

$url = "http://www.lcwc911.us/lcwc/lcwc/pubcad.htm";
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

$currentTime = time();

$page = preg_replace("@.*updatePanel@", "", $page);

$lines = explode("\n", $page);

foreach ($lines as $line) {
    if (!preg_match("@<tr class@", $line) && !preg_match("@<td [ic][dl]@", $line)) {
        continue;
    }

    if (preg_match("@_tdUnits|dxgvHeader_Glass@", $line)) {
        continue;
    }

    if (preg_match("@dxgvDataRow_Glass@", $line)) {
        $ctr = 0;

        $time = "";
        $description = "";
        $address = "";

        continue;
    }

    $line = preg_replace("@&nbsp;@", " ", $line);
    $line = preg_replace("@ +@", " ", $line);

    if (preg_match("@<td class=.dxgv.>@", $line)) {
        $ctr++;

        if ($ctr == 1) {
            // line contains incident number and time
            $time = preg_replace("@.*<br>@", "", $line);
            $time = preg_replace("@ *<.*@", "", $time);
            continue;
        }

        if ($ctr == 2) {
            // line contains description
            $description = preg_replace("@.*dxgv.>@", "", $line);
            $description = preg_replace("@ *<.td>.*@", "", $description);
            continue;
        }
    }

    if (preg_match("@tdLocation@", $line) && $ctr = 3) {
        $address = preg_replace("@.*dxgv.>@", "", $line);
        $address = preg_replace("@ *<.td>.*@", "", $address);
        $address = preg_replace("@<br>@i", ", ", $address);

        list($date_portion, $time_portion) = explode(" ", $time);

        list($month, $day, $year) = explode("/", $date_portion);
        list($hour, $minute, $second) = explode(":", $time_portion);

        $date = "$year/$month/$day";
        $hrMinSec = "$hour:$minute";
        $unixValue = strtotime($date) + strtotime($hrMinSec);
        $timestamp = date("l, F d, Y", strtotime($date));
        $timestamp = "$timestamp $hrMinSec -0800";


        $incident = [
            "State" => $state,
            "City" => "none",
            "County" => "lancaster",
            "Incident" => "none",
            "Description" => $description,
            "Unit" => "none",
            "latlng" => "none",
            "Primary Dispatcher #" => "Travis County FD",
            "Source" => $url,
            "Logo" => "none",
            "Address" => $address,
            "Timestamp" => $timestamp,
            "Epoch" => $unixValue,
        ];

        array_push($incidentList,$incident);
        echo "       $timestamp:  $description  $address\n";
    }
}
$generalInfo = [
    "curlWorking" => $curlWorking,
    "parseWorking" => $parseWorking,
    "agencyName" => "lancaster_county-PA"
];

array_push($incidentList,$generalInfo);
    ?>