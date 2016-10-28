<?PHP
//include_once('config.inc.php');
require_once 'datastore_config.php';
require_once 'GoogleDatastoreService.php';



$DS = new GoogleDatastoreService($google_api_config);
ini_set('memory_limit', '1024M');

$gql = "select * from device where app_id = 'HyLib_NTTU' and user_id = @1 ";
$entities = $DS->queryEntitiesWithGQL($gql, array(array("value" => array("stringValue" => 'hylibuser'))));
$entityCount = count($entities);
$rows = array();
for($i = 0; $i < $entityCount; $i++){
	$entityRow[] = $DS->entityJSONToRowFormat($entities[$i]);
	$rows[] = array('token_id' => $entityRow['token_id'], 'user_id' => $entityRow['user_id']);
}

echo count($rows);



?>