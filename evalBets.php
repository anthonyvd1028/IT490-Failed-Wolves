#!/usr/bin/php
<?php 
require_once('Email.inc');

function handleBet($odds, $wager, $betId, $win, $userId, $email)
{
	$mydb = new mysqli('127.0.0.1', 'admin', 'AdminPass123!', 'IT_490');
	if ($mydb->connect_errno != 0) {
		echo $mydb->connect_error . PHP_EOL;
	}

	if ($win)
	{
		$combinedOdds = null;
		foreach ($odds as $odd)
		{
			if ($odd[0] === "+")
			{
				$oddDec = 1 + (float)$odd/100;
			} elseif ($odd[0] === "-") {
				$oddDec = 1 + 100/abs((float)$odd);
			}
			
			if ($combinedOdds === null)
			{
				$combinedOdds = $oddDec;
			} else {
				$combinedOdds = $combinedOdds * $oddDec;
			}
		}
		
		$payout = round($combinedOdds * $wager, 2);
		$profit = round($combinedOdds * $wager - $wager, 2);
		
		$message = "$betId has won! The bet had a wager of $$wager dollars. The total payout is $$payout dollars. That is a profit of $$profit dollars";
		$subject = "Bet Won";

		$query = "UPDATE Bets SET Win = 1 WHERE BetID = '$betId';";
		$results = $mydb->query($query);
		if ($mydb->errno != 0) {
			echo "Failed to execute query" . PHP_EOL;
			exit(0);
		}

		$query = "UPDATE Portfolio SET BetsWon = BetsWon + 1, Payout = Payout + $payout WHERE UserID = $userId;";
		$results = $mydb->query($query);
		if ($mydb->errno != 0) {
			echo "Failed to execute query" . PHP_EOL;
			exit(0);
		}
		
		sendEmail($email, $subject, $message);	
		echo $message . PHP_EOL;
	} else {
		$message = "$betId has lost! The bet had a wager of $$wager dollars. Better luck next time.";
		$subject = "Bet Lost";

		$query = "UPDATE Bets SET Win = -1 WHERE BetID = '$betId';";
		$results = $mydb->query($query);
		if ($mydb->errno != 0) {
			echo "Failed to execute query" . PHP_EOL;
			exit(0);
		}
		
		$query = "UPDATE Portfolio SET BetsLost = BetsLost + 1 WHERE UserID = $userId;";
		$results = $mydb->query($query);
		if ($mydb->errno != 0) {
			echo "Failed to execute query" . PHP_EOL;
			exit(0);
		}

		sendEmail($email, $subject, $message);	
		echo $message . PHP_EOL;
	}	
}

$mydb = new mysqli('127.0.0.1', 'admin', 'AdminPass123!', 'IT_490');
if ($mydb->connect_errno != 0) {
	echo $mydb->connect_error . PHP_EOL;
}

$query = "SELECT * FROM Bets LEFT JOIN Odds ON Bets.OddID = Odds.OddID LEFT JOIN Events ON Events.EventID = Odds.EventID LEFT JOIN EventResults ON EventResults.EventID = Events.EventID LEFT JOIN users ON users.id = Bets.UserId WHERE EventResults.ResultID IS NOT NULL AND Bets.Win = 0 ORDER BY Bets.BetID;";

$results = $mydb->query($query);
if ($mydb->errno != 0) {
	echo "Failed to execute query" . PHP_EOL;
	exit(0);
}
$betId = null;
$win = true;
$wager = null;
$odds = [];
$userId = null;
$email = null;

if ($results->num_rows > 0)
{
	while ($row = $results->fetch_assoc())
	{
		$homeScore = $row['HomeScore'];
		$awayScore = $row['AwayScore'];
		$value = $row['Value'];
		
		if ($betId === null)
		{
			$betId = $row['BetID'];
			$wager = $row['Wager'];
			$userId = $row['UserId'];
			$email = $row['email'];
		}

		if ($betId != $row['BetID'] )
		{
			//send results
			handleBet($odds, $wager, $betId, $win, $userId, $email);
			$betId = $row['BetID'];
			$wager = $row['Wager'];
			$win = true;
			$odds = [];
			$userId = $row['UserId'];
			$email = $row['email'];
		}

		switch ($row['BetType'])
		{
			case "Over":
				if ($homeScore + $awayScore > $value)
				{
					$odds[] = $row['Odds'];
				} else {
					$win = false;
				}		
				break;
			case "Under":
				if ($homeScore + $awayScore < $value)
				{
					$odds[] = $row['Odds'];
				} else {
					$win = false;
				}	
                                break;
			case "AwaySpread":
				$adjustedValue = (float)$value + $awayScore;
				if ($adjustedValue > $homeScore)
				{
					$odds[] = $row['Odds'];
				} else {
					$win = false;
				}
                                break;
			case "HomeSpread":
				$adjustedValue = (float)$value + $homeScore;
				if ($adjustedValue > $awayScore)
				{
					$odds[] = $row['Odds'];
				} else {
					$win = false;
				}				
				break;
			case "HomeML":
				if ($homeScore > $awayScore)
				{
					$odds[] = $row['Odds'];
				} else {
					$win = false;
				}
				break;
			case "AwayML":
				if ($homeScore < $awayScore)
				{
					$odds[] = $row['Odds'];
				} else {
					$win = false;
				}
				break;				
		}
	}
}
if ($betId !== null)
{
	handleBet($odds, $wager, $betId, $win, $userId, $email);
}
?>
