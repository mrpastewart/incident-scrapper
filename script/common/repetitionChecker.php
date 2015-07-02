<?php
/**
 * Created by PhpStorm.
 * User: Lucas Wang
 * Date: 6/30/2015
 * Time: 11:06 AM
 */
class repetitionChecker
{
    //private $incident = array();
    private $file;
    private $filename;
    //private $timestamp;


    public function __construct($fd, $name)
    {
        //$this->incidents = $incs;
        $this->file = $fd;
        $this->filename = $name;
        //$this->timestamp = $num;
    }
    public function checkrep($incident)
    {
        $time = $incident["Unix Value"];
        $temp1 = $incident["Description"];
        $str = "Description: $temp1\tUnix: $time\n";
        $size = filesize("data\\$this->filename.txt");
        //echo $size;
        if($size >500)
            fseek($this->file, $size-500);
        else
            fseek($this->file, 0);
        $line = "";
        while (($buffer = fgets($this->file)) !== false)
        {
            $line = $buffer;
            //echo "$buffer\n";
        }
        if($line == $this->filename) {
            echo "here";
            fwrite($this->file, $str);
            return true;
        }
        else {
            $end = explode("Unix: ", $line);
            if ($end[1] == "0") {
                return true;
            }
            $temp = $end[1];
            echo "previous time = ".$temp."\ncurrent time = ". $time;
            if ($time > $temp) {
                //fwrite($this->file, $str);
                //echo "here";
                return true;
            }
            //echo "here";
            return false;
        }
    }
}