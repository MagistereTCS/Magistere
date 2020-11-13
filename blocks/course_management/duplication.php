<?php
require_once('../../config.php');
require_once($CFG->dirroot . '/blocks/course_management/lib/duplication_lib.php');

global $DB;

$blockinstanceid = required_param('blockinstanceid', PARAM_INT);
$action_type = required_param('link_type', PARAM_TEXT);
$course_id = required_param('course_id', PARAM_INT);
$new_category = optional_param('new_category_course', '', PARAM_ALPHANUMEXT);	

$context = context_block::instance($blockinstanceid);

if(has_capability('block/course_management:'.$action_type, $context, $USER->id, TRUE)){
	if($action_type == 'createsessionfromparcours'){
		$new_name = required_param('new_course_name', PARAM_TEXT);
		$new_shortname = required_param('new_course_shortname', PARAM_TEXT);
		$move_type = required_param('move_type', PARAM_TEXT);
		
		$date = optional_param('datepicker_session', '', PARAM_TEXT);
		
		if($move_type == "move"){
			$tmpshortname = sha1($new_shortname . time());
			
			if(shortname_is_unique($tmpshortname, $course_id)){
				$courseurl = course_duplication($course_id, $action_type, $new_name, $tmpshortname, $new_category, $date);
				$old_course_shortname = $DB->get_record('course', array('id' => $course_id), 'shortname');
				
				$new_course = $DB->get_record('course', array('shortname' => $tmpshortname));
				
				delete_course($course_id, false);
				
				$new_course->shortname = $old_course_shortname->shortname;
				
				$DB->update_record('course', $new_course);
				redirect($courseurl);
			}
		}else if ($move_type == "duplication" && shortname_is_unique($new_shortname, $course_id)){
			$courseurl = course_duplication($course_id, $action_type, $new_name, $new_shortname, $new_category, $date);
			redirect($courseurl);
		}

	}elseif($action_type == 'archive'){
		
		$archive_type = required_param('access', PARAM_ALPHANUMEXT);	
		archive_course($course_id, $new_category, $archive_type);
	
	}elseif($action_type == 'unarchive'){
	
		unarchive_course($course_id, $new_category);
		
	}elseif($action_type == 'discard'){
		$corbeille = $DB->get_record('course_categories',array('name'=>'Corbeille'));
		if ($corbeille !== false)
		{
			discard_course($course_id, $corbeille->id);
		}
		
	}elseif($action_type == 'restorefromtrash'){
	
		restorefromtrash_course($course_id, $new_category);
		
	}else{
		//createparcoursfromgabarit //creategabaritfromparcours // createparcoursfromsession //duplicate		
		$new_name = required_param('new_course_name', PARAM_TEXT);
		$new_shortname = required_param('new_course_shortname', PARAM_TEXT);
			
		if(shortname_is_unique($new_shortname, $course_id)){			
			$courseurl = course_duplication($course_id, $action_type, $new_name, $new_shortname, $new_category);
			redirect($courseurl);
		}
	}
}
?>