<?php
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once('lib/GaiaForm.php');

require_once($CFG->dirroot . '/course/lib.php');

$id = required_param('id', PARAM_INT); // course id
$type = required_param('type', PARAM_ALPHA); // type

$course = $DB->get_record('course', array('id'=>$id), '*', MUST_EXIST);
$context = context_course::instance($course->id);

require_course_login($course, true);
$PAGE->set_context($context);
$PAGE->set_pagelayout('workflow');
$PAGE->set_pagetype('course-view-' . $course->format);
$PAGE->set_url('/local/gaia/index.php', array('id' => $id));
$PAGE->set_course($course);


$PAGE->set_title("$course->shortname : ". get_string('pluginname', 'local_workflow'));
$PAGE->set_heading($course->fullname);

if ($PAGE->user_allowed_editing()) {

    $PAGE->requires->jquery();
    $PAGE->requires->jquery_plugin('ui');
    $PAGE->requires->jquery_plugin('ui-css');
    $PAGE->requires->js_call_amd("local_gaia/gaiaForm", "init", array($course->id, $CFG->wwwroot.'/local/gaia/api.php'));
    $buttons = $OUTPUT->edit_button($PAGE->url, true);
    $PAGE->set_button($buttons);

    $form = new GaiaForm($course->id, $type);

    if($form->isSubmittedForm()){
        $form->processForm();
    }

    echo $OUTPUT->header();

    echo html_writer::start_div('gaia');
    echo html_writer::tag('h2', get_string('pluginname', 'local_gaia'));

    echo $form->getForm();

    echo html_writer::end_div();

    echo $OUTPUT->footer();
} else {
    $notification = get_string('error_access_denied', 'local_gaia');
    redirect(new moodle_url($CFG->wwwroot . '/course/view.php', array('id' => $course->id)), $notification, null, \core\output\notification::NOTIFY_ERROR);
}
