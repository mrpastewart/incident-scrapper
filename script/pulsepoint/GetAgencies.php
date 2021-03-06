<?php
/**
 * Created by PhpStorm.
 * User: Srivatsav
 * Date: 6/16/2015
 * Time: 10:42 AM
 */
include("script/lib/DOM/simple_html_dom.php");


class Agencies {

    private $states = array();
    private $numbers = array();
    private $descriptors = array();
    //private $names = array();


    public function __construct()
    {
        $descs = array();
        $html = file_get_html('http://webapp.pulsepoint.org/');
        foreach($html->find('option') as $e)
        {
            $firstBracketPos = strpos($e->innertext,'[');
            $temp = substr($e->innertext,$firstBracketPos+1,$firstBracketPos+3);
            $state = substr($temp, 0, 2);


            //push individual values on
            array_push($this->states, $state);
            array_push($this->numbers, $e -> value);
            //array_push($this->names, substr($e->innertext,0,$firstBracketPos-1));

            $agencyid = $e -> value;
            $path = "data/ppt-$agencyid-$state.txt";
            if (!file_exists($path))
            {
                $myfile = fopen($path, "a+") or die("Unable to open file!");
                $temp2 = substr($e->innertext,0,$firstBracketPos-1);
                $txt = "$temp2 Epoch: 0\n";
                fwrite($myfile, $txt);
            }
            else
            {
                $myfile = fopen($path, "a+") or die("Unable to open file!");
            }

            $descs[$agencyid]= $myfile;
            /* 
	    if($descs[$agencyid]==null)
            {
                echo "BROKEN";
                die;
            }
	    */
        }
        $this->descriptors = $descs;
    }

    public function getStates() {
        return $this->states;
    }

    public function getNums() {
        return $this->numbers;
    }

    public function getDescriptors() {
        return $this->descriptors;
    }
}
