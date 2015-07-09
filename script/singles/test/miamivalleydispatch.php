<?php

//
//	Initialize curl
//

$ch = curl_init();

curl_setopt($ch, CURLOPT_VERBOSE, 0);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)");
curl_setopt($ch, CURLOPT_POST, 0);
$curlWorking = true;
$parseWorking = true;
$incidentList = [];

//
//	Retrieve page
//

curl_setopt($ch, CURLOPT_URL, "http://www.montgomery.miamivalleydispatch.org/");
$page = curl_exec($ch);

if (strlen($page) < 8000) {
	die();
}

if (! preg_match("@MainContent_dispatchcontrol1_ASPxPageControl2_grid_DXDataRow@", $page)) {
	die();
}

$page = preg_replace("@.*MainContent_dispatchcontrol1_ASPxPageControl2_grid_DXGroupRowExp0@", "", $page);
$page = preg_replace("@MainContent_dispatchcontrol1_ASPxPageControl2_grid_IADD.*@", "", $page);

$lines = explode("\n", $page);

foreach ($lines as $line) {
    if (!preg_match("@dxgvIndentCell dxgv@", $line)) {
        continue;
    }

    $line = preg_replace("@</td><td class=.dxgv.>@", "COLSEP", $line);
    $line = preg_replace("@</td><td class=.dxgv. style=.border-bottom-width:0px;.>@", "COLSEP", $line);

    $line = preg_replace("@</td><td class=.dxgv. style=.border-right-width:0px;.>.*@", "COLSEP", $line);
    $line = preg_replace("@</td><td class=.dxgv. style=.border-right-width:0px;border-bottom-width:0px;.>.*@", "COLSEP", $line);

    $line = preg_replace("@.*white-space:nowrap;border-left-width:0px;.>@", "", $line);
    $line = preg_replace("@.*white-space:nowrap;border-left-width:0px;border-bottom-width:0px;.>@", "", $line);

    $line = preg_replace("@COLSEP$@", "", $line);

    $line = preg_replace("@&amp;@", "&", $line);

//	echo ">> $line\n";

    list($date_time, $call, $calltype, $jurisdiction, $address) = explode("COLSEP", $line);

    list($date_string, $time_string) = explode(" ", $date_time);

    list($month, $day, $year) = explode("/", $date_string);

    if (strlen($month) < 2) {
        $month = "0$month";
    }
    if (strlen($day) < 2) {
        $day = "0$day";
    }

    $date = "$year-$month-$day";

    $date_time = "$date $time_string";
    list($hour, $minute) = explode(":", $time_string);

    $date = "$year-$month-$day";
    $hrMinSec = "$hour:$minute";
    $unixValue = strtotime($date) + strtotime($hrMinSec);
    $timestamp = date("l, F d, Y", strtotime($date));
    $timestamp = "$timestamp $hrMinSec -0800";

    $address = $address1 . $address2 . $address3;

    $incident = [
        "State" => "FL",
        "City" => "Miami",
        "County" => "Miami-Dade County",
        "Incident" => "none",
        "Description" => $calltype,
        "Unit" => "none",
        "latlng" => "none",
        "Primary Dispatcher #" => "Miami Valley Dispatch",
        "Source" => "http://www.montgomery.miamivalleydispatch.org/",
        "Logo" => "http://www.montgomery.miamivalleydispatch.org/Images/MONT-SHER-Medium.gif",
        "Address" => $address,
        "Timestamp" => $timestamp,
        "Epoch" => $unixValue,
    ];
    array_push($incidentList, $incident);



    //echo "parsed: $date_time, $calltype, $jurisdiction, $address\n";
}
$generalInfo = [
    "curlWorking" => $curlWorking,
    "parseWorking" => $parseWorking,
    "agencyName" => "Miami-Valley"
];
array_push($incidentList, $generalInfo);
//var_dump($incidentList);
?>
