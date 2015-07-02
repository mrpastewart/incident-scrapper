<?
require_once("/root/bin/config.php");

$table = "tampa";
$url = "http://apps.tampagov.net/appl_fire_calls_for_service/frmCallsList.asp";
$email_url = $url;
$agency = "Tampa, Florida";

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

$page = ereg_replace(".*Incident #", "", $page);
$page = ereg_replace("To Top Of Page.*", "", $page);

$lines = explode("\n", $page);

$ctr = 0;

foreach ($lines as $line) {
	if (ereg("^[ \t]+<tr>", $line)) {
		$ctr = 1;
		continue;
	}

	if (ereg("<td align=", $line) && $ctr == 1) {
		$ctr++;
		continue;
	}

	if (ereg("/.*/.*:", $line) && $ctr == 2) {
		$date_time = trim($line);
		$address = "";
		$ctr++;
		continue;
	}

	if (ereg("<td align=", $line) && $ctr == 3) {
		$ctr++;
		continue;
	}

	if ($ctr == 4) {
		$line = ereg_replace("  +", " ", $line);
		$line = ereg_replace("/ +", "/", $line);

		if (ereg("title=", $line)) {
			$line = ereg_replace(".*title=", "", $line);
			$description = ereg_replace(" *&.*", "", $line);

			$address = ereg_replace(".*txtaddress= *", "", $line);
			$address = ereg_replace("&txturl=.*", "", $address);
		} else if (ereg("GRID ONLY", $line)) {
			$line = ereg_replace(".*GRID ONLY <br>", "", $line);
			$description = ereg_replace(" *<fieldset.*", "", $line);
			$description = ereg_replace(" *<br.*", "", $description);
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

	list($date_portion, $time_portion, $ampm_portion) = split(" ", $date_time);

	list($month, $day, $year) = split("/", $date_portion);

	list($hour, $minute) = split(":", $time_portion);

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

	// seen?
	$result = mysql_query("select time from $table where time='$timestamp' and description = '" . mysql_real_escape_string($description) . "' and address = '" . mysql_real_escape_string($address) . "'");

	if (mysql_num_rows($result) > 0) {
		echo "  seen\n";
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

//
//	Send e-mails
//

if ($email_url == "") {
	$email_url = $url;
}

// get timezone offset
$offset = get_timezone_offset('UTC');

mysql_query("start transaction");

//$result = mysql_query("select time_format(date_add(time, interval $offset second), '%H:%i') as timex, description, address, updated from $table where sent = 0 order by time desc");
$result = mysql_query("select time_format(time, '%H:%i') as timex, description, address, updated from $table where sent = 0 order by time desc");

while (($row = mysql_fetch_array($result))) {
	if ($row[updated]) {
		$output .= "$row[timex]: $row[description]; $row[address] (nature changed)\n";
	} else if (strlen($row[address]) > 0) {
		$output .= "$row[timex]: $row[description]; $row[address]\n";
	} else {
		$output .= "$row[timex]: $row[description]\n";
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
