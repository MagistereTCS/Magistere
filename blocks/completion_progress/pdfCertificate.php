<?php

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/blocks/completion_progress/lib.php');
require_once($CFG->dirroot . '/blocks/completion_progress/ParticipantsList.php');

error_reporting(0);
ini_set("display_errors", 0);

$PAGE->set_context(context_system::instance());
$PAGE->set_title('Description parcours');
$PAGE->set_heading('Description parcours');
$PAGE->set_pagelayout('standard');
$PAGE->set_url('/blocks/progress/pdfCertificate.php');


$courseid = required_param('courseid', PARAM_INT);

// Determine course and context
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
//$context = context_course::instance($courseid);

require_login($course, false);

$end_date = required_param('end_date', PARAM_TEXT);
$duration_h = required_param('duration_h', PARAM_INT);
$duration_m = required_param('duration_m', PARAM_INT);
$comment = required_param('comment', PARAM_TEXT);
$sgaias = required_param('sgaia', PARAM_RAW);
$sgaias = json_decode($sgaias);
$sother = required_param('sother', PARAM_BOOL);

$id = required_param('id', PARAM_INT);
$contextid = required_param('contextid', PARAM_INT);
$role = required_param('role', PARAM_INT);
$group = required_param('group', PARAM_INT);
$name = required_param('name', PARAM_TEXT);
$realized = required_param('realized', PARAM_BOOL);
$activity = required_param('activity', PARAM_TEXT);
$neverconnected = required_param('neverconnected', PARAM_BOOL);

$course = $DB->get_record('course', array('id'=>$courseid), '*', MUST_EXIST);

$end_data_ex=explode('/', $end_date);
$end_datemk = mktime(0,0,0,$end_data_ex[1],$end_data_ex[0],$end_data_ex[2]);

$role_tuteur = $DB->get_record('role', array('shortname' => 'tuteur'));
$role_formateur = $DB->get_record('role', array('shortname' => 'formateur'));
$role_participant = $DB->get_record('role', array('shortname' => 'participant'));
$participantList = new ParticipantList();
$participantList->setId($id);
$participantList->setContextId($contextid);
$participantList->setCourseid($courseid);
$participantList->setDataFor(ParticipantList::DATA_FOR_PDF);
$participantList->setSortOrder('u.lastname, u.firstname');

// group
if ($group > 0){
    $participantList->setGroupId($group);
}

// name
$name = str_replace("\\",'',str_replace("'",'',str_replace('%','',trim($name))));
if ($name != '')
{
    $words = explode(' ', $name);

    foreach($words as $word){
        $participantList->addName($word);
    }
}

// activities
if ($activity != 'none' && strlen($activity) > 2)
{
    if($realized == 1){
        $participantList->setIsRealized();
    }

    $activity_ex = explode('*',$activity);
    $activityname = $activity_ex[0];
    $activityid = $activity_ex[1];

    $participantList->setActivity($activityname, $activityid);
}

// neverconnected
if ($neverconnected)
{
    $participantList->setIsNeverConnected();
}

$sgaia_to_render = array();
$tuteurs = array();
$formateurs = array();
$sother_to_render = array();
$data_to_render = array();

$pdfname = $course->shortname;

if(count($sgaias) > 0){
    /*
     * Session gaia part
     */
    $pdfname = '';
    foreach($sgaias as $sgaia){
        $d = explode('-', $sgaia->value);

        $s = array();
        $s['is_session_gaia'] = true;
        $s['sid'] = $d[0]; // session gaia id
        $s['did'] = $d[1]; // dispositif id
        $s['mid'] = $d[2]; // module id

        $infos = GaiaUtils::get_session_info($courseid, $s['sid'], $s['did'], $s['mid']);
        $s['dispositif_name'] = $infos->dispositif_name;
        $s['module_name'] = $infos->module_name;
        $s['sstartdate'] = $infos->startdate;
        $s['senddate'] = $infos->enddate;

        $participantList->setGaiaSession($s['sid'], $s['did'], $s['mid']);
        $participantList->setRoleId($role_participant->id);

        $s['participants'] = $participantList->getData();

        $participantList->setRoleId($role_formateur->id);
        $formateurs += $participantList->getData();

        $participantList->setRoleId($role_tuteur->id);
        $tuteurs += $participantList->getData();

        $sgaia_to_render[] = $s;

        $pdfname .= $s['did'].'_'.$s['mid'].'_';
    }

    $pdfname = substr($pdfname, 0, -1);

    $formateurs += $participantList->getOtherFormateurs();

    $tuteurs += $participantList->getOtherTuteurs();
}

if($sother) {
    /*
     * Session gaia other part
     */

    $participantList->resetGaiaSession();
    $participantList->setOtherGaiaSession();
    $participantList->setRoleId($role_participant->id);
    $sother_to_render = $participantList->getData(); // GaiaUtils::get_other_progression($courseid);

    $participantList->setRoleId($role_formateur->id);
    $formateurs += $participantList->getData(); //GaiaUtils::get_all_other_formateurs($courseid);

    $participantList->setRoleId($role_tuteur->id);
    $tuteurs += $participantList->getData();
}else if(count($sgaias) == 0){
    $participantList->setRoleId($role_participant->id);
    $data_to_render = $participantList->getData(); // $DB->get_records_sql($participant_sql);

    $participantList->setRoleId($role_formateur->id);
    $formateurs = $participantList->getData(); // $DB->get_records_sql($formateur_sql);

    $participantList->setRoleId($role_tuteur->id);
    $tuteurs += $participantList->getData();
}

$data_to_serialized = array(
    'sgaia_to_render' => $sgaia_to_render,
    'formateurs' => $formateurs,
    'tuteurs' => $tuteurs,
    'sother_to_render' => $sother_to_render,
    'data_to_render' => $data_to_render
);

$tmp_data = $CFG->tempdir.'/data_'.$courseid.'_'.time();

file_put_contents($tmp_data, serialize($data_to_serialized));

$data = array(
    'end_date'=>$end_datemk,
    'duration_h'=>$duration_h,
    'duration_m'=>$duration_m,
    'comment'=>$comment,
    'id' => $id ,
    'data_file_path' => $tmp_data
);

$data = base64_encode(serialize($data));

$tmp_pdf = $CFG->tempdir.'/test_'.$courseid.'_'.time().'.pdf';

$cmd = $CFG->wkhtmltopdf_path.' --image-dpi 600 --footer-right "Page [page] sur [toPage]" "'.$CFG->wwwroot.'/blocks/completion_progress/pdfCertificateSources.php?courseid='.$courseid.'&data='.$data.'" '.$tmp_pdf;

$exec_output = array();
$exec_return = 0;
//exec($cmd,&$exec_output,&$exec_return);
exec($cmd,$exec_output);


//echo '<pre>';
//print_r($exec_output);
//print_r($exec_return);


if ($exec_return == 0)
{
	header("Content-Description: File Transfer");
	header("Content-Type: application/octet-stream");
	header('Content-Disposition: attachment; filename="'.$pdfname.'.pdf"');

	readfile($tmp_pdf);

	unlink($tmp_pdf);
	unlink($tmp_data);
}
else
{
	echo 'Error';
}
