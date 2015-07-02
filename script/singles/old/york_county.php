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

curl_setopt($ch, CURLOPT_URL, "http://www.ycdes.org/webcad/");
$page = curl_exec($ch);

if (strlen($page) < 2000) {
	die();
}
/*
$page = ereg_replace(".*<table ", "", $page);
$page = ereg_replace("</table>.*", "", $page);
*/
$lines = explode("\n", $page);

foreach ($lines as $line) {
	if (! ereg("<td><", $line)) {
		continue;
	}

	$line = ereg_replace("</td><td>", "COLSEP", $line);
	$line = ereg_replace("&nbsp;", " ", $line);

//echo "\n*** $line\n";

	list($junk, $timestamp, $box, $description, $address1, $address2, $address3, $cross_street, $cross_street, $cross_street, $intersection, $location) = split("COLSEP", $line);

	$address1 = ereg_replace("^ +", "", $address1);
	$address2 = ereg_replace("^ +", "", $address2);
	$address3 = ereg_replace("^ +", "", $address3);

	$address1 = ereg_replace(" +$", "", $address1);
	$address2 = ereg_replace(" +$", "", $address2);
	$address3 = ereg_replace(" +$", "", $address3);

	$location = ereg_replace("</td.*", "", $location);

	if (strlen($address1) > 0) { $address2 = " " . $address2; }
	if (strlen($address3) > 0) { $address2 = $address2 . " "; }

	$time = ereg_replace(".* ", "", $timestamp);
	$date = ereg_replace(" .*", "", $timestamp);

//	list($day, $month, $year) = split("-", $date);
	list($month, $day, $year) = split("-", $date);
	$timestamp = "$year-$month-$day $time";

	$address = $address1 . $address2 . $address3;

	echo "parsed: \n";
	echo "\ttimestamp: $timestamp\n\tbox: $box\n\tdesc = $description\n";
	echo "\taddress = $address\n";
	echo "\tlocation = $location\n";

	// seen?
	$result = mysql_query("select id,time from york_county where time = '" . mysql_real_escape_string("$timestamp") . "' and description = '" . mysql_real_escape_string($description) . "' and address = '" . mysql_real_escape_string($address) . "' and location='" . mysql_real_escape_string($location) . "'");

	if (mysql_num_rows($result) > 0) {
		echo "  seen\n";
		continue;
	}

	mysql_query("insert into york_county (time, description, address, location) values ('" . mysql_real_escape_string("$timestamp") . "', '" . mysql_real_escape_string($description) . "', '" . mysql_real_escape_string($address) . "', '" . mysql_real_escape_string($location) . "')");

echo "insert into york_county (time, description, address, location) values ('" . mysql_real_escape_string("$timestamp") . "', '" . mysql_real_escape_string($description) . "', '" . mysql_real_escape_string($address) . "', '" . mysql_real_escape_string($location) . "')\n";

	echo "  inserted\n";
}

mysql_query("delete from york_county where time < date_sub(now(), interval 1 day)");

//
//
//

mysql_query("start transaction");

$result = mysql_query("select time, description, address, location from york_county where sent = 0 order by time desc");

while (($row = mysql_fetch_array($result))) {
	$output .= "$row[time]: $row[description]; $row[address]; $row[location]\n";
}

mysql_query("update york_county set sent=1");

mysql_query("commit");

if (strlen($output) > 0) {
	$headers = "From: \"BBScanner.com\" <gordon@bbscanner.com>\n"
	."X-Mailer: PHP/" . phpversion() . "\n"
	."MIME-Version: 1.0\n"
	."Content-Type: text/plain\n";

	$output .= "--\nhttp://www.ycdes.org/webcad/\n";

//	mail("gordon@gordonedwards.net", "Dispatches from ycdes.org", "$output", $headers, "-fgordon@bbscanner.com");
	mail("scrapealerts@gmail.com", "Dispatches from ycdes.org", "$output", $headers, "-fgordon@bbscanner.com");
}
?>
