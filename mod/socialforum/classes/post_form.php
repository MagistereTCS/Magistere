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
 * File containing the form definition to post in the social forum.
 *
 * @package   mod_socialforum
 * @copyright 2017 TCS  {@link http://www.tcs.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/repository/lib.php');

/**
 * Class to post in a social forum.
 *
 * @package   mod_socialforum
 * @copyright 2017 TCS  {@link http://www.tcs.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class mod_socialforum_post_form extends moodleform {

    /**
     * Returns the options array to use in filemanager for forum attachments
     *
     * @param stdClass $forum
     * @return array
     */
    public static function attachment_options($socialforum) {
        global $COURSE, $PAGE, $CFG;
        $maxbytes = get_user_max_upload_file_size($PAGE->context, $CFG->maxbytes, $COURSE->maxbytes, $socialforum->maxbytes);
        return array(
            'subdirs' => 0,
            'maxbytes' => $maxbytes,
            'maxfiles' => $socialforum->maxattachments,
            'accepted_types' => '*',
            'return_types' => FILE_INTERNAL
        );
    }

    /**
     * Returns the options array to use in forum text editor
     *
     * @param context_module $context
     * @param int $postid post id, use null when adding new post
     * @return array
     */
    public static function editor_options(context_module $context, $postid) {
        global $COURSE, $PAGE, $CFG;
        // TODO: add max files and max size support
        $maxbytes = get_user_max_upload_file_size($PAGE->context, $CFG->maxbytes, $COURSE->maxbytes);
        return array(
            'maxfiles' => EDITOR_UNLIMITED_FILES,
            'maxbytes' => $maxbytes,
            'trusttext'=> true,
            'return_types'=> FILE_INTERNAL | FILE_EXTERNAL,
            'subdirs' => file_area_contains_subdirs($context, 'mod_socialforum', 'post', $postid)
        );
    }

    /**
     * Form definition
     *
     * @return void
     */
    function definition()
    {
        global $CFG, $OUTPUT, $USER;

        $mform =& $this->_form;
        $course = $this->_customdata['course'];
        $cm = $this->_customdata['cm'];
        $socialforum = $this->_customdata['socialforum'];
        $modcontext = $this->_customdata['modcontext'];
        $edit = $this->_customdata['edit'];

        $post = $this->_customdata['post'];
        $subject = $this->_customdata['subject'];
        $is_post = $this->_customdata['ispost'];
        $is_subject = $this->_customdata['issubject'];

        $mform->addElement('header', 'general', '');//fill in the data depending on page params later using set_data
		
        if ($is_subject) {
            $mform->addElement('text', 'subjecttitle', get_string('subject', 'socialforum'), 'size="48"');
            $mform->setType('subjecttitle', PARAM_TEXT);
            $mform->addRule('subjecttitle', get_string('required'), 'required', null, 'client');
            $mform->addRule('subjecttitle', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        }

        $mform->addElement('editor', 'message', get_string('message', 'socialforum'), null, self::editor_options($modcontext, (empty($post) ? null : $post)));
        $mform->setType('message', PARAM_RAW);
        $mform->addRule('message', get_string('required'), 'required', null, 'client');
        if($socialforum->modesubscribe == SF_DISALLOWSUBSCRIBE) {
            $mform->addElement('static', 'subscribemessage', get_string('subscription', 'socialforum'), get_string('disallowsubscribe', 'socialforum'));
            $mform->addElement('hidden', 'subscribesubject', 0);
            $mform->setType('subscribesubject', PARAM_INT);
            $mform->addHelpButton('subscribemessage', 'subscription', 'socialforum');
        } else if($socialforum->modesubscribe == SF_FORCESUBSCRIBE){
            $mform->addElement('static', 'subscribemessage', get_string('subscription', 'socialforum'), get_string('everyoneissubscribed', 'socialforum'));
            $mform->addElement('hidden', 'subscribesubject', 1);
            $mform->setType('subscribesubject', PARAM_INT);
            $mform->addHelpButton('subscribemessage', 'subscription', 'socialforum');
        } else {
            $options = array();
            $options[SF_UNSUBSCRIBESUBJECT] = get_string('unsubscriptionsubject', 'socialforum');
            $options[SF_SUBSCRIBESUBJECT] = get_string('subscriptionsubject', 'socialforum');
            $mform->addElement('select', 'subscribesubject', get_string('subscriptionmode', 'forum'), $options);
            $mform->addHelpButton('subscribesubject', 'subscriptionmode', 'forum');

        }

        if (!empty($socialforum->maxattachments) && $socialforum->maxbytes != 1)  {  //  1 = No attachments at all
        $mform->addElement('filemanager', 'attachments', get_string('attachment', 'socialforum'), null, self::attachment_options($socialforum));
        $mform->addHelpButton('attachments', 'attachment', 'forum');
        }
        if ($is_subject == 0) {
            if (has_capability('mod/socialforum:adddeferredcontributions', $modcontext, $USER->id)) {

                $mform->addElement('header', 'displayperiod', get_string('displayperiod', 'socialforum'));

                $radioarray = array();
                $radioarray[] = $mform->createElement('radio', 'published', '', get_string('sendcontributionnow', 'socialforum'), 0);
                $radioarray[] = $mform->createElement('radio', 'published', '', get_string('sendcontributionafter', 'socialforum'), 1);
                $mform->addGroup($radioarray, 'radioar', '', array(' '), false);


                $mform->addElement('date_time_selector', 'timepublished', null, array('optional' => true));

                $mform->addElement('html', '
        		<script>
        			$(document).ready(function() {
        			    $("input[name=\'published\']").change(function() {
        			        $("#id_timepublished_enabled").click();
						});
					});
        		</script>');
            }
        }


        //-------------------------------------------------------------------------------
        // buttons
        if (!$is_post) { // hack alert
            $submit_string = get_string('savechanges');
        } else {
            $submit_string = get_string('posttosocialforum', 'socialforum');
        }
        $this->add_action_buttons(false, $submit_string);

		$mform->addElement('hidden', 'course', $course->id);
        $mform->setType('course', PARAM_INT);
		
        $mform->addElement('hidden', 'socialforum', $socialforum->id);
        $mform->setType('socialforum', PARAM_INT);

        $mform->addElement('hidden', 'userid', $USER->id);
        $mform->setType('userid', PARAM_INT);

        $mform->addElement('hidden', 'post', $post);
        $mform->setType('post', PARAM_INT);

        $mform->addElement('hidden', 'subject', $subject);
        $mform->setType('subject', PARAM_INT);

        $mform->addElement('hidden', 'ispost', $is_post);
        $mform->setType('ispost', PARAM_INT);

        $mform->addElement('hidden', 'issubject', $is_subject);
        $mform->setType('issubject', PARAM_INT);

        $mform->addElement('hidden', 'groupid');
        $mform->setType('groupid', PARAM_INT);

        $mform->addElement('hidden', 'edit', $edit);
        $mform->setType('edit', PARAM_INT);

    }

    function data_preprocessing(&$default_values) {
        if ($this->current->instance) {
            // editing existing instance - copy existing files into draft area
            $draftitemid = file_get_submitted_draft_itemid('attachments');
            file_prepare_draft_area($draftitemid, $this->context->id, 'mod_socialforum', 'content', 0, array('subdirs'=>true));
            $default_values['attachments'] = $draftitemid;
        }
    }

    /**
     * Form validation
     *
     * @param array $data data from the form.
     * @param array $files files uploaded.
     * @return array of errors.
     */
    function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if (empty($data['message']['text'])) {
            $errors['message'] = get_string('erroremptymessage', 'forum');
        }
		if(isset($data['subjecttitle'])){
			if (empty($data['subjecttitle'])) {
				$errors['subjecttitle'] = get_string('erroremptysubject', 'forum');
			}
		}
        return $errors;
    }
}