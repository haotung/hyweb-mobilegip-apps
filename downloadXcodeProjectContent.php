<?PHP
include_once('config.inc.php');
include_once('zip.lib.php');
use google\appengine\api\cloud_storage\CloudStorageTools;
if(!array_key_exists('appId', $_GET)){
	die();	
}
$appId = $_GET['appId'];
$zip = new ZipFile();
set_time_limit(120);
ini_set('memory_limit', '512M');
main();


function main(){
	global $zip;
	
	if(isParamValueYes('hylibLoginBG')) 
		addLoginBG();
	if(isParamValueYes('appIcon')) 
		addAppIcon();
	if(isParamValueYes('launchImage')) 
		addLaunchImage(isParamValueYes('iPhone6'), isParamValueYes('iPad'));
	if(isParamValueYes('appDataFolder'))
		addAppData();
	$zipContent = $zip->file();
	//echo strlen($zipContent);
	
	header('Content-Type: application/zip');
	header('Content-Length: ' . strlen($zipContent));
	header('Content-Disposition: attachment; filename="'.$_GET['appId'].'_XcodeContent.zip"');
	echo $zipContent;
	
	
}

function isParamValueYes($paramName){
	if(array_key_exists($paramName, $_GET)){
		if(strcmp($_GET[$paramName], 'yes') === 0){
			return true;	
		}
	}
	else{
		return false;	
	}	
}

function addAppData(){
	global $zip, $db, $appId;
	$query = "SELECT app_content_file.item_name, app_content_file.filename "
			."FROM app_content_file, `app_content_file_type` "
			."Where app_content_file.item_name = app_content_file_type.item_name "
			."And app_content_file.app_id = '".$appId."' "
			."And app_content_file_type.content_type = 'text/html' ";
			
	$oriFileNameMap['cp_privacy_html'] = 'cp_privacy.html';	
	$oriFileNameMap['cp_sys_info_html'] = 'cp_sysInfo.html';	
	$rs = $db->Execute($query);
	while($row = $rs->FetchRow()){
		$itemName = $row['item_name'];
		$storeFileName = $row['filename'];
		$outputFileName = $oriFileNameMap[$itemName];
		$fileUrl = 'gs://hyweb-mobilegip-apps/content_files/'.$storeFileName;
		if(!file_exists($fileUrl)){
			continue;	
		}
		$fileBlob = file_get_contents($fileUrl);	
		$zip->addFile($fileBlob, '/appdata/html/'.$outputFileName);
	}	
	
}

function addLoginBG(){
	global $zip, $db;
	$folder = '/LoginBG';
	$processTypes[] = array('imageType' => 'ios_hylib_login_background_2x3',
							'outputFiles' => array(
								array('width' => 320, 'height' => 480, 'filepath' => '/HyLibLoginFormBG.png'),
								array('width' => 640, 'height' => 960, 'filepath' => '/HyLibLoginFormBG@2x.png')
							)
					  );
	$processTypes[] = array('imageType' => 'ios_hylib_login_background_9x16',
							'outputFiles' => array(
								array('width' => 640, 'height' => 1136, 'filepath' => '/HyLibLoginFormBG-568h@2x.png'),
								array('width' => 750, 'height' => 1334, 'filepath' => '/HyLibLoginFormBG-375w@2x.png'),
								array('width' => 1242, 'height' => 2208, 'filepath' => '/HyLibLoginFormBG-414w@3x.png'),
								array('width' => 750, 'height' => 1334, 'filepath' => '/HyLibLoginFormBG-667h@2x.png'),
								array('width' => 1242, 'height' => 2208, 'filepath' => '/HyLibLoginFormBG-736h@3x.png')
							)
					  );
	for($t = 0; $t < count($processTypes); $t++){
		$type = $processTypes[$t];
		$imageType = $type['imageType'];
		
		$query = "Select filename "
				."From app_content_file "
				."Where app_id = '".$_GET['appId']."' "
				."And item_name = '".$imageType."' ";
		$rs = $db->Execute($query);
		if($row = $rs->FetchRow()){
			$sourceFilename = $row['filename'];
			$fileUrl = 'gs://hyweb-mobilegip-apps/content_files/'.$sourceFilename;
			if(!file_exists($fileUrl)){
				continue;	
			}
			$oriImageBlob = file_get_contents($fileUrl);	
			$outputFiles = $type['outputFiles'];
			for($i = 0; $i < count($outputFiles); $i++){
				$file = $outputFiles[$i];
				$width = $file['width'];
				$height = $file['height'];
				$filepath = $file['filepath'];
				
				try{	
					$image = new Imagick();
					$image->readImageBlob($oriImageBlob);
				}
				catch(Exception $ex){
					break;	
				}
			
				$geo = $image->getImageGeometry(); 
				$sourceWidth = $geo['width'];
				$sourceHeight = $geo['height'];
				if($sourceWidth != $width || $sourceHeight != $height){
					$image->resizeImage($width, $height, imagick::FILTER_CATROM, 1); 
				}
				$image->setImageFormat('png');
				$zip->addFile($image->getimageblob(), $folder.$filepath);
				//echo $folder.$filepath." added.<br/>\n";
			}
		}
	}
}


