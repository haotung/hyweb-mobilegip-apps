<?PHP
require_once 'config.inc.php';

$gql = "Select * from device where app_id = 'HyLib_NTTU' and OS = 'iOS' and user_id = 'hylibuser' ";
syslog(LOG_DEBUG, 'start fetch');
for($i = 0; $i < 5; $i++){
	$query = $datastore->gqlQuery($gql, ['allowLiterals' => true]);
	$deviceEntity = $datastoreTool->fetchOne($query);
	if($deviceEntity != null){
		echo $deviceEntity['token_id']."<br/>\n";
	}
	// $deviceEntities = $datastore->runQuery($query);



	// foreach($deviceEntities as $device){
	// 	//syslog(LOG_DEBUG, $device['token_id']);
	// }
}

syslog(LOG_DEBUG, 'end fetch');


/*
$entities = array();
for($i = 1; $i <= 5; $i++){
	$kvs = array();
	$kvs[] = array('key' => 'token', 'value' => 'token'.$i, 'type' => PropertyType::String);
	$kvs[] = array('key' => 'time', 'value' => date('Y-m-d H:i:s'), 'type' => PropertyType::DateType);
	$entity = $DS->buildEntity('test_push_log', $kvs, 'device'.$i);	
	$entities[] = $entity;
}

$ids = $DS->saveEntities($entities, false);
echo json_encode($ids);
*/

//$DS->saveEntity('test_push_log', $kvs);
//echo "after new GDS\n";



?>