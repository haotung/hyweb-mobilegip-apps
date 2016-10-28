<?PHP

function isAllParamsValid($arr, $keys, $allowEmptyString){
	
	$keyCount = count($keys);
	for($i = 0; $i < $keyCount; $i++){
		if(!array_key_exists($keys[$i], $arr)){
			return false;
		}
		else if(!$allowEmptyString && (strlen($arr[$keys[$i]]) == 0)){
			return false;
		}
	}
	return true;
}


?>