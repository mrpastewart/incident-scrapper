<?
require_once("/root/bin/config.php");

$table = "genesee_county";
$url = "http://geneseecounty911.org/events.php";
$agency_name = "Genesee County, Michigan";

//
//	Connect to database
//

if (! mysql_pconnect($dbhost, $dbuname, $dbpass)) {
	die("Unable to connect to phpnuke database server.\n");
}

if (! mysql_select_db("jkinley1")) {
	die("Unable to select database\n");
}

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

$page = ereg_replace(".*<th>Jurisdiction</th>", "", $page);
$page = ereg_replace("table.*", "", $page);

$lines = explode("\n", $page);

foreach ($lines as $line) {
	if (ereg("<tr class", $line)) {
		$ctr = 1;
		continue;
	}

	if (ereg("<td class", $line) && $ctr == 1) {
		$ctr++;
		$line = ereg_replace("</td>.*", "", $line);
		$date = ereg_replace(".*>", "", $line);
		continue;
	}

	if (ereg("<td class", $line) && $ctr == 2) {
		$ctr++;
		$line = ereg_replace("</td>.*", "", $line);
		$time = ereg_replace(".*>", "", $line);
		$time = ereg_replace("&nbsp;", " ", $time);
		continue;
	}

	if (ereg("<td class", $line) && $ctr == 3) {
		$ctr++;
		$line = ereg_replace("</td>.*", "", $line);
		$description = ereg_replace(".*>", "", $line);
		continue;
	}

	if (ereg("<td class", $line) && $ctr == 4) {
		$ctr++;
		$line = ereg_replace("</a>.*", "", $line);
		$line = ereg_replace("</td>.*", "", $line);
		$address = ereg_replace(".*>", "", $line);
		continue;
	}

	if (ereg("<td class", $line) && $ctr == 5) {
		$ctr++;
		$line = ereg_replace("</td>.*", "", $line);
		$location = ereg_replace(".*>", "", $line);

		if (strlen($location) > 0) {
			$address .= "; $location";
		}

		continue;
	}

	if (ereg("<td class", $line) && $ctr == 6) {
		$ctr++;
		$line = ereg_replace("</td>.*", "", $line);
		$city = ereg_replace(".*>", "", $line);

		if (strlen($city) > 0) {
			$address .= "; $city";
		}

//		continue;
	}

	if ($ctr != 7) {
		continue;
	}

	$ctr++;

	$address = ereg_replace("  +", " ", $address);

	list($month, $day) = split("/", $date);
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

	// seen?
	$result = mysql_query("select time from $table where description = '" . mysql_real_escape_string($description) . "' and address = '" . mysql_real_escape_string($address) . "' and time='$timestamp'");

	if (mysql_num_rows($result) > 0) {
		echo "  seen\n";
		continue;
	}

	mysql_query("insert into $table (time, address, description) values ('$timestamp', '" . mysql_real_escape_string($address) . "', '" . mysql_real_escape_string($description) . "')");

	echo "  inserted\n";

}

mysql_query("delete from $table where time < date_sub(now(), interval 1 month)");

//
//	Send e-mails
//

if ($email_url == "") {
	$email_url = $url;
}

// get timezone offset
$offset = get_timezone_offset('UTC');

mysql_query("start transaction");

$result = mysql_query("select time_format(time, '%H:%i') as timex, address, description from $table where sent = 0 order by time desc");

while (($row = mysql_fetch_array($result))) {
	$output .= "$row[timex]: $row[description]; $row[address]\n";
}

mysql_query("update $table set sent=1");

mysql_query("commit");

if (strlen($output) > 0) {
	$headers = "From: \"Scraper\" <gordon@bbscanner.com>\n"
	."X-Mailer: PHP/" . phpversion() . "\n"
	."MIME-Version: 1.0\n"
	."Content-Type: text/plain\n";

	$output .= "--\n$email_url\n";

//	mail("gordon@gordonedwards.net", "Dispatches from $agency_name", "$output", $headers, "-fgordon@bbscanner.com");
	mail("scrapealerts@gmail.com", "Dispatches from $agency_name", "$output", $headers, "-fgordon@bbscanner.com");
}

/**    Returns the offset from the origin timezone to the remote timezone, in seconds.
 *    @param $remote_tz;
 *    @param $origin_tz; If null the servers current timezone is used as the origin.
 *    @return int;
 */

function get_timezone_offset($remote_tz, $origin_tz = null) {
    if($origin_tz === null) {
        if(!is_string($origin_tz = date_default_timezone_get())) {
            return false; // A UTC timestamp was returned -- bail out!
        }
    }

    $origin_dtz = new DateTimeZone($origin_tz);
    $remote_dtz = new DateTimeZone($remote_tz);
    $origin_dt = new DateTime("now", $origin_dtz);
    $remote_dt = new DateTime("now", $remote_dtz);
    $offset = $origin_dtz->getOffset($origin_dt) - $remote_dtz->getOffset($remote_dt);
    return $offset;
}
?>
