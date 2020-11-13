<?php

//  BRIGHTALLY CUSTOM CODE
//  Coder: Ted vd Brink
//  Contact: ted.vandenbrink@brightalley.nl
//  Date: 6 juni 2012
//
//  Description: Enrols users into a course by allowing a user to upload an csv file with only email adresses
//  Using this block allows you to use CSV files with only emailaddress
//  After running the upload you can download a txt file that contains a log of the enrolled and failed users.

//  License: GNU General Public License http://www.gnu.org/copyleft/gpl.html

require('../../config.php');

require_once("$CFG->dirroot/blocks/csv_enrol/edit_form.php");
require_once("$CFG->dirroot/repository/lib.php");
require_once("$CFG->dirroot/user/lib.php");
require_once("$CFG->dirroot/blocks/csv_enrol/locallib.php");

global $USER;
require_login();

$courseid = optional_param('id', '', PARAM_INT);
$iid = optional_param('iid', '', PARAM_INT);
$returnurl = new moodle_url($CFG->wwwroot.'/course/view.php', array('id' => $courseid));

$context = context_course::instance($courseid);

if (!has_capability('mod/csv_enrol:uploadcsv',$context,$USER->id)) {
    die("Unauthorized.");
}

$title = get_string('csvenrol','block_csv_enrol');
$struser = get_string('user');
$course = $DB->get_record('course', array('id' => $courseid)); //used for coursename

$PAGE->set_url('/blocks/csv_enrol/edit.php',array('id' => $courseid));
$PAGE->set_context($context);
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->set_pagelayout('standard');
$PAGE->navigation->extend_for_user($USER);
//$PAGE->navbar->add($title);

$ajax_url = $CFG->wwwroot.'/blocks/csv_enrol/js/ajax.php';
$PAGE->requires->js_call_amd('block_csv_enrol/dropFilePicker', 'init', array($courseid, $ajax_url));

$contextname = $context->get_context_name();

$parentcourse = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$PAGE->set_course($parentcourse);

$data = new stdClass();
$options = array('subdirs' => 1, 'maxbytes' => $CFG->userquota, 'maxfiles' => - 1, 'accepted_types' => array('*.csv','*.txt'), 'return_types' => FILE_INTERNAL);

file_prepare_standard_filemanager($data, 'files', $options, $context, 'user', 'csvenrol', 0);

$data->coursename = $course->fullname;
$data->courseid = $courseid;
$data->content_csv = "";
$data->delete_users = 0;
$data->type = "";
$data->role = "";
$data->groups = "";

$mform = new block_csv_enrol_form(null, array('data' => $data, 'options' => $options));
$formdata = $mform->get_data();

$mform_prev = new block_csv_enrol_form_previsual(null, array('data' => $data, 'options' => $options, 'formdata' => $formdata));
$formdata_prev = $mform_prev->get_data();

