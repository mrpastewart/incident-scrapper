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

curl_setopt($ch, CURLOPT_URL, "http://www.montgomery.miamivalleydispatch.org/");
$page = curl_exec($ch);

if (strlen($page) < 8000) {
	die();
}

if (! ereg("MainContent_dispatchcontrol1_ASPxPageControl2_grid_DXDataRow", $page)) {
	die();
}

$page = ereg_replace(".*MainContent_dispatchcontrol1_ASPxPageControl2_grid_DXGroupRowExp0", "", $page);
$page = ereg_replace("MainContent_dispatchcontrol1_ASPxPageControl2_grid_IADD.*", "", $page);

$lines = explode("\n", $page);

foreach ($lines as $line) {
	if (! ereg("dxgvIndentCell dxgv", $line)) {
		continue;
	}

	$line = ereg_replace("</td><td class=.dxgv.>", "COLSEP", $line);
	$line = ereg_replace("</td><td class=.dxgv. style=.border-bottom-width:0px;.>", "COLSEP", $line);

	$line = ereg_replace("</td><td class=.dxgv. style=.border-right-width:0px;.>.*", "COLSEP", $line);
	$line = ereg_replace("</td><td class=.dxgv. style=.border-right-width:0px;border-bottom-width:0px;.>.*", "COLSEP", $line);

	$line = ereg_replace(".*white-space:nowrap;border-left-width:0px;.>", "", $line);
	$line = ereg_replace(".*white-space:nowrap;border-left-width:0px;border-bottom-width:0px;.>", "", $line);

	$line = ereg_replace("COLSEP$", "", $line);

	$line = ereg_replace("&amp;", "&", $line);

//	echo ">> $line\n";

	list($date_time, $call, $calltype, $jurisdiction, $address) = split("COLSEP", $line);

	list($date_string, $time_string) = split(" ", $date_time);

	list($month, $day, $year) = split("/", $date_string);

	if (strlen($month) < 2) { $month = "0$month"; }
	if (strlen($day) < 2) { $day = "0$day"; }

	$date = "$year-$month-$day";

	$date_time = "$date $time_string";

	echo "parsed: $date_time, $calltype, $jurisdiction, $address\n";

	// seen?
//	$result = mysql_query("select id,time from miamivalleydispatch where time = '" . mysql_real_escape_string("$date_time") . "' and description = '" . mysql_real_escape_string($calltype) . "' and address = '" . mysql_real_escape_string($address) . "' and location='" . mysql_real_escape_string($jurisdiction) . "'");
	$result = mysql_query("select id,time from miamivalleydispatch where miamivalleydispatch.call = '" . mysql_real_escape_string("$call") . "'");

	if (mysql_num_rows($result) > 0) {
		echo "  seen\n";
		continue;
	}

	mysql_query("insert into miamivalleydispatch (time,miamivalleydispatch.call,description,address,location) values ('" . mysql_real_escape_string("$date_time") . "', '" . mysql_real_escape_string("$call") . "', '" . mysql_real_escape_string($calltype) . "', '" . mysql_real_escape_string($address) . "', '" . mysql_real_escape_string($jurisdiction) . "')");

	echo "  inserted\n";

	$output .= "$date_time: $call: $calltype: $jurisdiction: $address\n";

	mysql_query("delete from miamivalleydispatch where time < date_sub(now(), interval 1 day)");
}

if (strlen($output) > 0) {
	$headers = "From: \"BBScanner.com\" <gordon@bbscanner.com>\n"
	."X-Mailer: PHP/" . phpversion() . "\n"
	."MIME-Version: 1.0\n"
	."Content-Type: text/plain\n";

	$output .= "--\nhttp://www.montgomery.miamivalleydispatch.org/\n";

//	mail("gordon@gordonedwards.net", "Miami Valley Dispatch", "$output", $headers, "-fgordon@bbscanner.com");
	mail("scrapealerts@gmail.com", "Montgomery County Dispatch", "$output", $headers, "-fgordon@bbscanner.com");
}
?>
