<?php
/**
 * Created by PhpStorm.
 * User: LucasWang
 * Date: 7/7/15
 * Time: 11:36 PM
 */
$url = "http://www.vhc27.com/incidents";
$url = "http://www.magnolia55.com/incidents";
$curlWorking = true;
$parseWorking = true;
$incidentList = [];
$state = "DE";
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

$page = preg_replace("@.*<stong>Voicemail:</strong>@", "", $page);
$page = preg_replace("@>Upcoming Events<.*@", "", $page);

$lines = explode("\n", $page);

foreach ($lines as $line) {
    if (preg_match("@<div class=.pstagc.@", $line)) {
        $ctr = 1;
        continue;
    }

    if (preg_match("@.*,.* @ ", $line) && $ctr == 1) {
        $line = preg_replace("@.*<em class=.date.>@", "", $line);
        $line = preg_replace("@</em>.*@", "", $line);
        $line = preg_replace("@,@", "", $line);
        list($dow, $month, $day, $year, $junk, $time_portion) = explode(" ", trim($line));

        $ctr++;
        continue;
    }

    if ($ctr == 1) {
        $ctr = 0;
        continue;
    }

    if (preg_match("@<td class=.gbl..*>Nature:@", $line) && $ctr == 2) {
        $ctr++;
        continue;
    }

    if (preg_match("@<td class=.gbr.@", $line) && $ctr == 3) {
        $ctr++;
        continue;
    }

    if ($ctr == 4) {
        $description = trim($line);
        $ctr++;
        continue;
    }

    if (preg_match("@<td class=.gbl..*>City:@", $line) && $ctr == 5) {
        $ctr++;
        continue;
    }

    if (preg_match("@<td class=.gbr.@", $line) && $ctr == 6) {
        $ctr++;
        continue;
    }

    if ($ctr == 7) {
        $address = trim($line);
        $ctr = 99;
        continue;
    }

    if (preg_match("@<td class=.gbl..*>Location:@", $line) && $ctr == 5) {
        $ctr = 8;
        continue;
    }

    if (preg_match("@<td class=.gbr.@", $line) && $ctr == 8) {
        $ctr++;
        continue;
    }

    if ($ctr == 9) {
        $address = trim($line);
        $ctr++;
        continue;
    }

    if (preg_match("@<td class=.gbl..*>Address:@", $line) && $ctr == 10) {
        $ctr++;
        continue;
    }

    if (preg_match("@<td class=.gbr.@", $line) && $ctr == 11) {
        $ctr++;
        continue;
    }

    if ($ctr == 12) {
        if (strlen($address) > 0) {
            $address .= ", ";
        }

        $line = preg_replace("@<br.*@", "", $line);

        $address .= trim($line);
        $ctr++;
        continue;
    }

    if (!preg_match("@</td@", $line) && $ctr == 13) {
        if (strlen($address) > 0) {
            $address .= ", ";
        }

        $line = preg_replace("@<br.*@", "", $line);

        $address .= trim($line);
        $ctr++;
        continue;
    }

    if (preg_match("@</td@", $line) && $ctr == 13) {
        $ctr++;
        continue;
    }

    if (preg_match("@/table>@", $line) && $ctr > 7) {
        $ctr = 99;
    }

    if ($ctr != 99) {
        continue;
    }

    $ctr++;

    $address = trim(preg_replace("@, *$@", "", $address));

    if (strlen($address) < 1) {
        continue;
    }

    if ($month == "January") {
        $month = "01";
    }
    if ($month == "February") {
        $month = "02";
    }
    if ($month == "March") {
        $month = "03";
    }
    if ($month == "April") {
        $month = "04";
    }
    if ($month == "May") {
        $month = "05";
    }
    if ($month == "June") {
        $month = "06";
    }
    if ($month == "July") {
        $month = "07";
    }
    if ($month == "August") {
        $month = "08";
    }
    if ($month == "September") {
        $month = "09";
    }
    if ($month == "October") {
        $month = "10";
    }
    if ($month == "November") {
        $month = "11";
    }
    if ($month == "December") {
        $month = "12";
    }

    $timestamp = "$year-$month-$day $time_portion";

    echo "parsed: \n";
    echo "\ttimestamp: $timestamp\n";
    echo "\tdescription: $description\n";
    echo "\taddress: $address\n";
}
?>