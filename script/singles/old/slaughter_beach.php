<?
require_once("/root/bin/config.php");

$table = "slaughter_beach";
$url = "http://www.memorialfire89.com/runlog.cfm";
$email_url = $url;
$agency_name = "Slaughter Beach, Milford, Delaware";

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

$page = ereg_replace(".*Live Run Log", "", $page);
$page = ereg_replace("Displaying.*", "", $page);

$lines = explode("\n", $page);

foreach ($lines as $line) {
	if (ereg("<div class=.psdate.>", $line)) {
		$ctr = 1;

		$line = ereg_replace("</div.*", "", $line);
		$line = ereg_replace(".*<div class=.psdate.>", "", $line);
		$line = ereg_replace(",", "", $line);
		list($dow, $month, $day, $year, $junk, $time_portion) = split(" ", $line);

		$address = "";
		continue;
	}

	if (ereg(">Nature:<", $line) && $ctr == 1) {
		$ctr++;

		if (ereg(">Location:<", $line)) {
			$location = ereg_replace(".*>Location:<", "", $line);
			$location = ereg_replace("<br.*", "", $location);
			$location = ereg_replace(".*/strong> *", "", $location);

			if (strlen($address) > 0) {
				$address .= "; ";
			}

			$address .= $location;
		}

		$line = ereg_replace(">Location:<.*", "", $line);
		$line = ereg_replace("<br.*", "", $line);
		$description = ereg_replace(".*</strong> *", "", $line);

		continue;
	}

	if (ereg(">Location:|>City:|>Address:", $line) && $ctr == 2) {
		$line = ereg_replace(".*>City: *", "", $line);
		$line = ereg_replace(".*</strong> *", "", $line);
		$line = ereg_replace("<br.*", "", $line);

		if (strlen($address) > 0) {
			$address .= "; ";
		}

		$address .= $line;

		continue;
	}

	if (ereg("[ \t]*</div>", $line) && $ctr == 2) {
		$ctr++;
	}

	if ($ctr != 3) {
		continue;
	}

	$ctr++;

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

	// seen?
	$result = mysql_query("select time from $table where description = '" . mysql_real_escape_string($description) . "' and address = '" . mysql_real_escape_string($address) . "' and time='$timestamp'");

	if (mysql_num_rows($result) > 0) {
		echo "  seen\n";
		continue;
	}

	mysql_query("insert into $table (time, address, description) values ('$timestamp', '" . mysql_real_escape_string($address) . "', '" . mysql_real_escape_string($description) . "')");

	echo "  inserted\n";

}

mysql_query("delete from $table where time < date_sub(now(), interval 1 year)");

//
//	Send e-mails
//

if ($email_url == "") {
	$email_url = $url;
}

// get timezone offset
$offset = get_timezone_offset('UTC');

mysql_query("start transaction");

$result = mysql_query("select time_format(time, '%H:%i:%s') as timex, address, description from $table where sent = 0 order by time desc");

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
