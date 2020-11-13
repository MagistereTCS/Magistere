<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Page for creating or editing customed views on courses.
 */

require_once('../../config.php');
require_once('editcustomview_form.php');
require_login();

$user = $USER->id;
$userAca = $CFG->academie_name;
$id = optional_param('id', 0, PARAM_INT);
$url = new moodle_url('/local/metaadmin/editcustomview.php');
$context = context_system::instance();

if($id !== 0){
    global $CFG,$USER;

    $sql = 'SELECT mv.*
FROM metaadmin_customview mv
WHERE mv.id = :id
AND mv.user_id = :userid
AND user_academy = :academy_name';

    $checkview = get_centralized_db_connection()->get_record_sql($sql,array("id" => $id, "userid" => $USER->id, "academy_name" => $CFG->academie_name));
    if ($checkview === false) {
        print_error('Wrong custom view ID.');
    }
}

if ($id != 0) {
    $strtitle = new lang_string("modcustomview");
    $title = get_string('modcustomview');
    $fullname = $title;
} else {
    $strtitle = new lang_string("addnewview");
    $title = get_string('addnewview');
    $fullname = $title;
	$nbviews = get_centralized_db_connection()->get_record_sql("SELECT COUNT(*) AS nb FROM metaadmin_customview WHERE user_id = :userid AND user_academy = :academy_name ",array("userid" => $USER->id, "academy_name" => $CFG->academie_name));
	if ($nbviews->nb > 4) {
		print_error(get_string('error_nbviews', 'local_metaadmin'));
	}
}

$PAGE->requires->js(new moodle_url('/local/metaadmin/js/customviews.js'));
$PAGE->set_url($url);
$PAGE->set_pagelayout('admin');
$PAGE->set_context($context);
$PAGE->set_title($title);
$PAGE->set_heading($fullname);

$mform = new editcustomview_form(null, array(
    'id' => $id,
    'context' => $context
));

$cancelurl = new moodle_url('/my/');
if ($mform->is_cancelled()) {
    redirect($cancelurl);
}

if (($sentData = $mform->get_data()) && isset($sentData->scourses)) {
    $data = new stdClass();
    $dataCourses = array();
    $data->user_id = $user;
    $data->user_academy = $userAca;
    $data->view_name = $sentData->view_name;
    $data->display_type = $sentData->display_type;
    $data->trainee_calc = $sentData->trainee_calc;
    $data->send_report = $sentData->send_report;
    if ($data->send_report) {
        $data->frequency_report = $sentData->frequency_report;
        $emails = str_replace(" ", "", $sentData->emails);
        $data->emails = $emails;
        if ($sentData->frequency_report == "monthly") {
            //Only index is returned, so to get the real day value, we have to +1
            $data->day_report = $sentData->numday_report++;
        } else {
            $data->day_report = $sentData->nameday_report;
        }
    }
	
    foreach ($sentData->scourses as $scourse) {
        $course = new stdClass();
        //$course->course_hub_id = $scourse;
        //$course->course_academy = get_hub_course_academy($scourse);
        //$course->course_id = get_hub_course_realid($scourse);
		$iduniq=explode('*+%',$scourse);
		$course->index_year = $iduniq[0];
		$course->index_origine_gaia_id = $iduniq[1];
		$course->index_title = $iduniq[2];
        $dataCourses[] = $course;
    }
    if ($sentData->id == 0) {
        $data->id = create_customview($data, $dataCourses);
        $manageurl = new moodle_url('/local/metaadmin/customview_statsparticipants.php?id='.$data->id);
    } elseif ($sentData->id > 0 ) {
        $data->id = $sentData->id;
        update_customview($data, $dataCourses);
        $manageurl = new moodle_url('/local/metaadmin/customview_statsparticipants.php?id='.$sentData->id);
    }
    redirect($manageurl);
}

echo $OUTPUT->header();
echo $OUTPUT->heading($strtitle);
$mform->display();
echo $OUTPUT->footer();
