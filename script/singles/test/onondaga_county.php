<?php
/**
 * Created by PhpStorm.
 * User: LucasWang
 * Date: 7/8/15
 * Time: 10:10 AM
 */
$url = "http://911csi.ongov.net/CADInet/app/_rlvid.jsp?_rap=pc_Cad911Toweb.doLink1Action&_rvip=/events.jsp";
$curlWorking = true;
$parseWorking = true;
$incidentList = [];
$state = "NY";
//
//	Initialize curl
//

$ch = curl_init();

curl_setopt($ch, CURLOPT_VERBOSE, 0);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_TIMEOUT, 270);
curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)");
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

$page = preg_replace("@.*Cross Streets</span></th></tr>@", "", $page);
$page = preg_replace("@</tbody>.*@", "", $page);

$lines = explode("\n", $page);

foreach ($lines as $line) {
    $line = html_entity_decode($line);

    $line = preg_replace("@ +@", " ", $line);

    if (preg_match("@^<tr class=.rowClass@", $line)) {
        $ctr = 1;
        $line = preg_replace("@</span></td>@", "", $line);
        $agency = preg_replace("@.* class=.outputText.>@", "", $line);
        continue;
    }

    if (preg_match("@^<td nowrap=@", $line) && $ctr == 1) {
        $ctr = 2;
        $line = preg_replace("@</span></td>@", "", $line);
        $date_time = preg_replace("@.* class=.outputText.>@", "", $line);
        continue;
    }

    if (preg_match("@^<td valign@", $line) && $ctr == 2) {
        $ctr = 3;
        $line = preg_replace("@</span></td>$@", "", $line);
        $line = preg_replace("@</span><span id=.form1:tableEx1:[0-9]+:text[0-9]+. class=.outputText.> *@", "; ", $line);
        $description = trim(preg_replace("@.* class=.outputText.>@", "", $line));
        continue;
    }

    if (preg_match("@^<td align=@", $line) && $ctr == 3) {
        $ctr = 4;

        $line = preg_replace("@<td align=.left. valign=.top. >@", " ", $line);
        $line = preg_replace("@<span id=.form[0-9]*:tableEx[0-9]*:[0-9]*:textActiveevents_[a-z]*[0-9]*. class=.outputText.> @", " ", $line);
        $line = preg_replace("@</span>@", " ", $line);
        $line = preg_replace("@</td>@", "", $line);
        $address = trim(preg_replace("@ +@", " ", $line));

        if (strlen($address) > 0) {
            list($date_portion, $time_portion) = explode(" ", $date_time);

            list($month, $day, $year) = explode("/", $date_portion);

            list($hour, $minute, $second) = explode(":", $time_portion);

            $date = "20$year-$month-$day";
            $hrMinSec = "$hour:$minute";
            $unixValue = strtotime($date) + strtotime($hrMinSec);
            $timestamp = date("l, F d, Y", strtotime($date));
            $timestamp = "$timestamp $hrMinSec -0800";

            $incident = [
                "State" => "NY",
                "City" => "none",
                "County" => "Onondaga",
                "Incident" => "none",
                "Description" => $description,
                "Unit" => "none",
                "latlng" => "none",
                "Primary Dispatcher #" => $agency,
                "Source" => $url,
                "Logo" => "http://911csi.ongov.net/CADInet/images/CtoW03.jpg",
                "Address" => $address,
                "Timestamp" => $timestamp,
                "Epoch" => $unixValue,
            ];

            array_push($incidentList,$incident);
            echo "       $timestamp:  $description  $address\n";
        }
    }
}
$generalInfo = [
    "curlWorking" => $curlWorking,
    "parseWorking" => $parseWorking,
    "agencyName" => "onondaga-NY"
];

array_push($incidentList,$generalInfo);
        ?>