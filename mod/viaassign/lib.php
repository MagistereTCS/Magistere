<?PHP
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
 * This file contains the moodle hooks for the viaassign module.
 *
 * It delegates most functions to the viaassignment class.
 *
 * @package   mod_viaassign
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Adds an viaassignment instance
 *
 * This is done by calling the add_instance() method of the viaassignment type class
 * @param stdClass $data
 * @param mod_viaassign_mod_form $form
 * @return int The instance id of the new viaassignment
 */
function viaassign_add_instance(stdClass $data, mod_viaassign_mod_form $form = null) {
    global $CFG;
    require_once($CFG->dirroot . '/mod/viaassign/locallib.php');

    $viaassignment = new viaassign(context_module::instance($data->coursemodule), null, null);
    return $viaassignment->add_instance($data, true);
}

/**
 * delete an viaassignment instance
 * @param int $id
 * @return bool
 */
function viaassign_delete_instance($id) {
    global $CFG;
    require_once($CFG->dirroot . '/mod/viaassign/locallib.php');
    $cm = get_coursemodule_from_instance('viaassign', $id, 0, false, MUST_EXIST);
    $context = context_module::instance($cm->id);

    $viaassignment = new viaassign($context, null, null);
    return $viaassignment->delete_instance();
}

/**
 * This function is used by the reset_course_userdata function in moodlelib.
 * This function will remove all viaassignment submissions and feedbacks in the database
 * and clean up any related data.
 *
 * @param stdClass $data the data submitted from the reset course.
 * @return array
 */
function viaassign_reset_userdata($data) {
    global $CFG, $DB;
    require_once($CFG->dirroot . '/mod/viaassign/locallib.php');

    $status = array();
    $params = array('courseid' => $data->courseid);
    $sql = "SELECT a.id FROM {viaassign} a WHERE a.course=:courseid";
    $course = $DB->get_record('course', array('id' => $data->courseid), '*', MUST_EXIST);
    if ($viaassigns = $DB->get_records_sql($sql, $params)) {
        foreach ($viaassigns as $viaassign) {
            $cm = get_coursemodule_from_instance('viaassign',
                                                 $viaassign->id,
                                                 $data->courseid,
                                                 false,
                                                 MUST_EXIST);
            $context = context_module::instance($cm->id);
            $viaassignment = new viaassign($context, $cm, $course);
            $status = array_merge($status, $viaassignment->reset_userdata($data));
        }
    }
    return $status;
}

/**
 * Removes all grades from gradebook
 *
 * @param int $courseid The ID of the course to reset
 * @param string $type Optional type of viaassignment to limit the reset to a particular viaassignment type
 */
function viaassign_reset_gradebook($courseid, $type='') {
    global $CFG, $DB;

    $params = array('moduletype' => 'viaassign', 'courseid' => $courseid);
    $sql = 'SELECT a.*, cm.idnumber as cmidnumber, a.course as courseid
            FROM {viaassign} a, {course_modules} cm, {modules} m
            WHERE m.name=:moduletype AND m.id=cm.module AND cm.instance=a.id AND a.course=:courseid';

    if ($viaassignments = $DB->get_records_sql($sql, $params)) {
        foreach ($viaassignments as $viaassignment) {
            viaassign_grade_item_update($viaassignment, 'reset');
        }
    }
}

/**
 * Implementation of the function for printing the form elements that control
 * whether the course reset functionality affects the viaassignment.
 * @param moodleform $mform form passed by reference
 */
function viaassign_reset_course_form_definition(&$mform) {
    $mform->addElement('header', 'viaassignheader', get_string('modulenameplural', 'viaassign'));
    $name = get_string('deleteallsubmissions', 'viaassign');
    $mform->addElement('advcheckbox', 'reset_viaassign_submissions', $name);
}

/**
 * Course reset form defaults.
 * @param  object $course
 * @return array
 */
function viaassign_reset_course_form_defaults($course) {
    return array('reset_viaassign_submissions' => 1);
}

/**
 * Update an viaassignment instance
 *
 * This is done by calling the update_instance() method of the viaassignment type class
 * @param stdClass $data
 * @param stdClass $form - unused
 * @return object
 */
