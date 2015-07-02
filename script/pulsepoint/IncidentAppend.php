<?php
/**
 * Created by PhpStorm.
 * User: Srivatsav
 * Date: 6/16/2015
 * Time: 9:33 AM
 */

class IncidentAppend {

    public function incidentadd($file, $str)
    {
        //change this so it's adding to the beginning of the fle
        $txt = "$str";
        fwrite($file, $txt);
    }


    public function checkrep($file, $num, $name)
    {
        /*$handle = fopen("$filename.txt", "r");
        if ($handle) {
            while (($buffer = fgets($handle, 4096)) !== false) {
                if($buffer == $str)
                    return false;
            }
            if (!feof($handle)) {
                echo "Error: unexpected fgets() fail\n";
            }
            fclose($handle);
        }
        return true;*/

        //$size = filesize("Counties.txt");
        //fopen("Countie")

        //echo "$filename.txt\n";
        //$line = '';

        //$cursor = -1;
        //echo "$name.txt\n";
        $t =  filesize("data\\$name.txt");
        //echo "$t\n";
        if($t >500)
            fseek($file, $t-500);
        else
        {
            fseek($file, 0);
        }
        //$str = fgets($file);
        //echo $str;
        //sleep(30);
        /*$file = "data\\$name.txt";
        $data = file($file);
        $line = $data[count($data)-1];
        echo $line;*/

        $line = "";
        while (($buffer = fgets($file)) !== false)
        {
            $line = $buffer;
            //echo "$buffer\n";
        }

        //echo "$line\n";

        //sleep(30);
        //echo "here\n";
        //define('YOUR_EOL', "\n");
        //$fp = fopen('yourfile.txt', 'r');

        /*$pos = -1; $line = ''; $c = '';
        do {
            $line = $c . $line;
            fseek($file, $pos--, SEEK_END);
            $c = fgetc($file);
        } while ($c != YOUR_EOL);
        echo "$line\n";*/

        //echo "oneit\n";

        //echo $line;

        /*if (!feof($file)) {
            echo "Error: unexpected fgets() fail\n";
        }*/



        /*$t = "";
        while ($t != "\n") {
            fseek($file, $pos, SEEK_END);
            $t = fgetc($fp);
            $pos = $pos - 1;
        }
        $lastline = fgets($fp);*/
        //echo "last line: $buffer\n";

        //sleep(30);

        $end = explode("Unix: ", $line);
        //echo "\n\n".$end[0] . "\n". $end[1]."\n\n";
        if($end[1] == "0")
        {
            return true;
        }
        $temp = intval($end[1]);

        if($num > $temp)
        {
            return true;
        }
        return false;
    }


}