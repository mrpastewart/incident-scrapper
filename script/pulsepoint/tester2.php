<?php
/**
 * Created by PhpStorm.
 * User: Srivatsav
 * Date: 6/26/2015
 * Time: 11:35 AM
 */
$file = fopen("data\\test.txt", "a+");
fwrite($file, "first\n");
fwrite($file, "second line\n");
fwrite($file, "last");

//echo filesize("data\\test.txt");


//echo "$name.txt\n";
$t =  filesize("data\\test.txt");
echo "$t\n";
if($t >500)
    fseek($file, $t-500);
else
{
    //echo "here\n";
    fseek($file, 0);
    //echo "here\n";
}
//$str = fgets($file);
while (($str = fgets($file)) !== false)
{
    $buffer = $str;
}

echo $buffer;

//echo "here";
//echo $str;
//sleep(30);
?>