//3 options: file uploaded, cancelled, or saved
if ($mform->is_cancelled()) {
   redirect($returnurl);  
} else if ($formdata) {
	//$content_csv = $mform->get_file_content('userfile');
	$draftid = file_get_submitted_draft_itemid('userfile');
	$content_csv = file_get_contents_utf8($draftid);
	$data->content_csv = $content_csv;
	$data->type = $formdata->type;
	$data->role = $formdata->role;
	if(isset($formdata->groups)){
		$data->groups = implode(",", $formdata->groups); // Les données ne peuvent t'être qu'en int ou string d'où la conversion de l'array en string ... 
	} 
	if(isset($formdata->delete_users)){
		$data->delete_users = $formdata->delete_users;
	}
	
	$mform_prev = new block_csv_enrol_form_previsual(null, array('data' => $data, 'options' => $options, 'formdata' => $formdata));
	
	echo $OUTPUT->header();
	echo $OUTPUT->heading(get_string('previewcsvfile', 'block_csv_enrol'));
	echo html_writer::tag('div', get_string('previewcsvfile_description', 'block_csv_enrol'), array('id'=>'page-blocks-csv-enrol-preview-description'));
	echo $OUTPUT->box_start('generalbox');
	if(!(isset($formdata->groups)) && $formdata->type == "simple"){
		echo html_writer::tag('div', get_string('previewcsvfile_nogroup', 'block_csv_enrol'), array('id'=>'page-blocks-csv-enrol-preview-nogroup'));
	}
	echo html_writer::tag('div', html_writer::table(create_preview_csv_enrol_table($content_csv, $formdata, $returnurl)), array('class'=>'flexible-wrap'));
	echo $OUTPUT->box_end();
	echo $OUTPUT->box_start('generalbox', null, array('style'=>'text-align:center'));
	$mform_prev->display();
	echo $OUTPUT->box_end();
	echo $OUTPUT->footer();
	die;
}
if($mform_prev && $formdata_prev){

	$content_csv = $formdata_prev->content_csv;
	//3 options: file uploaded, cancelled, or saved
	if ($mform_prev->is_cancelled()) {
		redirect(new moodle_url($CFG->wwwroot.'/course/view.php', array('id' => $courseid)));
	} else if ($mform_prev && $content_csv) {
	
		//upload file, store, and process csv
		$content = $formdata_prev->content_csv; //save uploaded file
		$fs = get_file_storage();
	
		//Cleanup old files:
		//First, create target directory:
		if(!$fs->file_exists($context->id, 'user', 'csvenrol', 0, '/', 'History'))
			$fs->create_directory($context->id, 'user', 'csvenrol', 0, '/History/',$USER->id);
	
		//Second, move all files to created dir
		$areafiles = $fs->get_area_files($context->id, 'user', 'csvenrol',false, "filename", false);
		$filechanges = array("filepath"=>'/History/');
		foreach ($areafiles as $key => $areafile) {
			if($areafile->get_filepath()=="/")
			{
				$fs->create_file_from_storedfile($filechanges, $areafile); //copy file to new location
				$areafile->delete(); //remove old copy
			}
		}

		$filename = "upload_".date("Ymd_His").".csv";
	
		// Prepare file record object
		$fileinfo = array(
				'contextid' => $context->id, // ID of context
				'component' => 'user',     // usually = table name
				'filearea' => 'csvenrol',     // usually = table name
				'itemid' => 0,               // usually = ID of row in table
				'filepath' => '/',           // any path beginning and ending in /
				'filename' => $filename,// any filename
				'userid' => $USER->id );
	
		// Create file containing uploaded file content
		$newfile = $fs->create_file_from_string($fileinfo, $content);
	
		// Read CSV and get results
		$log = block_csv_enrol_enrol_users($courseid,$content,$formdata_prev);
	
		//save log file, reuse fileinfo from csv file
		$fileinfo['filename'] = "upload_".date("Ymd_His")."_log.txt";
		$newfile = $fs->create_file_from_string($fileinfo, $log);
	
		// Back to main page
		redirect(new moodle_url($CFG->wwwroot.('/course/view.php'),
		array('id' => $courseid)));
	
	} else if ($formdata_prev &&  !$mform_prev->get_file_content('userfile')) {
	
		// Just show the updated filemanager
		$formdata_prev = file_postupdate_standard_filemanager($formdata_prev, 'files', $options, $context, 'user', 'csvenrol', 0);
	
	}
}

echo $OUTPUT->header();
echo $OUTPUT->heading_with_help(get_string('uploadusersin', 'block_csv_enrol', $contextname), 'uploadusers', 'block_csv_enrol');
echo $OUTPUT->box_start('generalbox');
echo html_writer::tag('div', html_writer::div(download_template_files_table_csv_enrol()), array('id'=>'page-blocks-csv-enrol-template'));
$mform->display();
echo $OUTPUT->box_end();
echo $OUTPUT->footer();