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
 * @package   mod_socialforum
 * @copyright  2017 TCS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->dirroot.'/mod/socialforum/lib.php');

$id      = optional_param('id', 0, PARAM_INT);             // the forum to subscribe or unsubscribe to
$mode    = optional_param('mode', null, PARAM_INT);     // the forum's subscription mode
$user    = optional_param('user', 0, PARAM_INT);        // userid of the user to subscribe, defaults to $USER
$sesskey = optional_param('sesskey', null, PARAM_RAW);  // sesskey
$subjectid = optional_param('subjectid', 0, PARAM_INT);  // subject id to subscribe or unsubscribe



if($subjectid){
    $url = new moodle_url('/mod/socialforum/subscribe.php', array('subjectid' => $subjectid));
    if (!is_null($sesskey)) {
        $url->param('sesskey', $sesskey);
    }
    $PAGE->set_url($url);

    $subject = $DB->get_record('sf_contributions', array('id' => $subjectid), '*', MUST_EXIST);
    $socialforum = $DB->get_record('socialforum', array('id' => $subject->socialforum), '*', MUST_EXIST);

    $course = $DB->get_record('course', array('id' => $socialforum->course), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('socialforum', $socialforum->id, $course->id, false, MUST_EXIST);
    $context = context_module::instance($cm->id);

    if ($user) {
        require_sesskey();
    if (!has_capability('mod/socialforum:managesubscriptions', $context)) {
        print_error('nopermissiontosubscribe', 'socialforum');
    }
        $user = $DB->get_record('user', array('id' => $user), '*', MUST_EXIST);
    } else {
        $user = $USER;
    }

    require_login($course, false, $cm);

    if (is_null($mode) and !is_enrolled($context, $USER, '', true)) {   // Guests and visitors can't subscribe - only enrolled
        $PAGE->set_title($course->shortname);
        $PAGE->set_heading($course->fullname);
        if (isguestuser()) {
            echo $OUTPUT->header();
            echo $OUTPUT->confirm(get_string('subscribeenrolledonly', 'forum') . '<br /><br />' . get_string('liketologin'),
                get_login_url(), new moodle_url('/mod/socialforum/view.php', array('ctid' => $subject->id, 'issubject' => '1')));
            echo $OUTPUT->footer();
            exit;
        } else {
            // there should not be any links leading to this place, just redirect
            redirect(new moodle_url('/mod/socialforum/view.php', array('ctid' => $subject->id, 'issubject' => '1')), get_string('subscribeenrolledonly', 'forum'));
        }
    }
    if(socialforum_subject_is_subscribed($user->id, $subject->id)){
        socialforum_subject_unsubscribe($user->id, $subject->id);
        $message = get_string("youarenowunsubscribed", "socialforum");
    } else {
        socialforum_subject_subscribe($user->id, $subject->id);
        $message = get_string("youarenowsubscribed", "socialforum");
    }

    $returnto = "view.php?ctid=$subject->id&issubject=1";
    redirect($returnto, $message, 1);

} else {

    $url = new moodle_url('/mod/socialforum/subscribe.php', array('id' => $id));
    if (!is_null($mode)) {
        $url->param('mode', $mode);
    }
    if ($user !== 0) {
        $url->param('user', $user);
    }
    if (!is_null($sesskey)) {
        $url->param('sesskey', $sesskey);
    }
    $PAGE->set_url($url);

    $socialforum = $DB->get_record('socialforum', array('id' => $id), '*', MUST_EXIST);

    $course = $DB->get_record('course', array('id' => $socialforum->course), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('socialforum', $socialforum->id, $course->id, false, MUST_EXIST);
    $context = context_module::instance($cm->id);

    if ($user) {
        require_sesskey();
        if (!has_capability('mod/socialforum:managesubscriptions', $context)) {
            print_error('nopermissiontosubscribe', 'socialforum');
        }
        $user = $DB->get_record('user', array('id' => $user), '*', MUST_EXIST);
    } else {
        $user = $USER;
    }

    require_login($course, false, $cm);

    if (is_null($mode) and !is_enrolled($context, $USER, '', true)) {   // Guests and visitors can't subscribe - only enrolled
        $PAGE->set_title($course->shortname);
        $PAGE->set_heading($course->fullname);
        if (isguestuser()) {
            echo $OUTPUT->header();
            echo $OUTPUT->confirm(get_string('subscribeenrolledonly', 'forum') . '<br /><br />' . get_string('liketologin'),
                get_login_url(), new moodle_url('/mod/socialforum/view.php', array('id' => $cm->id)));
            echo $OUTPUT->footer();
            exit;
        } else {
            // there should not be any links leading to this place, just redirect
            redirect(new moodle_url('/mod/socialforum/view.php', array('id' => $cm->id)), get_string('subscribeenrolledonly', 'forum'));
        }
    }

    $returnto = "view.php?id=$cm->id";

//if (!is_null($mode) and has_capability('mod/socialforum:managesubscriptions', $context)) {
    require_sesskey();
    switch ($mode) {
        case SF_CHOOSESUBSCRIBE : // 0
            manage_subscriptions($socialforum->id, SF_CHOOSESUBSCRIBE);
            redirect($returnto, get_string("everyonecannowchoose", "forum"), 1);
            break;
        case SF_FORCESUBSCRIBE : // 1
            manage_subscriptions($socialforum->id, SF_FORCESUBSCRIBE);
            redirect($returnto, get_string("everyoneisnowsubscribed", "forum"), 1);
            break;
        case SF_INITIALSUBSCRIBE : // 2
            manage_subscriptions($socialforum->id, SF_INITIALSUBSCRIBE);
            redirect($returnto, get_string("everyoneisnowsubscribed", "forum"), 1);
            break;
        case SF_DISALLOWSUBSCRIBE : // 3
            manage_subscriptions($socialforum->id, SF_DISALLOWSUBSCRIBE);
            redirect($returnto, get_string("noonecansubscribenow", "forum"), 1);
            break;
        default:
            print_error(get_string('invalidforcesubscribe', 'forum'));
    }
//}
}

