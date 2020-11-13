<?php
//require_once('../../config.php');

require_once('libffmpeg.php');

function cr_insertResource($data)
{
	global $CFG;

	try {
		
		$data->thumbnailid = null;
		$data->subtitleid = null;
		$data->chapterid = null;
			
		$data->contributorid = cr_insertContributor();
		
		if($data->type == 'diaporama')
		{
			cr_extract_diaporama($data);
		}
			
		else if($data->type == 'video')
		{
			$data->thumbnailid = cr_create_thumbnail_video($data);
			$data->subtitleid = cr_create_subtitle_file($data);
			$data->chapterid = cr_create_chapter_file($data);
		}
			
		else if($data->type == 'image')
		{
			$data->thumbnailid = cr_create_thumbnail_image($data);
		}

		cr_insertResourceIntoDB($data);
	} catch(Exception $e) {
		echo 'ERROR: ' . $e->getMessage();

	}
}

function cr_insertResourceIntoDB($data)
{
	$time = time();

	$data->title = substr($data->title, 0, 255);
	$data->description = substr($data->description, 0, 10000);
	$data->filename = substr($data->filename, 0, 255);
	$data->cleanname = substr($data->cleanname, 0, 255);

	if(!isset($data->domainrestricted)){
        $data->domainrestricted = 0;
    }

	$resource = array(
		'name' => $data->title,
		'description' => $data->description,
		'createdate' => $data->createDate,
		'lastusedate' => $data->createDate,
		'contributorid' => $data->contributorid,
		'public' => 1,
		'resourceid' => $data->resourceid,
		'hashname' => $data->hash,
		'type' => $data->type,
		'extension' => $data->extension,
		'filename' => $data->filename,
		'filesize' => $data->filesize,
		'cleanname' => $data->cleanname,
		'editdate' => $data->createDate,
		'thumbnailid' => $data->thumbnailid,
		'thumbnailpos'  => 0,
		'subtitleid' => $data->subtitleid,
		'chapterid' => $data->chapterid,
        'domainrestricted' => $data->domainrestricted
	);
	
	if ($data->type == 'video' || $data->type == 'audio')
	{
	    $resource['encoded'] = '0';
	}
	
	$dbconn = get_centralized_db_connection();
	
	$dbconn->insert_record('cr_resources', $resource);
}

function cr_insertContributor()
{
	global $USER, $CFG;
	
	$academy = $CFG->academie_name;

	$dbconn = get_centralized_db_connection();
	
	$employeeNumber = '';
	
	if (isset($USER->profile['employeeNumber']))
	{
		$employeeNumber = $USER->profile['employeeNumber'];
	}
	$contributor = false;
	if (strlen($employeeNumber) > 2)
	{
		$contributor = $dbconn->get_record_sql('SELECT * FROM cr_contributor WHERE numen="' . $employeeNumber . '" AND academie="' . $academy . '"');
	}
	
	if ($contributor === false && strlen($USER->email) > 2)
	{
		$contributor = $dbconn->get_record_sql('SELECT * FROM cr_contributor WHERE email="'. $USER->email . '" AND academie="' . $academy . '"');
	}

	$contributorId = -1;
	
	if(isset($contributor->id)){
		$contributorId = $contributor->id;
	}else{
		$data = array(
				'firstname' => $USER->firstname,
				'lastname' => $USER->lastname,
				'email' => $USER->email,
				'numen' => $employeeNumber,
				'academie' => $academy
		);
		
		$contributorId = $dbconn->insert_record('cr_contributor', $data);
	}
	

	return $contributorId;
}

function cr_create_thumbnail_video($data)
{
	global $CFG;
	
	if (!file_exists($CFG->centralizedresources_media_path['thumbnail']))
	{
		mkdir($CFG->centralizedresources_media_path['thumbnail'], 0775, true);
	}
	
	$secondes = optional_param('tsecondes', '', PARAM_INT);
	
	$data->thumbnailposition = intval($secondes);
	
	rand();
	$tmpname = rand(1,2000000000) . time();
	
	$thumbnailpath = $CFG->tempdir.'/' . $tmpname  . '.png';
	
	$returnStatus = ffmpeg::create_thumbnail($CFG->centralizedresources_media_path['video'] . substr($data->hash, 0, 2) . '/' . $data->hash . $data->createDate . '.' . $data->extension, $thumbnailpath, $data->thumbnailposition);
	
	$thumbnailid = null;
	
	
	//If all it's ok
	if($returnStatus == 0){

		$hashname= sha1_file($thumbnailpath);
		$filesize = filesize($thumbnailpath);
		
		$data = array(
				'name' => 'thumbnail',
				'public' => 0,
				'filename' => 'thumb.png',
				'cleanname' => 'thumb.png',
				'filesize' => $filesize,
				'type' => 'thumbnail',
				'resourceid' => sha1($hashname. $data->createDate),
				'lastusedate' => $data->lastusedate,
				'createdate' => $data->createDate,
				'editdate' => $data->editdate,
				'contributorid' => $data->contributorid,
				'hashname' => $hashname,
				'thumbnailpos' => $data->thumbnailposition,
				'extension' => 'png',
				'thumbnailid' => null,
				'description' => ''
		);
		
		$dbconn = get_centralized_db_connection();
		
		$thumbnailid = $dbconn->insert_record('cr_resources', $data);
		
		$path = $CFG->centralizedresources_media_path['thumbnail'] . '/' . substr($hashname, 0, 2);
		
		if (!file_exists($path))
		{
			mkdir($path, 0775, true);
		}
		
		$dest = $CFG->centralizedresources_media_path['thumbnail'] . '/' . substr($hashname, 0, 2) . '/' . $hashname. $data['createdate'] . '.png';
		
		rename($thumbnailpath, $dest);
	}
	
	return $thumbnailid;
}

