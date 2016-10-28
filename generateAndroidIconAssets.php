<?PHP
include_once('config.inc.php');
include_once('zip.lib.php');
use google\appengine\api\cloud_storage\CloudStorageTools;
if(!array_key_exists('appId', $_GET)){
	die();	
}

$logStr = '';
$appId = $_GET['appId'];
$zipFileName = $appId.'_android_project_files.zip';
$zip = new ZipFile();
ini_set('memory_limit', '512M');
set_time_limit(120);
$additionalAttrs = array();
main();

function main(){
	global $zip, $zipFileName, $logStr;
	
	if(isParamValueYes('appIcon')) 
		addAppIcon();
	if(isParamValueYes('assets'))
		addAppData();
	if(isParamValueYes('hylibXml'))
		addHyLibXMLs();
	if(isParamValueYes('googlePlayFiles'))
		addGooglePlayFiles();
		
	$zip->addFile($logStr, '/log.txt');
	$zipContent = $zip->file();
	//echo strlen($zipContent);
	
	header('Content-Type: application/zip');
	header('Content-Length: ' . strlen($zipContent));
	header('Content-Disposition: attachment; filename="'.$zipFileName.'"');
	echo $zipContent;
	
}

function loadAdditionalAttrs(){
	global $additionalAttrs, $db;
	$additionalAttrs['useLBS'] = false;	
	$additionalAttrs['specifiedBooks'] = false;
	$query = "Select attr_name, attr_values "
			."From app_additional_attrs "
			."Where app_id = '".$_GET['appId']."' ";
	$rs = $db->Execute($query);
	while($row = $rs->FetchRow()){
		$attrName = $row['attr_name'];
		$attrValues = $row['attr_values'];
		if(strcmp($attrName, 'useLBS') === 0){
			$additionalAttrs['useLBS'] = (strcmp(strtolower($attrValues), 'true') === 0);
		}
		else if(strcmp($attrName, 'useSpecifiedBooks') === 0){
			$additionalAttrs['useSpecifiedBooks'] = (strcmp(strtolower($attrValues), 'true') === 0);
		}
	} 
}

function addGooglePlayFiles(){
	global $zip, $db;
	
	//512x512 App icon
	$appId = $_GET['appId'];
	$query = "Select icon_file_name "
			."From app "
			."Where app_id = '".$appId."' ";
	$rs = $db->Execute($query);
	if($row = $rs->FetchRow()){
		$iconFileName = $row['icon_file_name'];
	}
			
	if(strlen($iconFileName) > 0){
		$iconImg = new Imagick();
		$fileUrl = 'gs://hyweb-mobilegip-apps/app_icon/'.$iconFileName;
		$oriImageBlob = file_get_contents($fileUrl);
		$iconImg->readimageblob($oriImageBlob);
		$iconImg->resizeImage(512, 512, imagick::FILTER_CATROM, 1); 
		$iconImg->setImageFormat('png');
		$zip->addFile($iconImg->getimageblob(), 'google-play/web_high_res_icon.png');
	}
	
	
	//Google Play Theme (1024x500)
	$query = "Select item_name, filename "
			."From app_content_file "
			."Where app_id = '".$appId."' "
			."And item_name = 'google_play_theme' ";
	$rs = $db->Execute($query);
	if($row = $rs->FetchRow()){	
		$fileUrl = 'gs://hyweb-mobilegip-apps/content_files/'.$row['filename'];
		$oriImageBlob = file_get_contents($fileUrl);
		$launchImg = new Imagick();
		$launchImg->readimageblob($oriImageBlob);
		$launchImg->setImageFormat('png');
		$zip->addFile($launchImg->getimageblob(), 'google-play/theme.png');
	}
	
}

