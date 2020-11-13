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

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once ($CFG->dirroot.'/course/moodleform_mod.php');
require_once ($CFG->dirroot.'/mod/socialforum/lib.php');

class mod_socialforum_mod_form extends moodleform_mod {

    function definition() {
        global $CFG, $COURSE, $DB;

        $mform    =& $this->_form;

//-------------------------------------------------------------------------------
        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('forumname', 'socialforum'), array('size'=>'64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $this->standard_intro_elements(get_string('forumintro', 'forum'));


        // Attachments and word count.
        $mform->addElement('header', 'attachmentswordcounthdr', get_string('attachmentswordcount', 'forum'));

        $choices = get_max_upload_sizes($CFG->maxbytes, $COURSE->maxbytes, 0, $CFG->forum_maxbytes);
        $choices[1] = get_string('uploadnotallowed');
        $mform->addElement('select', 'maxbytes', get_string('maxattachmentsize', 'forum'), $choices);
        $mform->addHelpButton('maxbytes', 'maxattachmentsize', 'forum');
        $mform->setDefault('maxbytes', $CFG->forum_maxbytes);

        $choices = array(
            0 => 0,
            1 => 1,
            2 => 2,
            3 => 3,
            4 => 4,
            5 => 5,
            6 => 6,
            7 => 7,
            8 => 8,
            9 => 9,
            10 => 10,
            20 => 20,
            50 => 50,
            100 => 100
        );
        $mform->addElement('select', 'maxattachments', get_string('maxattachments', 'forum'), $choices);
        $mform->addHelpButton('maxattachments', 'maxattachments', 'forum');
        $mform->setDefault('maxattachments', $CFG->forum_maxattachments);

        $mform->addElement('selectyesno', 'displaywordcount', get_string('displaywordcount', 'forum'));
        $mform->addHelpButton('displaywordcount', 'displaywordcount', 'forum');
        $mform->setDefault('displaywordcount', 0);

        // Subscription and tracking.
        $mform->addElement('header', 'subscriptionandtrackinghdr', get_string('subscriptionandtracking', 'forum'));

        $options = array();
        $options[SF_CHOOSESUBSCRIBE] = get_string('subscriptionoptional', 'forum');
        $options[SF_FORCESUBSCRIBE] = get_string('subscriptionforced', 'forum');
        $options[SF_INITIALSUBSCRIBE] = get_string('subscriptionauto', 'forum');
        $options[SF_DISALLOWSUBSCRIBE] = get_string('subscriptiondisabled','forum');
        $mform->addElement('select', 'modesubscribe', get_string('subscriptionmode', 'forum'), $options);
        $mform->addHelpButton('modesubscribe', 'subscriptionmode', 'forum');

        if ($CFG->enablerssfeeds && isset($CFG->forum_enablerssfeeds) && $CFG->forum_enablerssfeeds) {
//-------------------------------------------------------------------------------
            $mform->addElement('header', 'rssheader', get_string('rss'));
            $choices = array();
            $choices[0] = get_string('none');
            $choices[1] = get_string('discussions', 'forum');
            $choices[2] = get_string('posts', 'forum');
            $mform->addElement('select', 'rsstype', get_string('rsstype'), $choices);
            $mform->addHelpButton('rsstype', 'rsstype', 'forum');

            $choices = array();
            $choices[0] = '0';
            $choices[1] = '1';
            $choices[2] = '2';
            $choices[3] = '3';
            $choices[4] = '4';
            $choices[5] = '5';
            $choices[10] = '10';
            $choices[15] = '15';
            $choices[20] = '20';
            $choices[25] = '25';
            $choices[30] = '30';
            $choices[40] = '40';
            $choices[50] = '50';
            $mform->addElement('select', 'rssarticles', get_string('rssarticles'), $choices);
            $mform->addHelpButton('rssarticles', 'rssarticles', 'forum');
            $mform->disabledIf('rssarticles', 'rsstype', 'eq', '0');
        }

        $this->standard_grading_coursemodule_elements();

        $this->standard_coursemodule_elements();
//-------------------------------------------------------------------------------
// buttons
        $this->add_action_buttons();

    }

//    function data_preprocessing(&$default_values) {
//        parent::data_preprocessing($default_values);
//
//        // Set up the completion checkboxes which aren't part of standard data.
//        // We also make the default value (if you turn on the checkbox) for those
//        // numbers to be 1, this will not apply unless checkbox is ticked.
//        $default_values['completiondiscussionsenabled']=
//            !empty($default_values['completiondiscussions']) ? 1 : 0;
//        if (empty($default_values['completiondiscussions'])) {
//            $default_values['completiondiscussions']=1;
//        }
//        $default_values['completionrepliesenabled']=
//            !empty($default_values['completionreplies']) ? 1 : 0;
//        if (empty($default_values['completionreplies'])) {
//            $default_values['completionreplies']=1;
//        }
//        $default_values['completionpostsenabled']=
//            !empty($default_values['completionposts']) ? 1 : 0;
//        if (empty($default_values['completionposts'])) {
//            $default_values['completionposts']=1;
//        }
//    }
}

