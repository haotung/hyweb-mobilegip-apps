<?PHP
include_once('config.inc.php');
$appId = getParamPrecisely('app_id');
$versionCode = getParamPrecisely('version_code');
$OS = getParamPrecisely('OS');
$build = getParamPrecisely('build');
$response = array('versionCode' => 0);

if($appId !== false && $versionCode !== false && $OS !== false && $build !== false){
	$query = "Select version_code, menu_xml "
		."From menu "
		."Where app_id = '".$appId."' "
		."And version_code > ".$versionCode." "
		."And hidden = 0 ";
	if($OS == 'iOS'){
		$query .= "And ios_required_build <= ".$build." ";
	}
	else if($OS == 'Android'){
		$query .= "And android_required_build <= ".$build." ";	
	}
	else{
		$query .= "And 1 = 0 ";	
	}
	
	$query .= " Order by version_code desc ";
	
	//$response['query'] = $query;
	$rs = $db->Execute($query);
	if($row = $rs->FetchRow()){
		$testXMLStr = $row['menu_xml'];
		$xml = simplexml_load_string($testXMLStr);
		if($xml !== false){
			$response['versionCode'] = $row['version_code'];
			$response['xml'] = $row['menu_xml'];	
		}
	}
}

echo json_encode($response);


function getParamPrecisely($paramName){
	if(array_key_exists($paramName, $_GET)){
		return $_GET[$paramName];	
	}	
	else{
		return false;
	}
}

?>