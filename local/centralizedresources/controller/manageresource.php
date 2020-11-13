<?php

require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot.'/local/centralizedresources/form/manageResource_form.php');
require_once($CFG->dirroot.'/local/centralizedresources/lib/cr_file_api.php');
require_once($CFG->dirroot.'/local/centralizedresources/form/addResource_form.php');
require_once($CFG->dirroot.'/local/centralizedresources/lib/cr_insert_resource.php');

class local_centralizedresources_controller_manageresource extends mr_controller {

	protected function init()
	{
		
	}
	
	public function default_action()
	{
		global $PAGE, $CFG, $USER, $COURSE;

		$context = $this->get_context();

		
		$view = '';
		
		if(has_capability('local/centralizedresources:manage', $context)){
			$manageResourceForm = new manageResource_form(new moodle_url('/local/centralizedresources/view.php?controller=manageresource&action=search'));

			//Display the form
			ob_start();
			$manageResourceForm->display();

            $view .= '<div class="resource-search">';
			$view .= ob_get_contents();
            $view .= '</div>';

			ob_end_clean();
						
			$editownresource = has_capability('local/centralizedresources:editownressource', $context);
			$editresource = has_capability('local/centralizedresources:editressource', $context);
			
			$addressourceurl = $CFG->wwwroot . '/local/centralizedresources/view.php?controller=addresource&action=default&courseid=' . $COURSE->id;
			
			$returnurl = $CFG->wwwroot.'/course/view.php?id='.$COURSE->id;
			
			$return_button_label = get_string('local_cr_return_button_label', 'local_centralizedresources');
			$jtable_title = get_string('local_cr_jtable_title', 'local_centralizedresources');
			$add_resource_button_label = get_string('local_cr_add_resource_button_label', 'local_centralizedresources');

            $PAGE->requires->jquery_plugin('ui-css');
            $PAGE->requires->jquery_plugin('jtable-css');

            $PAGE->requires->js_call_amd('local_centralizedresources/resultSearchTable', 'init', array(
                    $CFG->wwwroot . '/local/centralizedresources/view.php?controller=manageresource&action=search&courseid='.required_param('courseid', PARAM_INT),
                    $CFG->wwwroot . '/local/centralizedresources/view.php?controller=manageresource&action=getpagerecord',
                    false,
                    ($editownresource || $editresource),
                    has_capability('local/centralizedresources:addresource', $context) ? true: false,
                    true,
                    $addressourceurl,
                    $returnurl,
                    $jtable_title,
                    $return_button_label,
                    $add_resource_button_label,
                    '',
                    ''
                )
            );

            $view .= '<div id="search_result" style="width:100%"></div>';
		}
		
		return $view;
	}
	
