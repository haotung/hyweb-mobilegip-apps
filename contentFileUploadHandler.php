<?PHP
include_once('config.inc.php');

//$storeFileName = $oriFileName.'-'.substr($_FILES['file']['tmp_name'], -6);
$result['success'] = false;

$appId = $_POST['appId'];
if(strlen($appId) == 0){
	die(json_encode($result));
}

$itemName = $_POST['itemName'];
if(strlen($itemName) == 0){
	$result['errorMsg'] = '未指定檔案用途';
	die(json_encode($result));	
}

$tmpFilePath = $_FILES['file']['tmp_name'];
$oriFileName = $_FILES['file']['name'];
if(strpos($oriFileName, '=?UTF-8?') === 0){
	$oriFileNameBase64 = substr($oriFileName, 10, strlen($oriFileName) - 14);
	$oriFileName = base64_decode($oriFileNameBase64);
}
$isImage = false;
$ext = strtolower(pathinfo('/path/'.$oriFileName, PATHINFO_EXTENSION));
/*
if(strlen($ext) == 0){
	$pos = strrpos($oriFileName, '.');
	if(($pos !== false) && ($pos < strlen($oriFileName) - 1)){
		$ext = substr($oriFileName, $pos + 1);	
	}
	
}
*/
$storeFileName = $_POST['appId'].'-'.substr($_FILES['file']['tmp_name'], -6).'.'.$ext;
$storePath = 'gs://hyweb-mobilegip-apps/content_files/'.$storeFileName;
$contentType = '';

$query = "Select width, height, description, content_type, (content_type like 'image/%') as is_image "
		."From app_content_file_type "
		."Where item_name = '".$itemName."' ";
$rs = $db->Execute($query);
if($row = $rs->FetchRow()){
	if($row['is_image']){
		$isImage = true;
		validateImage($row);
	}
	$contentType = $row['content_type'];
}
else{
	$result['errorMsg'] = '未知的檔案用途';
	die(json_encode($result));
}


//通過所有檢查後，檢查是否已有指定檔案，如果有要先從Cloud Storage移除
$query = "Select filename "
		."From app_content_file "
		."Where app_id = '".$appId."' "
		."And item_name = '".$itemName."' ";
$rs = $db->Execute($query);
if($row = $rs->FetchRow()){
	$imgFileName = $row['filename'];
	if(strlen($imgFileName) > 0){
		unlink('gs://hyweb-mobilegip-apps/content_files/'.$imgFileName);
	}
	$query = "Update app_content_file "
			."Set filename = '".$storeFileName."' "
			."Where app_id = '".$appId."' "
			."And item_name = '".$itemName."' ";
}
else{
	$query = "Insert into app_content_file "
			."Set app_id = '".$appId."', "
			."item_name = '".$itemName."', "
			."filename = '".$storeFileName."' ";
}
//echo $query;
$db->Execute($query);

move_uploaded_file($tmpFilePath, $storePath);
$result['success'] = true;
$result['filename'] = $storeFileName;
$result['oriFileName'] = $oriFileName;
$result['isImage'] = $isImage;
$result['contentType'] = $contentType;
echo json_encode($result);



function validateImage($fileMetadata){
	global $tmpFilePath;
	global $result;
	$tmpImage = new Imagick();
	$oriImageBlob = file_get_contents($tmpFilePath);
	
	
	try{
		$tmpImage->readImageBlob($oriImageBlob);
		
		//檢查實際檔案格式
		$format = strtolower($tmpImage->getImageFormat());
		$contentTypeParts = split('/', $fileMetadata['content_type']);
		if(count($contentTypeParts) > 1 && $contentTypeParts[1] != '*'){
			if(strtoupper($format) != strtoupper($contentTypeParts[1])){
				$result['errorMsg'] = '需使用'.strtoupper($contentTypeParts[1]).'格式圖檔(實際格式為:'.$format.')';
				die(json_encode($result));
			}
		}
		
		//檢查圖檔尺寸
		$geo = $tmpImage->getImageGeometry(); 
		$width = $geo['width'];
		$height = $geo['height'];
		if(($fileMetadata['width'] != $width) || ($fileMetadata['height'] != $height)){
			$result['errorMsg'] = '圖片尺寸不符。（'.$fileMetadata['description'].' 需要 '.$fileMetadata['width'].'x'.$fileMetadata['height'].', 實際上傳尺寸為 '.$width.'x'.$height.')';
			die(json_encode($result));
		}
	}
	catch(ImagickException $e){
		$result['errorMsg'] = '無效的圖檔';
		die(json_encode($result));	
	}
	

	
}


?>