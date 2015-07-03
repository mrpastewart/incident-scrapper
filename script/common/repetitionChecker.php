<?php
/**
 * Created by PhpStorm.
 * User: Lucas Wang
 * Date: 6/30/2015
 * Time: 11:06 AM
 */
class repetitionChecker
{
    private $file;
    private $filename;
    //private $incident = array();
    //private $timestamp;


    public function __construct($fd, $name)
    {
        $this->file = $fd;
        $this->filename = $name;
        //$this->incidents = $incs;
        //$this->timestamp = $num;
    }


    public function checkrep($incident)
    {
        $time = $incident["Epoch"];
        $desc = $incident["Description"];
        $str = "Description: $desc\tEpoch: $time\n";

        $size = filesize("./data/".$this->filename.".txt");
        //echo "       checkrep(): file=./data/".$this->filename.".txt   size=$size    ";
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

    	//echo "      desc=".$desc."*   filename=".$this->filename."*\n";
        if($line == $this->filename) {
            echo "writing: ".$str;
            fwrite($this->file, $str);
            return true;
        }
        else {
            $end = explode("Epoch: ", $line);
            $epoch = $end[1];
            //echo "previous time = ".$epoch."    current_time = ". $time."\n";

            if (($epoch == "0") || ($time > $epoch)) {
                fwrite($this->file, $str);
                return true;
            }
            return false;
        }
    }
}
