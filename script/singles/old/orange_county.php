<?
require_once("/root/bin/config.php");

$table = "orange_county";
$url = "http://www.orangecountyfl.net/EmergencySafety/FireRescueActiveCalls.aspx";
$email_url = $url;
$agency_name = "Orange County, Florida";

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

$page = ereg_replace(".*MAP</th>", "", $page);
$page = ereg_replace("</table>.*", "", $page);

$lines = explode("\n", $page);

foreach ($lines as $line) {
	if (ereg("<tr[ >]", $line)) {
		$ctr = 1;
		continue;
	}

	if (ereg("DESCRIPTIONLabel", $line) && $ctr == 1) {
		$ctr++;

		$line = ereg_replace(".*Label.*\">", "", $line);
		$description = trim(ereg_replace("</span>.*", "", $line));

		$address = "";
		continue;
	}

	if (ereg("TYPELabel", $line) && $ctr == 2) {
		$ctr++;

		$line = ereg_replace(".*Label.*\">", "", $line);
		$line = trim(ereg_replace("</span>.*", "", $line));

		if (strlen($line) > 0) {
			$description .= "; $line";
		}

		continue;
	}

	if (ereg("NOLabel", $line) && $ctr == 3) {
		$ctr++;

		$line = ereg_replace(".*Label.*\">", "", $line);
		$address = ereg_replace("</span>.*", "", $line);
		continue;
	}

	if (ereg("NAMELabel", $line) && $ctr == 4) {
		$ctr++;

		$line = ereg_replace("</a>.*", "", $line);
		$line = ereg_replace("</span>.*", "", $line);
		$line = ereg_replace(".*>", "", $line);

		$address .= " $line";
		continue;
	}

	if ($ctr != 5) {
		continue;
	}

	$ctr++;

	if (strlen($address) < 1) {
		continue;
	}

	echo "parsed: \n";
	echo "\tdescription: $description\n";
	echo "\taddress: $address\n";

	// seen?
	$result = mysql_query("select time from $table where description = '" . mysql_real_escape_string($description) . "' and address = '" . mysql_real_escape_string($address) . "'");

	if (mysql_num_rows($result) > 0) {
		echo "  seen\n";
		continue;
	}

	mysql_query("insert into $table (time, address, description) values (now(), '" . mysql_real_escape_string($address) . "', '" . mysql_real_escape_string($description) . "')");

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

$result = mysql_query("select time_format(date_add(time, interval $offset second), '%H:%i') as timex, address, description from $table where sent = 0 order by time desc");

while (($row = mysql_fetch_array($result))) {
	$output .= "$row[timex]: $row[description]; $row[address]\n";
}

mysql_query("update $table set sent=1");

mysql_query("commit");

if (strlen($output) > 0) {
	$headers = "From: \"BBScanner.com\" <gordon@bbscanner.com>\n"
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
