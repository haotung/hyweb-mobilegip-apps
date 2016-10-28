<?PHP
include_once('config.inc.php');

$result['success'] = false;
if(array_key_exists('file', $_FILES)){
	//上傳檔案	
	$gs_name = $_FILES['file']['tmp_name'];
	$oriFileName = $_FILES['file']['name'];
	$storeFileName = $oriFileName.'-'.substr($_FILES['file']['tmp_name'], -6);
	move_uploaded_file($gs_name, 'gs://mobilegip-app-download/app_installation/'.$storeFileName);
	$result['success'] = true;
	$result['tmpFileName'] = $storeFileName;
	$result['oriFileName'] = $oriFileName;
	die(json_encode($result));
}
else if(!array_key_exists('appId', $_POST)){
	$result['errorMsg'] = '未知的操作';
	die(json_encode($result));
}


//發佈
	
$oriFileName = $_POST['oriFileName'];
$storeFileName = $_POST['storeFileName'];


if(strtolower(substr($oriFileName, -4)) == '.ipa'){
	$OS = 'iOS';	
}
else if(strtolower(substr($oriFileName, -4)) == '.apk'){
	$OS = 'Android';	
}
else{
	$result['errorMsg'] = '不正確的檔案';
	die(json_encode($result));
}

$app_id = $_POST['appId'];
$version = $_POST['version'];
$whats_new = $_POST['whatsnew'];
$upload_time = date("Y-m-d H:i:s");
$query = "Insert into app_installation_file "
		."set app_id = '".$app_id."', "
		."version = '".$version."', "
		."OS = '".$OS."', "
		."ori_file_name = '".$oriFileName."', "
		."store_file_name = '".$storeFileName."', "
		."whats_new = '".$whats_new."', "
		."upload_time = '".$upload_time."' ";
//echo $query;
$db->Execute($query);
$result['success'] = true;
echo json_encode($result);
?>