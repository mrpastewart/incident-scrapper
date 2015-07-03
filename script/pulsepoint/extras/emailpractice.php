<?php
date_default_timezone_set('Etc/UTC');
require 'PHPMailerAutoload.php';

$mail             = new PHPMailer();
$body= "Tester<br>you suck";
$mail->IsSMTP(); // telling the class to use SMTP
$mail->SMTPDebug  = 2;                     // enables SMTP debug information (for testing)
$mail->Debugoutput = 'html';

$mail->SMTPAuth   = true;                  // enable SMTP authentication
$mail->SMTPSecure = "tls";                 // sets the prefix to the servier
$mail->Host       = "smtp.gmail.com";      // sets GMAIL as the SMTP server
$mail->Port       = 587;                   // set the SMTP port for the GMAIL server
$mail->Username   = "lucas211w@gmail.com";  // GMAIL username
$mail->Password   = "tdjlsclzcwwrqteo";            // GMAIL password
$mail->SetFrom('lucas211w@gmail.com', 'Lucas Wang');
$mail->AddReplyTo("lucas211w@gmail.com","Lucas Wang");
$mail->Subject    = "PHPMailer Test Subject via smtp (Gmail), basic";
$mail->msgHTML($body);
//$address = "pap13p@gmail.com";
$address = "georooArchive@gmail.com";
$mail->AddAddress($address, "Srivatsav Pyda");

if(!$mail->Send()) {
    echo "Mailer Error: " . $mail->ErrorInfo;
} else {
    echo "Message sent!";
}
?>
