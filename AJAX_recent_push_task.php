<?PHP
require_once 'datastore_config.php';
require_once 'GoogleDatastoreService.php';

date_default_timezone_set('Asia/Taipei');
$DS = new GoogleDatastoreService($google_api_config);

$tasks = array();
if(array_key_exists('appId', $_GET)){
	$fromIndex = 0;
	$limitSize = 20;
	if(array_key_exists('fromIndex', $_GET)){
		$fromIndex = intval($_GET['fromIndex']);	
	}
	if(array_key_exists('limitSize', $_GET)){
		$limitSize = intval($_GET['limitSize']);	
	}
	queryTasks($_GET['appId'], $fromIndex, $limitSize);
}

echo json_encode($tasks);

function queryTasks($appId, $fromIndex, $limitSize){
	global $tasks;
	global $DS;
	$appId = $_GET['appId'];
	$gql = 	 "Select * "
			."From push_task "
			."Where app_id = '".$appId."' "
			."Order by time desc "
			."Limit ".$limitSize." offset ".$fromIndex;
	$entities = $DS->queryEntitiesWithGQL($gql);
	$entityCount = count($entities);
	for($i = 0; $i < $entityCount; $i++){
		$entityRow = $DS->entityJSONToRowFormat($entities[$i]);
		$tasks[] = array(	'taskAutoId' => $entityRow['__id__'], 
							'IP' => $entityRow['IP'], 'OS' => $entityRow['OS'], 
							'message' => $entityRow['message'], 
							'success' => getValueSafely($entityRow, 'success', false),
							'sent_device' => getValueSafely($entityRow, 'sent_device', 0),
							'time' => date("Y-m-d H:i:s", strtotime($entityRow['time']))
						);
		unset($entityRow);
	}
	unset($entities);
}

function getValueSafely($array, $key, $defaultValue){
	if(array_key_exists($key, $array)){
		return $array[$key];
	}
	else{
		return $defaultValue;
	}	
}

?>