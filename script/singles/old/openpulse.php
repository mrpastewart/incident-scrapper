<?php
/**
 * Created by PhpStorm.
 * User: Srivatsav
 * Date: 6/8/2015
 * Time: 4:45 PM
 */
/
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://webapp.pulsepoint.org/active_incidents.php?agencyid=32D01");
curl_exec($ch);
curl_close($ch);
?>
