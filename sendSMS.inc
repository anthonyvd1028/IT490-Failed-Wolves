<?php
require_once __DIR__ . '/vendor/autoload.php';
use Twilio\Rest\Client;

function getApiKey($keyName) {
    $env = __DIR__ . '/.env';

    if (!file_exists($env)) {
        die(".env file is not found." . PHP_EOL);
    }

    $lines = file($env, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        if (strpos(trim($line), "#") === 0) continue;
        $parts = explode('=', $line, 2);
        if ($parts[0] === $keyName) {
            return trim($parts[1]);
        }
    }
}

$code = random_int(100000, 999999);
echo $code;

$twilio_number = '+18553955724';
$number = '+19083613354';

function sendSMS($number, $code) {
    $sid = getApiKey("TWILIO_ACCOUNT_SID");
    $token = getApiKey("TWILIO_AUTH_TOKEN");

    $client = new Client($sid, $token);

    try {
        $message = $client->messages->create(
            $number,
            [
                //"messagingServiceSid" => "MGf8f7ba48da28d83e6f67366b76a8efe1",
                "from" => "+18553955724",   //<-- if not using MG SID
                "body" => "Your OTP: $code"
            ]
        );

	echo $message->sid;
	var_dump($message);


    } catch (Exception $e) {
        echo "Twilio Error: " . $e->getMessage();
    }
}

echo getApiKey("TWILIO_ACCOUNT_SID") . PHP_EOL;
echo getApiKey("TWILIO_AUTH_TOKEN") . PHP_EOL;

sendSMS($number, $code);
