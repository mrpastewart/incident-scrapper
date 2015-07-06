<?php
/**
 * Created by PhpStorm.
 * User: LucasWang
 * Date: 7/6/15
 * Time: 12:44 PM
 */
//Unsure of Where it parses the data from. 
$url = "https://symposium-live.com/system/events/index/SouthwesternNewHampshireDistrictFireMutualAid";
$curlWorking = true;
$parseWorking = true;
$state = "PA";
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
curl_setopt($ch, CURLOPT_COOKIESESSION, 1);
curl_setopt($ch, CURLOPT_COOKIEJAR, "/tmp/cookie_jar");
curl_setopt($ch, CURLOPT_POST, 1);

//
//	Retrieve main page to obtain cookie
//

curl_setopt($ch, CURLOPT_URL, $url);

curl_exec($ch);

//
//	Retrieve data page
//

$url2 = "https://symposium-live.com/system/events/ajax_get_all/" . time() . "000";

curl_setopt($ch, CURLOPT_URL, $url2);

curl_setopt($ch, CURLOPT_POSTFIELDS, "client_id=1B4C5893-7751-480F-ADE7-6857D227F2A7&agency_filter=all&event_id=&sort_column=sAgency&sort_direction=up");

$page = curl_exec($ch);

if(curl_getinfo($ch,CURLINFO_HTTP_CODE) != 200)
{
    $curlWorking = false;
}


$currentTime = time();

$page = preg_replace("@sClientID@", "\n", $page);

$lines = explode("\n", $page);

foreach ($lines as $line) {
    if (!preg_match("@agency_event_id@", $line)) {
        continue;
    }

    $line = preg_replace("@sStatus.*@", "", $line);

    $streetNumber = preg_replace("@.*sStreetNumber.:.@", "", $line);
    $streetNumber = preg_replace("@.,..*@", "", $streetNumber);

    $streetName = preg_replace("@.*sStreetName.:.@", "", $line);
    $streetName = preg_replace("@.,..*@", "", $streetName);

    $streetName = preg_replace("@\\\/@", "/", $streetName);
    $streetName = preg_replace("@N/A@", "", $streetName);

    $city = preg_replace("@.*sCity.:.@", "", $line);
    $city = preg_replace("@.,..*@", "", $city);

    $description = preg_replace("@.*sType.:.@", "", $line);
    $description = preg_replace("@.,..*@", "", $description);

    $description = preg_replace("@\\\/@", "/", $description);
    $description = preg_replace("@N/A@", "", $description);
    $description = trim($description);

    $address = trim("$streetNumber $streetName");

    if (strlen($address) > 0) {
        $address .= ", ";
    }
    $address .= $city;

    echo "parsed: \n";
    echo "\tdescription: $description\n";
    echo "\taddress: $address\n";

    if (strlen($description) < 1 || preg_match("@MEDICAL@i", $description)) {
        echo "  skipped\n";
        continue;
    }
}
?>