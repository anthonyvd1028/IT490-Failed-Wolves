#!/usr/bin/php
<?php
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');

function pushChanges($req)
{
	global $QA;
	$cluster = $req['cluster'];
	try {
		$push = array();
		$push['type'] = "pull";
		$push['path'] = "~/.ssh/";

		switch ($req['cluster'])
		{
			case 'QA':
				$QA->publish($push);
				break;
			case 'Prod':

				break;
		}

		return array('message' => "Changes have been pulled by $cluster");	
	} catch (Exception $e) {
		return array('message' => "There was an error pushing the changes to $cluster");
	}	
}

function changeStatus($req)
{
	$status = $req['status'];
	$version = $req['version'];
	$cluster = $req['cluster'];

	try {
		$mydb = new mysqli('127.0.0.1' , 'admin' , 'AdminPass123!' , 'IT_490');
        	if ($mydb->connect_errno != 0) {
         		echo "Failed to connect to database: " . $mydb->connect_error . PHP_EOL;
                	exit(0);
		}
	
		if ($version === "Latest")
		{	
			$query = "SELECT * FROM $cluster ORDER BY VersionNumber DESC;";
        		$results = $mydb->query($query);
			$results = $results->fetch_assoc();
			if ($mydb->errno != 0) {
       				return array('message' => "There was an SQL error");
       				exit(0);
			}
			$version = $results['VersionNumber'];
		} 

		$query = "UPDATE $cluster SET Status = '$status' WHERE VersionNumber = $version;";
        	$results = $mydb->query($query);
		if ($mydb->errno != 0) {
       			return array('message' => "There was an SQL error while changing the status");
       			exit(0);
		}

		return array('message' => "Status was changed successfully");
	} catch (Exception $e) {
		return array('message' => "There was an SQL error while changing the status");
	}		
}

function pullChanges($req)
{
	$path = $req['path'];
	$user = $req['user'];
	$ip = $req['ip'];

	try {
		$mydb = new mysqli('127.0.0.1' , 'admin' , 'AdminPass123!' , 'IT_490');
        	if ($mydb->connect_errno != 0) {
         		echo "Failed to connect to database: " . $mydb->connect_error . PHP_EOL;
                	exit(0);
		}
		// TODO: Change Test to DEV
		$query = "SELECT * FROM Test ORDER BY VersionNumber DESC;";
        	$results = $mydb->query($query);
		$results = $results->fetch_assoc();
		if ($mydb->errno != 0) {
       			return array('message' => "There was an SQL error");
       			exit(0);
		}
		
		if (!(isset($results['VersionNumber'])))
		{
			$version = 1;
		} else {
			$version = $results['VersionNumber'] + 1;
		}
		
		exec("mkdir ~/versions/$version");
		$pull = "scp $user@$ip:$path ~/versions/$version/";
		exec($pull);	

		// TODO: Change Test to DEV
		$query = "INSERT INTO Test (Status, Path) VALUES ('Pending', '~/versions/$version/files.tar.gz');";
        	$results = $mydb->query($query);
		if ($mydb->errno != 0) {
       			return array('message' => "There was an SQL error");
       			exit(0);
		}

		return array('message' => "Changes pulled successfully");
	} catch (Exception $e) {	
		return array('message' => "There was an error pulling the changes");
	}	
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
    case "status":
	return changeStatus($request);
	break;
    case "push":
	return pushChanges($request);
	break;
  }
  return array('message'=>"No valid type found");
}

$server = new rabbitMQServer("deployement.ini","devdeployement");
$QA = new rabbitMQClient("deployement.ini","deployementQA");

echo "testRabbitMQServer BEGIN".PHP_EOL;
$server->process_requests('requestProcessor');
echo "testRabbitMQServer END".PHP_EOL;
exit();
?>
