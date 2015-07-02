<?
require_once("/root/bin/config.php");

$table = "newark";
$url = "http://pipes.yahoo.com/pipes/pipe.run?_id=d1c530619b7a02a2e0b18aca47a4602c&_render=rss";
$email_url = "http://ag0p85pls00984bsg1fkeb3bkqkq4832-a-sites-opensocial.googleusercontent.com/gadgets/ifr?url=http://www.gstatic.com/sites-gadgets/rss-sites/rss_sites.xml&container=enterprise&view=home&lang=en&country=ALL&sanitize=0&v=ae5aac49e40df693&libs=core:setprefs:settitle&mid=102&parent=http://www.aetnahhl.org/#up_bg_color=%23000&up_font_family=Sans+Serif&up_items_to_show=5&up_font_size=12&up_display=1&up_title_color=%23F90&up_snippet=3&up_txt_color=%23F94&up_rss_feed_url=http://pipes.yahoo.com/pipes/pipe.run?_id%3Dd1c530619b7a02a2e0b18aca47a4602c%26_render%3Drss&st=e%3DAIHE3cCT4Wr2f5UTS25YCR42RXt3ELW9oNoKj%252FRJOulVkcW8HqHR5I4hHjVJph7NUDV%252F%252F9UcajwnNg9lEL3Bx%252BgY%252BKAKCgECCKbc%252Fvyr5XMTPUxUCMZ9besWIrDmKbroc2yHTf7CNqhq%26c%3Denterprise&rpctoken=-6034069853823482358";
$agency_name = "Newark, Delaware";

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

// get timezone offset
$offset = get_timezone_offset('UTC');

//
//	Retrieve page
//

curl_setopt($ch, CURLOPT_URL, $url);

$page = curl_exec($ch);

if (strlen($page) < 200) {
	die();
}

$currentTime = time();

$page = ereg_replace(".*/generator>", "", $page);
$page = ereg_replace("</channel>.*", "", $page);

$page = ereg_replace("&lt;", "<", $page);
$page = ereg_replace("&gt;", ">", $page);
$page = ereg_replace("&amp;", "&", $page);

$lines = explode("\n", $page);

foreach ($lines as $line) {
	$line = ereg_replace(" *<br/> *", ", ", $line);
	$line = ereg_replace(" *, *, *", ", ", $line);

	if (ereg("<title>", $line)) {
		$description = ereg_replace(".*<title>", "", $line);
		$description = ereg_replace("</title>.*", "", $description);
		continue;
	}

	if (ereg("<description>", $line)) {
		$temp = ereg_replace(".*<description>", "", $line);
		$temp = ereg_replace("</description>.*", "", $temp);

		$description .= ", $temp";
		continue;
	}

	if (! ereg("</item>", $line)) {
		continue;
	}

	$description = ereg_replace(", *20[0-9][0-9] [A-Z][a-z][a-z] [0-9][0-9] [0-9][0-9]:[0-9][0-9]:[0-9][0-9] *", "", $description);

	$description = ereg_replace(" *[.,] *$", "", $description);

	echo "parsed: \n";
	echo "\tdescription: $description\n";

	// seen?
	$result = mysql_query("select time from $table where description = '" . mysql_real_escape_string($description) . "'");

	if (mysql_num_rows($result) > 0) {
		echo "  seen\n";
		continue;
	}

	$sql = "insert into $table (time, description) values (now(), '" . mysql_real_escape_string($description) . "')";

	$result = mysql_query($sql);

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

$result = mysql_query("select time_format(date_add(time, interval $offset second), '%H:%i') as timex, description from $table where sent = 0 order by time desc");

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
