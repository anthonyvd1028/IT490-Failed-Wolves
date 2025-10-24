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
	if ($parts[0] === "API_KEY_SGO")
	{
		$key = $parts[1];
	}
}

$base_url = "https://api.sportsgameodds.com/v2/events";
$params = [
	"leagueID" => "NFL",
	"oddsAvailable" => "true",
	"week" => "current",
	"markets" => "moneyline,spread,total"
];

$cursor = null;
$weekStart = new DateTime('thursday this week', new DateTimeZone('UTC'));
$weekStart->setTime(0,0,0);

$weekEnd = new DateTime('tuesday next week', new DateTimeZone('UTC'));
$weekEnd->setTime(23,59,59);

$rabbit = [];

do {
	if ($cursor)
	{
		$params['cursor'] = $cursor;
	}

	$url = $base_url . '?' . http_build_query($params);

	$curl = curl_init();
	curl_setopt_array($curl, [
    		CURLOPT_URL => $url,
    		CURLOPT_RETURNTRANSFER => true,
    		CURLOPT_HTTPHEADER => ["X-Api-Key: $key"]
	]);

	$response = curl_exec($curl);
	curl_close($curl);

	if ($response === false)
	{
		die('Curl error: ' . curl_error($curl) . PHP_EOL);
	}

	$data = json_decode($response, true);
	
	foreach ($data['data'] as $event)
	{
		$home = $event['teams']['home']['names']['long'];
        	$away = $event['teams']['away']['names']['long'];
		$start = new DateTime($event['status']['startsAt']);
		
		if ($start >= $weekStart && $start <= $weekEnd)
		{
			$time = $start->format('Y-m-d H:i');

			$return = array(
				'home' => $home,
				'away' => $away,
				'start' => $time,
				'homeML' => array(),
				'awayML' => array(),
				'homeSpread' => array(),
				'awaySpread' => array(),
				'over' => array(),
				'under' => array()
			);
			
			foreach ($event['odds']['points-home-game-ml-home']['byBookmaker'] as $bet => $value)
			{
				if ($bet === 'williamhill')
				{
					continue;
				}

				$return['homeML'][$bet] = $value['odds'];
			}

			foreach ($event['odds']['points-away-game-ml-away']['byBookmaker'] as $bet => $value)
			{
				if ($bet === 'williamhill')
				{
					continue;
				}

				$return['awayML'][$bet] = $value['odds'];				
			}	

			foreach ($event['odds']['points-home-game-sp-home']['byBookmaker'] as $bet => $value)
			{
				$return['homeSpread'][$bet] = array($value['spread'], $value['odds']);
			}
			 			
			foreach ($event['odds']['points-away-game-sp-away']['byBookmaker'] as $bet => $value)
			{
				$return['awaySpread'][$bet] = array($value['spread'], $value['odds']);
			}
			
			foreach ($event['odds']['points-all-game-ou-over']['byBookmaker'] as $bet => $value)
			{
				$return['over'][$bet] = array($value['overUnder'], $value['odds']);
			}
			
			foreach ($event['odds']['points-all-game-ou-under']['byBookmaker'] as $bet => $value)
			{
				$return['under'][$bet] = array($value['overUnder'], $value['odds']);
			}
			//var_dump($return);
			//$rabbitResponse = $client->publish(array('type' => 'insertData', 'data' => $return));
			//echo $rabbitResponse . PHP_EOL;
			$rabbit[] = $return;
		}	
	}
	$cursor = $data['nextCursor'] ?? null;
} while ($cursor);
var_dump($rabbit);
$rabbitResponse = $client->publish(array('type' => 'insertData', 'data' => $rabbit));
?>
