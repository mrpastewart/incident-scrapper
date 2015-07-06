<?php
/**
 * Created by PhpStorm.
 * User: Srivatsav
 * Date: 7/6/2015
 * Time: 12:21 PM
 */

//$table = "san_antonio";
$url = "https://webapps2.sanantonio.gov/activefire/Fire.aspx";
$email_url = "http://www.sanantonio.gov/SAFD/NewsMediaandReports/ActiveFires.aspx";
$agency = "San Antonio, Texas";
$curlWorking = true;
$parseWorking = true;
$incidentList = [];

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

if (strlen($page) < 200) {
    die();
}

$currentTime = time();

$page = preg_replace("@.*>TAC</a></th>@", "", $page);
$page = preg_replace("@</table>.*@", "", $page);

$lines = explode("\n", $page);

foreach ($lines as $line) {
    if (!preg_match("@^[ \t]*<td style|<a href=@", $line)) {
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
        list($f1, $f2, $f3, $f4, $f5, $f6, $f7) = explode("</td>", $line);

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

    list($date_portion, $time_portion, $ampm_portion) = explode(" ", $date_time);

    list($month, $day, $year) = explode("/", $date_portion);

    list($hour, $minute, $second) = explode(":", $time_portion);

    if ("$ampm_portion" == "PM") {
        if ($hour < 12) {
            $hour += 12;
        }
    } else if ($hour == 12) {
        $hour = 0;
    }

    $timestamp = "$year-$month-$day $hour:$minute:$second";

    /*echo "parsed: \n";
    echo "\tdate: $date_time\n";
    echo "\ttimestamp: $timestamp\n";
    echo "\tnumber units: $number_units\n";
    echo "\taddress: $address\n";
    echo "\tdescription: $description\n";*/

    $date = "$year-$month-$day";
    $hrMinSec = "$hour:$minute";
    $unixValue = strtotime($date) + strtotime($hrMinSec);
    $timestamp = date("l, F d, Y", strtotime($date));
    $timestamp = "$timestamp $hrMinSec -0800";

    if (preg_match("@MEDICAL RESPONSE@i", $description)) {
        continue;
    }

    if ($number_units == "") {
        $number_units = 0;
    }


    $incident = [
        "State" => "TX",
        "City" => "San Antonio",
        "County" => "Bexar County",
        "Incident" => "none",
        "Description" => $description,
        "Unit" => $number_units,
        "latlng" => "none",
        "Primary Dispatcher #" => "San Antonio Fire Department",
        "Source" => $url,
        "Logo" => "none",
        "Address" => $address,
        "Timestamp" => $timestamp,
        "Epoch" => $unixValue,
    ];
    array_push($incidentList, $incident);
    $updated = 0;
}
$generalInfo = [
    "curlWorking" => $curlWorking,
    "parseWorking" => $parseWorking,
    "agencyName" => "San_Antonio"
];
array_push($incidentList, $generalInfo);


//var_dump($incidentList);
