<?php
/**
 * Created by PhpStorm.
 * User: LucasWang
 * Date: 7/6/15
 * Time: 11:52 AM
 */
//The code no longer works for this website. The page returned has too more information than code intends.
$url = "http://www.montgomery.miamivalleydispatch.org/";
$curlWorking = true;
$parseWorking = true;
$state = "OH";
$incidentList = [];

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

curl_setopt($ch, CURLOPT_URL, $url);
$page = curl_exec($ch);

if(curl_getinfo($ch,CURLINFO_HTTP_CODE) != 200)
{
    $curlWorking = false;
}
$page = preg_replace("@.*<TABLE>@", "", $page);
$page = preg_replace("@</TABLE>.*@", "", $page);
echo $page;
$lines = explode("\n", $page);

foreach ($lines as $line) {
    if (!preg_match("@COL2@", $line)) {
        continue;
    }

    $line = preg_replace("@</TD><TD CLASS=.COL2.>@", "", $line);
    $line = preg_replace("@</TD><TD CLASS=.COL3.>@", "COLSEP", $line);
    $line = preg_replace("@</TD><TD CLASS=.COL4.>@", "COLSEP", $line);
    $line = preg_replace("@</TD><TD CLASS=.COL7.>@", "COLSEP", $line);
    $line = preg_replace("@</TD></TR>", "", $line);
    $line = preg_replace("@ *<SPAN CLASS=.M.>@", "COLSEP", $line);
    $line = preg_replace("@</SPAN>@", "", $line);

    list($date, $call, $calltype, $jurisdiction, $location) = explode("COLSEP", $line);

	list($day, $month, $year) = explode("-", $date);
	$date = "20$year-$month-$day";

	echo "parsed: $date, $address, $agency\n";
}
?>