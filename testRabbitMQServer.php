#!/usr/bin/php
<?php
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');

function insertData($response)
{
	$mydb = new mysqli('127.0.0.1' , 'admin' , 'AdminPass123!' , 'IT_490');
    	if ($mydb->connect_errno != 0) {
        	echo "Failed to connect to database: " . $mydb->connect_error . PHP_EOL;
        	exit(0);
    	}
	
	$home = $response['data']['home'];
	$away = $response['data']['away'];
	$start = $response['data']['start'];
	
	$query = "SELECT * FROM Events WHERE HomeTeam = '$home' AND AwayTeam = '$away';";
	
	$results = $mydb->query($query);
    	if ($mydb->errno != 0) {
        	echo "failed to execute query:" . PHP_EOL;
        	exit(0);
    	}

	if ($results->num_rows === 0)
	{
		$query = "INSERT INTO Events (HomeTeam, AwayTeam, StartsAt) VALUES ('$home', '$away', '$start');";

		$results = $mydb->query($query);
    		if ($mydb->errno != 0) {
        		echo "failed to execute query:" . PHP_EOL;
        		exit(0);
		}
	}
	
	
	$query = "SELECT * FROM Events WHERE HomeTeam = '$home' AND AwayTeam = '$away';";
	
	$results = $mydb->query($query);
    	if ($mydb->errno != 0) {
        	echo "failed to execute query:" . PHP_EOL;
        	exit(0);
    	}
	
        $eventID = $results->fetch_assoc()['EventID'];
	
	foreach ($response['data']['homeML'] as $book => $value)
	{
		$query = "INSERT INTO Odds (EventID, Sportsbook, BetType, Value, Odds) VALUES ('$eventID', '$book', 'ML', 'HomeML', '$value');";	
	     	echo $query . PHP_EOL;

		$results = $mydb->query($query);
    		if ($mydb->errno != 0) {
        		echo "failed to execute query:" . PHP_EOL;
        		exit(0);
    		}
	}




	return;
}

function createSession($id, $username)
{
    $mydb = new mysqli('127.0.0.1' , 'admin' , 'AdminPass123!' , 'IT_490');
    if ($mydb->connect_errno != 0) {
        echo "Failed to connect to database: " . $mydb->connect_error . PHP_EOL;
        exit(0);
    }
    $query = "SELECT session_token FROM sessions WHERE username='" . $username . "';";
    $response = $mydb->query($query);
    if ($mydb->errno != 0) {
        echo "failed to execute query:" . PHP_EOL;
        exit(0);
    }
    if ($response->num_rows > 0) {
        $old_token = $response->fetch_assoc()['session_token'];
        $query = "DELETE FROM sessions WHERE session_token='" . $old_token . "';";
        $response = $mydb->query($query);
        if ($mydb->errno != 0) {
            echo "failed to execute query:" . PHP_EOL;
            exit(0);
        }
    }
    $new_token = hash('sha256', $id . random_int(0,1000));
    $query = "INSERT INTO sessions (session_token, username, expiration) VALUES ('" . $new_token . "', '" . $username . "', DATE_ADD(NOW(), INTERVAL 1 HOUR));";
    $response = $mydb->query($query);
    if ($mydb->errno != 0) {
        echo "failed to execute query:" . PHP_EOL;
        exit(0);
    }
    return $new_token;
}

function doLogin($username,$password)
{
    $mydb = new mysqli('127.0.0.1' , 'admin' , 'AdminPass123!' , 'IT_490');
    if ($mydb->connect_errno != 0) {
        echo "Failed to connect to database: " . $mydb->connect_error . PHP_EOL;
        exit(0);
    }
    echo "Succesfully connected to database".PHP_EOL;
    $query = "SELECT password, id FROM users WHERE username='" . $username . "';";
    $response = $mydb->query($query);
    if ($mydb->errno != 0) {
        echo "failed to execute query:" . PHP_EOL;
        exit(0);
    }
    if ($response->num_rows > 0) {
        $response = $response->fetch_assoc();
	$password =  hash('sha256', $password);
	if($password == $response["password"]) {
            $id = createSession($response['id'], $username);    
            return array("returnCode" => '1', 'message'=>"Authenticated", 'sessionId' => $id);
	} else {
	    return array("returnCode" => '2', 'message'=>"Invalid password");
	}
    }
    return array("returnCode" => '2', 'message'=>"Invalid Username or Password");
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
    $query = "SELECT username FROM users WHERE username='" . $username . "';";
    $response = $mydb->query($query);
    if ($mydb->errno != 0) {
        echo "failed to execute query:" . PHP_EOL;
        exit(0);
    }
    if ($response->num_rows > 0) {
        return array("returnCode" => '3', "message" => "User already exists");
    }

    $password = hash('sha256', $password);

    $insert = "INSERT INTO users (username, password) VALUES ('" . $username . "', '" . $password . "');";
    $insertResp = $mydb->query($insert);
    if ($mydb->errno != 0) {
        echo "failed to execute insert query:" . PHP_EOL;
        exit(0);
    }
    if ($insertResp === TRUE) {
        return array("returnCode" => '1', "message" => "You have successfully registered");
    }
    return array("returnCode" => '2', 'message'=>"Registration failed");
}

function validateSession($sessionId)
{
    $mydb = new mysqli('127.0.0.1' , 'admin' , 'AdminPass123!' , 'IT_490');
    if ($mydb->connect_errno != 0) {
        echo "Failed to connect to database: " . $mydb->connect_error . PHP_EOL;
        exit(0);
    }
    
    $stmt = $mydb->prepare("SELECT username, expiration FROM sessions WHERE session_token = ? LIMIT 1");
    if (!$stmt) {
        echo "failed to prepare statement:" . PHP_EOL;
        exit(0);
    }

    $stmt->bind_param('s', $sessionId);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($res->num_rows === 0) {
        $stmt->close();
	return array("returnCode" => '2', "message" => "Invalid session", "valid" => false);
    } else {
    	$row = $res->fetch_assoc();
    	$stmt->close();
    	date_default_timezone_set("America/New_York"); 
    
    	$exp = strtotime($row['expiration']);
    	$now = time();

    	if ($exp <= $now) {
    	    return array("returnCode" => '3', "message" => "Session expired", "valid" => false);
    	}
    
    	return array(
        	"returnCode" => '1',
        	"message"    => "Valid session",
        	"valid"	     => true
    	);
     }
}

function requestProcessor($request)
{
  echo "received request".PHP_EOL;
  //var_dump($request);
  if(!isset($request['type'])) {
    return "ERROR: unsupported message type";
  }
  switch ($request['type']) {
    case "login":
        return doLogin($request['username'],$request['password']);
    case "validate_session":
        return validateSession($request['sessionId']);
    case "registration":
        return doRegister($request['username'],$request['password'],$request['password2']);
    case "insertData":
	insertData($request);
  }
  return array("returnCode" => '0', 'message'=>"Server received request and processed");
}

$server = new rabbitMQServer("testRabbitMQ.ini","testServer");

echo "testRabbitMQServer BEGIN".PHP_EOL;
$server->process_requests('requestProcessor');
echo "testRabbitMQServer END".PHP_EOL;
exit();
?>