function viaassign_update_instance(stdClass $data, $form) {
    global $CFG;
    require_once($CFG->dirroot . '/mod/viaassign/locallib.php');
    $context = context_module::instance($data->coursemodule);
    $viaassignment = new viaassign($context, null, null);
    return $viaassignment->update_instance($data);
}

/**
 * Return the list if Moodle features this module supports
 *
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, null if doesn't know
 */
function viaassign_supports($feature) {
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
            return true;
        case FEATURE_GRADE_OUTCOMES:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_ADVANCED_GRADING:
            return false;
        case FEATURE_PLAGIARISM:
            return false;

        default:
            return null;
    }
}

/**
 * Lists all gradable areas for the advanced grading methods gramework
 *
 * @return array('string'=>'string') An array with area names as keys and descriptions as values
 */
function viaassign_grading_areas_list() {
    return array('submissions' => get_string('submissions', 'viaassign'));
}

/**
 * extend an assigment navigation settings
 *
 * @param settings_navigation $settings
 * @param navigation_node $navref
 * @return void
 */
function viaassign_extend_settings_navigation(settings_navigation $settings, navigation_node $navref) {
    global $PAGE, $DB;

    $cm = $PAGE->cm;
    if (!$cm) {
        return;
    }

    $context = $cm->context;
    $course = $PAGE->course;

    if (!$course) {
        return;
    }

    // Link to gradebook.
    if (has_capability('gradereport/grader:view', $cm->context) &&
            has_capability('moodle/grade:viewall', $cm->context)) {
        $link = new moodle_url('/grade/report/grader/index.php', array('id' => $course->id));
        $linkname = get_string('viewgradebook', 'viaassign');
        $node = $navref->add($linkname, $link, navigation_node::TYPE_SETTING);
    }

    // Link to download all submissions.
    if (has_any_capability(array('mod/viaassign:grade', 'mod/viaassign:viewgrades'), $context)) {
        $link = new moodle_url('/mod/viaassign/view.php', array('id' => $cm->id, 'action' => 'grading'));
        $node = $navref->add(get_string('viewgrading', 'viaassign'), $link, navigation_node::TYPE_SETTING);
    }
}

/**
 * Add a get_coursemodule_info function in case any viaassignment type wants to add 'extra' information
 * for the course (see resource).
 *
 * Given a course_module object, this function returns any "extra" information that may be needed
 * when printing this activity in a course listing.  See get_array_of_activities() in course/lib.php.
 *
 * @param stdClass $coursemodule The coursemodule object (record).
 * @return cached_cm_info An object on information that the courses
 *                        will know about (most noticeably, an icon).
 */
/*function viaassign_get_coursemodule_info($coursemodule) {
    global $CFG, $DB;

    $dbparams = array('id' => $coursemodule->instance);

    $fields = 'id, name, intro, introformat';
    if (! $viaassignment = $DB->get_record('viaassign', $dbparams, $fields)) {
        return false;
    }

    $info = new cached_cm_info();
    if ($coursemodule->showdescription) {
        // Convert intro to html. Do not filter cached version, filters run at display time.
        $info->content = format_module_intro('viaassign', $viaassignment, $coursemodule->instance, false);
    }

    $info->name = $viaassignment->name;

    return $info;
}*/

/**
 * Return a list of page types
 * @param string $pagetype current page type
 * @param stdClass $parentcontext Block's parent context
 * @param stdClass $currentcontext Current context of block
 */
function viaassign_page_type_list($pagetype, $parentcontext, $currentcontext) {
    $modulepagetype = array(
        'mod-viaassign-*' => get_string('page-mod-viaassign-x', 'viaassign'),
        'mod-viaassign-view' => get_string('page-mod-viaassign-view', 'viaassign'),
    );
    return $modulepagetype;
}

/**
 * Print an overview of all viaassignments
 * for the courses.
 *
 * @param mixed $courses The list of courses to print the overview for
 * @param array $htmlarray The array of html to return
 */
