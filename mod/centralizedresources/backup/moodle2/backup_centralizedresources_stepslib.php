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
 * @package    mod
 * @subpackage centralizedresources
 * @copyright  TCS
 */

/**
 * Define all the backup steps that will be used by the backup_centralizedresources_activity_task
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Define the complete centralizedresources structure for backup, with file and id annotations
 */
class backup_centralizedresources_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {
        // To know if we are including userinfo
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated
        $centralizedresources = new backup_nested_element('centralizedresources', array('id'), array(
            'name', 'intro', 'introformat', 'centralizedresourceid', 'display', 'displayoptions', 'timecreated', 'timemodified'));
			
        // Build the tree
        // (love this)

        // Define sources
        $centralizedresources->set_source_table('centralizedresources', array('id' => backup::VAR_ACTIVITYID));

        // Define id annotations
        // (none)

        // Define file annotations
        $centralizedresources->annotate_files('mod_centralizedresources', 'intro', null); // This file area hasn't itemid

        // Return the root element (centralizedresources), wrapped into standard activity structure
        return $this->prepare_activity_structure($centralizedresources);
    }
}
