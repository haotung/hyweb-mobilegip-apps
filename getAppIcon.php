<?PHP
include_once('config.inc.php');
use google\appengine\api\cloud_storage\CloudStorageTools;

$size = 0;
if(isset($_GET['size'])){
	$size = intval($_GET['size']);	
}
if($size == 0){
	$size = 144;	
}


$iconFileName = '';
$query = "Select icon_file_name "
		."From app "
		."Where app_id = '".$_GET['app_id']."' ";
$rs = $db->Execute($query);
if($row = $rs->FetchRow()){
	$iconFileName = $row['icon_file_name'];
}
		
if(strlen($iconFileName) > 0){
	
	
	if(strpos($iconFileName, '.png') !== false){
		header('Content-Type: image/png');
	}
	else{
		header('Content-Type: image/jpg');
	}
	
	//echo 'OK 1';
	$fileUrl = 'gs://hyweb-mobilegip-apps/app_icon/'.$iconFileName;
	$image = new Imagick();
	$imagenblob=file_get_contents($fileUrl);
	
	//echo 'blob size from file = '.strlen($imagenblob)."<br/>\n";
	$image->readimageblob($imagenblob);
	//echo 'before resizing, the file size is:'.$image->getImageSize()."<br/>\n";
	
	$image->resizeImage($size, $size, imagick::FILTER_CATROM, 1); 
	//echo 'after resizing, the file size is:'.$image->getImageSize()."<br/>\n";
	
	header('Content-Length: '.strlen($imagenblob));
	$imagenblob=$image->getimageblob();
	//echo 'blob size = '.strlen($imagenblob)."<br/>\n";
	/*
	$ctx = stream_context_create($options);
	file_put_contents($destino,$imagenblob,0, $ctx);
	*/
	echo $imagenblob;
	/*
	$fileSize = filesize($fileUrl);
	header('Content-Length: ' . $fileSize);
	*/
	/*
	CloudStorageTools::serve($fileUrl,
		['save_as' => $iconFileName]
	);
	*/
	
}
else{
	$pngImgUrl = 'gs://hyweb-mobilegip-apps/app_icon/'.$_GET['app_id'].'.png';
	$jpgImgUrl = 'gs://hyweb-mobilegip-apps/app_icon/'.$_GET['app_id'].'.jpg';
	
	//echo $object_image_file;
	$pngGotten = false;
	try{
		CloudStorageTools::deleteImageServingUrl($pngImgUrl);
		$object_public_url = CloudStorageTools::getImageServingUrl($pngImgUrl,
	                                            ['size' => $size, 'crop' => false]);
	    $pngGotten = true;
	}
	catch(Exception $ex){
		//echo 'ex:'.$ex->getMessage();	
	}
	
	
	if(!$pngGotten){
		try{
			$object_public_url = CloudStorageTools::getImageServingUrl($jpgImgUrl,
		                                            ['size' => $size, 'crop' => false]);
		}
		catch(Exception $ex){
			//echo 'ex:'.$ex->getMessage();	
		}
	}
	//$object_public_url = CloudStorageTools::getPublicUrl($object_image_file, false);
	//echo $object_public_url;
	
	header('Location:' .$object_public_url);
}
?>