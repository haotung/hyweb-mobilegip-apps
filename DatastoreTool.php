<?PHP
require_once('vendor/autoload.php');
use Google\Cloud\ServiceBuilder;
use Google\Cloud\Datastore\DatastoreClient;
use Google\Cloud\Datastore\Query\QueryInterface;


class DatastoreTool{
	private $datastore;

	public function __construct(DatastoreClient $datastore){
		$this->datastore = $datastore;
	}



	public function fetchOne(QueryInterface $query, array $options = [])
    {
    	$entities = $this->datastore->runQuery($query, $options);
    	foreach ($entities as $entity) {
    		return $entity;
    	}
    	return null;
    }


}




?>