<?PHP

use google\appengine\api\cloud_storage\CloudStorageTools;

$object_url = 'gs://mobilegip-app-download/HyLib_NTCB_v1.1.2025.ipa';

$bucketName = CloudStorageTools::getDefaultGoogleStorageBucketName();

//echo $bucketName;
$object_public_url = CloudStorageTools::getPublicUrl($object_url, false);
header('Location:' .$object_public_url);

?>