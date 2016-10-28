<?PHP
require_once('config.inc.php');
$d1 = new DateTime();
syslog(LOG_DEBUG, $d1->format("Y-m-d H:i:s.u"));
$appId = 'HyLib_NTTU';	

if(array_key_exists('appId', $_GET)){
	$appId = $_GET['appId'];
}




$d2 = new DateTime();
syslog(LOG_DEBUG, $d2->format("Y-m-d H:i:s.u"));

$userIds = array();
if(isset($datastore)){
	$gql = "select distinct user_id from device where app_id = '".$appId."' and user_id > '' order by user_id ";
	syslog(LOG_DEBUG, "gql = ".$gql);
	$query = $datastore->gqlQuery($gql, ['allowLiterals' => true]);
	$entities = $datastore->runQuery($query);
	foreach ($entities as $device) {
		$userIds[] = $device['user_id']; 
	}

}

$d3 = new DateTime();
syslog(LOG_DEBUG, $d3->format("Y-m-d H:i:s.u"));
header('Content-Type: application/json; charset=utf-8');
echo json_encode($userIds);



?>