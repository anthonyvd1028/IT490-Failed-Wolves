<?php
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');

$client = new rabbitMQClient("testRabbitMQ.ini","testServer");

if (!isset($_POST))
{
        $msg = "NO POST MESSAGE SET, POLITELY FUCK OFF";
        echo json_encode($msg);
        exit(0);
}
$request = $_POST;
$response = "unsupported request type, politely FUCK OFF";
switch ($request["type"])
{
case "login":
	$rabbitRequest = array();
	$rabbitRequest['type'] = "login";
	$rabbitRequest['username'] = $request["uname"];
	$rabbitRequest['password'] = $request["pword"];
	$response = $client->send_request($rabbitRequest);
	break;
}
 
echo json_encode($response["message"]);
exit(0);

?>