function cr_create_thumbnail_image($data)
{
	global $CFG;

	if (!file_exists($CFG->centralizedresources_media_path['thumbnail']))
	{
		mkdir($CFG->centralizedresources_media_path['thumbnail'], 0775, true);
	}
	
	$sourceImagePath = $CFG->centralizedresources_media_path['image'] . substr($data->hash, 0, 2) . '/' . $data->hash . $data->createDate . '.' . $data->extension;
	
	$hashname = sha1_file($sourceImagePath);
	
	$thumbnailpath = $CFG->centralizedresources_media_path['thumbnail'] . '/' . substr($hashname, 0, 2) . '/' . $hashname. $data->createDate . '.png';
	
	mkdir($CFG->centralizedresources_media_path['thumbnail'] . '/' . substr($hashname, 0, 2), 0755, true);
	
	$img = thumbnailImage($sourceImagePath);
	
	$thumbnailid = null;
	
	if($img){
		imageToFile($img, $thumbnailpath);
		
		$data = array(
				'name' => 'thumbnail',
				'public' => 0,
				'filename' => 'thumb.png',
				'filesize' => filesize($thumbnailpath),
				'type' => 'thumbnail',
				'resourceid' => sha1($hashname. $data->createDate),
				'lastusedate' => $data->lastusedate,
				'createdate' => $data->createDate,
				'editdate' => $data->editdate,
				'contributorid' => $data->contributorid,
				'hashname' => $hashname,
				'extension' => 'png',
				'thumbnailid' => null,
				'thumbnailpos' => 0,
				'description' => '',
				'cleanname' => 'thumb.png'
		);
		
		$dbconn = get_centralized_db_connection();

		$thumbnailid =  $dbconn->insert_record('cr_resources', $data);
	}
	
	return $thumbnailid;
}

function cr_extract_diaporama($data)
{
	global $CFG;
	
	mkdir($CFG->centralizedresources_media_path['diaporama'], 0755, true);
	
	//extract the zip
	$zipArchive = new ZipArchive;
	
	$extractPath = $CFG->centralizedresources_media_path['diaporama'] . substr($data->hash, 0, 2) . '/' . $data->hash . $data->createDate;
	
	if($zipArchive->open($extractPath . '/' . $data->hash . $data->createDate . '.' . $data->extension) == true){

		if($zipArchive->extractTo($extractPath) == false)
		{
			throw new moodle_exception("Error while unziping a file");
		}
		
		$zipArchive->close();
		
		unlink($extractPath . '/' . $data->hash . $data->createDate . '.' . $data->extension);
	}
	
	return $extractPath;
}

function cr_create_subtitle_file($data)
{
	return cr_create_attached_video_file('subtitle', $data);
}

function cr_create_chapter_file($data)
{
	return cr_create_attached_video_file('chapter', $data);
}

function cr_create_attached_video_file($typefile, $data)
{
	global $CFG;

	if (!file_exists($CFG->centralizedresources_media_path[$typefile]))
	{
		mkdir($CFG->centralizedresources_media_path[$typefile], 0775, true);
	}
	$attachedfile = cr_moveFileToMediaFolder($typefile, false);

	$attachedfileid = null;

	if($attachedfile){

		$data = array(
				'name' => $typefile,
				'public' => 0,
				'filename' => $attachedfile['filename'],
				'filesize' => $attachedfile['filesize'],
				'type' => $attachedfile['type'],
				'resourceid' => sha1($attachedfile['hashname'] . $attachedfile['createDate']),
				'lastusedate' => $data->lastusedate,
				'createdate' => $attachedfile['createDate'],
				'editdate' => $data->editdate,
				'contributorid' => $data->contributorid,
				'hashname' => $attachedfile['hashname'],
				'extension' => $attachedfile['extension'],
				'thumbnailid' => null,
				'thumbnailpos' => 0,
				'description' => '',
				'cleanname' => $attachedfile['cleanname'],
				'subtitleid' => '',
				'chapterid' => ''
		);

		$dbconn = get_centralized_db_connection();

		$attachedfileid =  $dbconn->insert_record('cr_resources', $data);
	}

	return $attachedfileid;
}
