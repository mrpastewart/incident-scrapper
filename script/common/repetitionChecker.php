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
	public $has_epoch;
	private $description;
	private $address;
	private $checkrest;
	private $shouldadd;
	public $index;
	
    //private $incident = array();
    //private $timestamp;


    //public function __construct($fd, $name)
    /*public function getnewest()
    {
    	return $this->newest;
    }
    public function setnewest($incident)
    {
    	$this->newest = $incident;
    }*/
    public function getIndex()
    {
    	return $this->index;
    }
    public function init($fd, $arr)
    {
    	$this->index = -1;
        $this->file = $fd;
        $this->checkrest = true;
        $this->shouldadd = false;
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
	    if($arr[0]["Epoch"] != "none")
		{
		    $this->epoch = floatval($end[1]);
			echo "       LAST_EPOCH: ".$this->epoch."\n";
			$this->has_epoch = true;
		}
		else
		{
			//echo "here2";
			//echo "type two. \n";
			//die();
			$this->has_epoch = false;
			if($end[1] == "0")
			{
				//echo "here";
				$this->shouldadd = true;
				$this->description = "";
				$this->address = "";
			}
			else
			{
				//echo $line."\n";
				$end = explode("\t", $line);
				$desc = substr($end[0], 13, strlen($end[0]));
				$addr = substr($end[1], 9, strlen($end[1]));
				$this->description = $desc;
				$this->address = $addr;
				echo "description: ".$desc."\naddress: ".$addr."\n";
			}

			//die();
	    }
	}


    public function getEpoch() {
    	return $this->epoch;
    }
	public function getAddr()
	{
		return $this->address;
	}
	public function getDesc()
	{
		return $this->description;
	}
	public function checkRemaining()
	{
		return $this->checkrest;
	}
    public function incidentadd($in) 
	{
		//var_dump($in);
		$str = "description: ".$in["Description"]."\taddress: ".$in["Address"].
		"\tunits: ".$in["Unit"]."\tEpoch: ".$in["Epoch"]."\n";
        fwrite($this->file, $str);

		//echo "***** NEW_EPOCH: ".$str."\n";
    }
	
    public function checkrep($incident, $ind)
    {
    	if($this->shouldadd == true)
    	{
    		//$this->shouldadd = false;
    		//$this->newest = $incident;
    		echo "shouldadd\n";
    		return true;            
    	}
        $time = $incident["Epoch"];
        $desc = $incident["Description"];
        $addr = $incident["Address"];
        //$str = "Description: $desc\tEpoch: $time\n";
		//echo "prev_time=".$this->epoch."    current_time=". $time."   addr=".$addr;
		if($this->has_epoch)
		{
			echo "has epoch \n";
			//echo "new: ".$time."\nold: $this->epoch\n";
			//echo "here3";
			if ($time > $this->epoch) {
			//echo "....TRUE\n";
				return true;
			}
			//echo "....FALSE\n";
			return false;
		}
		else
		{
			echo "no epoch\n";
			if($desc == $this->description && $addr == $this->address)
			{
				$this->index = $ind;
				echo "index: ".$this->index."\n";
				$this->checkrest = false;
				return false;
			}
			//$this->newest = $incident;
			return false;
		}
    }
}
