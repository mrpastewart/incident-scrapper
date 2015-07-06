<?php
/**
 * Created by PhpStorm.
 * User: Srivatsav
 * Date: 6/29/2015
 * Time: 11:16 AM
 */
//include("script\\pulsepoint\\IncidentAppend");
//include("script\\pulsepoint\\EmailSender");
//include "script\\pulsepoint\\IncidentAppend.php";

//include(".\\script\\common\\repetitionChecker.php");
//require ".\\script\\lib\\PHPMailer\\PHPMailerAutoload.php";
//$dir = new DirectoryIterator(".\\script\\singles\\new");

include("script/common/repetitionChecker.php");
require "script/lib/PHPMailer/PHPMailerAutoload.php";
$dir = new DirectoryIterator("script/singles/new");

/*
 * setup timezone so we don't get errors
 */
if( ! ini_get('date.timezone') )
{
    date_default_timezone_set('GMT');
}

$files = array();
$x = 0;
foreach($dir as $file ){
    $x++;
    //echo $file,"\n";
    if($x >2)
        array_push($files, $file->getFilename());
}



/*
 * MEGA KLUDGE: TESTING ONLY
$files = array();
array_push($files, "AlbemarleCounty.php");
//array_push($files, "Austin.php");
*/



//var_dump($files);

//sleep(30);
//$x = iterator_count(new DirectoryIterator("script\\singles\\old"));
//$x = $x -2;

$Checker = new repetitionChecker();

echo "number of files: ".sizeof($files)."\n";
for($indexer = 0; $indexer < sizeof($files); $indexer++)
{
    $scrape = array();

    $fl = $files[$indexer];
    echo "******** agency=$fl\n";

    //include("script\\singles\\new\\$fl");
    include(realpath("script/singles/new/$fl"));

    $scrape = $incidentList;

    /*
     * insert the agency scrape file calls here
     */
    $size = sizeof(($scrape));
    //echo $scrape[$size - 1]["curlWorking"]."\n";
    //echo
    //var_dump($scrape[$size - 1]);

    //if ($scrape[$size - 1]["curlWorking"] == false || $scrape[$size - 1]["parseWorking"] == false) {
    if ($scrape[$size - 1]["curlWorking"] == false) {
        echo "---------------  skipping the rest...\n";

        continue;
    }


    $name = $scrape[$size - 1]["agencyName"];

    $file_path = "./data/$name.txt";
    //echo "********** name=$name  file_path=$file_path \n";

    $file_fd = fopen($file_path, "a+");
    if(filesize($file_path) == 0) {
        fwrite($file_fd, "$name\tEpoch=0\n");
    }
    $Checker->init($file_fd);
    $epoch = $Checker->getEpoch();
    $o_epoch = $epoch;

    for($a = $size - 2; $a >= 0; $a--) {
        $arr = $scrape[$a];
	//var_dump($arr);

	/*
         * remember the most recent incident for state file
         */
        $time = intval($arr["Epoch"]);
        //echo "                this=".$time."     epoch=".$epoch."  addr=".$incident["Address"]."\n";
        if($time > $epoch) {
            $epoch = $time;
            $last_incident = $arr;
        }

	/* TODO: TURN FILTER ON ONCE FULLY IMPLEMENTED
        if(!filter($arr, $name))
            continue;
	*/

        if(!$Checker->checkrep($arr))
            continue;

        $temp1 = $arr["State"];
        $temp2 = $arr["City"];
        //$county = $arr["County"];
        $temp3 = $arr["Incident"];
        $county = $arr["County"];
        $temp4 = $arr["Address"];
        $temp5 = $arr["Unit"];
        $temp6 = $arr["latlng"];
        $temp7 = $arr["Timestamp"];
        $temp8 = $arr["Primary Dispatcher #"];
        $temp9 = $arr["Description"];
        $temp10 = $arr["Source"];

        $str =  "State: $temp1<br>City/Twp/Box#: $temp2<br>County: $county<br>Incident: $temp3<br>".
	        "Address: $temp4<br>Unit: $temp5<br>Latlng: $temp6<br>Date: $temp7<br>Primary Dispatcher #: $temp8<br>".
		"Description: $temp9<br>Source: $temp10<br>";

        $str2 = "State: $temp1\nCity/Twp/Box#: $temp2\nCounty: $county\nIncident: $temp3\nAddress: $temp4\n".
		"Unit: $temp5\nLatlng: $temp6\nDate: $temp7\nPrimary Dispatcher #: $temp8\nDescription: $temp9\n".
		"Source: $temp10\n";


	echo "          $temp9:  $temp4";
        emailsend($str, $str2);

    }

    /*
     * update state file with latest epoch
     */
    if($epoch > $o_epoch) {
        $Checker->incidentadd($last_incident);
    }
}


function filter($arr, $name)
{
    $txt = $arr["Incident"];
    if($txt =="default")
        return false;
    if($txt == "known")
        return true;
    if($txt == "unknown") {

        $name_update_path = "./data/incident_updates/$name-updates.txt";
        $txt = fopen($name_update_path, "a+");
        $temp = $arr["Description"];
        $temp += "\n";
        fwrite($txt, $temp);
        return true;
    }
}

function emailsend($message, $message2)
{
    //echo $message;
    $incidents_path = "./data/INCIDENTS.txt";
    $myfile = fopen($incidents_path, "a+") or die("can't open file");
    fwrite($myfile, "/n");
    fwrite($myfile, $message2);
    fclose($myfile);
    date_default_timezone_set('Etc/UTC');

    $start_mail = time();
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
    //$mail->SetFrom('lucas211w@gmail.com', 'Lucas Wang');
    //$mail->AddReplyTo("lucas211w@gmail.com","Lucas Wang");

    $mail->Username   = "georooArchive@gmail.com";  // GMAIL username
    $mail->Password   = "georoo123";            // GMAIL password
    $mail->SetFrom('georooArchive@gmail.com', 'GeoRoo Scrapper');
    $mail->AddReplyTo("georooArchive@gmail.com","GeoRoo Archive");

    $mail->Subject    = "Incident Report";
    $mail->msgHTML($body);
    //clark_dong@yahoo.com
    $address = "georooarchive@gmail.com";
    $mail->AddAddress($address, "GeoRoo Test");

    if(!$mail->Send()) {
        echo "Mailer Error: " . $mail->ErrorInfo;
    } else {
        echo "     EMAIL sent!   ";
    }
    echo "   ". time()- $start_mail." seconds\n";
}
?>
