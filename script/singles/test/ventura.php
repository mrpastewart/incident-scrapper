<?php
/**
 * Created by PhpStorm.
 * User: LucasWang
 * Date: 7/7/15
 * Time: 11:26 AM
 */
$url = "http://firelineweb.ventura.org/";
$curlWorking = true;
$parseWorking = true;
$incidentList = [];
$state = "CA";

//
//	Initialize curl
//

$ch = curl_init();
curl_setopt($ch, CURLOPT_VERBOSE, 0);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_TIMEOUT, 270);
curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_2) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/40.0.2214.115 Safari/537.36");
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

$page = preg_replace("@.*<th>Comment</th>@", "", $page);

$lines = explode("\n", $page);

foreach ($lines as $line) {
    if (preg_match("@<tr style=.background-color@", $line)) {
        $ctr = 1;
        continue;
    }

    if (preg_match("@onclick=.InitializeMap@", $line) && $ctr == 1) {
        $ctr++;

        list($f1, $f2, $f3, $f4, $f5, $f6, $f7) = explode(",", $line);

        $address = preg_replace("@^'@", "", $f5);
        $address = preg_replace("@'$@", "", $address);

        $community = preg_replace("@^'@", "", $f6);
        $community = preg_replace("@'$@", "", $community);

        if (strlen($community) > 0) {
            if (strlen($address) > 0) {
                $address .= ", ";
            }

            $address .= $community;
        }

        $description = preg_replace("@^'@", "", $f7);
        $description = preg_replace("@'$@", "", $description);

        continue;
    }

    if ($ctr != 2) {
        continue;
    }

    $ctr++;

    $address = trim(preg_replace("@, *$@", "", $address));

    if (strlen($address) < 1) {
        continue;
    }

    $incident = [
        "State" => "CA",
        "City" => $community,
        "County" => "Ventura",
        "Incident" => "none",
        "Description" => $description,
        "Unit" => "none",
        "latlng" => "none",
        "Primary Dispatcher #" => "VCFD FireLine",
        "Source" => $url,
        "Logo" => "none",
        "Address" => $address,
        "Timestamp" => "none",
        "Epoch" => "none",
    ];

    array_push($incidentList,$incident);
    echo        "none:  $description  $address\n";
}
$generalInfo = [
    "curlWorking" => $curlWorking,
    "parseWorking" => $parseWorking,
    "agencyName" => "ventura-CA"
];

array_push($incidentList,$generalInfo);
?>