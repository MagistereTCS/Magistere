<?php
require_once('../../config.php');
require_once($CFG->dirroot.'/blocks/progress/lib.php');
require_once($CFG->libdir.'/tablelib.php');

global $DB, $PAGE, $OUTPUT;
$PAGE->set_context(null);

$user_list = [];
foreach ($_POST['stagiaire'] as $username) {
	$user = $DB->get_record('user',['username'=> $username]);

	if ($user !== false) {
		$user_list[] = $user;
	}
}

$formation_hours = optional_param('formation_hours', null, PARAM_RAW);

$user_courses = [];
// get id of category 'session' and ids of sub-categories os 'session'
$id_list = '('.session_id_tree().')';

// on recupere la liste des cours de chaque utilisateur dans un tableau (cle:user_id=>tableau des cours)
foreach ($user_list as $current_user) {
	$user_enrolments = $DB->get_records('user_enrolments', ['userid' => $current_user->id]);
	foreach ($user_enrolments as $current_enrolment) {
		$enrolment = $DB->get_record('enrol', ['id' => $current_enrolment->enrolid]);
		// $course = $DB->get_record('course', array('id' => $enrolment->courseid));
		$courses = $DB->get_records_sql('
          SELECT * FROM {course} 
          WHERE `id` = '.$enrolment->courseid.' 
          AND `category` IN '.$id_list
        );
		
		foreach ($courses as $course) {
			if ($course->startdate != '' && $course->startdate != 0) {
				if (isset($_POST['date_debut']) && $_POST['date_debut'] != '') {
					list($day, $month, $year) = explode('/', $_POST['date_debut']);
					$timestamp = mktime(0, 0, 0, $month, $day, $year);
					if ((int) $timestamp > (int) $course->startdate) {
                        continue;
                    }
				}
				if (isset($_POST['date_fin']) && $_POST['date_fin'] != '') {
					list($day, $month, $year) = explode('/', $_POST['date_fin']);
					$timestamp = mktime(0, 0, 0, $month, $day, $year);
					if ((int) $timestamp < (int) $course->startdate) {
                        continue;
                    }
				}
			}
			$user_courses[$current_user->id][] = $enrolment->courseid;
		}
	}
}

$final_result_array = [];
$course_list_array = [];
// pour chaque etudiant
foreach($user_courses as $user_id => $courses) {

	// pour chaques cours de l'étudiant
	foreach ($courses as $course_id) {
		// on recupere la config du bloc progress du cours
		$context  = context_course::instance($course_id);
		$block = $DB->get_record('block_instances', [
		    'parentcontextid' => $context->id,
            'blockname'=>'progress'
        ]);
		if (!$block) {continue;}
		$config = unserialize(base64_decode($block->configdata));

		// on calcule le pourcentage d'avancement
		$modules = modules_in_use_by_course_id($course_id);
		if(empty($modules)) {continue;}
		$events = custom_event_information($config, $modules, $course_id);
		if(is_null($events)) {continue;}
		$attempts = custom_get_attempts($modules, $config, $events, $user_id, $course_id);
		$progressvalue = get_progess_percentage($events, $attempts);

		$final_result_array[$user_id][$course_id] = $progressvalue;
		$course_list_array[$course_id] = $course_id;
	}
}

// on construit le csv ou le excel
include('build_tableur.php');

if($_POST['sortie'] == 'sortie_csv') {
    build_csv($final_result_array, $course_list_array, $formation_hours);
} else if($_POST['sortie'] == 'sortie_xls') {
    build_excel ($final_result_array, $course_list_array, $formation_hours);
} else if($_POST['sortie'] == 'sortie_html') {
    build_html_table($final_result_array, $course_list_array, $formation_hours);
}

function session_id_tree ($m_cat_id=null) {
	global $DB;
	if ($m_cat_id==null) {
		$category_session = $DB->get_record('course_categories', ['name' => 'Session de formation']);
		$id_list = $category_session->id; 
		$id_list .= session_id_tree ($category_session->id);
		return $id_list;
	} else {
		$sub_category_session = $DB->get_records('course_categories', ['parent' => $m_cat_id]);
		$id_list = '';
		
		if (!empty($sub_category_session)) {
			foreach ($sub_category_session as $category_data) {
				$id_list .= ','.$category_data->id;
				$id_list .= session_id_tree ($category_data->id);
			}
		}
		return $id_list;
	}
}
