<?php

/**
 * Return the path accoding the $data which contains information about the mimedata, extension and if it's a multimedia resource
 * @param unknown $data
 * @return string return an empty string if the file type is unknown
 */
function cr_find_path_for_file($data)
{
	global $CFG;

	$mimetype = $data['mimetype'];
	$extension = $data['extension'];
	$filemanager = $data['filemanager'];
	$isMultimedia = $data['is_multimedia'];

	$all_allow_extensions = array();

	foreach ($CFG->centralizedresources_allow_filetype as $ext){
		$all_allow_extensions = array_merge($all_allow_extensions, $ext);
	}
	
	//By default the file is considered like a file
	$path = $CFG->centralizedresources_media_path['file'];
	$type = 'file';
	
	if(in_array($extension, $all_allow_extensions)){
		//here we have only allowed extension
		//we have just to find the destination folder
		//and the correct type
		if(in_array($extension, $CFG->centralizedresources_allow_filetype['subtitle']) && $filemanager == 'subtitle'){
			$path = $CFG->centralizedresources_media_path['subtitle'];
			$type = 'subtitle';
		}else if(in_array($extension, $CFG->centralizedresources_allow_filetype['chapter']) && $filemanager == 'chapter'){
			$path = $CFG->centralizedresources_media_path['chapter'];
			$type = 'chapter';
		}else if(strstr($mimetype, 'video')){
			if(in_array($extension, $CFG->centralizedresources_allow_filetype['video'])){
				$path = $CFG->centralizedresources_media_path['video'];
				$type = 'video';
			}
		}else if (strstr($mimetype, 'image')){
			if($isMultimedia == 'indexthumb' && in_array($extension, $CFG->centralizedresources_allow_filetype['image'])){
                $path = $CFG->centralizedresources_media_path['indexthumb'];
                $type = 'indexthumb';
			}else if(in_array($extension, $CFG->centralizedresources_allow_filetype['image'])){
                $path = $CFG->centralizedresources_media_path['image'];
                $type = 'image';
            }
		}else if(strstr($mimetype, 'application')){
			if($isMultimedia == 'multimedia_file'){
				if(in_array($extension, $CFG->centralizedresources_allow_filetype['diaporama'])){
					$path = $CFG->centralizedresources_media_path['diaporama'];
					$type = 'diaporama';
				}
			}else{
				if(in_array($extension, $CFG->centralizedresources_allow_filetype['archive'])){
					$path = $CFG->centralizedresources_media_path['archive'];
					$type = 'archive';
				}else if(in_array($extension, $CFG->centralizedresources_allow_filetype['document'])){
					//some document mimedata use the 'application' mimedata...
					$path = $CFG->centralizedresources_media_path['document'];
					$type = 'document';
				}
			}

		}else if(strstr($mimetype, 'text')){
			if(in_array($extension, $CFG->centralizedresources_allow_filetype['document'])){
				$path = $CFG->centralizedresources_media_path['document'];
				$type = 'document';
			}
		}else if (strstr($mimetype, 'audio')){
			if(in_array($extension, $CFG->centralizedresources_allow_filetype['audio'])){
				$path = $CFG->centralizedresources_media_path['audio'];
				$type = 'audio';
			}
		}
	}

	return array(
			'path' => $path,
			'type' => $type
	);
}

function cr_clean_filename($filename)
{
	$clean = iconv("UTF8", 'ASCII//TRANSLIT//IGNORE', $filename);
	$clean = preg_replace("/[^a-zA-Z0-9\.-;]/", '_', $clean);
	
	return $clean;
}

function cr_get_resource_data_with_thumbnail_data($resourceid)
{
	$select = array("r.id AS resource_id",
        "r.resourceid AS resource_resourceid",
        "r.name AS resource_name",
        "r.hashname AS resource_hashname",
        "r.description AS resource_description",
        "r.type AS resource_type",
        "r.filename as resource_filename",
        "r.cleanname as resource_cleanname",
        "r.filesize as resource_filesize",
        "r.extension as resource_extension",
        "r.createdate as resource_createdate",
        "r.lastusedate as resource_lastusedate",
        "r.contributorid as resource_contributorid",
        "r.thumbnailid as resource_thumbnailid",
        "r.public as resource_public",
        "r.editdate as resource_editdate",
        "r.thumbnailpos as resource_thumbnailpos",
        "r.domainrestricted as resource_domainrestricted",
        "r1.id AS thumbnail_id",
        "r1.resourceid AS thumbnail_resourceid",
        "r1.name AS thumbnail_name",
        "r1.hashname AS thumbnail_hasname",
        "r1.description AS thumbnail_description",
        "r1.type AS thumbnail_type",
        "r1.filename as thumbnail_filename",
        "r1.cleanname as thumbnail_cleanname",
        "r1.filesize as thumbnail_filesize",
        "r1.extension as thumbnail_extension",
        "r1.createdate as thumbnail_createdate",
        "r1.lastusedate as thumbnail_lastusedate",
        "r1.contributorid as thumbnail_contributorid",
        "r1.thumbnailid as thumbnail_thumbnailid",
        "r1.public as thumbnail_public",
        "r1.editdate as thumbnail_editdate",
        "r1.thumbnailpos as thumbnail_thumbnailpos"
    );
	
	$request = "SELECT " . implode($select, ", ") . " FROM cr_resources r LEFT JOIN cr_resources r1 ON r.thumbnailid=r1.id WHERE r.resourceid=\"" . $resourceid . "\" AND r.type <> 'thumbnail'";
	
	$dbconn = get_centralized_db_connection();



	$data = $dbconn->get_record_sql($request);
	
	return $data;
}

