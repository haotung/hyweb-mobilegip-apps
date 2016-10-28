<?PHP
include_once('header.inc.php');
$result['error_code'] = 0;

if(!array_key_exists('hospital_id', $_GET)){
	$result['error_code'] = 303;
}
else{
	$hospital_id = $_GET['hospital_id'];
	if(!array_key_exists('cur_version', $_GET)){
		$cur_version = 0;
	}
	else{
		$cur_version = $_GET['cur_version'];
	}
	
	$query = "Select version_code, consultant_list_json "
			."From all_consultant_cache "
			."Where hspt_id = ".$hospital_id." "
			."And version_code > ".$cur_version." "
			."Order by version_code desc "
			."Limit 1 ";
	$rs = $db->Execute($query);
	if($row = $rs->FetchRow()){
		$result['version_code'] = $row['version_code'];
		$result['depts_in_hospital'] = json_decode($row['consultant_list_json'], true);
	}
}


echo json_encode($result);
$db->Close();



?>
