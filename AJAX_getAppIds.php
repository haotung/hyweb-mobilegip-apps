<?PHP
include_once('config.inc.php');

use google\appengine\api\users\User;
use google\appengine\api\users\UserService;

$user = UserService::getCurrentUser();



$appLimited = '';
if(isset($user)){
	$userEmail = $user->getEmail();
	if($userEmail == 'ksjeng.pmp@gmail.com'){
		$appLimited = 'GIP_CSU_Ent';
	}
}
else{
	$appLimited = 'XXXXXXXXXXXXX';	
}

$appIdCondition = '';
if(isset($_GET['appId'])){
	$appIdCondition = " And app_id like '".$_GET['appId']."%' ";
}
else if(strlen($appLimited) > 0){
	$appIdCondition = " And app_id = '".$appLimited."' ";
}

$debugMsg = array();
$Apps = array();
$AppIdIndex = array();
$query = "Select app_id, name, note, hash_header "
		."From app "
		."Where 1 "
		.$appIdCondition
		."Order by app_id ";
$rs = $db->Execute($query);
$i = 0;
$debugMsg[] = $query;

$smallNum = pow(16, 7) + 1;
$largeNum = pow(16, 7) * 16 - 1;
while($row = $rs->FetchRow()){
	$hashHeader = dechex(rand($smallNum, $largeNum));
	//$db->Execute("Update app set hash_header = '".$hashHeader."' Where app_id = '".$row['app_id']."' ");
	
	$Apps[] = array('AppId' => $row['app_id'], 'name' => $row['name'], 'note' => $row['note'], 'accounts' => array(), 'hashHeader' => $row['hash_header']);
	$AppIdIndex[$row['app_id']] = $i++;
}


for($i = 0; $i < count($Apps); $i++){

	$appId = $Apps[$i]['AppId'];


}

$Apps[] = array('app_id' => 'XX_debug', 'name' => 'debug', 'note' => 'debug', 'accounts' => array(), 'msg' => $debugMsg);
header('Content-Type: application/json; charset=utf-8');
echo json_encode($Apps);




?>