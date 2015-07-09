<?php

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

curl_setopt($ch, CURLOPT_URL, "http://www.livecad.us/livecad/livecad.aspx?DeptID=OH-test");
$page = curl_exec($ch);

if (strlen($page) < 2000) {
    die();
}

$currentTime = time();

$page = preg_replace("@.*<th class=.GVHeader. scope=.col.>Time</th>@", "", $page);

$lines = explode("\n", $page);

foreach ($lines as $line) {
    if (!preg_match("@GVRow@", $line)) {
        continue;
    }

    if (!preg_match("@</td><td align=.left. style=@", $line)) {
        continue;
    }

    $line = preg_replace("@</td><td@", "COLSEP", $line);

    list($f1, $f2, $f3, $f4, $f5, $f6) = explode("COLSEP", $line);

    $f2 = preg_replace("@.*\">@", "", $f2);
    $address = preg_replace("@</a>.*@", "", $f2);

    $f3 = preg_replace("@.*\">@", "", $f3);
    $location = preg_replace("@</a>.*@", "", $f3);

    $f4 = preg_replace("@.*\">@", "", $f4);
    $f4 = preg_replace("@<a>@", "", $f4);
    $description = preg_replace("@</a>.*@", "", $f4);

    $f6 = preg_replace("@.*\">@", "", $f6);
    $f6 = preg_replace("@<a>@", "", $f6);
    $time = preg_replace("@</a>.*@", "", $f6);

    if (!preg_match("@:@", $time)) {
        continue;
    }

    list($hour, $minute) = explode(":", $time);
    $date = date("Y-m-d");
    $hrMinSec = "$hour:$minute";
    $unixValue = strtotime($date) + strtotime($hrMinSec);
    $timestamp = date("l, F d, Y", strtotime($date));
    $timestamp = "$timestamp $hrMinSec -0800";

    $incident = [
        "State" => "OH",
        "City" => $location,
        "County" => "none",
        "Incident" => "none",
        "Description" => $description,
        "Unit" => "none",
        "latlng" => "none",
        "Primary Dispatcher #" => "Test Fire Department",
        "Source" => "http://www.livecad.us/livecad/livecad.aspx?DeptID=OH-test",
        "Logo" => "none",
        "Address" => $address,
        "Timestamp" => $timestamp,
        "Epoch" => $unixValue,
    ];
        echo "       $timestamp:  $description  $address\n";

    array_push($incidentList, $incident);

    /*echo "parsed: \n";
    echo "\ttime = $time, hour = $hour, minute = $minute\n";
    echo "\taddress: $address\n";
    echo "\tlocation: $location\n";
    echo "\tdescription = $description\n";*/

    $time = mktime($hour, $minute, 00);

    if ($time > $currentTime) {
        $time -= 86400;
    }
}
$generalInfo = [
    "curlWorking" => $curlWorking,
    "parseWorking" => $parseWorking,
    "agencyName" => "Mecc"
];
array_push($incidentList, $generalInfo);
//var_dump($incidentList);
?>
