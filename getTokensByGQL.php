<?PHP
require_once 'datastore_config.php';
require_once 'GoogleDatastoreService.php';

$DS = new GoogleDatastoreService($google_api_config);
syslog(LOG_DEBUG, 'gql = '.$_GET['GQL']);
$entities = $DS->queryEntitiesWithGQL($_GET['GQL']);
$entityCount = count($entities);
$tokens = array();
for($i = 0; $i < $entityCount; $i++){
	$entityRow = $DS->entityJSONToRowFormat($entities[$i]);
	$tokens[] = array('token_id' => $entityRow['token_id'], 'user_id' => (array_key_exists('user_id', $entityRow))?$entityRow['user_id']:'');
	unset($entityRow);
}
unset($entities);
echo json_encode($tokens);

?>