function viaassign_print_overview($courses, &$htmlarray) {
    global $USER, $CFG, $DB;

    if (empty($courses) || !is_array($courses) || count($courses) == 0) {
        return array();
    }

    if (!$viaassignments = get_all_instances_in_courses('viaassign', $courses)) {
        return;
    }

    $viaassignmentids = array();

    // Do viaassignment_base::isopen() here without loading the whole thing for speed.
    foreach ($viaassignments as $key => $viaassignment) {
        $time = time();
        $isopen = false;
        if ($viaassignment->duedate) {
            if ($viaassignment->duedate) {
                $isopen = ($viaassignment->allowsubmissionsfromdate <= $time && $time <= $viaassignment->duedate);
            } else {
                $isopen = ($viaassignment->allowsubmissionsfromdate <= $time);
            }
        }
        if ($isopen) {
            $viaassignmentids[] = $viaassignment->id;
        }
    }

    if (empty($viaassignmentids)) {
        // No viaassignments to look at - we're done.
        return true;
    }

    // Definitely something to print, now include the constants we need.
    require_once($CFG->dirroot . '/mod/viaassign/locallib.php');

    $strduedate = get_string('duedate', 'viaassign');
    $strnolatesubmissions = get_string('nolatesubmissions', 'viaassign');
    $strduedateno = get_string('duedateno', 'viaassign');
    $strduedateno = get_string('duedateno', 'viaassign');
    $strgraded = get_string('graded', 'viaassign');
    $strnotgradedyet = get_string('notgradedyet', 'viaassign');
    $strnotsubmittedyet = get_string('notsubmittedyet', 'viaassign');
    $strsubmitted = get_string('submitted', 'viaassign');
    $strviaassignment = get_string('modulename', 'viaassign');
    $strreviewed = get_string('reviewed', 'viaassign');

    // We do all possible database work here *outside* of the loop to ensure this scales.
    list($sqlviaassignmentids, $viaassignmentidparams) = $DB->get_in_or_equal($viaassignmentids);

    foreach ($viaassignments as $viaassignment) {
        // Do not show viaassignments that are not open.
        if (!in_array($viaassignment->id, $viaassignmentids)) {
            continue;
        }
        $dimmedclass = '';
        if (!$viaassignment->visible) {
            $dimmedclass = ' class="dimmed"';
        }
        $href = $CFG->wwwroot . '/mod/viaassign/view.php?id=' . $viaassignment->coursemodule;
        $str = '<div class="viaassign overview">' .
               '<div class="name">' .
               $strviaassignment . ': '.
               '<a ' . $dimmedclass .
                   'title="' . $strviaassignment . '" ' .
                   'href="' . $href . '">' .
               format_string($viaassignment->name) .
               '</a></div>';
        if ($viaassignment->duedate) {
            $userdate = userdate($viaassignment->duedate);
            $str .= '<div class="info">' . $strduedate . ': ' . $userdate . '</div>';
        } else {
            $str .= '<div class="info">' . $strduedateno . '</div>';
        }

        $context = context_module::instance($viaassignment->coursemodule);

        $str .= '</div>';
        if (empty($htmlarray[$viaassignment->course]['viaassign'])) {
            $htmlarray[$viaassignment->course]['viaassign'] = $str;
        } else {
            $htmlarray[$viaassignment->course]['viaassign'] .= $str;
        }
    }
}

/**
 * Print recent activity from all viaassignments in a given course
 *
 * This is used by the recent activity block
 * @param mixed $course the course to print activity for
 * @param bool $viewfullnames boolean to determine whether to show full names or not
 * @param int $timestart the time the rendering started
 * @return bool true if activity was printed, false otherwise.
 */
