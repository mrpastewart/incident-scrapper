<?php
/**
 * Created by PhpStorm.
 * User: LucasWang
 * Date: 7/8/15
 * Time: 10:12 PM
 */
$url = "http://www.pinellascounty.org/911/activity.xml";
$curlWorking = true;
$parseWorking = true;
$incidentList = [];
$state = "FL";
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

$page = preg_replace("@</Location>@", "</Location>\n", $page);
$page = preg_replace("@</Type>@", "</Type>\n", $page);

$lines = explode("\n", $page);

foreach ($lines as $line) {
    if (preg_match("@<Location>@", $line)) {
        $line = preg_replace("@.*<Location>@", "", $line);
        $address = preg_replace("@</Location>.*@", "", $line);
        continue;
    }

    if (preg_match("@<Type>@", $line) && !preg_match("@><Type>@", $line)) {
        $line = preg_replace("@.*<Type>@", "", $line);
        $description = preg_replace("@</Type>.*@", "", $line);
    }

    if (!preg_match("@</Type>@", $line)) {
        continue;
    }

    $description = preg_replace("@  +@", " ", $description);
    $address = preg_replace("@  +@", " ", $address);

    if ($description == "MEDICAL") {
        continue;
    }

    $ctr++;

    $incident = [
        "State" => "FL",
        "City" => "none",
        "County" => "Pinellas",
        "Incident" => "none",
        "Description" => $description,
        "Unit" => "none",
        "latlng" => "none",
        "Primary Dispatcher #" => "Pinellas County",
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
    "agencyName" => "pinellas_county-FL"
];

array_push($incidentList,$generalInfo);
?>