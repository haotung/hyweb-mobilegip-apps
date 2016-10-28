<?PHP
include_once('config.inc.php');
require_once 'datastore_config.php';
require_once 'GoogleDatastoreService.php';

$DS = new GoogleDatastoreService($google_api_config);

header('Content-Type: application/json; charset=utf-8');
$output['success'] = 'YES';

main();
$db->Close();
echo json_encode($output);
syslog(LOG_DEBUG, json_encode($output));

function main(){
	global $output;
	global $db;
	global $DS;
	$data = json_decode(file_get_contents('php://input'), true);
	$token = $data['token'];
	$userId = $data['userId'];
	$OS = $data['OS'];
	
	
	$gql =   "select * "
			."from push_log "
			."where token_id = '".$token."' "
			.((strlen($userId) > 0)?"And user_id = '".$userId."' ":'')
			."order by time desc";
	syslog(LOG_DEBUG, 'gql = '.$gql);
	$entities = $DS->queryEntitiesWithGQL($gql);
	$entityCount = count($entities);
	$messages = array();
	for($i = 0; $i < $entityCount; $i++){
		$entityRow = $DS->entityJSONToRowFormat($entities[$i]);
		$message = array();
		$message['message'] = $entityRow['message'];
		syslog(LOG_DEBUG, "time = ".$entityRow['time']);
		/*
		$parts = date_parse(str_replace("T", " ", $entityRow['time']));
		$date = new DateTime();
		$date->setDate($parts['year'], $parts['month'], $parts['day']);
		$date->setTime($parts['hour'], $parts['minute'], $parts['second']);
		*/
		$date = strtotime($entityRow['time']);
		$message['time'] = date("Y-m-d H:i:s", $date);
		$message['taskId'] = $entityRow['task_auto_id'];
		$messages[] = $message;
		$entityRow = null;
		unset($entityRow);
	}
	$entities = null;
	unset($entities);
	
	
	$output['messages'] = $messages;
	/*
	$query = "SELECT push_task.message, push_task.time, push_task.all_parameters "
			."from push_task, push_log "
			."where push_task.task_id = push_log.task_id "
			."and push_log.token_id = '".$token."' ";
	if(strlen($userId) > 0){
		$query .= "and push_log.account = '".$userId."' ";	
	}
	
	
	$rs = $db->Execute($query);
	$messages = array();
	while($row = $rs->FetchRow()){
		
		$message = array();
		$message['message'] = $row['message'];
		$message['time'] = $row['time'];
		$params = json_decode($row['all_parameters']);
		if(is_object($params)){
			if(is_object($params->additionalData)){
				$message['additionalData'] = $params->additionalData;	
			}
		}
		$messages[] = $message;
		
	}
	*/
		
	//$output['query'] = $query;
}

//additionalData

//{"AppId":"GIP_TaiWater","targetDevices":"SpecifiedAccounts","isSilent":true,"targetOS":["iOS","Android"],"message":"\u60a8\u8a02\u95b1\u7684\u5340\u57df\u6709\u505c\u6c34\u901a\u77e5","badge":1,"forDevelopment":true,"additionalData":{"zipcodes":["333","403","407"],"targetType":"cp","targetId":"7308_919"},"specAccounts":["tina.chung@hyweb.com.tw"],"timeStamp":"20150306 101433","redirect":"true","checkStr":"bec37024ddb5ae94786e3749aa0ecfa8"}


?>