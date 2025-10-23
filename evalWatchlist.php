#!/usr/bin/php
<?php 
$mydb = new mysqli('127.0.0.1', 'admin', 'AdminPass123!', 'IT_490');
if ($mydb->connect_errno != 0) {
	echo $mydb->connect_error . PHP_EOL;
	exit(0);
}

$query = "SELECT * FROM Watchlist LEFT JOIN Odds ON Odds.OddID = Watchlist.OddID LEFT JOIN users ON users.id = Watchlist.UserID;";
$results = $mydb->query($query);
if ($mydb->errno != 0) {
        echo "Failed to execute query" . PHP_EOL;
	exit(0);
}

if ($results->num_rows > 0) 
{
	while ($row = $results->fetch_assoc())
	{
		$odd = $row['Odds'];
		$eventId = $row['EventID'];
		$sportsbook = $row['Sportsbook'];
		$type = $row['BetType'];

		$query = "SELECT * FROM Odds WHERE EventID = $eventId AND Sportsbook = '$sportsbook' AND BetType = '$type' ORDER BY OddID DESC;";
		$results2 = $mydb->query($query);
		$results2 = $results2->fetch_assoc();

		$newOdd = $results2['Odds'];

		if ($odd[0] === "+")
                {
                        $oldOddDec = 1 + (float)$odd/100;
                } elseif ($odd[0] === "-") {
                	$oldOddDec = 1 + 100/abs((float)$odd);
      		}

		if ($newOdd[0] === "+")
                {
                        $newOddDec = 1 + (float)$newOdd/100;
                } elseif ($newOdd[0] === "-") {
                	$newOddDec = 1 + 100/abs((float)$newOdd);
		}

		if ($oldOddDec > $newOddDec)
		{
			echo "Odds Havent Gotten Better" . PHP_EOL;
		} elseif ($oldOddDec < $newOddDec) {
			echo "Odds Gotten Better" . PHP_EOL;
		} else {
			echo "Odds Havent Changed" . PHP_EOL;
		}
	}
}
?>
