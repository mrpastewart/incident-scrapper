<?php
/**
 * Created by PhpStorm.
 * User: Srivatsav
 * Date: 7/1/2015
 * Time: 12:27 PM
 */

$url = "http://www.ci.austin.tx.us/fact/default.cfm";
$agency_name = "Austin, Texas";


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

$page = preg_replace("@.*<strong>AGENCY@", "", $page);
$page = preg_replace("@</table>.*@", "", $page);
$page = preg_replace("@.*down_arrow.jpg@", "", $page);

$lines = explode("\n", $page);
$ctr=0;
foreach ($lines as $line) {
    if (preg_match("@<TR BGCOLOR@", $line)) {
        $ctr = 1;
        continue;
    }

    if (preg_match("@<font face=.arial. size=.1.>@", $line) && $ctr == 1) {
        $ctr++;
        $line = preg_replace("@</font.*@", "", $line);
        $date = preg_replace("@.*>@", "", $line);
        continue;
    }

    if (preg_match("@<font face=.arial. size=.1.>@", $line) && $ctr == 2) {
        $ctr++;
        $line = preg_replace("@&nbsp;</font><font face=.arial. size=.1.>@", " ", $line);
        $line = preg_replace("@</font.*@", "", $line);
        $time = preg_replace("@.*>@", "", $line);
        continue;
    }

    if (preg_match("@<font face=.arial. size=.1.>@", $line) && $ctr == 3) {
        $ctr++;
        $line = preg_replace("@</font.*@", "", $line);
        $description = preg_replace("@.*>@", "", $line);
        continue;
    }

    if (preg_match("@<font face=.arial. size=.1.>@", $line) && $ctr == 4) {
        $ctr++;
        $line = preg_replace("@</font.*@", "", $line);
        $address = preg_replace("@.*>@", "", $line);
        continue;
    }

    if (preg_match("@<font face=.arial. size=.1.>@", $line) && $ctr == 5) {
        $ctr++;
        continue;
    }

    if (preg_match("@^[ \t]*&nbsp;@", $line) && $ctr == 5) {
        $ctr++;
        continue;
    }

    if (preg_match("@<font face=.arial. size=.1.>@", $line) && $ctr == 6) {
        $ctr++;
        $line = preg_replace("@</font.*@", "", $line);
        $agency = preg_replace("@.*>@", "", $line);
        continue;
    }

    if ($ctr != 7) {
        continue;
    }

    $ctr = 8;

    $line = html_entity_decode($line);
    $line = preg_replace("@ +@", " ", $line);

    list($month, $day, $year) = explode(" ", $date);

    $day = preg_replace("@,.*@", "", $day);

    if ($month == "Jan") {
        $month = "01";
    }
    if ($month == "Feb") {
        $month = "02";
    }
    if ($month == "Mar") {
        $month = "03";
    }
    if ($month == "Apr") {
        $month = "04";
    }
    if ($month == "May") {
        $month = "05";
    }
    if ($month == "Jun") {
        $month = "06";
    }
    if ($month == "Jul") {
        $month = "07";
    }
    if ($month == "Aug") {
        $month = "08";
    }
    if ($month == "Sep") {
        $month = "09";
    }
    if ($month == "Oct") {
        $month = "10";
    }
    if ($month == "Nov") {
        $month = "11";
    }
    if ($month == "Dec") {
        $month = "12";
    }

    list($time_portion, $ampm_portion) = explode(" ", $time);

    list($hour, $minute) = explode(":", $time_portion);

    if ("$ampm_portion" == "PM") {
        if ($hour < 12) {
            $hour += 12;
        }
    } else if ($hour == 12) {
        $hour = 0;
    }

    $timestamp = "$year-$month-$day $hour:$minute:00";
    $date = "$year-$month-$day";
    $hrMinSec = "$hour:$minute";
    $unixValue = strtotime($date) + strtotime($hrMinSec);
    $timestamp = date("l, F d, Y", strtotime($date));
    $timestamp = "$timestamp $hrMinSec -0800";


    $incident = [
        "State" => "none",
        "City" => "Austin",
        "County" => "Travis County",
        "Incident" => $standardIncident,
        "Description" => $description,
        "Unit" => "none",
        "latlng" => "none",
        "Primary Dispatcher #" => "Fire Incidents In Travis County",
        "Source" => "http://www.ci.austin.tx.us/fact/default.cfm?sort=10",
        "Logo" => "https://pbs.twimg.com/profile_images/3158256826/f9f34aa109a26aa107a2a9edb85a201b_normal.jpeg",
        "Address" => $address,
        "Timestamp" => $timestamp,
        "Unix Value" => $unixValue,
    ];




    echo "parsed: \n";
    echo "\tdate: $date\n";
    echo "\ttime: $time\n";
    echo "\ttimestamp: $timestamp\n";
    echo "\taddress: $address\n";
    echo "\tdescription: $description\n";
    echo "\tagency: $agency\n";
}