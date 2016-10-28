<?PHP
include_once('config.inc.php');



$rs = $db->Execute('Select * from app ');
while($row = $rs->FetchRow()){
	//var_dump($row);
	echo $row['app_id']."<br/>\n";
}

?>