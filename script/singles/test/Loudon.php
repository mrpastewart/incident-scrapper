<?php

$url = "http://adveng.tk/lcfr/ngc3.php";
$email_url = "http://adveng.tk/lcfr/";
$agency = "Loudoun, Virginia";
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

$page = preg_replace("@{.id.:.@", "\n{\"id\":\"", $page);

$lines = explode("\n", $page);

foreach ($lines as $line) {
    if (! preg_match("@^{.id.:.@", $line)) {
        continue;
    }

    $line = preg_replace("@.*printaddr.:.@", "", $line);
    $address = preg_replace("@\",\".*@", "", $line);

    $line = preg_replace("@.*city.:.@", "", $line);
    $city = preg_replace("@\",\".*@", "", $line);

    $line = preg_replace("@.*tstamp.:.@", "", $line);
    $date_time = preg_replace("@\",\".*@", "", $line);
    $date_time = preg_replace("@\\\\@", "", $date_time);

    $line = preg_replace("@.*ntext.:.@", "", $line);
    $description = preg_replace("@\",\".*@", "", $line);
    $description = preg_replace("@\\\\@", "", $description);

    $line = preg_replace("@.*ndesc.:.@", "", $line);
    $extended = preg_replace("@\",\".*@", "", $line);
    $extended = preg_replace("@\\\\@", "", $extended);

    if (strlen($extended) > 0) {
        $description .= " - $extended";
    }

    list($date_portion, $time_portion, $ampm_portion) = explode(" ", $date_time);

    list($month, $day, $year) = explode("/", $date_portion);

    list($hour, $minute, $second) = explode(":", $time_portion);

    $timestamp = "$year-$month-$day $hour:$minute:$second";





    $date = "$year-$month-$day";
    $hrMinSec = "$hour:$minute";
    $unixValue = strtotime($date) + strtotime($hrMinSec);
    $timestamp = date("l, F d, Y", strtotime($date));
    $timestamp = "$timestamp $hrMinSec -0800";


    $incident = [
        "State" => "VA",
        "City" => "Loudon",
        "County" => "Loudon County",
        "Incident" => "none",
        "Description" => $description,
        "Unit" => "none",
        "latlng" => "none",
        "Primary Dispatcher #" => "Loudon County Fire Department",
        "Source" => "http://adveng.tk/lcfr/",
        "Logo" => "none",
        "Address" => $address,
        "Timestamp" => $timestamp,
        "Epoch" => $unixValue,
    ];
    array_push($incidentList, $incident);

    /*echo "parsed: \n";
    echo "\tdate: $date_time\n";
    echo "\ttimestamp: $timestamp\n";
    echo "\taddress: $address\n";
    echo "\tdescription: $description\n";*/
}
$generalInfo = [
    "curlWorking" => $curlWorking,
    "parseWorking" => $parseWorking,
    "agencyName" => "Loudon"
];
array_push($incidentList, $generalInfo);
//var_dump($incidentList);
?>