	public function search_action()
	{
		global $USER, $CFG, $COURSE, $OUTPUT;

		$contextid = optional_param('contextid', false, PARAM_INT);
		
		$context = $this->get_context();
		if($contextid != false){
			$context = context::instance_by_id($contextid);
		}
		
		if(!has_capability('local/centralizedresources:manage', $context)){
			return '';	
		}
		
		$creator_search = optional_param('creator_search', '', PARAM_TEXT);
		
		$filter_params = array(
			'document' => optional_param('document', false, PARAM_BOOL),
			'video' => optional_param('video', false, PARAM_BOOL),
			'image' => optional_param('image', false, PARAM_BOOL),
			'audio' => optional_param('audio', false, PARAM_BOOL),
			'archive' => optional_param('archive', false, PARAM_BOOL),
			'diaporama' => optional_param('diaporama', false, PARAM_BOOL),
			'file' => optional_param('other', false, PARAM_BOOL)
		);
		
		$creator_is_me = intval(optional_param('creator_is_me', 0, PARAM_INT));
		
		$domain_restriction = intval(optional_param('domain_restriction', 0, PARAM_INT));
		
		$field_search = optional_param('field_search', '', PARAM_TEXT);
		
		$so = required_param('so', PARAM_TEXT);
		$si = required_param('si', PARAM_INT);
		$ps = required_param('ps', PARAM_INT);
		
		//Build the query to fetch the data from the database
		$where = 'r.contributorid = c.id AND r.public = 1';
		
		$numen = '';
		if(isset($USER->profile) && isset($USER->profile['employeeNumber'])){
			$numen = $USER->profile['employeeNumber'];
		}
			
		if($creator_is_me == 1)
		{
			if($numen != ''){
				$where .= ' AND c.numen="'.$numen.'"';
			}else{
				$creator_search = "$USER->email";
			}
			
		}
		
		//Search on the creator attributes
		if(!empty($creator_search)){
			$creator_search = str_replace("\\",'',str_replace("'",'',str_replace('%','',trim($creator_search))));
			
			$creator = explode(" ", $creator_search);
			
			foreach($creator as $val)
			{
				$where .= ' AND (c.firstname LIKE "%' . $val . '%" OR c.lastname LIKE "%' . $val . '%" OR c.email LIKE "%' . $val . '%")';
			}
			
		}
		
		if(!empty($field_search)){
			
			$field_search = str_replace("\\",'',str_replace('"','',str_replace('%','',trim($field_search))));
				
			$search = explode(" ", $field_search);
			
			$where_name = array();
			$where_description = array();
			
			foreach($search as $keyword)
			{
				
				$keyword_trim = trim($keyword);
				
				if(strlen($keyword_trim) > 2)
				{
					$where_name[] = '(r.name LIKE "%' . $keyword_trim . '%")';
					$where_description[] = '(r.description LIKE "%' . $keyword_trim . '%")';
				}
			}
			
			if(!empty($where_name) && !empty($where_description))
			{
				$where .= ' AND (';
				$where .= implode(' OR ', $where_name);
				$where .= ' OR ';
				$where .= implode(' OR ', $where_description);
				$where .= ')';
			}
		}
		
		if($domain_restriction == 1)
		{
			$uri = explode("/", $CFG->wwwroot);
			$academy = $uri[count($uri)-1];
			$academy = strtolower($academy);
			
			$where .= ' AND c.academie LIKE "%' . $academy . '%"';
		}
		
		if(in_array(1, array_values($filter_params)))
		{
			$where .= ' AND (';
			
			$filterwhere = array();
			
			foreach($filter_params as $type => $selected){
				if($selected == 1){
					$filterwhere[] = 'r.type LIKE "' . $type . '"';
				}
			}
			
			$where .= implode(" OR ", $filterwhere);
			
			$where .= ')';
		}
		
		$date = optional_param('datepicker', '', PARAM_TEXT);
		
		if($date != ''){
			list($day, $month, $year) = explode('/', $date);
				
			$date = mktime(23,59,59,$month,$day,$year);
			
			$where .= ' AND r.editdate > ' . $date;
		}

		try{
			$courseid = optional_param('courseid', 1, PARAM_INT); // variable optionnelle car le mod_centralizedresources ne se sert pas de la case action.
			
			$resourceUpdateUrl = $CFG->wwwroot . "/local/centralizedresources/view.php?controller=manageresource&amp;action=updateresource&amp;courseid=".$courseid."&amp;resourceid=";
		
			$editownresource = has_capability('local/centralizedresources:editownressource', $context) ? 1 : 0;
			
			$editresource = has_capability('local/centralizedresources:editressource', $context) ? 1 : 0;
			
			$email = $USER->email;
			
			$author_string = "CONCAT('<a href=\"mailto:', c.email, '\">', c.firstname, ' ', c.lastname, '</a>')";
			
			$uri = explode("/", $CFG->wwwroot);
			$myacademy = $uri[count($uri)-1];
			$myacademy= strtolower($myacademy);

			$editownresourceclause = '';
			if(!empty($numen)){
			    $editownresourceclause .= '(c.numen like "'. $numen .'")';
            }else{
                $editownresourceclause .= '(c.numen = "" AND c.email="'.$email.'")';
            }




			$request = "SELECT SQL_CALC_FOUND_ROWS
r.id, r.name, r.type, r.description, r.extension, IF(r.editdate>0,CONCAT('/Date(',r.lastusedate,'000)/'),'') AS lastusedate, r.views,
c.academie AS domain, CONCAT(c.firstname, ' ', c.lastname) AS creator,
c.email,
IF(r.editdate>0,CONCAT('/Date(',r.editdate,'000)/'),'') AS updateDate,
	CASE
	WHEN " . $editownresource ."=1 AND ((".$editownresourceclause.")  AND c.academie like \"" . $myacademy . "\") THEN r.resourceid 
	WHEN ". $editresource ."=1 AND c.academie like \"" . $myacademy . "\" THEN r.resourceid
	ELSE NULL 
	END AS action
FROM cr_resources r, cr_contributor c
WHERE " . $where . " AND r.type != 'videolr' AND r.type != 'thumbnail' AND r.type != 'subtitle' AND r.type != 'chapter' AND r.type != 'indexthumb'
AND (r.domainrestricted=0 OR (r.domainrestricted=1 AND c.academie LIKE \"".$myacademy."\"))
ORDER BY " . $so . "
LIMIT " . $si . "," . $ps;


			$dbconn = get_centralized_db_connection();

			$search_result = $dbconn->get_records_sql($request);

			$count_row = $dbconn->get_record_sql("SELECT FOUND_ROWS() as found_rows");
			
			foreach($search_result as $row)
			{
				
				if($row->type == 'diaporama')
				{
					list($ext_description,$ext_icon) = get_extension_info('diapo');	
				}else{
					list($ext_description,$ext_icon) = get_extension_info(strtolower($row->extension)); //strtolower pour avoir toujour une extension en minuscule
				}
				$img = '<img src="' . $OUTPUT->image_url('fileicon/'.$ext_icon, 'theme') . '"/>';

				$row->label_type = $img . $ext_description;

				$preview_url = new moodle_url('/local/centralizedresources/preview.php', array('id' => $courseid, 'r' => $row->id));

                $row->name = html_writer::link($preview_url, $row->name, ['target' => '_blank']);

				//add the description with the information icon
				$information_icon = '<img style="float:right" src="' . $OUTPUT->image_url('general/information', 'theme') . '" title="' . $row->description . '"/>';
				
				$row->name .= $information_icon;
				
				if(isset($row->action)){
					$row->action = $resourceUpdateUrl . $row->action;
				}
				
				$row->creator = '<a href="mailto:' . $row->email . '">' . $row->creator . '</a>';
			}

			//Return result to jTable
			$jTableResult = array();
			$jTableResult['Result'] = "OK";
			$jTableResult['TotalRecordCount'] = $count_row->found_rows;
			$jTableResult['Records'] = $search_result;
			
			print json_encode($jTableResult);
		}catch(Exception $e){
			$jTableResult = array();
			$jTableResult['Result'] = "ERROR";
			$jTableResult['Message'] = $e->getMessage();

			print json_encode($jTableResult);
		}		
	}
	
	public function updateresource_action()
	{
		global $PAGE, $CFG;
		
		$addResourceForm = new addResource_form($PAGE->url);
		
		$context = $this->get_context();
		
		$view = '';
		
		$resourceid = required_param('resourceid', PARAM_TEXT);
			
		$haseditcapabilityforthisressource = $this->check_capability_edit($resourceid);
		
		if($haseditcapabilityforthisressource == false){
			return '';	
		}
		
		if($addResourceForm->is_submitted()){
			$data = $addResourceForm->get_submitted_data();

			if($data !== null){
				
				if(isset($data->cr_cancel) && $data->cr_cancel)
				{
					redirect($CFG->wwwroot.'/local/centralizedresources/view.php?controller=manageresource&action=default&courseid='.$data->course);
				}
					
				
				$data->resourceid = required_param('resourceid', PARAM_TEXT);
					
				$this->updateresource($data);
				
				$msg = get_string('local_cr_update_resource_validation_text', 'local_centralizedresources');
				
				if($data->cr_save_return)
				{
					redirect($CFG->wwwroot.'/course/view.php?id='.$data->course, "<p>".$msg."</p>");
				}
				
				if($data->cr_save)
				{
					redirect($CFG->wwwroot.'/local/centralizedresources/view.php?controller=manageresource&action=default&courseid='.$data->course, "<p>".$msg."</p>");
				}
			}
		}else{
			$resourceData = cr_get_resource_data_with_thumbnail_data($resourceid);

			$secondes = '';
			
			if($resourceData->resource_type == 'video' && $resourceData->thumbnail_thumbnailpos != null){
				$secondes = intval($resourceData->thumbnail_thumbnailpos);
			}
			
			$type = ($resourceData->resource_type == 'diaporama' ? 'multimedia_file' : 'single_file');

			$formData = array(
                'title' => $resourceData->resource_name,
                'description' => $resourceData->resource_description,
                'tsecondes' => $secondes,
                'type' => $type,
                'mimetype' => $resourceData->resource_type,
                'resourceid' => required_param('resourceid', PARAM_TEXT),
                'domainrestricted' => $resourceData->resource_domainrestricted
			);
			
			$addResourceForm->set_data($formData);
			
			ob_start();
			$addResourceForm->display();
			$view = ob_get_contents();
			
			ob_clean();
		}
		
		
		return $view;
	}
	
	private function updateresource($data)
	{
		global $CFG, $USER;
		
		$data->title = substr($data->title, 0, 255);
		$data->description = substr($data->description, 0, 10000);
		$data->filename = substr($data->filename, 0, 255);
		$data->cleanname = substr($data->cleanname, 0, 255);

        if(!isset($data->domainrestricted)){
            $data->domainrestricted = 0;
        }

		$dbconn = get_centralized_db_connection();
		
		$isMultimedia = ($data->type == 'multimedia_file');
		
		$resource = $dbconn->get_record_sql('SELECT * FROM cr_resources r WHERE r.resourceid="'. $data->resourceid . '"');
		
		$fileinfo = cr_moveFileToMediaFolder('attachments', $isMultimedia, $resource->createdate);

		$data->contributorid = cr_insertContributor(get_centralized_db_connection());
		
		if($fileinfo){
			
			$data->filename = $fileinfo['filename'];
			$data->hash = $fileinfo['hashname'];
			$data->extension = $fileinfo['extension'];
			$data->type = $fileinfo['type'];
			$data->filesize = $fileinfo['filesize'];
			$data->cleanname =$fileinfo['cleanname'];
			$data->createDate = $fileinfo['createDate'];
			$data->lastusedate = $fileinfo['createDate'];
			$data->editdate = time();
			$data->mimetype = $fileinfo['mimetype'];
			$data->thumbnailid = null;
			$data->subtitleid = null;
			$data->chapterid = null;

			//remove the old resourse file
			$basefolder = $CFG->centralizedresources_media_path[$data->type] . substr($data->hash, 0, 2) . '/';
			$basefolderresource = $CFG->centralizedresources_media_path[$resource->type] . substr($resource->hashname, 0, 2) . '/';
			
			$newname = $data->hash . $data->createDate . '.' . $data->extension;
			$oldname = $data->hash . $resource->createdate . '.' . $data->extension;

			if($data->type == 'diaporama'){
				$oldsubfolder = $data->hash . $data->createDate . '/';
				$newsubfolder = $data->hash . $resource->createdate . '/';
				
				if($newsubfolder != $oldsubfolder){
					mkdir($basefolder .  $newsubfolder, 0775, true);
					
					rename($basefolder . $oldsubfolder . $oldname, $basefolder . $newsubfolder . $newname);

					cr_unlink($basefolder . $oldsubfolder);
				}
				
				$data->createDate = $resource->createdate;
					
				cr_extract_diaporama($data);
			}else{
				
				$data->createDate = $resource->createdate;
				
				if($fileinfo['type'] == 'video')
				{
				    $data->encoded = 0;
				    
				    $data->thumbnailid = cr_create_thumbnail_video($data);
				    $data->subtitleid = cr_create_subtitle_file($data);
				    $data->chapterid = cr_create_chapter_file($data);
				}
				
				if($fileinfo['type'] == 'audio')
				{
				    $data->encoded = 0;
				}
				
				if($fileinfo['type'] == 'image')
				{
					$data->thumbnailid = cr_create_thumbnail_image($data);
				}
				
				if($oldname != $newname){
					$resourcetodelete = $basefolderresource .  $resource->hashname . $resource->createdate . '.' . $resource->extension;
					
					rename($basefolder . $oldname, $basefolder . $newname);
					
					cr_unlink($resourcetodelete);
					
					if ($resource->thumbnailid > 0)
					{
						$thumbnail = $dbconn->get_record_sql('SELECT * FROM cr_resources r WHERE r.id="'. $resource->thumbnailid. '"');
						
						cr_unlink($CFG->centralizedresources_media_path['thumbnail'].substr($thumbnail->hashname,0,2).'/'.$thumbnail->hashname.$thumbnail->createdate.'.'.$thumbnail->extension);
						
						$dbconn->execute("DELETE FROM cr_resources WHERE id = ".$resource->thumbnailid);
					}
					
					if (intval($resource->lowresid) > 0)
					{
						$resourcelr = $dbconn->get_record_sql('SELECT * FROM cr_resources r WHERE r.id="'. $resource->lowresid. '"');
						
						cr_unlink($CFG->centralizedresources_media_path['video'].substr($resourcelr->hashname,0,2).'/'.$resourcelr->hashname.$resourcelr->createdate.'.'.$resourcelr->extension);
						
						$dbconn->execute("DELETE FROM cr_resources WHERE id = ".$resource->lowresid);
					}
					
				}
			}
					
			$resource_update = array(
					'id' => $resource->id,
					'name' => $data->title,
					'description' => $data->description,
					'hashname' => $data->hash,
					'type' => $fileinfo['type'],
					'filename' => $fileinfo['filename'],
					'cleanname' => $fileinfo['cleanname'],
					'filesize' => $fileinfo['filesize'],
					'extension' => $data->extension,
					'lastusedate' => $data->lastusedate,
					'editdate' => $data->editdate,
					'contributorid' => $data->contributorid,
					'thumbnailid' => $data->thumbnailid,
					'subtitleid' => $data->subtitleid,
					'chapterid' => $data->chapterid,
					'lowresid' => null,
                    'domainrestricted' => $data->domainrestricted
			);

			if($fileinfo['type'] == 'video' || $fileinfo['type'] == 'audio')
			{
				$resource_update['encoded'] = 0;
			}else{
				$resource_update['encoded'] = null;
			}
			
		}else{
			
			$resourceData = cr_get_resource_data_with_thumbnail_data($data->resourceid);

            if($data->mimetype == "video")
            {
                if($resourceData->thumbnail_thumbnailpos != $data->tsecondes){
                    $data->thumbnailposition = $data->tsecondes;
                }
                $data->createDate = $resourceData->resource_createdate;
                $data->lastusedate = $resourceData->resource_lastusedate;
                $data->editdate = $resourceData->resource_editdate;
                $data->hash = $resourceData->resource_hashname;
                $data->extension = $resourceData->resource_extension;

                $data->thumbnailid = cr_create_thumbnail_video($data);
                $data->subtitleid = cr_create_subtitle_file($data);
                $data->chapterid = cr_create_chapter_file($data);
            }
			
			$resource_update = array(
                'id' => $resourceData->resource_id,
                'name' => $data->title,
                'description' => $data->description,
                'contributorid' => $data->contributorid,
                'thumbnailid' => $data->thumbnailid,
                'subtitleid' => $data->subtitleid,
                'chapterid' => $data->chapterid,
                'editdate' => time(),
                'domainrestricted' => $data->domainrestricted
			);
			

		}
        if($resource_update['thumbnailid'] == null){unset($resource_update['thumbnailid']);}
        if($resource_update['subtitleid'] == null){unset($resource_update['subtitleid']);}
        if($resource_update['chapterid'] == null){unset($resource_update['chapterid']);}

        $dbconn->update_record('cr_resources', $resource_update);
	}
	
	public function check_capability_edit($resourceid)
	{
		global $USER, $CFG;
		
		$context = $this->get_context();
		
		$dbconn = get_centralized_db_connection();
		
		$contributor = $dbconn->get_record_sql('SELECT * FROM cr_contributor c, cr_resources r WHERE r.resourceid="' . $resourceid . '" AND r.contributorid = c.id
				 AND c.firstname="' . $USER->firstname . '"
				 AND c.lastname="' . $USER->lastname . '"
				 AND c.email="' . $USER->email . '"
				 AND c.numen="' . $USER->idnumber . '"');

		$academy = $CFG->academie_name;

		$employeeNumber = '';

        if (isset($USER->profile['employeeNumber']))
        {
            $employeeNumber = $USER->profile['employeeNumber'];
        }

        if (strlen($employeeNumber) > 2)
        {
            $contributor = $dbconn->get_record_sql('SELECT * 
FROM cr_contributor c 
INNER JOIN cr_resources r ON r.contributorid=c.id
WHERE c.numen="' . $employeeNumber . '"
AND r.resourceid="'.$resourceid.'"
AND c.academie="' . $academy . '"');
        }

        if ($contributor === false && strlen($USER->email) > 2)
        {
            $contributor = $dbconn->get_record_sql('SELECT * 
FROM cr_contributor c 
INNER JOIN cr_resources r ON r.contributorid=c.id 
WHERE c.email="' . $USER->email. '"
AND r.resourceid="'.$resourceid.'"
AND c.academie="' . $academy . '"');
        }

		if($contributor){
			return has_capability('local/centralizedresources:editownressource', $context);
		}else {
            return has_capability('local/centralizedresources:editressource', $context);
        }
	}
	
	public function getpagerecord_action()
	{
		$so = required_param('so', PARAM_TEXT);
		$ps = required_param('ps', PARAM_INT);
		
		$record_id = required_param('record_id', PARAM_INT);
		
		$dbconn = get_centralized_db_connection();

		$result = $dbconn->get_record_sql('SELECT * FROM (SELECT @pos:=@pos+1 as pos, c.id
				FROM cr_resources c, (SELECT @pos:=-1) as init
				WHERE c.type <> "thumbnail" AND c.type <> "subtitle" AND c.type <> "chapter" AND c.type <> "indexthumb" AND c.type <> "videolr"
				AND c.public = 1
				ORDER BY ' . $so . ') a WHERE a.id = ' . $record_id);
		
		$page = 1;
		if ($result !== false && isset($result->pos))
		{
			$page = intval($result->pos / $ps) + 1;
		}
		
		$json = array();
		if (isset($result->pos))
		{
			$json['pos'] = $result->pos;
		}else{
			$json['pos'] = 0;
		}
		$json['page'] = $page;
		
		print json_encode($json);
	}
}