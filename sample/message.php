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
	echo json_encode(array("message" => $response["message"], "sessionId" => $response["sessionId"]));
	break;

case "registration":
        $rabbitRequest = array();
        $rabbitRequest['type'] = "registration";
        $rabbitRequest['username'] = $request["uname"];
        $rabbitRequest['password'] = $request["pword"];
        $rabbitRequest['password2'] = $request["pword2"];
        $response = $client->send_request($rabbitRequest);
	echo json_encode(array('message' => $response["message"]));
	break;

case "validateSession":
	$rabbitRequest = array();
        $rabbitRequest['type'] = "validate_session";
        $rabbitRequest['sessionId'] = $request["sessionId"];
        $response = $client->send_request($rabbitRequest);
	echo json_encode(array('message' => $response["message"], 'valid' => $response['valid']));
	break;
case "getOdds":
	$rabbitRequest = array();
	$rabbitRequest['type'] = "getOdds";
	$rabbitRequest['sessionId'] = $request["sessionId"];
	$response = $client->send_request($rabbitRequest);
	echo json_encode(array('data' => $response["data"]));
case "getOddsSportsbook":
	$rabbitRequest = array();
	$rabbitRequest['type' = "getOddsSportsbook";
	$rabbitRequest['sessionId'] = $request["sessionId"];
	$rabbitRequest['sportsbook'] = $request["sportsbook"];
        $response = $client->send_request($rabbitRequest);
	echo json_encode(array('data' => $response["data"]));
}

exit(0);

?> 
