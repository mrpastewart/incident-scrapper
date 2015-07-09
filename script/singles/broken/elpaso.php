<?php
/**
 * Created by PhpStorm.
 * User: LucasWang
 * Date: 7/7/15
 * Time: 10:57 AM
 */
$url = "http://www.elpasotexas.gov/traffic/";
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

//$page = preg_replace(".*><b>STATUS</b>", "", $page);
//$page = preg_replace("table.*", "", $page);

$lines = explode("\n", $page);

foreach ($lines as $line) {
	if (preg_match("@<tr>@", $line)) {
		$ctr = 1;
		continue;
	}

	if (preg_match("@'#[0-9A-F]*'>&nbsp;@", $line) && $ctr == 1) {
		$ctr++;

		$line = preg_replace("@</td>.*@", "", $line);
		$time = preg_replace("@.*&nbsp;@", "", $line);

		$line = preg_replace("@<br.*", "@", $line);
		$date = preg_replace("@.*&nbsp;@", "", $line);

		list($month, $day, $year) = preg_split("@/@", $date);
		list($hour, $minute, $ampm) = preg_split("@[: ]@", $time);
		printf($date); 

		continue;
	}

	if (preg_match("@<BR>@", $line) && $ctr == 2) {
		$ctr++;

		$description = trim(preg_replace("<BR>.*", "", $line));

		$line = preg_replace("@</a>.*@", "", $line);
		$address = preg_replace("@.*'>@", "", $line);
		continue;
	}

	if ($ctr != 3) {
		continue;
	}

	$ctr++;

	if ("$ampm" == "PM") {
		if ($hour < 12) {
			$hour += 12;
		}
	} else if ($hour == 12) {
		$hour = 0;
	}

	$year += 2000;

	$timestamp = "$year-$month-$day $hour:$minute";

	echo "parsed: \n";
	echo "\ttimestamp: $timestamp\n";
	echo "\tdescription: $description\n";
	echo "\taddress: $address\n";
}
?>