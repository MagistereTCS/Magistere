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
 * Define all the backup steps that will be used by the backup_viaassign_activity_task
 *
 * @package   mod_viaassign
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Define the complete choice structure for backup, with file and id annotations
 *
 * @package   mod_viaassign
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_viaassign_activity_structure_step extends backup_activity_structure_step {
    /**
     * Define the structure for the viaassign activity
     * @return void
     */
    protected function define_structure() {
        // To know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated.
        $viaassign = new backup_nested_element('viaassign', array('id'),
        array('course', 'name', 'intro', 'introformat', 'duedate', 'allowsubmissionsfromdate',
        'grade', 'timemodified', 'timecreated', 'completionsubmit', 'userrole', 'maxactivities',
        'maxduration', 'maxusers', 'recordingmode', 'isreplayallowed', 'waitingroomaccessmode',
        'roomtype', 'multimediaquality', 'takepresence', 'minpresence', 'sendnotifications',
        'sendstudentnotifications'));

        $userflags = new backup_nested_element('userflags');

        $userflag = new backup_nested_element('userflag', array('id'),
                    array('userid', 'viaassign', 'locked', 'mailed', 'extensionduedate'));

        $submissions = new backup_nested_element('submissions');

        $submission = new backup_nested_element('submission', array('id'),
                      array('viaassignid', 'viaid', 'userid', 'timecreated', 'timemodified', 'status', 'groupid'));

        $grades = new backup_nested_element('grades');

        $grade = new backup_nested_element('grade', array('id'),
         array('viaassign', 'userid', 'viaid', 'timecreated', 'timemodified', 'grader', 'grade'));

        $pluginconfigs = new backup_nested_element('plugin_configs');

        $pluginconfig = new backup_nested_element('plugin_config', array('id'),
        array('viaassign', 'plugin', 'subtype', 'name', 'value'));

        // Build the tree.
        $viaassign->add_child($userflags);
        $userflags->add_child($userflag);
        $viaassign->add_child($submissions);
        $submissions->add_child($submission);
        $viaassign->add_child($grades);
        $grades->add_child($grade);
        $viaassign->add_child($pluginconfigs);
        $pluginconfigs->add_child($pluginconfig);

        // Define sources.
        $viaassign->set_source_table('viaassign', array('id' => backup::VAR_ACTIVITYID));
        $pluginconfig->set_source_table('viaassign_plugin_config',
        array('viaassign' => backup::VAR_PARENTID));

        if ($userinfo) {
            $userflag->set_source_table('viaassign_user_flags',
            array('viaassign' => backup::VAR_PARENTID));

            $submission->set_source_table('viaassign_submission',
                array('viaassignid' => backup::VAR_PARENTID));

            $grade->set_source_table('viaassign_grades',
            array('viaassign' => backup::VAR_PARENTID));

            // Support 1 type of subplugins.
            $this->add_subplugin_structure('viaassignfeedback', $grade, true);
        }

        // Define id annotations.
        $userflag->annotate_ids('user', 'userid');
        $submission->annotate_ids('user', 'userid');
        $submission->annotate_ids('group', 'groupid');
        $grade->annotate_ids('user', 'userid');
        $grade->annotate_ids('user', 'grader');

        // Define file annotations.
        // This file area hasn't itemid.
        $viaassign->annotate_files('mod_viaassign', 'intro', null);

        // Return the root element (choice), wrapped into standard activity structure.

        return $this->prepare_activity_structure($viaassign);
    }
}