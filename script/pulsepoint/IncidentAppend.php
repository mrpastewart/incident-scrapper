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
        $txt = "$str";
        fwrite($file, $txt);
    }


    /*
     * update epoch time only at end of each agency pass
     */
    public function checkrep($file, $time, $name, $description, $address)
    {

	$t= fstat($file)['size'];
        if($t > 500) {
            fseek($file, $t-500);
        } else {
            fseek($file, 0);
        }

        $line = "";
        while (($buffer = fgets($file)) !== false)
        {
            $line = $buffer;
            //echo "$buffer\n";
        }
	$line = str_replace("\r", "", $line);   // strip away all possible line breaks
	$line = str_replace("\n", "", $line);

	$str = "Description: $description\tAddress: $address\tEpoch: $time\n";

        $end = explode("Epoch: ", $line);
        $old_epoch = intval($end[1]);

	echo "              $old_epoch   $time   $description  $address  ";
        if(($end[1] == "0") || ($time > $old_epoch)) {
	    echo ".....TRUE\n";
	    fwrite($file, $str);
            return true;
        }
        echo ".....FALSE\n";
        return false;
    }


}
