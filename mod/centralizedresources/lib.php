<?php
require_once($CFG->dirroot . '/local/centralizedresources/controller/addresource.php');
require_once($CFG->dirroot . '/mod/centralizedresources/lib/cr_lib.php');

function centralizedresources_supports($feature) {
	switch($feature) {
		case FEATURE_MOD_ARCHETYPE:
			return MOD_ARCHETYPE_RESOURCE;
		case FEATURE_MOD_INTRO:
			return true;
		case FEATURE_SHOW_DESCRIPTION:
			return true;
		case FEATURE_GRADE_HAS_GRADE:
			return false;
		case FEATURE_BACKUP_MOODLE2:
			return true;
		default:
			return null;
	}
}

/**
 * Saves a new instance of the centralizedresources into the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param object $centralizedresource An object from the form in mod_form.php
 * @param mod_centralizedresources_mod_form $mform
 * @return int The id of the newly inserted centralizedresources record
 */
function centralizedresources_add_instance(stdClass $centralizedresource, mod_centralizedresources_mod_form $mform = null) {
    global $DB, $CFG;

    $centralizedresource->timecreated = time();
    $centralizedresource->timemodified = time();
        
    centralizedresources_set_display_options($centralizedresource);	
	
	if(intval($centralizedresource->add_new_resource == 1)){		
		$fileinfo = cr_moveFileToMediaFolder('attachments', $centralizedresource->type);
		if(empty($fileinfo)){
			redirect($CFG->wwwroot.'/course/modedit.php?add=centralizedresources&type=&course='.$centralizedresource->course.'&section=0&return=1&sr=');
		}
		else{
			$centralizedresource->filename = $fileinfo['filename'];
			$centralizedresource->hash = $fileinfo['hashname'];
			$centralizedresource->extension = $fileinfo['extension'];
			$centralizedresource->type = $fileinfo['type'];
			$centralizedresource->filesize = $fileinfo['filesize'];
			$centralizedresource->cleanname =$fileinfo['cleanname'];
			$centralizedresource->createDate = $fileinfo['createDate'];
			$centralizedresource->lastusedate = $fileinfo['createDate'];
			$centralizedresource->editdate = $fileinfo['createDate'];

            $centralizedresource->domainrestricted  = (isset($centralizedresource->domainrestricted) ? $centralizedresource->domainrestricted : 0);

            $centralizedresource->resourceid = sha1($centralizedresource->hash . $centralizedresource->createDate);
	
			cr_insertResource($centralizedresource);
			
			$where = 'resourceid = "' . $centralizedresource->resourceid . '" AND type <> "thumbnail"';
			$cr_added = get_cr_resource($where);
			$centralizedresource->centralizedresourceid = $cr_added->id;
		}
	}
	else{
		if(intval($centralizedresource->centralizedresourceid == 0)){
			redirect($CFG->wwwroot.'/course/modedit.php?add=centralizedresources&type=&course='.$centralizedresource->course.'&section=0&return=1&sr=');
		}
		else{
			$dbconn = get_centralized_db_connection();
			$query = 'UPDATE cr_resources SET ' . 'lastusedate=' . time();		
			$query .= ' where id="' . $centralizedresource->centralizedresourceid . '"';
				
			$dbconn->execute($query);
		}
	}
	
    return $DB->insert_record('centralizedresources', $centralizedresource);
}

/**
 * Updates an instance of the centralizedresources in the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param object $centralizedresource An object from the form in mod_form.php
 * @param mod_centralizedresources_mod_form $mform
 * @return boolean Success/Fail
 */
function centralizedresources_update_instance(stdClass $centralizedresource, mod_centralizedresources_mod_form $mform = null) {
	global $DB, $CFG;

	$centralizedresource->timemodified = time();
	$centralizedresource->id = $centralizedresource->instance;
	
	centralizedresources_set_display_options($centralizedresource);
	
	if ($centralizedresource->display == RESOURCELIB_DISPLAY_DOWNLOAD) {
		$where = "id = \"" . $centralizedresource->centralizedresourceid . "\"";
		$cr_resource = get_cr_resource($where);
		if($cr_resource->type == $CFG->centralizedresources_media_types['diaporama']){
			redirect($CFG->wwwroot.'/course/modedit.php?update='.$centralizedresource->coursemodule.'&forbiddenfd=1&sr=0');
		}
	}

	if(intval($centralizedresource->add_new_resource == 1)){		
			$fileinfo = cr_moveFileToMediaFolder('attachments', $centralizedresource->type);
			if(empty($fileinfo)){
				redirect($CFG->wwwroot.'/course/modedit.php?update='.$centralizedresource->coursemodule.'&return=1&sr=0');
			}
			else{
				
				$centralizedresource->filename = $fileinfo['filename'];
				$centralizedresource->hash = $fileinfo['hashname'];
				$centralizedresource->extension = $fileinfo['extension'];
				$centralizedresource->type = $fileinfo['type'];
				$centralizedresource->filesize = $fileinfo['filesize'];
				$centralizedresource->cleanname =$fileinfo['cleanname'];
				$centralizedresource->createDate = $fileinfo['createDate'];
				$centralizedresource->lastusedate = $fileinfo['createDate'];
				$centralizedresource->editdate = $fileinfo['createDate'];

                $centralizedresource->domainrestricted  = (isset($centralizedresource->domainrestricted) ? $centralizedresource->domainrestricted : 0);

				$centralizedresource->resourceid = sha1($centralizedresource->hash . $centralizedresource->createDate);
		
				cr_insertResource($centralizedresource);
				
                $where = 'resourceid = "' . $centralizedresource->resourceid . '" AND type <> "thumbnail"';
				$cr_added = get_cr_resource($where);
				$centralizedresource->centralizedresourceid = $cr_added->id;
			}
		}
	else{
		if(intval($centralizedresource->centralizedresourceid == 0)){
			redirect($CFG->wwwroot.'/course/modedit.php?update='.$centralizedresource->coursemodule.'&return=1&sr=0');
		}
		else{
		$dbconn = get_centralized_db_connection();
		$query = 'UPDATE cr_resources SET ' . 'lastusedate=' . time();		
		$query .= ' where id="' . $centralizedresource->centralizedresourceid . '"';
			
		$dbconn->execute($query);
		}
	}

	return $DB->update_record('centralizedresources', $centralizedresource);
}

/**
 * Removes an instance of the centralizedresources from the database
 *
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 */
function centralizedresources_delete_instance($id) {

	global $DB;

	if (! $centralizedresource = $DB->get_record('centralizedresources', array('id' => $id))) {
		return false;
	}

	# Delete any dependent records here #

	$DB->delete_records('centralizedresources', array('id' => $centralizedresource->id));

	// return false;
	return true;
}

/**
 * Updates display options based on form input.
 *
 * Shared code used by centralizedresources_add_instance and centralizedresources_update_instance.
 *
 * @param object $data Data object
 */
function centralizedresources_set_display_options($data) {
	$displayoptions = array();
	if ($data->display == RESOURCELIB_DISPLAY_POPUP) {
		$displayoptions['popupwidth']  = $data->popupwidth;
		$displayoptions['popupheight'] = $data->popupheight;
	}
	if (!empty($data->showsize)) {
		$displayoptions['showsize'] = 1;
	}
	$data->displayoptions = serialize($displayoptions);
}
