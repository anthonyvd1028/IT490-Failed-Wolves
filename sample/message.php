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
if (!empty($_POST))
{
	$request = $_POST;
} else {
	$request = json_decode(file_get_contents('php://input'), true);	
}

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
	$rabbitRequest['email'] = $request["email"];       
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
	$response = $client->send_request($rabbitRequest);
	echo json_encode(array('data' => $response["games"]));
	break;
case "getOddsSportsbook":
	$rabbitRequest = array();
	$rabbitRequest['type'] = "getOddsSportsbook";
	$rabbitRequest['sportsbook'] = $request["sportsbook"];
        $response = $client->send_request($rabbitRequest);
	echo json_encode(array('data' => $response["games"]));
	break;
case "sendBet":
	$rabbitRequest = array();
	$rabbitRequest['type'] = "sendBet";
	$rabbitRequest['sessionId'] = $request["sessionId"];
	$rabbitRequest['wager'] = $request['wager'];
	$rabbitRequest['bet'] = $request['bet'];
	$response = $client->send_request($rabbitRequest);
	echo json_encode(array('message' => $response['message']));
	break;
case "getPortfolio":
	$rabbitRequest = array();
	$rabbitRequest['type'] = "getPortfolio";
	$rabbitRequest['sessionId'] = $request['sessionId'];
	$response = $client->send_request($rabbitRequest);
	echo json_encode(array('stats' => $response['stats']));
	break;
case "insertWatchlist":
	$rabbitRequest = array();
	$rabbitRequest['type'] = "insertWatchlist";
	$rabbitRequest['sessionId'] = $request['sessionId'];
	$rabbitRequest['oddId'] = $request['oddId'];
	$response = $client->send_request($rabbitRequest);
	echo json_encode(array('message' => $response['message']));
	break;
case "getWatchlist":
	$rabbitRequest = array();
	$rabbitRequest['type'] = "getWatchlist";
	$rabbitRequest['sessionId'] = $request['sessionId'];
	$response = $client->send_request($rabbitRequest);
	echo json_encode(array('watchlist' => $response['watchlist']));
	break;
}
exit(0);
?> 
