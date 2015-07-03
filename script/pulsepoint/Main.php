<?php
/**
 * Created by PhpStorm.
 * User: Srivatsav
 * Date: 6/16/2015
 * Time: 9:49 AM
 */


//Importing classes
//include "IncidentAppend.php";
include "script/pulsepoint/AgencyFiles.php";
include "script/pulsepoint/GetAgencies.php";
include "script/pulsepoint/getIncidents.php";
include "script/pulsepoint/EmailSender.php";


/*
 * setup timezone so we don't get errors
 */
if( ! ini_get('date.timezone') )
{
    date_default_timezone_set('GMT');
}

$start_time = time();

/*
 * read in county mapping
 */
$Counties = array();
$county_fd = fopen("data/COUNTIES-ppt.txt", "a+");
fseek($county_fd, 0);
if ($county_fd) {
    while (($buffer = fgets($county_fd)) !== false)
    {
        $temp = explode(",", $buffer);
        $Counties[$temp[0]] = $temp[1];
    }
    if (!feof($county_fd)) {
        echo "Error: unexpected fgets() fail\n";
    }
}
fclose($county_fd);


/* DELETE
	//Get agency list
	//$agencies = $GA->getList();

	//Create agency Files
	//$AF = new AgencyFiles($agencies);
	//$AF->agencyCreation();

	//Create local arrays of agency names and numbers
	//$agencynames = $AF->getNames();
	//$descriptors["counties"] = $handle;
*/

$GA = new GetAgencies();
$agencynumbers =  $GA->getNums();
$agencystates = $GA->getStates();
$descriptors = $GA->getDescriptors();
//var_dump($agencynumbers);

//Compile list of every recent incident from pulsepoint
$GI = new getIncidents($agencynumbers, $agencystates, $Counties, $descriptors);
$Incidents = $GI->getIncidents();
//var_dump($Incidents);


/* DELETE
	//Create local arrays for guts of email
	$Description = $GI->getDescription();
	$Units = $GI->getUnits();
	$Addresses = $GI->getAddress();
	$IncidentNumber = $GI->getNumber();
	$StateList = $GI->getStates();
	$Locations = $GI->getGeo();
	$Times = $GI->getTimes();
	$Dates = $GI->getDates();
	$Unix = $GI ->getUnix();

	for($i = 0; $i < sizeof($IncidentNumber); $i++)
	{
	    echo $IncidentNumber[$i] . " " . $Incidents[$i];
	}

	foreach($StateList as $str)
	{
	    echo $str;
	    echo "\n";
	}
	//$ES->sendEmail();
	//$ES = new EmailSender($IncidentNumber, $Description, $Addresses, $Units, $Incidents, $StateList, $Times, $Locations, $Counties, $Dates. $Unix);

	$Counties = $GI->getCounties();
*/


$ES = new EmailSender($Incidents, $descriptors);

$program_time = time()-$start_time;
echo "       Total Run Time: $program_time seconds\n";


?>
