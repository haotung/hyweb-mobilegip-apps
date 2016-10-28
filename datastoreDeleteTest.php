<?PHP
require_once 'datastore_config.php';
require_once 'GoogleDatastoreService.php';

$DS = new GoogleDatastoreService($google_api_config);


$id = 6454502756450304;

$keys[] = $DS->createKeyByNameAndId('push_log', null, $id);
$DS->deleteByKeys($keys);

echo 'deleted';



?>