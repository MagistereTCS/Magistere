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
 * Delete confirmation of custom view.
 */

defined('MOODLE_INTERNAL') || die;
require_once($CFG->libdir.'/formslib.php');

class deletecustomview_form extends moodleform {
    /**
     * The form definition.
     */
    public function definition() {
        $params = new \stdClass();
        $mform = $this->_form;
        $viewId = $this->_customdata['id'];
        $viewName = $this->_customdata['name'];

        $params->viewname = $viewName;
        $mform->addElement('static', 'view_name', '', get_string('delete_confirm', 'local_metaadmin', $params));
        $mform->addElement('hidden', 'id', $viewId);
        $mform->setType('id', PARAM_INT);

        $objs = array();
        $objs[] =& $mform->createElement('submit', 'deleteyes', get_string('view_yes', 'local_metaadmin'));
        $objs[] =& $mform->createElement('submit', 'deleteno', get_string('view_no', 'local_metaadmin'));
        $grp =& $mform->addElement('group', 'viewbuttonsgrp', get_string('view_buttongrp', 'local_metaadmin'), $objs, array(' ', '<br />'), false);
    }
}