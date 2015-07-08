<?php
/**
 * Created by PhpStorm.
 * User: LucasWang
 * Date: 7/8/15
 * Time: 10:36 AM
 */
$url = "http://es.surryinfo.net/psportal/";
$curlWorking = true;
$parseWorking = true;
$incidentList = [];
$state = "NC";
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

$page = preg_replace("@.*Active Calls@", "", $page);
$page = preg_replace("@Pri 1.*@", "", $page);

$lines = explode("\n", $page);

foreach ($lines as $line) {
    if (preg_match("@priority style22 style24@", $line)) {
        $ctr = 1;

        $line = preg_replace("@.*style64.>@", "", $line);
        $address = preg_replace("@</div>.*@", "", $line);
        continue;
    }

    if (preg_match("@<td .*class=.priority@", $line) && $ctr == 1) {
        $ctr++;
        continue;
    }

    if (preg_match("@</span></div></td>@", $line) && $ctr == 2) {
        $ctr++;

        $line = preg_replace("@.*<dd>@", "", $line);
        $description = trim(preg_replace("@</span>.*@", "", $line));
        continue;
    }

    if ($ctr != 3) {
        continue;
    }

    $ctr++;

    $address = trim(preg_replace("@, *$@", "", $address));

    if (strlen($address) < 1) {
        continue;
    }

    $incident = [
        "State" => "NC",
        "City" => "none",
        "County" => "Surry",
        "Incident" => "none",
        "Description" => $description,
        "Unit" => "none",
        "latlng" => "none",
        "Primary Dispatcher #" => "Surry County Public Safety Information Site",
        "Source" => $url,
        "Logo" => "none",
        "Address" => $address,
        "Timestamp" => "none",
        "Epoch" => "none",
    ];

    array_push($incidentList,$incident);
    echo "         $description  $address\n";

}
$generalInfo = [
    "curlWorking" => $curlWorking,
    "parseWorking" => $parseWorking,
    "agencyName" => "surry-NC"
];

array_push($incidentList,$generalInfo);
?>