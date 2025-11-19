#!/usr/bin/php
<?php
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');

function bundleFiles($args)
{
	if (!(isset($args['-o'])))
	{
		echo "Please specifiy the files to push" . PHP_EOL;
		exit();
	}

	$files = implode(' ', $args['-f']);
	
	if (file_exists('files.tar.gz'))
	{
		echo 'file exists' . PHP_EOL;
		unlink('files.tar.gz');
	}
		
	exec("tar -czf files.tar.gz $files");

	return __DIR__ . '/files.tar.gz';
}

function sortArgs($argc, $argv)
{
	$argsSorted = array();
	$currentOperand = NULL;
	
	for ($i = 1; $i < $argc; $i++)
	{
		if (str_starts_with($argv[$i], '-'))
		{
			$currentOperand = $argv[$i];
			$argsSorted[$currentOperand] = array();
			continue;
		}

		if ($currentOperand == NULL)
		{
			continue;
		} else {
			array_push($argsSorted[$currentOperand], $argv[$i]);
		}		
	}

	return $argsSorted;
}

$client = new rabbitMQClient("deployement.ini","devdeployement");
$args = sortArgs($argc, $argv);

if (!(isset($args['-o'])))
{
	echo "Please specifiy an operation mode" . PHP_EOL;
	exit();
}

$request = array();

switch ($args['-o'][0])
{
	case 'pull':
		$request['type'] = "pull";
		$path = bundleFiles($args);
		$request['path'] = $path;
		$request['ip'] = exec("ip a | grep 172 | awk '{print $2}' | cut -d '/' -f1");
		$request['user'] = exec("whoami");
		break;
	case 'status':
		$request['type'] = "status";
		$request['cluster'] = $args['-c'][0];
		$request['version'] = $args['-v'][0];
		$request['status'] = $args['-s'][0];
		break;
	case 'push':
		$request['type'] = "push";
		$request['cluster'] = $args['-c'][0];
		$request['version'] = $args['-v'][0];
		break;
}

$response = $client->send_request($request);

print_r($response);
echo "\n\n";

echo $argv[0]." END".PHP_EOL;

