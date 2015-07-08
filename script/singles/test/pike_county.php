<?php
/**
 * Created by PhpStorm.
 * User: LucasWang
 * Date: 7/8/15
 * Time: 10:21 AM
 */
$url = "https://webcad.pikepa.org/pages/public/liveincidents1.aspx";
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

$page = preg_replace("@.*>Units Assigned</td>@", "", $page);
$page = preg_replace("@</table>.*@", "", $page);


$lines = explode("\n", $page);

foreach ($lines as $line) {
    if (preg_match("@<tr >@", $line)) {
        $ctr = 1;
        continue;
    }

    if (preg_match("@<td class=.table-data@", $line) && $ctr == 1) {
        $ctr = 2;
        $line = preg_replace("@.*<br>@", "", $line);
        $date_time = preg_replace("@&nb.*@", "", $line);
        continue;
    }

    if (preg_match("@<td class=.table-data@", $line) && $ctr == 2) {
        $ctr = 3;
        $line = preg_replace("@.*table-data.>@", "", $line);
        $description = preg_replace("@&nb.*", "", $line);
        continue;
    }

    if (preg_match("@<td id=.rptResults_ctl00_tdLocation@", $line) && $ctr == 3) {
        $ctr = 4;
        $line = preg_replace("@.*table-data.>@", "", $line);
        $address = preg_replace("@&nbsp;</td>.*@", "", $line);
        $address = preg_replace("@</td>.*@", "", $line);

        $address = preg_replace("@<BR>@", "; ", $address);
        $address = preg_replace("@ +; +@", "; ", $address);
        $address = preg_replace("@ +@", " ", $address);
        continue;
    }

    if ($ctr != 4) {
        continue;
    }

    $ctr = 5;

    $line = html_entity_decode($line);
    $line = preg_replace("@ +@", " ", $line);

    list($date_portion, $time_portion) = explode(" ", $date_time);

    list($month, $day, $year) = explode("/", $date_portion);

    list($hour, $minute, $second) = explode(":", $time_portion);

    $date = "$year-$month-$day";
    $hrMinSec = $time_portion;
    $unixValue = strtotime($date) + strtotime($hrMinSec);
    $timestamp = date("l, F d, Y", strtotime($date));
    $timestamp = "$timestamp $hrMinSec -0800";

    $incident = [
        "State" => "PA",
        "City" => "none",
        "County" => "Pike",
        "Incident" => "none",
        "Description" => $description,
        "Unit" => "none",
        "latlng" => "none",
        "Primary Dispatcher #" => "Pike County CAD",
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
    "agencyName" => "pike_county-PA"
];

array_push($incidentList,$generalInfo);
?>