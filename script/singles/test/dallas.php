<?php
/**
 * Created by PhpStorm.
 * User: Srivatsav
 * Date: 7/6/2015
 * Time: 11:44 AM
 */
//$table = "dallas";
$url = "http://www.firerescuephotos.com/fa/";
$email_url = $url;
$agency_name = "Dallas, Texas";
$state = "TX";
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

$page = curl_exec($ch);

if (strlen($page) < 200) {
    die();
}

$currentTime = time();

$page = preg_replace("@.*INCIDENTS TABLE STARTS HERE@", "", $page);
$page = preg_replace("@</tbody>.*@", "", $page);

$lines = explode("\n", $page);

foreach ($lines as $line) {
    if (preg_match("@<tr class@", $line)) {
        $ctr = 1;
        continue;
    }

    if (preg_match("@nowrap.>@", $line) && $ctr == 1) {
        $ctr++;
        $line = preg_replace("@.*nowrap.>@", "", $line);
        $description = preg_replace("@</td>.*@", "", $line);
        continue;
    }

    if (preg_match("@nowrap.>@", $line) && $ctr == 2) {
        $ctr++;
        $line = preg_replace("@.*target=._blank.>@", "", $line);
        $address = preg_replace("@</a>.*@", "", $line);
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

    if ($description != '' && $address != '')
        $incident = [
            "State" => $state,
            "City" => "Dallas",
            "County" => "Dallas County",
            "Incident" => "none",
            "Description" => $description,
            "Unit" => "none",
            "latlng" => "none",
            "Primary Dispatcher #" => "Dallas Fire-Rescue Department",
            "Source" => $url,
            "Logo" => "https://pbs.twimg.com/profile_images/525486454747586560/9OHpDWO-.png",
            "Address" => $address,
            "Timestamp" => "none",
            "Epoch" => "none"
          ];
         array_push($incidentList,$incident);
         echo "          $description  $address\n";
}
$generalInfo = [
    "curlWorking" => $curlWorking,
    "parseWorking" => $parseWorking,
    "agencyName" => "dallas-TX"
];
array_push($incidentList, $generalInfo);
?>
