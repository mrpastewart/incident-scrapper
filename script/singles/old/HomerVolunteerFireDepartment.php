<?
require_once("/root/bin/config.php");

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
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)");
curl_setopt($ch, CURLOPT_POST, 0);

//
//	Retrieve page
//

curl_setopt($ch, CURLOPT_URL, "http://www.livecad.us/livecad/livecad.aspx?DeptID=OH-45-470");
$page = curl_exec($ch);

if (strlen($page) < 2000) {
	die();
}

$currentTime = time();

$page = ereg_replace(".*<th class=.GVHeader. scope=.col.>Time</th>", "", $page);

$lines = explode("\n", $page);

foreach ($lines as $line) {
	if (! ereg("GVRow", $line)) {
		continue;
	}

	if (! ereg("</td><td align=.left. style=", $line)) {
		continue;
	}

	$line = ereg_replace("</td><td", "COLSEP", $line);

	list($f1, $f2, $f3, $f4, $f5, $f6) = split("COLSEP", $line);

	$f2 = ereg_replace(".*\">", "", $f2);
	$address = ereg_replace("</a>.*", "", $f2);

	$f3 = ereg_replace(".*\">", "", $f3);
	$location = ereg_replace("</a>.*", "", $f3);

	$f4 = ereg_replace(".*\">", "", $f4);
	$f4 = ereg_replace("<a>", "", $f4);
	$description = ereg_replace("</a>.*", "", $f4);

	$f6 = ereg_replace(".*\">", "", $f6);
	$f6 = ereg_replace("<a>", "", $f6);
	$time = ereg_replace("</a>.*", "", $f6);

	list($hour, $minute) = split(":", $time);

	echo "parsed: \n";
	echo "\ttime = $time, hour = $hour, minute = $minute\n";
	echo "\taddress: $address\n";
	echo "\tlocation: $location\n";
	echo "\tdescription = $description\n";

	$time = mktime($hour, $minute, 00);

	if ($time > $currentTime) {
		$time -= 86400;
	}

	// seen?
	$result = mysql_query("select time from homer_vfd where description = '" . mysql_real_escape_string($description) . "' and address = '" . mysql_real_escape_string($address) . "' and location='" . mysql_real_escape_string($location) . "'");

	if (mysql_num_rows($result) > 0) {
		echo "  seen\n";
		continue;
	}

	mysql_query("insert into homer_vfd (time, description, address, location) values (from_unixtime($time), '" . mysql_real_escape_string($description) . "', '" . mysql_real_escape_string($address) . "', '" . mysql_real_escape_string($location) . "')");

	echo "  inserted\n";
}

mysql_query("delete from homer_vfd where time < date_sub(now(), interval 1 day)");

//
//	Send e-mails
//

// get timezone offset
$offset = get_timezone_offset('UTC');

mysql_query("start transaction");

$result = mysql_query("select time_format(date_add(time, interval $offset second), '%H:%i') as timex, description, address, location from homer_vfd where sent = 0 order by time desc");

while (($row = mysql_fetch_array($result))) {
	$output .= "$row[timex]: $row[description]; $row[address]; $row[location]\n";
}

mysql_query("update homer_vfd set sent=1");

mysql_query("commit");

if (strlen($output) > 0) {
	$headers = "From: \"BBScanner.com\" <gordon@bbscanner.com>\n"
	."X-Mailer: PHP/" . phpversion() . "\n"
	."MIME-Version: 1.0\n"
	."Content-Type: text/plain\n";

	$output .= "--\nhttp://www.livecad.us/livecad/livecad.aspx?DeptID=OH-45-470\n";

//	mail("gordon@gordonedwards.net", "Dispatches from Homer VFD", "$output", $headers, "-fgordon@bbscanner.com");
	mail("scrapealerts@gmail.com", "Dispatches from Homer VFD", "$output", $headers, "-fgordon@bbscanner.com");
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
