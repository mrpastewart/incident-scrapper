<?php
/**
 * Created by PhpStorm.
 * User: LucasWang
 * Date: 7/6/15
 * Time: 1:26 PM
 */
//Need to hardcode Timestamp
$url = "http://cadweb.wfpd.net/ActiveEvents.php";
$curlWorking = true;
$parseWorking = true;
$state = "TX";
$incidentList = [];


//
//	Initialize curl
//

$ch = curl_init();

//curl_setopt($ch, CURLOPT_VERBOSE, 1);
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

$page = preg_replace("@.*>City<@", "" , $page);
$page = preg_replace("@br clear.*@", "" , $page);

$lines = explode("\n", $page);

foreach ($lines as $line) {
    if (!preg_match("@^<td align=left>@", $line)) {
        continue;
    }

    list($f1, $f2, $f3, $f4, $f5, $f6, $f7, $f8, $f9) = explode("</td>", $line);

    $number_units = preg_replace("@.*left>@", "", $f5);

    if ($number_units < 1) {
        continue;
    }

    $description = preg_replace("@.*left>@", "", $f6);

    $address = preg_replace("@.*left>@", "", $f7);

    if ($address == ".") {
        $address = preg_replace("@.*left>@", "", $f8);
    } else {
        $address .= ", ";
        $address .= preg_replace("@.*left>@", "", $f9);
    }

    if (strlen($description) < 1) {
        continue;
    }

    $incident = [
        "State" => "TX",
        "City" => "Wichita Falls",
        "County" => "Wichita",
        "Incident" => "none",
        "Description" => $description,
        "Unit" => "none",
        "latlng" => "none",
        "Primary Dispatcher #" => "Wichita Falls Public Safety 911 Communications & Dispatch Events",
        "Source" => $url,
        "Logo" => "none",
        "Address" => $address,
        "Timestamp" => "none",
        "Epoch" => "none",
    ];

    array_push($incidentList,$incident);
    echo "       $timestamp:  $description  $address\n";
}
		$generalInfo = [
    "curlWorking" => $curlWorking,
    "parseWorking" => $parseWorking,
    "agencyName" => "wichita-TX"
];

array_push($incidentList,$generalInfo);
?>