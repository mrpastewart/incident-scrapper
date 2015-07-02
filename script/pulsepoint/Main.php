<?php
/**
 * Created by PhpStorm.
 * User: Srivatsav
 * Date: 6/16/2015
 * Time: 9:49 AM
 */



//Importing classes
include "script\\pulsepoint\\AgencyFiles.php";
include "script\\pulsepoint\\GetAgencies.php";
include "script\\pulsepoint\\getIncidents.php";
include "script\\pulsepoint\\EmailSender.php";
//include "IncidentAppend.php";
$init = time();
$Counties = array();
$handle = fopen("script\\pulsepoint\\Counties.txt", "a+");
fseek($handle, 0);
if ($handle) {
    while (($buffer = fgets($handle)) !== false)
    {
        $temp = explode(",", $buffer);
        $Counties[$temp[0]] = $temp[1];
    }
    if (!feof($handle)) {
        echo "Error: unexpected fgets() fail\n";
    }
}

fclose($handle);
//Get agency list
$GA = new GetAgencies();
//$agencies = $GA->getList();


//Create agency Files
//$AF = new AgencyFiles($agencies);
//$AF->agencyCreation();



//Create local arrays of agency names and numbers
//$agencynames = $AF->getNames();
$agencynumbers =  $GA->getNums();
$agencystates = $GA->getStates();
$descriptors = $GA->getDescriptors();

//$descriptors["counties"] = $handle;





//Compile list of every recent incident from pulsepoint


$GI = new getIncidents($agencynumbers, $agencystates, $Counties, $descriptors);
$Incidents = $GI->getIncidents();



//Create local arrays for guts of email
/*$Description = $GI->getDescription();
$Units = $GI->getUnits();
$Addresses = $GI->getAddress();
$IncidentNumber = $GI->getNumber();
$StateList = $GI->getStates();
$Locations = $GI->getGeo();
$Times = $GI->getTimes();*/


$Counties = $GI->getCounties();



/*$Dates = $GI->getDates();
$Unix = $GI ->getUnix();*/

/*for($i = 0;$i < sizeof($IncidentNumber);$i++)
{
    echo $IncidentNumber[$i] . " " . $Incidents[$i];
}*/

/*foreach($StateList as $str)
{
    echo $str;
    echo "\n";
}*/

//$ES = new EmailSender($IncidentNumber, $Description, $Addresses, $Units, $Incidents, $StateList, $Times, $Locations, $Counties, $Dates. $Unix);



$ES = new EmailSender($Incidents, $descriptors);

$programtime = time()-$init;
echo $programtime;
//$ES->sendEmail();
?>