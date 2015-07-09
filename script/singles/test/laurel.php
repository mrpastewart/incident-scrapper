<?php
/**
 * Created by PhpStorm.
 * User: LucasWang
 * Date: 7/7/15
 * Time: 10:44 AM
 */
 $url = "http://www.laurelfiredept.com/runlog.cfm";
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

$page = preg_replace("@.*<td class=.cm. valign=.top. width=.98%.>@", "", $page);
$page = preg_replace("@Displaying.*@", "", $page);

$lines = explode("\n", $page);

foreach ($lines as $line) {
	if (preg_match("@<td class=.tbl1tr.><div class=.tbl1tl.>@", $line)) {
		$ctr = 1;

		$line = preg_replace("@.*<td class=.tbl1tr.><div class=.tbl1tl.>@", "", $line);
		$line = preg_replace("@</div>.*@", "", $line);
		$line = preg_replace("@,@", "", $line);
		list($dow, $month, $day, $year, $junk, $time_portion) = preg_split("@ @", $line);
		continue;
	}

	if (preg_match("@<strong>Nature:</strong>@", $line) && $ctr == 1) {
		$ctr++;

		$tline = "";
		$address = "";

		if (preg_match("@Location:@", $line)) {
			$address = preg_replace("@.*</strong> *@", "", $line);
			$address = preg_replace("@<br/>.*@", "", $address);

			$line = preg_replace("@<strong>Location.*@", "", $line);
		}

		$line = preg_replace("@.*</strong> *@", "", $line);
		$description = preg_replace("@<br/>.*@", "", $line);
	}

	if (preg_match("@<strong>City:</strong>|<strong>Address:</strong>@", $line) && $ctr == 2) {
		$ctr++;
		$line = preg_replace("@<strong>City:</strong>|<strong>Address:</strong> *@", "", $line);

		if (strlen($address) > 0) {
			$address .= ", ";
		}

		$address .= trim(preg_replace("@<br/>.*@", "", $line));
		continue;
	}

	if ($ctr != 3) {
		continue;
	}

	$ctr++;

	$description = trim($description);

	$description = html_entity_decode($description);
	$description = preg_replace("@ +@", " ", $description);

	if ($month == "January") { $month = "01"; }
	if ($month == "February") { $month = "02"; }
	if ($month == "March") { $month = "03"; }
	if ($month == "April") { $month = "04"; }
	if ($month == "May") { $month = "05"; }
	if ($month == "June") { $month = "06"; }
	if ($month == "July") { $month = "07"; }
	if ($month == "August") { $month = "08"; }
	if ($month == "September") { $month = "09"; }
	if ($month == "October") { $month = "10"; }
	if ($month == "November") { $month = "11"; }
	if ($month == "December") { $month = "12"; }

	$timestamp = "$year-$month-$day $time_portion";

	echo "parsed: \n";
	echo "\ttimestamp: $timestamp\n";
	echo "\tdescription: $description\n";
	echo "\taddress: $address\n";
	}
	?>