function viaassign_print_recent_activity($course, $viewfullnames, $timestart) {
    global $CFG, $USER, $DB, $OUTPUT;

    // Do not use log table if possible, it may be huge.

    $dbparams = array($timestart, $course->id, 'viaassign');
    $namefields = user_picture::fields('u', null, 'userid');
    if (!$submissions = $DB->get_records_sql("SELECT asb.id, asb.timemodified, cm.id AS cmid,
                                                     $namefields
                                                FROM {viaassign_submission} asb
                                                     JOIN {viaassign} a      ON a.id = asb.viaassignid
                                                     JOIN {course_modules} cm ON cm.instance = a.id
                                                     JOIN {modules} md        ON md.id = cm.module
                                                     JOIN {user} u            ON u.id = asb.userid
                                               WHERE asb.timemodified > ? AND
                                                     a.course = ? AND
                                                     md.name = ?
                                            ORDER BY asb.timemodified ASC", $dbparams)) {
         return false;
    }

    $modinfo = get_fast_modinfo($course);
    $show    = array();
    $grader  = array();

    $showrecentsubmissions = get_config('viaassign', 'showrecentsubmissions');

    foreach ($submissions as $submission) {
        if (!array_key_exists($submission->cmid, $modinfo->get_cms())) {
            continue;
        }
        $cm = $modinfo->get_cm($submission->cmid);
        if (!$cm->uservisible) {
            continue;
        }
        if ($submission->userid == $USER->id) {
            $show[] = $submission;
            continue;
        }

        $context = context_module::instance($submission->cmid);
        // The act of submitting of viaassignment may be considered private -
        // only graders will see it if specified.
        if (empty($showrecentsubmissions)) {
            if (!array_key_exists($cm->id, $grader)) {
                $grader[$cm->id] = has_capability('moodle/grade:viewall', $context);
            }
            if (!$grader[$cm->id]) {
                continue;
            }
        }

        $groupmode = groups_get_activity_groupmode($cm, $course);

        if ($groupmode == SEPARATEGROUPS &&
                !has_capability('moodle/site:accessallgroups',  $context)) {
            if (isguestuser()) {
                // Shortcut - guest user does not belong into any group.
                continue;
            }
            $groupingid = $cm->groupingid;

            if ($CFG->version > 2014111012) {
                $info = $cm->availabilityconditionsjson;
                $structure = json_decode($info);

                if (isset($structure)&& isset($structure->c[0]) && $structure->op != "!&") {
                    if ($structure->c[0]->type == "grouping") {
                        $groupingid = $structure->c[0]->id;
                    }
                }
            }
            // This will be slow - show only users that share group with me in this cm.
            if (!$modinfo->get_groups($groupingid)) {
                continue;
            }
                $usersgroups = groups_get_all_groups($course->id, $submission->userid, $groupingid);
            if (is_array($usersgroups)) {
                $usersgroups = array_keys($usersgroups);
                    $intersect = array_intersect($usersgroups, $modinfo->get_groups($groupingid));
                if (empty($intersect)) {
                    continue;
                }
            }
        }
        $show[] = $submission;
    }

    if (empty($show)) {
        return false;
    }

    echo $OUTPUT->heading(get_string('newsubmissions', 'viaassign').':', 2);

    foreach ($show as $submission) {
        $cm = $modinfo->get_cm($submission->cmid);
        $link = $CFG->wwwroot.'/mod/viaassign/view.php?id='.$cm->id;
        print_recent_activity_note($submission->timemodified,
                                   $submission,
                                   $cm->name,
                                   $link,
                                   false,
                                   $viewfullnames);
    }

    return true;
}

/**
 * Returns all viaassignments since a given time.
 *
 * @param array $activities The activity information is returned in this array
 * @param int $index The current index in the activities array
 * @param int $timestart The earliest activity to show
 * @param int $courseid Limit the search to this course
 * @param int $cmid The course module id
 * @param int $userid Optional user id
 * @param int $groupid Optional group id
 * @return void
 */
function viaassign_get_recent_mod_activity(&$activities,
                                        &$index,
                                        $timestart,
                                        $courseid,
                                        $cmid,
                                        $userid=0,
                                        $groupid=0) {
    global $CFG, $COURSE, $USER, $DB;

    if ($COURSE->id == $courseid) {
        $course = $COURSE;
    } else {
        $course = $DB->get_record('course', array('id' => $courseid));
    }

    $modinfo = get_fast_modinfo($course);

    $cm = $modinfo->get_cm($cmid);
    $params = array();
    if ($userid) {
        $userselect = 'AND u.id = :userid';
        $params['userid'] = $userid;
    } else {
        $userselect = '';
    }

    if ($groupid) {
        $groupselect = 'AND gm.groupid = :groupid';
        $groupjoin   = 'JOIN {groups_members} gm ON  gm.userid=u.id';
        $params['groupid'] = $groupid;
    } else {
        $groupselect = '';
        $groupjoin   = '';
    }

    $params['cminstance'] = $cm->instance;
    $params['timestart'] = $timestart;

    $userfields = user_picture::fields('u', null, 'userid');

    if (!$submissions = $DB->get_records_sql('SELECT asb.id, asb.timemodified, ' .
                                                     $userfields .
                                             '  FROM {viaassign_submission} asb
                                                JOIN {viaassign} a ON a.id = asb.viaassignid
                                                JOIN {user} u ON u.id = asb.userid ' .
                                          $groupjoin .
                                            '  WHERE asb.timemodified > :timestart AND
                                                     a.id = :cminstance
                                                     ' . $userselect . ' ' . $groupselect .
                                            ' ORDER BY asb.timemodified ASC', $params)) {
         return;
    }

    $groupmode       = groups_get_activity_groupmode($cm, $course);
    $cmcontext      = context_module::instance($cm->id);
    $grader          = has_capability('moodle/grade:viewall', $cmcontext);
    $accessallgroups = has_capability('moodle/site:accessallgroups', $cmcontext);
    $viewfullnames   = has_capability('moodle/site:viewfullnames', $cmcontext);

    $showrecentsubmissions = get_config('viaassign', 'showrecentsubmissions');
    $show = array();
    foreach ($submissions as $submission) {
        if ($submission->userid == $USER->id) {
            $show[] = $submission;
            continue;
        }
        // The act of submitting of viaassignment may be considered private -
        // only graders will see it if specified.
        if (empty($showrecentsubmissions)) {
            if (!$grader) {
                continue;
            }
        }

        if ($groupmode == SEPARATEGROUPS and !$accessallgroups) {
            if (isguestuser()) {
                // Shortcut - guest user does not belong into any group.
                continue;
            }
            $groupingid = $cm->groupingid;

            if ($CFG->version > 2014111012) {
                $info = $cm->availabilityconditionsjson;
                $structure = json_decode($info);

                if (isset($structure)&& isset($structure->c[0]) && $structure->op != "!&") {
                    if ($structure->c[0]->type == "grouping") {
                        $groupingid = $structure->c[0]->id;
                    }
                }
            }
            // This will be slow - show only users that share group with me in this cm.
            if (!$modinfo->get_groups($groupingid)) {
            continue;
            }
                $usersgroups = groups_get_all_groups($course->id, $submission->userid, $groupingid);
            if (is_array($usersgroups)) {
                $usersgroups = array_keys($usersgroups);
                    $intersect = array_intersect($usersgroups, $modinfo->get_groups($groupingid));
                if (empty($intersect)) {
                    continue;
                }
            }
        }
        $show[] = $submission;
    }

    if (empty($show)) {
        return;
    }

    if ($grader) {
        require_once($CFG->libdir.'/gradelib.php');
        $userids = array();
        foreach ($show as $id => $submission) {
            $userids[] = $submission->userid;
        }
        $grades = grade_get_grades($courseid, 'mod', 'viaassign', $cm->instance, $userids);
    }

    $aname = format_string($cm->name, true);
    foreach ($show as $submission) {
        $activity = new stdClass();

        $activity->type         = 'viaassign';
        $activity->cmid         = $cm->id;
        $activity->name         = $aname;
        $activity->sectionnum   = $cm->sectionnum;
        $activity->timestamp    = $submission->timemodified;
        $activity->user         = new stdClass();
        if ($grader) {
            $activity->grade = $grades->items[0]->grades[$submission->userid]->str_long_grade;
        }

        $userfields = explode(',', user_picture::fields());
        foreach ($userfields as $userfield) {
            if ($userfield == 'id') {
                // Aliased in SQL above.
                $activity->user->{$userfield} = $submission->userid;
            } else {
                $activity->user->{$userfield} = $submission->{$userfield};
            }
        }
        $activity->user->fullname = fullname($submission, $viewfullnames);

        $activities[$index++] = $activity;
    }

    return;
}

/**
 * Print recent activity from all viaassignments in a given course
 *
 * This is used by course/recent.php
 * @param stdClass $activity
 * @param int $courseid
 * @param bool $detail
 * @param array $modnames
 */
function viaassign_print_recent_mod_activity($activity, $courseid, $detail, $modnames) {
    global $CFG, $OUTPUT;

    echo '<table border="0" cellpadding="3" cellspacing="0" class="viaassignment-recent">';

    echo '<tr><td class="userpicture" valign="top">';
    echo $OUTPUT->user_picture($activity->user);
    echo '</td><td>';

    if ($detail) {
        $modname = $modnames[$activity->type];
        echo '<div class="title">';
        echo '<img src="' . $OUTPUT->image_url('icon', 'viaassign') . '" '.
             'class="icon" alt="' . $modname . '">';
        echo '<a href="' . $CFG->wwwroot . '/mod/viaassign/view.php?id=' . $activity->cmid . '">';
        echo $activity->name;
        echo '</a>';
        echo '</div>';
    }

    if (isset($activity->grade)) {
        echo '<div class="grade">';
        echo get_string('grade').': ';
        echo $activity->grade;
        echo '</div>';
    }

    echo '<div class="user">';
    echo "<a href=\"$CFG->wwwroot/user/view.php?id={$activity->user->id}&amp;course=$courseid\">";
    echo "{$activity->user->fullname}</a>  - " . userdate($activity->timestamp);
    echo '</div>';

    echo '</td></tr></table>';
}

/**
 * Checks if a scale is being used by an viaassignment.
 *
 * This is used by the backup code to decide whether to back up a scale
 * @param int $viaassignmentid
 * @param int $scaleid
 * @return boolean True if the scale is used by the viaassignment
 */
function viaassign_scale_used($viaassignmentid, $scaleid) {
    global $DB;

    $return = false;
    $rec = $DB->get_record('viaassign', array('id' => $viaassignmentid, 'grade' => -$scaleid));

    if (!empty($rec) && !empty($scaleid)) {
        $return = true;
    }

    return $return;
}

/**
 * Checks if scale is being used by any instance of viaassignment
 *
 * This is used to find out if scale used anywhere
 * @param int $scaleid
 * @return boolean True if the scale is used by any viaassignment
 */
function viaassign_scale_used_anywhere($scaleid) {
    global $DB;

    if ($scaleid and $DB->record_exists('viaassign', array('grade' => -$scaleid))) {
        return true;
    } else {
        return false;
    }
}

/**
 * List the actions that correspond to a view of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 *       crud = 'r' and edulevel = LEVEL_PARTICIPATING will
 *       be considered as view action.
 *
 * @return array
 */
function viaassign_get_view_actions() {
    return array('view submission', 'view feedback');
}

/**
 * List the actions that correspond to a post of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 *       crud = ('c' || 'u' || 'd') and edulevel = LEVEL_PARTICIPATING
 *       will be considered as post action.
 *
 * @return array
 */
function viaassign_get_post_actions() {
    return array('upload', 'submit', 'submit for grading');
}

/**
 * Call cron on the viaassign module.
 */
function viaassign_cron() {
    global $CFG;

    require_once($CFG->dirroot . '/mod/viaassign/locallib.php');
    viaassign::cron();
}

/**
 * Returns all other capabilities used by this module.
 * @return array Array of capability strings
 */
function viaassign_get_extra_capabilities() {
    return array('gradereport/grader:view',
                 'moodle/grade:viewall',
                 'moodle/site:viewfullnames',
                 'moodle/site:config');
}

/**
 * Create grade item for given viaassignment.
 *
 * @param stdClass $viaassign record with extra cmidnumber
 * @param array $grades optional array/object of grade(s); 'reset' means reset grades in gradebook
 * @return int 0 if ok, error code otherwise
 */
function viaassign_grade_item_update($viaassign, $grades=null) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    if (!isset($viaassign->courseid)) {
        $viaassign->courseid = $viaassign->course;
    }

    $params = array('itemname' => $viaassign->name, 'idnumber' => $viaassign->cmidnumber);

    // Check if feedback plugin for gradebook is enabled, if yes then
    // gradetype = GRADE_TYPE_TEXT else GRADE_TYPE_NONE.
    $gradefeedbackenabled = false;

    if (isset($viaassign->gradefeedbackenabled)) {
        $gradefeedbackenabled = $viaassign->gradefeedbackenabled;
    } else if ($viaassign->grade == 0) { // Grade feedback is needed only when grade == 0.
        $mod = get_coursemodule_from_instance('viaassign', $viaassign->id, $viaassign->courseid);
        $cm = context_module::instance($mod->id);
        $viaassignment = new viaassign($cm, null, null);
        $gradefeedbackenabled = $viaassignment->is_gradebook_feedback_enabled();
    }

    if ($viaassign->grade > 0) {
        $params['gradetype'] = GRADE_TYPE_VALUE;
        $params['grademax']  = $viaassign->grade;
        $params['grademin']  = 0;
    } else if ($viaassign->grade < 0) {
        $params['gradetype'] = GRADE_TYPE_SCALE;
        $params['scaleid']   = -$viaassign->grade;
    } else if ($gradefeedbackenabled) {
        // Viaassign->grade : 0 and feedback enabled.
        $params['gradetype'] = GRADE_TYPE_TEXT;
    } else {
        // Viaassign->grade : 0 and no feedback enabled.
        $params['gradetype'] = GRADE_TYPE_NONE;
    }

    if ($grades === 'reset') {
        $params['reset'] = true;
        $grades = null;
    }

    return grade_update('mod/viaassign',
                        $viaassign->courseid,
                        'mod',
                        'viaassign',
                        $viaassign->id,
                        0,
                        $grades,
                        $params);
}

