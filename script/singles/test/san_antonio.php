<?php
/**
 * Created by PhpStorm.
 * User: Lucas
 * Date: 7/6/2015
 * Time: 12:21 PM
 */
$url = "https://webapps2.sanantonio.gov/activefire/Fire.aspx";
$curlWorking = true;
$parseWorking = true;
$incidentList = [];
$state = "TX";

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

echo "Retrieving $url\n";
$page = curl_exec($ch);
echo "Received " . strlen($page) . " bytes\n";

if(curl_getinfo($ch,CURLINFO_HTTP_CODE) != 200)
{
    $curlWorking = false;
}


$currentTime = time();

$page = preg_replace("@.*>TAC</a></th>@", "", $page);
$page = preg_replace("@</table>.*@", "", $page);

$lines = explode("\n", $page);

foreach ($lines as $line) {
	if (! preg_match("@^[ \t]*<td style|<a href=@", $line)) {
		continue;
	}

	$line = html_entity_decode($line);

	$line = preg_replace("@ +@", " ", $line);

/*
			<td style="width:30px;">1</td><td style="width:140px;">5/24/2014 2:46:42 PM</td><td>Other/Unknown</td><td>
                                    <a href="http://maps.google.com/maps?q=300 Chelsea Dr+78213" target="blank">300 Chelsea Dr </a>
                                </td><td>SCALES/VANCE JACKSON</td><td>Apartment Complexes</td><td>78213</td><td style="width:50px;">*TAC6</td>
*/

	if (preg_match("@^[ \t]*<td style=@", $line)) {
		list($f1, $f2, $f3, $f4, $f5, $f6, $f7) = preg_split("@</td>@", $line);

		$number_units = trim(preg_replace("@.*>@", "", $f1));
		$date_time = preg_replace("@.*>@", "", $f2);
		$description = trim(preg_replace("@.*>@", "", $f3));
		continue;
	}

	$line = preg_replace("@ *</a>.*@", "", $line);
	$address = preg_replace("@.*\">@", "", $line);

	if (strlen($description) < 2 || strlen($address) < 2) {
		continue;
	}

	list($date_portion, $time_portion, $ampm_portion) = preg_split("@ @", $date_time);

	list($month, $day, $year) = preg_split("@/@", $date_portion);

	list($hour, $minute, $second) = preg_split("@:@", $time_portion);

	if ("$ampm_portion" == "PM") {
		if ($hour < 12) {
			$hour += 12;
		}
	} else if ($hour == 12) {
		$hour = 0;
	}

    $date = "$year/$month/$day";
    $hrMinSec = "$hour:$minute";
    $unixValue = strtotime($date) + strtotime($hrMinSec);
    $timestamp = date("l, F d, Y", strtotime($date));
    $timestamp = "$timestamp $hrMinSec -0800";

    $incident = [
        "State" => "TX",
        "City" => "San Antonio",
        "County" => "Bexar",
        "Incident" => "none",
        "Description" => $description,
        "Unit" => "none",
        "latlng" => "none",
        "Primary Dispatcher #" => "San Antonio Fire Department",
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
    "agencyName" => "san_antonio-TX"
];

array_push($incidentList,$generalInfo);
	?>