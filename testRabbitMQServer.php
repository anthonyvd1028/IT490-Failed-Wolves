#!/usr/bin/php
<?php
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');
require_once('Email.inc');

function getGamesSportsbook($sportsbook)
{	
	$return = array('events' => array());

       	$mydb = new mysqli('127.0.0.1' , 'admin' , 'AdminPass123!' , 'IT_490');	
	if ($mydb->connect_errno != 0) {
		return array("returnCode" => '1' , "message" => "Database connection failed:" . $mydb->connect_error);
	}

	$query = "SELECT * FROM Odds LEFT JOIN Events ON Events.EventID = Odds.EventID WHERE Events.StartsAt > NOW() AND Odds.Date = CURDATE() AND Odds.Sportsbook = '$sportsbook' ORDER BY Events.EventID, Odds.Sportsbook, Odds.BetType;";
	
	$results = $mydb->query($query);
    	if ($mydb->errno != 0) {
        	echo "failed to execute query:" . PHP_EOL;
        	exit(0);
    	}

	if ($results->num_rows > 0)
	{
		while ($row = $results->fetch_assoc())
		{
			$eventID = $row['EventID'];
			$sportsbook = $row['Sportsbook'];
			$betType = $row['BetType'];
			$odds = $row['Odds'];
			$value = $row['Value'];
			$homeTeam = $row['HomeTeam']; 
			$awayTeam = $row['AwayTeam']; 
			$startsAt = $row['StartsAt'];
			$oddId = $row['OddID'];

			if (!isset($return['events'][$eventID]))
			{
				$return['events'] += array($eventID => array('home' => $homeTeam, 'away' => $awayTeam, 'startsAt' => $startsAt, 'odds' => array()));
			}

			if (!isset($return['events'][$eventID]['odds'][$sportsbook]))
			{
				$return['events'][$eventID]['odds'] += array($sportsbook => array());
			}	
			
			if ($betType == "HomeML") {
				$return['events'][$eventID]['odds'][$sportsbook]['homeML'] = array('value' => $value, 'odds' => $odds, 'oddId' => $oddId);	
			} elseif ($betType == "AwayML") {
				$return['events'][$eventID]['odds'][$sportsbook]['awayML'] = array('value' => $value, 'odds' => $odds, 'oddId' => $oddId);		
			} elseif ($betType == "Over") {
				$return['events'][$eventID]['odds'][$sportsbook]['over'] = array('value' => $value, 'odds' => $odds, 'oddId' => $oddId);	
			} elseif ($betType == "Under") {
				$return['events'][$eventID]['odds'][$sportsbook]['under'] = array('value' => $value, 'odds' => $odds, 'oddId' => $oddId);	
			} elseif ($betType == "HomeSpread") {
				$return['events'][$eventID]['odds'][$sportsbook]['homeSpread'] = array('value' => $value, 'odds' => $odds, 'oddId' => $oddId);	
			} elseif ($betType == "AwaySpread") {
				$return['events'][$eventID]['odds'][$sportsbook]['awaySpread'] = array('value' => $value, 'odds' => $odds, 'oddId' => $oddId);	
			} 	

		}
	}

	return array('games' => $return);
}

