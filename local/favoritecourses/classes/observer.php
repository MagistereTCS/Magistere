<?php

/**
 * Event observers used in favoritecourses
 *
 * @package local-favoritecourses
 * @author TCS
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


/**
 * Event observer for favoritecourses.
 */
class local_favoritecourses_observer {

    /**
     * Triggered via user_enrolment_deleted event.
     *
     * @param \core\event\user_enrolment_deleted $event
     */
    public static function user_enrolment_deleted(\core\event\user_enrolment_deleted $event) {
        global $DB;

        $courseid = $event->courseid;
        $relateduserid = $event->relateduserid;
        $context = context_course::instance($courseid);
        
        $user_roles = get_user_roles($context, $relateduserid);
        
        $has_higher_role = false;
        foreach ($user_roles as $user_role){
            if (in_array($user_role->shortname, array('administrateurnational', 'administrateurlocal', 'gestionnaire', 'superviseur'))) {
                $has_higher_role = true;
                break;
            }
        }
        
        if (!$has_higher_role) {
            $DB->execute("DELETE FROM {local_favoritecourses} WHERE courseid = ? AND userid= ?", array($courseid, $relateduserid));
        }
        
        
    }
}
