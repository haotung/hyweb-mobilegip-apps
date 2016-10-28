<?PHP
require_once 'datastore_config.php';
require_once 'Google/Service.php';
require_once 'Google/Auth/OAuth2.php';
require_once 'Google/Client.php';
require_once 'Google/Config.php';
require_once 'Google/Model.php';
require_once 'Google/Collection.php';
require_once 'Google/Auth/AssertionCredentials.php';
require_once 'Google/Service/Resource.php';
require_once 'Google/Service/Datastore.php';

$scopes = [
    "https://www.googleapis.com/auth/datastore",
    "https://www.googleapis.com/auth/userinfo.email",
  ];
$client = new Google_Client();
$client->setApplicationName($google_api_config['application-id']);
$client->setAssertionCredentials(new Google_Auth_AssertionCredentials(
  $google_api_config['service-account-name'],
  $scopes, $google_api_config['private-key']));
$service = new Google_Service_Datastore($client);
$service_dataset = $service->datasets;
$dataset_id = $google_api_config['dataset-id'];



?>