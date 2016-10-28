<?PHP
include_once('config.inc.php');
use google\appengine\api\cloud_storage\CloudStorageTools;
//app_id=' + appId + '&imageType=' + imageType + '&maxHeight=400'
if(!array_key_exists('app_id', $_GET) || !array_key_exists('itemName', $_GET)){
	die();
}
$maxHeight = 400;
if(array_key_exists('maxHeight', $_GET)){
	$maxHeight = $_GET['maxHeight'];
	if(strlen($maxHeight) === 0 || $maxHeight === '0'){
		$maxHeight = 400;	
	}
}




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
	
	
	header('Content-Type: image/png');

	
	$fileUrl = 'gs://hyweb-mobilegip-apps/content_files/'.$filename;
	$image = new Imagick();
	$imagenblob=file_get_contents($fileUrl);
	
	$image->readimageblob($imagenblob);
	
	$geo = $image->getImageGeometry(); 

	$width = $geo['width'];
	$height = $geo['height'];
	if($height > $maxHeight){
		$width = round($width * ($maxHeight / $height));
		$image->resizeImage($width, $maxHeight, imagick::FILTER_CATROM, 1); 
			
	}
	
	header('Content-Length: '.strlen($imagenblob));
	$imagenblob=$image->getimageblob();
	echo $imagenblob;
	
}
?>