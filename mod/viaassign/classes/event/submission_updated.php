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
 * The mod_viaassign submission_updated abstract event.
 *
 * @package    mod_viaassign
 * @copyright  2014 Adrian Greeve <adrian@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_viaassign\event;

defined('MOODLE_INTERNAL') || die();

/**
 * The mod_viaassign submission_updated abstract event class.
 *
 * @property-read array $other {
 *      Extra information about the event.
 *
 *      - int submissionid: ID number of this submission.
 *      - int submissionattempt: Number of attempts made on this submission.
 *      - string submissionstatus: Status of the submission.
 *      - int groupid: (optional) The group ID if this is a teamsubmission.
 *      - string groupname: (optional) The name of the group if this is a teamsubmission.
 * }
 *
 * @package    mod_viaassign
 * @since      Moodle 2.7
 * @copyright  2014 Adrian Greeve <adrian@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class submission_updated extends base {

    /**
     * Init method.
     */
    protected function init() {
        $this->data['objecttable'] = 'viaassign_submission';
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
    }

    /**
     * Returns localised general event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventsubmissionupdated', 'mod_viaassign');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '$this->userid' updated the virtual classroom with id '$this->objectid' " .
            "for the viaassignment with the course module id '$this->contextinstanceid'.";
    }

    /**
     * Custom validation.
     *
     * @throws \coding_exception
     * @return void
     */
    protected function validate_data() {
         parent::validate_data();
        // Make sure this class is never used without proper object details.
        if (empty($this->objectid) || empty($this->objecttable)) {
            throw new \coding_exception('The course_module_viewed event must define objectid and object table.');
        }
    }
}
