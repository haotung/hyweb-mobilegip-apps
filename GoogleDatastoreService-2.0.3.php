<?PHP

require_once 'datastore_config.php';
require_once 'google-api-php-client-2.0.3/src/Google/autoload.php';
// require_once 'google-api-php-client-2.0.3/src/Google/Service.php';
// require_once 'google-api-php-client-2.0.3/src/Google/Auth/OAuth2.php';
// require_once 'google-api-php-client-2.0.3/src/Google/Client.php';
// require_once 'google-api-php-client-2.0.3/src/Google/Config.php';
// require_once 'google-api-php-client-2.0.3/src/Google/Model.php';
// require_once 'google-api-php-client-2.0.3/src/Google/Collection.php';
// require_once 'google-api-php-client-2.0.3/src/Google/Auth/AssertionCredentials.php';
// require_once 'google-api-php-client-2.0.3/src/Google/Service/Resource.php';
// require_once 'google-api-php-client-2.0.3/src/Google/Service/Datastore.php';


class GoogleDatastoreService{
	private $google_api_config;
	private $client;
	private $service;
	private $service_dataset;
	private $dataset_id;
	
	
	
	public function __construct(array $googleApiConfig){
		$this->google_api_config = $googleApiConfig;	
		$this->dataset_id = $this->google_api_config['dataset-id'];	
		$this->buildConnection();
		//echo "GoogleDatastoreService constructed<br/>\n";
	}


	private function buildConnection(){
		$scopes = [
		    "https://www.googleapis.com/auth/datastore",
		    "https://www.googleapis.com/auth/userinfo.email",
		  ];
		$this->client = new Google_Client();
		$this->client->setApplicationName($this->google_api_config['application-id']);
		$this->client->setAssertionCredentials(
			new Google_Auth_AssertionCredentials(
				$this->google_api_config['service-account-name'],
				$scopes, $this->google_api_config['private-key']
			)
		);
		$this->service = new Google_Service_Datastore($this->client);
		$this->service_dataset = $this->service->datasets;
		
	}
	
	public function deleteByKeys($keys){
		$req = $this->createDeleteRequestForKeys($keys);
  		$result = $this->service_dataset->commit($this->dataset_id, $req, []);
		
	}
	
	
	public function saveEntities($entities, $useAutoId = false){
		$req = $this->createCommitRequestForEntities($entities, $useAutoId);
  		$result = $this->service_dataset->commit($this->dataset_id, $req, []);
  		$autoIds = array();
  		if(!is_null($result)){
			if(array_key_exists('mutationResult', $result['modelData'])){
				if(array_key_exists('insertAutoIdKeys', $result['modelData']['mutationResult'])){
					$keys = $result['modelData']['mutationResult']['insertAutoIdKeys'];
					$keysCount = count($keys);
					for($i = 0; $i < $keysCount; $i++){
						$key = $keys[$i];
						$autoIds[] = $key['path'][0]['id'];
					}
				}
			}
  		}
  		$entities = null;
  		unset($entities);
  		return $autoIds;
	}
	
	public function saveEntity($kind, $keyValueSets, $name = null){
		$entity = $this->buildEntity($kind, $keyValueSets, $name);
		$req = $this->createCommitRequestForEntity($entity, ($name == null));
  		$this->service_dataset->commit($this->dataset_id, $req, []);
		
	}
	
	public function buildEntity($kind, $keyValueSets, $name = null){
		$entity = new Google_Service_Datastore_Entity();
		$entity->setKey($this->createKey($kind, $name));
		$entity->setProperties($this->buildPropertiesForKeyValueSets($keyValueSets));
		return $entity;
	}
	
	private function buildPropertiesForKeyValueSets($keyValueSets){
		$property_map = [];
		$keyValueSetCount = count($keyValueSets);
		for($i = 0; $i < $keyValueSetCount; $i++){
			$keyValueSet = $keyValueSets[$i];
			$property_map[$keyValueSet['key']] = $this->buildPropertyForKeyValueSet($keyValueSet);
		}
		return $property_map;
	}
	
