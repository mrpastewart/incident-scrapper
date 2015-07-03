<?php
/**
 * Created by PhpStorm.
 * User: Srivatsav
 * Date: 6/14/2015
 * Time: 9:08 PM
 */
class AgencyFiles
{

    private    $agencynames = array();
    private    $agencynumber = array();
    private    $agencystate = array();

    //constructor
    public function __construct(/*array*/ $agencies) {
        for($i = 1; $i < sizeOf($agencies); $i++)
        {
            array_push($this->agencynumber,$agencies[$i]["number"]);
            array_push($this->agencynames, $agencies[$i]["name"]);
            array_push($this->agencystate, $agencies[$i]["state"]);
        }
    }
    public function getNames()
    {
        return $this->agencynames;
    }
    public function getNumbers()
    {
        return $this->agencynumber;
    }
    public function getStates()
    {
        return $this->agencystate;
    }
    public function agencyCreation()
    {
        for($i = 0;$i < sizeof($this->agencynumber);$i++) {
	    $path = "data/ppt-$temp1-$temp3.txt";

            //Create a file for each agency, each file name is based on agency number
            $temp = $this->agencynumber[$i];
            if (!file_exists($path)) {
                $temp1 = $this->agencynumber[$i];
                $temp2 = $this->agencynames[$i];
                $temp3 = $this->agencystate[$i];
                $myfile = fopen($path, "w") or die("Unable to open file!");
                $txt = "$temp2 Epoch: 0\n";
                fwrite($myfile, $txt);
                //fclose($myfile);
            }
        }
    }
}
