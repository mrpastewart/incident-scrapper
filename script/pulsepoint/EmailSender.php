<?php
/**
 * Created by PhpStorm.
 * User: Srivatsav
 * Date: 6/16/2015
 * Time: 11:15 AM
 */
include "script/pulsepoint//IncidentAppend.php";
require "script/lib/PHPMailer/PHPMailerAutoload.php";

class EmailSender {
    /*  DELETE
	    private $messages = array();
	    private $State = array();
	    private $City = array();
	    private $Incident = array();
	    private $Address = array();
	    private $Unit = array();
	    private $Nums = array();
	    private $sendToChecker = array();
	    private $localmessage = array();
	    private $Dates = array();
	    private $Times = array();
	    private $Unix = array();
	    private $Geo = array();
	    private $Counties = array();
	    //private $Incidents = array();
    */


//$IncidentNumber, $Description, $Addresses, $Units, $Incidents, $StateList, $Times, $Locations, $Counties, $Dates. $Unix

    public function __construct($Incs, $descs)
    {
        /* DELETE
		$this->Incidents = $arr;
		$this->sendToChecker = $overall;
		$this->Nums = $numbers;
		$this->State = $states;
		$this->Unit = $units;
		$this->Incident = $description;
		$this->Dates = $Date;
		$this->Times = $times;
		$this->Unix = $Un;
		$this->Geo = $geo;
		$this->Counties = $counties;

		foreach ($address as $addr)
		{
		    $split = explode(", ", $addr,2);
		    array_push($this->City, $split[1]);
		    array_push($this->Address, $split[0]);
		}
	*/
        
        for($i = sizeof($Incs)-1; $i >=0; $i--) {

            /* DELETE
		    //County stuff
		    $county = "";
		    if (array_key_exists($this->Nums[$i], $this->Counties))
		    {
			$county = $this->Counties[$this->Nums[$i]];
		    } else {
			$temp = $this->Geo[$i];
			//$temp = explode(",", $this->Geo[$i]);
			//echo $this->location[$i];
			//$temp1 = $temp[0];
			//$temp2 = $temp[1];
			$geocode = file_get_contents("http://maps.googleapis.com/maps/api/geocode/json?latlng=$temp&sensor=false");
			$output = json_decode($geocode);
			$temp = false;
			for ($j = 0; $j < sizeof($output->results[0]->address_components); $j++)
			{
			    //echo $output->results[0]->address_components[$j]->types[0]. ": " .
			    //$this->Counties, $output->results[0]->address_components[$j]->long_name;
			    if ($output->results[0]->address_components[$j]->types[0] == "administrative_area_level_2")
			    {
				$new = $output->results[0]->address_components[$j]->long_name;
				$this->Counties[$this->Nums[$i]] = $new;
				//array_push($this->Counties, $this->Nums[$i]->$new);
				$county = $new;
				$txt = fopen("data/COUNTIES-ppt.txt", "a");
				$temp = $this->Nums[$i];
				$str = "$temp,$county\n";
				fwrite($txt, $str);
				fclose($txt);
				$temp = true;
				break;
			    }
			}
			if (!$temp)
			{
			    //$this->Counties[$this->Nums[$i]] = "none";
			    $county = "none";
			}
		    }

		    State:     PA
		    City/Twp/Box#:  Hershey
		    County:    Erie   <<< MANUALLY ADD COUNTY
		    Incident:   crime   <<< STANDARDIZED CATEGORY
		    Address:    902 E 5TH
		    Unit:   Unit A, Unit B, Unit C
		    latlng:   LAT,LONG
		    Date:   fri, 06, 2015 11:35:30 -0800 (time zone)
		    Primary Dispatcher #:   <Agency name>
		    Description:    Strike Team   <<< MOVE ORIGINAL TYPE HERE
		    Source: PULSEPOINT  <<< HARDCODE THE SOURCE
		    Logo: http://webapp.pulsepoint.org/logo-agencyid.php?agencyid=01008
	    */


            $arr = $Incs[$i];
            $temp1 = $arr["State"];
            $temp2 = $arr["City"];
            $temp3 = $arr["Incident"];
            $county = $arr["County"];
            $temp4 = $arr["Address"];
            $temp5 = $arr["Unit"];
            $temp6 = $arr["latlng"];
            $temp7 = $arr["Date"];
            $temp8 = $arr["Number"];
            $temp9 = $arr["Description"];
            $temp10 = $arr["Source"];
            $str = "State: $temp1<br>City/Twp/Box#: $temp2<br>County: $county<br>Incident: $temp3<br>Address: $temp4<br>".
	    	   "Unit: $temp5<br>Latlng: $temp6<br>Date: $temp7<br>Primary Dispatcher #: $temp8<br>Description: $temp9<br>".
		   "Source: $temp10<br>";

            $str2 = "State: $temp1\nCity/Twp/Box#: $temp2\nCounty: $county\nIncident: $temp3\nAddress: $temp4\nUnit: $temp5\n".
	    	    "Latlng: $temp6\nDate: $temp7\nPrimary Dispatcher #: $temp8\nDescription: $temp9\nSource: $temp10\n";

	    if ($temp3 != "none") {
		echo "      SENDING: $temp9  $temp4  ";
		$this->sendEmail($str, $str2, $arr["General"], $descs[$temp8]);
	    }
        }

    }


    public function sendEmail($message, $localmessage, $sendToChecker, $descriptor)
    {
        $checker = new IncidentAppend();
        $this->emailsend($message, $localmessage);
        $checker->incidentadd($descriptor, $sendToChecker);
        sleep(3);
    }


    private function emailsend($message, $message2)
    {
        //echo $message;
        $myfile = fopen("data/INCIDENTS-pulsepoint.txt", "a") or die("can't open file");
        fwrite($myfile, $message2);
        fclose($myfile);
	date_default_timezone_set('GMT');

        $init = time();
        $mail = new PHPMailer();
        $body= $message;
        $mail->IsSMTP(); // telling the class to use SMTP
        //$mail->SMTPDebug  = 2;                     // enables SMTP debug information (for testing)
        //$mail->Debugoutput = 'html';

        $mail->SMTPAuth   = true;                  // enable SMTP authentication
        $mail->SMTPSecure = "tls";                 // sets the prefix to the servier
        $mail->Host       = "smtp.gmail.com";      // sets GMAIL as the SMTP server
        $mail->Port       = 587;                   // set the SMTP port for the GMAIL server
        //$mail->Username   = "lucas211w@gmail.com";  // GMAIL username
        //$mail->Password   = "tdjlsclzcwwrqteo";            // GMAIL password
        $mail->Username   = "georooArchive@gmail.com";  // GMAIL username
        $mail->Password   = "georoo123";            // GMAIL password

        $mail->SetFrom('support@georoo.com', 'GeoRoo');
        $mail->AddReplyTo('support@georoo.com', 'GeoRoo');
        $mail->Subject    = "Incident Report";
        $mail->msgHTML($body);
        //clark_dong@yahoo.com
        //$address = "pap13p@gmail.com";
        $address = "georooArchive@gmail.com";
        $mail->AddAddress($address, "GeoRoo Archive");

        if(!$mail->Send()) {
            echo "Mailer Error: " . $mail->ErrorInfo;
        } else {
            echo "Message sent!   ";
        }
        echo "\n\n". time()- $init."\n";
    }
}
