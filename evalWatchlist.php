#!/usr/bin/php
<?php 
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
		var_dump($row);
		$odd = $row['Odds'];
		$eventId = $row['EventID'];
		$sportsbook = $row['Sportsbook'];
		$type = $row['BetType'];

		$query = "SELECT * FROM Odds WHERE EventID = $eventId AND Sportsbook = '$sportsbook' AND BetType = '$type' ORDER BY OddID DESC;";
		$results2 = $mydb->query($query);
		$results2 = $results2->fetch_assoc();
		var_dump($results2);
		if ($odd[0] === "+")
                {
                        $oldOddDec = 1 + (float)$odd/100;
                } elseif ($odd[0] === "-") {
                	$oldOddDec = 1 + 100/abs((float)$odd);
      		}

		if ($results2['Odds'][0] === "+")
                {
                        $newOddDec = 1 + (float)$odd/100;
                } elseif ($results2['Odds'] === "-") {
                	$newOddDec = 1 + 100/abs((float)$odd);
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
