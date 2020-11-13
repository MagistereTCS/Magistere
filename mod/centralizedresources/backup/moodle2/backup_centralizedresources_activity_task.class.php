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
 * Defines backup_centralizedresources_activity_task class
 *
 * @package     mod_centralizedresources
 * @category    backup
 * @copyright   TCS
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/mod/centralizedresources/backup/moodle2/backup_centralizedresources_stepslib.php');

/**
 * Provides the steps to perform one complete backup of the centralizedresources instance
 */
class backup_centralizedresources_activity_task extends backup_activity_task {

    /**
     * No specific settings for this activity
     */
    protected function define_my_settings() {
    }

    /**
     * Defines a backup step to store the instance data in the centralizedresources.xml file
     */
    protected function define_my_steps() {
        $this->add_step(new backup_centralizedresources_activity_structure_step('centralizedresources_structure', 'centralizedresources.xml'));
    }

    /**
     * Encodes URLs to the index.php and view.php scripts
     *
     * @param string $content some HTML text that eventually contains URLs to the activity instance scripts
     * @return string the content with the URLs encoded
     */
    static public function encode_content_links($content) {
        global $CFG, $DB;

        $base = preg_quote($CFG->wwwroot,"/");

        // Link to resource view by moduleid.
        $search = "/(".$base."\/mod\/centralizedresources\/view.php\?id\=)([0-9]+)/";

        return preg_replace($search, '$@CENTRALIZEDRESOURCEVIEWBYID*$2@$', $content);
    }
}
