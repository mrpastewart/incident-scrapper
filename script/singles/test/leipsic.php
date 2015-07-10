<?php
/**
 * Created by PhpStorm.
 * User: LucasWang
 * Date: 7/7/15
 * Time: 10:44 AM
 */
$url = "http://leipsicvfc.com/history.html";
$curlWorking = true;
$parseWorking = true;
$incidentList = [];
$state = "DE";

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

if(curl_getinfo($ch,CURLINFO_HTTP_CODE) != 200) {
    $curlWorking = false;
}

$currentTime = time(); 
$page = preg_replace("@entries the in Call History@", " ", $page);
$page = preg_replace("@Maritime Security Level.*@", "", $page);
$lines = explode("\n", $page);

foreach ($lines as $line) {
	if (preg_match("@ class=\"data-title@", $line)) {
		$ctr = 1;
		$line = preg_replace("@</a></div>.*@", "", $line);
		$description = preg_replace("@.*>@", "", $line);
		continue;
	}
	
	if (preg_match("@class=\"data-date@", $line) && $ctr ==1) {
		$ctr++;
	    $line = preg_replace("@</div>.*@", "", $line);
        $date = preg_replace("@.*\">@","",$line);
		continue;
	}

	if (preg_match("@class=\"data-text@", $line) && $ctr == 2) {
		$ctr++;
	    $line = preg_replace("@.*Location:@", "", $line);
		$address = preg_replace("@District:.*@","",$line);
		continue;
	}
    if($address != "" || $description != "")
    {
	    $incident = [
        "State" => "DE",
        "City" => "none",
        "County" => "Kent",
        "Incident" => "none",
        "Description" => $description,
        "Unit" => "none",
        "latlng" => "none",
        "Primary Dispatcher #" => "Leipsic Volunteer Fire Company",
        "Source" => $url,
        "Logo" => "http://yourfirstdue.com/manager/data/1236364802/logo/Image2.jpg",
        "Address" => $address,
        "Timestamp" => "none",
        "Epoch" => "none",
    ];

    array_push($incidentList,$incident);
    echo "       none:  $description  $address\n";
    $description = ""; 
    $address = ""; 
    }
	}
	$generalInfo = [
    "curlWorking" => $curlWorking,
    "parseWorking" => $parseWorking,
    "agencyName" => "leipsic-DE"
];

array_push($incidentList,$generalInfo);
	?>