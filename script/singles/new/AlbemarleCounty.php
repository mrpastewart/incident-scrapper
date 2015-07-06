<?php
/**
 * Created by PhpStorm.
 * User: LucasWang
 * Date: 6/25/15
 * Time: 9:00 AM
 */

//Script return values
$curlWorking = true;
$parseWorking = true;
$incidentList = [];

/*
$city = null;
$description = null;
$unit = null;
$url = null;
$address = null;
$timestamp = null;
$unixValue = null;
*/

$unit = "";
//
//	Initialize curl
//
//$url = "http://warhammer.mcc.virginia.edu/fids/fids.php?display=PLAIN";
$url = "http://warhammer.mcc.virginia.edu/fids/fids.php?station=1";	// TEST ONLY!

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

$page = preg_replace("@.*In<br>Service@", "", $page);
$page = preg_replace("@Any number in.*@", "", $page);

$lines = explode("\n", $page);


$last_incident = "none";
$found_table = false;
$ctr = 0;
foreach ($lines as $line) {


    if (preg_match("@<tr>@", $line)) {
        $ctr = 1;
        continue;
    }

    if (preg_match("@<td align=@", $line) && $ctr == 1) {
        $ctr++;
        $line = preg_replace("@</font.*@", "", $line);
        $date = preg_replace("@.*>@", "", $line);

        if (strlen($date) < 1) {
            $ctr = 99;
        }

        continue;
    }

    if (preg_match("@<td align=@", $line) && $ctr == 2) {
        $ctr++;
        $line = preg_replace("@<br.*@", "", $line);
        $line = preg_replace("@</font.*@", "", $line);

	//echo("          line=$line\n");
        $incident_numb = preg_replace("@.*<b>@", "", $line);

	/*
	 * save only if previous incident# chgs
	 * otherwise aggregate the "unit"
	 */
	if (($last_incident != "none") && ($incident_numb != $last_incident) && 
	    ($description != '') && ($address != '')) {

	    $incident = [
		"State" => "VA",
		"City" => $city,
		"County" => "Albemarle",
		"Incident" => $standardIncident,
		"Description" => $description,
		"Unit" => $unit,
		"latlng" => "none",
		"Primary Dispatcher #" => "Albemarle FD",
		"Source" => $url,
		"Logo" => "http://warhammer.mcc.virginia.edu/fids/images/coaseal2.gif",
		"Address" => $address,
		"Timestamp" => $timestamp,
		"Epoch" => $unixValue,
	    ];

	    array_push($incidentList,$incident);
	    $unit = "";
	    //var_dump($incident);
	}

	$last_incident = $incident_numb;

        continue;
    }

    if (preg_match("@<td align=@", $line) && $ctr == 3) {
        $ctr++;
        $line = preg_replace("@</a.*@", "", $line);
        $line = preg_replace("@</font.*@", "", $line);
        $new_unit = preg_replace("@.*=new>@", "", $line);

	if(strlen($unit) > 0) {
	    $unit .= ",".$new_unit;
	} else {
	    $unit = $new_unit;
	}

	//echo("        incident=$incident_numb   unit=$unit\n");
        continue;
    }

    if (preg_match("@<td align=@", $line) && $ctr == 4) {
        $ctr++;
        $line = preg_replace("@</font.*@", "", $line);

	/*
	 * extract city from gmap link
	 */
        $line = preg_replace("@.*daddr=@", "", $line);
	$fields = explode("&", $line);		// take first param
        $address = preg_replace("@.*=new>@", "", $line);

	$addr1 = explode("\+", $fields[0]);	// address in gmap link
	$addr2 = explode(" ", $address);		// address displayed
	$city = "";
	for ($i = 0; $i < sizeof($addr1)-1; $i++) {
	    if ($addr1[$i] != $addr2[$i]) {
		$city .= $addr1[$i]." ";
	    }
	}
	$city = trim($city, " ");	// get rid of trailing spaces
	//echo ("       CITY=".$city."/n");

        continue;
    }

    if (preg_match("@<td bgcolor@", $line) && $ctr == 5) {
        $ctr++;
        $line = preg_replace("@</font.*@", "", $line);
        //$description = preg_replace("@.*>@", "", $line);
        $description = preg_replace("@.*>@", "", $line);
        continue;
    }

    if (preg_match("@<td align=@", $line) && $ctr == 6) {
        $ctr++;
        $line = preg_replace("@</font.*@", "", $line);
        $time = preg_replace("@.*>@", "", $line);
        continue;
    }

    if ($ctr != 7) {
        continue;
    }

    $ctr = 8;


    $line = html_entity_decode($line);
    $line = preg_replace("@ +@", " ", $line);

    list($month, $day, $year) = explode("/", $date);
    if(preg_match("@ @", $time))
        list($time_portion, $ampm_portion) = explode(" ", $time);

    $hrMinSec = $time;
    $date = "$year-$month-$day";
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
    "Unit" => $unit,
    "latlng" => "none",
    "Primary Dispatcher #" => "Albemarle FD",
    "Source" => $url,
    "Logo" => "http://warhammer.mcc.virginia.edu/fids/images/coaseal2.gif",
    "Address" => $address,
    "Timestamp" => $timestamp,
    "Epoch" => $unixValue,
];

array_push($incidentList,$incident);
//var_dump($incident);

$generalInfo = [
    "curlWorking" => $curlWorking,
    "parseWorking" => $parseWorking,
    "agencyName" => "albemarle-VA"
];
array_push($incidentList,$generalInfo);

//var_dump($incidentList);



?>