function cr_moveFileToMediaFolder($filemanagerName, $isMultimedia, $createDate = 0)
{
	//get the draft item id
	$draftitemid = file_get_submitted_draft_itemid($filemanagerName);
	
	$courseid = optional_param('course', 0, PARAM_INT);

    if($courseid == 0){
        $context = context_system::instance();
    }else{
        $context = context_course::instance($courseid);
    }

	$context_id = $context->id;
		
	//then retrieves information about the file from the moodle database
	$draftareaFiles = file_get_drafarea_files($draftitemid, false);

	if(count($draftareaFiles->list) == 0){
		return array();
	}
	
	$fs = get_file_storage();
	
	$fileinfo = $draftareaFiles->list[0];

	//save the file in our area
	file_save_draft_area_files($draftitemid, $context_id, 'local_centralizedresources', 'transfered', 0);
		
	//and finally get the real file and move it to our folder
	$file = $fs->get_file($context_id, 'local_centralizedresources', 'transfered',
			0, $fileinfo->filepath, $fileinfo->filename);


	
	if ($file) {
		$filename = trim($file->get_filename());
		$posext = strrpos($filename, ".", -1);
		$ext = null;
		
		if($posext !== false){
			$ext = substr($filename, $posext+1);
			$ext = strtolower($ext);
			$filename = substr($filename, 0, $posext);
		}
		
		$return = cr_find_path_for_file(array(
				'mimetype' => $file->get_mimetype(),
				'extension' => $ext,
				'filemanager' => $filemanagerName,
				'is_multimedia' => $isMultimedia
		));
		
		$filehash = $file->get_contenthash();

		if(!empty($return['path'])){
			if (!file_exists($return['path']))
			{
				mkdir($return['path'], 0755, true);
			}
			
			//copy to our folder and delete the draft file of moodle
			$folder = $return['path'] . '/'
					. substr($filehash, 0, 2) . '/';

			if (!file_exists($folder))
			{
				mkdir($folder, 0755, true);
			}
			
			if($createDate == 0){
				$createDate = time();
			}

			if($isMultimedia == 'multimedia_file'){
				$folder .= $filehash . $createDate . '/';
				
				if(is_dir($folder)){
					cr_unlink($folder, true);
				}
				
				mkdir($folder, 0775, true);
				
				$folder .= $filehash . $createDate . '.' . $ext;
				
			}else{
				$folder .= $filehash . $createDate . '.' . $ext;
			}
			
			$cleanname = cr_clean_filename($filename);
			
			if(!empty($ext)){
				$filename .= '.' . $ext;
				$filename = str_replace(";", "", $filename);
				$cleanname .= '.' . $ext;
			}
			
			$file->copy_content_to($folder);
			
			$file->delete();
			
			return array(
					'hashname' => $filehash,
					'cleanname' => $cleanname,
					'filename' => $filename,
					'type' => $return['type'],
					'extension' => $ext,
					'filesize' => $file->get_filesize(),
					'createDate' => $createDate,
					'mimetype' => get_mimetype_description(array('filename' => $filename))
			);
		}
		else {
			throw new moodle_exception("Error the destination path doesn't exist.");
		}
		
		
			
	} else {
		return array();
	}
}

function cr_unlink($filename,$isDiapo = false)
{
	global $CFG;
	
	$folders = array_values($CFG->centralizedresources_media_types);
	
	$uri = explode('/', $filename);

	if(is_dir($filename)){
		if ($isDiapo)
		{
			rrmdir($filename);
		}
	}else{
		unlink($filename);
	}
	
	
	for($i = count($uri) - 2; $i >= 0; $i--){
		
		if(in_array($uri[$i], $folders)){
			return;
		}
	
		$folder = '';
		for($j = 0; $j <= $i; $j++){
			$folder .= $uri[$j] . '/';
		}
		
		rmdir($folder);
	}
}

function rrmdir($dir) {
	if (is_dir($dir)) {
		$objects = scandir($dir);
		foreach ($objects as $object) {
			if ($object != "." && $object != "..") {
				if (filetype($dir."/".$object) == "dir") rrmdir($dir."/".$object); else unlink($dir."/".$object);
			}
		}
		reset($objects);
		rmdir($dir);
	}
}

function cr_get_list_file($dir) {
  if (!is_readable($dir)) return null; 
  
  $scanned_directory = array_diff(scandir($dir), array('..', '.'));
  
  return $scanned_directory;
}