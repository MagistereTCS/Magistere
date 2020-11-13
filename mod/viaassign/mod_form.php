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
 * This file contains the forms to create and edit an instance of this module
 *
 * @package   mod_viaassign
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');

require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot . '/mod/viaassign/locallib.php');
require_once($CFG->dirroot . '/lib/accesslib.php');
require_once($CFG->dirroot . '/mod/via/locallib.php');

/**
 * viaassignment settings form.
 *
 * @package   mod_viaassign
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_viaassign_mod_form extends moodleform_mod {
    /**
     * Called to define this moodle form
     *
     * @return void
     */
    public function definition() {
        global $CFG, $DB, $PAGE;
        $mform = $this->_form;

        $PAGE->requires->jquery();
        $PAGE->requires->js('/mod/via/javascript/mod_form.js', true);

        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('viaassignmentname', 'viaassign'), array('size' => '64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        // Description!
        if ($CFG->version >= 2015051100) {
            $this->standard_intro_elements();
        } else {
            $this->add_intro_editor(true);
        }

        $ctx = null;
        if ($this->current && $this->current->coursemodule) {
            $cm = get_coursemodule_from_instance('viaassign', $this->current->id, 0, false, MUST_EXIST);
            $ctx = context_module::instance($cm->id);
        }
        $viaassignment = new viaassign($ctx, null, null);
        if ($this->current && $this->current->course) {
            if (!$ctx) {
                $ctx = context_course::instance($this->current->course);
            }
            $course = $DB->get_record('course', array('id' => $this->current->course), '*', MUST_EXIST);
            $viaassignment->set_course($course);
        }

        $config = get_config('viaassign');
        /* Availability */
        $mform->addElement('header', 'availability', get_string('availability', 'viaassign'));
        $mform->setExpanded('availability', true);

        // We validate that roles existe that can create activities!
        // If no roles can create activities we display a warning!
        $roles = get_role_names_with_caps_in_context($ctx, array('mod/viaassign:view'));
        if (!$roles) {
            $roles = '';
            $mform->addElement('html', html_writer::tag('h6',
            get_string('userrole_none', 'viaassign'), array('class' => 'subheader')));
        }
        $select = $mform->addElement('select', 'userrole', get_string('userrole', 'mod_viaassign'), $roles);
        $select->setMultiple(true);

        // It is possible to add a default value!
        if (isset($config->userrole)) {
            $mform->setDefault('userrole', $config->userrole);
        }
        $mform->addRule('userrole', null, 'required', null, 'client');
        $mform->addHelpButton('userrole', 'userrole', 'viaassign');

        $name = get_string('allowsubmissionsfromdate', 'viaassign');
        $mform->addElement('date_time_selector', 'allowsubmissionsfromdate', $name);
        $mform->addHelpButton('allowsubmissionsfromdate', 'allowsubmissionsfromdate', 'viaassign');

        $name = get_string('duedate', 'viaassign');
        $mform->addElement('date_time_selector', 'duedate', $name);
        $mform->addHelpButton('duedate', 'duedate', 'viaassign');

        /* Settings */
        $mform->addElement('header', 'viasettings', get_string('viasettings', 'viaassign'));
        $mform->setExpanded('viasettings', true);

        $options = array_combine(range(1, 30), range(1, 30));
        $mform->addElement('select', 'maxactivities', get_string('maxactivities', 'mod_viaassign'), $options);
        $mform->setDefault('maxactivities', '1');
        $mform->addHelpButton('maxactivities', 'maxactivities', 'viaassign');

        $mform->addElement('text', 'maxduration', get_string('maxduration', 'viaassign'), array('size' => 4, 'maxlength' => 4));
        $mform->setType('maxduration', PARAM_INT);
        $mform->setDefault('maxduration', '60');

        $mform->addElement('text', 'maxusers', get_string('maxusers', 'viaassign'), array('size' => 4, 'maxlength' => 3));
        $mform->setType('maxusers', PARAM_INT);
        $mform->setDefault('maxusers', '0');
        $mform->addHelpButton('maxusers', 'maxusers', 'viaassign');

        // Session recordings!
        $recordoptions = array( 0 => get_string('notactivated', 'via'),
            1 => get_string('unified', 'via'),
            2 => get_string('multiple', 'via'));
        $mform->addElement('select', 'recordingmode', get_string('recordingmode', 'via'), $recordoptions);
        $mform->setDefault('recordingmode', 0);
        $mform->addHelpButton('recordingmode', 'recordingmode', 'via');

        // Review playbacks!
        $recordoptions = array( 0 => get_string('playbackaccesstype0', 'via'),
                                1 => get_string('playbackaccesstype1', 'via'),
                                2 => get_string('playbackaccesstype2', 'via'));
        $mform->addElement('select', 'isreplayallowed', get_string('reviewactivity', 'viaassign'), $recordoptions);
        $mform->setDefault('isreplayallowed', 0);
        $mform->disabledif ('isreplayallowed', 'recordingmode', 'eq', 0);
        $mform->addHelpButton('isreplayallowed', 'reviewactivity', 'viaassign');

        // Activity type!
        $roomoptions = array( 1 => get_string('standard', 'viaassign'), 2 => get_string('seminar', 'viaassign'));
        $mform->addElement('select', 'roomtype', get_string('roomtype', 'viaassign'), $roomoptions);
        $mform->setDefault('roomtype', 1);
        $mform->addHelpButton('roomtype', 'roomtype', 'via');

        $qualityoptions = $DB->get_records('via_params', array('param_type' => 'multimediaprofil'));
        if (!$qualityoptions) {
            via_get_list_profils();
            $qualityoptions = $DB->get_records('via_params', array('param_type' => 'multimediaprofil'));
        }
        if ($qualityoptions) {
            $options = array();
            foreach ($qualityoptions as $option) {
                $options[$option->value] = via_get_profilname($option->param_name);
            }
            $mform->addElement('select', 'multimediaquality', get_string('multimediaquality', 'via'), $options);
            $mform->disabledif ('multimediaquality', 'pastevent', 'eq', 1);
            $mform->addHelpButton('multimediaquality', 'multimediaquality', 'via');
        }

        $waitingroomoptions = array(0 => get_string('donousewaitingroom', 'via'),
            1 => get_string('inhostabsence', 'via'),
            2 => get_string('awaitingauthorization', 'via'));
        $mform->addElement('select', 'waitingroomaccessmode', get_string('waitingroomaccessmode', 'via'), $waitingroomoptions);
        $mform->setDefault('waitingroomaccessmode', 0);
        $mform->addHelpButton('waitingroomaccessmode', 'waitingroomaccessmode', 'via');

        if (get_config('via', 'via_presencestatus')) {
            // Presence!
            $mform->addElement('selectyesno', 'takepresence', get_string('takepresence', 'viaassign'));
            $mform->setDefault('takepresence', 0);
            $mform->addHelpButton('takepresence', 'takepresence', 'viaassign');

            $mform->addElement('text', 'minpresence', get_string('presence', 'via'), array('size' => '4'));
            $mform->addHelpButton('minpresence', 'presence', 'via');
            $mform->setType('minpresence', PARAM_INT);
            $mform->setDefault('minpresence', '30');
            $mform->disabledif ('minpresence', 'takepresence', 0);
        } else {
            $mform->addElement('hidden', 'takepresence', get_string('takepresence', 'viaassign'));
            $mform->setType('takepresence', PARAM_INT);
            $mform->setDefault('takepresence', 0);

            $mform->addElement('hidden', 'minpresence', get_string('presence', 'via'), array('size' => '4'));
            $mform->setDefault('minpresence', '0');
            $mform->setType('minpresence', PARAM_INT);
        }

        $mform->addElement('header', 'notifications', get_string('notifications', 'viaassign'));

        $name = get_string('sendnotifications', 'viaassign');
        $mform->addElement('selectyesno', 'sendnotifications', $name);
        $mform->addHelpButton('sendnotifications', 'sendnotifications', 'viaassign');

        $name = get_string('sendstudentnotificationsdefault', 'viaassign');
        $mform->addElement('selectyesno', 'sendstudentnotifications', $name);
        $mform->addHelpButton('sendstudentnotifications', 'sendstudentnotificationsdefault', 'viaassign');

        // Code: $this->standard_grading_coursemodule_elements();

        $mform->addElement('header', 'modstandardgrade', get_string('grade'));
        // If supports grades and grades arent being handled via ratings.
        if (!$this->_features->rating) {
            $mform->addElement('modgrade', 'grade', get_string('grade'));
            $mform->addHelpButton('grade', 'modgrade', 'grades');
            $mform->setDefault('grade', 0);
        }

        // GROUPS AND VISIBILITY!
        // Standard grouping features.
        $this->_features->groups = true;
        $this->_features->groupings = true;
        $this->_features->groupmembersonly = true;
        $this->standard_coursemodule_elements();

        $this->apply_admin_defaults();

        $mform->addElement('hidden', 'viaassignfeedback_comments_enabled', 1);
        $mform->setType('viaassignfeedback_comments_enabled', PARAM_BOOL);

        $mform->addElement('hidden', 'viaassignfeedback_comments_inline', 1);
        $mform->setType('viaassignfeedback_comments_inline', PARAM_BOOL);

        $this->add_action_buttons();

        // Add warning popup/noscript tag, if grades are changed by user.
        $hasgrade = false;
        if (!empty($this->_instance)) {
            $hasgrade = $DB->record_exists_select('viaassign_grades',
                'viaassign = ? AND grade <> -1',
                array($this->_instance));
        }

        if ($mform->elementExists('grade') && $hasgrade) {
            $module = array(
                'name' => 'mod_viaassign',
                'fullpath' => '/mod/viaassign/module.js',
                'requires' => array('node', 'event'),
                'strings' => array(array('changegradewarning', 'mod_viaassign'))
                );
            $PAGE->requires->js_init_call('M.mod_viaassign.init_grade_change', null, false, $module);

            // Add noscript tag in case.
            $noscriptwarning = $mform->createElement('static',
                'warning',
                null,
                html_writer::tag('noscript',
                        get_string('changegradewarning', 'mod_viaassign')));
            $mform->insertElementBefore($noscriptwarning, 'grade');
        }
    }

    /**
     * Perform minimal validation on the settings form
     * @param array $data
     * @param array $files
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if ($data['allowsubmissionsfromdate'] && $data['duedate']) {
            if ($data['allowsubmissionsfromdate'] > $data['duedate']) {
                $errors['duedate'] = get_string('duedatevalidation', 'viaassign');
            }
        }
        if (!isset($data['userrole'])) {
            $errors['userrole'] = get_string('userrolevalidation', 'viaassign');
        }

        return $errors;
    }

    /**
     * Any data processing needed before the form is displayed
     * (needed to set up draft areas for editor and filemanager elements)
     * @param array $defaultvalues
     */
    public function data_preprocessing(&$defaultvalues) {
        global $DB;

        $ctx = null;
        if ($this->current && $this->current->coursemodule) {
            $cm = get_coursemodule_from_instance('viaassign', $this->current->id, 0, false, MUST_EXIST);
            $ctx = context_module::instance($cm->id);
        }
        $viaassignment = new viaassign($ctx, null, null);
        if ($this->current && $this->current->course) {
            if (!$ctx) {
                $ctx = context_course::instance($this->current->course);
            }
            $course = $DB->get_record('course', array('id' => $this->current->course), '*', MUST_EXIST);
            $viaassignment->set_course($course);
        }
        $viaassignment->plugin_data_preprocessing($defaultvalues);

        if (!$defaultvalues['id']) {
            // TEMPLATE VALUES!
            if ($sviinfos = $DB->get_records('via_params', array('param_type' => 'ActivityTemplate'))) {
                foreach ($sviinfos as $key => $svi) {
                    switch($svi->param_name) {
                        case 'RecordingMode' :
                            $defaultvalues['recordingmode'] = $svi->value;
                            break;

                        case 'RecordModeBehavior' :
                            $defaultvalues['recordmodebehavior'] = $svi->value;
                            break;

                        case 'IsReplayAllowed' :
                            $defaultvalues['isreplayallowed'] = $svi->value;
                            break;

                        case 'ProfilID' :
                            $defaultvalues['multimediaquality'] = $svi->value;
                            break;

                        case 'ActivityType' :
                            $defaultvalues['activitytype'] = $svi->value;
                            break;

                        case 'RoomType' :
                            $defaultvalues['roomtype'] = $svi->value;
                            break;

                        case 'WaitingRoomAccessMode' :
                            $defaultvalues['waitingroomaccessmode'] = $svi->value;
                            break;
                    }
                }
            }
        }
    }

    /**
     * Add any custom completion rules to the form.
     *
     * @return array Contains the names of the added form elements
     */
    public function add_completion_rules() {
        $mform =& $this->_form;

        $mform->addElement('checkbox', 'completionsubmit', '', get_string('completionsubmit', 'viaassign'));
        return array('completionsubmit');
    }

    /**
     * Determines if completion is enabled for this module.
     *
     * @param array $data
     * @return bool
     */
    public function completion_rule_enabled($data) {
        return !empty($data['completionsubmit']);
    }
}