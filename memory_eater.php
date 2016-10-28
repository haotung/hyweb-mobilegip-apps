<?PHP
use google\appengine\api\taskqueue\PushTask;
use google\appengine\api\modules\ModulesService;

require_once 'datastore_config.php';
require_once 'GoogleDatastoreService.php';

$DS = new GoogleDatastoreService($google_api_config);
$key = "q0q40q0f9j0249ur0394jf29j4f-19j24f-9j14f9j104h9f01h4013nv0n1034nv01n4v014f9j14f9j104h9f04h9f01h4013nv0n1034nv01n4v014f9j11h4013nv0n1034nv01n4v014f";
$token = array();
for($i = 0; $i < 8000; $i++){
	$token[] = array('key' => $key, 'user' => 'awofjawoijfeawejf awefawofjawoijfeawejf awef');
}

$jsonStr = array();
for($i = 0; $i < 3; $i++){
	$str = json_encode($token);
	$jsonStr[] = $str.$str.$str.$str;
}
sleep(180);
syslog(LOG_DEBUG ,date("Y-m-d H:i:s")." Memory usage:".memory_get_usage());

?>