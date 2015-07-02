<?
require_once("/root/bin/config.php");

set_time_limit(600);

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
//	Loop through all agencies
//

$pulsepoint_result = mysql_query("select agency, description, units from pulsepoint_agencies");

while (($pulsepoint_row = mysql_fetch_array($pulsepoint_result))) {
	$agency = $pulsepoint_row[agency];
	$include_units = $pulsepoint_row[units];

	if ($agency == "32D01") {
		continue;
	}

//if ($agency != "43070") { continue; }

	$active_url = "http://webapp.pulsepoint.org/active_incidents.php?agencyid=$agency";
	$recent_url = "http://webapp.pulsepoint.org/recent_incidents.php?agencyid=$agency";
echo "url=$active_url\n";
echo "url=$recent_url\n";
	$email_url = "http://webapp.pulsepoint.org/";
	$agency_name = "$pulsepoint_row[description]";

	$output = "";

	echo "agency = $agency, name = $agency_name\n";

//
//	Retrieve pages
//

curl_setopt($ch, CURLOPT_URL, $active_url);
$page1 = curl_exec($ch);

if (ereg("No Active", $page1)) {
	$page1 = "";
}

curl_setopt($ch, CURLOPT_URL, $recent_url);
$page2 = curl_exec($ch);

if (ereg("No Recent", $page2)) {
	$page2 = "";
}

$page = $page1 . $page2;

$currentTime = time();

$lines = explode("\n", $page);

$units = "";

foreach ($lines as $line) {
	if (ereg("<row id=", $line)) {
		$ctr = 1;
		$alarm = "";
	}

	if (ereg("<cell>", $line) && $ctr == 1) {
		$ctr++;
		$line = ereg_replace(".*<cell>", "", $line);
		$timestamp = ereg_replace("</cell>.*", "", $line);
		continue;
	}

	if (ereg("<cell>", $line) && $ctr == 2) {
		$ctr++;
		$line = ereg_replace(".*<cell>", "", $line);
		$description = ereg_replace("</cell>.*", "", $line);
		continue;
	}

	if (ereg("<cell>", $line) && $ctr == 3) {
		$ctr++;
		$line = ereg_replace(".*<cell>", "", $line);
		$address = ereg_replace("</cell>.*", "", $line);
		continue;
	}

	if (eregi("^&lt;br.* alarm", $line) && $ctr == 4) {
		$alarm = eregi_replace("alarm.*", "Alarm", $line);
		$alarm = ereg_replace(".*&gt;", "", $alarm);
	}

	if (ereg("<cell>", $line) && $ctr == 4 && $include_units) {
		$units = ereg_replace("<cell>", "", $line);
		$units = ereg_replace("</cell>.*", "", $units);

		$units = ereg_replace("&lt;/font&gt;", "", $units);
		$units = ereg_replace("&lt;font color='#[0-9A-F]+'&gt;", "", $units);
		continue;
	}

	if (ereg("<cell hidden", $line) && $ctr == 4) {
		$ctr++;
		continue;
	}

	if (ereg("<cell hidden", $line) && $ctr == 5) {
		$ctr++;
		$line = ereg_replace(".*'>", "", $line);
		$description = ereg_replace("</cell>.*", "", $line);
		continue;
	}

	if (! ereg("</row>", $line)) {
		continue;
	}

	$ctr++;

	$description = ereg_replace("  +", " ", $description);
	$address = ereg_replace("&amp;", "&", $address);
	$address = ereg_replace("  +", " ", $address);

	if (strlen($alarm) > 0) {
		$description .= " ($alarm)";
	}

	if (eregi("Medical Emergency", $description)) {
		continue;
	}

	echo "parsed: \n";
	echo "\tdescription: $description\n";
	echo "\taddress: $address\n";
	echo "\tunits: $units\n";

	// seen?
	$result = mysql_query("select time from pulsepoint where agency='" . mysql_real_escape_string($agency) . "' and description = '" . mysql_real_escape_string($description) . "' and address = '" . mysql_real_escape_string($address) . "'");

	if (mysql_num_rows($result) > 0) {
		echo "  seen\n";
		continue;
	}

	mysql_query("insert into pulsepoint (time, agency, address, description, units) values (now(), '" . mysql_real_escape_string($agency) . "', '" . mysql_real_escape_string($address) . "', '" . mysql_real_escape_string($description) . "', '" . mysql_real_escape_string($units) . "')");

	echo "  inserted\n";

	$units = "";
}

//
//	Send e-mails
//

if ($email_url == "") {
	$email_url = $url;
}

// get timezone offset
$offset = get_timezone_offset('UTC');

mysql_query("start transaction");

$result = mysql_query("select time_format(date_add(time, interval $offset second), '%H:%i') as timex, address, description, units from pulsepoint where sent = 0 and agency='$agency' order by time desc");

while (($row = mysql_fetch_array($result))) {
	if (strlen($units) > 0) {
		$output .= "$row[timex]: $row[description]; $row[address] ($units)\n";
	} else {
		$output .= "$row[timex]: $row[description]; $row[address]\n";
	}
}

mysql_query("update pulsepoint set sent=1 where agency='$agency'");

mysql_query("commit");

if (strlen($output) > 0) {
	$headers = "From: \"BBScanner.com\" <gordon@bbscanner.com>\n"
	."X-Mailer: PHP/" . phpversion() . "\n"
	."MIME-Version: 1.0\n"
	."Content-Type: text/plain\n";

	$output .= "--\n$email_url\n";

	if ($include_units) {
	mail("gordon@gordonedwards.net", "Dispatches from $agency_name", "$output", $headers, "-fgordon@bbscanner.com");
	}

	mail("scrapealerts@gmail.com", "Dispatches from $agency_name", "$output", $headers, "-fgordon@bbscanner.com");
}
}

mysql_query("delete from pulsepoint where time < date_sub(now(), interval 1 month)");

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
