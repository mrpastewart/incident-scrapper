<?
require_once("/root/bin/config.php");

$table = "indianapolis";
$url = "https://indy.safetown.org/idex/v1/event/list/1fa83f26-4800-4b6f-872a-22bcc217f441/";
$email_url = "https://indy.safetown.org/community-alerts/";
$agency_name = "Indianapolis, Indiana";

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

die($page);

$currentTime = time();

//$page = ereg_replace(".*<th>URL</th></tr>", "" , $page);
//$page = ereg_replace("</tr></table>.*", "" , $page);

$lines = explode("{\"eventId\":", $page);

foreach ($lines as $line) {
	echo ">>> $line\n";
continue;

	$line = ereg_replace(" style='color:red;'", "" , $line);
	$line = ereg_replace(".*</center></td><td>", "" , $line);
	$line = ereg_replace("<td>", "" , $line);

	list($f1, $f2, $f3, $f4, $f5, $f6, $address) = split("</th>", $line);

	$description = ereg_replace(".*\">", "" , $f2);
	$description = ereg_replace("</a>.*", "" , $description);

	$address = ereg_replace(".*\">", "" , $f4);
	$address = ereg_replace("</a>.*", "" , $address);

	$units = "";

	if (ereg("<span", $f5)) {
		$unit_array = explode("</span>", $f5);

		foreach ($unit_array as $unit) {
			$unit = trim(ereg_replace(".*\">", "" , $unit));

			if (strlen($unit) > 0) {
				if (strlen($units) > 0) {
					$units .= ", ";
				}
	
				$units .= $unit;
			}
		}
	}

	if (strlen($units) == 0) {
		continue;
	}

	echo "parsed: \n";
	echo "\tdescription: $description\n";
	echo "\taddress: $address\n";
	echo "\tunits: $units\n";

	// seen?
	$result = mysql_query("select units from $table where description = '" . mysql_real_escape_string($description) . "' and address = '" . mysql_real_escape_string($address) . "' and units = '" . mysql_real_escape_string($units) . "' and time > date_sub(now(), interval 1 hour)");

	if (mysql_num_rows($result) > 0) {
		echo "  seen\n";
		continue;
	}

	mysql_query("insert into $table (time, address, description, units) values (now(), '" . mysql_real_escape_string($address) . "', '" . mysql_real_escape_string($description) . "', '" .  mysql_real_escape_string($units) . "')");

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

$result = mysql_query("select time_format(time, '%H:%i:%s') as timex, address, description, units from $table where sent = 0 order by time desc");

while (($row = mysql_fetch_array($result))) {
	$output .= "$row[timex]: $row[description]; $row[address] ($row[units])\n";
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
