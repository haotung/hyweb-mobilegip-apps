<?PHP
include_once('config.inc.php');
header("Content-Type:application/json; charset=utf-8");
$result['success'] = false;
if(!array_key_exists('ifid', $_POST)){
	$result['errorMsg'] = '未知的版號';
	die(json_encode($result));	
}


$query = "Select store_file_name "
		."From app_installation_file "
		."Where ifid = ".$_POST['ifid']." ";
$rs = $db->Execute($query);
if($row = $rs->FetchRow()){
	$storePath = 'gs://mobilegip-app-download/app_installation/'.$row['store_file_name'];
	unlink($storePath);
	$query = "Delete from app_installation_file "
			."Where ifid = ".$_POST['ifid']." ";
	$db->Execute($query);	
}
$result['success'] = true;
echo json_encode($result);


?>