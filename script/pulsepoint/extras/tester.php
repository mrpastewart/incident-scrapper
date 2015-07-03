<?php
/**
 * Created by PhpStorm.
 * User: Srivatsav
 * Date: 6/23/2015
 * Time: 9:14 AM
 */




//Importing classes
include "AgencyFiles.php";
include "GetAgencies.php";
include "getIncidents.php";
include "EmailSender.php";
//include "IncidentAppend.php";

$init = time();

$Counties = array();
$handle = fopen("Counties.txt", "a+");
fseek($handle, 0);
if ($handle) {
    while (($buffer = fgets($handle, 4096)) !== false)
    {
        $temp = explode(",", $buffer);
        $Counties[$temp[0]] = $temp[1];
    }
    if (!feof($handle)) {
        echo "Error: unexpected fgets() fail\n";
    }
}


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
$descriptors["counties"] = $handle;

$programTime = time() - $init;


/*for($i = 0;$i<sizeof($agencystates);$i++)
{
    echo "$agencystates[$i], $agencynumbers[$i]\n";
}
echo sizeof($descriptors);
echo "\n\nTime Taken: $programTime";*/

$GI = new getIncidents($agencynumbers, $agencystates, $Counties, $descriptors);
$Incidents = $GI->getIncidents();

$programTime = time() - $init;

foreach($Incidents as $Incident)
{
    printf(var_dump($Incident));
    echo "\n";
}
echo "\n\nTime Taken: $programTime";
?>