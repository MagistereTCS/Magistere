<?php
require_once('../../../config.php');
require_once($CFG->libdir.'/csvlib.class.php');
require_once($CFG->libdir.'/filelib.php');
require_login();

$fileurl = required_param("url", PARAM_URL);
$courseid = required_param("courseid", PARAM_INT);
$msg = "";
$content = "";
$type = "";

$data_url = explode("/",$fileurl);

$fs = get_file_storage();

// Prepare file record object
$fileinfo = array(
		'component' => $data_url[6], // usually = table name
		'filearea' => $data_url[7],  // usually = table name
		'itemid' => $data_url[8],    // usually = ID of row in table
		'contextid' => $data_url[5], // ID of context
		'filepath' => '/',           // any path beginning and ending in /
		'filename' => urldecode($data_url[9])); // any filename

// Get file
$file = $fs->get_file($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'],
		$fileinfo['itemid'], $fileinfo['filepath'], $fileinfo['filename']);

// Test format du fichier
if($file){
	if($file->get_mimetype() != "text/csv"){
		$msg = get_string('errornocsvfile', 'block_csv_enrol');
		$data = json_encode(array(
				'url' => $fileurl,
				'msg' => $msg,
				'type' => $type
		));
		echo $data;
		exit();
	}
	// Read contents
	$content = $file->get_content();
	
	$iid = csv_import_reader::get_new_iid('uploaduser');
	$cir = new csv_import_reader($iid, 'uploaduser');
	
	$cir->load_csv_content($content, 'UTF-8', 'semicolon');

	$columns = $cir->get_columns();
	if (empty($columns)) {
		$cir->close();
		$cir->cleanup();
	}

	$v = "/^[a-zA-Z0-9_\-\.]+@[a-zA-Z0-9\-\.]+\.[a-zA-Z]+$/";
	$lines = explode("\n",$content);
	foreach ($lines as $key=>$line) {
		if(trim($line)=="") continue;
		if (count($columns) > 1) { // Fichier complexe
			$type = "complex";
			$rawuser = explode(";",$line);
			if(trim($rawuser[0])=="") continue;
			// Test Email
			
			$test_email = preg_match($v, $rawuser[0]);
			if($key == 0 && !($test_email)) continue; // Cas où le fichier contient une entete
					
			if(!($test_email)){
				$msg = get_string('erroremailentry', 'block_csv_enrol');
				break;
			}
			// Test rôle
			$test_role = $DB->get_record('role', array('shortname' => $rawuser[1]));
			if(!($test_role)){
				$msg = get_string('errorroleentry', 'block_csv_enrol');
				break;
			}
			// Gestion des groupes
			for($i = 2; $i <= count($rawuser)-1; ++$i) { 
				if(trim($rawuser[$i])=="") continue;
				$group = $DB->get_record('groups', array('name' => trim($rawuser[$i]), 'courseid' => $courseid));
				if(!($group)){ 
					$msg = get_string('errornogroupentry', 'block_csv_enrol');
					break;
				}
			}
		}else{ // Fichier simple
			$type = "simple";
			// Test Email
			$test_email = preg_match($v, trim($line));
			if($key == 0 && !($test_email)) continue; // Cas où le fichier contient une entete
			if(!($test_email) ){
				$msg = get_string('erroremailentry', 'block_csv_enrol');
				break;
			}
		}
	}
	
	$cir->close();
}else{
	$msg = get_string('errorformatfile', 'block_csv_enrol');
}

$data = json_encode(array(
		'url' => $fileurl,
		'msg' => $msg,
		'type' => $type
));
echo $data;

?>