<?php 
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/phpmailer/src/Exception.php';
require __DIR__ . '/phpmailer/src/PHPMailer.php';
require __DIR__ . '/phpmailer/src/SMTP.php';

function getPassword() {
	$env = __DIR__ . '/.env';

	if (!file_exists($env))
	{
		die(".env file is not found." . PHP_EOL);
	}

	$lines = file($env, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

	foreach ($lines as $line)
	{
		if (strpos(trim($line), "#") === 0) continue;
		$parts = explode('=', $line, 2);
		if ($parts[0] === "GMAIL_APP_PASS")
		{
			$key = $parts[1];
			return $key;
		}
	}
}

function sendEmail($to, $subject, $body) {
	try {
		$mail = new PHPMailer(true);

		$mail->SMTPDebug =2;
		$mail->Debugoutput = 'error_log';

		$mail->isSMTP();
		$mail->Host = 'smtp.gmail.com';
		$mail->SMTPAuth = true;
		$mail->Username = 'bettracker.it490@gmail.com';
		$mail->Password = getPassword();
		$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
		$mail->Port = 587;

		$mail->setFrom('bettracker.it490@gmail.com', 'No Reply');
		$mail->addAddress($to);
		$mail->Subject = $subject;
		$mail->Body = $body;
		$mail->send();
	} catch (Exception $e) {
		echo "Could not send mail: {$mail->ErrorInfo}" . PHP_EOL;
		echo "Exception message: " . $e->getMessage() . PHP_EOL;
	}
}


?>
