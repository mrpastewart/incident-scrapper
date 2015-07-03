<?php
/**
 * Created by PhpStorm.
 * User: Srivatsav
 * Date: 6/16/2015
 * Time: 9:49 AM
 */



//Importing classes
//include "AgencyFiles.php";
//include "GetAgencies.php";
//include "IncidentAppend.php";
include "getIncidents.php";
include "EmailSender.php";

/*
 * setup timezone so we don't get errors
 */
if( ! ini_get('date.timezone') )
{
    date_default_timezone_set('GMT');
}

/*
//Get agency list
$GA = new GetAgencies();
$agencies = $GA->getList();


//Create agency Files
$AF = new AgencyFiles($agencies);
$AF->agencyCreation();



//Create local arrays of agency names and numbers
$agencynames = $AF->getNames();
//$agencynumbers =  $AF->getNumbers();
//$agencystates = $AF->getStates();*/
$agencynumbers = array("LAFDS", "43070");
$agencystates = array("CA", "CA");




//Compile list of every recent incident from pulsepoint
$GI = new getIncidents($agencynumbers, $agencystates);
$Incidents = $GI->listIncidents();



//Create local arrays for guts of email
$Description = $GI->getDescription();
$Units = $GI->getUnits();
$Addresses = $GI->getAddress();
$IncidentNumber = $GI->getNumber();
$StateList = $GI->getStates();


/*foreach($StateList as $str)
{
    echo $str;
    echo "\n";
}*/

//for($i = 0;$i < 10;$i++)
//{
    $ES = new EmailSender($IncidentNumber, $Description, $Addresses, $Units, $Incidents, $StateList);
    $ES->sendEmail();
    //sleep(600);
//}
?>
