#!/usr/bin/php
<?php
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');

function pushChanges($req)
{
	var_dump($req);




	return array('message' => "Changes have been pushed");
}

function requestProcessor($request)
{
  echo "received request".PHP_EOL;
  if(!isset($request['type'])) {
    return "ERROR: unsupported message type";
  }
  switch ($request['type']) {
    case "push":
        return pushChanges($request);
  }
  return array('message'=>"No valid type found");
}

$server = new rabbitMQServer("deployement.ini","devdeployement");

echo "testRabbitMQServer BEGIN".PHP_EOL;
$server->process_requests('requestProcessor');
echo "testRabbitMQServer END".PHP_EOL;
exit();
?>
