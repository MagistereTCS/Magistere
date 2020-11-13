<?php
function ddl_resource_file($resource_link, $fileName, $fileSize){
		// Output headers.
		header('Content-Description: File Transfer');
		header('Content-Type: application/octet-stream');
		header("Content-Disposition: attachment; filename=".$fileName);
		//header("Content-type: image/jpg");
		header('Content-Transfer-Encoding: binary');
		header("Content-Length: ".$fileSize);
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Pragma: public');
		
		ob_clean();
		flush();
		readfile($resource_link);
		exit;
}

function get_cr_attached_video_file_link($data, $type){
	global $CFG;
	$id = $type."id";
	$elementid = $data->$id;

	if($elementid !=null)
	{
		$where = "id = $elementid";
		$cr_element = get_cr_resource($where);

		$url_resource = '/'.$CFG->centralizedresources_media_types[$type].'/'.$cr_element->cleanname;

		return get_resource_centralized_secure_url($url_resource, $cr_element->hashname. $cr_element->createdate, $CFG->secure_link_timestamp_image);
	}
	else{
		return null;
	}
}

function get_cr_resource($where){
	global $CFG;

	$dbconn = get_centralized_db_connection();
	
	$cr_resource = $dbconn->get_record_sql('SELECT * FROM cr_resources WHERE '. $where);
	
	return $cr_resource;
}

function formatBytes($size, $precision = 2) {
	$base = log($size, 1024);
    $suffixes = array('', 'KB', 'MB', 'GB', 'TB');   

    return round(pow(1024, $base - floor($base)), $precision) . $suffixes[floor($base)];
}

/**
 * Returns general link or pdf embedding html.
 * @param string $fullurl
 * @param string $title
 * @param string $clicktoopen
 * @return string html
 */
function centralizedresources_embed_pdf($fullurl, $title, $clicktoopen, $width = "100%", $height = "600px") {
	global $CFG, $PAGE;

	$code = <<<EOT
<div class="centralizedresourcecontent resourcepdf">
  <object id="resourceobject" data="$fullurl#view=FitV" type="application/pdf" width="$width" height="$height">
    <param name="src" value="$fullurl#view=FitV" />
    <param name="view" value="FitV" />
    $clicktoopen
  </object>
</div>
EOT;

	// the size is hardcoded in the boject obove intentionally because it is adjusted by the following function on-the-fly
	//$PAGE->requires->js_init_call('M.util.init_maximised_embed', array('resourceobject'), true);

	return $code;
}