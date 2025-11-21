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

$server = new rabbitMQServer("deployement.ini","deployementQA");

echo "testRabbitMQServer BEGIN".PHP_EOL;
$server->process_requests('requestProcessor');
echo "testRabbitMQServer END".PHP_EOL;
exit();
?>
