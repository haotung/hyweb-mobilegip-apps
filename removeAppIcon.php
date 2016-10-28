<?PHP
include_once('config.inc.php');
use google\appengine\api\cloud_storage\CloudStorageTools;


$iconFileName = '';
$query = "Select icon_file_name "
		."From app "
		."Where app_id = '".$_GET['app_id']."' ";
$rs = $db->Execute($query);
if($row = $rs->FetchRow()){
	$iconFileName = $row['icon_file_name'];
}
		
if(strlen($iconFileName) > 0){
	

	$fileUrl = 'gs://hyweb-mobilegip-apps/app_icon/'.$iconFileName;
	
	$query = "Update app "
			."Set icon_file_name = '' "
			."Where app_id = '".$_GET['app_id']."' ";
	$db->Execute($query);
	unlink($fileUrl);
	
}
?>