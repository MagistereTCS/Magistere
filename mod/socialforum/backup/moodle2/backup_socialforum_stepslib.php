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
 * @subpackage socialforum
 * @copyright  TCS
 */

/**
 * Define all the backup steps that will be used by the backup_socialforum_activity_task
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Define the complete socialforum structure for backup, with file and id annotations
 */
class backup_socialforum_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {
        // To know if we are including userinfo
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated
        $socialforum = new backup_nested_element('socialforum', array('id'), array(
            'name', 'intro', 'introformat', 'display', 'displayoptions', 'timecreated', 'timemodified'));

        $contributions = new backup_nested_element('contributions');

        $contribution = new backup_nested_element('contribution', array('id'), array(
            'message', 'messageformat', 'userid', 'groupid',
            'attachment', 'issubject', 'subjecttitle', 'subject', 'timecreated', 'timemodified', 'timepublished', 'usermodified', 'mailed',
            'timemailed'));

        $favorites = new backup_nested_element('favorites');

        $favorite = new backup_nested_element('favorite', array('id'), array(
            'userid', 'contribution', 'timecreated'));

        $popularities = new backup_nested_element('popularities');

        $popularity = new backup_nested_element('popularity', array('id'), array(
            'userid', 'contribution', 'timecreated'));

        $subscriptions = new backup_nested_element('subscriptions');

        $subscription = new backup_nested_element('subscription', array('id'), array(
            'userid', 'subject', 'timecreated'));


        // Build the tree
        $socialforum->add_child($contributions);
        $contributions->add_child($contribution);

        $contribution->add_child($favorites);
        $favorites->add_child($favorite);

        $contribution->add_child($popularities);
        $popularities->add_child($popularity);

        $contribution->add_child($subscriptions);
        $subscriptions->add_child($subscription);

        // (love this)

        // Define sources
        $socialforum->set_source_table('socialforum', array('id' => backup::VAR_ACTIVITYID));
        if ($userinfo) {
            $contribution->set_source_table('sf_contributions', array('socialforum' => backup::VAR_PARENTID));
            $favorite->set_source_table('sf_favorites', array('contribution' => backup::VAR_PARENTID));
            $popularity->set_source_table('sf_popularities', array('contribution' => backup::VAR_PARENTID));
            $subscription->set_source_table('sf_subscriptions', array('subject' => backup::VAR_PARENTID));
        }

        // Define id annotations
        $contribution->annotate_ids('user', 'userid');

        $favorite->annotate_ids('user', 'userid');

        $popularity->annotate_ids('user', 'userid');

        $subscription->annotate_ids('user', 'userid');

        // Define file annotations
        $socialforum->annotate_files('mod_socialforum', 'intro', null); // This file area hasn't itemid

        $contribution->annotate_files('mod_socialforum', 'message', 'id');
        $contribution->annotate_files('mod_socialforum', 'attachment', 'id');

        // Return the root element (socialforum), wrapped into standard activity structure
        return $this->prepare_activity_structure($socialforum);
    }
}
