<?PHP
include_once('config.inc.php');
include_once('zip.lib.php');
use google\appengine\api\cloud_storage\CloudStorageTools;
if(!array_key_exists('appId', $_GET)){
	die();	
}

$logStr = '';
$appId = $_GET['appId'];
$zipFileName = $appId.'_menuXml.zip';
$zip = new ZipFile();
ini_set('memory_limit', '512M');
$additionalAttrs = array();
main();

function main(){
	global $zip, $zipFileName, $logStr;
	addMenuXML();
	$zip->addFile($logStr, '/log.txt');
	$zipContent = $zip->file();
	//echo strlen($zipContent);
	
	header('Content-Type: application/zip');
	header('Content-Length: ' . strlen($zipContent));
	header('Content-Disposition: attachment; filename="'.$zipFileName.'"');
	echo $zipContent;
	
}


function addMenuXML(){
	global $db, $zip, $logStr;
	//loadAdditionalAttrs();
	$appId = $_GET['appId'];
	
	//AndroidManifest.xml
	$fileUrl = 'gs://hyweb-mobilegip-apps/sample_xml/HyLib_standard_sample_menu.xml';
	$xmlContent = file_get_contents($fileUrl);
	$xml = simplexml_load_string($xmlContent);
	if($xml !== false){
		$domDoc = dom_import_simplexml($xml);
		$menuNodeNodes = $domDoc->getElementsByTagName('Node');
		$menuNodeCount = $menuNodeNodes->length;
		for($i = 0; $i < $menuNodeCount; $i++){
			$menuNodeNode = $menuNodeNodes->item($i);
			$nodeId = $menuNodeNode->getAttribute('id');	
			if(strcmp($nodeId, 'menu_links') === 0){
				$stringNode->nodeValue = $displayName;
				$logStr .= "relative links root node gotten\n";
				
				$query = "Select attr_name, attr_values "
						."From app_additional_attrs "
						."Where app_id = '".$_GET['appId']."' ";
				$rs = $db->Execute($query);
				while($row = $rs->FetchRow()){
					if(strcmp($row['attr_name'], 'relatedLinks') === 0){
						$relatedLinks = json_decode($row['attr_values'], true);	
					}
				}

				$logStr .= 'relatedLinks = '.json_encode($relatedLinks)."\n";
				
				for($i = 0; $i < count($relatedLinks); $i++){
					if(strlen($relatedLinks[$i]['url']) > 0 && strlen($relatedLinks[$i]['text']) > 0){
						$menuNodeNode->appendChild(new DOMText("\n\t\t\t"));
						$relativeUrlNode = $menuNodeNode->appendChild(new DOMElement('Node'));
						$relativeUrlNode->setAttribute('id', 'r_url'.($i + 1));
						$relativeUrlNode->setAttribute('targetType', 'url');
						$relativeUrlNode->setAttribute('text', $relatedLinks[$i]['text']);
						$relativeUrlNode->setAttribute('targetUrl', $relatedLinks[$i]['url']);
						//$relativeUrlNode->setAttribute('icon', "images/app_icon.png");
						$menuNodeNode->appendChild($relativeUrlNode);
					}
				}
				
				$menuNodeNode->appendChild(new DOMText("\n\t\t"));
				break;
			}
		}
		
		$resultXml = simplexml_import_dom($domDoc);
		$zip->addFile($resultXml->asXML(), '/menu.xml');
	}
}

?>