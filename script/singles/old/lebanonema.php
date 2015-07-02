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

curl_setopt($ch, CURLOPT_URL, "http://lebanonema.org/pager/html/monitor.html");
$page = curl_exec($ch);

if (strlen($page) < 8000) {
	die();
}

$page = ereg_replace(".*<TABLE>", "", $page);
$page = ereg_replace("</TABLE>.*", "", $page);

$lines = explode("\n", $page);

foreach ($lines as $line) {
	if (! ereg("COL2", $line)) {
		continue;
	}

	$line = ereg_replace("</TD><TD CLASS=.COL2.>", "", $line);
	$line = ereg_replace("</TD><TD CLASS=.COL3.>", "COLSEP", $line);
	$line = ereg_replace("</TD><TD CLASS=.COL4.>", "COLSEP", $line);
	$line = ereg_replace("</TD><TD CLASS=.COL7.>", "COLSEP", $line);
	$line = ereg_replace("</TD></TR>", "", $line);
	$line = ereg_replace(" *<SPAN CLASS=.M.>", "COLSEP", $line);
	$line = ereg_replace("</SPAN>", "", $line);

	list($time, $date, $x, $address, $agency) = split("COLSEP", $line);

	list($day, $month, $year) = split("-", $date);
	$date = "20$year-$month-$day";

	echo "parsed: $date, $address, $agency\n";

	// seen?
	$result = mysql_query("select id,time from lebanonema where time = '" . mysql_real_escape_string("$date $time") . "' and address = '" . mysql_real_escape_string($address) . "' and agency='" . mysql_real_escape_string($agency) . "'");

	if (mysql_num_rows($result) > 0) {
		echo "  seen\n";

		$row = mysql_fetch_array($result);
		mysql_query("delete from lebanonema where time < '$row[time]'");
		break;
	}

	echo "  inserted\n";

	mysql_query("insert into lebanonema (time, address, agency) values ('" . mysql_real_escape_string("$date $time") . "', '" . mysql_real_escape_string($address) . "', '" . mysql_real_escape_string($agency) . "')");

	$output .= "$date $time: $address $agency\n";
}

if (strlen($output) > 0) {
	$headers = "From: \"BBScanner.com\" <gordon@bbscanner.com>\n"
	."X-Mailer: PHP/" . phpversion() . "\n"
	."MIME-Version: 1.0\n"
	."Content-Type: text/plain\n";

	$output .= "--\nhttp://lebanonema.org/pager/html/monitor.html\n";

	$description = "<p>$description</p>"
		."<p><a href='http://www.radioreference.com/apps/audioAdmin/?a=ef&feedId=$feed_id'>http://www.radioreference.com/apps/audioAdmin/?a=ef&feedId=$feed_id</a></p>";

//	mail("gordon@gordonedwards.net", "Dispatches from lebanonema.org", "$output", $headers, "-fgordon@bbscanner.com");
	mail("scrapealerts@gmail.com", "Dispatches from lebanonema.org", "$output", $headers, "-fgordon@bbscanner.com");
}
?>
