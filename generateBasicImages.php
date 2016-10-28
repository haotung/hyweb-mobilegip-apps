<?PHP
include_once('config.inc.php');
include_once('zip.lib.php');
use google\appengine\api\cloud_storage\CloudStorageTools;
if(!array_key_exists('appId', $_GET)){
	die();	
}
$appId = $_GET['appId'];
if(!array_key_exists('purpose', $_GET)){
	die();	
}
$purpose = $_GET['purpose'];

$processTypes = array();


if($purpose == 'hylibLoginBG'){
	$processTypes[] = array('imageType' => 'ios_hylib_login_background_2x3',
							'outputFiles' => array(
								array('width' => 320, 'height' => 480, 'filepath' => '/HyLibLoginFormBG.png'),
								array('width' => 640, 'height' => 960, 'filepath' => '/HyLibLoginFormBG@2x.png')
							)
					  );
	$processTypes[] = array('imageType' => 'ios_hylib_login_background_9x16',
							'outputFiles' => array(
								array('width' => 640, 'height' => 1136, 'filepath' => '/HyLibLoginFormBG-568h@2x.png'),
								array('width' => 750, 'height' => 1334, 'filepath' => '/HyLibLoginFormBG-375w@2x.png'),
								array('width' => 1242, 'height' => 2208, 'filepath' => '/HyLibLoginFormBG-414w@3x.png')
							)
					  );

}


$zip = new ZipFile();
for($t = 0; $t < count($processTypes); $t++){
	$type = $processTypes[$t];
	$imageType = $type['imageType'];
	
	$query = "Select filename "
			."From app_content_file "
			."Where app_id = '".$_GET['appId']."' "
			."And item_name = '".$imageType."' ";
	$rs = $db->Execute($query);
	if($row = $rs->FetchRow()){
		$sourceFilename = $row['filename'];
		$fileUrl = 'gs://hyweb-mobilegip-apps/app_basic_image/'.$sourceFilename;
		$oriImageBlob = file_get_contents($fileUrl);
		
		
		$image = new Imagick();
		$outputFiles = $type['outputFiles'];
		for($i = 0; $i < count($outputFiles); $i++){
			$file = $outputFiles[$i];
			$width = $file['width'];
			$height = $file['height'];
			$filepath = $file['filepath'];
			
			$image->readimageblob($oriImageBlob);
			$image->resizeImage($width, $height, imagick::FILTER_CATROM, 1); 
			$image->setImageFormat('png');
			$zip->addFile($image->getimageblob(), $filepath);
			
		}
	}
}

$zipContent = $zip->file();
header('Content-Type: application/zip');
header('Content-Length: ' . strlen($zipContent));
header('Content-Disposition: attachment; filename="'.$zipFileName.'"');
echo $zipContent;

?>