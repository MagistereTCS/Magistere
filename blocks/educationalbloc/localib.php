<?php
	function is_first_instance_using($instanceid){
		 global $DB;
		$instance = $DB->get_record_sql('SELECT * FROM {block_instances} WHERE id = ?', array($instanceid));
		if(empty($instance->configdata)){
			return true;
		}else { return false; }
	}
	
	function first_configuration($instanceid){
		global $CFG, $PAGE, $USER;				
		
		content_updating($instanceid);
		
		if($PAGE->course->id == 1){
			$redirect_url2 = $CFG->wwwroot.'?sesskey='.$USER->sesskey.'&bui_editid='.$instanceid;		
			header('Location: '.$redirect_url2); 
			exit;
		}else{
			$redirect_url = $CFG->wwwroot.'/course/view.php?id='.$PAGE->course->id.'&sesskey='.$USER->sesskey.'&bui_editid='.$instanceid;		
			header('Location: '.$redirect_url); 
			exit;
		}
	}
	
	function content_updating($instanceid){
		global $DB;
		$dataobject->id = $instanceid;
		$dataobject->configdata = 'A';
		
		$DB->update_record('block_instances', $dataobject);
	}
?>