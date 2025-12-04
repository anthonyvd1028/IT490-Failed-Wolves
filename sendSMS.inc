<?php
include 'Email.inc';

$number = 9738978373;
$code = random_int(100000,999999);
function sendSMS($number, $code){
	$to = $number . '@vtext.com';
	$body = "Your BetTracker OTP code is: " .  $code;
	if (sendEmail($to, "", $body)) {
        	echo "Success" . PHP_EOL;
	} else {
        	echo "SMS could not be sent" . PHP_EOL;
	}
}
sendSMS($number, $code);
?>

