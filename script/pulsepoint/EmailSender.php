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

    public function __construct() {
    }

    public function sendEmail($Incident, $desc) {
            $arr = $Incident;
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

	    echo "      SENDING: $temp9  $temp4  ";
	    $this->sendEmail1($str, $str2, $arr["General"], $desc);
    }

    public function sendEmail1($message, $localmessage, $sendToChecker, $descriptor)
    {
        $checker = new IncidentAppend();
        $this->emailsend($message, $localmessage);
        $checker->incidentadd($descriptor, $sendToChecker);
        //sleep(3);
    }


    private function emailsend($message, $message2)
    {
        $myfile = fopen("data/INCIDENTS-pulsepoint.txt", "a") or die("can't open file");
        fwrite($myfile, $message2."\n");
        fclose($myfile);
	date_default_timezone_set('GMT');

        $email_start = time();
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
        echo "    ". time()- $email_start." secs\n";
    }
}
