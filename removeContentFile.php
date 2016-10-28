<?PHP
include_once('config.inc.php');
use google\appengine\api\cloud_storage\CloudStorageTools;


$filename = '';
$query = "Select filename "
		."From app_content_file "
		."Where app_id = '".$_GET['app_id']."' "
		."And item_name = '".$_GET['itemName']."' ";
		
$rs = $db->Execute($query);
if($row = $rs->FetchRow()){
	$filename = $row['filename'];
}
		
if(strlen($filename) > 0){
	$fileUrl = 'gs://hyweb-mobilegip-apps/content_files/'.$filename;
	unlink($fileUrl);
}

$query = "Delete from app_content_file "
		."Where app_id = '".$_GET['app_id']."' "
		."And item_name = '".$_GET['itemName']."' ";
$db->Execute($query);

?>