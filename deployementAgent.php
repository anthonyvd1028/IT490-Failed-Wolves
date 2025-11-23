#!/usr/bin/php
<?php
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');

function pullChanges($req)
{
	$newDir = $req['newDir'];
	$oldDir = $req['oldDir'];
	$path = $req['path'];
	exec("cp -r $oldDir $newDir");
       	$pull = "scp avd8@172.29.169.30:$path $newDir";
	exec($pull);
	exec("tar -xzf {$newDir}files.tar.gz -C $newDir");	
	echo "Copied" . PHP_EOL;

	$hostname = exec("hostname");
	$type = explode("-", $hostname)[1];
	echo "Type: $type" . PHP_EOL;
	foreach ($req['nodes'] as $node)
	{
		if ($node == $type && $node == 'API')
		{
			$command = "bash {$newDir}setup/setup-api.sh $newDir";
			echo $command . PHP_EOL;
			exec($command);
			echo "Restarted $node" . PHP_EOL;
		} elseif ($node == $type && $node == 'WEB') {
			$command = "bash {$newDir}setup/setup-web.sh $newDir";
			echo $command . PHP_EOL;
			exec($command);
			echo "Restarted $node" . PHP_EOL;
		}

		#TODO: Add if statements for web, db, and agent
	}
	
	return;
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

$hostname = exec("hostname");
$type = explode("-", $hostname)[1];
switch ($type)
{	
	case 'API':
		$server = new rabbitMQServer("deployement.ini","deployementQA-API");
		break;
	case 'WEB':
		$server = new rabbitMQServer("deployement.ini","deployementQA-WEB");
		break;
	case 'DB':
		$server = new rabbitMQServer("deployement.ini","deployementQA-DB");
		break;
}

echo "testRabbitMQServer BEGIN".PHP_EOL;
$server->process_requests('requestProcessor');
echo "testRabbitMQServer END".PHP_EOL;
exit();
?>
