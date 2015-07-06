<?php
/**
 * Created by PhpStorm.
 * User: LucasWang
 * Date: 7/6/15
 * Time: 10:55 AM
 */

$url = "http://icema.indianacounty.org/911media/";
$state = "PA";
$curlWorking = true;
$parseWorking = true;
$incidentList = [];

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

if(curl_getinfo($ch,CURLINFO_HTTP_CODE) != 200)
{
    $curlWorking = false;
}

$currentTime = time();

$page = ereg_replace(".*Details  </th></tr>", "", $page);

$lines = explode("<tr>", $page);

foreach ($lines as $line) {
    if (!preg_match("@<td@", $line)) {
        continue;
    }

    if (preg_match("@<td>Report Current</td><td>&nbsp</td><td>Report Current</td><td>&nbsp</td>@", $line)) {
        continue;
    }

    $line = preg_replace("@</td></tr>.*@", "", $line);

    $line = preg_replace("@</td><td>@", "COLSEP", $line);
    $line = preg_replace("@ *COLSEP *@", "COLSEP", $line);

    list($f1, $f2, $f3, $f4, $f5, $f6, $f7, $f8, $f9) = explode("COLSEP", $line);

    $f1 = preg_replace("@.*<td>@", "", $f1);
    $date = preg_replace("@<.*@", "", $f1);

    $time = $f2;

    $description = $f3;

    $address = $f4;
    $city = $f5;

    list($month, $day) = explode("/", $date);

    $year = date("Y");


    $date = "$year-$month-$day";
    $hrMinSec = $time;
    $unixValue = strtotime($date) + strtotime($hrMinSec);
    $timestamp = date("l, F d, Y", strtotime($date));
    $timestamp = "$timestamp $hrMinSec -0800";

    if ($description != '' && $address != '')
        $incident = [
            "State" => $state,
            "City" => $city,
            "County" => "Butler",
            "Incident" => "none",
            "Description" => $description,
            "Unit" => "none",
            "latlng" => "none",
            "Primary Dispatcher #" => "Butler County Emergency Services",
            "Source" => "http://ems.co.butler.pa.us/publicwebcad/Summary.aspx",
            "Logo" => "http://ems.co.butler.pa.us/publicwebcad/Images/PublicWebCADLogo.gif",
            "Address" => $address,
            "Timestamp" => $timestamp,
            "Epoch" => $unixValue,
        ];
    array_push($incidentList,$incident);

    echo "        $timestamp:  $description  $address\n";

}

$generalInfo = [
    "curlWorking" => $curlWorking,
    "parseWorking" => $parseWorking,
    "agencyName" => "Indiana-PA"
];
array_push($incidentList,$generalInfo);

?>