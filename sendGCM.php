<?PHP
use google\appengine\api\taskqueue\PushTask;
include_once('config.inc.php');
require_once 'datastore_config.php';
require_once 'GoogleDatastoreService.php';

$DS = new GoogleDatastoreService($google_api_config);


//function postToGoogleGCM($gcmKey, $tokenIds, &$data, &$result, $taskId){

$gcmKey = $_POST['gcmKey'];
$message = $_POST['message'];
$additionalData = json_decode($_POST['additionalData']);
$taskAutoId = $_POST['taskAutoId'];
$subTaskId = $_POST['subTaskId'];
$savePushLogOnServer = (strcmp($_POST['savePushLogOnServer'], '1') === 0);
syslog(LOG_DEBUG, "savePushLogOnServer = ".$savePushLogOnServer);
$query = "Update push_subtask set status = 'processing' where task_auto_id = ".$taskAutoId." and subtask_id = ".$subTaskId;
$db->Execute($query);

syslog(LOG_DEBUG ,"Memory usage:".memory_get_usage()." before load tokens");
$storePath = 'gs://hyweb-mobilegip-apps/push_subtask_tmp/'.$taskAutoId.'_'.$subTaskId.'.token';
$tokensJSON = file_get_contents($storePath);
$tokens = json_decode($tokensJSON, true);
$tokensJSON = null;
unset($tokensJSON);
syslog(LOG_DEBUG ,"Memory usage:".memory_get_usage()." after remove tokensJSON");
unlink($storePath);
syslog(LOG_DEBUG, 'start subtask for '.count($tokens).' entities of taskAutoId:'.$taskAutoId);

$url = 'https://android.googleapis.com/gcm/send';
$curTime = date("Y-m-d H:i:s");
$tokenEntities = array();
$tokenIds = array();
$tokenCount = count($tokens);
for($i = 0; $i < $tokenCount; $i++){
	$tokenIds[] = $tokens[$i]['t'];	
	if($savePushLogOnServer){
		$kvs = array();
		$kvs[] = array('key' => 'task_auto_id', 'value' => $taskAutoId, 'type' => PropertyType::Integer);
		$kvs[] = array('key' => 'token_id', 'value' => $tokens[$i]['t'], 'type' => PropertyType::String);
		$kvs[] = array('key' => 'user_id', 'value' => $tokens[$i]['u'], 'type' => PropertyType::String);
		$kvs[] = array('key' => 'message', 'value' => $message, 'type' => PropertyType::String);
		$kvs[] = array('key' => 'time', 'value' => $curTime, 'type' => PropertyType::DateType);
		$tokenEntities[] = $DS->buildEntity('push_log', $kvs);
		$kvs = null;
		unset($kvs);
	}
}
syslog(LOG_DEBUG ,"Memory usage:".memory_get_usage()." before remove tokens");
$tokens = null;
unset($tokens);
syslog(LOG_DEBUG ,"Memory usage:".memory_get_usage()." after remove tokens");
$fields = array('registration_ids'  => $tokenIds,
                'data'              => array( 
											'message' => $message,
											'additionalData' => $additionalData
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
//$result['gcmKey'] = $gcmKey;
//$result['tokenIds'] = $tokenIds;
syslog(LOG_DEBUG, 'send to GCM with '.count($tokenIds).' tokens asynchronously');
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


if(count($tokenEntities) > 0){
	$DS->saveEntities($tokenEntities, true);
	$tokenEntities = null;
	unset($tokenEntities);
}

$query = "Update push_subtask set status = 'finished' where task_auto_id = ".$taskAutoId." and subtask_id = ".$subTaskId;
$db->Execute($query);

$query = "Select * "
		."From push_subtask "
		."Where task_auto_id = ".$taskAutoId." ";
$rs = $db->Execute($query);
$allSubtaskFinished = true;
$allSentTokenCount = 0;
while($row = $rs->FetchRow()){	
	if(strcmp('finished', $row['status']) !== 0){
		$allSubtaskFinished = false;	
		break;
	}
	else{
		$allSentTokenCount += $row['token_count'];
	}
}
if($allSubtaskFinished){
	$query = "Delete from push_subtask where task_auto_id = ".$taskAutoId;
	$db->Execute($query);	
	
	
	$query = "SELECT * FROM push_task where  __key__ = KEY(push_task, ".$taskAutoId.")";
	$updated_entities = array();
	
	$entityObjs = $DS->queryEntityObjectsWithGQL($query);
	for($i = 0; $i < count($entityObjs); $i++){
		$entity = $entityObjs[$i];
		$DS->setEntityProperty($entity, 'success', true, PropertyType::Boolean);
		$DS->setEntityProperty($entity, 'sent_device', $allSentTokenCount, PropertyType::Integer);
		$updated_entities[] = $entity;
	}
	
	$DS->saveEntities($updated_entities, false);	
	syslog(LOG_DEBUG, "save all sent token count:".$allSentTokenCount." for task auto ID:".$taskAutoId);
}
else{
	syslog(LOG_DEBUG, $taskAutoId.' still has subtasks which are executing');
}

syslog(LOG_DEBUG, 'finish subtask for '.$tokenCount.' entities of taskAutoId:'.$taskAutoId);
$db->Close();

?>