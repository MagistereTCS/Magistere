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
 * Library of functions and constants for module etherpadlite
 * This file should have two well differenced parts:
 *   - All the core Moodle functions, neeeded to allow
 *     the module to work integrated in Moodle.
 *   - All the etherpadlite specific functions, needed
 *     to implement all the module logic. Please, note
 *     that, if the module become complex and this lib
 *     grows a lot, it's HIGHLY recommended to move all
 *     these module specific functions to a new php file,
 *     called "locallib.php" (see forum, quiz...). This will
 *     help to save some memory when Moodle is performing
 *     actions across all modules.
 *
 * @package    mod_etherpadlite
 *
 * @author     Timo Welde <tjwelde@gmail.com>
 * @copyright  2012 Humboldt-Universität zu Berlin <moodle-support@cms.hu-berlin.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param object $etherpadlite An object from the form in mod_form.php
 * @return int The id of the newly inserted etherpadlite record
 */
function etherpadlite_add_instance(stdClass $etherpadlite, mod_etherpadlite_mod_form $mform = null) {

    global $DB;
    $config = get_config("etherpadlite");

    $instance = new \mod_etherpadlite\client($config->apikey, $config->url.'api');

    try {
        $groupid = $instance->create_group();
    } catch (Exception $e) {
        // The group already exists or something else went wrong.
        throw $e;
    }

    try {
        $padid = $instance->create_group_pad($groupid, $config->padname);
    } catch (Exception $e) {
        // The pad already exists or something else went wrong.
        throw $e;
    }

    $etherpadlite->uri = $padid;

    $etherpadlite->timecreated = time();

    return $DB->insert_record('etherpadlite', $etherpadlite);
}


/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param object $etherpadlite An object from the form in mod_form.php
 * @return boolean Success/Fail
 */
function etherpadlite_update_instance(stdClass $etherpadlite, mod_etherpadlite_mod_form $mform = null) {
    global $DB;

    $etherpadlite->timemodified = time();
    $etherpadlite->id = $etherpadlite->instance;

    // You may have to add extra stuff in here.
    if (empty($etherpadlite->guestsallowed)) {
        $etherpadlite->guestsallowed = 0;
    }

    return $DB->update_record('etherpadlite', $etherpadlite);
}


/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 */
function etherpadlite_delete_instance($id) {

    global $DB;

    if (! $etherpadlite = $DB->get_record('etherpadlite', array('id' => $id))) {
        return false;
    }

    $result = true;

    $pads = $DB->get_records('etherpadlite',array('uri'=>$etherpadlite->uri));
    
    if (count($pads) < 2)
    {
	    // Delete any dependent records here.
	
	    $config = get_config("etherpadlite");
	    $instance = new \mod_etherpadlite\client($config->apikey, $config->url.'api');
	
	    $padid = $etherpadlite->uri;
	    $groupid = explode('$', $padid);
	    $groupid = $groupid[0];
	
	    $instance->delete_pad($padid);
	    $instance->delete_group($groupid);
	}

    if (! $DB->delete_records('etherpadlite', array('id' => $etherpadlite->id))) {
        $result = false;
    }

    return $result;
}


/**
 * Return a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @return null
 * @todo Finish documenting this function
 */
function etherpadlite_user_outline($course, $user, $mod, $etherpadlite) {
    return $return;
}


/**
 * Print a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * @return boolean
 * @todo Finish documenting this function
 */
function etherpadlite_user_complete($course, $user, $mod, $etherpadlite) {
    return true;
}


/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in etherpadlite activities and print it out.
 * Return true if there was output, or false is there was none.
 *
 * @return boolean
 * @todo Finish documenting this function
 */
function etherpadlite_print_recent_activity($course, $isteacher, $timestart) {
    return false;  // True if anything was printed, otherwise false.
}


/**
 * Function to be run periodically according to the moodle cron
 * This function searches for things that need to be done, such
 * as sending out mail, toggling flags etc ...
 *
 * @return boolean
 * @todo Finish documenting this function
 **/
function etherpadlite_cron () {
    return true;
}


/**
 * Must return an array of user records (all data) who are participants
 * for a given instance of etherpadlite. Must include every user involved
 * in the instance, independient of his role (student, teacher, admin...)
 * See other modules as example.
 *
 * @param int $etherpadliteid ID of an instance of this module
 * @return mixed boolean/array of students
 */
function etherpadlite_get_participants($etherpadliteid) {
    return false;
}


/**
 * Execute post-install custom actions for the module
 * This function was added in 1.9
 *
 * @return boolean true if success, false on error
 */
function etherpadlite_install() {
    return true;
}


/**
 * Execute post-uninstall custom actions for the module
 * This function was added in 1.9
 *
 * @return boolean true if success, false on error
 */
function etherpadlite_uninstall() {
    return true;
}

/**
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, null if doesn't know
 */
function etherpadlite_supports($feature) {
    switch($feature) {
        case FEATURE_GROUPS:
            return true;
        case FEATURE_GROUPINGS:
            return true;
        case FEATURE_GROUPMEMBERSONLY:
            return true;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_COMPLETION_HAS_RULES:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return false;
        case FEATURE_GRADE_OUTCOMES:
            return false;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;

        default:
            return null;
    }
}


// Any other etherpadlite functions go here.  Each of them must have a name that
// starts with etherpadlite_
// Remember (see note in first lines) that, if this section grows, it's HIGHLY
// recommended to move all funcions below to a new "localib.php" file.
// A funtion to generate a random name if something doesn't already exist.
function etherpadlite_gen_random_string() {
    $length = 5;
    $characters = "0123456789";
    $string = "";
    for ($p = 0; $p < $length; $p++) {
        $string .= $characters[mt_rand(0, strlen($characters))];
    }
    return $string;
}

function etherpadlite_guestsallowed($e) {
    global $CFG;

    if (get_config("etherpadlite", "adminguests") == 1) {
        if ($e->guestsallowed) {
            return true;
        }
    }
    return false;
}

function etherpadlite_get_completion_state($course,$cm,$userid,$type) {
    global $CFG,$DB;

    // Get forum details
    if (!($etherpadmodule=$DB->get_record('etherpadlite',array('id'=>$cm->instance)))) {
        throw new Exception("Can't find etherpadlite {$cm->instance}");
    }

    $result=$type; // Default return value

    /*
    if(!$ciepmodule->trackingcourseenabled){
        return $result;
    }
    */

    return true;
}