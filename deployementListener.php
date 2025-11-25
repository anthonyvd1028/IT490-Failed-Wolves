#!/usr/bin/php
<?php
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');

function rollback($req)
{
	global $QA;
        $cluster = $req['cluster'];

        $mydb = new mysqli('127.0.0.1' , 'admin' , 'AdminPass123!' , 'IT_490');
        if ($mydb->connect_errno != 0) {
                echo "Failed to connect to database: " . $mydb->connect_error . PHP_EOL;
                exit(0);
        }

        $query = "SELECT * FROM $cluster WHERE Status = 'Good' ORDER BY VersionNumber DESC;";
        $results = $mydb->query($query);
        $results = $results->fetch_assoc();
        if ($mydb->errno != 0) {
                return array('message' => "There was an SQL error");
                exit(0);
        }

        $newDir = $results['Path'];

        $push['type'] = "switch";
        $push['dir'] = $newDir;

        switch ($cluster)
        {
                case 'QA':
                        $QA->publish($push);
			break;
		#TODO: Add case for Prod
	}

        return array('message' => "Version has been switched");
}

function switchVersion($req)
{
	global $QA;
	$version = $req['version'];
	$cluster = $req['cluster'];

	$mydb = new mysqli('127.0.0.1' , 'admin' , 'AdminPass123!' , 'IT_490');
        if ($mydb->connect_errno != 0) {
                echo "Failed to connect to database: " . $mydb->connect_error . PHP_EOL;
                exit(0);
        }

	$query = "SELECT * FROM $cluster WHERE VersionNumber = $version;";
	$results = $mydb->query($query);
	$results = $results->fetch_assoc();
        if ($mydb->errno != 0) {
        	return array('message' => "There was an SQL error");
                exit(0);
        }

	$newDir = $results['Path'];

	$push['type'] = "switch";
	$push['dir'] = $newDir;

	switch ($cluster)
	{
		case 'QA':
			$QA->publish($push);	
			break;
	}

	return array('message' => "Version has been switched");
}

function pushChanges($req)
{
	$mydb = new mysqli('127.0.0.1' , 'admin' , 'AdminPass123!' , 'IT_490');
        if ($mydb->connect_errno != 0) {
                echo "Failed to connect to database: " . $mydb->connect_error . PHP_EOL;
        	exit(0);
 	}

	global $QA;
	$cluster = $req['cluster'];
	try {
		$push = array();
		$push['type'] = "pull";
		$push['nodes'] = $req['nodes'];

		switch ($req['cluster'])
		{
			case 'QA':
				$query = "SELECT * FROM Dev WHERE Status = 'Good' ORDER BY VersionNumber DESC;";
        			$results = $mydb->query($query);
				$results = $results->fetch_assoc();
				if ($mydb->errno != 0) {
       					return array('message' => "There was an SQL error");
       					exit(0);
				}
				$path = $results['Path'];
					
				$query = "SELECT * FROM QA ORDER BY VersionNumber DESC;";
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

				$newDir = "~/versions/$version/";			
				$query = "INSERT INTO QA (VersionNumber, Status, Path) VALUES ('$version', 'Pending', '$newDir');";
                                $results = $mydb->query($query);
                                if ($mydb->errno != 0) {
                                        return array('message' => "There was an SQL error");
                                        exit(0);
                                }

				$query = "SELECT * FROM QA WHERE Status = 'Good' ORDER BY VersionNumber DESC;";
                                $results = $mydb->query($query);
                                $results = $results->fetch_assoc();
                                if ($mydb->errno != 0) {
                                        return array('message' => "There was an SQL error");
                                        exit(0);
                                }
				$oldDir = $results['Path'];

				$push['path'] = $path;
				$push['newDir'] = $newDir;
				$push['oldDir'] = $oldDir;
				$QA->publish($push);
				break;
			case 'Prod':
				#TODO: Add Prod logic. Copy QA logic
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
		
		$query = "SELECT * FROM Dev ORDER BY VersionNumber DESC;";
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

		$query = "INSERT INTO Dev (Status, Path) VALUES ('Pending', '~/versions/$version/files.tar.gz');";
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
    case "switch":
	return switchVersion($request);
	break;
    case "rollback":
	return rollback($request);
	break;
  }
  return array('message'=>"No valid type found");
}

$server = new rabbitMQServer("deployement.ini","devdeployement");
$QA = new rabbitMQClient("deployement.ini","deployementQA-WEB");

echo "testRabbitMQServer BEGIN".PHP_EOL;
$server->process_requests('requestProcessor');
echo "testRabbitMQServer END".PHP_EOL;
exit();
?>
