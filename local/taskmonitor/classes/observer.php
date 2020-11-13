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
 * @package    taskmonitor
 * @copyright  2020 TCS
 * @author     TCS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

//require_once($CFG->dirroot.'/local/taskmonitor/lib.php');

defined('MOODLE_INTERNAL') || die();


/**
 * Event observer for local_taskmonitor.
 */
class local_taskmonitor_observer {


    /**
     * Triggered via local_taskmonitor event.
     *
     * @param local_taskmonitor\event\task_scheduled_completed $event
     */
    public static function task_scheduled_completed(local_taskmonitor\event\task_scheduled_completed $event) {
        $taskevent = new stdClass();
        $taskevent->classname = "\\".$event->other['classname'];
        $taskevent->starttime = $event->other['starttime'];
        $taskevent->runtime = $event->other['runtime'];
        $taskevent->query = $event->other['query'];
        $taskevent->failed = ($event->other['error']==true?1:0);
        $GLOBALS['DB']->insert_record('local_taskmonitor_event', $taskevent);
    }
    
    /**
     * Triggered via local_taskmonitor\event\task_adhoc_completed event.
     *
     * @param local_taskmonitor\event\task_adhoc_completed $event
     */
    public static function task_adhoc_completed(local_taskmonitor\event\task_adhoc_completed $event) { 
        $taskevent = new stdClass();
        $taskevent->classname = "\\".$event->other['classname'];
        $taskevent->starttime = $event->other['starttime'];
        $taskevent->runtime = $event->other['runtime'];
        $taskevent->query = $event->other['query'];
        $taskevent->failed = ($event->other['error']==true?1:0);
        $GLOBALS['DB']->insert_record('local_taskmonitor_event', $taskevent);
    }
    
}
