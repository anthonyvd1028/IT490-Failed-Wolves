#!/usr/bin/php
<?php
require_once('../path.inc');
require_once('../get_host_info.inc');
require_once('../rabbitMQLib.inc');

$client = new rabbitMQClient('../testRabbitMQ.ini', 'testServer');
$env = __DIR__ . '/.env';

if (!file_exists($env))
{
        die(".env file is not found." . PHP_EOL);
}

$lines = file($env, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

foreach ($lines as $line)
{
        if (strpos(trim($line), "#") === 0) continue;
        $parts = explode('=', $line, 2);
        if ($parts[0] === "API_KEY_NFL")
        {
                $key = $parts[1];
        }
}

$date = new DateTime();

for ($i = 1; $i <= 6; $i++)
{
	$day = clone $date;
	$day->modify("-{$i} day");
	$formattedDate = $day->format("Ymd");
	
	$curl = curl_init();
	$url = "https://nfl-api-data.p.rapidapi.com/nfl-scoreboard-day?day=" . $formattedDate;
	
	curl_setopt_array($curl, [
    		CURLOPT_URL => $url,
    		CURLOPT_RETURNTRANSFER => true,
    		CURLOPT_ENCODING => "",
    		CURLOPT_MAXREDIRS => 10,
    		CURLOPT_TIMEOUT => 30,
    		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    		CURLOPT_CUSTOMREQUEST => "GET",
    		CURLOPT_HTTPHEADER => [
        		"x-rapidapi-host: nfl-api-data.p.rapidapi.com",
        		"x-rapidapi-key: " . $key
    		],
	]);

	$response = curl_exec($curl);
	$err = curl_error($curl);

	curl_close($curl);

	if ($err) {
    		echo "cURL Error #:" . $err . PHP_EOL;
	}

	$result = json_decode($response);

	if (empty($result->events))
	{
		continue;
	}	
	
	foreach ($result->events as $event)
	{
		$competition = $event->competitions[0];
		$teams = $competition->competitors;
		$startsAt = $competition->date;

		$home = null;
		$away = null;
		$homeScore = null;
		$awayScore = null;

		foreach ($teams as $team)
		{
			if ($team->homeAway === 'home')
			{
				$home = $team->team->displayName;
				$homeScore = $team->score;
			} else {
				$away = $team->team->displayName;
				$awayScore = $team->score;
			}
		}
		
		$data = array('home' => $home, 'away' => $away, 'homeScore' => $homeScore, 'awayScore' => $awayScore, 'startsAt' => $startsAt);
		$rabbitResponse = $client->publish(array('type' => 'insertEvent', 'data' => $data));
	}
}
