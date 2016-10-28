<?PHP
use google\appengine\api\modules\ModulesService;

$module = ModulesService::getCurrentModuleName();
syslog(LOG_DEBUG, "running on module:".$module);
echo "running on module:".$module;

?>