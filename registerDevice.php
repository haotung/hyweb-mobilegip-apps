<?PHP
include_once('config.inc.php');
require_once 'datastore_config.php';
// require_once 'GoogleDatastoreService.php';
header('Content-Type: application/json; charset=utf-8');
$output['success'] = 'YES';
$output['time'] = date('Y-m-d H:i:s');
// $DS = new GoogleDatastoreService($google_api_config);
main();
$output['PostData'] = $_POST;
echo json_encode($output);


function main(){
	global $output;
	$OS = $_POST['OS'];
	$AppId = $_POST['AppId'];
	$deviceToken = $_POST['deviceToken'];
	$userId = $_POST['userId'];
	$isLogout = $_POST['isLogout'];
	
	// ===== for Debug =====
	/*
	$AppId = "GIP_CSU";
    $OS = 'iOS';
    $deviceToken = '4590d3fb1f7161e94c36ea931a37332a62b737859906b01d952912948f53162c';
    $userId = "";
	*/
	
	if(!existsAppId($AppId)){
		$output['success'] = 'NO';
		$output['error'] = 'Unknown App Id';
		return;
	}
	
	registerDevice($OS, $AppId, $deviceToken, $userId, $isLogout);
}

function registerDevice($OS, $AppId, $deviceToken, $userId, $isLogout){
	global $db;
	$query = "Select * "
			."From device "
			."Where app_id = '".$AppId."' "
			."And OS = '".$OS."' "
			."And token_id = '".$deviceToken."' ";
	try{
		$rs = $db->Execute($query);
		if($rs->FetchRow()){
			if((strlen($userId) > 0) || ($isLogout == '1')){
				$query = "Update device "
						."Set user_id = '".$userId."', "
						."update_time = '".date('Y-m-d H:i:s')."' "
						."Where app_id = '".$AppId."' "
						."And OS = '".$OS."' "
						."And token_id = '".$deviceToken."' ";
				$db->Execute($query);
			}
			else{
				$query = "Update device "
						."Set update_time = '".date('Y-m-d H:i:s')."' "
						."Where app_id = '".$AppId."' "
						."And OS = '".$OS."' "
						."And token_id = '".$deviceToken."' ";
				$db->Execute($query);
				
			}
		}
		else{
			$query = "Insert into device "
					."Set user_id = '".$userId."', "
					."update_time = '".date('Y-m-d H:i:s')."', "
					."app_id = '".$AppId."', "
					."OS = '".$OS."', "
					."token_id = '".$deviceToken."' ";
			$db->Execute($query);
		}
	}
	catch(Exception $ex){
		syslog(LOG_ERR, $ex->getMessage());
	}
	saveToDatastore($OS, $AppId, $deviceToken, $userId, $isLogout);
}

function saveToDatastore($OS, $AppId, $deviceToken, $userId, $isLogout){
	global $datastore;
	global $datastoreTool;
	
	$gql = "select * from device where app_id = '".$AppId."' and token_id = '".$deviceToken."' ";
	syslog(LOG_DEBUG, "gql = ".$gql);
	$query = $datastore->gqlQuery(
				$gql,
				['allowLiterals' => true]
			 );
	$oriUserId = '';
	$deviceEntity = $datastoreTool->fetchOne($query);
	if($deviceEntity != null){
		$deviceEntity['app_id'] = $AppId;
		$deviceEntity['OS'] = $OS;
		$deviceEntity['token_id'] = $deviceToken;
		$deviceEntity['user_id'] = (($isLogout == '1' || strlen($userId) > 0)? $userId:$oriUserId);
		$deviceEntity['update_time'] = new DateTime();
		$deviceEntity['is_development'] = false;
	}
	else{
		$key = $datastore->key('device', $AppId.';'.$deviceToken);
		$deviceEntity = $datastore->entity($key, [
			'app_id' => $AppId, 
			'OS' => $OS, 
			'token_id' => $deviceToken, 
			'user_id' => (($isLogout == '1' || strlen($userId) > 0)? $userId:$oriUserId),
			'update_time' => new DateTime(),
			'is_development' => false
		]);
	}

	$datastore->upsert($deviceEntity);

	
}

function existsAppId($checkingAppId){
	global $db;
	$checkingAppId = str_replace('_Ent', '', $checkingAppId);
	$query = "Select * "
			."From app "
			."Where app_id like '".$checkingAppId."%' ";
	$rs = $db->Execute($query);
	if($rs->FetchRow()){
		return true;
	}
	else{
		return false;
	}
}

?>