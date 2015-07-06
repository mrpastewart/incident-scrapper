<?php
/**
 * Created by PhpStorm.
 * User: LucasWang
 * Date: 7/6/15
 * Time: 1:12 PM
 *///Need To write code to retrieve timestamp as well as address.

$url = "http://pipes.yahoo.com/pipes/pipe.run?_id=d1c530619b7a02a2e0b18aca47a4602c&_render=rss";
$curlWorking = true;
$parseWorking = true;
$state = "NJ";
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

if(curl_getinfo($ch,CURLINFO_HTTP_CODE) != 200)
{
    $curlWorking = false;
}

$currentTime = time();

$page = preg_replace("@.*/generator>@", "", $page);
$page = preg_replace("@</channel>.*@", "", $page);

$page = preg_replace("@&lt;@", "<", $page);
$page = preg_replace("@&gt;@", ">", $page);
$page = preg_replace("@&amp;@", "&", $page);

$lines = explode("\n", $page);

foreach ($lines as $line) {
    $line = preg_replace("@ *<br/> *@", ", ", $line);
    $line = preg_replace("@ *, *, *@", ", ", $line);

    if (preg_match("@<title>@", $line)) {
        $description = preg_replace("@.*<title>@", "", $line);
        $description = preg_replace("@</title>.*@", "", $description);
        continue;
    }

    if (preg_match("@<description>@", $line)) {
        $temp = preg_replace("@.*<description>@", "", $line);
        $temp = preg_replace("@</description>.*@", "", $temp);

        $description .= ", $temp";
        continue;
    }

    if (!preg_match("@</item>@", $line)) {
        continue;
    }

    $description = preg_replace("@, *20[0-9][0-9] [A-Z][a-z][a-z] [0-9][0-9] [0-9][0-9]:[0-9][0-9]:[0-9][0-9] *@", "", $description);

    $description = preg_replace("@ *[.,] *$@", "", $description);

    echo "parsed: \n";
    echo "\tdescription: $description\n";
}