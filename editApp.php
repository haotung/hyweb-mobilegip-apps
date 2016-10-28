<?PHP
include_once('config.inc.php');
$progName = 'Mobile GIP App資料管理';

if(!array_key_exists('appId', $_GET)){
	header("Location: /admin");
}

$progPath = '/editApp.php?appId='.$_GET['appId'];
$logoutBackPath = '/admin';
include_once('authority_check.php');



//準備metadata
$query = "Select * "
		."From app "
		."Where app_id = '".$_GET['appId']."' ";
$rs = $db->Execute($query);

if($row = $rs->FetchRow()){
	$appMetadata = $row;
}

//讀取已上傳的圖

$basicImages = getFiles('basic');
$hylibFiles = getFiles('hylib');


//HyLib settings

$testAccounts = '[]';
$relatedLinks = '[]';
$useLBS = 'false';
$useSpecifiedBooks = 'false';
$displayNews = 'false';

$query = "Select attr_name, attr_values "
		."From app_additional_attrs "
		."Where app_id = '".$_GET['appId']."' ";
$rs = $db->Execute($query);
while($row = $rs->FetchRow()){
	if(strcmp($row['attr_name'], 'testAccounts') === 0){
		$testAccounts = $row['attr_values'];	
	}
	else if(strcmp($row['attr_name'], 'relatedLinks') === 0){
		$relatedLinks = $row['attr_values'];	
	}
	else if(strcmp($row['attr_name'], 'useLBS') === 0){
		$useLBS = (strcmp('true', strtolower($row['attr_values'])) === 0)?'true':'false';	
	}
	else if(strcmp($row['attr_name'], 'useSpecifiedBooks') === 0){
		$useSpecifiedBooks =(strcmp('true', strtolower($row['attr_values'])) === 0)?'true':'false';	
	}
	else if(strcmp($row['attr_name'], 'displayNews') === 0){
		$displayNews =(strcmp('true', strtolower($row['attr_values'])) === 0)?'true':'false';	
	}
}




/*
$query = "Select act.item_name, act.width, act.height, act.description, act.content_type, ifnull(af.filename, '') as filename "
		."From app_content_file_type as act "
		."Left Join app_content_file as af "
		."On af.item_name = act.item_name "
		."And af.app_id = '".$_GET['appId']."' "
		."Where act.purpose = 'hylib' "
		."Order by act.item_name ";
$rs = $db->Execute($query);
$imgContainerHeight = 200;
while($row = $rs->FetchRow()){
	$row['containerHeight'] = $imgContainerHeight;
	$row['containerWidth'] = round($row['width'] * ($imgContainerHeight / $row['height']));
	$row['contentTypeName'] = getContentTypeName($row['content_type']);
	$row['imageSize'] = (strpos($row['content_type'], 'image') === 0)?($row['width'].' x '.$row['height']):'';
	$hylibFiles[] = $row;	
}
*/



$smarty->assign("appMetadata",$appMetadata);
$smarty->assign("basicImages",$basicImages);
$smarty->assign("hylibFiles",$hylibFiles);
$smarty->assign("progName",$progName);
$smarty->assign("nickname",$nickname);
$smarty->assign("logoutUrl",$logoutUrl);
$smarty->assign("testAccounts", $testAccounts);
$smarty->assign("relatedLinks",$relatedLinks);
$smarty->assign("useLBS",$useLBS);
$smarty->assign("useSpecifiedBooks",$useSpecifiedBooks);
$smarty->assign("displayNews",$displayNews);
$smarty->assign("appId", $_GET['appId']);
$smarty->display("editApp.tpl.html");


function getFiles($purpose){
	global $db;
	$files = array();
	$query = "Select act.item_name, act.width, act.height, act.description, act.content_type, (act.content_type like 'image/%') as is_image, ifnull(af.filename, '') as filename "
			."From app_content_file_type as act "
			."Left Join app_content_file as af "
			."On af.item_name = act.item_name "
			."And af.app_id = '".$_GET['appId']."' "
			."Where act.purpose = '".$purpose."' "
			."Order by act.item_name ";
	$rs = $db->Execute($query);
	$imgContainerHeight = 200;
	while($row = $rs->FetchRow()){
		$row['containerHeight'] = $imgContainerHeight;
		$row['containerWidth'] = round($row['width'] * ($imgContainerHeight / $row['height']));
		$row['mediaType'] = getMediaType($row['media_type']);
		$row['contentTypeName'] = getContentTypeName($row['content_type']);
		$row['imageSize'] = (strpos($row['content_type'], 'image') === 0)?($row['width'].' x '.$row['height']):'';
		$files[] = $row;	
	}
	return $files;
}

function getMediaType($contentType){
	$parts = split('/', $contentType);
	return $parts[0];	
}

function getContentTypeName($contentType){
	if($contentType == 'text/html'){
		return "網頁檔";	
	}
	else{
		return "圖檔";
		
	}
	
}

?>