<?PHP
include_once('config.inc.php');
$AppIdIndex = array();
$query = "Select * "
		."From app "
		."Where app_id = '".$_GET['appId']."' ";
$rs = $db->Execute($query);

if($row = $rs->FetchRow()){
	echo json_encode($row);
}
else{
	echo json_encode(array());	
}
?>