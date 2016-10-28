<?PHP
include_once('config.inc.php');
require_once 'datastore_config.php';
require_once 'GoogleDatastoreService.php';
use google\appengine\api\users\User;
use google\appengine\api\users\UserService;

ini_set('memory_limit', '768M');
set_time_limit(600);


$DS = new GoogleDatastoreService($google_api_config);
move_push_task();


function move_push_task(){
	global $DS;
	global $db;
	
	
	$results = $DS->queryEntitiesWithGQL("select task_id from push_task order by task_id desc limit 5 offset 0");
	//echo json_encode($results);
	$maxTaskId = 0;
	if(count($results) > 0){
		$entity = $results[0];
		$maxTaskId = intval($entity['entity']['properties']['task_id']['integerValue']);
		
	}
	echo 'maxTaskId = '.$maxTaskId."<br/>\n";
	
	$entities = array();
	$batchSize = 200;
	$startIndex = 0;
	
	while(true){
		$query = "Select * from push_task where task_id > ".$maxTaskId." order by task_id limit ".$startIndex.", ".$batchSize." ";
		$startIndex += $batchSize;	
		$rs = $db->Execute($query);
		$lastTaskId = $maxTaskId;
		while($row = $rs->FetchRow()){
			$kvs = array();
			$kvs[] = array('key' => 'task_id', 'value' => $row['task_id'], 'type' => PropertyType::Integer);
			$kvs[] = array('key' => 'app_id', 'value' => $row['app_id'], 'type' => PropertyType::String);
			$kvs[] = array('key' => 'OS', 'value' => $row['OS'], 'type' => PropertyType::String);
			$kvs[] = array('key' => 'for_development', 'value' => ($row['for_development'] == 1), 'type' => PropertyType::Boolean);
			$kvs[] = array('key' => 'IP', 'value' => $row['IP'], 'type' => PropertyType::String);
			$kvs[] = array('key' => 'message', 'value' => $row['message'], 'type' => PropertyType::String);
			$kvs[] = array('key' => 'all_parameters', 'value' => $row['all_parameters'], 'type' => PropertyType::String, 'indexed' => false);
			$kvs[] = array('key' => 'time', 'value' => $row['time'], 'type' => PropertyType::DateType);
			$kvs[] = array('key' => 'success', 'value' => ($row['success'] == 1), 'type' => PropertyType::Boolean);
			$kvs[] = array('key' => 'sent_device', 'value' => $row['sent_device'], 'type' => PropertyType::Integer);
			$kvs[] = array('key' => 'tester_account', 'value' => $row['tester_account'], 'type' => PropertyType::String);
			$entity = $DS->buildEntity('push_task', $kvs);	
			$entities[] = $entity;
			$lastTaskId = $row['task_id'];
			
		}
		if(count($entities) == 0){
			syslog(LOG_DEBUG, "no entities, startIndex = ".$startIndex);
			break;	
		}
		$ids = $DS->saveEntities($entities, true);
		syslog(LOG_DEBUG, "save entities, startIndex = ".$startIndex);
		$entities = null;
		echo "lastTaskId = ".$lastTaskId."<br/>\n";
	}
	
	$db->Close();
	
	
}


function move_device(){
	global $DS;
	global $db;
	$entities = array();
	$batchSize = 200;
	$startIndex = 0;
	while(true){
		$query = "Select * from device where update_time > '2015-08-02 14:42:12' order by update_time limit ".$startIndex.", ".$batchSize." ";
		$startIndex += $batchSize;
		$rs = $db->Execute($query);
		while($row = $rs->FetchRow()){
			$kvs = array();
			$kvs[] = array('key' => 'app_id', 'value' => $row['app_id'], 'type' => PropertyType::String);
			$kvs[] = array('key' => 'OS', 'value' => $row['OS'], 'type' => PropertyType::String);
			$kvs[] = array('key' => 'token_id', 'value' => $row['token_id'], 'type' => PropertyType::String);
			$kvs[] = array('key' => 'user_id', 'value' => $row['user_id'], 'type' => PropertyType::String);
			$kvs[] = array('key' => 'update_time', 'value' => $row['update_time'], 'type' => PropertyType::DateType);
			$kvs[] = array('key' => 'is_development', 'value' => ($row['is_development'] == 1), 'type' => PropertyType::Boolean);
			$entity = $DS->buildEntity('device', $kvs, $row['app_id'].';'.$row['token_id']);	
			$entities[] = $entity;
		}
		if(count($entities) == 0){
			syslog(LOG_DEBUG, "no entities, startIndex = ".$startIndex);
			echo "No records to move<br/>\n";
			break;	
		}
		$ids = $DS->saveEntities($entities, false);
		syslog(LOG_DEBUG, "save entities, startIndex = ".$startIndex);
		$entities = null;
		//echo json_encode($ids);
	}
}

?>