<?php
/**
 * Created by PhpStorm.
 * User: LucasWang
 * Date: 7/8/15
 * Time: 2:07 PM
 */
$url = "http://www.cad.oregon911.net/call-list?AJAX_REFRESH=W";
$curlWorking = true;
$parseWorking = true;
$incidentList = [];
$state = "OR";

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

$page = preg_replace("@.*<th>URL</th></tr>@", "" , $page);
$page = preg_replace("@</tr></table>.*@", "" , $page);

$lines = explode("</tr>", $page);

foreach ($lines as $line) {
    $line = preg_replace("@ style='color:red;'@", "", $line);
    $line = preg_replace("@.*</center></td><td>@", "", $line);
    $line = preg_replace("@<td>@", "", $line);

    list($f1, $f2, $f3, $f4, $f5, $f6, $address) = preg_split("</th>", $line);

    $description = preg_replace("@.*\">@", "", $f2);
    $description = preg_replace("@</a>.*@", "", $description);

    $address = preg_replace("@.*\">@", "", $f4);
    $address = preg_replace("@</a>.*@", "", $address);

    $units = "";

    if (preg_match("@<span@", $f5)) {
        $unit_array = explode("</span>", $f5);

        foreach ($unit_array as $unit) {
            $unit = trim(preg_replace("@.*\">@", "", $unit));

            if (strlen($unit) > 0) {
                if (strlen($units) > 0) {
                    $units .= ", ";
                }

                $units .= $unit;
            }
        }
    }

    if (strlen($units) == 0) {
        continue;
    }


    $incident = [
        "State" => "OR",
        "City" => "none",
        "County" => "Washington",
        "Incident" => "none",
        "Description" => $description,
        "Unit" => $unit,
        "latlng" => "none",
        "Primary Dispatcher #" => "Warren County New Jersey",
        "Source" => $url,
        "Logo" => "none",
        "Address" => $address,
        "Timestamp" => "none",
        "Epoch" => "none",
    ];

    array_push($incidentList,$incident);
    echo "       none:  $description  $address\n";
}

$generalInfo = [
    "curlWorking" => $curlWorking,
    "parseWorking" => $parseWorking,
    "agencyName" => "washington_county-DE"
];

array_push($incidentList,$generalInfo);
?>