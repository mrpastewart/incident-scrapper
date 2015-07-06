<?php
/**
 * Created by PhpStorm.
 * User: LucasWang
 * Date: 7/6/15
 * Time: 11:46 AM
 */
//SEPARATE TABLES ALSO NO READILY AVAILABLE TIMESTAMP

$url = "http://www.miamidade.gov/firecad/calls_include.asp";
$curlWorking = true;
$parseWorking = true;
$state = "FL";
$incidentList = [];

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

$page = curl_exec($ch);

if (strlen($page) < 200) {
    die();
}

$currentTime = time();

//$page = ereg_replace(".*In<br>Service", "", $page);
//$page = ereg_replace("</TABLE>.*", "", $page);
$page = preg_replace("@</TR>@", "\n", $page);

$lines = explode("\n", $page);

$line_stack = array();

foreach ($lines as $line) {
    $line = preg_replace("@<table WIDTH=[0-9]* *>@i", "", $line);
    $line = pregi_replace("@ *<tr></tr> *@i", "", $line);

    if (eregi(">UNITS</TD>", $line)) {
        $line = eregi_replace(".*>UNITS</TD>", "", $line);
        $line = eregi_replace("^ *</tr> *", "", $line);
        array_push($line_stack, $line);
        continue;
    }

    if (! eregi("^<TR", $line)) {
        continue;
    }

    array_push($line_stack, $line);
}

foreach ($line_stack as $line) {
    list($f1, $f2, $f3, $f4, $f5) = split("</TD>", $line);

    $time = ereg_replace(".*> *", "", $f1);

    $description = ereg_replace(".*detailfont[0-9]>", "", $f3);

    $address = ereg_replace(".*detailfont[0-9]>", "", $f4);
    $address = ereg_replace(" *& *", " & ", $address);

    echo "parsed: \n";
    echo "\taddress: $address\n";
    echo "\tdescription: $description\n";

?>