function getGames()
{	
	$return = array('events' => array());

       	$mydb = new mysqli('127.0.0.1' , 'admin' , 'AdminPass123!' , 'IT_490');	
	if ($mydb->connect_errno != 0) {
		return array("returnCode" => '1' , "message" => "Database connection failed:" . $mydb->connect_error);
	}

	$query = "SELECT * FROM Odds LEFT JOIN Events ON Events.EventID = Odds.EventID WHERE Events.StartsAt > NOW() AND Odds.Date = CURDATE() ORDER BY Events.EventID, Odds.Sportsbook, Odds.BetType;";
	
	$results = $mydb->query($query);
    	if ($mydb->errno != 0) {
        	echo "failed to execute query:" . PHP_EOL;
        	exit(0);
    	}

	if ($results->num_rows > 0)
	{
		while ($row = $results->fetch_assoc())
		{
			$eventID = $row['EventID'];
			$sportsbook = $row['Sportsbook'];
			$betType = $row['BetType'];
			$odds = $row['Odds'];
			$value = $row['Value'];
			$homeTeam = $row['HomeTeam']; 
			$awayTeam = $row['AwayTeam']; 
			$startsAt = $row['StartsAt'];
			$oddId = $row['OddID'];

			if (!isset($return['events'][$eventID]))
			{
				$return['events'] += array($eventID => array('home' => $homeTeam, 'away' => $awayTeam, 'startsAt' => $startsAt, 'odds' => array()));
			}

			if (!isset($return['events'][$eventID]['odds'][$sportsbook]))
			{
				$return['events'][$eventID]['odds'] += array($sportsbook => array());
			}	
			
			if ($betType == "HomeML") {
				$return['events'][$eventID]['odds'][$sportsbook]['homeML'] = array('value' => $value, 'odds' => $odds, 'oddId' => $oddId);	
			} elseif ($betType == "AwayML") {
				$return['events'][$eventID]['odds'][$sportsbook]['awayML'] = array('value' => $value, 'odds' => $odds, 'oddId' => $oddId);		
			} elseif ($betType == "Over") {
				$return['events'][$eventID]['odds'][$sportsbook]['over'] = array('value' => $value, 'odds' => $odds, 'oddId' => $oddId);	
			} elseif ($betType == "Under") {
				$return['events'][$eventID]['odds'][$sportsbook]['under'] = array('value' => $value, 'odds' => $odds, 'oddId' => $oddId);	
			} elseif ($betType == "HomeSpread") {
				$return['events'][$eventID]['odds'][$sportsbook]['homeSpread'] = array('value' => $value, 'odds' => $odds, 'oddId' => $oddId);	
			} elseif ($betType == "AwaySpread") {
				$return['events'][$eventID]['odds'][$sportsbook]['awaySpread'] = array('value' => $value, 'odds' => $odds, 'oddId' => $oddId);	
			} 	

		}
	}

	return array('games' => $return);
}

function insertEvent($response)
{
	// Connect to database
       	$mydb = new mysqli('127.0.0.1' , 'admin' , 'AdminPass123!' , 'IT_490');
	if ($mydb->connect_errno != 0) {
		return array("returnCode" => '1' , "message" => "Database connection failed:" . $mydb->connect_error);
	}

	$home = $response['data']['home'] ?? null;
	$away = $response['data']['away'] ?? null;
	$homeScore = $response['data']['homeScore'] ?? null;
	$awayScore = $response['data']['awayScore'] ?? null;

	echo $home . $away . PHP_EOL;
	$query = "SELECT * FROM Events WHERE HomeTeam = '$home' AND AwayTeam = '$away';";
	echo $query . PHP_EOL;	
	$results = $mydb->query($query);
    	if ($mydb->errno != 0) {
        	echo "failed to execute query:" . PHP_EOL;
        	exit(0);
    	}
	
	 $eventID = $results->fetch_assoc()['EventID'];
	if (empty($eventID))
	{
		exit;
	}

	// Insert event result into EventResults
	$query = "INSERT INTO EventResults (EventID, HomeScore, AwayScore) VALUES ('$eventID', '$homeScore', '$awayScore');";
	echo $query . PHP_EOL; 
	$results = $mydb->query($query);

	if ($mydb->errno != 0) {
		echo "Failed to execute insert query:" . $mydb->error . PHP_EOL;
		exit(0);
	}
	echo "Inserted event result successfully!" . PHP_EOL;
	return array("returnCode" => '0' , "message" => "Event results inserted successfully");


}

