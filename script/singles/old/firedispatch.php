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

$views = array("SMCO", "SantaCruz", "PaloAlto", "SanRamon", "Yolo");

$agency_ids["SMCO"] = "04100";
$agency_ids["SantaCruz"] = "04400";
$agency_ids["PaloAlto"] = "0431200";
$agency_ids["SanRamon"] = "0070001";
$agency_ids["Yolo"] = "0570000";

$agency_names["SMCO"] = "San Mateo County";
$agency_names["SantaCruz"] = "Santa Cruz County";
$agency_names["PaloAlto"] = "Palo Alto";
$agency_names["SanRamon"] = "San Ramon Valley";
$agency_names["Yolo"] = "Yolo County";

foreach($views as $view) {
	$agency_id = $agency_ids[$view];
	$agency_name = $agency_names[$view];

	$redir_url = "http://www.firedispatch.com/AgencyRedirector.asp?Agency=$agency_id";
	$active_url = "http://www.firedispatch.com/View-$view.asp";
	$email_url = "http://www.firedispatch.com/";

	echo "view = $view, agency_name = $agency_name, id = $agency_id, url = $active_url & $redir_url\n\n";

	$output = "";

//
//	Retrieve pages
//

curl_setopt($ch, CURLOPT_COOKIESESSION, 1);
curl_setopt($ch, CURLOPT_COOKIEJAR, "/tmp/cookie_jar");
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

curl_setopt($ch, CURLOPT_URL, $redir_url);
$page = curl_exec($ch);

curl_setopt($ch, CURLOPT_URL, $active_url);
$page = curl_exec($ch);

if (ereg("No Active", $page)) {
	$page = "";
}

$currentTime = time();

$page = ereg_replace("</TABLE></FONT>.*", "", $page);
$page = ereg_replace(".*BGCOLOR=whitesmoke>", "", $page);

$lines = explode("</TR>", $page);

$description = "";
$address = "";
$agency = "";

foreach ($lines as $line) {
	if (ereg("<TR><TD ROWSPAN=2", $line)) {
		list($f1, $f2, $f3, $f4, $f5, $f6, $f7) = split("</TD>", $line);

		$description = eregi_replace("</B>.*", "", $f3);
		$description = ereg_replace(".*<B>", "", $description);

		continue;
	} else if (ereg("<TR><TD BGCOLOR=white", $line)) {
		list($f1, $f2, $f3, $f4, $f5, $f6, $f7) = split("</TD>", $line);

		$address = eregi_replace("</a>.*", "", $f2);
		$address = ereg_replace(".*>", "", $address);

		$agency = eregi_replace("</a>.*", "", $f3);
		$agency = ereg_replace(".*>", "", $agency);
	} else {
		continue;
	}

	$description = ereg_replace("  +", " ", $description);
	$address = ereg_replace("&amp;", "&", $address);
	$address = ereg_replace("  +", " ", $address);

	if (strlen($alarm) > 0) {
		$description .= " ($alarm)";
	}

	if (eregi("Medical Emergency|medical aid|training", $description)) {
		$description = "";
		$address = "";
		$agency = "";
		continue;
	}

	echo "parsed: \n";
	echo "\tdescription: $description\n";
	echo "\taddress: $address\n";
	echo "\tagency: $agency\n";

	// seen?
	$result = mysql_query("select time from firedispatch where agency_id='" . mysql_real_escape_string($agency_id) . "' and description = '" . mysql_real_escape_string($description) . "' and address = '" . mysql_real_escape_string($address) . "'");

	if (mysql_num_rows($result) > 0) {
		echo "  seen\n";
		$description = "";
		$address = "";
		$agency = "";
		continue;
	}

	mysql_query("insert into firedispatch (time, agency_id, address, description, agency) values (now(), '" . mysql_real_escape_string($agency_id) . "', '" . mysql_real_escape_string($address) . "', '" . mysql_real_escape_string($description) . "', '" . mysql_real_escape_string($agency) . "')");

	echo "  inserted\n";
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

$result = mysql_query("select time_format(date_add(time, interval $offset second), '%H:%i') as timex, address, description, agency from firedispatch where sent = 0 and agency_id='$agency_id' order by time desc");

while (($row = mysql_fetch_array($result))) {
	$output .= "$row[timex]: $row[description]; $row[address]; $row[agency]\n";
}

mysql_query("update firedispatch set sent=1 where agency_id='$agency_id'");

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
}

mysql_query("delete from firedispatch where time < date_sub(now(), interval 1 month)");

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
