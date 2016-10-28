<?PHP
require_once('config.inc.php');
use google\appengine\api\taskqueue\PushTask;
$response = array();
$data;
// $module = ModulesService::getCurrentModuleName();
$module = 'default';


ini_set('memory_limit', '128M');
main();
echo json_encode($response);
unset($response);


function main(){
	global $response;
	global $data;
	global $db;
	global $ip;
	global $module;
	syslog(LOG_DEBUG, "running on module:".$module);
	if (!empty($_SERVER['HTTP_CLIENT_IP']))
	    $ip=$_SERVER['HTTP_CLIENT_IP'];
	else if (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
	    $ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
	else
	    $ip=$_SERVER['REMOTE_ADDR'];
	
	$data = json_decode(file_get_contents('php://input'), true);
	//$data = json_decode('{"AppId": "GIP_TaiWater","targetDevices": "AllDevices","isSilent": true,"targetOS": [    "iOS",    "Android"],"message": "","badge": 1,"forDevelopment": true,"additionalData": {    "zipcodes": ["304","238","901","239","640","652","238","238","701","334","368","950","433","804","633","655","636","635","632","634","633","655","636","635","632","634","717","510","600","324","510","625","727","852","947","265","364","540","811","300","338","710","503","411","204","401","709","845","604","338","806","600","407","351","946","404","333","621","427","966","510","243","606","411","269","407","310","426","351","806","334","320","201","813","411","522","514","522","514","709","732","612","320","825","944","433"    ]}}', true);
	header('Content-Type: application/json; charset=utf-8');

	// if(strcmp($data['AppId'], 'GIP_CBC') === 0){
	// 	$data['targetDevices'] = 'SpecifiedAccounts';
	// 	$data['specAccounts'][] = 'hyweb';
	// }

	$response['results'] = array();
	//$response['success'] = true;
	//$response['params'] = $data;
	//checkCode
	if(!array_key_exists('isSilent', $data)){
		$data['isSilent'] = false;
	}
	$appId = $data['AppId'];
	syslog(LOG_DEBUG, "sendNotification.php executes for AppId = ".$appId);
	if(strlen($appId) == 0){
		//syslog(LOG_DEBUG, "push notification request has no appId, parameters = ".file_get_contents('php://input'));
		syslog(LOG_ERR, "push notification request has no appId, parameters = ".file_get_contents('php://input'));	
		die();
	}

	$query = "Select ignore_check_str from app where app_id = '".$appId."' and ignore_check_str = 1 ";
	$response['queries'][] = $query;
	$rs = $db->Execute($query);
	$ignoreCheckStr = 0;
	if($row = $rs->FetchRow()){
		$ignoreCheckStr = 1;
	}
	
	
	if(($ignoreCheckStr === 0) && (!validateCheckStr())){
		$response['success'] = false;
		$response['error'] = 'Invalid check string('.json_encode($data).')';
	}
	else{
		$targetOS = $data['targetOS'];
		
		for($i = 0; $i < count($targetOS); $i++){
			$result = array();
			$result['targetOS'] = $targetOS[$i];
			
			if($targetOS[$i] == 'iOS'){
				$taskId = createTask($targetOS[$i]);
				$taskAutoId = createTaskOnDatastore($targetOS[$i], $taskId);
				$sentCount = executeAppleNotificationTask($taskId, $taskAutoId, $result);
				if(is_null($sentCount)){
					$sentCount = 0;
				}
				$query = "Update push_task set sent_device = ".$result['sentDeviceCount']." Where task_id = ".$taskId." ";
				$db->Execute($query);
				if($result['sentDeviceCount'] == 0){
					updateTaskResultOnDatastore($taskAutoId, $sentCount);
				}
				$response['queries'][] = $query;
				
				syslog(LOG_DEBUG ,"Memory usage:".memory_get_usage()." after Apple push");
			}
			else if($targetOS[$i] == 'Android'){
				syslog(LOG_DEBUG ,"Memory usage:".memory_get_usage()." before GCM push");
				$taskId = createTask($targetOS[$i]);
				$taskAutoId = createTaskOnDatastore($targetOS[$i], $taskId);
				$sentCount = executeAndroidNotificationTask($taskId, $taskAutoId, $result);
				if(is_null($sentCount)){
					$sentCount = 0;
				}
				$db->Execute('Update push_task set sent_device = '.$sentCount.' Where task_id = '.$taskId.' ');
				if($sentCount == 0){
					updateTaskResultOnDatastore($taskAutoId, $sentCount);
				}
				
			}
			else if($targetOS[$i] == 'Windows Phone'){
				$result['status'] = 'failed (not implemented)';
			
			}
			else{
				$result['status'] = 'failed (unknown OS)';
			}
			$response['results'][] = $result;
		}
	}
	
	$db->Close();
}

function updateTaskResultOnDatastore($taskAutoId, $sentCount, $success = true){
	// global $DS;
	global $datastore;
	global $datastoreTool;

	$gql = "SELECT * FROM push_task where  __key__ = Key(push_task, ".$taskAutoId.")";
	$query = $datastore->gqlQuery(
				$gql,
				['allowLiterals' => true]
			 );
	$entity = $datastoreTool->fetchOne($query);
	$entity['sent_device'] = $sendCount;
	$entity['success'] = $success;
	$datastore->update($entity);



	//$entities = $DS->queryEntitiesWithGQL($query);
	//echo "entities = ".json_encode($entities)."<br/>\n";
	// $updated_entities = array();
	
	// $entityObjs = $DS->queryEntityObjectsWithGQL($query);
	// for($i = 0; $i < count($entityObjs); $i++){
	// 	$entity = $entityObjs[$i];
	// 	$DS->setEntityProperty($entity, 'sent_device', $sentCount, PropertyType::Integer);
	// 	$DS->setEntityProperty($entity, 'success', $success, PropertyType::Boolean);
	// 	//$properties = $entity->getProperties();	
	// 	$updated_entities[] = $entity;
	// }
	
	// $DS->saveEntities($updated_entities, false);	
}

function validateCheckStr(){
	global $db;
	global $data;
	global $response;
	
	$inputTimeStamp = $data['timeStamp'];
	$inputCheckStr = $data['checkStr'];
	$message = $data['message'];
	$hashHeader = '';
	if($data['redirect'] == 'true'){
		$hashHeader = 'fe4981asnx';
	}
	else{
		$query = "Select hash_header from app Where app_id = '".$data['AppId']."' ";
		$rs = $db->Execute($query);
		if($row = $rs->FetchRow()){
			$hashHeader = $row['hash_header'];
		}
		else{
			return false;	
		}
	}
	$hashStr = $hashHeader.$data['AppId'].$inputTimeStamp.$message;
	return (md5($hashStr) === $inputCheckStr);
}

function createTask($OS){
	global $db;
	global $data;
	global $ip;
	$query = "Insert into push_task set app_id = '".$data['AppId']."', OS = '".$OS."', message = '"
		.addslashes($data['message'])."', for_development = ".(($data['forDevelopment'] == true)?1:0).", "
		."all_parameters = '".addslashes(file_get_contents('php://input'))."', "
		."IP = '".$ip."', time = '".date('Y-m-d H:i:s')."' ";
	//$result['log_query'] = $query;
	$db->Execute($query);
	$taskId = $db->Insert_ID();
	return $taskId;
}

function createTaskOnDatastore($OS, $task_id = null){
	global $data;
	global $ip;
	global $datastore;

	$entityAttrs = array();





	$kvs = array();
	if(!is_null($task_id)){
		$kvs[] = array('key' => 'task_id', 'value' => $task_id);
	}
	$kvs[] = array('key' => 'app_id', 'value' => $data['AppId']);
	$kvs[] = array('key' => 'OS', 'value' => $OS);
	$kvs[] = array('key' => 'for_development', 'value' => $data['forDevelopment']);
	$kvs[] = array('key' => 'IP', 'value' => $ip);
	$kvs[] = array('key' => 'message', 'value' => $data['message']);
	$kvs[] = array('key' => 'all_parameters', 'value' => file_get_contents('php://input'), 'indexed' => false);
	$kvs[] = array('key' => 'time', 'value' => date('Y-m-d H:i:s'));
	$kvs[] = array('key' => 'success', 'value' => false);
	$kvs[] = array('key' => 'sent_device', 'value' => 0);
	$kvs[] = array('key' => 'tester_account', 'value' => '');
	//$kvs[] = array('key' => 'tokens', 'value' => $allLogEntities, 'type' => PropertyType::ListType);
	

	$kvsCount = count($kvs);
	for($i = 0; $i < $kvsCount; $i++){
		$entityAttrs[$kvs[$i]['key']] = $kvs[$i]['value'];
	}



	$key = $datastore->key('push_task');
	$entity = $datastore->entity($key, $entityAttrs);
	$datastore->insert($entity);

	// $entity = $DS->buildEntity('push_task', $kvs);	
	// $entities[] = $entity;
	
	// $ids = $DS->saveEntities($entities, true);

	return $key->pathEnd()['id'];
}

function getTokens($AppId, $OS, $targetDevices, $filterDeviceOnServer, $msgCat, $specAccounts, $startIndex = -1, $limitSize = 500){
	global $db;
	global $datastore;
	global $datastoreTool;
	global $response;
	
	$tmpTableName = '';
	$sentDeviceCount = 0;
	$fromAdditionalTables = '';
	$catCondtion = '';
	if($filterDeviceOnServer){
		if($msgCat != null){
			$catCondtion = "and device.token_id = favorite_node.token_id "
						  ."and favorite_node.node_id = '".$msgCat."' ";
						  
			$fromAdditionalTables .= ', favorite_node';
		}
	}
	$gql = '';
	if($targetDevices == 'AllDevices'){
		if(strlen($fromAdditionalTables) == 0
		&& strlen($catCondtion) == 0){
			$gql = "select token_id, user_id from device where app_id = '".$AppId."' and OS = '".$OS."' order by update_time desc, token_id, user_id ";

		}
		else{
			$query = "Select device.token_id, user_id "
					."From device ".$fromAdditionalTables." "
					."Where app_id = '".$AppId."' "
					."And OS = '".$OS."' "
					.$catCondtion
					."Order by device.update_time desc ";
		}
	}
	else if($targetDevices == 'AllRegisteredAccounts'){
		$query = "Select device.token_id, user_id "
				."From device ".$fromAdditionalTables." "
				."Where app_id = '".$AppId."' "
				."And OS = '".$OS."' "
				."And user_id > '' "
				.$catCondtion
				."Order by device.update_time desc ";
	}
	else if($targetDevices == 'SpecifiedAccounts'){
		if($specAccounts != null && count($specAccounts) > 0){
			
			if(count($specAccounts) > 1){
				$tmpTableName = "temp".date('YmdHis').rand(1001, 9999).'_'.strtolower($OS);
				$query2 = "create table ".$tmpTableName." ("
						."`account` varchar(64)) "
						."ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ";
				//echo $query2;
				$db->Execute($query2);
				for($a = 0; $a < count($specAccounts); $a++){
					if(strlen($specAccounts[$a]) == 0){
						continue;
					}
					$query2 = "Insert into ".$tmpTableName." "
							."set account = '".$specAccounts[$a]."' ";
					$db->Execute($query2);
				}
				
				$query = "Select device.token_id, device.user_id "
					."From device, ".$tmpTableName." ".$fromAdditionalTables." "
					."Where app_id = '".$AppId."' "
					."And OS = '".$OS."' "
					.$catCondtion
					."And device.user_id = ".$tmpTableName.".account "
					."Order by device.update_time desc ";
			}
			else{
				if(strlen($catCondtion) > 0){
					$query = "Select device.token_id, device.user_id "
						."From device, ".$fromAdditionalTables." "
						."Where app_id = '".$AppId."' "
						."And OS = '".$OS."' "
						.$catCondtion
						."Order by device.update_time desc ";
				}
				else{
					$gql = 	 "select * "
							."from device "
							."where app_id = '".$AppId."' "
							."AND OS = '".$OS."' "
							."AND user_id = '".$specAccounts[0]."' ";
				}
			}
		}
	}
	
	$response['log'][] = 'query = '.$query;
	$response['log'][] = 'gql = '.$gql;
	
	if(strlen($query) > 0){
		if($startIndex >= 0){
			$query .= " limit ".$startIndex.", ".$limitSize;	
		}
		$rs = $db->Execute($query);
		$tokens = array();
		while($row = $rs->FetchRow()){
			$tokens[] = $row;
			$row = null;
			unset($row);
		}
		if(strlen($tmpTableName) > 0){
			$dropQuery = 'Drop table '.$tmpTableName;
			$db->Execute($dropQuery);
		}
		return $tokens;
	}
	else if(strlen($gql) > 0){
		//$gql = "select * from device where app_id = '".$AppId."' and OS = '".$OS."' order by update_time desc ";
		if($startIndex >= 0){
			$gql .= "limit ".$limitSize." offset ".$startIndex;	
		}
		syslog(LOG_DEBUG, "gql = ".$gql);
		/*
		$getTokenURL = ((strcmp($_SERVER["HTTPS"], "off") == 0)?"http":"https")."://".$_SERVER['HTTP_HOST']."/getTokensByGQL.php?GQL=".urlencode($gql);
		syslog(LOG_DEBUG, 'getTokenURL = '.$getTokenURL); 
		$tokenResp = file($getTokenURL);
		$tokenJSONStr = implode("\n", $tokenResp);
		unset($tokenResp);
		$tokens = json_decode($tokenJSONStr);
		unset($tokenJSONStr);
		return json_decode($tokens, true); 
		*/
		
		
		//syslog(LOG_DEBUG, 'tokenResp = '.$tokenResp);
		//改接新的DataStore Library
		$query = $datastore->gqlQuery($gql, ['allowLiterals' => true]);
		$entities = $datastore->runQuery($query);
		$tokens = array();
		foreach ($entities as $$entity) {
			$tokenId = $entity['token_id'];
			$userId = $entity['user_id'];
			$tokens[] = array('token_id' => $tokenId, 'user_id' => (($userId != null)?$userId:''));
		}
		syslog(LOG_DEBUG, "token count = ".count($tokens));

		// $entities = $DS->queryEntitiesWithGQL($gql);
		// $entityCount = count($entities);
		// $tokens = array();
		// for($i = 0; $i < $entityCount; $i++){
		// 	$entityRow = $DS->entityJSONToRowFormat($entities[$i]);
		// 	$tokens[] = array('token_id' => $entityRow['token_id'], 'user_id' => (array_key_exists('user_id', $entityRow))?$entityRow['user_id']:'');
		// 	$entityRow = null;
		// 	unset($entityRow);
		// }
		// $entities = null;
		// unset($entities);
		return $tokens;
		
	}
	return array();
}

function executeAndroidNotificationTask($taskId, $taskAutoId, &$result){
	global $db;
	global $data;
	global $response;
	global $module;
	
	$costDownWaiting = false;
	if(strcmp($module, 'many-devices') === 0){
		$costDownWaiting = true;
	}
	$result['status'] = 'success';
	$AppId = $data['AppId'];
	$query = "Select gcm_key, filter_device_on_server, save_push_log_on_server "
			."From app "
			."Where app_id = '".$AppId."' ";
	$rs = $db->Execute($query);
	
	
	
	if($row = $rs->FetchRow()){
		$gcmKey = $row['gcm_key'];
		$savePushLogOnServer = ($row['save_push_log_on_server'] == 1);
		
		syslog(LOG_DEBUG, 'row[save_push_log_on_server] = '.$row['save_push_log_on_server'].', savePushLogOnServer = '.$savePushLogOnServer);
		$tmpTableName = '';
		$sentDeviceCount = 0;
		$fromAdditionalTables = '';
		$catCondtion = '';
		$filterDeviceOnServer = false;
		$msgCat = null;
		if($row['filter_device_on_server'] == 1){
			$filterDeviceOnServer = true;	
		}
		if($data['additionalData'] != null){
			if(array_key_exists('msgCat', $data['additionalData'])){
				$msgCat = $data['additionalData']['msgCat'];
			}	
		}
		
		
		$targetDevices = $data['targetDevices'];
		syslog(LOG_DEBUG, 'before get android tokens');
		syslog(LOG_DEBUG ,"Memory usage:".memory_get_usage()."@before get android tokens");	
		//$query = "Insert into push_log_transfer_queue set task_auto_id = ".$taskAutoId.", task_finish_time = '".date("Y-m-d H:i:s")."'; ";
		//$db->Execute($query);
		$logQuries = array();
		
		
		$startIndex = 0;
		$sentDeviceCount = 0;
		$maxExec = 0;
		$subTaskId = 0;
		while(true){
			$tokenRows = getTokens($AppId, 'Android', $targetDevices, $filterDeviceOnServer, $msgCat, $data['specAccounts'], $startIndex, 500);
			$tokenRowCount = count($tokenRows);
			if($tokenRowCount == 0){
				break;	
			}
			$maxExec++;
			$startIndex += 500;
			syslog(LOG_DEBUG ,"Memory usage:".memory_get_usage()."@after get android tokens");	
			$tokens = array();
			//$tokenEntities = array();
				
			$valueSets = array();
			for($i = 0; $i < $tokenRowCount; $i++){
				$row = $tokenRows[$i];
				
				array_push($tokens, array('t' => $row['token_id'], 'u' => $row['user_id']));

			}
			$tokenRows = null;
			unset($tokenRows);			
					
			if($costDownWaiting){
				if($subTaskId == 1){
					sleep(3);	
				}
				while(true){
					$waitingSubTaskCount = -1;
					$query = "SELECT count(*) as waitingSubTaskCount FROM `push_subtask` where status <> 'finished' ";
					$rs = $db->Execute($query);
					if($row = $rs->FetchRow()){
						$waitingSubTaskCount = $row['waitingSubTaskCount'];
					}
					if($waitingSubTaskCount < 5){
						break;	
					}
					else{
						syslog(LOG_DEBUG, "Waiting for ".$waitingSubTaskCount." waiting subtasks and ".$processingSubTaskCount." processing subtasks. ");
						sleep(1);
					}
				}
			}
			postToGoogleGCMAsync($gcmKey, $tokens, $data['message'], $data['additionalData'], $taskAutoId, $subTaskId++, $savePushLogOnServer);
			
			$tokens = null;
			$tokens = array();
			syslog(LOG_DEBUG ,"Memory usage:".memory_get_usage()."@Google push");
			/*
			if((strcmp($module, 'many-devices') === 0) && $subTaskId % 5 == 4){
				syslog(LOG_DEBUG, "pause 3 seconds");
				sleep(3);	
			}
			*/
			$row = null;
			unset($row);
			$sentDeviceCount += $tokenRowCount;
		}
	/*
		if(count($tokens) > 0){
			
		
			$processingSubTaskCount = -1;
			$waitingSubTaskCount = -1;
			$query = "SELECT count(*) as processingSubTaskCount FROM `push_subtask` as a where task_auto_id = ".$taskAutoId." and status = 'processing' ";
			$rs = $db->Execute($query);
			if($row = $rs->FetchRow()){
				$processingSubTaskCount = $row['processingSubTaskCount'];
			}
			
			$query = "SELECT count(*) as waitingSubTaskCount FROM `push_subtask` as a where task_auto_id = ".$taskAutoId." and status = 'assigned' ";
			$rs = $db->Execute($query);
			if($row = $rs->FetchRow()){
				$waitingSubTaskCount = $row['waitingSubTaskCount'];
			}
			syslog(LOG_DEBUG, "There are ".$waitingSubTaskCount." waiting subtasks and ".$processingSubTaskCount." processing subtasks. ");
			
			postToGoogleGCMAsync($gcmKey, $tokens, $data['message'], $data['additionalData'], $taskAutoId, $subTaskId++, $savePushLogOnServer);
			$sentDeviceCount += count($tokens);
			$tokens = null;
			unset($tokens);
		}
		*/
		$result['sentDeviceCount'] = $sentDeviceCount;
	}
	else{
		$result['status'] = 'failed (unknown App)';
		$response['results'][] = $result;
	}
	$response['log'][] = 'sentDeviceCount = '.$sentDeviceCount;
	return $sentDeviceCount;
	
}

function postToGoogleGCMAsync($gcmKey, $tokens, $message, $additionalData, $taskAutoId, $subTaskId, $savePushLogOnServer = false){
	global $db;
	syslog(LOG_DEBUG, 'assign subtask for '.count($tokens).' entities of taskAutoId:'.$taskAutoId.', subTaskId:'.$subTaskId);
	$query = "Insert into push_subtask set task_auto_id = ".$taskAutoId.", subtask_id = ".$subTaskId.", status = 'assigned', token_count = ".count($tokens)." ";
	$db->Execute($query);
	$storePath = 'gs://hyweb-mobilegip-apps/push_subtask_tmp/'.$taskAutoId.'_'.$subTaskId.'.token';
	$tokenJSON = json_encode($tokens);
	file_put_contents($storePath, $tokenJSON);
	$tokenJSON = null;
	unset($tokenJSON);
	$task = new PushTask('/sendGCM.php', ['gcmKey' => $gcmKey, 'message' => $message, 'additionalData' => json_encode($additionalData), 'taskAutoId' => $taskAutoId, 'subTaskId' => $subTaskId, 'savePushLogOnServer' => ($savePushLogOnServer)?"1":"0"]);
    $task_name = $task->add();
	
}



function postToGoogleGCM($gcmKey, $tokenIds, &$data, &$result, $taskId){
	// Set POST variables
	
	
	//Asynctime output_add_rewrite_var
	postToGoogleGCMAsync($gcmKey, $tokenIds, $data['message'], $data['additionalData']);
	
	return;
	
	
	global $db;
    $url = 'https://android.googleapis.com/gcm/send';


    $fields = array('registration_ids'  => $tokenIds,
                    'data'              => array( 
												'message' => $data['message'],
												'additionalData' => $data['additionalData']
                                                )
                );
 
    $headers = array('Content-Type: application/json',
                     'Authorization: key='.$gcmKey
                    );
	
	$postData = json_encode($fields);
	$context = array( 
	    'http' => array(
	        'method' => 'POST',
	        'header' => 'Authorization: key='.$gcmKey."\r\n" .
	                    'Content-Type: application/json' . "\r\n",
	        'content' => $postData
	    )
	);
	$context = @stream_context_create($context);
	$execResult = @file_get_contents("https://android.googleapis.com/gcm/send", false, $context);
	$result['gcmKey'] = $gcmKey;
	//$result['tokenIds'] = $tokenIds;
	
	$execResultJSONObj = json_decode($execResult,true);
	//$result['postResults'][] = $execResultJSONObj;
	
	if($execResultJSONObj['canonical_ids'] > 0){
		$resultMessages = $execResultJSONObj['results'];
		for($i = 0; $i < count($resultMessages); $i++){
			$message = $resultMessages[$i];
			if(array_key_exists('registration_id', $message)){
				$newId = $message['registration_id'];
				
				$newIdExistsQuery = "Select * from device where token_id = '".$newId."' ";
				$rs = $db->Execute($newIdExistsQuery);
				//$result['check queries'][] = $newIdExistsQuery;
				
				if($row = $rs->FetchRow()){
					$updateIdQuery = "Delete from device where token_id = '".$tokenIds[$i]."' ";
				}
				else{
					$updateIdQuery = "Update device set token_id = '".$newId."' where token_id = '".$tokenIds[$i]."' ";
					
				}
				$db->Execute($updateIdQuery);
			}
		}
	}
	

	
	
}

function executeAppleNotificationTask($taskId, $taskAutoId, &$result){
	global $db;
	global $data;
	global $response;
	global $module;
	
	$result['status'] = 'success';
	$AppId = $data['AppId'];

	$query = "Select iOS_dev_cert, iOS_dev_pass, iOS_prdt_cert, iOS_prdt_pass, filter_device_on_server, save_push_log_on_server, push_to_sandbox_server "
			."From app "
			."Where app_id = '".$AppId."' ";
	$rs = $db->Execute($query);
	$sentDeviceCount = 0;
	if($row = $rs->FetchRow()){
		$certFileName = '';			
		$certPassPhrase = '';
		$devDeviceCondition = '';
		$catCondtion = '';
		$fromAdditionalTables = '';
		$pushToSandboxServer = ($row['push_to_sandbox_server'] == 1);
		$savePushLogOnServer = ($row['save_push_log_on_server'] == 1);
		syslog(LOG_DEBUG, 'row[save_push_log_on_server] = '.$row['save_push_log_on_server'].', savePushLogOnServer = '.$savePushLogOnServer);
		$filterDeviceOnServer = ($row['filter_device_on_server'] == 1);
		if($data['forDevelopment']){
			$certFileName = $row['iOS_dev_cert'];
			$certPassPhrase = $row['iOS_dev_pass'];
			//$devDeviceCondition = ' and is_development = 1 ';
		}
		else{
			$certFileName = $row['iOS_prdt_cert'];
			$certPassPhrase = $row['iOS_prdt_pass'];
		}
		if($data['additionalData'] != null){
			if(array_key_exists('msgCat', $data['additionalData'])){
				$msgCat = $data['additionalData']['msgCat'];
				if($filterDeviceOnServer){
					$catCondtion = "and device.token_id = favorite_node.token_id "
								  ."and favorite_node.node_id = '".$data['additionalData']['msgCat']."' ";
					$fromAdditionalTables .= ', favorite_node';
				}
			}	
		}
		
		if(strlen($certFileName) == 0){
			$result['status'] = "failed (certification file haven't been set)";
			return 0;
		}
		if(!file_exists(dirname(__FILE__).'/cert/'.$certFileName)){
			$certFileOnGS = 'gs://hyweb-mobilegip-apps/certs/'.$certFileName;
			if(!file_exists($certFileOnGS)){
				$result['status'] = "failed (certification file '".$certFileOnGS."' doesn't exist)";
				return 0;
			}
			else{
				$result['log'][] = 'use cert in GS';
				$certFileName = $certFileOnGS;
			}
		}
		else{
			$result['log'][] = 'use cert in code folder';	
		}
		//tokens
		if($data['forDevelopment']){
			if(strcmp(substr($AppId, -4), '_Ent') != 0){
				$AppId .= '_Ent';	
			}	
		}
		$query = '';
		$tmpTableName = '';
		
		$targetDevices = $data['targetDevices'];
		syslog(LOG_DEBUG, 'before get iOS tokens');
		$deviceTokens = getTokens($AppId, 'iOS', $targetDevices, $filterDeviceOnServer, $msgCat, $data['specAccounts']);
		syslog(LOG_DEBUG, 'after get iOS tokens');
		
		$queryLogs = array();
		//$deviceTokens = array();
		/*
		while($row = $rs->FetchRow()){
			$deviceTokens[] = array('token_id' => $row['token_id'], 'account' => $row['user_id']);
		}
		*/
		$costDownWaiting = false;
		if(strcmp($module, 'many-devices') === 0){
			$costDownWaiting = true;
		}
		$batchTokens = array();
		$allTokenCount = count($deviceTokens);
		if($allTokenCount > 0){
			//20 connections
			$batchSize = max(3, ceil($allTokenCount / (($costDownWaiting)?5:20)));
			
			//60 connections
			/*
			if($allTokenCount / $batchSize > 60){
				$batchSize = ceil($allTokenCount / 60);
			}
			*/
			$batchTokenCount = 0;
			$subTaskId = 0;
			for($i = 0; $i < $allTokenCount; $i++){
				$batchTokens[] = array('t' => $deviceTokens[$i]['token_id'], 'u' => $deviceTokens[$i]['user_id']);
				$batchTokenCount++;
				if($batchTokenCount >= $batchSize){
					

					
					if($costDownWaiting){
						$waitingLoop = 0;
						while(true){
							$processingSubTaskCount = -1;
							$waitingSubTaskCount = -1;
							$query = "SELECT count(*) as waitingSubTaskCount FROM `push_subtask` where status <> 'finished' ";
							$rs = $db->Execute($query);
							if($row = $rs->FetchRow()){
								$waitingSubTaskCount = $row['waitingSubTaskCount'];
							}
							
							if($waitingSubTaskCount < 6 || ($waitingLoop++ > 15)){
								break;	
							}
							else{
								syslog(LOG_DEBUG, "Waiting for ".$waitingSubTaskCount." subtasks. ");
								sleep(1);
							}
						}
					}
					
					if(strcmp($module, 'many-devices') === 0){
						pushToAppleAsync($certFileName, $certPassPhrase, $batchTokens, $data['message'], $data['badge'], $data['forDevelopment'], $data['isSilent'], $taskAutoId, $subTaskId++, $savePushLogOnServer, $pushToSandboxServer);
					}
					else{
						syslog(LOG_DEBUG, 'call pushToApple batch tokens');
						pushToApple($certFileName, $certPassPhrase, $batchTokens, $data['message'], $data['badge'], $data['forDevelopment'], $data['isSilent'], $taskId, $taskAutoId, $result);
					}
					$batchTokenCount = 0;
					$batchTokens = null;
					$batchTokens = array();
					syslog(LOG_DEBUG ,"Memory usage:".memory_get_usage()."@Apple push");
					/*
					if((strcmp($module, 'many-devices') === 0) && $subTaskId % 4 == 3){
						syslog(LOG_DEBUG, "pause 2 seconds");
						sleep(1);	
					}
					*/
				}
			}
			if(count($batchTokens) >= 0){
				//pushToApple($certFileName, $certPassPhrase, $batchTokens, $data['message'], $data['badge'], $data['forDevelopment'], $data['isSilent'], $taskId, $taskAutoId,  $result);
				
				
				$processingSubTaskCount = -1;
				$waitingSubTaskCount = -1;
				$query = "SELECT count(*) as processingSubTaskCount FROM `push_subtask` as a where task_auto_id = ".$taskAutoId." and status = 'processing' ";
				$rs = $db->Execute($query);
				if($row = $rs->FetchRow()){
					$processingSubTaskCount = $row['processingSubTaskCount'];
				}
				
				$query = "SELECT count(*) as waitingSubTaskCount FROM `push_subtask` as a where task_auto_id = ".$taskAutoId." and status = 'assigned' ";
				$rs = $db->Execute($query);
				if($row = $rs->FetchRow()){
					$waitingSubTaskCount = $row['waitingSubTaskCount'];
				}
				syslog(LOG_DEBUG, "There are ".$waitingSubTaskCount." waiting subtasks and ".$processingSubTaskCount." processing subtasks. ");
				
				if(strcmp($module, 'many-devices') === 0){
					pushToAppleAsync($certFileName, $certPassPhrase, $batchTokens, $data['message'], $data['badge'], $data['forDevelopment'], $data['isSilent'], $taskAutoId, $subTaskId++, $savePushLogOnServer, $pushToSandboxServer);
				}
				else{
					syslog(LOG_DEBUG, 'call pushToApple for remained tokens');
					pushToApple($certFileName, $certPassPhrase, $batchTokens, $data['message'], $data['badge'], $data['forDevelopment'], $data['isSilent'], $taskId, $taskAutoId,  $result);
					
				}
				
			}
			$batchTokens = null;
			unset($batchTokens);
			$deviceTokens = null;
			unset($deviceTokens);
			//$query = "Insert into push_log_transfer_queue set task_auto_id = ".$taskAutoId.", task_finish_time = '".date("Y-m-d H:i:s")."'; ";
			//$db->Execute($query);
			//$result['tokens'] = $deviceTokens;
		}
		
		$result['sentDeviceCount'] = $allTokenCount;
	}
	if(strlen($tmpTableName) > 0){
		$dropQuery = 'Drop table '.$tmpTableName;
		$db->Execute($dropQuery);
	}
	return $allTokenCount;
}


//$targetTokens[] = '4590d3fb1f7161e94c36ea931a37332a62b737859906b01d952912948f53162c';
//pushToApple('GIP_CSU-Push-Dev.pem', '1111', $targetTokens, 'message from Google App Engine', true);

/*
$gcmKey = 'AIzaSyBnViKz-1rDaMGbn3uoBBQnNZRzOcvhQBE';
$AndroidTokens[] = "APA91bHiweG7XkovyvU5Ukv2j4iSnnQs-snE6ZXyv-klDDKmTTT7LndR0tPUi4pei6aa_5whvixwXRY_uyCaDLlEPYVr-i-NsD_lMWEWI3jTF3zQCnD8obPrLlEKzf1nMEQ5B8B6tsPpp-ffeOJP6n3uE7Y_YDCGXY9VMGfHxDlXB5Sc56rFRmw";
$AndroidTokens[] = "APA91bF96z2_NBBjL0UZbG8adzUkozqOQsW-mU6fWaSLrkQK7kW9Z8ILAcviyMjPsicitJA6D1-e9gGpmPoJT05K5NUXIdLL9ML0_vHMKzxmRZftcvHIsjqt-nFR_QVrJTejmwd7R4u0qeQ2z9V3Gb7yUF_i17qUZIv7mE5X259nUsoz6sCSg_0";
$data = array('message' => 'message from Google App Engine', 'additionalData' => '');
$result = array();
echo "call gcm<br/>\n";
postToGoogleGCM($gcmKey, $AndroidTokens, $data, $result);
echo "end<br/>\n";
*/


function pushToAppleAsync($certFileName, $certPassPhrase, $tokens, $message, $badge, $forDevelopment, $isSilent, $taskAutoId, $subTaskId, $savePushLogOnServer, $pushToSandboxServer){
	global $db;
	global $data;
	syslog(LOG_DEBUG, 'assign APN subtask for '.count($tokens).' entities of taskAutoId:'.$taskAutoId.', subTaskId:'.$subTaskId);
	$query = "Insert into push_subtask set task_auto_id = ".$taskAutoId.", subtask_id = ".$subTaskId.", status = 'assigned', token_count = ".count($tokens)." ";
	$db->Execute($query);
	$storePath = 'gs://hyweb-mobilegip-apps/push_subtask_tmp/'.$taskAutoId.'_'.$subTaskId.'.token';
	$tokenJSON = json_encode($tokens);
	file_put_contents($storePath, $tokenJSON);
	$tokens = null;
	unset($tokens);
	$tokenJSON = null;
	unset($tokenJSON);
	$taskParams = [
							'certFileName' => $certFileName, 
							'certPassPhrase' => $certPassPhrase, 
							'message' => $message, 
							'badge' => $badge, 
							'forDevelopment' => ($forDevelopment)?"1":"0", 
							'pushToSandboxServer' => ($pushToSandboxServer)?"1":"0", 
							'isSilent' => ($isSilent)?"1":"0", 
							'taskAutoId' => $taskAutoId, 
							'subTaskId' => $subTaskId,
							'savePushLogOnServer' => ($savePushLogOnServer)?"1":"0"];
	if(array_key_exists('additionalData', $data)){
		$taskParams['additionalData'] = json_encode($data['additionalData']);	
	}
	$task = new PushTask('/sendApplePNTask.php', $taskParams);
    $task_name = $task->add();
	
}


function pushToApple($certFileName, $certPassPhrase, $tokens, $message, $badge, $forDevelopment, $isSilent, $taskId, $taskAutoId, &$result){
	global $db;
	global $data;
	global $datastore;
	global $datastoreTool;
	if(count($tokens) == 0){
		return;	
	}
	$apnsHost = 'ssl://gateway.push.apple.com:2195';
	/*
	if($forDevelopment){
		$apnsHost = 'ssl://gateway.sandbox.push.apple.com:2195';
	}
	*/
	$result['forDevelopment'] = $forDevelopment;
	$streamContext = stream_context_create();
	//$certUrl = 'gs://hyweb-mobilegip-apps/certs/'.$certFileName;
	if(strpos($certFileName, 'gs://') === 0){
		$certPath = $certFileName;
	}
	else{
		$certPath = dirname(__FILE__).'/cert/'.$certFileName;
	}
	//$result['status'] .= ' certPath = '.$certPath;
	stream_context_set_option($streamContext, 'ssl', 'local_cert', $certPath);
	//stream_context_set_option($streamContext, 'ssl', 'local_cert', $certUrl);
	stream_context_set_option($streamContext, 'ssl', 'passphrase', $certPassPhrase);
	$fp = stream_socket_client(
		$apnsHost, $err,
		$errstr, 20, STREAM_CLIENT_CONNECT, $streamContext
	);
	if (!$fp){
		echo "can't establish connection<br/>\n";
		$result['sentDeviceCount'] = 0;
		return;
	}
	
	$logQueries = array();
	if($isSilent){
		$payload['aps'] = array('content-available' => 1, 'message' => $message, 'badge'=> $badge, 'sound'=>'');
	}
	else{
		if($data['additionalData'] != null && strlen($data['additionalData']) > 0){
			$payload['aps'] = array('content-available' => 1, 'alert' => $message, 'badge'=> $badge, 'sound'=>'');
		}
		else{
			$payload['aps'] = array('alert' => $message, 'badge'=> $badge, 'sound'=>'');
		}
	}
	if($data['additionalData'] != null){
		$payload['aps']['additionalData'] = $data['additionalData'];	
	}
	$output = json_encode($payload);
	$tokenEntities = array();
	
	for($i = 0; $i < count($tokens); $i++){
		$msg = chr(0) . pack('n', 32) . pack('H*', $tokens[$i]['t']) . pack('n', strlen($output)) . $output;
		// Send it to the server
		$sendResult = fwrite($fp, $msg, strlen($msg));
		syslog(LOG_DEBUG, 'sendResult = '.$sendResult.' for token:'.json_encode($tokens[$i]['t']));
		$logQuery = "Insert into push_log_tmp set task_auto_id = ".$taskAutoId.", token_id = '".$tokens[$i]['token_id']."', account = '".$tokens[$i]['user_id']."'; ";\
		array_push($logQueries, $logQuery);
		/*
		$kvs = array();
		$kvs[] = array('key' => 'task_auto_id', 'value' => $taskAutoId, 'type' => PropertyType::Integer);
		$kvs[] = array('key' => 'token_id', 'value' => $tokens[$i]['token_id'], 'type' => PropertyType::String);
		$kvs[] = array('key' => 'user_id', 'value' => $tokens[$i]['user_id'], 'type' => PropertyType::String);
		*/
		//array_push($tokenEntities, $DS->buildEntity('push_log', $kvs));
	}
	
	$logQuriesStr = implode("\n", $logQueries);
	
	$db->ExecuteMultipleQueries($logQuriesStr);
	$result['payload'] = $payload;
	
	fclose($fp);
	syslog(LOG_DEBUG, 'pushToApple for '.count($tokens).' tokens finished.');
	
	//$DS->saveEntities($tokenEntities, true);
}


function pushToAppleWithCURL($certFileName, $certPassPhrase, $tokens, $message, $badge, $forDevelopment, $isSilent, $taskId, &$result){
	global $db;
	global $data;
	$apnsHost = 'https://gateway.push.apple.com:2195';

	if($forDevelopment){
		
		
		
		//$apnsHost = 'https://gateway.sandbox.push.apple.com:2195';
	}
	$result['forDevelopment'] = $forDevelopment;
	if(strpos($certFileName, 'gs://') === 0){
		$certPath = $certFileName;
	}
	else{
		$certPath = dirname(__FILE__).'/cert/'.$certFileName;
	}
	$logQueries = array();
	if($isSilent){
		$payload['aps'] = array('content-available' => 1, 'message' => $message, 'badge'=> $badge, 'sound'=>'');
	}
	else{
		$payload['aps'] = array('content-available' => 1, 'alert' => $message, 'badge'=> $badge, 'sound'=>'');
	}
	if($data['additionalData'] != null){
		$payload['aps']['additionalData'] = $data['additionalData'];	
	}
	$output = json_encode($payload);
	$postTokenArray = array();
	for($i = 0; $i < count($tokens); $i++){
		//$msg = chr(0) . pack('n', 32) . pack('H*', $tokens[$i]['token_id']) . pack('n', strlen($output)) . $output;
		// Send it to the server
		//$sendResult = fwrite($fp, $msg, strlen($msg));
		$postTokenArray[] = $tokens[$i]['token_id'];
		$logQuery = "Insert into push_log set task_id = ".$taskId.", token_id = '".$tokens[$i]['token_id']."', account = '".$tokens[$i]['account']."'; ";
		array_push($logQueries, $logQuery);
	}
	
	$logQuriesStr = implode("\n", $logQueries);
	$db->ExecuteMultipleQueries($logQuriesStr);
	$result['payload'] = $payload;
	$result['sentDeviceCount'] = count($tokens);
	
	$postFields['device_tokens'] = $postTokenArray;
	$postFields['aps'] = $payload['aps'];
	
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL,$apnsHost);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_HEADER, 1);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_SSLCERT, $certPath);
	curl_setopt($ch, CURLOPT_SSLCERTPASSWD, $certPassPhrase);
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postFields));
	$result['postFields'] = $postFields;
	$curl_scraped_page = curl_exec($ch);
	
	$result['send_result'] = $curl_scraped_page;
	
	
	
	
}

?>