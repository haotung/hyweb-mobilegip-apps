<?PHP
include_once('config.inc.php');
header("Content-Type:application/json; charset=utf-8");
$conditions = '';
if(array_key_exists('appId', $_GET)){
	$conditions = "And app.app_id = '".$_GET['appId']."' ";
}
$query = "Select aif.ifid, app.name, app.universal_distribution_bundle_id, aif.app_id, version, store_file_name, aif.OS, whats_new, upload_time "
		."From app_installation_file as aif, app "
		."Where aif.app_id = app.app_id "
		.$conditions
		."Order by aif.upload_time desc ";
$list = array();
$appIndex = array();
$rs = $db->Execute($query);
while($row = $rs->FetchRow()){
	$appId = $row['app_id'];
	$curAppIndex = -1;
	if(array_key_exists($appId, $appIndex)){
		$curAppIndex = $appIndex[$appId];	
	}
	else{
		$curAppIndex = count($list);	
		$list[] = array('appId' => $appId,
						'name' => $row['name'],
						'bundleId' => $row['universal_distribution_bundle_id'],
						'versions' => array());
		$appIndex[$appId] = $curAppIndex;
	}
	$list[$curAppIndex]['versions'][] = array('version' => $row['version'],
											'fileName' => $row['store_file_name'],
											'OS' => $row['OS'],
											'whats_new' => $row['whats_new'],
											'upload_time' => $row['upload_time'],
											'ifid' => $row['ifid']);
}

echo json_encode($list);

?>