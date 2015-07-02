<?
require_once("/root/bin/config.php");

$table = "wichita";
$url = "http://cadweb.wfpd.net/ActiveEvents-FD.php";
$email_url = $url;
$agency_name = "Wichita, Texas";

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

//curl_setopt($ch, CURLOPT_VERBOSE, 1);
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

$page = ereg_replace(".*>City<", "" , $page);
$page = ereg_replace("br clear.*", "" , $page);

$lines = explode("\n", $page);

foreach ($lines as $line) {
	if (! ereg("^<td align=left>", $line)) {
		continue;
	}

	list($f1, $f2, $f3, $f4, $f5, $f6, $f7, $f8, $f9) = split("</td>", $line);

	$number_units = ereg_replace(".*left>", "" , $f5);

	if ($number_units < 1) {
		continue;
	}

	$description = ereg_replace(".*left>", "" , $f6);

	$address = ereg_replace(".*left>", "" , $f7);

	if ($address == ".") {
		$address = ereg_replace(".*left>", "" , $f8);
	} else {
		$address .= ", ";
		$address .= ereg_replace(".*left>", "" , $f9);
	}

	if (strlen($description) < 1) {
		continue;
	}

	echo "parsed: \n";
	echo "\tdescription: $description\n";
	echo "\taddress: $address\n";

	// seen?
	$result = mysql_query("select id from $table where description = '" . mysql_real_escape_string($description) . "' and address = '" . mysql_real_escape_string($address) . "' and time > date_sub(now(), interval 1 hour)");

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
