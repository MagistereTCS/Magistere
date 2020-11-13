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
 * @package moodlecore
 * @subpackage backup-moodle2
 * @copyright 2010 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define all the restore steps that will be used by the restore_url_activity_task
 */

/**
 * Structure step to restore one socialforum activity
 */
class restore_socialforum_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {

        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('socialforum', '/activity/socialforum');
        if ($userinfo) {
            $paths[] = new restore_path_element('socialforum_contribution', '/activity/socialforum/contributions/contribution');
            $paths[] = new restore_path_element('socialforum_favorite', '/activity/socialforum/contributions/contribution/favorites/favorite');
            $paths[] = new restore_path_element('socialforum_popularity', '/activity/socialforum/contributions/contribution/popularities/popularity');
            $paths[] = new restore_path_element('socialforum_subscription', '/activity/socialforum/contributions/contribution/subscriptions/subscription');
        }

        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure($paths);
    }

    protected function process_socialforum($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        // insert the socialforum record
        $newitemid = $DB->insert_record('socialforum', $data);
		
        // immediately after inserting "activity" record, call this
        $this->apply_activity_instance($newitemid);
    }

    protected function process_socialforum_contribution($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        $data->socialforum = $this->get_new_parentid('socialforum');
        $data->timemodified = $this->apply_date_offset($data->timemodified);
        $data->timepublished = $this->apply_date_offset($data->timepublished);
        $data->subject = $this->get_mappingid('socialforum_contribution', $data->subject);
        $data->userid = $this->get_mappingid('user', $data->userid);
        //$data->groupid = $this->get_mappingid('group', $data->groupid);
        $data->usermodified = $this->get_mappingid('user', $data->usermodified);

        $newitemid = $DB->insert_record('sf_contributions', $data);
        $this->set_mapping('socialforum_contribution', $oldid, $newitemid);
    }

    protected function process_socialforum_subscription($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->subject = $this->get_mappingid('socialforum_contribution', $data->subject);
        $data->userid = $this->get_mappingid('user', $data->userid);

        $newitemid = $DB->insert_record('sf_subscriptions', $data);
    }

    protected function process_socialforum_favorite($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->contribution = $this->get_mappingid('socialforum_contribution', $data->contribution);
        $data->userid = $this->get_mappingid('user', $data->userid);

        $newitemid = $DB->insert_record('sf_favorites', $data);
    }

    protected function process_socialforum_popularity($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->contribution = $this->get_mappingid('socialforum_contribution', $data->contribution);
        $data->userid = $this->get_mappingid('user', $data->userid);

        $newitemid = $DB->insert_record('sf_popularities', $data);
    }

    protected function after_execute() {
        // Add socialforum related files, no need to match by itemname (just internally handled context)
        $this->add_related_files('mod_socialforum', 'intro', null);

        $this->add_related_files('mod_socialforum', 'message', 'socialforum_contribution');
        $this->add_related_files('mod_socialforum', 'attachment', 'socialforum_contribution');
    }
	
}
