<?PHP
include_once('config.inc.php');

$output = array('success' => false);
syslog(LOG_DEBUG, "parameter:".json_encode($_GET));
$appId = '';
if(array_key_exists('appId', $_GET)){
	$appId = $_GET['appId'];	
}
if(strlen($appId) == 0){
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode($output);
	die();	
}
if(array_key_exists('packageName', $_GET)){
	$packageName = $_GET['packageName'];	
}
if(array_key_exists('OS', $_GET)){
	$OS = $_GET['OS'];	
}

$condition = '';
$updateItem = '';
if(strcmp($OS, 'iOS') === 0){
	if(isset($appId) && strlen($appId) > 0){
		$condition = "app.app_id = '".$appId."' ";
	}
	$updateItem = 'ios_latest_version';
}
else if(strcmp($OS, 'Android') === 0){
	if(isset($appId) && strlen($appId) > 0){
		$condition = "app.app_id = '".$appId."' ";
	}
	else if(isset($packageName) && strlen($packageName) > 0){
		$condition = "app.android_package_name = '".$packageName."' ";
	}
	$updateItem = 'android_latest_version';
}

if(strlen($condition) > 0){
	$versionColumnName = strtolower($_GET['OS']).'_latest_version';
	$query = "Select app.app_id, last_update.last_update_time, ".$versionColumnName." as latest_version, itunes_url "
			."from app "
			."left join last_update "
			."on last_update.app_id = app.app_id "
			."and last_update.update_item = '".$updateItem."' "
			."where ".$condition;
	$rs = $db->Execute($query);
	if($row = $rs->FetchRow()){
		if(needUpdate($row['last_update_time'])){
			
			$url = 'http://hyweb-mobilegip-apps.appspot.com/updateLatestVersionOnStore.php';
			$context = [
			  'http' => [
			    'method' => 'GET'
			  ]
			];
			$context = stream_context_create($context);
			$result = file_get_contents($url, false, $context);
			
			$rs = $db->Execute($query);
			$row = $rs->FetchRow();
				
		}	
		
		if(strlen($row['latest_version']) > 0){
			$output['success'] = true;
			$output['latestVersion'] = $row['latest_version'];
			if(!is_null($row['itunes_url'])){
				$output['iTunesUrl'] = $row['itunes_url'];	
			}
		}
	}
		
}




function needUpdate($lastUpdateTime){
	return false;
	$ourHourAgo = date('Y-m-d H:i:s', strtotime('-6 hour'));
	if(is_null($lastUpdateTime)){
		return true;
	}
	else{
		return ($lastUpdateTime < $ourHourAgo);
	}
}


header('Content-Type: application/json; charset=utf-8');
echo json_encode($output);
		
?>