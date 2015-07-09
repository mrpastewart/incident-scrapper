<?php
/**
 * Created by PhpStorm.
 * User: LucasWang
 * Date: 7/6/15
 * Time: 10:55 AM
 */
 $url = "http://www.44-control.net/";
 $state = "NY";
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

$page = preg_replace("@.*>Call Status</th>@", "", $page);
$page = preg_replace("@</table>.*@", "", $page);

$lines = explode("\n", $page);

foreach ($lines as $line) {
	if (! preg_match("@<td>@", $line)) {
		continue;
	}

	$line = preg_replace("@&nbsp;@", " ", $line);
	$line = preg_replace("@ +@", " ", $line);

	list($f1, $f2, $f3, $f4, $f5, $f6, $f7) = preg_split("@</td>@", $line);

	$date_time = preg_replace("@.*>@", "", $f1);

	$description = trim(preg_replace("@.*>@", "", $f3));
	$address = trim(preg_replace("@.*>@", "", $f5));
	$common = trim(preg_replace("@.*>@", "", $f6));

	if (strlen($common) > 0) {
		$address .= " ($common)";
	}

	list($date_portion, $time_portion) = preg_split("@ @", $date_time);

	list($month, $day) = preg_split("@/@", $date_portion);
	list($hour, $minute) = preg_split("@:@", $time_portion);

	$year = date("Y");

    $date = "$year-$month-$day";
    $hrMinSec = "$hour:$minute";
    $unixValue = strtotime($date) + strtotime($hrMinSec);
    $timestamp = date("l, F d, Y", strtotime($date));
    $timestamp = "$timestamp $hrMinSec -0800";

    if ($description != '' && $address != '')
    {
        $incident = [
            "State" => $state,
            "City" => "none",
            "County" => "Rockland",
            "Incident" => "none",
            "Description" => $description,
            "Unit" => "none",
            "latlng" => "none",
            "Primary Dispatcher #" => "44 Control 'FIREWATCH'",
            "Source" => $url,
            "Logo" => "http://www.44-control.net/images/44maltese_blackandred.jpg",
            "Address" => $address,
            "Timestamp" => $timestamp,
            "Epoch" => $unixValue,
        ];
    array_push($incidentList,$incident);

    echo "        $timestamp:  $description  $address\n";
    }
	}
	
$generalInfo = [
    "curlWorking" => $curlWorking,
    "parseWorking" => $parseWorking,
    "agencyName" => "rockland_county-NY"
];
array_push($incidentList,$generalInfo);
	?>