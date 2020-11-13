<?php

require_once($CFG->dirroot .'/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
require_once($CFG->dirroot.'/local/magisterelib/indexationServices.php');

//fonction de vérification de l'unicité du shortname
	function shortname_is_unique($shortname, $courseid){
		global $DB;
		$result = $DB->get_records_menu('course',array('shortname'=>$shortname));
		if(count($result) != 0)
		{
			$_SESSION['short_name_not_unique'] = 'Ce nom de parcours est déjà utilisé, la duplication a été annulée.';
			$redirect_url = new moodle_url("/course/view.php?", array('id'=>$courseid));
			redirect($redirect_url);
		}
		else return true;
	}

//fonction de création de duplication général
	function course_duplication($courseid, $action_type, $name, $shortname, $category, $date=null){
		global $USER, $DB, $CFG;
		$backupid = course_backup($courseid, $USER->id, $action_type);				
		$transaction = $DB->start_delegated_transaction();
	
		$session_context = null;
		
		$formateur = $DB->get_record('role',array('shortname'=>'formateur'));
		
		if($action_type == 'createsessionfromparcours'
				|| $action_type == 'createparcoursfromsession'
				|| $action_type == 'createparcoursfromgabarit'
				|| $action_type == 'creategabaritfromparcours')
		{	
			$categorie_name = '';
			switch($action_type){
				case 'createsessionfromparcours': $categorie_name = 'Session de formation'; break;
				case 'createparcoursfromgabarit':
				case 'createparcoursfromsession':
					$categorie_name = 'Parcours de formation'; break;
				default:
					$categorie_name = 'Gabarit';
			}
			
			$course_categorie = $DB->get_record('course_categories',array('name'=>$categorie_name));
			$categorie_context = $DB->get_record('context',array('contextlevel'=>40, 'instanceid'=>$course_categorie->id ));
			role_assign($formateur->id, $USER->id, $categorie_context->id);		
		}
		
		try{
			$new_course = course_create ($name, $shortname, $category);
			course_restore($backupid, $new_course,  $USER->id, $action_type);	
		}catch(Exception $e) {
			echo 'Exception reçue : ',  $e->getMessage(), "\n";
		}
		
		if($action_type == 'createsessionfromparcours'
				|| $action_type == 'createparcoursfromgabarit'
				|| $action_type == 'createparcoursfromsession'
				|| $action_type == 'creategabaritfromparcours'){
			try{
				
				if ( $DB->record_exists('enrol', array('courseid'=>$new_course, 'enrol'=>'manual')) === false)
				{
					//gestion des méthode d'inscription
					$record_enrol = new stdClass();			              
					$record_enrol->enrol = 'manual';
					$record_enrol->courseid = $new_course;
					$DB->insert_record('enrol', $record_enrol, false);
				}

				//inscription au parcours
				enrol_course_creator($new_course, $USER->id, $formateur->id);

			}catch(Exception $e) {
				error_log('Exception reçue : ' . $e->getMessage());
				//die();
			}
			
			//retrait du rôle formateur sur les sessions
			role_unassign($formateur->id, $USER->id, $categorie_context->id);
		}
		
		// Commit
		$transaction->allow_commit();
		$record = new stdClass();
		$record->id = $new_course;
		$record->fullname  = $name;
		$record->shortname = $shortname;
		
		//gestion des date de début de parcours
		if($action_type == 'createsessionfromparcours'){
			$date = str_replace('/', '-', $date);				
			$date = strtotime($date);
			$record->startdate = $date;
		}
		elseif($action_type != 'duplicate' || $action_type == 'createparcoursfromgabarit' || $action_type == 'createparcoursfromsession'){
			$record->startdate = '0';		
		}

		//gestion de la visibilité de l'archive
		if($action_type == 'archive'){
			if($archive_type == 'hidden'){
				$record->visible = 0;		
			}else {	//visible or data_error
				$record->visible = 1;			
			}		
		}
		
		$record->category = $category;	
		
		$DB->update_record('course', $record);
		
		//traitement de l'indexation
        IndexationServices::copy_indexation($courseid, $new_course, $name);

        //2948 : JB - 14/03/2019 Traitement des activités Via
        if($action_type == 'duplicate' || $action_type == 'createsessionfromparcours'){
            get_via_activities_in_course($new_course);
        }

		//vidange du fichier
		$folder = $CFG->dataroot.'/temp/backup/';
		//clear_folder($folder);
		
		return new moodle_url("/course/view.php?", array('id'=>$new_course));																																											
	}
	
	//fonction d'archivage
	function archive_course($course_id, $course_category, $archive_type, $redirect = true){
		global $DB;
		$data_archive = new stdClass();
		$data_archive->id = $course_id;		
		
		$a_courseid[] = $course_id;
		move_courses($a_courseid, $course_category);		
		
		// edit visibility of course
		if($archive_type == 'hidden'){
			$data_archive->visible = 0;		
		}else {	//visible or data_error
			$data_archive->visible = 1;			
		}		
		//updating
		$DB->update_record('course', $data_archive);		
		//redirect
        if(!$redirect){
            return;
        }

		$nexturl = new moodle_url("/course/view.php?", array('id'=>$course_id));																																											
		redirect($nexturl);	
	}
	
	//reopen function
	function unarchive_course($course_id, $course_category){
		global $DB;
		$data_archive = new stdClass();
		$data_archive->id = $course_id;		
		$categoryid = $course_category;

		$a_courseid[] = $course_id;
		move_courses($a_courseid, $categoryid);		
		// visibility ok
		$data_archive->visible = 1;				
		//updating
		$DB->update_record('course', $data_archive);		
		//redirect
		$nexturl = new moodle_url("/course/view.php?", array('id'=>$course_id));																																											
		redirect($nexturl);		
	}
	
	//fonction de mise a la corbeille
	function discard_course($course_id, $course_category, $redirect = true){
		global $DB, $CFG;
		
		$course = $DB->get_record('course', array('id'=>$course_id));
		$category_save = new stdClass();
		$category_save->course_id = $course->id;
		$category_save->category_id = $course->category;
		$category_save->updatetime = time();
		
		if ( ! $DB->insert_record('course_trash_category', $category_save) )
		{
			echo 'L\'insertion dans la table corbeille a echouee, deplacement du cours interrompu!';
			return false;
		}
		
		
		require_once($CFG->dirroot . '/' . $CFG->admin . '/registration/lib.php');
		require_once($CFG->dirroot . '/course/publish/lib.php');
		
		
		/// UNPUBLISH
		$publicationmanager = new course_publish_manager();
		$registrationmanager = new registration_manager();
		
		$published = $DB->get_records('course_published', array('courseid'=>$course->id));
		
		if (count($published) > 0)
		{
		
			$courseids = array();
			$publicationids = array();
			$huburl = '';
			foreach ($published as $publish)
			{
				if ($huburl == '')
				{
					$huburl = $publish->huburl;
				}
				$publicationids[] = $publish->id;
				$courseids[] = $publish->hubcourseid;
			}
			
			//unpublish the publication by web service
			//$huburl = $published[0]->huburl;
			$registeredhub = $registrationmanager->get_registeredhub($huburl);
			$function = 'hub_unregister_courses';
			$params = array('courseids' => $courseids);
			$serverurl = $huburl."/local/hub/webservice/webservices.php";
			require_once($CFG->dirroot."/webservice/xmlrpc/lib.php");
			$xmlrpcclient = new webservice_xmlrpc_client($serverurl, $registeredhub->token);
			$result = $xmlrpcclient->call($function, $params);
	
			//delete the publication from the database
			foreach ($publicationids as $id)
			{
				$publicationmanager->delete_publication($id);
			}
		}
		
		
		$data_archive = new stdClass();
		$data_archive->id = $course_id;
		
		$a_courseid[] = $course_id;
		move_courses($a_courseid, $course_category);
	
		// edit visibility of course
		$data_archive->visible = 0;
		$data_archive->timemodified = time();
		
		//updating
		$DB->update_record('course', $data_archive);
		//redirect
		
		// Trigger a course updated event.
		$event = \block_course_management\event\course_trashed::create(array(
		    'objectid' => $course->id,
		    'context' => context_course::instance($course->id),
		    'other' => array('shortname' => $course->shortname,
		        'fullname' => $course->fullname,
		        'origin_category' => $category_save->category_id)
		));
		$event->trigger();

        if(!$redirect){
            return;
        }

		$nexturl = new moodle_url("/course/view.php?", array('id'=>$course_id));
		redirect($nexturl);
	}
	
	//fonction de restauration d'un parcours mis a la corbeille
	function restorefromtrash_course($course_id, $course_category){
		global $DB, $PAGE;
		$data_archive = new stdClass();
		$data_archive->id = $course_id;
		$categoryid = $course_category;
	
		$a_courseid[] = $course_id;
		move_courses($a_courseid, $categoryid);
		// visibility ok
		$data_archive->visible = 1;
		//updating
		$DB->update_record('course', $data_archive);
		
		// Trigger a course updated event.
		$event = \block_course_management\event\course_untrashed::create(array(
		    'objectid' => $course_id,
		    'context' => context_course::instance($course_id),
		    'other' => array()
		));
		$event->trigger();
		
		$DB->delete_records('course_trash_category', array('course_id'=>$course_id));
		//redirect
		$nexturl = new moodle_url("/course/view.php?", array('id'=>$course_id));
		redirect($nexturl);
	}

	// execution du backup
	// $courseid	=> identifiant du cours à Backuper
	// $userid	=> identifiant de l'utilisateur courant
	// $action_type	=> le type d'action (cas de la création de session)
	function course_backup ($courseid, $userid, $action_type) {
				if($action_type=='createsessionfromparcours' || $action_type == 'createparcoursfromsession'){
					$backupsettings = array (
						'users' => 0,               // Include enrolled users (default = 1)
						'anonymize' => 0,           // Anonymize user information (default = 0)
						'role_assignments' => 0,    // Include user role assignments (default = 1)
						'activities' => 1,          // Include activities (default = 1)
						'blocks' => 1,              // Include blocks (default = 1)
						'filters' => 1,             // Include filters (default = 1)
						'comments' => 0,            // Include comments (default = 1)
						'userscompletion' => 0,     // Include user completion details (default = 1)
						'logs' => 0,                // Include course logs (default = 0)
						'grade_histories' => 0      // Include grade history (default = 0)
					);
				}else{
					$backupsettings = array (
						'users' => 1, 
						'anonymize' => 0,  
						'role_assignments' => 1,
						'activities' => 1,          
						'blocks' => 1,              
						'filters' => 1,             
						'comments' => 1,    
						'userscompletion' => 1,
						'logs' => 1,                
						'grade_histories' => 1 
					);
				}				

				$backup_mode = backup::MODE_GENERAL;
				
				$bc = new backup_controller(backup::TYPE_1COURSE, $courseid, backup::FORMAT_MOODLE,
						backup::INTERACTIVE_NO, $backup_mode, $userid);

				foreach ($bc->get_plan()->get_tasks() as $taskindex => $task) {
					$settings = $task->get_settings();
					foreach ($settings as $settingindex => $setting) {
						$setting->set_status(backup_setting::NOT_LOCKED);

						// Modify the values of the intial backup settings
						if ($taskindex == 0) {
							foreach ($backupsettings as $key => $value) {
								if ($setting->get_name() == $key) {
									$setting->set_value($value);
								}
							}
						}
					}
				}
				$backupid = $bc->get_backupid();

				$bc->execute_plan();

				$bc->destroy();
				return $backupid;
	}
	
	
//fonction de restauration du cours
	function course_restore($backupid, $ci, $ui, $action_type){
		global $DB;		
		
		$transaction = null;
		
		if($action_type=='createsessionfromparcours'
				|| $action_type == 'createparcoursfromsession'
				|| $action_type == 'creategabaritfromparcours'
				|| $action_type == 'createparcoursfromgabarit'){
			$backupsettings = array (
				'users' => 0,               // Include enrolled users (default = 1)
				'anonymize' => 0,           // Anonymize user information (default = 0)
				'role_assignments' => 0,    // Include user role assignments (default = 1)
				'activities' => 1,          // Include activities (default = 1)
				'blocks' => 1,              // Include blocks (default = 1)
				'filters' => 1,             // Include filters (default = 1)
				'comments' => 0,            // Include comments (default = 1)
				'userscompletion' => 0,     // Include user completion details (default = 1)
				'logs' => 0,                // Include course logs (default = 0)
				'grade_histories' => 0      // Include grade history (default = 0)
			);
		}else{
			$backupsettings = array (
				'users' => 1,
				'anonymize' => 0,
				'role_assignments' => 1,
				'activities' => 1,
				'blocks' => 1,
				'filters' => 1,
				'comments' => 1,
				'userscompletion' => 1,
				'logs' => 1,
				'grade_histories' => 1
			);
		}
				
		if($action_type=='createsessionfromparcours'){
			$backup_mode = backup::MODE_IMPORT;
		}else{
			$backup_mode = backup::MODE_GENERAL;
		}
		
		try {
			$transaction = $DB->start_delegated_transaction();
			$controller = new restore_controller($backupid, $ci,
				backup::INTERACTIVE_NO, $backup_mode, $ui,
				backup::TARGET_NEW_COURSE);
			
			
			foreach ($controller->get_plan()->get_tasks() as $taskindex => $task) {
				$settings = $task->get_settings();
				foreach ($settings as $settingindex => $setting) {
					$setting->set_status(backup_setting::NOT_LOCKED);

					// Modify the values of the intial backup settings
					if ($taskindex == 0) {
						foreach ($backupsettings as $key => $value) {
							if ($setting->get_name() == $key) {
								$setting->set_value($value);
							}
						}
					}
				}
			}
			
			$controller->execute_precheck();
			$controller->execute_plan();
			$transaction->allow_commit();
		}
		catch (Exception $e)
		{
			$transaction->dispose();
		}
	}
	
//fonction de création de cours
	function course_create ($coursename, $shortname, $coursecategory){
		$courseid = restore_dbops::create_new_course($coursename, $shortname, $coursecategory);
		return $courseid;
	}	
	
	function clear_folder($dossier){
		if(($dir=opendir($dossier))===false){return;}else{

		while($name=readdir($dir)){
			if($name==='.' or $name==='..')
				continue;
			$full_name=$dossier.'/'.$name;

			if(is_dir($full_name))
				clear_folder($full_name);
			// else unlink($full_name);
			}

		closedir($dir);

		@rmdir($dossier);
		}
	}
	
	//fonction permettant l'ajout d'un $utilisateur et un de ses $rôles à un $cours (nouveau cours)
	function enrol_course_creator($course_id, $userid, $role_id){
		global $DB;
		 $instances = $DB->get_records('enrol', array('courseid'=>$course_id, 'enrol'=>'manual'), '', '*');
		 foreach($instances as $instance)
		 {
			$user_enrolement = enrol_get_plugin('manual');
			$user_enrolement->enrol_user($instance, $userid,  $role_id, 0,  0, NULL);
		 }
	}

	//fonction de modification de la date de début de session des classes virtuelles pour les parcours duppliqués
	function get_via_activities_in_course($courseid){
	    global $DB;
	    $via_activities = $DB->get_records('via', array('course' => $courseid));
	    if(!$via_activities){
	        return false;
        }

        foreach($via_activities as $via_activity){
            $update_via = new stdClass();
            $update_via->id = $via_activity->id;
            $update_via->datebegin = 0;
            $update_via->viaactivityid = null;
            $update_via->activitytype= 3;

            try{
                $DB->update_record('via', $update_via);
            } catch(Exception $e) {
                echo $e->getMessage();
            }
        }
        return true;
    }