function addAppIcon(){
	global $zip, $db;
	$appId = $_GET['appId'];
	$outputFileName = $_GET['outputFileName'];
	if(strlen($outputFileName) == 0){
		$outputFileName = 'icon';	
	}
	$iconFileName = '';
	$query = "Select icon_file_name "
			."From app "
			."Where app_id = '".$appId."' ";
	$rs = $db->Execute($query);
	if($row = $rs->FetchRow()){
		$iconFileName = $row['icon_file_name'];
	}
			
	if(strlen($iconFileName) > 0){
		
		$fileUrl = 'gs://hyweb-mobilegip-apps/app_icon/'.$iconFileName;
		$oriImageBlob = file_get_contents($fileUrl);
		
		$dpis[] = array('folder' => 'drawable-ldpi', 'size' => 36);
		$dpis[] = array('folder' => 'drawable-mdpi', 'size' => 48);
		$dpis[] = array('folder' => 'drawable-hdpi', 'size' => 72);
		$dpis[] = array('folder' => 'drawable-xhdpi', 'size' => 96);
		$dpis[] = array('folder' => 'drawable-xxhdpi', 'size' => 144);
		$dpis[] = array('folder' => 'drawable-xxxhdpi', 'size' => 192);
		
		$iconImg = new Imagick();
		
		$dpisCount = count($dpis);
		for($i = 0; $i < $dpisCount; $i++){
			$pathInZip = '/res/'.$dpis[$i]['folder'].'/';
			$size = $dpis[$i]['size'];
			
			$iconImg->readimageblob($oriImageBlob);
			$iconImg->resizeImage($size, $size, imagick::FILTER_CATROM, 1); 
			$imgFileName = $outputFileName.'.png';
			$iconImg->setImageFormat('png');
			$zip->addFile($iconImg->getimageblob(), $pathInZip.$imgFileName);
		}
		//Web512
		/*
		//addGooglePlayFiles()
		$iconImg->readimageblob($oriImageBlob);
		$iconImg->resizeImage(512, 512, imagick::FILTER_CATROM, 1); 
		$iconImg->setImageFormat('png');
		$zip->addFile($iconImg->getimageblob(), 'google-play/web_high_res_icon.png');
		*/
		
		$zipContent = $zip->file();
		
		/*
		$options = ['gs' => ['Content-Type' => 'application/zip']];
		$ctx = stream_context_create($options);
		file_put_contents('gs://hyweb-mobilegip-apps/zip_temp/'.date('YmdHis').'.zip', $zipContent, 0, $ctx);
		*/
		
		
	}
}

function addAppData(){	
	global $db, $zip;
	$appId = $_GET['appId'];
	$appdataPath = '/'.strtolower($appId).'/';
	
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
		$zip->addFile($fileBlob, $appdataPath.'html/'.$outputFileName);
	}
	
	//images
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
		$fileUrl = 'gs://hyweb-mobilegip-apps/app_icon/'.$iconFileName;
		$oriImageBlob = file_get_contents($fileUrl);
		
		$iconImg = new Imagick();
		$size = 152;
		$iconImg->readimageblob($oriImageBlob);
		$iconImg->resizeImage($size, $size, imagick::FILTER_CATROM, 1);
		$iconImg->setImageFormat('png');
		$zip->addFile($iconImg->getimageblob(), $appdataPath.'images/app_icon.png');
	}
	
	//drawable
	
	$query = "Select item_name, filename "
			."From app_content_file "
			."Where app_id = '".$appId."' "
			."And item_name like 'ios_launch_image%' ";
	$rs = $db->Execute($query);
	while($row = $rs->FetchRow()){
		$drawablePath = '';
		if(strcmp($row['item_name'], 'ios_launch_image_2x3') === 0){
			$drawablePath = 'drawable-hdpi';
		}
		else if(strcmp($row['item_name'], 'ios_launch_image_9x16') === 0){
			$drawablePath = 'drawable-xxhdpi';
			
		}
		if(strlen($drawablePath) > 0){
			$fileUrl = 'gs://hyweb-mobilegip-apps/content_files/'.$row['filename'];
			$oriImageBlob = file_get_contents($fileUrl);
			$launchImg = new Imagick();
			$launchImg->readimageblob($oriImageBlob);
			$launchImg->setImageFormat('png');
			$zip->addFile($launchImg->getimageblob(), 'res/'.$drawablePath.'/bg01.png');
		}
	}
	
	
}

