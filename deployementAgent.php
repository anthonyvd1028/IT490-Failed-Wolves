#!/usr/bin/php
<?php
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');

function switchDir($req)
{
	var_dump($req);
	$newDir = $req['dir'];
	$hostname = exec("hostname");
        $type = explode("-", $hostname)[1];

	switch ($type)
	{
		case "API":
			$command = "bash {$newDir}setup/setup-api.sh $newDir";
		        echo $command . PHP_EOL;
        		exec($command);
        		echo "Restarted $type" . PHP_EOL;
			break;
		case "WEB":
			$command = "bash {$newDir}setup/setup-web.sh $newDir";
		        echo $command . PHP_EOL;
        		exec($command);
        		echo "Restarted $type" . PHP_EOL;
			break;
		case "DB":
			$command = "bash {$newDir}setup/setup-db.sh $newDir";
		        echo $command . PHP_EOL;
        		exec($command);
        		echo "Restarted $type" . PHP_EOL;
			break;		
	}
	return;
}

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
		} elseif ($node == $type && $node == 'DB') {
                        $command = "bash {$newDir}setup/setup-db.sh $newDir";
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
    case "switch":
	return switchDir($request);
	break;
  }
  return array('message'=>"No valid type found");
}

$hostname = exec("hostname");
$type = explode("-", $hostname)[1];
$cluster = explode("-", $hostname)[0];
switch ($type)
{	
	case 'API':
		if ($cluster == "QA")
		{
			$server = new rabbitMQServer("deployement.ini","deployementQA-API");
		} elseif ($cluster == "PROD") {
			$server = new rabbitMQServer("deployement.ini","deployementPROD-API");
		}	
		break;
	case 'WEB':
		if ($cluster == "QA")
		{
			$server = new rabbitMQServer("deployement.ini","deployementQA-WEB");
		} elseif ($cluster == "PROD") {
			$server = new rabbitMQServer("deployement.ini","deployementPROD-WEB");
		}	
		break;
	case 'DB':
		if ($cluster == "QA")
		{
			$server = new rabbitMQServer("deployement.ini","deployementQA-DB");
		} elseif ($cluster == "PROD") {
			$server = new rabbitMQServer("deployement.ini","deployementPROD-DB");
		}	
		break;
}

echo "testRabbitMQServer BEGIN".PHP_EOL;
$server->process_requests('requestProcessor');
echo "testRabbitMQServer END".PHP_EOL;
exit();
?>
