<?
require_once("/root/bin/config.php");

$table = "northumberland_county";
$url = "https://cad.norrycopa.net/Pages/Public/LiveIncidents.aspx";
$email_url = "https://cad.norrycopa.net/Pages/Public/LiveIncidents.aspx";
$agency = "Northumberland County, Pennsylvania";

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

$page = ereg_replace("AFA TEST.*", "", $page);
$page = ereg_replace(".*lblPageRefreshed", "", $page);

$lines = explode("\n", $page);

foreach ($lines as $line) {
	if (! (ereg("<td class", $line) || ereg("tdLocation", $line) || ereg("dxgvDataRow_Glass", $line))) {
		continue;
	}

	$line = ereg_replace("&nbsp;", " ", $line);
	$line = html_entity_decode($line);

	if (ereg("dxgvDataRow_Glass", $line)) {
		$ctr = 1;
		continue;
	}

	if (ereg("<td class", $line)) {
		if ($ctr == 1) {
			$ctr++;

			$date_time = ereg_replace(" *</td>.*", "", $line);
			$date_time = ereg_replace(".*>", "", $date_time);

			continue;
		}

		if ($ctr == 2) {
			$ctr++;

			$description = ereg_replace(" *</td>.*", "", $line);
			$description = ereg_replace(".*\">", "", $description);

			continue;
		}
	}

	if (! (ereg("tdLocation", $line) && $ctr == 3)) {
		continue;
	}

	$address = ereg_replace("&nbsp;</td>.*", "", $line);
	$address = ereg_replace("</td>.*", "", $address);
	$address = eregi_replace("<br>", "; ", $address);
	$address = eregi_replace(" *; *", "; ", $address);
	$address = ereg_replace(".*>", "", $address);

	list($date_portion, $time_portion) = split(" ", $date_time);

	list($month, $day, $year) = split("/", $date_portion);
	list($hour, $minute, $second) = split(":", $time_portion);

	$timestamp = "$year-$month-$day $hour:$minute:$second";

	echo "parsed: \n";
	echo "\tdate/time: $date_time\n";
	echo "\ttimestamp: $timestamp\n";
	echo "\taddress: $address\n";
	echo "\tdescription: $description\n";

	// seen?
	$result = mysql_query("select id from $table where description = '" . mysql_real_escape_string($description) . "' and address = '" . mysql_real_escape_string($address) . "'");

	if (mysql_num_rows($result) > 0) {
		$row = mysql_fetch_array($result);
		echo "  seen, id = $row[id]\n";
		continue;
	}

	// see if nature changed for same address
	$result = mysql_query("select time from $table where address = '" . mysql_real_escape_string($address) . "' and time > date_sub(now(), interval 2 hour)");

	if (mysql_num_rows($result) > 0) {
		$updated = 1;
	} else {
		$updated = 0;
	}

	mysql_query("insert into $table (time, description, address, updated) values ('$timestamp', '" . mysql_real_escape_string($description) . "', '" . mysql_real_escape_string($address) . "', $updated)");

	echo "  inserted\n";
}

mysql_query("delete from $table where time < date_sub(now(), interval 1 week)");

//die("***** DEBUG *****\n");

//
//	Send e-mails
//

if ($email_url == "") {
	$email_url = $url;
}

// get timezone offset
$offset = get_timezone_offset('UTC');

mysql_query("start transaction");

//$result = mysql_query("select time_format(date_add(time, interval $offset second), '%H:%i:%s') as timex, description, address, updated from $table where sent = 0 order by time desc");
$result = mysql_query("select time_format(time, '%H:%i') as timex, description, address, updated from $table where sent = 0 order by time desc");

while (($row = mysql_fetch_array($result))) {
	if ($row[updated]) {
		$output .= "$row[timex]: $row[description]; $row[address] (nature changed)\n";
	} else {
		$output .= "$row[timex]: $row[description]; $row[address]\n";
	}
}

mysql_query("update $table set sent=1");

mysql_query("commit");

if (strlen($output) > 0) {
	$headers = "From: \"BBScanner.com\" <gordon@bbscanner.com>\n"
	."X-Mailer: PHP/" . phpversion() . "\n"
	."MIME-Version: 1.0\n"
	."Content-Type: text/plain\n";

	$output .= "--\n$email_url\n";

//	mail("gordon@gordonedwards.net", "Dispatches from $agency", "$output", $headers, "-fgordon@bbscanner.com");
	mail("scrapealerts@gmail.com", "Dispatches from $agency", "$output", $headers, "-fgordon@bbscanner.com");
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
