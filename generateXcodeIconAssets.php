<?PHP
include_once('config.inc.php');
include_once('zip.lib.php');
use google\appengine\api\cloud_storage\CloudStorageTools;
if(!array_key_exists('appId', $_GET)){
	die();	
}
$appId = $_GET['appId'];
$iconFileName = '';
$query = "Select icon_file_name "
		."From app "
		."Where app_id = '".$appId."' ";
$rs = $db->Execute($query);
if($row = $rs->FetchRow()){
	$iconFileName = $row['icon_file_name'];
}
		
if(strlen($iconFileName) > 0){
	
	$sampleJSONPath = 'gs://hyweb-mobilegip-apps/definition_files/xcode_app_icon_assets.json';
	$jsonContentStr = file_get_contents($sampleJSONPath);
	$jsonObj = json_decode($jsonContentStr, true);
	$requiredSizes = array();
	
	$fileUrl = 'gs://hyweb-mobilegip-apps/app_icon/'.$iconFileName;
	$ext = strtolower(pathinfo($fileUrl, PATHINFO_EXTENSION));
	$oriImageBlob = file_get_contents($fileUrl);
	
	parseSampleJson($ext);
	
	$zipFileName = $appId.'_icon_xcode_assets.zip';
	$zip = new ZipFile();
	$iconImg = new Imagick();
	$dirName = $appId."_AppIcon.appiconset";
	$pathInZip = '/'.$dirName.'/';
	$requiredSizeCount = count($requiredSizes);
	for($i = 0; $i < $requiredSizeCount; $i++){
		$size = $requiredSizes[$i];
		$iconImg->readimageblob($oriImageBlob);
		$iconImg->resizeImage($size, $size, imagick::FILTER_CATROM, 1); 
		$imgFileName = 'icon'.$size.'.'.$ext;
		$zip->addFile($iconImg->getimageblob(), $pathInZip.$imgFileName);
	}
	
	$zip->addFile(json_encode($jsonObj, JSON_PRETTY_PRINT), $pathInZip.'Contents.json');
	
	
	$zipContent = $zip->file();
	
	/*
	$options = ['gs' => ['Content-Type' => 'application/zip']];
	$ctx = stream_context_create($options);
	file_put_contents('gs://hyweb-mobilegip-apps/zip_temp/'.date('YmdHis').'.zip', $zipContent, 0, $ctx);
	*/
	
	header('Content-Type: application/zip');
	header('Content-Length: ' . strlen($zipContent));
	header('Content-Disposition: attachment; filename="'.$zipFileName.'"');
	echo $zipContent;
	
}




function parseSampleJson($ext){
	global $jsonObj;
	global $requiredSizes;
	$images = $jsonObj['images'];
	$imageCount = count($images);
	$requiredSizes = array();
	for($i = 0; $i < $imageCount; $i++){
		$image = $images[$i];
		if(!array_key_exists('size', $image)){
			continue;
		}
		if(!array_key_exists('scale', $image)){
			continue;
		}
		unset($image['filename']);
		$sizeDesc = $image['size'];
		$scaleDesc = $image['scale'];
		$size = getFirstNum($sizeDesc);
		$scale = getFirstNum($scaleDesc);
		if($size == 0 || $scale == 0){
			continue;	
		}
		$realSize = $scale * $size;
		$image['filename'] = 'icon'.$realSize.'.'.$ext;
		if(!in_array($realSize, $requiredSizes)){
			$requiredSizes[] = $realSize;
		}
		
		$jsonObj['images'][$i] = $image;
		//unset($jsonObj['images'][$i]['filename']);
		
	}
	
	sort($requiredSizes);

}


function getFirstNum($str){
	$numbers = explode('x', $str);
	if(count($numbers) > 0){
		return intval($numbers[0]);
	}
	return 0;
}

?>