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

namespace local_centralizedresources\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
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
            'cr_contributor',
            [
                'firstname' => 'privacy:metadata:firstname',
                'lastname' => 'privacy:metadata:lastname',
                'email' => 'privacy:metadata:email',
                'numen' => 'privacy:metadata:numen',
                'academie' => 'privacy:metadata:academie',
            ],
            'privacy:metadata:cr_contributor'
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
        global $CFG,$DB;
        $contextlist = new contextlist();

        require_once($CFG->dirroot.'/local/magisterelib/databaseConnection.php');
        $user = $DB->get_record("user",["id"=> $userid]);

        if($user){

            if ($user->auth == 'shibboleth'){
                $contributor = get_centralized_db_connection()->get_record('cr_contributor', array('numen' => $user->username));
            }else{
                $contributor = get_centralized_db_connection()->get_record('cr_contributor', array('email' => $user->email));
            }

            if($contributor !== false){
                $contextlist->add_user_context($userid);
            }
        }


        return $contextlist;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param   userlist    $userlist   The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        global $CFG,$DB;
        $context = $userlist->get_context();
        if ($context instanceof \context_user) {
            require_once($CFG->dirroot.'/local/magisterelib/databaseConnection.php');

            $user = $DB->get_record("user",["id"=> $context->instanceid]);
            
            if ($user->auth == 'shibboleth'){
                $contributor = get_centralized_db_connection()->get_record('cr_contributor', array('numen' => $user->username));
            }else{
                $contributor = get_centralized_db_connection()->get_record('cr_contributor', array('email' => $user->email));
            }

            if($contributor !== false){
                $userlist->add_user($context->instanceid);
            }
        }
    }

    /**
     * Export personal data for the given approved_contextlist. User and context information is contained within the contextlist.
     *
     * @param approved_contextlist $contextlist a list of contexts approved for export.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $CFG;

        $user = $contextlist->get_user();
        $context = \context_user::instance($user->id);

        require_once($CFG->dirroot.'/local/magisterelib/databaseConnection.php');
        
        if ($user->auth == 'shibboleth'){
            $contributors = get_centralized_db_connection()->get_records('cr_contributor', ['numen' => $user->username]);
        }else{
            $contributors = get_centralized_db_connection()->get_records('cr_contributor', ['email' => $user->email]);
        }
        
        // Get the user's contacts.
        if ($contributors) {
            $contributorsdata = [];
            foreach ($contributors as $record) {
                $contributorsdata[] = (object) [
                    'firstname' => $record->firstname,
                    'lastname' => $record->lastname,
                    'email' => $record->email,
                    'numen' => $record->numen,
                    'academie' => $record->academie
                ];
            }
            writer::with_context($context)->export_data([get_string('pluginname', 'local_centralizedresources'),get_string('privacy:metadata:cr_contributor', 'local_centralizedresources')], (object) $contributorsdata);
        }
    }

    protected static function anonymeContributor($user){
        $params = [
            "numen" => $user->username,
            "email" => $user->email,
            "newnumen" => get_config("tool_dataprivacy",'anonymoususername')."_".$user->id,
            "newfirstname" => get_config("tool_dataprivacy",'anonymousfirstname')."_".$user->id,
            "newlastname" => get_config("tool_dataprivacy",'anonymouslastname')."_".$user->id,
            "newemail" => get_config("tool_dataprivacy",'anonymousemail')."_".$user->id."@".get_config("tool_dataprivacy",'anonymousemail').".lan"

        ];
        
        if ($user->auth == 'shibboleth'){
            get_centralized_db_connection()->execute("UPDATE {cr_contributor} SET numen = :newnumen, firstname = :newfirstname, lastname = :newlastname, email = :newemail WHERE  numen = :numen ",$params);
        }else{
            get_centralized_db_connection()->execute("UPDATE {cr_contributor} SET numen = :newnumen, firstname = :newfirstname, lastname = :newlastname, email = :newemail WHERE  email = :email ",$params);
        }

    }
    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context the context to delete in.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {

        global $DB,$CFG;

        if (!$context instanceof \context_user) {
            return;
        }

        require_once($CFG->dirroot.'/local/magisterelib/databaseConnection.php');

        $user = $DB->get_record('user',["id" => $context->instanceid]);
        if($user){
            static::anonymeContributor($user);
        }
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist a list of contexts approved for deletion.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $CFG;

        if (empty($contextlist->count())) {
            return;
        }

        require_once($CFG->dirroot.'/local/magisterelib/databaseConnection.php');
        
        static::anonymeContributor($contextlist->get_user());
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param   approved_userlist       $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {

        global $DB,$CFG;

        $context = $userlist->get_context();

        if (!$context instanceof \context_user) {
            return;
        }

        require_once($CFG->dirroot.'/local/magisterelib/databaseConnection.php');


        $userids = $userlist->get_userids();

        foreach ($userids as $userid) {
            $user = $DB->get_record('user',["id" => $userid]);
            static::anonymeContributor($user);

        }



    }
}