function addLaunchImage($genIPhone6, $genIPad){
	global $zip, $db;
	$appId = $_GET['appId'];
	$dirName = 'Images_'.$appId.'.xcassets/'.$appId."_LaunchImage.launchimage";
	
	$query = "Select item_name, filename "
			."From app_content_file "
			."Where app_id = '".$appId."' ";
	$rs = $db->Execute($query);
	$filenames = array();
	while($row = $rs->FetchRow()){
		$filenames[$row['item_name']] = $row['filename'];	
	}
	
	
	$sampleJSONPath = 'gs://hyweb-mobilegip-apps/definition_files/xcode_launch_image_assets.json';
	$jsonContentStr = file_get_contents($sampleJSONPath);
	$jsonObj = json_decode($jsonContentStr, true);
	$images = $jsonObj['images'];
	$exists_files = array();
	for($i = 0; $i < count($images); $i++){
		$size = getLaunchImageSize($images[$i]);
		$filename = $size['filename'];
		$jsonObj['images'][$i]['filename'] = $filename;	
		if(strlen($filename) > 0){
			if(!array_key_exists('image_source', $size)){
				$jsonObj['images'][$i]['filename'] = '';
				continue;
			}
			if(array_key_exists($filename, $exists_files)){
				//此尺寸檔案已加入zip檔，可跳過處理，節省運算量
				continue;	
			}
			//如不需iPhone 6則跳過
			if((!$genIPhone6) && (strcmp(substr($size['device'], 0, 7), 'iphone6') === 0)){
				continue;	
			}
			//如不需iPad則跳過
			if((!$genIPad) && (strcmp($size['device'], 'ipad') === 0)){
				continue;	
			}
			
			$fileUrl = 'gs://hyweb-mobilegip-apps/content_files/'.$filenames[$size['image_source']];
			if(!file_exists($fileUrl)){
				$jsonObj['images'][$i]['filename'] = '';
				continue;	
			}
			$oriImageBlob = file_get_contents($fileUrl);		
			$image = new Imagick();
			try{
				$image->readImageBlob($oriImageBlob);
			}
			catch(Exception $ex){
				$jsonObj['images'][$i]['filename'] = '';
				continue;	
			}
		
			$image->resizeImage($size['width'], $size['height'], imagick::FILTER_CATROM, 1); 
			$image->setImageFormat('png');
			$zip->addFile($image->getimageblob(), $dirName.'/'.$filename);
			//echo $filename." added.<br/>\n";
			$exists_files[$filename] = 1;
		}
	}
	$zip->addFile(json_encode($jsonObj, JSON_PRETTY_PRINT), $dirName.'/Contents.json');
	
	
}