function addHyLibXMLs(){
	global $db, $zip, $logStr;
	loadAdditionalAttrs();
	$appId = $_GET['appId'];
	
	//AndroidManifest.xml
	$fileUrl = 'gs://hyweb-mobilegip-apps/sample_xml/HyLib_standard_sample_AndroidManifest.xml';
	$xmlContent = file_get_contents($fileUrl);
	$xml = simplexml_load_string($xmlContent);
	if($xml !== false){
		$packageName = 'hyweb.mobilegip.'.strtolower($appId);
		$xml['package'] = $packageName;
		$xml['android:versionCode'] = '1';
		$xml['android:versionName'] = '1.0';
		$xml->application[0]->receiver[0]->{"intent-filter"}[0]->category[0]['android:name'] = $packageName;
		$logStr .= 'uses-permission count = '.count($xml->{"uses-permission"})."\n";
		$logStr .= 'permission count = '.count($xml->{permission})."\n";
		$usesPermissionCount = count($xml->{"uses-permission"});
		$permissionCount = count($xml->{"permission"});
		$xml->addChild('uses-permission');
		$xml->addChild('permission');
		$xml->{"uses-permission"}[$usesPermissionCount]['android:name'] = $packageName.'.permission.C2D_MESSAGE';
		$xml->{"permission"}[$permissionCount]['android:name'] = $packageName.'.permission.C2D_MESSAGE';
		$xml->{"permission"}[$permissionCount]['android:protectionLevel'] = 'signature';
		$zip->addFile($xml->asXML(), '/AndroidManifest.xml');
	}
	
	//application.xml
	$fileUrl = 'gs://hyweb-mobilegip-apps/sample_xml/HyLib_standard_sample_application.xml';
	$xmlContent = file_get_contents($fileUrl);
	$xml = simplexml_load_string($xmlContent);
	if($xml !== false){
		
		$query = "Select * From app where app_id = '".$appId."' ";
		$rs = $db->Execute($query);
		if($row = $rs->FetchRow()){
			$domDoc = dom_import_simplexml($xml);
			$stringNodes = $domDoc->getElementsByTagName('string');
			$stringNodeCount = $stringNodes->length;
			
			$dbMapXML['data_source'] = $row['hylib_url'];
			$dbMapXML['website_cp'] = $row['gip_cp'];
			$dbMapXML['gcm_sender_id'] = $row['gcm_key'];
			$dbMapXML['gcm_application_id'] = $row['app_id'];
			$dbMapXML['assets_folder'] = strtolower($appId);
			$dbMapXML['isOpenLBS'] = $additionalAttrs['useLBS']?'true':'false';
			for($i = 0; $i < $stringNodeCount; $i++){
				$stringNode = $stringNodes->item($i);
				$key = $stringNode->getAttribute('name');	
				$logStr .= 'attr name = '.$key."\n";
				if(array_key_exists($key, $dbMapXML)){
					$logStr .= 'setText = '.$dbMapXML[$key]."\n";
					$stringNode->nodeValue = $dbMapXML[$key];
				}
			}
		
			$resultXml = simplexml_import_dom($domDoc);
			
			$zip->addFile($resultXml->asXML(), 'res/values/application.xml');
			
		}
	}
	
	//hylib_application.xml
	$fileUrl = 'gs://hyweb-mobilegip-apps/sample_xml/HyLib_standard_sample_hylib_application.xml';
	$xmlContent = file_get_contents($fileUrl);
	$xml = simplexml_load_string($xmlContent);
	if($xml !== false){
		$domDoc = dom_import_simplexml($xml);
		$stringNodes = $domDoc->getElementsByTagName('string');
		$stringNodeCount = $stringNodes->length;
		for($i = 0; $i < $stringNodeCount; $i++){
			$stringNode = $stringNodes->item($i);
			$key = $stringNode->getAttribute('name');	
			if(strcmp($key, 'show_teacher_zone') === 0){
				$stringNode->nodeValue = $additionalAttrs['useSpecifiedBooks']?'true':'false';
				break;
				
			}
		}
		$resultXml = simplexml_import_dom($domDoc);	
		$zip->addFile($resultXml->asXML(), 'res/values/hylib_application.xml');
	}
	
	
	//strings.xml
	$fileUrl = 'gs://hyweb-mobilegip-apps/sample_xml/HyLib_standard_sample_strings.xml';
	$xmlContent = file_get_contents($fileUrl);
	$xml = simplexml_load_string($xmlContent);
	if($xml !== false){
		$query = "Select display_name "
				."From app "
				."Where app_id = '".$appId."' ";
		$rs = $db->Execute($query);
		if($row = $rs->FetchRow()){
			$displayName = $row['display_name'];	
			$domDoc = dom_import_simplexml($xml);
			$stringNodes = $domDoc->getElementsByTagName('string');
			$stringNodeCount = $stringNodes->length;
			for($i = 0; $i < $stringNodeCount; $i++){
				$stringNode = $stringNodes->item($i);
				$key = $stringNode->getAttribute('name');	
				if(strcmp($key, 'app_name') === 0){
					$stringNode->nodeValue = $displayName;
					break;
				}
			}
			$resultXml = simplexml_import_dom($domDoc);	
			$zip->addFile($resultXml->asXML(), 'res/values/strings.xml');
			
		}
		
	}

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



?>