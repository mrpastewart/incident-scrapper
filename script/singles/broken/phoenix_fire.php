<?php
/**
 * Created by PhpStorm.
 * User: LucasWang
 * Date: 7/6/15
 * Time: 12:14 PM
 */
//BROKEN: UNKOWN
$url = "https://htms.phoenix.gov/publicweb/Default.aspx";
$curlWorking = true;
$parseWorking = true;
$state = "PA";
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

if (strlen($page) < 2000) {
    die();
}

$currentTime = time();

$page = preg_replace("@.*>Units</td></tr>@", "", $page);
$page = preg_replace("@Page will automatically refresh every 60 seconds..*@", "", $page);
$page = preg_replace("@</table>.*@", "", $page);

$lines = explode("<tr>", $page);

foreach ($lines as $line) {
    if (!preg_match("@<td@", $line)) {
        continue;
    }

    $line = preg_replace("@<span style=.color:[a-z]+.>@", "", $line);
    $line = preg_replace("@</span>@", "", $line);
    $line = preg_replace("@ +@", " ", $line);

    $line = preg_replace("@ *, *2", ", ", $line);
    $line = preg_replace("@</td><td>@", "COLSEP", $line);

    list($f1, $f2, $f3, $f4) = explode("COLSEP", $line);
    $f2 = preg_replace("@.*\">@", "", $f2);
    $address = preg_replace("@</a>.*@", "", $f2);

    $description = preg_replace("@</a>.*@", "", $f3);

    echo "parsed: \n";
    echo "\taddress: $address\n";
    echo "\tdescription = $description\n";
}
?>