<?php
/**
 * Created by PhpStorm.
 * User: Srivatsav
 * Date: 6/16/2015
 * Time: 10:46 AM
 */

class Incidents {
    private $Counties = array();
    private $Incidents = array();

    //public function __construct(/*array*/ $agency, $state, $county)
    public function init(/*array*/ $agency, $state, $county)
    {
        $this->Counties=$county;
	$this->Incidents = array();	// must clear for each agency

        $i = 0;
        $current = "none";
        //foreach($agencies as $agencynumber) {

            //echo "Should have created all the files and written the agency name in it!\n";
            //Appends incidents to proper agency file

            //$agency = $agencynumber;
            echo "************** AGENCY: $agency\n";
            $previous = $current;
            $current = $agency;
            $recent_url = "http://webapp.pulsepoint.org/recent_incidents.php?agencyid=$agency";
            $active_url = "http://webapp.pulsepoint.org/active_incidents.php?agencyid=$agency";
            $include_units = true;

            $ch = curl_init();

            curl_setopt($ch, CURLOPT_VERBOSE, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 270);
            curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)");
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_POST, 0);

            curl_setopt($ch, CURLOPT_URL, $active_url);
            $page1 = curl_exec($ch);
            if (preg_match("/No Active/", $page1)) {
                $page1 = "";
            }

            curl_setopt($ch, CURLOPT_URL, $recent_url);
            $page2 = curl_exec($ch);
            if (preg_match("/No Recent/", $page2)) {
                $page2 = "";
            }

	    echo "$active_url\n$recent_url\n";
            $page = $page1 . $page2;		// put recent first, then active for proper time sequence

            $currentTime = time();
            $lines = explode("\n", $page);
            $units = "";
            $date = "";

            //printf(var_dump($lines));
            foreach ($lines as $line) {
                if (preg_match("/<row id=/", $line)) {
                    $ctr = 1;
                    $alarm = "";
		    continue;
                }

                //echo "line-$ctr: $line\n";
                if (preg_match("/<cell>/", $line) && $ctr == 1) {
                    $ctr++;
                    $line = preg_replace("/.*<cell>/", "", $line);
                    $timeStamp = preg_replace("@</cell>.*@", "", $line);
                    $timeStamp = substr($timeStamp,0,strpos($timeStamp,"(")-1); //removes the (minutes) at the end
                    $date = substr($timeStamp,0,10);
                    $hrMinSec = substr($timeStamp,11);
                    $epoch = strtotime($date) + strtotime($hrMinSec);
                    $timeStamp = date("l, F d, Y",strtotime($date));
                    $timeStamp = "$timeStamp $hrMinSec -0800";

                    //echo "epoch: date=".strtotime($date)."  sec=".strtotime($hrMinSec)."\n";
                    continue;
                }


                if (preg_match("/<cell>/", $line) && $ctr == 2) {
                    $ctr++;
                    $line = preg_replace("/.*<cell>/", "", $line);
                    $description = preg_replace("@</cell>.*@", "", $line);
                    continue;
                }

                if (preg_match("/<cell>/", $line) && $ctr == 3) {
                    $ctr++;
                    $line = preg_replace("/.*<cell>/", "", $line);
                    $address = preg_replace("@</cell>.*@", "", $line);

                    continue;
                }

                if (preg_match("/^&lt;br.* alarm/", $line) && $ctr == 4) {
                    $alarm = preg_replace("/alarm.*/i", "Alarm", $line);
                    $alarm = preg_replace("/.*&gt;/", "", $alarm);
                }

                if (preg_match("/<cell>/", $line) && $ctr == 4 && $include_units) {
                    $units = preg_replace("/<cell>/", "", $line);
                    $units = preg_replace("@</cell>.*@", "", $units);

                    $units = preg_replace("@&lt;/font&gt;@", "", $units);
                    $units = preg_replace("/&lt;font color='#[0-9A-F]+'&gt;/", "", $units);
                    continue;
                }

                if (preg_match("/<cell hidden/", $line) && $ctr == 4) {
                    $ctr++;
                    $line = preg_replace("/.*'>/", "", $line);
                    $latlng = preg_replace("@</cell>.*@", "", $line);
                    continue;
                }

                if (preg_match("/<cell hidden/", $line) && $ctr == 5) {
                    $ctr++;
                    $line = preg_replace("/.*'>/", "", $line);
                    $description = preg_replace("@</cell>.*@", "", $line);
                    continue;
                }


                if (!preg_match("@</row>@", $line)) {
                    $ctr++;
                    continue;
		} else {

		    /*
		     * process this incident
		     */
		    $ctr++;

		    $description = preg_replace("/  +/", " ", $description);
		    $address = preg_replace("/&amp;/", "&", $address);
		    $address = preg_replace("/  +/", " ", $address);

		    if (strlen($alarm) > 0) {
			$description .= " ($alarm)";
		    }

		    /* DELETE: DO NOT FILTER HERE
			if (preg_match("/Medical Emergency/i", $description)) {
			    continue;
			}

			if($this->filters($description)) {
			    continue;
			}

			//echo "      $epoch: $description - $address\n";
			$checker = new IncidentAppend();
			if(!($checker->checkrep($incident))) {
			    continue;
			}
		    */

		    $city = "none";
		    if(preg_match("/, /", $address))
		    {
			$split = explode(", ", $address, 2);
			$city = $split[1];
			$address = $split[0];
		    }

		    /*
		     * geocode county if already not in cache
		     */
		    if (!array_key_exists($agency, $this->Counties)) {

			//$temp = explode(",", $this->Geo[$i]);
			//echo $this->location[$i];
			//$temp1 = $temp[0];
			//$temp2 = $temp[1];

			$temp = $latlng;
			$geocode = file_get_contents("http://maps.googleapis.com/maps/api/geocode/json?latlng=$temp&sensor=false");
			$output = json_decode($geocode);
			$temp2 = false;

			for ($j = 0; $j < sizeof($output->results[0]->address_components); $j++) {
			    //echo $output->results[0]->address_components[$j]->types[0]. ": " .
			    //$this->Counties, $output->results[0]->address_components[$j]->long_name;

			    if ($output->results[0]->address_components[$j]->types[0] == "administrative_area_level_2") {
				$new = $output->results[0]->address_components[$j]->long_name;
				$new = str_replace(" County", "", $new);
				$this->Counties[$agency] = $new;
				//array_push($this->Counties, $this->Nums[$i]->$new);

				$county = $new;
				$txt = fopen("data/COUNTIES-ppt.txt", "a");
				$str = "$agency,$county\n";
				fwrite($txt, $str);
				fclose($txt);
				$temp2 = true;
				break;
			    }
			}
			if (!$temp2)
			{
			    $this->Counties[$agency] = "none";
			    //$county = "none";
			}
		    }

		    $county = $this->Counties[$agency];

		    //array_push($this->City, $split[1]);
		    //array_push($this->Address, $split[0]);

		    $str = "description: $description\taddress: $address\tunits: $units\tEpoch: $epoch\n";
		    $arr = array(
			//"State"=>$states[$i],
			"State"=>$state,
			"City"=> $city,
			"County"=> $county,
			"Incident"=>$this->standardize($description),
			"Address"=>$address,
			"Unit"=>$units,
			"latlng"=>$latlng,
			"Date"=>$date,
			"Primary Dispatcher #"=>$agency,
			"Description"=>$description,
			"Source"=>"PULSEPOINT",
			"Logo"=> "",
			"Timestamp"=>$timeStamp,
			"Epoch"=>$epoch,
			"General"=>$str,
			"Number"=>$agency
		    );

		    array_push($this->Incidents, $arr);
		    echo "\t$timeStamp: $description\t $address, $city, $states[$i]  => ".$this->standardize($description)."\n";

		}
	    //}
            $i++;

	    //sleep(5);		// delay after each agency fetch so not to flood the site

        }

    }

    private function filters($des)
    {
        $validIncidents = array (
            "Strike Team" => true,
            "Aircraft Emergency" => true,
            "Aircraft Crash" => true,
            "Explosion" => true,
            "Pipeline Emergency" => true,
            "Transformer Explosion" => true,
            "Structure Fire" => true,
            "Vegetation Fire" => true,
            "Confirmed Structure Fire" => true,
            "Confirmed Vegetation Fire" => true,
            "Commercial Fire" => true,
            "Residential Fire" => true,
            "Working Commercial Fire" => true,
            "Working Residential Fire" => true,
            "Vehicle Fire" => true,
            "Full Assignment" => true,
            "Gas Main" => true,
            "Hazardous Condition" => true,
            "Bomb Threat" => true,
            "Hazmat Response" => true,
            "Multi Casualty" => true,
            "Flood Warning" => true,
            "Flooding" => true,
            "Tornado Warning" => true,
            "Tornado" => true,
            "Tsunami Warning" => true,
            "Tsunami" => true,
            "Rescue" => true,
            "Cliff Rescue" => true,
            "Confined Space" => true,
            "Rope Rescue" => true,
            "Technical Rescue" => true,
            "Trench Rescue" => true,
            "Water Rescue" => true,
            "Expanded Traffic Collision" => true,
            "Traffic Collision" => true,
            "Traffic Collision Involving Train" => true,
            "Wires Arcing" => true,
            "Wires Down" => true,
        );

        //$temp = $validIncidents[$des];
        if(array_key_exists($des, $validIncidents)) {
            return true;
	}

        return false;
    }

    private function standardize($str)
    {
        $stan = array(
            "Strike Team" => "crime",
            "Aircraft Emergency" => "misc",
            "Aircraft Crash" => "misc",
            "Explosion" => "fire",
            "Pipeline Emergency" => "fire",
            "Transformer Explosion" => "fire",
            "Structure Fire" => "fire",
            "Vegetation Fire" => "fire",
            "Confirmed Structure Fire" => "fire",
            "Confirmed Vegetation Fire" => "fire",
            "Commercial Fire" => "fire",
            "Residential Fire" => "fire",
            "Working Commercial Fire" => "fire",
            "Working Residential Fire" => "fire",
            "Vehicle Fire" => "fire",
            "Full Assignment" => "fire",
            "Gas Main" => "hazmat",
            "Hazardous Condition" => "hazmat",
            "Bomb Threat" => "hazmat",
            "Hazmat Response" => "hazmat",
            "Multi Casualty" => "ems",
            "Flood Warning" => "weather",
            "Flooding" => "weather",
            "Tornado Warning" => "weather",
            "Tsunami Warning" => "weather",
            "Tornado" => "weather",
            "Tsunami" => "weather",
            "Rescue" => "rescue",
            "Cliff Rescue" => "rescue",
            "Confined Space" => "rescue",
            "Rope Rescue" => "rescue",
            "Technical Rescue" => "rescue",
            "Trench Rescue" => "rescue",
            "Water Rescue" => "rescue",
            "Expanded Traffic Collision" => "traffic",
            "Traffic Collision" => "traffic",
            "Traffic Collision Involving Train" => "traffic",
            "Wires Arcing" => "misc",
            "Wires Down" => "msic");
        if(array_key_exists($str, $stan))
            return $stan[$str];
        return "none";
    }

    public function getIncidents()
    {
        return $this->Incidents;
    }

    public function getCounties()
    {
        return $this->Counties;
    }
}
