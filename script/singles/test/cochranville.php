<?php
/**
 * Created by PhpStorm.
 * User: LucasWang
 * Date: 7/7/15
 * Time: 1:41 PM
 */
$url = "http://cochranvillefire.com/runlog.cfm";
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

if(curl_getinfo($ch,CURLINFO_HTTP_CODE) != 200)
{
    $curlWorking = false;
}

$currentTime = time();

$page = preg_replace("@.*Live Run Log@", "", $page);
$page = preg_replace("@Displaying.*@", "", $page);

$lines = explode("\n", $page);

foreach ($lines as $line) {
    if (preg_match("@ class=.date.>@", $line)) {
        $ctr = 1;
        $line = preg_replace("@</em>.*@", "", $line);
        $line = preg_replace("@</div>.*@", "", $line);
        $line = preg_replace("@.*>@", "", $line);
        $line = preg_replace("@,@", "", $line);
        list($dow, $month, $day, $year, $junk, $time_portion) = explode(" ", $line);
        $address = "";
        continue;
    }

    if (preg_match("@>Nature:<@", $line) && $ctr == 1) {
        $ctr++;
        continue;
    }

    if (preg_match("@<dd>@", $line) && $ctr == 2) {
        $ctr++;

        $line = preg_replace("@</dd>.*@", "", $line);
        $line = preg_replace("@.*<dd>@", "", $line);
        $description = preg_replace("@.*Nature: *@", "", $line);

        continue;
    }

    if (preg_match("@<address>@", $line) && $ctr == 3) {
        $ctr = 4;
    }

    if (preg_match("@>Location:|>City:|>Address:@", $line) && $ctr == 3) {
        $ctr++;
        continue;
    }

    if (preg_match("@<dd>@", $line) && $ctr == 4) {
        $ctr = 3;

        $line = preg_replace("@</address>.*@", "", $line);
        $line = preg_replace("@</dd>.*@", "", $line);

        $line = preg_replace("@.*<address>@", "", $line);
        $line = preg_replace("@.*<dd>@", "", $line);

        if (strlen($line) > 0) {
            if (strlen($address) > 0) {
                $address .= "; ";
            }

            $address .= $line;
        }

        continue;
    }

    if (preg_match("@</li>@", $line) && $ctr == 3) {
        $ctr++;
    }

    if ($ctr != 4) {
        continue;
    }

    $description = preg_replace("@  +@", " ", $description);
    $address = preg_replace("@  +@", " ", $address);

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

    $date = "$year/$month/$day";
    $hrMinSec = $time_portion;
    $unixValue = strtotime($date) + strtotime($hrMinSec);
    $timestamp = date("l, F d, Y", strtotime($date));
    $timestamp = "$timestamp $hrMinSec -0800";

    $incident = [
        "State" => "DE",
        "City" => "Chocranville",
        "County" => "Lancaster",
        "Incident" => "none",
        "Description" => $description,
        "Unit" => "none",
        "latlng" => "none",
        "Primary Dispatcher #" => "Chochranville 27 Fire Company",
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
    "agencyName" => "cochranville-DE"
];

array_push($incidentList,$generalInfo);
?>