<?php
$url = "http://www.orangecountyfl.net/EmergencySafety/FireRescueActiveCalls.aspx";
$state = "FL";
$curlWorking = true;
$parseWorking = true;
$incidentList = [];

//
//      Initialize curl
//

$ch = curl_init();

curl_setopt($ch, CURLOPT_VERBOSE, 0);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_TIMEOUT, 270);
curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)");
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch, CURLOPT_POST, 0);

//
//      Retrieve page
//

curl_setopt($ch, CURLOPT_URL, $url);

$page = curl_exec($ch);

if(curl_getinfo($ch,CURLINFO_HTTP_CODE) != 200) {
   $curlWorking = false;
}

if (strlen($page) < 200) {
        die();
}

$currentTime = time();

$page = preg_replace("@.*MAP</th>@", "", $page);
$page = preg_replace("@</table>.*@", "", $page);

$lines = explode("\n", $page);

foreach ($lines as $line) {
        if (preg_match("@<tr[ >]@", $line)) {
                $ctr = 1;
                continue;
        }

	/* NEW */
        if (preg_match("@DISPATCH_TIMELabel@", $line) && $ctr == 1) {
                $ctr++;

                $line = preg_replace("@.*Label.*\">@", "", $line);
                $line = trim(preg_replace("@</span>.*@", "", $line));

		$hrMinSec = $line;
	}

        if (preg_match("@DESCRIPTIONLabel@", $line) && $ctr == 2) {
                $ctr++;

                $line = preg_replace("@.*Label.*\">@", "", $line);
                $description = trim(preg_replace("@</span>.*@", "", $line));

                $address = "";
                continue;
        }


        if (preg_match("@TYPELabel@", $line) && $ctr == 3) {
                $ctr++;

                $line = preg_replace("@.*Label.*\">@", "", $line);
                $line = trim(preg_replace("@</span>.*@", "", $line));

                if (strlen($line) > 0) {
                        $description .= "; $line";
                }

                continue;
        }

        if (preg_match("@NOLabel@", $line) && $ctr == 4) {
                $ctr++;

                $line = preg_replace("@.*Label.*\">@", "", $line);
                $address = preg_replace("@</span>.*@", "", $line);
                continue;
        }


        if (preg_match("@NAMELabel@", $line) && $ctr == 5) {
                $ctr++;

                $line = preg_replace("@</a>.*@", "", $line);
                $line = preg_replace("@</span>.*@", "", $line);
                $line = preg_replace("@.*>@", "", $line);

                $address .= " $line";
                continue;
        }

        if (preg_match("@MAPBUTTON1@", $line) && $ctr == 6) {
                $ctr++;

		/*
		 * extract city from gmap link
		 */
		$line = preg_replace("@.*latlng=@", "", $line);
		$latlng = preg_replace("@\".*a>@", "", $line);

                continue;
        }


        if ($ctr != 7) {
                continue;
        }

        $ctr++;

        if (strlen($address) < 1) {
                continue;
        }

//$date = "$year-$month-$day";
//$hrMinSec = "$hour:$minute";
$date = date("Y-m-d");
$unixValue = strtotime($date) + strtotime($hrMinSec);
$timestamp = date("l, F d, Y", strtotime($date));
$timestamp = "$timestamp $hrMinSec -0800";

        echo "parsed: \n";
        echo "\tdescription: $description\n";
        echo "\taddress: $address\n";
        echo "\ttimestamp: $timestamp\n";
        echo "\tlatlng: $latlng\n";
        echo "\tepoch: $unixValue\n";
}

if ($description != '' && $address != '')
          $incident = [
              "State" => $state,
              "City" => $community,
              "County" => "Orange",
              "Incident" => "none",
              "Description" => $description,
              "Unit" => "none",
              "latlng" => $latlng,
              "Primary Dispatcher #" => "Orange County",
              "Source" => $url,
              "Logo" => "",
              "Address" => $address,
              "Timestamp" => $timestamp,
              "Epoch" => $unixValue
          ];
         array_push($incidentList,$incident);
echo "        $timestamp:  $description  $address\n";

$generalInfo = [
   "curlWorking" => $curlWorking,
   "parseWorking" => $parseWorking,
   "agencyName" => "orange"
];

array_push($incidentList,$generalInfo);


?>
