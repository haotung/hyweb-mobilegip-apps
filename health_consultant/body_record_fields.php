<?PHP

include_once('header.inc.php');
$result['error_code'] = 0;

$query = "select t.tab_id, t.tab_name, c.category_name, f.* "
		."from body_record_tab as t, body_record_category as c, body_record_field as f "
		."where t.tab_id = c.tab_id "
		."and c.brc_id = f.brc_id "
		."order by t.tab_id, c.brc_id, f.display_order";
$result['query'] = $query;
$rs = $db->Execute($query);

$result['tabs'] = array();
$preTabId = 0;
$preTab = null;
$preCategoryId = 0;
$preCategory = null;
while($row = $rs->FetchRow()){
	if($preCategoryId != $row['brc_id']){
		if(!is_null($preCategory)){
			$preTab['categories'][] = $preCategory;
		}
		$preCategory = array('category_id' => $row['brc_id'], 'category_name' => $row['category_name']);
		$preCategoryId = $row['brc_id'];
	}
	if($preTabId != $row['tab_id']){
		if(!is_null($preTab)){
			/*
			if(!is_null($preCategory)){
				$preTab['categories'][] = $preCategory;
			}
			*/
			$result['tabs'][] = $preTab;
		}
		$preTab = array('tab_name' => $row['tab_name']);
		$preTabId = $row['tab_id'];

	}
	$field = array('field_id' => $row['field_id'], 'name' => $row['field_name'], 'desc' => $row['field_desc'], 'type' => $row['field_type'], 'unit' => $row['field_unit']);
	$preCategory['fields'][] = $field;

}

if(!is_null($preCategory)){
	$preTab['categories'][] = $preCategory;
}
$preCategory = array('category_id' => $row['brc_id'], 'category_name' => $row['category_name']);
$preCategoryId = $row['brc_id'];
if(!is_null($preTab)){
	$result['tabs'][] = $preTab;
}

echo json_encode($result);



$db->Close();

?>