<?PHP
use google\appengine\api\cloud_storage\CloudStorageTools;
$handleUrl = '/iconUploadHandler.php';
$options = [ 'gs_bucket_name' => 'hyweb-mobilegip-apps' ];
if(array_key_exists('purpose', $_GET)){
	$purpose = $_GET['purpose'];
	if($purpose == 'appBasicImage'){
		$handleUrl = '/contentFileUploadHandler.php';
	}
	else if($purpose == 'uploadInstaller'){
		$handleUrl = '/installerUploadHandler.php';
		$options['gs_bucket_name'] = 'mobilegip-app-download';
	}
}

$upload_url = CloudStorageTools::createUploadUrl($handleUrl, $options);
echo $upload_url;

?>