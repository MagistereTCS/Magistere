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
 * Defines the class for the Message My Teacher block
 *
 * @package    block_messageteacher
 * @author     Mark Johnson <mark.johnson@tauntons.ac.uk>
 * @copyright  2010 onwards Tauntons College, UK
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 *  Class definition for the Message My Teacher block
 */
class block_messageteacher extends block_base {
    public function init() {
        $this->title = get_string('formers', 'block_messageteacher');
    }

    public function applicable_formats() {
          return array('all' => true, 'my' => false);
    }

    /**
     * Gets a list of "teachers" with the defined role, and displays a link to message each
     *
     * @access public
     * @return void
     */
    public function has_config() {
        return true;
    }
    public function get_content() {
        global $COURSE, $CFG, $USER, $DB, $OUTPUT, $PAGE;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->text = '';
        $this->content->footer = '';

        $usegroups = get_config('block_messageteacher', 'groups');
        $coursehasgroups = groups_get_all_groups($COURSE->id);

        $roles = explode(',', get_config('block_messageteacher', 'roles'));
        list($usql, $uparams) = $DB->get_in_or_equal($roles);
        $params = array($COURSE->id, CONTEXT_COURSE);
        $select = 'SELECT DISTINCT u.id, u.firstname, u.lastname, u.firstnamephonetic, u.lastnamephonetic, u.middlename, u.alternatename, u.picture, u.imagealt, u.email ';
        $from = 'FROM {role_assignments} ra
            JOIN {context} AS c ON ra.contextid = c.id
            JOIN {user} AS u ON u.id = ra.userid
        	JOIN {user_enrolments} ue ON ue.userid = u.id ';
        $where = 'WHERE ((c.instanceid = ? AND c.contextlevel = ?)';
        if (get_config('block_messageteacher', 'includecoursecat')) {
            $params = array_merge($params, array($COURSE->category, CONTEXT_COURSECAT));
            $where .= ' OR (c.instanceid = ? AND c.contextlevel = ?))';
        } else {
            $where .= ')';
        }
        $params = array_merge($params, array($USER->id), $uparams);
        
        $currenttime = time();
        $where .= 'AND ra.userid != ? AND ra.roleid '.$usql.' AND ue.enrolid IN (SELECT id FROM {enrol} WHERE courseid='.$COURSE->id.') AND ue.status = 0 AND 
        		(ue.timestart <'.$currenttime.' AND (ue.timeend = 0 OR ue.timeend >'.$currenttime.'))';
        $order = ' ORDER BY u.firstname ASC, u.lastname';
        
        if ($teachers = $DB->get_records_sql($select.$from.$where.$order, $params)) {
            if ($usegroups && $coursehasgroups) {
                try {
                    $groupteachers = array();
                    $usergroupings = groups_get_user_groups($COURSE->id, $USER->id);
                    if (empty($usergroupings)) {
                        throw new Exception('nogroupmembership');
                    } else {
                        foreach ($usergroupings as $usergroups) {
                            if (empty($usergroups)) {
                                throw new Exception('nogroupmembership');
                            } else {
                                foreach ($usergroups as $usergroup) {
                                    foreach ($teachers as $teacher) {
                                        if (groups_is_member($usergroup, $teacher->id)) {
                                            $groupteachers[$teacher->id] = $teacher;
                                        }
                                    }
                                }
                            }
                        }
                        if (empty($groupteachers)) {
                            throw new Exception('nogroupteachers');
                        } else {
                            $teachers = $groupteachers;
                        }
                    }
                } catch (Exception $e) {
                    $this->content->text = get_string($e->getMessage(), 'block_messageteacher');
                    return $this->content;
                }
            }

            $table = '<table>';
            foreach ($teachers as $teacher) {
                $urlparams = array (
                    'courseid' => $COURSE->id,
                    'referurl' => $this->page->url->out(),
                    'recipientid' => $teacher->id
                );
                //$url = new moodle_url('/blocks/messageteacher/message.php', $urlparams);
                $url = new moodle_url('/message/index.php', array('id' => $teacher->id));																				//add  <= QGO03062013
                $picture = '';
                if (get_config('block_messageteacher', 'showuserpictures')) {
                    $picture = new user_picture($teacher);
                    $picture->link = false;
                    $picture->size = 35;
                    $picture = $OUTPUT->render($picture);
                }
                
                $name = fullname($teacher);
                
                $table .= '<tr>';
                $table .= '<td><a href="'.$url.'" class="messageteacher_link">'.$picture.'</a></td>';
                $table .= '<td><a href="'.$url.'" class="messageteacher_link">'.$name.'</a></td></tr>';
            }
            $table .= '</table>';
            
            $this->content->text = $table;
        }

        return $this->content;
    }
}
