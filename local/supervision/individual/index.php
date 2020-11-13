<?php
require_once('../../../config.php');
require_once($CFG->dirroot.'/blocks/progress/lib.php');
require_once('progress_bar.php');
$PAGE->set_title('Suivi de mes formations');																	//TODO => modif page title
$PAGE->set_heading('Suivi de mes formations');
$PAGE->set_pagelayout('standard');

$PAGE->set_context(context_system::instance());
//TODO => modif page heading
require_login();             								  //make sure user is logged in

echo $OUTPUT->header();


$PAGE->set_url('/local/supervision/individual/index.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');


$ajax_url = $CFG->wwwroot.'/local/supervision/ajax.php';



//on recupere la liste des cours
$enrolments = $DB->get_records('user_enrolments', array('userid' => $USER->id));
$courses = array();
foreach($enrolments as $current_enrolment)
{
	$enrol = $DB->get_record('enrol', array('id' => $current_enrolment->enrolid));
	$courses[] = $enrol->courseid;
}
$courses = $DB->get_records_list('course', 'id', $courses);


//gestion du javascript
$jsmodule = array(
    'name' => 'block_progress',
    'fullpath' => '/blocks/progress/module.js',
    'requires' => array(),
    'strings' => array(
        array('time_expected', 'block_progress'),
    ),
);


?>
<style>
	.progressBarProgressTable {
    width: 100%;
    margin: 0 0 2px 0;
	height: 17px;
}

  .progressBarCell {
    border: 1px solid #000000 !important;
    height: 15px;
    margin: 0;
    padding: 0;
    text-align: center;
    vertical-align: middle;
    background-color: none;
}

  .progressBarCell img {
    width: 100%;
    height: 15px;
    margin: 1px 0 0 0;
    padding: 0;
}

  .progressBarCell .smallicon {
    width: 85%;
    max-width: 15px;
    height: auto;
    max-height: 15px;
    vertical-align: baseline;
}

  .progressEventInfo {
    font-size: x-small;
    text-align: left;
    white-space: pre;
    overflow: hidden;
}

  .progressEventInfo img {
    vertical-align: middle;
}

  .moduleIcon {
    float: left;
    margin-right: 5px;
}

  .progressBarHeader {
    font-size: 90%;
    margin: 0;
    padding: 0;
}

.progressConfigBox {
    border:1px dashed #cccccc;
    padding: 5px;
    margin:5px 0 !important;
}

  .groupselector {
    text-align: center;
}

.progressoverviewmenus {
    text-align: center;
    margin-bottom: 5px;
}

.progressoverviewmenus form, .progressoverviewmenus select, .progressoverviewmenus div {
    display: inline;
}

.progressoverviewmenus select {
    margin-left: 2px;
}

.progress_bar_container{
	width: 400px;
}

.titre_course {
	font-size: 18px !important;
	background-color:#978579 !important;
	color:#fff !important;
	padding:5px 5px 5px 10px;
	width:575px;

}

.no_progress_bar_message{
	color:brown;
}
</style>

<h1>Suivi de mes formations</h1>

<?php

foreach($courses as $course)
{
	echo '<h2 class="titre_course">'.$course->fullname.'</h2>';

	$course_data = generate_progress_bar($course->id);
	if($course_data){
		$completion = 'Formation complétée à '.$course_data['progression'];
		echo '<TABLE><tr><td><div class="progress_bar_container">'.$course_data['progress_bar'].'</div></td><td>'.$completion.'</td></tr></TABLE>';
		$modules = modules_in_use_by_course_id($course->id);
		$arguments = array($CFG->wwwroot, array_keys($modules));
		$PAGE->requires->js_init_call('M.block_progress.init', $arguments, false, $jsmodule);
	}
	else{
		echo '<span class="no_progress_bar_message">Cette formation ne prend pas en charge la barre de progression.</span>';
	}
}




?>
<script>
	$(function(){
		$('.progress_bar_container').mouseout(function(){
			$('.progressEventInfo').empty();
		})
	});
</script>

<?php


echo $OUTPUT->footer();

?>