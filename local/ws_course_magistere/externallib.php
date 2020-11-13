<?php
require_once($CFG->libdir . "/externallib.php");

class local_ws_course_magistere_external extends external_api {

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function course_magistere_parameters() {
        return new external_function_parameters(
                array('functionname' => new external_value(PARAM_TEXT, '0,"', VALUE_DEFAULT, '0, '),
					'firstparam' => new external_value(PARAM_INT, '0"', VALUE_DEFAULT, 0),
					'textparam' => new external_value(PARAM_TEXT, '0"', VALUE_DEFAULT, '0'),
				)
        );
    }
    
    public static function get_count_participant_parameters() {
    	return new external_function_parameters(
    			array('courseid' => new external_value(PARAM_INT),
    					'enrol' => new external_value(PARAM_TEXT)
    			)
    	);
    }
    
    public static function get_count_participant_returns(){
    		return new external_value(PARAM_TEXT, "fsdlkfhds,");
    } 
    
    public static function get_count_participant($courseid, $enrol) {
    	global $DB;
    	
    	$enrolRecord = $DB->get_record('enrol', array('courseid' => $courseid, 'enrol' => $enrol));
    	
    	if($enrolRecord){
    		return json_encode(
    				array('user_enrol' => $DB->count_records('user_enrolments', array('enrolid' => $enrolRecord->id)))
    		);
    	}
    	
    	return json_encode(array('user_enrol' => 0));
    }

    /**
     * Returns welcome message
     * @return string welcome message
     */
    public static function course_magistere($functionname = 'get_course_info', $firstparam = 0, $textparam = '0') {
        global $USER, $DB;	
        //Parameter validation
        //REQUIRED
        $params = self::validate_parameters(self::course_magistere_parameters(),
                array('functionname' => $functionname, 'firstparam' => $firstparam, 'textparam' => $textparam));

        //Context validation
        //OPTIONAL but in most web service it should present
        $context = context_user::instance($USER->id);
        self::validate_context($context);

        //Capability checking
        //OPTIONAL but in most web service it should present
        if (!has_capability('moodle/user:viewdetails', $context)) {
            throw new moodle_exception('cannotviewprofile');
        }

		if($functionname == 'get_course_info')
		{
			return self::get_course_info($params['firstparam']);
		}
		elseif($functionname == 'get_enrolments')
		{
			return self::get_enrolments($params['textparam']);
		}

        return $params['functionname'];
    }


	public static function get_course_info($course_id)
	{
		GLOBAL $DB;
		$result = array();

		$indexation_query = "SELECT * FROM {indexation_moodle} where course_id = ?";
		$indexation = $DB->get_record_sql($indexation_query, array($course_id));
		if(!$indexation)
		{
			$result['indexation'] = false;
		}
		else
		{
			$result['indexation'] = $indexation;
		}
		$course_query = "SELECT * FROM {course} where id = ?";
		$course = $DB->get_record_sql($course_query, array($course_id));
		if(!$course)
		{
			$result['course'] = false;
		}
		else
		{
			$result['course'] = $course;
		}

		return json_encode($result);
	}
	
	public static function get_enrolments($param)
	{
		GLOBAL $DB, $CFG;
		$param = explode('*', $param);
		$email = $param[0];
		$course_ids = strtr( $param[1] , '_' , ',' );
		// récupération du user => (id)
		$user = $DB->get_record('user', array('email' => $email));
			if(!$user) return;
		//récupération des roles à l'inscription => (role, contextid)
		$role_assignment = $DB->get_records('role_assignments', array('userid' => $user->id));
			if(!$role_assignment) return;		
		
		foreach($role_assignment as $ra){			
			// récupération du context pour chaque rôle du user => (instanceid = courseid)
			$select = " contextlevel = 50 AND id = ".$ra->contextid." AND instanceid IN (".$course_ids.")";
			$context = $DB->get_record_select('context', $select);
				if(!$context) continue;
			// Récupération du nom du Rôle
			$role = $DB->get_record('role', array('id' => $ra->roleid));
				if(!$role) continue;			
			// récupération du cours => (fullname)
			$course = $DB->get_record('course', array('id' => $context->instanceid, 'visible' => 1));
				if(!$course) continue;			
			$collection = $DB->get_record('indexation_moodle', array('course_id' => $course->id));
			//récuperation de la categorie
			$cat_cx_id = explode ( '/' , $context->path);	
			$course_cat = $DB->get_record_sql('SELECT cc.name
																		FROM {course_categories} as cc
																		JOIN {context} as cx ON cx.instanceid = cc.id
																		WHERE cx.contextlevel = 40
																		AND cx.id = ? ', array($cat_cx_id[2]));															
			//construction de la réponse															
			$course_info = array('fullname' => $course->fullname,
			'startdate' => $course->startdate,
			'url' => $CFG->wwwroot.'/course/view.php?id='.$course->id, 
			'collection' => $collection->collection,
			'category' => $course_cat->name);
			$result[$role->name][$course->id] = $course_info;			
		}
		return (string) json_encode($result );	
	}
	
	// public static function get_label_name($collection)
	// {
		// if($collection->collection=='découverte'){
			// $logo='decouverte';
			// $label='Découverte';		      				
		// }elseif ($collection->collection=='étude de cas') {
			// $logo='analyse';
			// $label='Etude de cas';
		// }elseif ($collection->collection=='action') {
			// $logo=$collection->collection;
			// $label='Action';
		// }elseif ($collection->collection=='réseau') {
			// $logo='reseau';
			// $label='Réseau';
		// }elseif ($collection->collection=='culture') {
			// $logo=$collection->collection;
			// $label='Culture';
		// } 
		// else{
			// $label= '';
			// $logo='empty';
		// }
		// return array('label' => $label, 'logo' => $logo);
	// }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function course_magistere_returns() {
        return new external_value(PARAM_TEXT, 'la réponse, en json');
    }



}
