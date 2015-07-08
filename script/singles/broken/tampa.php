<?php
/**
 * Created by PhpStorm.
 * User: LucasWang
 * Date: 7/6/15
 * Time: 2:17 PM
 */
//BROKEN: Only returns timestamp...the description and address is not returning
$url = "http://apps.tampagov.net/appl_fire_calls_for_service/frmCallsList.asp";

//
//	Initialize curl
//

$ch = curl_init();

curl_setopt($ch, CURLOPT_VERBOSE, 1);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_TIMEOUT, 270);
curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)");
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

$page = preg_replace("@.*Incident #@", "", $page);
$page = preg_replace("@To Top Of Page.*@", "", $page);

$lines = explode("\n", $page);

$ctr = 0;

foreach ($lines as $line) {
    if (preg_match("@^[ \t]+<tr>@", $line)) {
        $ctr = 1;
        continue;
    }

    if (preg_match("@<td align=@", $line) && $ctr == 1) {
        $ctr++;
        continue;
    }

    if (preg_match("@/.*/.*:@", $line) && $ctr == 2) {
        $date_time = trim($line);
        $address = "";
        $ctr++;
        continue;
    }

    if (preg_match("@<td align=@", $line) && $ctr == 3) {
        $ctr++;
        continue;
    }

    if ($ctr == 4) {
        $line = preg_replace("@  +@", " ", $line);
        $line = preg_replace("@/ +@", "/", $line);

        if (preg_match("@title=@", $line)) {
            $line = preg_replace("@.*title=@", "", $line);
            $description = preg_replace("@ *&.*@", "", $line);

            $address = preg_replace("@.*txtaddress= *@", "", $line);
            $address = preg_replace("@&txturl=.*@", "", $address);
        } else if (preg_match("@ ONLY@", $line)) {
            $line = preg_replace("@.*GRID ONLY <br>", "", $line);
            $description = preg_replace("@ *<fieldset.*", "", $line);
            $description = preg_replace("@ *<br.*", "", $description);
            $addres = "";
        }

        $ctr++;
        continue;
    }

    if ($ctr != 5) {
        continue;
    }

    $ctr++;

    if ($description == "MEDICAL") {
        continue;
    }

    list($date_portion, $time_portion, $ampm_portion) = explode(" ", $date_time);

    list($month, $day, $year) = explode("/", $date_portion);

    list($hour, $minute) = explode(":", $time_portion);

    if ("$ampm_portion" == "PM") {
        if ($hour < 12) {
            $hour += 12;
        }
    } else if ($hour == 12) {
        $hour = 0;
    }

    $timestamp = "$year-$month-$day $hour:$minute";

    echo "parsed: \n";
    echo "\ttimestamp: $timestamp\n";
    echo "\taddress: $address\n";
    echo "\tdescription: $description\n";
}
?>