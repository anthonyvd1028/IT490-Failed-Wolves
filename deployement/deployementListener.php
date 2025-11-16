#!/usr/bin/php
<?php
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');

function pullChanges($req)
{
	var_dump($req);


	return array('message' => "Changes have been pushed", 'request' => $req);
}

function requestProcessor($request)
{
  echo "received request".PHP_EOL;
  if(!isset($request['type'])) {
    return "ERROR: unsupported message type";
  }
  switch ($request['type']) {
    case "pull":
        return pullChanges($request);
	break;
  }
  return array('message'=>"No valid type found");
}

$server = new rabbitMQServer("deployement.ini","devdeployement");

echo "testRabbitMQServer BEGIN".PHP_EOL;
$server->process_requests('requestProcessor');
echo "testRabbitMQServer END".PHP_EOL;
exit();
?>
