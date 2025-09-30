#!/usr/bin/php
<?php
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');

function doLogin($username,$password)
{
	$mydb = new mysqli('127.0.0.1' , 'admin' , 'AdminPass123!' , 'IT_490');

	if ($mydb->connect_errno != 0) {
		echo "Failed to connect to database: " . $mydb->connect_error . PHP_EOL;
		exit(0);
	}

	echo "Succesfully connected to database".PHP_EOL;

	$query = "SELECT password FROM users WHERE username='" . $username . "';";

	$response = $mydb->query($query);
	if ($mydb->errno != 0)
	{
		echo "failed to execute query:" . PHP_EOL;
		exit(0);
	}
	if ($response->num_rows > 0)
	{
		if($password == $response->fetch_assoc()["password"])
		{
		return array("returnCode" => '1', 'message'=>"Authenticated");
			//return true;
		}
	}
	return array("returnCode" => '2', 'message'=>"Not authenticated");
	//return false;
}

function doRegister($username,$password,$password2)
{
        $mydb = new mysqli('127.0.0.1' , 'admin' , 'AdminPass123!' , 'IT_490');

        if ($mydb->connect_errno != 0) {
                echo "Failed to connect to database: " . $mydb->connect_error . PHP_EOL;
                exit(0);
        }

	echo "Succesfully connected to database".PHP_EOL;

   	if ($password !== $password2) {
        	return array("returnCode" => '4', "message" => "Passwords do not match");
   	}

        $query = "SELECT username  FROM users WHERE username='" . $username . "';";

        $response = $mydb->query($query);
        if ($mydb->errno != 0)
        {
                echo "failed to execute query:" . PHP_EOL;
                exit(0);
        }
        if ($response->num_rows > 0)
        {
		return array("returnCode" => '3', "message" => "User already exists");
        }
	$insert = "INSERT INTO users (username, password) VALUES ('" . $username . "', '" . $password . "');";
	$insertresp = $mydb->query($insert);
	if ($mydb->errno != 0) {
        echo "failed to execute insert query:" . PHP_EOL;
        exit(0);
	}
	if ($insertResp === TRUE) {
        return array("returnCode" => '1', "message" => "You have successfully registered");
	}
	return array("returnCode" => '2', 'message'=>"Registration failed");
        //return false;
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
    case "register":
	    return doRegister($request['username'],$request['password'],$request['password']);
  }
  return array("returnCode" => '0', 'message'=>"Server received request and processed");
}

$server = new rabbitMQServer("testRabbitMQ.ini","testServer");

echo "testRabbitMQServer BEGIN".PHP_EOL;
$server->process_requests('requestProcessor');
echo "testRabbitMQServer END".PHP_EOL;
exit();
?>

