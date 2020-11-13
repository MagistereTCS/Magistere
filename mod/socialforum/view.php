<?php
/**
 * @package   mod_socialforum
 * @copyright  2017 TCS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once('lib.php');

$id          = optional_param('id', 0, PARAM_INT);          // Course Module ID
$ctid        = optional_param('ctid', 0, PARAM_INT);        // Contribution ID or subject ID
$issubject     = optional_param('issubject', 0, PARAM_INT);

if($id){
    if (!$cm = get_coursemodule_from_id('socialforum', $id)) {
        print_error('Course Module ID was incorrect'); // NOTE this is invalid use of print_error, must be a lang string id
    }
} else {
	$ct = $DB->get_record('sf_contributions', array('id' => $ctid));
    if (!$cm = get_coursemodule_from_instance('socialforum', $ct->socialforum)) {
        print_error('Course Module ID was incorrect'); // NOTE this is invalid use of print_error, must be a lang string id
    }
}
$sf = new SocialForum($cm->instance);

if (!$course = $DB->get_record('course', array('id'=> $cm->course))) {
    print_error('course is misconfigured');  // NOTE As above
}
if (!$socialforum = $sf->get_instance()) {
    print_error('course module is incorrect'); // NOTE As above
}

require_course_login($course, true, $cm);

$params = array();
if ($id) {
    $params['id'] = $id;
    $display = null;
} else {
    $params['ctid'] = $ctid;
    if($issubject){
        $params['issubject'] = $issubject;
    }
    $display = SF_SUBJECT_VIEW;
}

$PAGE->set_url('/mod/socialforum/view.php', $params);

$context = context_module::instance($cm->id);
$coursecontext = context_course::instance($course->id);
$PAGE->set_context($context);

//if (!empty($CFG->enablerssfeeds) && !empty($CFG->forum_enablerssfeeds) && $socialforum->rsstype && $socialforum->rssarticles) {
//    require_once("$CFG->libdir/rsslib.php");
//
//    $rsstitle = format_string($course->shortname, true, array('context' => context_course::instance($course->id))) . ': ' . format_string($socialforum->name);
//    rss_add_http_header($context, 'mod_socialforum', $socialforum, $rsstitle);
//}

/// Print header.

$PAGE->set_title($socialforum->name);
$PAGE->add_body_class('socialforum');
$PAGE->set_heading($course->fullname);
$PAGE->requires->jquery();
echo $OUTPUT->header();

switch ($display) {
    case SF_SUBJECT_VIEW:
        $content = "";
        $subject = $sf->get_subject_by_id($ctid);
        $mlinks = html_writer::link($CFG->wwwroot.'/course/view.php?id='.$course->id, html_writer::img($OUTPUT->image_url('b/backtocourse','forum'), get_string("backtocourse", "socialforum")) . html_writer::span(get_string("backtocourse", "socialforum")),array('class'=>'mlink_backtocourse'));
        $mlinks .= html_writer::link(new moodle_url('view.php', array('id'=>$cm->id)), html_writer::img($OUTPUT->image_url('d/discussion','forum'), get_string("seeothersubjects", "socialforum")) . html_writer::span(get_string("seeothersubjects", "socialforum")),array('class'=>'mlink_seeother'));

        if (has_capability('mod/socialforum:viewdeferredcontributions', $context, $USER->id) && count($sf->get_deferred_contributions_by_subject_id($subject->id))) {
            $content = html_writer::table(display_subject_deferred_contributions($context, $sf, $ctid));
        }
        $content .= html_writer::div(display_main_action_subject($sf, $ctid), 'actions');
        $content .= html_writer::table(display_subject_detail($coursecontext, $sf, $ctid));
        $content .= html_writer::table(display_subject_contributions($coursecontext, $sf, $ctid));

        $header = $OUTPUT->heading(format_string($socialforum->name), 3);
        $header .= html_writer::div($mlinks,'mlinks clearfix');
        echo html_writer::div($header,'forumheader');
        echo html_writer::div($content);
        echo html_writer::div(display_subject_footer($sf, $ctid));

        echo html_writer::script(javascript_for_subject_detail($socialforum->id, $ctid), null);
        break;
    default:
        echo html_writer::script(false, new moodle_url('/mod/socialforum/js/jquery.tablesorter.min.js'));
        echo html_writer::div(html_writer::table(display_socialforum($sf, $cm)));
        $script = '$(document).ready(function() {
            // call the tablesorter plugin
            $(".social-forum-subjects").tablesorter();
        });';
        echo html_writer::script($script);
        break;
}

echo $OUTPUT->footer($course);