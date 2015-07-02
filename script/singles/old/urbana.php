<?
require_once("/root/bin/config.php");

$table = "urbana";
$url = "http://www.urbanavfd.org/uvfd-wp/category/cad/";
$email_url = $url;
$agency_name = "Urbana Volunteer Fire Department";

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

$page = ereg_replace(".*Archive for CAD", "", $page);
$page = ereg_replace("Older posts.*", "", $page);

$lines = explode("\n", $page);

foreach ($lines as $line) {
	if (ereg("Posted on", $line)) {
		$ctr = 1;

		$line = ereg_replace("</span></a><span class=.byline.>.*", "", $line);
		$line = ereg_replace(".*title=.", "", $line);
		$line = ereg_replace(". rel=.bookmark.><span class=.timestamp updated.>", " ", $line);

		list($time_portion, $ampm_portion, $date_portion) = split(" ", $line);
		continue;
	}

	if (ereg("<div class=.post-entry.>", $line) && $ctr == 1) {
		$ctr++;
		$s = "";
		continue;
	}

	if (ereg("</div>", $line) && $ctr == 2) {
		$ctr++;
		continue;
	}

	if ($ctr == 2) {
		$s .= " $line";
	}

	if ($ctr != 3) {
		continue;
	}

	$ctr++;

	$s = ereg_replace("<p>", " ", $s);
	$s = ereg_replace("</p>", " ", $s);

	$s = trim($s);

	$s = html_entity_decode($s);
	$s = ereg_replace(" +", " ", $s);

	$description = $s;

	list($month, $day, $year) = split("/", $date_portion);

	list($hour, $minute) = split(":", $time_portion);

	if ("$ampm_portion" == "PM") {
		if ($hour < 12) {
			$hour += 12;
		}
	} else if ($hour == 12) {
		$hour = 0;
	}

	$timestamp = "$year-$month-$day $hour:$minute:00";

	echo "parsed: \n";
	echo "\tdate: $date_portion\n";
	echo "\ttime: $time_portion $ampm_portion\n";
	echo "\ttimestamp: $timestamp\n";
	echo "\tdescription: $description\n";

	// seen?
	$result = mysql_query("select time from $table where description = '" . mysql_real_escape_string($description) . "'");

	if (mysql_num_rows($result) > 0) {
		echo "  seen\n";
		continue;
	}

	mysql_query("insert into $table (time, description) values ('$timestamp', '" . mysql_real_escape_string($description) . "')");

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

$result = mysql_query("select time_format(time, '%H:%i') as timex, description from $table where sent = 0 order by time desc");

while (($row = mysql_fetch_array($result))) {
	$output .= "$row[timex]: $row[description]\n";
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
