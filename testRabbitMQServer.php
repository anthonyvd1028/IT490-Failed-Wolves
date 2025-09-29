#!/usr/bin/php
<?php
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');

function doLogin($username,$password)
{
	// lookup username in databas
	$mydb = new mysqli('127.0.0.1' , 'admin' , 'AdminPass123!' , 'IT_490');

	if ($mydb->connect_errno != 0) {
		echo "Failed to connect to database: " . $mydb->connect_error . PHP_EOL;
		exit(0);
	}

	echo "Succesfully connected to database".PHP_EOL;

	//prepare and execute query
	$query = "SELECT password FROM users WHERE username='" . $username . "';";

	$response = $mydb->query($query);
	if ($response->num_rows > 0)
	{
		if($password == $response->fetch_assoc()["password"])
		{
			//echo "true";
			return true;
		}
	}
 	//echo "false";
	return false; //user not found
}

function requestProcessor($request)
{
  echo "received request".PHP_EOL;
  var_dump($request);
  if(!isset($request['type']))
  {
    return "ERROR: unsupported message type";
  }
  switch ($request['type'])
  {
    case "login":
        return doLogin($request['username'],$request['password']);
    case "validate_session":
      return doValidate($request['sessionId']);
  }
  return array("returnCode" => '0', 'message'=>"Server received request and processed");
}

$server = new rabbitMQServer("testRabbitMQ.ini","testServer");

echo "testRabbitMQServer BEGIN".PHP_EOL;
$server->process_requests('requestProcessor');
echo "testRabbitMQServer END".PHP_EOL;
exit();
?>