/**
 * Return grade for given user or all users.
 *
 * @param stdClass $viaassign record of viaassign with an additional cmidnumber
 * @param int $userid optional user id, 0 means all users
 * @return array array of grades, false if none
 */
function viaassign_get_user_grades($viaassign, $userid=0) {
    global $CFG;

    require_once($CFG->dirroot . '/mod/viaassign/locallib.php');

    $cm = get_coursemodule_from_instance('viaassign', $viaassign->id, 0, false, MUST_EXIST);
    $context = context_module::instance($cm->id);
    $viaassignment = new viaassign($context, null, null);
    $viaassignment->set_instance($viaassign);
    return $viaassignment->get_user_grades_for_gradebook($userid);
}

/**
 * Update activity grades.
 *
 * @param stdClass $viaassign database record
 * @param int $userid specific user only, 0 means all
 * @param bool $nullifnone - not used
 */
function viaassign_update_grades($viaassign, $userid=0, $nullifnone=true) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    if ($viaassign->grade == 0) {
        viaassign_grade_item_update($viaassign);
    } else if ($grades = viaassign_get_user_grades($viaassign, $userid)) {
        foreach ($grades as $k => $v) {
            if ($v->rawgrade == -1) {
                $grades[$k]->rawgrade = null;
            }
        }
        viaassign_grade_item_update($viaassign, $grades);
    } else {
        viaassign_grade_item_update($viaassign);
    }
}

