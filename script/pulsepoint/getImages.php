<?php
/**
 * Created by PhpStorm.
 * User: Srivatsav
 * Date: 6/24/2015
 * Time: 10:09 AM
 */
$agency = "LAC01";
$image_url = "http://webapp.pulsepoint.org/logo-agencyid.php?agencyid=$agency";
$include_units = true;

$ch = curl_init();

curl_setopt($ch, CURLOPT_VERBOSE, 0);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_TIMEOUT, 270);
curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)");
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch, CURLOPT_POST, 0);


curl_setopt($ch, CURLOPT_URL, $image_url);

$page1 = curl_exec($ch);

echo $page1;

$rtf = fopen("Images.rtf", "a");
fwrite($rtf, $page1->getAttribute);
fclose($rtf);
?>