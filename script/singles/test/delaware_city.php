<?php
/**
 * Created by PhpStorm.
 * User: LucasWang
 * Date: 7/6/15
 * Time: 2:39 PM

 */

$url = "http://www.dcfc15.com/incidents";
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

$page = preg_replace("@.*liverunlog.gif@", "", $page);
$page = preg_replace("@pagingTabsCarr.*@", "", $page);

$lines = explode("\n", $page);

foreach ($lines as $line) {

    if (preg_match("@class=.sch3c.>@", $line)) {
        $ctr = 1;
        $date_time = preg_replace("@.*sch3c.>@", "", $line);
        $date_time = preg_replace("@</div>.*@", "", $date_time);
        continue;
    }

    if (preg_match("@Nature:@", $line) && $ctr == 1) {
        $ctr++;
        continue;
    }

    if (preg_match("@class=.gbr.@", $line) && $ctr == 2) {
        $ctr++;
        continue;
    }

    if ($ctr == 3) {
        $ctr++;
        $description = trim($line);
        $address = "";
        continue;
    }

    if (preg_match("@City:|Address:|Location:@", $line) && $ctr == 4) {
        $ctr++;
        continue;
    }

    if (preg_match("@class=.gbr.@", $line) && $ctr == 5) {
        $ctr++;
        continue;
    }

    if ($ctr == 6) {
        $ctr = 4;

        if (strlen($address) > 0) {
            $address .= ", ";
        }

        $address .= trim($line);

        continue;
    }

    if (preg_match("@/table@", $line) && $ctr > 2 && $ctr < 7) {
        $ctr = 7;
    }

    if ($ctr != 7) {
        continue;
    }

    $ctr++;

    $address = preg_replace("@  +@", " ", $address);

    $date_time = preg_replace("@,@", "", $date_time);
    list($x, $month, $day, $year, $x, $hour, $minute) = preg_split("@[/ :]@", $date_time);

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
    $hrMinSec = "$hour:$minute";
    $unixValue = strtotime($date) + strtotime($hrMinSec);
    $timestamp = date("l, F d, Y", strtotime($date));
    $timestamp = "$timestamp $hrMinSec -0800";

    $incident = [
        "State" => "DE",
        "City" => "none",
        "County" => "New Castle",
        "Incident" => "none",
        "Description" => $description,
        "Unit" => "none",
        "latlng" => "none",
        "Primary Dispatcher #" => "Delaware City Fire Company",
        "Source" => $url,
        "Logo" => "http://www.dcfc15.com/images/logo.png",
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
    "agencyName" => "delaware_city-DE"
];

array_push($incidentList,$generalInfo);
?>
