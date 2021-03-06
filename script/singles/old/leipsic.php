<?
require_once("/root/bin/config.php");

$table = "leipsic";
$url = "http://leipsicvfc.com/history.html";
$email_url = $url;
$agency_name = "Leipsic VFD, Dover, Deleware";

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

$page = ereg_replace(".*>Print this page</a><br>", "", $page);
$page = ereg_replace(">Recent Calls<.*", "", $page);

$lines = explode("\n", $page);

foreach ($lines as $line) {
	if (ereg("class=.stitle.", $line)) {
		$ctr = 1;

		$line = ereg_replace("</i></td></tr>.*", "", $line);
		$date = ereg_replace(".*><i>", "", $line);
		continue;
	}

	if (ereg("Time:", $line)) {
		$ctr++;
		$time = trim(ereg_replace(".*Time: *", "", $line));
		continue;
	}

	if (ereg("Nature:", $line)) {
		$ctr++;
		$line = ereg_replace(".*Nature: *", "", $line);
		$description = trim(ereg_replace("&nbsp;", " ", $line));
		continue;
	}

	if (ereg("Location:", $line)) {
		$ctr++;
		$line = ereg_replace(".*Location: *", "", $line);
		$address = trim(ereg_replace("&nbsp;", " ", $line));
		continue;
	}

	if (ereg("District:", $line)) {
		$ctr++;
		$line = ereg_replace(".*District: *", "", $line);
		$line = trim(ereg_replace("&nbsp;", " ", $line));

		if (strlen($line) > 0) {
			if (strlen($address) > 0) {
				$address .= "; ";
			}

			$address .= $line;
		}
		continue;
	}

	if (! ereg("Read more", $line)) {
		continue;
	}

	$description = ereg_replace("  +", " ", $description);
	$address = ereg_replace("  +", " ", $address);

	$date = ereg_replace("\.", "", $date);

	list($dow, $month, $day, $year) = split(" ", $date);
	$day = ereg_replace("[a-z].*", "", $day);

	if ($month == "Jan") { $month = "01"; }
	if ($month == "Feb") { $month = "02"; }
	if ($month == "Mar") { $month = "03"; }
	if ($month == "Apr") { $month = "04"; }
	if ($month == "May") { $month = "05"; }
	if ($month == "Jun") { $month = "06"; }
	if ($month == "Jul") { $month = "07"; }
	if ($month == "Aug") { $month = "08"; }
	if ($month == "Sep") { $month = "09"; }
	if ($month == "Oct") { $month = "10"; }
	if ($month == "Nov") { $month = "11"; }
	if ($month == "Dec") { $month = "12"; }

	$timestamp = "$year-$month-$day $time";

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
