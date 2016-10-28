<?PHP
include_once('config.inc.php');

/*
$query = "Insert into device set app_id = 'test', OS = 'iOS', token_id = 'test1', user_id = 'test1', update_time = '".date('Y-m-d H:i:s')."';\n"
		."Insert into device set app_id = 'test', OS = 'iOS', token_id = 'test2', user_id = 'test2', update_time = '".date('Y-m-d H:i:s')."';\n";
echo $query;
*/
set_time_limit(300);

$sql = file_get_contents('device.sql');
$lines = split("\n", $sql);
echo count($lines);
$lineCount = count($lines);
$query = '';

ob_start();

for($i = 0; $i < $lineCount; $i++){
	//echo "aaa".substr($lines[$i], 0, 2)."aaa";
	if(strcmp(substr($lines[$i], 0, 2), '--') == 0){
		continue;	
	}
	$query .= $lines[$i]."\n";
	if($i % 100 == 0){
		$db->ExecuteMultipleQueries($query);
		$query = '';
		echo $i."<br/>\n";
		ob_flush();
        flush();
	}
	
}

if(strlen($query)){
	$db->ExecuteMultipleQueries($query);
	ob_flush();
    flush();
}

ob_end_flush();
//echo $query;
		

$db->Close();

?>