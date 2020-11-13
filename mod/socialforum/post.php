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
 * Edit and save a new post to a discussion
 *
 * @package   mod_socialforum
 * @copyright 2017 TCS  {@link http://www.tcs.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once('lib.php');
require_once($CFG->libdir.'/completionlib.php');
require_once('classes/post_form.php');

$socialforum   = optional_param('socialforum', 0, PARAM_INT);
$is_subject = optional_param('issubject', 0, PARAM_INT);
$is_post = optional_param('ispost', 0, PARAM_INT);
$subject = optional_param('subject', 0, PARAM_INT);
$post  = optional_param('post', 0, PARAM_INT);
$quote  = optional_param('quote', 0, PARAM_INT);
$edit    = optional_param('edit', 0, PARAM_INT);
$delete  = optional_param('delete', 0, PARAM_INT);
$name    = optional_param('name', '', PARAM_CLEAN);
$confirm = optional_param('confirm', 0, PARAM_INT);
$groupid = optional_param('groupid', null, PARAM_INT);

$PAGE->set_url('/mod/socialforum/post.php', array(
    'socialforum' => $socialforum,
    'post' => $post,
    'subject' => $subject,
    'edit'  => $edit,
    'quote'  => $quote,
    'delete'=> $delete,
    'name'  => $name,
    'confirm'=>$confirm,
    'groupid'=>$groupid,
));

$sitecontext = context_system::instance();

if (!empty($socialforum)) {      // User is starting a new discussion in a forum
    if (!$socialforum = $DB->get_record("socialforum", array("id" => $socialforum))) {
        print_error('invalidforumid', 'forum');
    }
    if (!$course = $DB->get_record("course", array("id" => $socialforum->course))) {
        print_error('invalidcourseid');
    }
    if (!$cm = get_coursemodule_from_instance("socialforum", $socialforum->id, $course->id)) {
        print_error("invalidcoursemodule");
    }
}

if(!empty($edit) || !empty($quote) || !empty($delete)) {
    if (!$contribution = $DB->get_record("sf_contributions", array("id" => $post))) {
        print_error('notpartofdiscussion', 'forum');
    }
    if (!$socialforum = $DB->get_record("socialforum", array("id" => $contribution->socialforum))) {
        print_error('invalidforumid', 'forum');
    }
    if (!$course = $DB->get_record('course', array('id' => $socialforum->course))) {
        print_error('invalidcourseid');
    }
    if (!$cm = get_coursemodule_from_instance("socialforum", $socialforum->id, $socialforum->course)) {
        print_error('invalidcoursemodule');
    }
}

require_course_login($course, true, $cm);

//these page_params will be passed as hidden variables later in the form.
$page_params = array('edit'=>$edit, 'quote'=>$quote);


// Retrieve the contexts.
$modcontext    = context_module::instance($cm->id);
$coursecontext = context_course::instance($course->id);

$PAGE->set_heading($course->fullname);
$PAGE->set_cm($cm, $course, $socialforum);
$PAGE->set_context($modcontext);
$PAGE->requires->jquery();

if (!(has_capability('mod/socialforum:addcontribution', $modcontext, $USER->id))) {
    print_error('cannotcreatedcontribution', 'socialforum');
}

if (!(has_capability('mod/socialforum:editcontribution', $modcontext, $USER->id))) {
    print_error('cannotupdatedcontribution', 'socialforum');
}

if($is_subject){
    $heading = get_string('yournewtopic', 'socialforum');
} else {
    $heading = get_string("yourcontribution", "socialforum");
}

$subscription_user = null;
if($subject){
    $subscription_user = $DB->get_record('sf_subscriptions', array('subject' => $subject, 'userid' => $USER->id));
} else {
    if(isset($contribution)) {
        $subscription_user = $DB->get_record('sf_subscriptions', array('subject' => $contribution->subject, 'userid' => $USER->id));
    }
}

if(isset($subscription_user)){
    $subscribeuser = SF_SUBSCRIBESUBJECT;
} else {
    if($socialforum->modesubscribe == SF_INITIALSUBSCRIBE){
        $subscribeuser = SF_SUBSCRIBESUBJECT;
    } else {
        $subscribeuser = SF_UNSUBSCRIBESUBJECT;
    }
}

$mform_post = new mod_socialforum_post_form('post.php', array('course' => $course,
    'cm' => $cm,
    'coursecontext' => $coursecontext,
    'modcontext' => $modcontext,
    'socialforum' => $socialforum,
    'general' => $heading,
    'ispost' => $is_post,
    'issubject' => $is_subject,
    'post' => $post,
    'subject' => $subject,
    'edit' => $edit), 'post', '', array('id' => 'mform-socialforum'));

if($socialforum->modesubscribe != SF_DISALLOWSUBSCRIBE && $socialforum->modesubscribe != SF_FORCESUBSCRIBE){
    $mform_post->set_data(array('subscribesubject'=>$subscribeuser));
}
if($edit || $quote) {
    $draftitemid = file_get_submitted_draft_itemid('attachments');
    file_prepare_draft_area($draftitemid, $modcontext->id, 'mod_socialforum', 'attachment', $contribution->id, mod_socialforum_post_form::attachment_options($socialforum));

    $draftid_editor = file_get_submitted_draft_itemid('message');
    $currenttext = file_prepare_draft_area($draftid_editor, $modcontext->id, 'mod_socialforum', 'message', $contribution->id, mod_socialforum_post_form::editor_options($modcontext,  $contribution->id), $contribution->message);
}
if($edit){
    if($contribution->issubject){
        $heading = get_string('edittopic', 'socialforum');
    } else {
        $heading = get_string("editcontribution", "socialforum");
    }

    $mform_post->set_data(array(
        'attachments'=>$draftitemid,
            'general'=>$heading,
            'subjecttitle'=>$contribution->subjecttitle,
            'message'=>array(
                'text'=>$currenttext,
                'format'=>empty($contribution->messageformat) ? editors_get_preferred_format() : $contribution->messageformat,
                'itemid'=>$draftid_editor
            ),
            'subscribesubject'=>$subscribeuser,
            'timepublished'=>$contribution->timepublished,
            'userid'=>$contribution->userid,
            'subject'=>$contribution->subject,
            'course'=>$course->id) +
        $page_params +

        (($contribution->subject != 0)?array(
            'ispost'=>1):
            array('issubject'=>1))+

        (($contribution->timepublished < time())?array(
            'published'=>0):
            array('published'=>1))+

        (isset($post->groupid)?array(
            'groupid'=>$contribution->groupid):
            array()));
}

if($quote){
    $heading = get_string("yourcontribution", "socialforum");
    $userfrom = $DB->get_record('user',array('id' => $contribution->userid));
    $headerquote = "<div class='header'><span class='author'>" . $userfrom->firstname . " " . $userfrom->lastname ."</span> a écrit : </div>";
    $mform_post->set_data(array(
        'general'=>$heading,
        'message'=>array(
            'text'=>"<blockquote>".$headerquote . $currenttext.'</blockquote><br/>',
            'format'=>empty($contribution->messageformat) ? editors_get_preferred_format() : $contribution->messageformat,
            'itemid'=>$draftid_editor
        ),
        'ispost'=>1,
        'subject'=>($contribution->issubject) ? $contribution->id : $contribution->subject, // Cas d'une citation de sujet
        'course'=>$course->id) + $page_params
    );
}

if ($fromform = $mform_post->get_data()) {
// Load up the $post variable.

    $contribution = new stdClass();
    $contribution->course = $fromform->course;
    $contribution->socialforum = $fromform->socialforum;
    $contribution->itemid        = $fromform->message['itemid'];
    $contribution->messageformat = $fromform->message['format'];
    $contribution->message       = $fromform->message['text'];
	if($fromform->issubject){
		$contribution->subjecttitle = $fromform->subjecttitle;
	}
    $contribution->issubject = $fromform->issubject;
    if($fromform->ispost){
        $contribution->subject = $fromform->subject;
    }
    $contribution->messageformat = editors_get_preferred_format();
    $contribution->userid = $fromform->userid;
    $contribution->attachments = $fromform->attachments;

    if (isset($groupid)) {
        $contribution->groupid = $groupid;
    } else {
        $contribution->groupid = groups_get_activity_group($cm);
    }
    if (has_capability('mod/socialforum:adddeferredcontributions', $modcontext)) {
        if(isset($fromform->published)){
            if ($fromform->published == 0) {
                $contribution->timepublished = time();
            } else {
                $contribution->timepublished = $fromform->timepublished;
            }
        }
    } else {
        $contribution->timepublished = time();
    }

    if($edit) {
        $contribution->id = $fromform->post;
        $contribution->usermodified = $USER->id;
        $contribution->timemodified= time();
        $DB->update_record('sf_contributions', $contribution);
        $message = get_string("contributionhasbeenupdated", "socialforum");
    } else {
        $contribution->timecreated = time();
        $contribution->id = $DB->insert_record('sf_contributions', $contribution);
        if($is_subject){
            $subject = $contribution->id; // Cas de la création d'un sujet pour la souscription
        }
        $message = get_string("contributionhasbeencreated", "socialforum");
    }

    if($fromform->subscribesubject) {
        if(!$subscription_user) {
            $subscription = new stdClass();
            $subscription->userid = $USER->id;
            $subscription->subject = $subject;
            $subscription->timecreated = time();
            $DB->insert_record('sf_subscriptions', $subscription);
        }
    } else {
        if ($subscription_user) {
            $DB->delete_records('sf_subscriptions', array('id' => $subscription_user->id));
        }
    }

    $contribution->message = file_save_draft_area_files($contribution->itemid, $modcontext->id, 'mod_socialforum', 'message', $contribution->id,
        mod_socialforum_post_form::editor_options($modcontext, null), $contribution->message);
    $DB->set_field('sf_contributions', 'message', $contribution->message, array('id'=>$contribution->id));
    socialforum_add_attachment($contribution, $socialforum, $cm, $mform_post);

//    $contributionurl = "view.php?id=$cm->id";
//    redirect($contributionurl);
    if($fromform->issubject){
        $returnto = "view.php?ctid=$contribution->id&issubject=1";
    } else {
        $returnto = "view.php?ctid=$contribution->subject&issubject=1";
    }

    redirect($returnto, $message, 1);

    exit;
}
$confirm_delete_subject = false;
if($delete){
    if (!(has_capability('mod/socialforum:deletecontribution', $modcontext))) {
        print_error('cannotdeletedcontribution', 'socialforum');
    } else {
        if($is_subject){
            if($confirm){
                $sf = new SocialForum($contribution->socialforum);
                $sf->delete_cascade_subject($contribution->id);
                $message = get_string("subjecthasbeendeleted", "socialforum");
                $returnto = "view.php?id=$cm->id";
                redirect($returnto, $message, 1);
            } else {
                $confirm_delete_subject = true;
            }
        } else {
            $subjectid = $contribution->subject;
            $sf = new SocialForum($contribution->socialforum);
            $sf->delete_contribution($contribution->id);
            $message = get_string("contributionhasbeendeleted", "socialforum");
            $returnto = "view.php?ctid=$subjectid&issubject=1";
            redirect($returnto, $message, 1);
        }
    }
}

echo $OUTPUT->header();
$heading = $OUTPUT->heading(format_string($socialforum->name), 2);
echo $OUTPUT->add_encart_activity($heading);
if($confirm_delete_subject){
    echo $OUTPUT->confirm(get_string("confirmdeletesubject", "socialforum"), 'post.php?delete=1&post='.$contribution->id.'&issubject=1&confirm=1', 'view.php?ctid='.$contribution->id.'&issubject=1');
} else {
    $mform_post->display();
}

echo $OUTPUT->footer();
