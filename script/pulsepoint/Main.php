<?php
/**
 * Created by PhpStorm.
 * User: Srivatsav
 * Date: 6/16/2015
 * Time: 9:49 AM
 */


//Importing classes
include "script/common/repetitionChecker.php";
include "script/pulsepoint/AgencyFiles.php";
include "script/pulsepoint/GetAgencies.php";
include "script/pulsepoint/getIncidents.php";
include "script/pulsepoint/EmailSender.php";

$start_time = time();

/*
 * setup timezone so we don't get errors
 */
if( ! ini_get('date.timezone') )
{
    date_default_timezone_set('GMT');
}

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


$Agencies = new Agencies();
$Incidents = new Incidents();
$Email = new EmailSender();
$Checker = new repetitionChecker();
//$Checker = new IncidentAppend();

$agencynumbers =  $Agencies->getNums();
$agencystates = $Agencies->getStates();
$descriptors = $Agencies->getDescriptors();
//var_dump($agencynumbers);


/*
 * process one agency at a time
 */
for($i = 0; $i < count($agencynumbers); $i++) {
    $last_incident = null;

    $Incidents->init($agencynumbers[$i], $agencystates[$i], $Counties);
    $incidents = $Incidents->getIncidents();
    //var_dump($incidents);

    $file_fd = $descriptors[$agencynumbers[$i]];
    $Checker->init($file_fd);
    $epoch = $Checker->getEpoch();
    $o_epoch = $epoch;			// for comparison
    //echo "********** fd=$file_fd   agency=".$agencynumbers[$i]."  epoch=".$epoch."\n";

    /*
     * process in reverse, oldest first
     */
    for($j = count($incidents); $j > 0; $j--) {
	$incident = $incidents[$j-1];

	/*
	 * remember the most recent incident for state file
	 */
	$time = intval($incident["Epoch"]);
	//echo "                this=".$time."     epoch=".$epoch."  addr=".$incident["Address"]."\n";
	if($time > $epoch) {
	    $epoch = $time;
	    $last_incident = $incident;
	}

	/*
	 * send only new incidents
	 */
	if(($Checker->checkrep($incident)) && ($incident["Incident"] != "none")) {
	    $Email->sendEmail($incident, $file_fd);
	}

    }

    /*
     * update state file with latest epoch
     */
    if($epoch > $o_epoch) {
	$Checker->incidentadd($last_incident);
    }

}


$program_time = time()-$start_time;
echo "       Total Run Time: $program_time seconds\n";


?>
