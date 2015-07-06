<?php
/**
 * Created by PhpStorm.
 * User: Lucas Wang
 * Date: 6/30/2015
 * Time: 11:06 AM
 */
class RepetitionChecker
{
    private $file;
    private $filename;
    private $epoch;
    //private $incident = array();
    //private $timestamp;


    //public function __construct($fd, $name)
    public function init($fd)
    {
        $this->file = $fd;
        //$this->filename = $name;	// TODO: no need for filename
        //$this->incidents = $incs;
        //$this->timestamp = $num;

	/*
	 * read in last line of state file
	 */
	$size = fstat($this->file)['size'];	// get it from fd, not filename
        if($size >500)
            fseek($this->file, $size-500);
        else
            fseek($this->file, 0);

        $line = "";
        while (($buffer = fgets($this->file)) !== false)
        {
            $line = $buffer;
        }
	$line = str_replace("\r", "", $line);	// strip away all possible line breaks
	$line = str_replace("\n", "", $line);

	/*
    	//echo "      desc=".$desc."*   filename=".$this->filename."*\n";
        if($line == $this->filename) {
            //fwrite($this->file, $str);
            $this->epoch = 0;
        } else {
            $end = explode("Epoch: ", $line);
            $this->epoch = intval($end[1]);
	}
	*/

	$end = explode("Epoch: ", $line);
	$this->epoch = intval($end[1]);
	echo "       LAST_EPOCH: ".$this->epoch."\n";

    }


    public function getEpoch() {
    	return $this->epoch;
    }

    public function incidentadd($in) {
	//var_dump($in);
	$str = "description: ".$in["Description"]."\taddress: ".$in["Address"].
		"\tunits: ".$in["Units"]."\tEpoch: ".$in["Epoch"]."\n";
        fwrite($this->file, $str);

	echo "***** NEW_EPOCH: ".$str."\n";
    }

    public function checkrep($incident)
    {
        $time = $incident["Epoch"];
        $desc = $incident["Description"];
        $addr = $incident["Address"];
        //$str = "Description: $desc\tEpoch: $time\n";

	//echo "prev_time=".$this->epoch."    current_time=". $time."   addr=".$addr;
	if ($time > $this->epoch) {
	    //echo "....TRUE\n";
	    return true;
	}
	//echo "....FALSE\n";
	return false;
    }
}
