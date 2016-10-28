<?PHP
include_once('header.inc.php');
$result['error_code'] = 0;

$query = "Select * from health_info_category "
		."Order by category_id ";

$rs = $db->Execute($query);
while($row = $rs->FetchRow()){
	$category = $row;
	$category_id = $category['category_id'];
	$query = "Select article_id, subject, content, image_url, release_time "
			."From health_info_article "
			."Where hidden = 0 "
			."And category_id = ".$category_id." "
			."Order by release_time desc "
			."Limit 0, 10 ";
	$article_rs = $db->Execute($query);
	$category['articles'] = array();
	while($article_row = $article_rs->FetchRow()){
		$category['articles'][] = $article_row;
	}
	$result['categories'][] = $category;	
	
}


echo json_encode($result);
$db->Close();

?>