<?PHP
include_once('config.inc.php');
require_once 'datastore_config.php';
require_once 'GoogleDatastoreService-2.0.3.php';


$DS = new GoogleDatastoreService($google_api_config);



$appLimited = 'HyLib_NTTU';	

$appIdCondition = '';
if(isset($_GET['appId'])){
	$appIdCondition = " And app_id like '".$_GET['appId']."%' ";
}
else if(strlen($appLimited) > 0){
	$appIdCondition = " And app_id = '".$appLimited."' ";
}


$debugMsg = array();
$Apps = array();
$AppIdIndex = array();
$query = "Select app_id, name, note, hash_header "
		."From app "
		."Where 1 "
		.$appIdCondition
		."Order by app_id ";
$rs = $db->Execute($query);
$i = 0;
$debugMsg[] = $query;

$smallNum = pow(16, 7) + 1;
$largeNum = pow(16, 7) * 16 - 1;
while($row = $rs->FetchRow()){
	$hashHeader = dechex(rand($smallNum, $largeNum));
	//$db->Execute("Update app set hash_header = '".$hashHeader."' Where app_id = '".$row['app_id']."' ");
	
	$Apps[] = array('AppId' => $row['app_id'], 'name' => $row['name'], 'note' => $row['note'], 'accounts' => array(), 'hashHeader' => $row['hash_header']);
	$AppIdIndex[$row['app_id']] = $i++;
}



/*
$query = "Select app_id, user_id "
		."From device "
		."Where user_id > '' "
		."Order by app_id, user_id ";
$rs = $db->Execute($query);
*/


$debugMsg[] = $query;
syslog(LOG_DEBUG, "app count".count($Apps));

for($i = 0; $i < count($Apps); $i++){

	$appId = $Apps[$i]['AppId'];
	
	
	$userIds = array();
	$gql = "select user_id from device where app_id = '".$appId."' and user_id > '' order by user_id ";
	syslog(LOG_DEBUG, "gql = ".$gql);
	$entities = $DS->queryEntitiesWithGQL($gql);
	$entityCount = count($entities);
	for($j = 0; $j < $entityCount; $j++){
		$entityRow = $DS->entityJSONToRowFormat($entities[$j]);
		$userIds[] = $entityRow['user_id'];
		$entityRow = null;
		unset($entityRow);
	}
	$gql = "select user_id from device where app_id = '".$appId."_Ent' and user_id > '' order by user_id ";
	$entities = $DS->queryEntitiesWithGQL($gql);
	$entityCount = count($entities);
	for($j = 0; $j < $entityCount; $j++){
		$entityRow = $DS->entityJSONToRowFormat($entities[$j]);
		$userIds[] = $entityRow['user_id'];
		$entityRow = null;
		unset($entityRow);
	}
	
	
	$totalUserIdCount = count($userIds);
	for($j = 0; $j < $totalUserIdCount; $j++){
		$userId = $userIds[$j];
		if(array_key_exists($appId, $AppIdIndex)){
			$appIndex = $AppIdIndex[$appId];
			
			
			
			if(strcmp($appId, 'GIP_CBC') === 0){
				$debugMsg[] = $appId.', appIndex = '.$appIndex;	
			}
			
			if(!in_array($userId, $Apps[$appIndex]['accounts'])){
				$Apps[$appIndex]['accounts'][] = $userId;
			}
		}
		else{
			$appId = str_replace('_Ent', '', $appId);
			if(array_key_exists($appId, $AppIdIndex)){
				$appIndex = $AppIdIndex[$appId];
				if(!in_array($userId, $Apps[$appIndex]['accounts'])){
					$Apps[$appIndex]['accounts'][] = $userId;
				}
			}
		}
	}
	
	
}




$Apps[] = array('app_id' => 'XX_debug_new_datastore_api', 'name' => 'debug', 'note' => 'debug', 'accounts' => array(), 'msg' => $debugMsg);
header('Content-Type: application/json; charset=utf-8');
echo json_encode($Apps);



?>