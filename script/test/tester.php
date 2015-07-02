<?php
/**
 * Created by PhpStorm.
 * User: Srivatsav
 * Date: 6/29/2015
 * Time: 7:59 PM
 */
include("script\\common\\repetitionChecker.php");
//echo var_dump($incidentList[sizeof($incidentList)-1]);
$txt = fopen("data\\butler_county.txt", "a+");
$RC = new repetitionChecker($txt, "butler_county");

$incident = array(
    "Description"=>"Russian Invasion",
    "Unix Value"=>2871304930
);
//echo $RC->checkrep($incident);
if($RC->checkrep($incident) == false)
    echo "false";
else
    echo "true";