<?php
/**
 * Created by PhpStorm.
 * User: LucasWang
 * Date: 7/6/15
 * Time: 1:54 PM
 */
//BROKEN: not returning any data
$url = "http://www.spokanevalleyfire.com/NewsAndEvents/RecentIncidents.aspx";
$curlWorking = true;
$parseWorking = true;
$state = "WA";
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

$page = preg_replace("@.*scription</th></tr>@", "", $page);
$page = preg_replace("@</table>.*@", "", $page);

//$lines = explode("\n", $page);
$lines = explode("<tr ", $page);

foreach ($lines as $line) {
    if (!preg_match("@^valign@", $line)) {
        continue;
    }

    $line = preg_replace("@.*style=.border-bottom: 1px solid #999999;padding: 0px 0px 0px 0px;.>@", "", $line);
    $line = preg_replace("@</td><td align=.left. style=.*solid #999999;padding: 0px 6px 0px 0px;.>@", "COLSEP", $line);
    $line = preg_replace("@</a></td><td align=.left. style=.border-bottom: 1px solid.* 0px 12px 0px 0px.*@", "", $line);
    $line = preg_replace("@</td><td align=.left. style=.border-bottom: 1px solid.*.>@", "COLSEP", $line);

    $line = html_entity_decode($line);

    $line = preg_replace("@ +", " ", $line);

    list($f1, $f2, $f3, $f4, $f5, $f6, $f7) = explode("COLSEP", $line);

    $date_time = $f1;
    $description = $f2;
    $address = $f3;

    echo $date_time;

    list($date_portion, $time_portion, $ampm_portion) = explode(" ", $date_time);

    list($month, $day, $year) = explode("/", $date_portion);

    list($hour, $minute, $second) = explode(":", $time_portion);

    if ("$ampm_portion" == "PM") {
        if ($hour < 12) {
            $hour += 12;
        }
    } else if ($hour == 12) {
        $hour = 0;
    }

    $timestamp = "$year-$month-$day $hour:$minute:00";

    echo "parsed: \n";
    echo "\tdate: $date_time\n";
    echo "\ttimestamp: $timestamp\n";
    echo "\taddress: $address\n";
    echo "\tdescription: $description\n";
}
?>