/**
 * List the file areas that can be browsed.
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @return array
 */
function viaassign_get_file_areas($course, $cm, $context) {
    global $CFG;
    require_once($CFG->dirroot . '/mod/viaassign/locallib.php');
    $areas = array();

    $viaassignment = new viaassign($context, $cm, $course);
    foreach ($viaassignment->get_submission_plugins() as $plugin) {
        if ($plugin->is_visible()) {
            $pluginareas = $plugin->get_file_areas();

            if ($pluginareas) {
                $areas = array_merge($areas, $pluginareas);
            }
        }
    }
    foreach ($viaassignment->get_feedback_plugins() as $plugin) {
        if ($plugin->is_visible()) {
            $pluginareas = $plugin->get_file_areas();

            if ($pluginareas) {
                $areas = array_merge($areas, $pluginareas);
            }
        }
    }

    return $areas;
}

/**
 * File browsing support for viaassign module.
 *
 * @param file_browser $browser
 * @param object $areas
 * @param object $course
 * @param object $cm
 * @param object $context
 * @param string $filearea
 * @param int $itemid
 * @param string $filepath
 * @param string $filename
 * @return object file_info instance or null if not found
 */
function viaassign_get_file_info($browser,
                              $areas,
                              $course,
                              $cm,
                              $context,
                              $filearea,
                              $itemid,
                              $filepath,
                              $filename) {
    global $CFG;
    require_once($CFG->dirroot . '/mod/viaassign/locallib.php');

    if ($context->contextlevel != CONTEXT_MODULE) {
        return null;
    }

    $fs = get_file_storage();
    $filepath = is_null($filepath) ? '/' : $filepath;
    $filename = is_null($filename) ? '.' : $filename;

    // Need to find the plugin this belongs to.
    $viaassignment = new viaassign($context, $cm, $course);
    $pluginowner = null;
    foreach ($viaassignment->get_submission_plugins() as $plugin) {
        if ($plugin->is_visible()) {
            $pluginareas = $plugin->get_file_areas();

            if (array_key_exists($filearea, $pluginareas)) {
                $pluginowner = $plugin;
                break;
            }
        }
    }
    if (!$pluginowner) {
        foreach ($viaassignment->get_feedback_plugins() as $plugin) {
            if ($plugin->is_visible()) {
                $pluginareas = $plugin->get_file_areas();

                if (array_key_exists($filearea, $pluginareas)) {
                    $pluginowner = $plugin;
                    break;
                }
            }
        }
    }

    if (!$pluginowner) {
        return null;
    }

    $result = $pluginowner->get_file_info($browser, $filearea, $itemid, $filepath, $filename);
    return $result;
}

