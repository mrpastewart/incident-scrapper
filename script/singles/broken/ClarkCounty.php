<?php
/**
 * Created by PhpStorm.
 * User: LucasWang
 * Date: 7/2/15
 * Time: 11:46 AM
 */
//NO ADDRESS FROM THE PAGE

//Script return values
$curlWorking = true;
$parseWorking = true;
$state = "NV";
$incidentList = [];

$table = "clark_county";
$url = "fire.co.clark.nv.us/Alarm%20OfficeConvertedStaging.aspx";
$email_url = "http://www.clarkcountynv.gov/depts/fire/Pages/AlarmOffice.aspx";
$agency_name = "Clark County, Nevada";


//
//	Initialize curl
//

$ch = curl_init();

curl_setopt($ch, CURLOPT_VERBOSE, 0);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
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

$page = preg_replace("@</table>@", "", $page);
$page = preg_replace("@.*<td>District</td>@", "", $page);

$lines = explode("\n", $page);

foreach ($lines as $line) {

    if (!preg_match("@<td>@", $line)) {
        continue;
    }

    list($f1, $f2, $f3, $f4) = explode("</td>", $line);

    $date_time = preg_replace("@.*<td>@", "", $f1);
    $address = preg_replace("@.*<td>@", "", $f2);
    $description = preg_replace("@.*<td>@", "", $f3);
    $district = preg_replace("@.*<td>@", "", $f4);
;

    if (strlen($district) > 0) {
        $address .= "; $district";
    }

    $description = preg_replace("@  +@", " ", $description);
    $address = preg_replace("@  +@", " ", $address);

    if (preg_match("@Medical Aid|Basic Life Support|Quint|One Engine@i", $description)) {
        continue;
    }

    $date_time = preg_replace("@ +@", " ", $date_time);
    list($month, $day, $year, $time) = explode(" ", $date_time);
    list($hour, $minute) = explode(":", $time);

    if (preg_match("@AM@i", $minute)) {
        $minute = preg_replace("@AM.*@i", "", $minute);
        $ampm = "AM";
    } else {
        $minute = preg_replace("@PM.*@", "", $minute);
        $ampm = "PM";
    }

    if ("$ampm" == "PM") {
        if ($hour < 12) {
            $hour += 12;
        }
    } else if ($hour == 12) {
        $hour = 0;
    }

    $date = "$year/$month/$day";
    $hrMinSec =  "$hour:$minute";

    $ctr++;

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

    //compiles the data in array
    if ($description != '' && $address != '')
        $incident = [
            "State" => $state,
            "City" => "none",
            "County" => "Butler County",
            "Incident" => $standardIncident,
            "Description" => $description,
            "Unit" => "none",
            "latlng" => "none",
            "Primary Dispatcher #" => "Butler County Emergency Services",
            "Source" => "http://ems.co.butler.pa.us/publicwebcad/Summary.aspx",
            "Logo" => "http://ems.co.butler.pa.us/publicwebcad/Images/PublicWebCADLogo.gif",
            "Address" => $address,
            "Timestamp" => $timestamp,
            "Unix Value" => $unixValue,
        ];
    printf($incident["Address"]);
    array_push($incidentList,$incident);
}
$generalInfo = [
    "curlWorking" => $curlWorking,
    "agencyName" => "albemarle_county"
];
array_push($incidentList,$generalInfo);
//var_dump($incidentList);
?>