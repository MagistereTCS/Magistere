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
 * This file contains the submission form used by the viaassign module.
 *
 * @package   mod_viaassign
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');


require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/mod/viaassign/locallib.php');

/**
 * Assign submission form
 *
 * @package   mod_viaassign
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_viaassign_submission_form extends moodleform {

    /**
     * Define this form - called by the parent constructor
     */
    public function definition() {
        $mform = $this->_form;

        $data = $this->_customdata['1'];

        // Hidden values!
        $mform->addElement('hidden', 'action', 'deletesubmission');
        $mform->setType('action', PARAM_ALPHA);

        $mform->addElement('hidden', 'id', $data->id);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'submission', $data->submissionid);
        $mform->setType('submission', PARAM_INT);

        $mform->addElement('hidden', 'viaid', $data->viaid);
        $mform->setType('viaid', PARAM_INT);

        $this->add_action_buttons(true, get_string('delete'));
        if ($data) {
            $this->set_data($data);
        }
    }
}