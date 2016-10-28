<?PHP
include_once('config.inc.php');

//$storeFileName = $oriFileName.'-'.substr($_FILES['file']['tmp_name'], -6);
$result['success'] = false;

$appId = $_POST['appId'];
if(strlen($appId) == 0){
	die(json_encode($result));
}

$gs_name = $_FILES['file']['tmp_name'];
$oriFileName = $_FILES['file']['name'];
$ext = strtolower(pathinfo('/path/'.$oriFileName, PATHINFO_EXTENSION));
$storeFileName = $_POST['appId'].'-'.substr($_FILES['file']['tmp_name'], -6).'.'.$ext;
$storePath = 'gs://hyweb-mobilegip-apps/app_icon/'.$storeFileName;

$tmpImage = new Imagick();
$oriImageBlob = file_get_contents($gs_name);

try{
	$tmpImage->readImageBlob($oriImageBlob);
}
catch(ImagickException $e){
	$result['errorMsg'] = '無效的圖檔';
	die(json_encode($result));	
}

$format = strtolower($tmpImage->getImageFormat());
if($format == 'jpeg') $format = 'jpg';
if(strtolower($format) != $ext){
	$result['errorMsg'] = '檔案格式與副檔名不符(實際格式為:'.$format.')';
	die(json_encode($result));
}

$geo = $tmpImage->getImageGeometry(); 

$width = $geo['width'];
$height = $geo['height'];

if($width != $height){
	$result['errorMsg'] = '長寬需相同';
	die(json_encode($result));
}

if($width < 1024 || $height < 1024){
	$result['warningMsg'] = '建議使用1024x1024圖檔已達最佳縮圖效果';
}

$query = "Select icon_file_name "
		."From app "
		."Where app_id = '".$appId."' ";
$rs = $db->Execute($query);
if($row = $rs->FetchRow()){
	$iconFileName = $row['icon_file_name'];
	if(strlen($iconFileName) > 0){
		unlink('gs://hyweb-mobilegip-apps/app_icon/'.$iconFileName);
	}
}

$query = "Update app "
		."set icon_file_name = '".$storeFileName."' "
		."Where app_id = '".$appId."' ";
//echo $query;
$db->Execute($query);

move_uploaded_file($gs_name, $storePath);
$result['success'] = true;
$result['filename'] = $storeFileName;
echo json_encode($result);

?>