function getLaunchImageSize($image){
	$rule[] = array('idiom' => 'ipad', 'subtype' => '', 'scale' => '1x', 'width' => 768, 'height' => 1024, 'aspect_ratio' => '3x4', 'device' => 'ipad');
	$rule[] = array('idiom' => 'ipad', 'subtype' => '', 'scale' => '2x', 'width' => 1536, 'height' => 2048, 'aspect_ratio' => '3x4', 'device' => 'ipad');
	$rule[] = array('idiom' => 'ipad', 'subtype' => 'pro', 'scale' => '2x', 'width' => 2048, 'height' => 2732, 'aspect_ratio' => '3x4', 'device' => 'ipad');
	$rule[] = array('idiom' => 'iphone', 'subtype' => '667h', 'scale' => '2x', 'width' => 750, 'height' => 1334, 'aspect_ratio' => '9x16', 'device' => 'iphone6');
	$rule[] = array('idiom' => 'iphone', 'subtype' => '736h', 'scale' => '3x', 'width' => 1242, 'height' => 2208, 'aspect_ratio' => '9x16', 'device' => 'iphone6 plus');
	$rule[] = array('idiom' => 'iphone', 'subtype' => 'retina4', 'scale' => '2x', 'width' => 640, 'height' => 1136, 'aspect_ratio' => '9x16', 'device' => 'iphone5');
	$rule[] = array('idiom' => 'iphone', 'subtype' => '', 'scale' => '1x', 'width' => 320, 'height' => 480, 'aspect_ratio' => '2x3', 'device' => 'iphone');
	$rule[] = array('idiom' => 'iphone', 'subtype' => '', 'scale' => '2x', 'width' => 640, 'height' => 960, 'aspect_ratio' => '2x3', 'device' => 'iphone');
	$idiom = $image['idiom'];
	$subtype = (array_key_exists('subtype', $image))?($image['subtype']):'';
	$scale = $image['scale'];
	for($i = 0; $i < count($rule); $i++){
		if(strcmp($rule[$i]['idiom'], $idiom) === 0
		&& strcmp($rule[$i]['subtype'], $subtype) === 0
		&& strcmp($rule[$i]['scale'], $scale) === 0){
			$size = array('width' => $rule[$i]['width'], 'height' => $rule[$i]['height']);
			$size['image_source'] = ((strcmp($idiom, 'ipad') === 0)?'ipad':'ios').'_launch_image_'.$rule[$i]['aspect_ratio'];
			//echo 'image_source = '.$size['image_source']."<br/>\n";
			$size['filename'] = $idiom.subtypeToFileTail($subtype).((strcmp($scale, '1x') === 0)?'':'@'.$scale).'.png';
			$size['device'] = $rule[$i]['device'];
			return $size;	
		}
	}
	
	$size = array('filename' => '');
	return $size;
}

function subtypeToFileTail($subtype){
	if(strlen($subtype) == 0){
		return '';	
	}	
	else if(strcmp($subtype, 'retina4') === 0){
		return '-568h';	
	}
	else{
		return '-'.$subtype;	
	}
}

function addAppIcon(){
	global $zip, $db;
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
		$fileUrl = 'gs://hyweb-mobilegip-apps/app_icon/'.$iconFileName;
		if(!file_exists($fileUrl)){
			return;
		}
		$ext = strtolower(pathinfo($fileUrl, PATHINFO_EXTENSION));
		$oriImageBlob = file_get_contents($fileUrl);
		
		$requiredSizes = parseSampleJson($ext, $jsonObj);
		$requiredSizes[] = 1024;	//上架用
		
		$iconImg = new Imagick();
		$dirName = $appId."_AppIcon.appiconset";
		$pathInZip = 'Images_'.$appId.'.xcassets/'.$dirName.'/';
		$requiredSizeCount = count($requiredSizes);
		for($i = 0; $i < $requiredSizeCount; $i++){
			$size = $requiredSizes[$i];
			$iconImg->readimageblob($oriImageBlob);
			$iconImg->resizeImage($size, $size, imagick::FILTER_CATROM, 1); 
			$imgFileName = 'icon'.$size.'.'.$ext;
			$zip->addFile($iconImg->getimageblob(), $pathInZip.$imgFileName);
		}
		
		$zip->addFile(json_encode($jsonObj, JSON_PRETTY_PRINT), $pathInZip.'Contents.json');
	}
	
}

function parseSampleJson($ext, &$jsonObj){
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
	return $requiredSizes;
}


function getFirstNum($str){
	$numbers = explode('x', $str);
	if(count($numbers) > 0){
		return intval($numbers[0]);
	}
	return 0;
}



?>