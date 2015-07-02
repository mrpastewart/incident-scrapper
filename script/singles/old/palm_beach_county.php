<?
require_once("/root/bin/config.php");

$table = "palm_beach_county";
$url = "http://www.co.palm-beach.fl.us/livescannerwebsvcs/api/LiveScanner/";
$email_url = "http://www.co.palm-beach.fl.us/livescannerwebsvcs/index.html";
$agency = "Palm Beach County Fire/Rescue";

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

$page = ereg_replace("\],\"Units\":.*", "", $page);
$page = ereg_replace("{.Events.:\[", "", $page);
$page = ereg_replace("{\"Event_No\":\"", "\n", $page);

$lines = explode("\n", $page);

foreach ($lines as $line) {
	$cad_id = ereg_replace("\",\"Description.*", "", $line);

	$description = ereg_replace(".*Description\":\"", "", $line);
	$description = ereg_replace("\",\"Event_Type.*", "", $description);

	if (strlen($cad_id) < 1 || strlen($description) < 1) {
		continue;
	}

	echo "parsed: \n";
	echo "\tcad_id: $cad_id\n";
	echo "\tdescription: $description\n";

	// seen?
	$result = mysql_query("select time from $table where cad_id = '" . mysql_real_escape_string($cad_id) . "'");

	if (mysql_num_rows($result) > 0) {
		echo "  seen\n";
		continue;
	}

	mysql_query("insert into $table (time, cad_id, description) values (now(), '" . mysql_real_escape_string($cad_id) . "', '" . mysql_real_escape_string($description) . "')");

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

$result = mysql_query("select time_format(date_add(time, interval $offset second), '%H:%i')as timex, cad_id, description from $table where sent = 0 order by time desc");

while (($row = mysql_fetch_array($result))) {
	$output .= "$row[timex]: $row[cad_id]; $row[description]\n";
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
