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


//reading description files
$has_been_seen = array();
$seen = fopen("descriptions.txt", "a+");        //change the location as you see fit
$unknown = fopen("unknown.txt", "a+");
while (($buffer = fgets($seen)) !== false)
{
    $line= explode("@:@", $buffer);
    $index[0];
    $value = $line[1];
    array_push($has_been_seen, $index=>$value);
}










//$dir = new DirectoryIterator("script/singles/new");		// TODO: RE-ENABLE THIS IN PRODUCTION
//$dir = new DirectoryIterator("script/singles/test");		// KLUDGE FOR TESTING ONLY
$dir = new DirectoryIterator("scr");                          // For small scale testing only

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
//echo "here\n";


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
    //echo "Num Scripts: ". sizeof($files)."\n";
    echo "******** agency=$fl\n";
    //if(!$fl = )
    //include("script\\singles\\new\\$fl");
    //include(realpath("script/singles/new/$fl"));
    include(realpath("scr/$fl"));

    $scrape = $incidentList;

    /*
     * insert the agency scrape file calls here
     */
    $size = sizeof(($scrape));
    if($size == 1)
    {
        continue;
    }
    //echo $scrape[$size - 1]["curlWorking"]."\n";
    //echo
    //var_dump($scrape[$size - 1]);

    //if ($scrape[$size - 1]["curlWorking"] == false || $scrape[$size - 1]["parseWorking"] == false) {
    if ($scrape[$size - 1]["curlWorking"] == false) {
        echo "---------------  skipping the rest...\n";

        continue;
    }


    $name = $scrape[$size - 1]["agencyName"];

    $file_path = "data/$name.txt";
    //echo "********** name=$name  file_path=$file_path \n";

    $file_fd = fopen($file_path, "a+");
    echo "file size: " . filesize($file_path). "\n";
    //die();
    if(filesize($file_path) == 0) {
        //echo "here";
        //die();
        fwrite($file_fd, "$name\tEpoch: 0\n");
    }
    //die();
    $Checker->init($file_fd, $scrape);
    //die();
    /*if($Checker->has_epoch == false)
    {
        for($a = $size - 2; $a >= 0; $a--) 
        {
            $arr = $scrape[$a];
            if($Checker)
        }
    }*/

    $epoch = $Checker->getEpoch();
    $o_epoch = $epoch;

    for($a = $size - 2; $a >= 0; $a--) {
        //echo "time: ".$time."\n";
        $arr = $scrape[$a];
	//var_dump($arr);

	/*
         * remember the most recent incident for state file
         */
        $time = floatval($arr["Epoch"]);
        //echo "time: ".$time."\n";
        //echo "                this=".$time."     epoch=".$epoch."  addr=".$incident["Address"]."\n";
        if($time > $epoch) {
            $epoch = $time;
            $last_incident = $arr;
        }

	/* TODO: TURN FILTER ON ONCE FULLY IMPLEMENTED
        if(!filter($arr, $name))
            continue;
	*/
        if($Checker->checkRemaining())
        {
            if(!$Checker->checkrep($arr, $a))
            {
                continue;
            }
        }

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


	//echo "          $temp9:  $temp4";
//        emailsend($str, $str2);			TODO: RE-ENABLED IN PRODUCTION MODE

    }
    //echo "here";
    /*
     * update state file with latest epoch
     */
    if($Checker->has_epoch == true)
    {
        echo "new: ".$epoch."\nold: ". $o_epoch ."\n";
        if($epoch > $o_epoch) 
        {
            $Checker->incidentadd($last_incident);
        }
    }
    else
    {
        echo "index: ".$Checker->index."\n";
        if($Checker->index == -1)
        {
            for($a = $size - 2; $a >= 0; $a--) 
            {
                $arr = $scrape[$a];
                

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
                //emailsend($str, $str2);
            }
            echo "no index\n";
            $Checker->incidentadd($arr);
        }
        if($Checker->index != 0 && $Checker->index != -1)
            $Checker->incidentadd($arr);
        echo "here\n";
    }
}

/**
At the beginning of the file, the program reads the contents of the list of all known descriptions
into an array called $has_been_seen. Each incident in the file is mapped to true or false in the format
"description:true," so the array has true or false values for each value. In the filter method, if the given description
is a key within the local array, then it identifies whether the mapped value is true or false and returns it.
If the key is not found, the description is added to a file called unkown.txt. Periodically, the contents of the unknown file
must be manually mapped to its corresponding boolean value and put in the seen file.
*/
function filter($description)
{
    if(!array_key_exists($description, $has_been_seen))
    {
        fwrite($unknown, "$description\n");
        array_push($has_been_seen, "unknown");
        return true;
    }
    else
    {
        $value = $has_been_seen[$description];
        if($value = "false")
            return false;
        else
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
