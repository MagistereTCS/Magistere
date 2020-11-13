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
 * Event observers used in local myindex.
 *
 * @package    myindex
 * @copyright  2020 TCS
 * @author     TCS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot.'/blocks/completion_progress/lib.php');

defined('MOODLE_INTERNAL') || die();


/**
 * Event observer for local_myindex.
 */
class local_myindex_observer {

    private static function get_progress_block_config($courseid, $deleted_block_id = null) {
        global $DB;
        
        $block_being_deleted = $deleted_block_id ? "AND bi.id != ?" : "";
        // si on en cours de suppresion d un block, il ne faut pas utiliser celui-ci pour le calcul de la progression
        $record = $DB->get_record_sql("
SELECT configdata
FROM {block_instances} bi
WHERE bi.parentcontextid = (SELECT id FROM {context} cx WHERE cx.contextlevel = 50 AND cx.instanceid = ?)
AND bi.blockname = 'completion_progress' " .$block_being_deleted."
ORDER BY id ASC
LIMIT 1", array($courseid, $deleted_block_id));

        $config = ($record === false || empty($record->configdata) ) ? null : unserialize(base64_decode($record->configdata));
        
        return array($record !== false, $config);
    }
    
    
    private static function compute_progress($courseid, $userid, $config) {
        global $DB;
        
        $exclusions = block_completion_progress_exclusions($courseid);
        $submissions = block_completion_progress_student_submissions($courseid, $userid);
        
        $course = get_course($courseid);
        
        $activities = block_completion_progress_get_activities($courseid, $config);
        $useractivities = block_completion_progress_filter_visibility($activities, $userid, $courseid, $exclusions);
        
        $completions = block_completion_progress_completions($useractivities, $userid, $course, $submissions);
        $progressvalue = block_completion_progress_percentage($useractivities, $completions);
        $DB->execute("INSERT INTO {local_myindex_courseprogress} (courseid, userid, progress)
VALUES (:courseid, :userid, :progressval)
ON DUPLICATE KEY UPDATE progress = :progressvalbis",
            array("courseid"=>$courseid, "userid"=>$userid, "progressval"=>$progressvalue, "progressvalbis" => $progressvalue));
    }
    
    
    private static function delete_courseprogress ($courseid) {
        global $DB;
        $DB->execute("DELETE FROM {local_myindex_courseprogress} WHERE courseid = ?", array($courseid));
    }
    
    private static function update_all_users($courseid) {
        list($hasblock, $config) = self::get_progress_block_config($courseid);
        if ($hasblock) {
            $users = enrol_get_course_users($courseid);
            foreach($users as $user) {
                self::compute_progress($courseid, $user->id, $config);
            }
        }
    }
    
    /**
     * Triggered via course_module_completion_updated event.
     *
     * @param core\event\course_module_completion_updated $event
     */
    public static function course_module_completion_updated(core\event\course_module_completion_updated $event) {
        list($hasblock, $config) = self::get_progress_block_config($event->courseid);
        if ($hasblock) {
            self::compute_progress($event->courseid, $event->userid, $config);
        }
    }
    
    /**
     * Triggered via block_completion_progress\event\instance_deleted event.
     *
     * @param block_completion_progress\event\instance_deleted $event
     */
    public static function block_completion_progress_instance_deleted(block_completion_progress\event\instance_deleted $event) { 
       list($hasblock, $config) = self::get_progress_block_config($event->courseid, $event->other);
       if (!$hasblock) {
           self::delete_courseprogress($event->courseid);
       }
    }
    
    /**
     * Triggered via core\event\course_deleted event.
     *
     * @param core\event\course_deleted $event
     */
    public static function course_deleted(core\event\course_deleted $event) {
        self::delete_courseprogress($event->objectid);
    }
    
    /**
     * Triggered via core\event\course_viewed event.
     *
     * @param core\event\course_viewed $event
     */
    public static function course_viewed(core\event\course_viewed $event) {
        list($hasblock, $config) = self::get_progress_block_config($event->courseid);
        if ($hasblock) {
            self::compute_progress($event->courseid, $event->userid, $config);
        }
    }
    
}
