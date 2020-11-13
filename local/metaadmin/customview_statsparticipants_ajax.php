<?php

ini_set("mysql.trace_mode", "0");

require_once('../../config.php');
global $DB, $PAGE, $OUTPUT, $SESSION, $CFG;

error_reporting(E_ALL);
ini_set("display_errors", 1);

require_once($CFG->dirroot.'/local/magisterelib/databaseConnection.php');
require_once($CFG->dirroot.'/local/metaadmin/lib.php');

$action = required_param('action', PARAM_ALPHA);

if($action == "list") {
    $so = required_param('so', PARAM_TEXT);
    $lastconnmin = required_param('lastconnmin', PARAM_INT);
    $lastconnmax = required_param('lastconnmax', PARAM_INT);
    $userrole = required_param('userrole', PARAM_TEXT);
    $parcoursidentifiant_year = required_param('parcoursidentifiant_year', PARAM_INT);
    $gaia_origine = required_param('gaia_origine', PARAM_INT);
    $parcoursidentifiant_name = required_param('parcoursidentifiant_name', PARAM_TEXT);
    $view_id = required_param('view_id', PARAM_INT);

    $select_off = optional_param('select_off', 0, PARAM_INT);
    $select_ofp = optional_param('select_ofp',0, PARAM_INT);
    $select_offlocales = optional_param('select_offlocales', 0, PARAM_INT);


	$view = get_custom_views_by_id($view_id);
	if ($view->display_type == 'bycoursebyaca') {
		$gacademy = required_param('aca', PARAM_TEXT);
	}
	
    $lastconnmin = $lastconnmin/1000;
    $lastconnmax = $lastconnmax/1000;

    $PAGE->set_context(context_system::instance());
    $academie = $CFG->academie_name;

    if (has_capability('local/metaadmin:statsparticipants_viewallacademies', context_system::instance())) {
		$result = array();
		$academies = get_magistere_academy_config();
		$special_aca = array('reseau-canope','dgesco','efe','ih2ef', 'dne-foad');
		if ($view->display_type == 'bycourse') {
			$result = get_view_courses_stats($parcoursidentifiant_year, $gaia_origine, $parcoursidentifiant_name, $userrole, $lastconnmin, $lastconnmax, $view_id,$select_off,$select_offlocales,$select_ofp);
			
		} else if ($view->display_type == 'bycoursebyaca') {
			if ( !array_key_exists($gacademy,$academies) || substr($gacademy,0,3) != 'ac-' || in_array($gacademy, $special_aca) ) {
				die("Academy not found!");
			}
				
			$result = get_aca_courses_stats($gacademy, $parcoursidentifiant_year, $gaia_origine, $parcoursidentifiant_name, $userrole, $lastconnmin, $lastconnmax, $view_id,$select_off,$select_offlocales,$select_ofp);
		} else {
			foreach($academies as $academy => $daca) {
				if (substr($academy,0,3) != 'ac-') {
					continue;
				}
				if (in_array($academy, $special_aca)) {
					continue;
				}
				$r = get_aca_stats($academy, $userrole, $parcoursidentifiant_year, $gaia_origine, $parcoursidentifiant_name, $lastconnmin, $lastconnmax, $view_id,$select_off,$select_offlocales,$select_ofp);
				if ($r !== false) {
					$result[] = $r;
				}
			}
			usort($result,'sort_results');
		}
    }
    else if (has_capability('local/metaadmin:statsparticipants_viewownacademy', context_system::instance())) {
        $result = array();
		$academies = get_magistere_academy_config();
		$special_aca = array('reseau-canope','dgesco','efe','ih2ef', 'dne-foad');
		if ($view->display_type == 'bycourse') {
			$result = get_view_courses_stats($parcoursidentifiant_year, $gaia_origine, $parcoursidentifiant_name, $userrole, $lastconnmin, $lastconnmax, $view_id,$select_off,$select_offlocales,$select_ofp);
		} else if ($view->display_type == 'bycoursebyaca') {
			if ( $view->user_academy != $gacademy ) {
				die("Academy not found!");
			}
			$result = get_aca_courses_stats($gacademy, $parcoursidentifiant_year, $gaia_origine, $parcoursidentifiant_name, $userrole, $lastconnmin, $lastconnmax, $view_id,$select_off,$select_offlocales,$select_ofp);
		} else {
			$r = get_aca_stats($view->user_academy, $userrole, $parcoursidentifiant_year, $gaia_origine, $parcoursidentifiant_name, $lastconnmin, $lastconnmax, $view_id,$select_off,$select_offlocales,$select_ofp);
			if ($r !== false) {
				$result[] = $r;
			}
			usort($result,'sort_results');
		}
    } else {
        $result = array();
    }

    //Return result to jTable
    $jTableResult = array();
    $jTableResult['Result'] = "OK";
    $jTableResult['TotalRecordCount'] = count($result);
    $jTableResult['Records'] = $result;
    print json_encode($jTableResult);
}

function sort_results($a, $b) {
    global $so;
    $orders = explode(' ',$so);
    $order_field = $orders[0];
    $order_asc = ($orders[1]=='DESC'?false:true);

    if ($a->{$order_field} == $b->{$order_field}) {
        return 0;
    }
    if ($order_asc)	{
        return ($a->{$order_field} < $b->{$order_field}? -1:1);
    } else {
        return ($a->{$order_field} > $b->{$order_field}? -1:1);
    }
}
