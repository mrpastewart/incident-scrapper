<?
require_once("/root/bin/config.php");

$table = "san_antonio";
$url = "https://webapps2.sanantonio.gov/activefire/Fire.aspx";
$email_url = "http://www.sanantonio.gov/SAFD/NewsMediaandReports/ActiveFires.aspx";
$agency = "San Antonio, Texas";

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

echo "Retrieving $url\n";
$page = curl_exec($ch);
echo "Received " . strlen($page) . " bytes\n";

if (strlen($page) < 200) {
	die();
}

$currentTime = time();

$page = ereg_replace(".*>TAC</a></th>", "", $page);
$page = ereg_replace("</table>.*", "", $page);

$lines = explode("\n", $page);

foreach ($lines as $line) {
	if (! ereg("^[ \t]*<td style|<a href=", $line)) {
		continue;
	}

	$line = html_entity_decode($line);

	$line = ereg_replace(" +", " ", $line);

/*
			<td style="width:30px;">1</td><td style="width:140px;">5/24/2014 2:46:42 PM</td><td>Other/Unknown</td><td>
                                    <a href="http://maps.google.com/maps?q=300 Chelsea Dr+78213" target="blank">300 Chelsea Dr </a>
                                </td><td>SCALES/VANCE JACKSON</td><td>Apartment Complexes</td><td>78213</td><td style="width:50px;">*TAC6</td>
*/

	if (ereg("^[ \t]*<td style=", $line)) {
		list($f1, $f2, $f3, $f4, $f5, $f6, $f7) = split("</td>", $line);

		$number_units = trim(ereg_replace(".*>", "", $f1));
		$date_time = ereg_replace(".*>", "", $f2);
		$description = trim(ereg_replace(".*>", "", $f3));
		continue;
	}

	$line = ereg_replace(" *</a>.*", "", $line);
	$address = ereg_replace(".*\">", "", $line);

	if (strlen($description) < 2 || strlen($address) < 2) {
		continue;
	}

	list($date_portion, $time_portion, $ampm_portion) = split(" ", $date_time);

	list($month, $day, $year) = split("/", $date_portion);

	list($hour, $minute, $second) = split(":", $time_portion);

	if ("$ampm_portion" == "PM") {
		if ($hour < 12) {
			$hour += 12;
		}
	} else if ($hour == 12) {
		$hour = 0;
	}

	$timestamp = "$year-$month-$day $hour:$minute:$second";

	echo "parsed: \n";
	echo "\tdate: $date_time\n";
	echo "\ttimestamp: $timestamp\n";
	echo "\tnumber units: $number_units\n";
	echo "\taddress: $address\n";
	echo "\tdescription: $description\n";

	if (eregi("MEDICAL RESPONSE", $description)) {
		continue;
	}

	if ($number_units == "") {
		$number_units = 0;
	}

	$updated = 0;

	// seen?
	$result = mysql_query("select id,units from $table where description = '" . mysql_real_escape_string($description) . "' and address = '" . mysql_real_escape_string($address) . "'");

	if (mysql_num_rows($result) > 0) {
		$row = mysql_fetch_array($result);

		if ($number_units > $row[units]) {
			$updated = 1;
			$id = $row[id];
		}

		if (! $updated) {
			echo "  seen\n";
			continue;
		}
	}

	if ($updated) {
		mysql_query("update $table set units=$number_units,updated=1,time='$timestamp',sent=0 where id=$id");
		echo "  updated\n";
	} else {
		mysql_query("insert into $table (time, description, address, units, updated) values ('$timestamp', '" . mysql_real_escape_string($description) . "', '" . mysql_real_escape_string($address) . "', $number_units, $updated)");
		echo "  inserted\n";
	}
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

$result = mysql_query("select time_format(time, '%H:%i') as timex, description, address, units, updated from $table where sent = 0 order by time desc");

while (($row = mysql_fetch_array($result))) {
	if ($row[updated]) {
		$output .= "$row[timex]: $row[description]; $row[address]; $row[units] units (# units increased) ***\n";
	} else {
		$output .= "$row[timex]: $row[description]; $row[address]; $row[units] units\n";
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