	private function buildPropertyForValueAndType($value, $type, $index = true){
		$property = new Google_Service_Datastore_Property();
		$property->setIndexed($index);
		if($type == PropertyType::Boolean){
			$property->setBooleanValue($value);
		}
		else if($type == PropertyType::Integer){
			$property->setIntegerValue($value);
		}
		else if($type == PropertyType::Double){
			$property->setDoubleValue($value);
		}
		else if($type == PropertyType::String){
			$property->setStringValue($value);
			if(strlen($value) > 1500){
				$property->setIndexed(false);
			}
		}
		else if($type == PropertyType::DateType){
			$parts = date_parse($value);
			$date = new DateTime();
			$date->setDate($parts['year'], $parts['month'], $parts['day']);
			$date->setTime($parts['hour'], $parts['minute'], $parts['second']);
			$property->setDateTimeValue(Date("c", $date->getTimestamp()));
		}
		else if($type == PropertyType::ListType){
			$property->setListValue($value);
		}
		
		return $property;
		
	}
	
	private function buildPropertyForKeyValueSet($keyValueSet){
		$type = $keyValueSet['type'];
		$value = $keyValueSet['value'];
		$index = true;
		if(array_key_exists('index', $keyValueSet)){
			$index = $keyValueSet['index'];
		}
		return $this->buildPropertyForValueAndType($value, $type, $index);
	}
	
	private function createKey($kind, $name = null) {
		$path = new Google_Service_Datastore_KeyPathElement();
		$path->setKind($kind);
		if($name != null){
			$path->setName($name);
		}
		$key = new Google_Service_Datastore_Key();
		$key->setPath([$path]);
		return $key;
	}
	
	public function createKeyByNameAndId($kind, $name, $id){
		$path = new Google_Service_Datastore_KeyPathElement();
		$path->setKind($kind);
		if($name != null){
			$path->setName($name);
		}
		else if($id != null){
			$path->setId($id);
		}
		$key = new Google_Service_Datastore_Key();
		$key->setPath([$path]);
		return $key;
	}
	
	private function createCommitRequestForEntity($entity, $useAutoId = false) {
		return $this->createCommitRequestForEntities([$entity], $useAutoId);
	}
	
	private function createCommitRequestForEntities($entities, $useAutoId = false) {
		$mutation = new Google_Service_Datastore_Mutation();
		if($useAutoId){
			//echo "use auto Id<br/>\n";
			$mutation->setInsertAutoId($entities);
		}
		else{
			$mutation->setUpsert($entities);
		}
		$entities = null;
		unset($entities);
		$req = new Google_Service_Datastore_CommitRequest();
		$req->setMode('NON_TRANSACTIONAL');
		$req->setMutation($mutation);
		return $req;
	}
	
	private function createDeleteRequestForKeys($keys) {
		$mutation = new Google_Service_Datastore_Mutation();
		$mutation->setDelete($keys);
		$entities = null;
		unset($entities);
		$req = new Google_Service_Datastore_CommitRequest();
		$req->setMode('NON_TRANSACTIONAL');
		$req->setMutation($mutation);
		return $req;
	}
	
	public function queryEntitiesWithGQL($gql, $params = null){
		$query = new Google_Service_Datastore_GqlQuery();
		$query->setQueryString($gql);
		$query->setAllowLiteral(true);
		
		$req = new Google_Service_Datastore_RunQueryRequest();
        $req->setGqlQuery($query);

        $result = $this->service_dataset->runQuery($this->dataset_id, $req, (is_null($params))?[]:$params);
        if(!is_null($result)){
        	$entityResults = $result['modelData']['batch']['entityResults'];
        	unset($result);
        	return $entityResults;
        }
        else{
        	return null;
        }
		
	}
	
	
	public function queryEntityObjectsWithGQL($gql){
		$jsonResults = $this->queryEntitiesWithGQL($gql);
		if(is_null($jsonResults)){
			return null;
		}
		$resultCount = count($jsonResults);
		$entityObjs = array();
		for($i = 0; $i < $resultCount; $i++){
			$entityObj = $this->entityJSONToEntityObj($jsonResults[$i]);
			$entityObjs[] = $entityObj;	
		}
		return $entityObjs;
	}
	
