<?PHP
require_once 'datastore_config.php';
require_once 'GoogleDatastoreService.php';
$DS = new GoogleDatastoreService($google_api_config);





$tokens[] = array('token_id' => 'token1', 'user_id' => 'user1');
$tokens[] = array('token_id' => 'token2', 'user_id' => 'user2');
$tokens[] = array('token_id' => 'token3', 'user_id' => 'user3');

$allLogEntities = array();
	
$tokenCount = count($tokens);

for($i = 0; $i < $tokenCount; $i++){
	$kvs = array();
	/*
	if($task_id != null){
		$kvs[] = array('key' => 'task_id', 'value' => $task_id, 'type' => PropertyType::Integer);
	}
	*/
	//$kvs[] = array('key' => 'task_auto_id', 'value' => $task_auto_id, 'type' => PropertyType::Integer);
	$kvs[] = array('key' => 'token_id', 'value' => $tokens[$i]['token_id'], 'type' => PropertyType::String);
	$kvs[] = array('key' => 'user_id', 'value' => $tokens[$i]['user_id'], 'type' => PropertyType::String);
	$allLogEntities[] = $DS->buildEntity('push_log', $kvs);
	
	$allLogEntities[] = $kvs;
}



$kvs = array();
//$kvs[] = array('key' => 'task_id', 'value' => 0, 'type' => PropertyType::Integer);
$kvs[] = array('key' => 'app_id', 'value' => 'GIP_Test', 'type' => PropertyType::String);
$kvs[] = array('key' => 'OS', 'value' => 'iOS', 'type' => PropertyType::String);
$kvs[] = array('key' => 'for_development', 'value' => true, 'type' => PropertyType::Boolean);
$kvs[] = array('key' => 'IP', 'value' => '123.123.123.123', 'type' => PropertyType::String);
$kvs[] = array('key' => 'message', 'value' => 'datastore test', 'type' => PropertyType::String);
$kvs[] = array('key' => 'all_parameters', 'value' => '{just test}', 'type' => PropertyType::String, 'indexed' => false);
$kvs[] = array('key' => 'time', 'value' => date('Y-m-d H:i:s'), 'type' => PropertyType::DateType);
$kvs[] = array('key' => 'success', 'value' => true, 'type' => PropertyType::Boolean);
$kvs[] = array('key' => 'sent_device', 'value' => 0, 'type' => PropertyType::Integer);
$kvs[] = array('key' => 'tester_account', 'value' => '', 'type' => PropertyType::String);
//$kvs[] = array('key' => 'tokens', 'value' => $allLogEntities, 'type' => PropertyType::ListType);
$entity = $DS->buildEntity('push_task', $kvs);	
$entities[] = $entity;

$ids = $DS->saveEntities($entities, true);


$autoId = $ids[0];
echo 'autoId = '.$autoId."<br/>\n";
echo "query it<br/>\n";

$query = "SELECT * FROM push_task where  __key__ = KEY(push_task, ".$autoId.")";
$entities = $DS->queryEntitiesWithGQL($query);
echo "entities = ".json_encode($entities)."<br/>\n";
$updated_entities = array();

$entityObjs = $DS->queryEntityObjectsWithGQL($query);
for($i = 0; $i < count($entityObjs); $i++){
	$entity = $entityObjs[$i];
	$DS->setEntityProperty($entity, 'message', 'datastore test 2', PropertyType::String);
	$properties = $entity->getProperties();	
	echo 'message = '.$properties['message']->getStringValue()."<br/>\n";
	$updated_entities[] = $entity;
}

$DS->saveEntities($updated_entities, false);
//save_log_to_datastore(null, $autoId, $tokens);

function save_log_to_datastore($task_id, $task_auto_id, $tokens){
	global $DS;
	$allLogEntities = array();
	
	$tokenCount = count($tokens);
	
	for($i = 0; $i < $tokenCount; $i++){
		$kvs = array();
		if($task_id != null){
			$kvs[] = array('key' => 'task_id', 'value' => $task_id, 'type' => PropertyType::Integer);
		}
		$kvs[] = array('key' => 'task_auto_id', 'value' => $task_auto_id, 'type' => PropertyType::Integer);
		$kvs[] = array('key' => 'token_id', 'value' => $tokens[$i]['token_id'], 'type' => PropertyType::String);
		$kvs[] = array('key' => 'user_id', 'value' => $tokens[$i]['user_id'], 'type' => PropertyType::String);
		
		$allLogEntities[] = $DS->buildEntity('push_log', $kvs);
	}
	$DS->saveEntities($allLogEntities, true);
}



?>