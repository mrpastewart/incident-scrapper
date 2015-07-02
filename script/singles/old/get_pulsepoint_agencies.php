<?
require_once("/root/bin/config.php");

$url = "http://webapp.pulsepoint.org/";

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
//	Retrieve pages
//

curl_setopt($ch, CURLOPT_URL, $url);
$page = curl_exec($ch);

if (strlen($page) < 200) {
	die();
}

$currentTime = time();

$lines = explode("\n", $page);

mysql_query("begin transaction");
mysql_query("delete from pulsepoint_agencies");

$ctr = 0;

foreach ($lines as $line) {
	if (! ereg("<option value=", $line)) {
		continue;
	}

	$line = ereg_replace(" selected", "", $line);

	$description = ereg_replace(".*' *>", "", $line);

	$id = ereg_replace("' *>.*", "", $line);
	$id = ereg_replace(".*'", "", $id);

	mysql_query("insert into pulsepoint_agencies (agency, description) values ('" . mysql_real_escape_string($id) . "', '" . mysql_real_escape_string($description) . "')");

}

if ($ctr < 20) {
	mysql_query("rollback");
} else {
	mysql_query("commit");
}
?>
