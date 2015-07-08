<?php
/**
 * Created by PhpStorm.
 * User: LucasWang
 * Date: 7/7/15
 * Time: 9:34 PM
 */
$url = "http://geneseecounty911.org/events.php";
$curlWorking = true;
$parseWorking = true;
$incidentList = [];
$state = "MI";

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

$page = preg_replace("@.*<th>Jurisdiction</th>@", "", $page);
$page = preg_replace("@table.*@", "", $page);

$lines = explode("\n", $page);

foreach ($lines as $line) {
    if (preg_match("@<tr class@", $line)) {
        $ctr = 1;
        continue;
    }

    if (preg_match("@<td class@", $line) && $ctr == 1) {
        $ctr++;
        $line = preg_replace("@</td>.*@", "", $line);
        $date = preg_replace("@.*>@", "", $line);
        continue;
    }

    if (preg_match("@<td class@", $line) && $ctr == 2) {
        $ctr++;
        $line = preg_replace("@</td>.*@", "", $line);
        $time = preg_replace("@.*>@", "", $line);
        $time = preg_replace("@&nbsp;@", " ", $time);
        continue;
    }

    if (preg_match("@<td class@", $line) && $ctr == 3) {
        $ctr++;
        $line = preg_replace("@</td>.*@", "", $line);
        $description = preg_replace("@.*>@", "", $line);
        continue;
    }

    if (preg_match("@<td class@", $line) && $ctr == 4) {
        $ctr++;
        $line = preg_replace("@</a>.*@", "", $line);
        $line = preg_replace("@</td>.*@", "", $line);
        $address = preg_replace("@.*>@", "", $line);
        continue;
    }

    if (preg_match("@<td class@", $line) && $ctr == 5) {
        $ctr++;
        $line = preg_replace("@</td>.*@", "", $line);
        $location = preg_replace("@.*>@", "", $line);

        if (strlen($location) > 0) {
            $address .= "; $location";
        }

        continue;
    }

    if (preg_match("@<td class@", $line) && $ctr == 6) {
        $ctr++;
        $line = preg_replace("@</td>.*@", "", $line);
        $city = preg_replace("@.*>@", "", $line);

        if (strlen($city) > 0) {
            $address .= "; $city";
        }

//		continue;
    }

    if ($ctr != 7 || null) {
        continue;
    }

    $ctr++;

    $address = preg_replace("@  +@", " ", $address);

    list($month, $day) = explode("/", $date);
    list($hour, $minute, $ampm) = split("[ :]", $time);

    if ("$ampm_portion" == "PM") {
        if ($hour < 12) {
            $hour += 12;
        }
    } else if ($hour == 12) {
        $hour = 0;
    }

    $year = date("Y");

    $t = mktime($hour, $minute, $second, $month, $day, $year);

    $now = time();

    if ($t > $now) {
        $year = $year - 1;
    }

    $timestamp = "$year-$month-$day $hour:$minute";

    echo "parsed: \n";
    echo "\ttimestamp: $timestamp\n";
    echo "\tdescription: $description\n";
    echo "\taddress: $address\n";
}
?>