<?PHP
include_once('header.inc.php');


$query = 'Select * from member';
$rs = $db->Execute($query);
while($row = $rs->FetchRow()){
	echo $row['name'];	
	echo $row['phone'];	
}


$db->Close();
?>