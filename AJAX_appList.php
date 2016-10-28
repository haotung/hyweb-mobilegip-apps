<?PHP
include_once('config.inc.php');
$appIdCondition = '';
if(isset($_GET['appId'])){
	$appIdCondition = " And app_id = '".$_GET['appId']."' ";
}
$Apps = array();
$AppIdIndex = array();
$query = "Select app_id, name, note, hash_header "
		."From app "
		."Where 1 "
		.$appIdCondition
		."Order by app_id ";
$rs = $db->Execute($query);
$i = 0;

$smallNum = pow(16, 7) + 1;
$largeNum = pow(16, 7) * 16 - 1;
while($row = $rs->FetchRow()){
	$hashHeader = dechex(rand($smallNum, $largeNum));
	//$db->Execute("Update app set hash_header = '".$hashHeader."' Where app_id = '".$row['app_id']."' ");
	
	$Apps[] = array('AppId' => $row['app_id'], 'name' => $row['name']);
}

echo json_encode($Apps);
?>