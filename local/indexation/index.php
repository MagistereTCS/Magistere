<?php

/**
 * indexation local plugin
 *
 * Fichier index qui gère l'affichage du formulaire d'indexation ainsi que ses onglets.
 *
 * @package    local
 * @subpackage indexation
 * @author     TCS
 * @date       Aout 2019
 */

require_once('../../config.php');
require_once($CFG->dirroot.'/local/indexation/form/general_form.php');
require_once($CFG->dirroot.'/local/indexation/form/detail_form.php');
require_once($CFG->dirroot.'/local/indexation/form/organisme_form.php');
require_once($CFG->dirroot.'/local/indexation/form/version_form.php');
require_once($CFG->dirroot.'/local/indexation/lib.php');
require_once($CFG->dirroot.'/local/workflow/lib.php');

require_login();

$courseid = required_param('id', PARAM_INT);

$indexation = $DB->get_record('local_indexation', array('courseid' => $courseid));

$PAGE->requires->jquery();
$PAGE->requires->js(new moodle_url('/local/indexation/js/indexation.js'));
$PAGE->requires->js(new moodle_url('/repository/filepicker.js'));
$PAGE->requires->css(new moodle_url('/local/indexation/style.css'));

$course = $DB->get_record('course', array('id' => $courseid));
$context = context_course::instance($courseid);

$PAGE->set_context($context);
$PAGE->set_pagelayout('indexation');
$PAGE->set_pagetype('course-view-' . $course->format);
$PAGE->set_url('/local/indexation/index.php');


$PAGE->set_course($course);

$site = get_site();

$pagedesc = get_string('indexation', 'local_indexation');
$title = $site->shortname.': '.$pagedesc;
$fullname = $course->fullname;

$PAGE->set_title($title);
$PAGE->set_heading($fullname);

$buttons = $OUTPUT->edit_button($PAGE->url, true);
$PAGE->set_button($buttons);

$workflow = start_workflow($course->id, true);
$notification_badges = $workflow->getIndexationNotificationBadges();
$count_notifications = array_count_values($notification_badges);

$notification_badge_general = '';
$notification_badge_organisme = '';
$notification_badge_detail = '';
$notification_badge_version = '';

if(isset($count_notifications["general"])){
    $notification_badge_general = generate_notification_HTML($count_notifications["general"]);
}
if(isset($count_notifications["organisme"])){
    $notification_badge_organisme = generate_notification_HTML($count_notifications["organisme"]);
}
if(isset($count_notifications["detail"])){
    $notification_badge_detail = generate_notification_HTML($count_notifications["detail"]);
}

if(isset($count_notifications["version"])){
    $notification_badge_version = generate_notification_HTML($count_notifications["version"]);
}

$formData = array(
    'course' => $course,
    'id' => $courseid,
    'coursefullname' => $course->fullname,
    'indexation' => $indexation,
    'coursedescription' => $course->summary,
    'notification_badges' => $notification_badges
);

$generalForm = new general_form(null, $formData);
$organismeForm = new organisme_form(null, $formData);
$detailForm = new detail_form(null, $formData);
$versionForm = new version_form(null, $formData);

if($generalForm->is_cancelled() || $detailForm->is_cancelled() || $detailForm->is_cancelled()){
    redirect(new moodle_url('/course/view.php', array('id' => $courseid)));
}

if(($data = $generalForm->get_data())){
    process_general_form($data, $indexation);
    if(isset($data->upload_thumbnail)){
        redirect(new moodle_url('/local/indexation/add_resource.php', array('id' => $courseid, 'type' => 'thumb')));
    }
    if(isset($data->upload_video)){
        redirect(new moodle_url('/local/indexation/add_resource.php', array('id' => $courseid, 'type' => 'video')));
    }
    redirect(new moodle_url('/local/indexation/index.php#organisme', array('id' => $courseid)));
} else if(($data = $organismeForm->get_data())) {
    process_organisme_form($data, $indexation);
    redirect(new moodle_url('/local/indexation/index.php#detail', array('id' => $courseid)));
} else if(($data = $detailForm->get_data())) {
    process_detail_form($data, $indexation);
    redirect(new moodle_url('/local/indexation/index.php#version', array('id' => $courseid)));
} else if(($data = $versionForm->get_data())){
    process_version_form($data, $indexation);
    redirect(new moodle_url('/local/workflow/index.php', array('id' => $courseid)), get_string('notification_indexation_updated', 'local_workflow'), null, \core\output\notification::NOTIFY_SUCCESS);
}

$wiki_url = html_writer::link('https://wiki.magistere.education.fr/Indexer_un_parcours', html_writer::tag('i', '', array('class' => 'fas fa-question-circle')), array('target' => '_blank'));
$help_button = html_writer::div($wiki_url,'help-index');

echo $OUTPUT->header();
if (!has_capability('local/indexation:index', $context)){
    echo $OUTPUT->notification('Vous ne possédez pas les droits associés à l\'indexation.', 'notifyerror');
    echo $OUTPUT->footer();
    exit;
}
echo $OUTPUT->heading(get_string('indexation_title', 'local_indexation').$help_button, 2, null, $id = "id_head_title");
//echo $OUTPUT->heading(get_string('indexation_title', 'local_indexation'));

echo html_writer::start_div('tabs');
echo html_writer::div('1. '.get_string('general_tab', 'local_indexation').$notification_badge_general, 'tab general');
echo html_writer::div('2. '.get_string('organisme_tab', 'local_indexation').$notification_badge_organisme, 'tab organisme');
echo html_writer::div('3. '.get_string('detail_tab', 'local_indexation').$notification_badge_detail, 'tab detail');
echo html_writer::div('4. '.get_string('version_tab', 'local_indexation').$notification_badge_version, 'tab version');
echo html_writer::end_div();

echo html_writer::start_div('panel general');
$generalForm->display();
echo html_writer::end_div();

echo html_writer::start_div('panel organisme', array('style' => 'display: none;'));
$organismeForm->display();
echo html_writer::end_div();

echo html_writer::start_div('panel detail', array('style' => 'display: none;'));
$detailForm->display();
echo html_writer::end_div();

echo html_writer::start_div('panel version', array('style' => 'display: none;'));
$versionForm->display();
echo html_writer::end_div();

$getkeywordurl = new moodle_url('/local/indexation/get_keywords_list.php');
echo '<script>var getkeywordsurl="'.$getkeywordurl.'"</script>';

echo $OUTPUT->footer();


