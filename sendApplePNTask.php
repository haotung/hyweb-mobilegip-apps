<?PHP
use google\appengine\api\taskqueue\PushTask;
include_once('config.inc.php');
require_once 'datastore_config.php';
require_once 'GoogleDatastoreService.php';
	
$DS = new GoogleDatastoreService($google_api_config);

$certFileName = $_POST['certFileName'];
$certPassPhrase = $_POST['certPassPhrase'];
$message = $_POST['message'];
$badge = $_POST['badge'];
$forDevelopment = ($_POST['forDevelopment'] == '1');
$isSilent = ($_POST['isSilent'] == '1');
$taskAutoId = $_POST['taskAutoId'];
$subTaskId = $_POST['subTaskId'];
$savePushLogOnServer = ($_POST['savePushLogOnServer'] == '1');
$pushToSandboxServer = ($_POST['pushToSandboxServer'] == '1');
$query = "Update push_subtask set status = 'processing' where task_auto_id = ".$taskAutoId." and subtask_id = ".$subTaskId;
$db->Execute($query);
if(array_key_exists('additionalData', $_POST)){
	$additionalData = json_decode($_POST['additionalData']);	
}

$storePath = 'gs://hyweb-mobilegip-apps/push_subtask_tmp/'.$taskAutoId.'_'.$subTaskId.'.token';
$tokensJSON = file_get_contents($storePath);
$tokens = json_decode($tokensJSON, true);
$tokensJSON = null;
unset($tokensJSON);
unlink($storePath);

$apnsHost = 'ssl://gateway.push.apple.com:2195';

if($forDevelopment && $pushToSandboxServer){
	$apnsHost = 'ssl://gateway.sandbox.push.apple.com:2195';
}

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
	return;
}

$logQueries = array();
if($isSilent){
	$payload['aps'] = array('content-available' => 1, 'message' => $message, 'badge'=> $badge, 'sound'=>'');
}
else{
	$payload['aps'] = array('content-available' => 1, 'alert' => $message, 'badge'=> $badge, 'sound'=>'');
}
if(isset($additionalData)){
	$payload['aps']['additionalData'] = $additionalData;	
}
$output = json_encode($payload);
$tokenEntities = array();



for($i = 0; $i < count($tokens); $i++){
	$msg = chr(0) . pack('n', 32) . pack('H*', $tokens[$i]['t']) . pack('n', strlen($output)) . $output;
	// Send it to the server
	
	if($i < 5){
		syslog(LOG_DEBUG, "token = ".$tokens[$i]['t']);	
	}
	$sendResult = fwrite($fp, $msg, strlen($msg));
	//$logQuery = "Insert into push_log_tmp set task_auto_id = ".$taskAutoId.", token_id = '".$tokens[$i]['token_id']."', account = '".$tokens[$i]['user_id']."'; ";\
	//array_push($logQueries, $logQuery);
	if($savePushLogOnServer){
		$kvs = array();
		$kvs[] = array('key' => 'task_auto_id', 'value' => $taskAutoId, 'type' => PropertyType::Integer);
		$kvs[] = array('key' => 'token_id', 'value' => $tokens[$i]['t'], 'type' => PropertyType::String);
		$kvs[] = array('key' => 'user_id', 'value' => $tokens[$i]['u'], 'type' => PropertyType::String);
		$kvs[] = array('key' => 'message', 'value' => $message, 'type' => PropertyType::String);
		$kvs[] = array('key' => 'time', 'value' => date("Y-m-d H:i:s"), 'type' => PropertyType::DateType);
		
		array_push($tokenEntities, $DS->buildEntity('push_log', $kvs));
		unset($kvs);
	}
}

syslog(LOG_DEBUG, 'tokenEntities count:'.count($tokenEntities));
if(count($tokenEntities) > 0){
	$DS->saveEntities($tokenEntities, true);
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
}
else{
	syslog(LOG_DEBUG, $taskAutoId.' still has subtasks which are executing');
}

syslog(LOG_DEBUG, 'finish subtask for '.count($tokens).' entities of taskAutoId:'.$taskAutoId);
$db->Close();


?>