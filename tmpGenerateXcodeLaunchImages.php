<?PHP


$jsonContentStr = file_get_contents('./def_files/Launch-Contents.json');
$jsonObj = json_decode($jsonContentStr, true);


$images = $jsonObj['images'];
echo '<table>';
for($i = count($images) - 1; $i >= 0; $i--){
	$image = $images[$i];
	if(strcmp($image['orientation'], 'portrait') != 0){
		unset($images[$i]);
		continue;
	}
	else if(strcmp($image['extent'], 'full-screen') != 0){
		unset($images[$i]);
		continue;	
	}
	echo '<tr>';
	echo '<td>'.$image['idiom'].'</td>';
	echo '<td>'.$image['subtype'].'</td>';
	echo '<td>'.$image['scale'].'</td>';
	echo '<td>'.$image['minimum-system-version'].'</td>';
	echo '<td>'.$image['extent'].'</td>';
	echo '<td>'.$image['filename'].'</td>';
	
	$size = getLaunchImageSize($image);
	echo '<td>'.$size['width'].'x'.$size['height'].'('.$size['image_source'].', '.$size['filename'].')</td>';
	
	
	echo '</tr>';
		
}


function getLaunchImageSize($image){
	$rule[] = array('idiom' => 'ipad', 'subtype' => '', 'scale' => '1x', 'width' => 768, 'height' => 1024, 'aspect_ratio' => '3x4');
	$rule[] = array('idiom' => 'ipad', 'subtype' => '', 'scale' => '2x', 'width' => 1536, 'height' => 2048, 'aspect_ratio' => '3x4');
	$rule[] = array('idiom' => 'iphone', 'subtype' => '667h', 'scale' => '2x', 'width' => 750, 'height' => 1334, 'aspect_ratio' => '9x16');
	$rule[] = array('idiom' => 'iphone', 'subtype' => '736h', 'scale' => '3x', 'width' => 1242, 'height' => 2208, 'aspect_ratio' => '9x16');
	$rule[] = array('idiom' => 'iphone', 'subtype' => 'retina4', 'scale' => '2x', 'width' => 640, 'height' => 1136, 'aspect_ratio' => '9x16');
	$rule[] = array('idiom' => 'iphone', 'subtype' => '', 'scale' => '1x', 'width' => 320, 'height' => 480, 'aspect_ratio' => '2x3');
	$rule[] = array('idiom' => 'iphone', 'subtype' => '', 'scale' => '2x', 'width' => 640, 'height' => 960, 'aspect_ratio' => '2x3');
	$idiom = $image['idiom'];
	$subtype = (array_key_exists('subtype', $image))?($image['subtype']):'';
	$scale = $image['scale'];
	
	for($i = 0; $i < count($rule); $i++){
		if(strcmp($rule[$i]['idiom'], $idiom) === 0
		&& strcmp($rule[$i]['subtype'], $subtype) === 0
		&& strcmp($rule[$i]['scale'], $scale) === 0){
			$size = array('width' => $rule[$i]['width'], 'height' => $rule[$i]['height']);
			$size['image_source'] = 'ios_launch_image_'.$rule[$i]['aspect_ratio'];
			$size['filename'] = $idiom.((strlen($subtype) > 0)?('-'.$subtype):'').((strcmp($scale, '1x') === 0)?'':'@'.$scale).'.png';
			return $size;	
		}
	}
	
	$size = array('filename' => '');
	return $size;
}


echo '</table>';
//echo json_encode($images, true);
//echo var_dump($jsonObj);

?>