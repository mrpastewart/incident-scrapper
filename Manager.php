<?php
/**
 * Created by PhpStorm.
 * User: Srivatsav
 * Date: 6/29/2015
 * Time: 11:16 AM
 */
//include("script\\pulsepoint\\IncidentAppend");
//include("script\\pulsepoint\\EmailSender");
include("script\\common\\repetitionChecker.php");
//include "script\\pulsepoint\\IncidentAppend.php";
require "script\\lib\\PHPMailer\\PHPMailerAutoload.php";

$dir = new DirectoryIterator("script\\singles\\new");
$files = array();
$x = 0;
foreach($dir as $file ){
    $x++;
    //echo $file,"\n";
    if($x >2)
        array_push($files, $file->getFilename());
}

//var_dump($files);

//sleep(30);
//$x = iterator_count(new DirectoryIterator("script\\singles\\old"));
$x = $x -2;

for($indexer = 0;$indexer < $x;$indexer++)
{
    echo $x;
    echo "\n\nnew one\n\n";
    $scrape = array();

    $fl = $files[$indexer];
    //echo $fl."\n";
    //echo "here"."\n";
    //sleep(30);
    include("script\\singles\\new\\$fl");
    $scrape = $incidentList;

    /*
    insert the agency scrape file calls here
    */
    //variable scrape will be array containing info


    $size = sizeof(($scrape));
    //echo $scrape[$size - 1]["curlWorking"]."\n";
    //echo
    if ($scrape[$size - 1]["curlWorking"] == false || $scrape[$size - 1]["parseWorking"] == false) {
        echo "here\n";

        continue;
    }
    /*if (!$f->checkrep($txt, $arr[0]["timestamp"], $name))
        continue;*/


    $name = $scrape[$size - 1]["agencyName"];
    //echo $name."\n"
    $txt = fopen("data\\$name.txt", "a+");
    if(filesize("data\\$name.txt") == 0)
        fwrite($txt, "$name\n");
    $reps = new repetitionChecker($txt, $name);
    //sleep(30);
    for($a = $size - 2;$a >= 0;$a--)
    {
        //echo $a;
        $arr = $scrape[$a];
        if(!filter($arr, $name))
            continue;
        if(!$reps->checkrep($arr))
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
        $temp8 = $arr["Number"];
        $temp9 = $arr["Description"];
        $temp10 = $arr["Source"];

        $str = "State: $temp1<br>City/Twp/Box#: $temp2<br>County: $county<br>Incident: $temp3<br>Address: $temp4<br>Unit: $temp5<br>Latlng: $temp6<br>Date: $temp7<br>Primary Dispatcher #: $temp8<br>Description: $temp9<br>Source: $temp10<br>";
        $str2 = "State: $temp1\nCity/Twp/Box#: $temp2\nCounty: $county\nIncident: $temp3\nAddress: $temp4\nUnit: $temp5\nLatlng: $temp6\nDate: $temp7\nPrimary Dispatcher #: $temp8\nDescription: $temp9\nSource: $temp10\n";
        emailsend($str, $str2);

    }
    echo "here $indexer\n";
}
function filter($arr, $name)
{
    $txt = $arr["Incident"];
    if($txt =="default")
        return false;
    if($txt == "known")
        return true;
    if($txt == "unknown") {
        $txt = fopen("data\\Incident Updates\\$name-updates.txt", "a+");
        $temp = $arr["Description"];
        $temp += "\n";
        fwrite($txt, $temp);
        return true;
    }
}
function emailsend($message, $message2)
{
    //echo $message;
    $myfile = fopen("data\\incidents.txt", "a+") or die("can't open file");
    fwrite($myfile, $message2);
    fclose($myfile);
    date_default_timezone_set('Etc/UTC');

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
    $mail->Username   = "lucas211w@gmail.com";  // GMAIL username
    $mail->Password   = "tdjlsclzcwwrqteo";            // GMAIL password
    $mail->SetFrom('lucas211w@gmail.com', 'Lucas Wang');
    $mail->AddReplyTo("lucas211w@gmail.com","Lucas Wang");
    $mail->Subject    = "Incident Report";
    $mail->msgHTML($body);
    //clark_dong@yahoo.com
    $address = "pap13p@gmail.com";
    $mail->AddAddress($address, "Srivatsav Pyda");

    if(!$mail->Send()) {
        echo "Mailer Error: " . $mail->ErrorInfo;
    } else {
        echo "Message sent!";
    }
    echo "\n\n". time()- $init."\n";
}
?>