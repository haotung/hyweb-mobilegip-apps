<?PHP
use Google\Cloud\ServiceBuilder;
require_once('vendor/autoload.php');


$gcloud = new ServiceBuilder([
    'keyFilePath' => 'credentials/hyweb-mobilegip-notification-cert.json',
    'projectId' => 'hyweb-mobilegip-notification'
]);


$datastore = $gcloud->datastore();


?>