	public function setEntityProperty(&$entity, $key, $value, $type, $index = true){
		$newProperty = $this->buildPropertyForValueAndType($value, $type, $index);
		$properties = $entity->getProperties();
		$properties[$key] = $newProperty;
		$entity->setProperties($properties);
	}
	
	public function entityJSONToEntityObj($entityJSON){
		if(!array_key_exists('entity', $entityJSON)){
			return null;
		}
		$entity = new Google_Service_Datastore_Entity();
		$entityBody = $entityJSON['entity'];
		$keyPathes = $entityBody['key']['path'];
		$firstLevelPath = $keyPathes[0];
		$kind = $firstLevelPath['kind'];
		$name = null;
		$id = null;
		
		if(array_key_exists('name', $firstLevelPath)){
			$name = $firstLevelPath['name'];
		}
		else if(array_key_exists('id', $firstLevelPath)){
			$id = $firstLevelPath['id'];
		}
		
		$entity->setKey($this->createKeyByNameAndId($kind, $name, $id));
		$newProperties = array();
		$properties = $entityBody['properties'];
		foreach($properties as $key => $valueDict){
			$property = $properties[$key];
			$newProperty = new Google_Service_Datastore_Property();
			$hasValue = false;
			if(array_key_exists('booleanValue', $valueDict)){
				$newProperty->setBooleanValue($valueDict['booleanValue']);
				$hasValue = true;
			}
			else if(array_key_exists('integerValue', $valueDict)){
				$newProperty->setIntegerValue($valueDict['integerValue']);
				$hasValue = true;
			}
			else if(array_key_exists('doubleValue', $valueDict)){
				$newProperty->setDoubleValue($valueDict['doubleValue']);
				$hasValue = true;
			}
			else if(array_key_exists('dateTimeValue', $valueDict)){
				$newProperty->setDateTimeValue($valueDict['dateTimeValue']);
				$hasValue = true;
			}
			else if(array_key_exists('stringValue', $valueDict)){
				$newProperty->setStringValue($valueDict['stringValue']);
				if(strlen($valueDict['stringValue']) > 1500){
					$newProperty->setIndexed(false);
				}
				$hasValue = true;
			}
			if($hasValue){
				$newProperties[$key] = $newProperty;	
			}
		}
		$entity->setProperties($newProperties);
		return $entity;
	}
	
	public function entityJSONToRowFormat($entityJSON){
		if(!array_key_exists('entity', $entityJSON)){
			return null;
		}
		$row = array();
		$entityBody = $entityJSON['entity'];
		$keyPathes = $entityBody['key']['path'];
		$firstLevelPath = $keyPathes[0];
		$row['__kind__'] = $firstLevelPath['kind'];
		if(array_key_exists('name', $firstLevelPath)){
			$row['__keytype__'] = 'name';
			$row['__name__'] = $firstLevelPath['name'];
		}
		else if(array_key_exists('id', $firstLevelPath)){
			$row['__keytype__'] = 'id';
			$row['__id__'] = $firstLevelPath['id'];
		}
		unset($keyPathes);
		unset($firstLevelPath);
		$properties = $entityBody['properties'];
		foreach($properties as $key => $valueDict){
			$property = $properties[$key];
			$value = null;
			if(array_key_exists('booleanValue', $valueDict)){
				$value = $valueDict['booleanValue'];
			}
			else if(array_key_exists('integerValue', $valueDict)){
				$value = $valueDict['integerValue'];
			}
			else if(array_key_exists('doubleValue', $valueDict)){
				$value = $valueDict['doubleValue'];
			}
			else if(array_key_exists('dateTimeValue', $valueDict)){
				$value = $valueDict['dateTimeValue'];
			}
			else if(array_key_exists('stringValue', $valueDict)){
				$value = $valueDict['stringValue'];
			}
			if($value != null){
				$row[$key] = $value;
			}
		}
		unset($properties);
		unset($entityJSON);
		unset($entityBody);
		return $row;
	}
}

abstract class PropertyType{
	
	const Boolean = 0;
	const Integer = 1;
	const Double = 2;
	const String = 3;
	const DateType = 4;
	const ListType = 5;
	
}


?>