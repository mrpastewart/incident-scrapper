<?php
/**
 * Created by PhpStorm.
 * User: LucasWang
 * Date: 7/2/15
 * Time: 10:57 AM
 */
 
//NO ADDRESS

//Script return values
$curlWorking = true;
$parseWorking = true;
$incidentList = [];
$state = "DE";
$city = "Frederica";

$url = "http://www.bowersfire.com/incidents";
$email_url = $url;
$agency_name = "Bowers Fire, Frederica, Delaware";


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

$page = preg_replace("@.*Live Run Log@", "", $page);
$page = preg_replace("@pagingTabs.*@", "", $page);
printf($page);

$lines = explode("\n", $page);

foreach ($lines as $line) {

    if (ereg("pstag", $line)) {
        $ctr = 1;
        $date_time = preg_replace("@</div>.*@", "", $line);
        $date_time = preg_replace("@.*.> *@", "", $date_time);
        continue;
    }

    if (ereg("Nature:", $line) && $ctr == 1) {
        $ctr++;
        $description = preg_replace("@.*Nature:</strong> *@", "", $line);
        $description = preg_replace("@<br/>.*@", "", $description);

        $address = "";

        if (!ereg("Location:", $line)) {
            continue;
        }
    }

    if (ereg("Location:", $line) && $ctr == 2) {
        $address = preg_replace("@.*Location:</strong> *@", "", $line);
        $address = preg_replace("@<br/>.*@", "", $address);
        continue;
    }

    if (ereg("City:|Address:|Location:", $line) && $ctr == 2) {
        $line = preg_replace("@.*</strong> *@", "", $line);
        $line = preg_replace("@<br/>.*@", "", $line);

        if (strlen($address) > 0) {
            $address .= ", ";
        }

        $address .= $line;

        continue;
    }

    if (preg_match("@</div>@", $line) && $ctr == 2) {
        $ctr = 5;
    }

    if (preg_match("@Cross Streets@", $line)) {
        $ctr = 5;
    }

    if ($ctr != 5) {
        continue;
    }

    $ctr++;

    $address = preg_replace("@  +@", " ", $address);

    $date_time = preg_replace("@,@", "", $date_time);
    list($x, $month, $day, $year, $x, $hour, $minute) = split("[/ :]", $date_time);

    if ($month == "January") {
        $month = "01";
    }
    if ($month == "February") {
        $month = "02";
    }
    if ($month == "March") {
        $month = "03";
    }
    if ($month == "April") {
        $month = "04";
    }
    if ($month == "May") {
        $month = "05";
    }
    if ($month == "June") {
        $month = "06";
    }
    if ($month == "July") {
        $month = "07";
    }
    if ($month == "August") {
        $month = "08";
    }
    if ($month == "September") {
        $month = "09";
    }
    if ($month == "October") {
        $month = "10";
    }
    if ($month == "November") {
        $month = "11";
    }
    if ($month == "December") {
        $month = "12";
    }

    $timestamp = "$year-$month-$day $hour:$minute";

    $hrMinSec = "$hour:$minute";
    $date = "$year/$month/$day";
    $unixValue = strtotime($date) + strtotime($hrMinSec);
    $timestamp = date("l, F d, Y", strtotime($date));
    $timestamp = "$timestamp $hrMinSec -0800";


    //
    //Filter for standardizing descriptions
    //
    $stan = array(
        //List of knowns
    );

    if(array_key_exists($description, $stan))
        $standardIncident =  $stan[$description];
    else
        $standardIncident = "unknown";

    echo "        $timestamp:  $description   $address\n";
}

$incident = [
    "State" => "VA",
    "City" => $city,
    "County" => "Albemarle",
    "Incident" => $standardIncident,
    "Description" => $description,
    "Unit" => "none",
    "latlng" => "none",
    "Primary Dispatcher #" => "Albemarle FD",
    "Source" => $url,
    "Logo" => "http://warhammer.mcc.virginia.edu/fids/images/coaseal2.gif",
    "Address" => $address,
    "Timestamp" => $timestamp,
    "Epoch" => $unixValue,
];

array_push($incidentList,$incident);
$generalInfo = [
    "curlWorking" => $curlWorking,
    "parseWorking" => $parseWorking,
    "agencyName" => "albemarle-VA"
];
?>