function insertData($response)
{
	var_dump($response);
	$mydb = new mysqli('127.0.0.1' , 'admin' , 'AdminPass123!' , 'IT_490');
    	if ($mydb->connect_errno != 0) {
        	echo "Failed to connect to database: " . $mydb->connect_error . PHP_EOL;
        	exit(0);
    	}

	foreach ($response['data'] as $game)
	{
	$home = $game['home'];
	$away = $game['away'];
	$start = $game['start'];
	
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
	
	foreach ($game['homeML'] as $book => $value)
	{
		$query = "INSERT INTO Odds (EventID, Sportsbook, BetType, Value, Odds) VALUES ('$eventID', '$book', 'HomeML', NULL, '$value');";	
	     	echo $query . PHP_EOL;

		$results = $mydb->query($query);
    		if ($mydb->errno != 0) {
        		echo "failed to execute query:" . PHP_EOL;
        		exit(0);
    		}
	}

	foreach ($game['awayML'] as $book => $value)
	{
		$query = "INSERT INTO Odds (EventID, Sportsbook, BetType, Value, Odds) VALUES ('$eventID', '$book', 'AwayML', NULL, '$value');";	
	     	echo $query . PHP_EOL;

		$results = $mydb->query($query);
    		if ($mydb->errno != 0) {
        		echo "failed to execute query:" . PHP_EOL;
        		exit(0);
    		}
	}

	foreach ($game['homeSpread'] as $book => $value)
	{
		$query = "INSERT INTO Odds (EventID, Sportsbook, BetType, Value, Odds) VALUES ('$eventID', '$book', 'HomeSpread', '$value[0]', '$value[1]');";	
	     	echo $query . PHP_EOL;

		$results = $mydb->query($query);
    		if ($mydb->errno != 0) {
        		echo "failed to execute query:" . PHP_EOL;
        		exit(0);
    		}
	}

	foreach ($game['awaySpread'] as $book => $value)
	{
		$query = "INSERT INTO Odds (EventID, Sportsbook, BetType, Value, Odds) VALUES ('$eventID', '$book', 'AwaySpread', '$value[0]', '$value[1]');";	
	     	echo $query . PHP_EOL;

		$results = $mydb->query($query);
    		if ($mydb->errno != 0) {
        		echo "failed to execute query:" . PHP_EOL;
        		exit(0);
    		}
	}

	foreach ($game['over'] as $book => $value)
	{
		$query = "INSERT INTO Odds (EventID, Sportsbook, BetType, Value, Odds) VALUES ('$eventID', '$book', 'Over', '$value[0]', '$value[1]');";	
	     	echo $query . PHP_EOL;

		$results = $mydb->query($query);
    		if ($mydb->errno != 0) {
        		echo "failed to execute query:" . PHP_EOL;
        		exit(0);
    		}
	}

	foreach ($game['under'] as $book => $value)
	{
		$query = "INSERT INTO Odds (EventID, Sportsbook, BetType, Value, Odds) VALUES ('$eventID', '$book', 'Under', '$value[0]', '$value[1]');";	
	     	echo $query . PHP_EOL;

		$results = $mydb->query($query);
    		if ($mydb->errno != 0) {
        		echo "failed to execute query:" . PHP_EOL;
        		exit(0);
    		}
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

function doRegister($username,$password,$password2, $email)
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

    $insert = "INSERT INTO users (username, password, email) VALUES ('" . $username . "', '" . $password . "', '" . $email . "');";
    $insertResp = $mydb->query($insert);
    if ($mydb->errno != 0) {
        echo "failed to execute insert query:" . PHP_EOL;
        exit(0);
    }
    if ($insertResp === TRUE) {   
	$query = "SELECT id FROM users WHERE username = '$username';";
	$insertResp = $mydb->query($query);
	$insertResp = $insertResp->fetch_assoc();
	$ID = $insertResp['id'];
	$query = "INSERT INTO Portfolio (UserID) VALUES ('$ID');";
	$insertResp = $mydb->query($query);

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

function sendBet($bet){
    $mydb = new mysqli('127.0.0.1' , 'admin' , 'AdminPass123!' , 'IT_490');
    if ($mydb->connect_errno != 0) {
        echo "Failed to connect to database: " . $mydb->connect_error . PHP_EOL;
	exit(0);

    }
    echo "Succesfully connected to database".PHP_EOL;

    $sessionId = $bet['sessionId'];
    $wager = $bet['wager'];

    $query = "SELECT * FROM sessions LEFT JOIN users ON sessions.username = users.username WHERE sessions.session_token = '$sessionId';";
    echo $query . PHP_EOL;
    $results = $mydb->query($query);
    $results = $results->fetch_assoc();

    $userId = $results['id'];

    $betId = uniqid("BET_", true);
    
    foreach ($bet['bet'] as $leg) {
	    $query = "INSERT INTO Bets (BetID, UserId, OddID, Wager) VALUES ('$betId', '$userId', '$leg', '$wager');";
   	    echo $query . PHP_EOL;
	    $results = $mydb->query($query);
    }
    
    $query = "UPDATE Portfolio SET BetsPlaced = BetsPlaced + 1, Payout = Payout - $wager WHERE UserID = '$userId';";
    $results = $mydb->query($query);
    return array('message' => 'Bet Placed');
}

function insertWatchlist($request)
{
   $mydb = new mysqli('127.0.0.1' , 'admin' , 'AdminPass123!' , 'IT_490');
    if ($mydb->connect_errno != 0) {
        echo "Failed to connect to database: " . $mydb->connect_error . PHP_EOL;
        exit(0);
    }
   echo "Succesfully connected to database".PHP_EOL;
   $query = "SELECT * FROM users LEFT JOIN sessions ON user.username = sessions.username WHERE sessions.session_token = '$sessionId';";
   $results = $mydb->query($query);
   $results = $results->fetch_assoc();

   $userId = $response['ID'];
   $eventId = $request['eventId'];
   $oddId = $request['oddId'];
   $sessionId = $request['sessionId'];

   $query = "INSERT INTO watchlist (UserID, EventID, OddID) VALUES ($userId, $eventId, $oddId);";
   $response = $mydb->query($query);
   if ($mydb->errno != 0) {
       echo "failed to execute query:" . PHP_EOL;
       exit(0);
    }

   return array('message' => 'Added to watchlist');
}

function getPortfolio($ID)
{
    	$mydb = new mysqli('127.0.0.1' , 'admin' , 'AdminPass123!' , 'IT_490');
    	if ($mydb->connect_errno != 0) {
        	echo "Failed to connect to database: " . $mydb->connect_error . PHP_EOL;
		exit(0);
    	}
    	echo "Succesfully connected to database".PHP_EOL;
	
	$query = "SELECT * FROM Portfolio LEFT JOIN users ON users.id = Portfolio.UserID LEFT JOIN sessions ON sessions.username = users.username WHERE sessions.session_token = '$ID';";
	$results = $mydb->query($query);
	$results = $results->fetch_assoc();
	//var_dump($results);
	$query = "SELECT Bets.BetID, MIN(Win) AS Win FROM Portfolio LEFT JOIN users ON users.ID = Portfolio.UserID LEFT JOIN sessions ON sessions.username = users.username RIGHT JOIN Bets ON Portfolio.UserID = Bets.UserId WHERE sessions.session_token = '$ID' GROUP BY Bets.BetID;";

	$bets = [];

	$results2 = $mydb->query($query);	
	if ($results2->num_rows > 0)
	{
		while ($row = $results2->fetch_assoc())
		{
			//var_dump($row);
			if ($row['Win'] > 0)
			{
				$status = "Won";
			} elseif ($row['Win'] < 0) {
				$status = "Loss";
			} else {
				$status = "Pending";
			}

			$descriptions = [];
			$wager = null;
			$betID = $row['BetID'];

			$query = "SELECT * FROM Bets LEFT JOIN Odds ON Odds.OddId = Bets.OddId LEFT JOIN Events ON Events.EventID = Odds.EventID WHERE Bets.BetID = '$betID';";
			$results3 = $mydb->query($query);
			while ($line = $results3->fetch_assoc())
			{
				if ($line['BetType'] == "Under") {
					$msg = $line['AwayTeam'] . " @ " . $line['HomeTeam'] . " Under " . $line['Value'];
				} elseif ($line['BetType'] == "Over") {
					$msg = $line['AwayTeam'] . " @ " . $line['HomeTeam'] . " Over " . $line['Value'];
				} elseif ($line['BetType'] == "AwaySpread") {
					$msg = $line['AwayTeam'] . " " . $line['Value'];
				} elseif ($line['BetType'] == "HomeSpread") {
					$msg = $line['HomeTeam'] . " " . $line['Value'];
				} elseif ($line['BetType'] == "HomeML") {
					$msg = $line['HomeTeam'] . " ML";
				} elseif ($line['BetType'] == "AwayML") {
					$msg = $line['AwayTeam'] . " ML";
				}
				$wager = $line['Wager'];
				$descriptions[] = $msg;	
			}

			$bets[] = array('wager' => $wager, 'betId' => $betID, 'status' => $status, 'description' => implode("<br>", $descriptions));
		
		}
	}
	return array('stats' => array('payout' => $results['Payout'], 'betsWon' => $results['BetsWon'], 'betsLost' => $results['BetsLost'], 'betsPlaced' => $results['BetsPlaced'], 'created' => $results['created_at'], 'betHistory' => $bets));
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
        return doRegister($request['username'],$request['password'],$request['password2'],$request['email']);
    case "insertData":
	return insertData($request);
    case "insertEvent":
	return insertEvent($request);
    case "getOdds":
	 return getGames();
    case "getOddsSportsbook":
	 return getGamesSportsbook($request['sportsbook']);
    case "sendBet":
	  return sendBet($request);
    case "insertWatchlist":
         return insertWatchlist($request);
    case "getPortfolio":
	  return getPortfolio($request['sessionId']);
  }
  return array("returnCode" => '0', 'message'=>"Server received request and processed");
}

$server = new rabbitMQServer("testRabbitMQ.ini","testServer");

echo "testRabbitMQServer BEGIN".PHP_EOL;
$server->process_requests('requestProcessor');
echo "testRabbitMQServer END".PHP_EOL;
exit();
?>