/**
 * Prints the complete info about a user's interaction with an viaassignment.
 *
 * @param stdClass $course
 * @param stdClass $user
 * @param stdClass $coursemodule
 * @param stdClass $viaassign the database viaassign record
 *
 * This prints the submission summary and feedback summary for this student.
 */
function viaassign_user_complete($course, $user, $coursemodule, $viaassign) {
    global $CFG;
    require_once($CFG->dirroot . '/mod/viaassign/locallib.php');

    $context = context_module::instance($coursemodule->id);

    $viaassignment = new viaassign($context, $coursemodule, $course);

    echo $viaassignment->view_student_summary($user, false);
}

/**
 * Print the grade information for the viaassignment for this user.
 *
 * @param stdClass $course
 * @param stdClass $user
 * @param stdClass $coursemodule
 * @param stdClass $viaassignment
 */
function viaassign_user_outline($course, $user, $coursemodule, $viaassignment) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');
    require_once($CFG->dirroot.'/grade/grading/lib.php');

    $gradinginfo = grade_get_grades($course->id,
                                        'mod',
                                        'viaassign',
                                        $viaassignment->id,
                                        $user->id);

    $gradingitem = $gradinginfo->items[0];
    $gradebookgrade = $gradingitem->grades[$user->id];

    if (empty($gradebookgrade->str_long_grade)) {
        return null;
    }
    $result = new stdClass();
    $result->info = get_string('outlinegrade', 'viaassign', $gradebookgrade->str_long_grade);
    $result->time = $gradebookgrade->dategraded;

    return $result;
}

/**
 * Obtains the automatic completion state for this module based on any conditions
 * in viaassign settings.
 *
 * @param object $course Course
 * @param object $cm Course-module
 * @param int $userid User ID
 * @param bool $type Type of comparison (or/and; can be used as return value if no conditions)
 * @return bool True if completed, false if not, $type if conditions not set.
 */
function viaassign_get_completion_state($course, $cm, $userid, $type) {
    global $CFG, $DB;
    require_once($CFG->dirroot . '/mod/viaassign/locallib.php');

    $viaassign = new viaassign(null, $cm, $course);

    // If completion option is enabled, evaluate it and return true/false.
    if ($viaassign->get_instance()->completionsubmit) {
        $dbparams = array('viaassignid' => $viaassign->get_instance()->id, 'userid' => $userid);
        $submission = $DB->get_record('viaassign_submission', $dbparams, '*', IGNORE_MISSING);
        return $submission && $submission->status == VIAASSIGN_SUBMISSION_STATUS_CREATED;
    } else {
        // Completion option is not enabled so just return $type.
        return $type;
    }
}