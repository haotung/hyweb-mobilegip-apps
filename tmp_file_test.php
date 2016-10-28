<?PHP
use google\appengine\api\cloud_storage\CloudStorageTools;


$certFileUrl = 'gs://hyweb-mobilegip-apps/certs/HyLib_NTUT-Push-Prod.pem';
$certFile = fopen($certFileUrl, "r");

$content = '';
while(($buffer = fgets($certFile, 4096)) !== false){
	$content.=$buffer;	
	
}
fclose($certFile);
//echo $content;

$dir = sys_get_temp_dir();
$tmp = tempnam($dir, 'foo');
file_put_contents($tmp, $content);
/*
$f = fopen($tmp, 'a');
fwrite($f, ' world');
fclose($f);
*/
echo 'content:'.file_get_contents($tmp)."<br/>\n";
/*
$dir = sys_get_temp_dir();
$tmp = tempnam($dir, 'foo');
file_put_contents($tmp, 'hello');
$f = fopen($tmp, 'a');
fwrite($f, ' world');
fclose($f);

echo 'content:'.file_get_contents($tmp)."<br/>\n";
echo 'tmp file:'.$tmp."<br/>\n";

$temp = tmpfile();

fwrite($temp, "Testing, testing.");
echo 'temp file:'.$temp."<br/>\n";

echo file_get_contents($temp);
*/

?>