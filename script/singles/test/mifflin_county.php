<?php
/**
 * Created by PhpStorm.
 * User: LucasWang
 * Date: 7/8/15
 * Time: 1:24 PM
 */
$url = "https://access.active911.com/interface/js.php?r3vdbmn";
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

$page = preg_replace("@.*a91.data=@", "", $page);
$page = preg_replace("@.*.alarms.:@", "", $page);

$lines = explode("{\"id\":", $page);

foreach ($lines as $line) {
    if (!preg_match("@stamp@", $line)) {
        continue;
    }

    $description = preg_replace("@.,.generic_title.*@", "", $line);
    $description = preg_replace("@.*description.:.@", "", $description);

    if (strlen($description) < 5) {
        continue;
    }

    $address = preg_replace("@.,.city.*@", "", $line);
    $address = preg_replace("@.*address.:.@", "", $address);

    $city = preg_replace("@.,.state.*@", "", $line);
    $city = preg_replace("@.*city.:.@", "", $city);

    $address .= ", $city";

    $address = preg_replace("@  +@", " ", $address);

    $incident = [
        "State" => "PA",
        "City" => "none",
        "County" => "Mifflin",
        "Incident" => "none",
        "Description" => $description,
        "Unit" => "none",
        "latlng" => "none",
        "Primary Dispatcher #" => "Port Penn Vol. Fire Co.",
        "Source" => $url,
        "Logo" => "none",
        "Address" => $address,
        "Timestamp" => "none",
        "Epoch" => "none",
    ];

    array_push($incidentList,$incident);
    echo "       none:  $description  $address\n";
}
$generalInfo = [
    "curlWorking" => $curlWorking,
    "parseWorking" => $parseWorking,
    "agencyName" => "mifflin-PA"
];

array_push($incidentList,$generalInfo);
?>