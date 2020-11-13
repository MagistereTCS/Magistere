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

namespace local_supervision\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\deletion_criteria;
use core_privacy\local\request\helper;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

/**
 * Implementation of the privacy subsystem plugin provider .
 */
class provider implements
    // This plugin stores personal data.
    \core_privacy\local\metadata\provider,

    // This plugin is a core_user_data_provider.
    \core_privacy\local\request\plugin\provider,

    // This plugin is capable of determining which users have data within it.
    \core_privacy\local\request\core_userlist_provider {
    /**
     * Return the fields which contain personal data.
     *
     * @param collection $items a reference to the collection to use to store the metadata.
     * @return collection the updated collection of metadata items.
     */
    public static function get_metadata(collection $items) : collection {
        $items->add_database_table(
            'progress_complete',
            [
                'courseid' => 'privacy:metadata:courseid',
                'userid' => 'privacy:metadata:userid',
                'is_complete' => 'privacy:metadata:is_complete'
            ],
            'privacy:metadata:progress_complete'
        );

        return $items;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid the userid.
     * @return contextlist the list of contexts containing user info for the user.
     */
    public static function get_contexts_for_userid(int $userid) : contextlist {

        $sql = "
            SELECT ctx.id FROM {t_user_rne} tur 
            JOIN {user} u ON u.id = :userid
            JOIN {context} ctx ON ctx.instanceid = u.id AND ctx.contextlevel = :userlevel
            WHERE u.username = tur.identifiant";
        $params = [
            'userlevel' => CONTEXT_USER,
            'userid' => $userid,
        ];
        $contextlist = new contextlist();
        $contextlist->add_from_sql($sql, $params);

        $sql = "
               SELECT ctx.id FROM {progress_complete} pc
               JOIN {context} ctx ON ctx.instanceid =  :userid AND ctx.contextlevel = :userlevel
               WHERE pc.userid = ctx.instanceid";
        $params = [
            'userid' => $userid,
            'userlevel' => CONTEXT_USER,
        ];
        $contextlist->add_from_sql($sql, $params);
        return $contextlist;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param   userlist    $userlist   The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {

        $context = $userlist->get_context();

        if ($context instanceof \context_user) {
            // Fetch all choice answers.
            $sql = "
                SELECT u.userid
                FROM {user} u 
                WHERE u.id = :uid";

            $params = [
                'uid'      => $context->instanceid,
            ];

            $userlist->add_from_sql('userid', $sql, $params);


            $sql = "
                SELECT pc.userid
                FROM  {progress_complete} pc
                WHERE pc.userid = :uid";

            $params = [
                'uid'      => $context->instanceid,
            ];

            $userlist->add_from_sql('userid', $sql, $params);

        }

        if ($context instanceof \context_course) {
            // Fetch all choice answers.
            $sql = "
                SELECT pc.userid
                FROM { course } c
                JOIN  {progress_complete} pc ON pc.courseid = c.id
                WHERE c.id = :cid";

            $params = [
                'cid'      => $context->instanceid,
            ];

            $userlist->add_from_sql('userid', $sql, $params);
        }

    }

    /**
     * Export personal data for the given approved_contextlist. User and context information is contained within the contextlist.
     *
     * @param approved_contextlist $contextlist a list of contexts approved for export.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        $userid = $contextlist->get_user()->id;
        $username = $contextlist->get_user()->username;
        $context = \context_user::instance($userid);

        $sql = '
            SELECT u.username AS identifiant, uid.data AS code_rne
            FROM {user} u
            INNER JOIN {user_info_data} uid ON (uid.userid = u.id)
            WHERE u.id = :userid 
            AND uid.fieldid = (SELECT id FROM {user_info_field} WHERE shortname = "rne")';
        $t_user_rne = $DB->get_records_sql($sql, ['userid' => $userid]);
        if ($t_user_rne) {
            $t_user_rnedata = [];
            foreach ($t_user_rne as $record) {
                $t_user_rnedata[] = (object) [
                    'identifiant' => $record->identifiant,
                    'code_rne' => $record->code_rne,
                ];
            }
            writer::with_context($context)->export_data([get_string('pluginname', 'local_supervision'),get_string('privacy:metadata:t_user_rne', 'local_supervision')], (object) $t_user_rnedata);
        }

        $progress_complete = $DB->get_records('progress_complete', ['userid' => $userid]);

        if ($progress_complete) {
            $progress_completedata = [];
            foreach ($progress_complete as $record) {
                $progress_completedata[] = (object) [
                    'courseid' => $record->courseid,
                    'userid' => $record->userid,
                    'is_complete' => $record->is_complete
                ];
            }
            writer::with_context($context)->export_data([get_string('pluginname', 'local_supervision'),get_string('privacy:metadata:progress_complete', 'local_supervision')], (object) $progress_completedata);
        }
    }


    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context the context to delete in.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {

        global $DB;

        if (!$context instanceof \context_user) {
            return;
        }

        $user = $DB->get_record('user',["id" => $context->instanceid]);

        if($user){
            $DB->delete_records('progress_complete', ['userid' => $user->id]);
        }

    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist a list of contexts approved for deletion.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $username = $contextlist->get_user()->username;
        $userid = $contextlist->get_user()->id;

        $DB->delete_records('progress_complete', ['userid' => $userid]);

    }

    /**
     * Delete multiple users within a single context.
     *
     * @param   approved_userlist       $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {

        global $DB;

        $context = $userlist->get_context();

        if (!$context instanceof \context_user) {
            return;
        }
        $userids = $userlist->get_userids();

        foreach ($userids as $userid) {
            $user = $DB->get_record('user',["id" => $userid]);

            $DB->delete_records('progress_complete', ['userid' => $user->id]);
        }

    }
}
