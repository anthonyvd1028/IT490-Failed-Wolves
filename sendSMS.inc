<?php
include 'Email.inc';

function sendSMS($number, $code){
	$to = $number . '@vtext.com';
	$body = "Your BetTracker OTP code is: " . $code;
	if (sendEmail($to, "", $body)) {
		echo "SMS sent successfully" . PHP_EOL;
	} else {
		echo "SMS could not be sent" . PHP_EOL;
	}
}
?>
