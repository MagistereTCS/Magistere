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
 * This file contains the definition for the grading table which subclassses easy_table
 *
 * @package   mod_viaassign
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/tablelib.php');
require_once($CFG->libdir.'/gradelib.php');
require_once($CFG->dirroot.'/mod/viaassign/locallib.php');


/**
 * Extends table_sql to provide a table of viaassignment submissions
 *
 * @package   mod_viaassign
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class viaassign_grading_table extends table_sql implements renderable {
    /** @var viaassign $viaassignment */
    private $viaassignment = null;
    /** @var int $perpage */
    private $perpage = 10;
    /** @var int $rownum (global index of current row in table) */
    private $rownum = -1;
    /** @var renderer_base for getting output */
    private $output = null;
    /** @var stdClass gradinginfo */
    private $gradinginfo = null;
    /** @var int $tablemaxrows */
    private $tablemaxrows = 10000;
    /** @var boolean $quickgrading */
    private $quickgrading = false;
    /** @var boolean $hasgrantextension - Only do the capability check once for the entire table */
    private $hasgrantextension = false;
    /** @var boolean $hasgrade - Only do the capability check once for the entire table */
    private $hasgrade = false;
    /** @var array $plugincache - A cache of plugin lookups to match a column name to a plugin efficiently */
    private $plugincache = array();
    /** @var array $scale - A list of the keys and descriptions for the custom scale */
    private $scale = null;
    /** @var string $plugingradingbatchoperations - List of plugin supported batch operations */
    public $plugingradingbatchoperations = array();

    /**
     * overridden constructor keeps a reference to the viaassignment class that is displaying this table
     *
     * @param viaassign $viaassignment The viaassignment class
     * @param int $perpage how many per page
     * @param string $filter The current filter
     * @param int $rowoffset For showing a subsequent page of results
     * @param bool $quickgrading Is this table wrapped in a quickgrading form?
     * @param string $downloadfilename
     */
    public function __construct(viaassign $viaassignment,
                                $perpage,
                                $filter,
                                $rowoffset,
                                $quickgrading,
                                $downloadfilename = null) {
        global $CFG, $PAGE, $DB, $USER;
        parent::__construct('mod_viaassign_grading');
        $this->viaassignment = $viaassignment;

        // Check permissions up front.
        $this->hasgrantextension = has_capability('mod/viaassign:grantextension', $this->viaassignment->get_context());

        if ($this->viaassignment->get_instance()->grade != '0') {
            $this->hasgrade = $this->viaassignment->can_grade();
        } else {
            $this->hasgrade = false;
        }

        foreach ($viaassignment->get_feedback_plugins() as $plugin) {
            if ($plugin->is_visible() && $plugin->is_enabled()) {
                foreach ($plugin->get_grading_batch_operations() as $action => $description) {
                    if (empty($this->plugingradingbatchoperations)) {
                        $this->plugingradingbatchoperations[$plugin->get_type()] = array();
                    }
                    $this->plugingradingbatchoperations[$plugin->get_type()][$action] = $description;
                }
            }
        }

        $this->perpage = $perpage;
        $this->quickgrading = $quickgrading && $this->hasgrade;
        $this->output = $PAGE->get_renderer('mod_viaassign');

        $urlparams = array('action' => 'grading',
                            'id' => $viaassignment->get_course_module()->id,
                            'viaid' => optional_param('viaid', '0', PARAM_INT));
        $url = new moodle_url($CFG->wwwroot . '/mod/viaassign/view.php', $urlparams);
        $this->define_baseurl($url);

        // Do some business - then set the sql.
        $currentgroup = groups_get_activity_group($viaassignment->get_course_module(), true);

        if ($rowoffset) {
            $this->rownum = $rowoffset - 1;
        }

        $groupingid = $viaassignment->get_course_module()->groupingid;

 //       if ($CFG->version > 2014111012) {
 //           $info = $viaassignment->get_course_module()->availability;
 //           $structure = json_decode($info);

////            if (isset($structure) && $structure->op != "!&") {
 //               if ($structure->c[0]->type == "grouping") {
 //                   $groupingid = $structure->c[0]->id;
 //               } elseif ($structure->c[0]->type == "group") {
	//				$currentgroup = $structure->c[0]->id;
 //                   }
 //           }
 //       }
        // We need to get the users with the correct roles/depending on the role selected when the delegated activity was created.
        $users = array_keys( $viaassignment->list_participants($currentgroup, true, $groupingid));
        if (count($users) == 0) {
            // Insert a record that will never match to the sql is still valid.
            $users[] = -1;
        }

        $params = array();
        $params['viaassignmentid1'] = (int)$this->viaassignment->get_instance()->id;
        $params['viaassignmentid2'] = (int)$this->viaassignment->get_instance()->id;
        $params['viaassignmentid3'] = (int)$this->viaassignment->get_instance()->id;

        $extrauserfields = get_extra_user_fields($this->viaassignment->get_context());
        $fields = user_picture::fields('u', $extrauserfields) . ', ';

        if ($CFG->dbtype == "mssql" || $CFG->dbtype == "sqlsrv") {
            $fields = 'id = ROW_NUMBER() OVER (ORDER BY u.id), ';
        } else if ($CFG->dbtype == "mysqli" || $CFG->dbtype == 'mysql') {
            $fields .= ' @curRow := @curRow + 1 AS id, ';
        }
        $fields .= 'u.id as userid, ';
        $fields .= 's.status as status, ';
        $fields .= 's.id as submissionid, ';
        $fields .= 's.timecreated as firstsubmission, ';
        $fields .= 's.timemodified as timesubmitted, ';
        $fields .= 's.viaid as viaidsumbitted, ';
        $fields .= 'v.id as viaid, ';
        $fields .= 'v.course as viacourse, ';
        $fields .= 'v.name as vianame, ';
        $fields .= 'v.datebegin as viadate, ';
        $fields .= 'v.duration as viaduration, ';
        $fields .= 'g.id as gradeid, ';
        $fields .= 'g.grade as grade, ';
        $fields .= 'g.timemodified as timemarked, ';
        $fields .= 'g.timecreated as firstmarked, ';
        $fields .= 'uf.mailed as mailed, ';
        $fields .= 'uf.locked as locked, ';
        $fields .= 'uf.extensionduedate as extensionduedate ';

        $from = '{user} u
                         LEFT JOIN {viaassign_submission} s ON
                            u.id = s.userid AND
                            s.viaassignid = :viaassignmentid1
                         LEFT JOIN {via} v ON
                            v.id = s.viaid
                         LEFT JOIN {viaassign_grades} g ON
                            u.id = g.userid AND
                            g.viaassign = :viaassignmentid2 AND
                            s.viaid = g.viaid
                         LEFT JOIN {viaassign_user_flags} uf ON
                            u.id = uf.userid AND uf.viaassign = :viaassignmentid3';

        if ($CFG->dbtype == "mysqli") {
            $from .= ' JOIN (SELECT @curRow := 0) r';
        }

        $userparams = array();
        $userindex = 0;

        list($userwhere, $userparams) = $DB->get_in_or_equal($users, SQL_PARAMS_NAMED, 'user');
        $where = 'u.id ' . $userwhere;
        $params = array_merge($params, $userparams);

        if ($filter == VIAASSIGN_FILTER_SUBMITTED) {
            $where .= ' AND (s.timemodified IS NOT NULL AND
                                 s.status = :created) ';
            $params['created'] = VIAASSIGN_SUBMISSION_STATUS_CREATED;
        } else if ($filter == VIAASSIGN_FILTER_NOT_SUBMITTED) {
            $where .= ' AND (s.timemodified IS NULL OR s.status IS NULL) ';
        } else if ($filter == VIAASSIGN_FILTER_REQUIRE_GRADING) {
            $where .= ' AND (s.timemodified IS NOT NULL AND
                                 s.status = :created AND
                                 (s.timemodified > g.timemodified OR g.timemodified IS NULL))';
            $params['created'] = VIAASSIGN_SUBMISSION_STATUS_CREATED;
        } else if (strpos($filter, VIAASSIGN_FILTER_SINGLE_USER) === 0) {
            $userfilter = (int) array_pop(explode('=', $filter));
            $where .= ' AND (u.id = :userid)';
            $params['userid'] = $userfilter;
        }

        if ( $CFG->version >= 2015051108 &&($CFG->dbtype == "mssql" || $CFG->dbtype == "sqlsrv")) {
             $where .= " ORDER BY 1";
        }
        $this->set_sql($fields, $from, $where, $params);

        $columns = array();
        $headers = array();

        // Select.
        $columns[] = 'select';
        $headers[] = get_string('select') .
            '<div class="selectall"><label class="accesshide" for="selectall">' . get_string('selectall') . '</label>
             <input type="checkbox" id="selectall" name="selectall" title="' . get_string('selectall') . '"/></div>';

        // User picture.
        $columns[] = 'picture';
        $headers[] = '';

        // Fullname.
        $columns[] = 'fullname';
        $headers[] = get_string('fullname');

        // Via Title.
        $columns[] = 'name';
        $headers[] = get_string('title', 'viaassign');

        // Via Title.
        $columns[] = 'datebegin';
        $headers[] = get_string('time', 'viaassign');

        // Submission status.
        $columns[] = 'status';
        $headers[] = get_string('status', 'viaassign');

        // Grade.
        if ($this->hasgrade) {
            $columns[] = 'grade';
            $headers[] = get_string('grade');
        }

        // Feedback plugins.
        foreach ($this->viaassignment->get_feedback_plugins() as $plugin) {
            if ($plugin->is_visible() && $plugin->is_enabled() && $plugin->has_user_summary()) {
                $index = 'plugin' . count($this->plugincache);
                $this->plugincache[$index] = array($plugin);
                $columns[] = $index;
                $headers[] = $plugin->get_name();
            }
        }

        // Load the grading info for all users.
        $this->gradinginfo = grade_get_grades($this->viaassignment->get_course()->id,
                                              'mod',
                                              'viaassign',
                                              $this->viaassignment->get_instance()->id,
                                              $users);

        $columns[] = 'edit';
        $headers[] = get_string('actionsheader', 'viaassign');

        // Set the columns.
        $this->define_columns($columns);
        $this->define_headers($headers);
        foreach ($extrauserfields as $extrafield) {
             $this->column_class($extrafield, $extrafield);
        }
        // We require at least one unique column for the sort.
        $this->sortable(true, 'userid');
        $this->no_sorting('edit');
        $this->no_sorting('select');

        $plugincolumnindex = 0;

        foreach ($this->viaassignment->get_feedback_plugins() as $plugin) {
            if ($plugin->is_visible() && $plugin->is_enabled() && $plugin->has_user_summary()) {
                $feedbackpluginindex = 'plugin' . $plugincolumnindex++;
                $this->no_sorting($feedbackpluginindex);
            }
        }
    }

    /**
     * Before adding each row to the table make sure rownum is incremented.
     *
     * @param array $row row of data from db used to make one row of the table.
     * @return array one row for the table
     */
    public function format_row($row) {
        if ($this->rownum < 0) {
            $this->rownum = $this->currpage * $this->pagesize;
        } else {
            $this->rownum += 1;
        }

        return parent::format_row($row);
    }

    /**
     * Add the userid to the row class so it can be updated via ajax.
     *
     * @param stdClass $row The row of data
     * @return string The row class
     */
    public function get_row_class($row) {
        return 'user' . $row->userid;
    }

    /**
     * Return the number of rows to display on a single page.
     *
     * @return int The number of rows per page
     */
    public function get_rows_per_page() {
        return $this->perpage;
    }

    /**
     * For download only - list all the valid options for this custom scale.
     *
     * @param stdClass $row - The row of data
     * @return string A list of valid options for the current scale
     */
    public function col_scale($row) {
        global $DB;

        if (empty($this->scale)) {
            $dbparams = array('id' => -($this->viaassignment->get_instance()->grade));
            $this->scale = $DB->get_record('scale', $dbparams);
        }

        if (!empty($this->scale->scale)) {
            return implode("\n", explode(',', $this->scale->scale));
        }
        return '';
    }

    /**
     * Display a grade with scales etc.
     *
     * @param string $grade
     * @param boolean $editable
     * @param int $userid The user id of the user this grade belongs to
     * @param int $modified Timestamp showing when the grade was last modified
     * @return string The formatted grade
     */
    public function display_grade($grade, $editable, $userid, $modified) {
        return $this->viaassignment->display_grade($grade, $editable, $userid, $modified);
    }

    /**
     * Format a user picture for display.
     *
     * @param stdClass $row
     * @return string
     */
    public function col_picture(stdClass $row) {
        global  $CFG, $DB;

        $row->id = $row->userid;
        //if ($CFG->version >= 2016052301) {
        if (isset($headerinfo['user'])) {
            $user = $headerinfo['user'];
        } else {
            // Look up the user information if it is not supplied.
            $user = $DB->get_record('user', array('id' => $row->id));
        }
        return $this->output->user_picture($user);
       /* } else {
            return $this->output->user_picture($row);
        }*/
    }

    /**
     * Format a user record for display (link to profile).
     *
     * @param stdClass $row
     * @return string
     */
    public function col_fullname($row) {
        global $DB;
        $row->id = $row->userid;
        if ($this->viaassignment->fullname($row)) {
            $fullname = $this->viaassignment->fullname($row);
        } else {
            $tempuser = $DB->get_record('user', array('id' => $row->userid));
            $fullname = fullname($tempuser, true);
        }

        if (!$this->viaassignment->is_active_user($row->id)) {
            $suspendedstring = get_string('userenrolmentsuspended', 'grades');
            $fullname .= ' ' . html_writer::empty_tag('img', array('src' => $this->output->image_url('i/enrolmentsuspended'),
                'title' => $suspendedstring, 'alt' => $suspendedstring, 'class' => 'usersuspendedicon'));
            $fullname = html_writer::tag('span', $fullname, array('class' => 'usersuspended'));
        }
        return $fullname;
    }

    public function col_name($row) {
        if ($row->viaid) {
            $link = html_writer::link(new moodle_url('../../mod/via/view.php',
                    array('viaid' => $row->viaid)), $row->vianame, array('title' => s($row->vianame)));
        } else {
            $link = '--';
        }

        return $link;
    }

    public function col_datebegin($row) {
        if ($row->viaid) {
            $string = userdate($row->viadate) . ', <br/>'. $row->viaduration;
        } else {
            $string = '--';
        }

        return $string;
    }

    /**
     * Insert a checkbox for selecting the current row for batch operations.
     *
     * @param stdClass $row
     * @return string
     */
    public function col_select(stdClass $row) {
        $row->id = $row->userid;
        $selectcol = '<label class="accesshide" for="selectuser_' . $row->userid . '">';
        $selectcol .= get_string('selectuser', 'assign', $row->userid);
        $selectcol .= '</label>';
        $selectcol .= '<input type="checkbox"
                              id="selectuser_' . $row->userid . '"
                              name="selectedusers"
                              value="' . $row->userid . '"/>';
        $selectcol .= '<input type="hidden"
                              name="grademodified_' . $row->userid . '"
                              value="' . $row->timemarked . '"/>';
        $selectcol .= '<input type="hidden"
                              name="viamodified_' . $row->userid . '"
                              value="' . $row->viaid . '"/>';
        return $selectcol;
    }

    /**
     * Return a users grades from the listing of all grade data for this viaassignment.
     *
     * @param int $userid
     * @return mixed stdClass or false
     */
    private function get_gradebook_data_for_user($userid) {
        if (isset($this->gradinginfo->items[0]) && $this->gradinginfo->items[0]->grades[$userid]) {
            return $this->gradinginfo->items[0]->grades[$userid];
        }
        return false;
    }

    /**
     * Format a column of data for display.
     *
     * @param stdClass $row
     * @return string
     */
    public function col_grade($row) {
        global $DB;

        if ($this->hasgrade) {
            $link = '';
            $separator = $this->output->spacer(array(), true);
            $grade = '';
            $gradingdisabled = $this->viaassignment->grading_disabled($row->id);
            $cm = $this->viaassignment->get_course_module();

            $name = $this->viaassignment->fullname($row);

            $viaid = isset($row->viaid) ? $row->viaid : 0;

            $icon = $this->output->pix_icon('gradefeedback', get_string('gradeuser', 'viaassign', $name), 'mod_viaassign');
            $urlparams = array('id' => $cm->id,
                                'rownum' => $this->rownum,
                                'viaid' => $viaid,
                                'userid' => $row->userid,
                                'action' => 'grade');

            $url = new moodle_url('/mod/viaassign/view.php', $urlparams);
            $link = $this->output->action_link($url, $icon);

            $grade .= $link . $separator;

            if ($row->grade) {
                $grade .= $this->display_grade($row->grade,
                                                $this->quickgrading && !$gradingdisabled,
                                                $row->userid,
                                                $row->timemarked);
            } else {
                // We double check if it's not 0!
                $validate = $DB->get_record('viaassign_grades',
                                            array('viaassign' => $cm->instance, 'userid' => $row->userid, 'viaid' => 0));
                if ($validate) {
                    if ($viaid != 0) {
                        $validate->viaid = $viaid;
                        $result = $DB->update_record('viaassign_grades', $validate);
                    }
                    $grade .= $this->display_grade($validate->grade,
                                                    $this->quickgrading && !$gradingdisabled,
                                                    $row->userid,
                                                    $validate->timemodified);
                }
            }

            return $grade;
        } else {
            return '';
        }
    }

    /**
     * Format a column of data for display
     *
     * @param stdClass $row
     * @return string
     */
    public function col_status(stdClass $row) {
        $o = '';

        $instance = $this->viaassignment->get_instance();

        if (isset($row->status) && $row->status == VIAASSIGN_SUBMISSION_STATUS_CREATED) {
            // If created it can be in the future, now or in the past...
            $now = time();
            if ($row->viadate > $now) {
                $o .= $this->output->container(get_string('submissionstatus_future', 'viaassign'),
                    array('class' => 'submissionstatus' . $row->status));
            } else if ($row->viadate < $now && ($row->viadate + ($row->viaduration * 60)) > $now) {
                $o .= $this->output->container(get_string('submissionstatus_now', 'viaassign'),
                     array('class' => 'submissionstatus' . $row->status));
            } else {
                $o .= $this->output->container(get_string('submissionstatus_done', 'viaassign'),
                    array('class' => 'submissionstatus' . $row->status));
            }
        } else {
            $o .= $this->output->container(get_string('submissionstatus_', 'viaassign'),
                array('class' => 'submissionstatus' . $row->status));
        }

        if ($instance->duedate && $row->timesubmitted > $instance->duedate) {
            if (!$row->extensionduedate ||$row->timesubmitted > $row->extensionduedate) {
                $usertime = format_time($row->timesubmitted - $instance->duedate);
                $latemessage = get_string('submittedlateshort',
                                            'viaassign',
                                            $usertime);
                $o .= $this->output->container($latemessage, 'latesubmission');
            }
        }
        if ($row->locked) {
            $lockedstr = get_string('submissionslockedshort', 'viaassign');
            $o .= $this->output->container($lockedstr, 'lockedsubmission');
        }

        if (!$row->timesubmitted) {
            $now = time();
            $due = $instance->duedate;
            if ($row->extensionduedate) {
                $due = $row->extensionduedate;
            }
            if ($due && ($now > $due)) {
                $overduestr = get_string('overdue', 'viaassign', format_time($now - $due));
                $o .= $this->output->container($overduestr, 'overduesubmission');
            }
        }
        if ($row->extensionduedate) {
            $userdate = userdate($row->extensionduedate);
            $extensionstr = get_string('userextensiondate', 'viaassign', $userdate);
            $o .= $this->output->container($extensionstr, 'extensiondate');
        }

        return $o;
    }

    /**
     * Format a column of data for display.
     *
     * @param stdClass $row
     * @return string
     */
    public function col_edit(stdClass $row) {
        global $USER;

        $edit = '';

        $actions = array();
        $noimage = null;

        if ($this->hasgrade) {
            // Here we want to be able to delete an activity or give an extension.
            $viaid = isset($row->viaid) ? $row->viaid : 0;
            $urlparams = array('id' => $this->viaassignment->get_course_module()->id,
                'rownum' => $this->rownum,
                'userid' => $row->id,
                'viaid' => $viaid,
                'action' => 'grade');

            $url = new moodle_url('/mod/viaassign/view.php', $urlparams);

            if (!$row->grade) {
                $description = get_string('grade');
            } else {
                $description = get_string('updategrade', 'viaassign');
            }
            $actions['grade'] = new action_menu_link_secondary($url, $noimage, $description);
        }

        // Everything we need is in the row.
        $submission = $row;
        $flags = $row;

        $caneditsubmission = $this->viaassignment->can_edit_submission($row->id, $USER->id);

        // Hide for offline viaassignments.
        if ($this->viaassignment->is_any_submission_plugin_enabled()) {
            if ($row->status == 'created') {
                if (!$row->locked) {
                    $urlparams = array('id' => $this->viaassignment->get_course_module()->id,
                                        'userid' => $row->id,
                                        'action' => 'lock',
                                        'sesskey' => sesskey(),
                                        'page' => $this->currpage);
                    $url = new moodle_url('/mod/viaassign/view.php', $urlparams);

                    $description = get_string('preventsubmissionsshort', 'viaassign');
                    $actions['lock'] = new action_menu_link_secondary($url, $noimage, $description);
                } else {
                    $urlparams = array('id' => $this->viaassignment->get_course_module()->id,
                                        'userid' => $row->id,
                                        'action' => 'unlock',
                                        'sesskey' => sesskey(),
                                        'page' => $this->currpage);
                    $url = new moodle_url('/mod/viaassign/view.php', $urlparams);
                    $description = get_string('allowsubmissionsshort', 'viaassign');
                    $actions['unlock'] = new action_menu_link_secondary($url, $noimage, $description);
                }
            }

            if ($this->viaassignment->get_instance()->duedate && $row->status != 'created' && $this->hasgrantextension) {
                $urlparams = array('id' => $this->viaassignment->get_course_module()->id,
                                    'userid' => $row->id,
                                    'action' => 'grantextension',
                                    'sesskey' => sesskey(),
                                    'page' => $this->currpage);
                $url = new moodle_url('/mod/viaassign/view.php', $urlparams);
                $description = get_string('grantextension', 'viaassign');
                $actions['grantextension'] = new action_menu_link_secondary($url, $noimage, $description);
            }
            // We can only delted if there is something that was submitted/created!
            $context = $this->viaassignment->get_course_context();
            if ($row->status == 'created' && $caneditsubmission && has_capability('mod/viaassign:deleteothers', $context)) {
                $urlparams = array('id' => $this->viaassignment->get_course_module()->id,
                                    'action' => 'confirm_delete_via',
                                    'sesskey' => sesskey(),
                                    'page' => $this->currpage,
                                    'userid' => $row->id,
                                    'viaid' => $row->viaid,
                                    'rownum' => $this->rownum);
                $url = new moodle_url('/mod/viaassign/view.php', $urlparams);
                $description = get_string('deletesubmission', 'viaassign');
                $actions['deletesubmission'] = new action_menu_link_secondary($url, $noimage, $description);
            }
        }

        $totalsubmissions = $row->status == VIAASSIGN_SUBMISSION_STATUS_CREATED;

        $hasattempts = $totalsubmissions < $this->viaassignment->get_instance()->maxactivities;

        $menu = new action_menu();
        $menu->set_owner_selector('.gradingtable-actionmenu');
        $menu->set_alignment(action_menu::TL, action_menu::BL);
        $menu->set_constraint('.gradingtable > .no-overflow');
        $menu->set_menu_trigger(get_string('editaction', 'viaassign'));
        foreach ($actions as $action) {
            $menu->add($action);
        }

        // Prioritise the menu ahead of all other actions.
        $menu->prioritise = true;

        $edit .= $this->output->render($menu);

        return $edit;
    }
    /**
     * Write the plugin summary with an optional link to view the full feedback/submission.
     *
     * @param viaassign_plugin $plugin Submission plugin or feedback plugin
     * @param stdClass $item Submission or grade
     * @param string $returnaction The return action to pass to the
     *                             view_submission page (the current page)
     * @param string $returnparams The return params to pass to the view_submission
     *                             page (the current page)
     * @return string The summary with an optional link
     */
    private function format_plugin_summary_with_link(viaassign_plugin $plugin,
                                                     stdClass $item,
                                                     $returnaction,
                                                     $returnparams) {
        $link = '';
        $showviewlink = false;

        $summary = $plugin->view_summary($item, $showviewlink);
        $separator = '';
        if ($showviewlink) {
            $viewstr = get_string('view' . substr($plugin->get_subtype(), strlen('viaassign')), 'viaassign');
            $icon = $this->output->pix_icon('t/preview', $viewstr);
            $urlparams = array('id' => $this->viaassignment->get_course_module()->id,
                                'sid' => $item->id,
                                'gid' => $item->id,
                                'plugin' => $plugin->get_type(),
                                'action' => 'viewplugin' . $plugin->get_subtype(),
                                'returnaction' => $returnaction,
                                'returnparams' => http_build_query($returnparams));
            $url = new moodle_url('/mod/viaassign/view.php', $urlparams);
            $link = $this->output->action_link($url, $icon);
            $separator = $this->output->spacer(array(), true);
        }

        return $link . $separator . $summary;
    }

    /**
     * Format the submission and feedback columns.
     *
     * @param string $colname The column name
     * @param stdClass $row The submission row
     * @return mixed string or NULL
     */
    public function other_cols($colname, $row) {
        global $DB;
        // For extra user fields the result is already in $row.
        if (empty($this->plugincache[$colname])) {
            return $row->$colname;
        }

        // This must be a plugin field.
        $plugincache = $this->plugincache[$colname];

        $plugin = $plugincache[0];

        if (isset($plugincache[1])) {
            $field = $plugincache[1];
        }

        if ($plugin->get_subtype() == 'viaassignfeedback') {
            $grade = null;

            if ($row->gradeid) {
                $grade = new stdClass();
                $grade->id = $row->gradeid;
                $grade->timecreated = $row->firstmarked;
                $grade->timemodified = $row->timemarked;
                $grade->viaassign = $this->viaassignment->get_instance()->id;
                $grade->userid = $row->userid;
                $grade->grade = $row->grade;
                $grade->mailed = $row->mailed;
                $grade->viaid = $row->viaid;
            }

            if ($this->quickgrading && $plugin->supports_quickgrading()) {
                if (!$grade) {
                    $grade = $DB->get_record('viaassign_grades', array(
                    'userid' => $row->userid,
                    'viaassign' => $this->viaassignment->get_course_module()->instance,
                    'viaid' => 0));
                }
                return $plugin->get_quickgrading_html($row->userid, $grade);
            } else {
                $button = '';
                $name = $this->viaassignment->fullname($row);
                $viaid = isset($row->viaid) ? $row->viaid : 0;
                $icon = $this->output->pix_icon('gradefeedback', get_string('feedbackuser', 'viaassign', $name), 'mod_viaassign');
                $urlparams = array('id' => $this->viaassignment->get_course_module()->id,
                    'rownum' => $this->rownum,
                    'viaid' => $viaid,
                    'userid' => $row->userid,
                    'action' => 'feedback');

                $url = new moodle_url('/mod/viaassign/view.php', $urlparams);
                $link = $this->output->action_link($url, $icon);
                $separator = $this->output->spacer(array(), true);
                $button .= $link . $separator;

                if ($grade) {
                    $button .= $this->format_plugin_summary_with_link($plugin,
                        $grade,
                        'grading',
                        array());
                } else {
                    $grade = $DB->get_record('viaassign_grades', array(
                        'userid' => $row->userid,
                        'viaassign' => $this->viaassignment->get_course_module()->instance,
                        'viaid' => 0));
                    if ($grade) {
                        $button .= $this->format_plugin_summary_with_link($plugin,
                            $grade,
                            'grading',
                            array());
                    }
                }
            }
            return $button;
        }

        return '';
    }

    /**
     * Using the current filtering and sorting - load all rows and return a single column from them.
     *
     * @param string $columnname The name of the raw column data
     * @return array of data
     */
    public function get_column_data($columnname) {
        $this->setup();
        $this->currpage = 0;
        $this->query_db($this->tablemaxrows);
        $result = array();
        foreach ($this->rawdata as $row) {
            $result[] = $row->$columnname;
        }
        return $result;
    }

    /**
     * Return things to the renderer.
     *
     * @return string the viaassignment name
     */
    public function get_viaassignment_name() {
        return $this->viaassignment->get_instance()->name;
    }

    /**
     * Return things to the renderer.
     *
     * @return int the course module id
     */
    public function get_course_module_id() {
        return $this->viaassignment->get_course_module()->id;
    }

    /**
     * Return things to the renderer.
     *
     * @return int the course id
     */
    public function get_course_id() {
        return $this->viaassignment->get_course()->id;
    }

    /**
     * Return things to the renderer.
     *
     * @return stdClass The course context
     */
    public function get_course_context() {
        return $this->viaassignment->get_course_context();
    }

    /**
     * Return things to the renderer.
     *
     * @return bool Does this viaassignment accept submissions
     */
    public function submissions_enabled() {
        return $this->viaassignment->is_any_submission_plugin_enabled();
    }

    /**
     * Return things to the renderer.
     *
     * @return bool Can this user view all grades (the gradebook)
     */
    public function can_view_all_grades() {
        $context = $this->viaassignment->get_course_context();
        return has_capability('gradereport/grader:view', $context) &&
               has_capability('moodle/grade:viewall', $context);
    }

    /**
     * Override the table show_hide_link to not show for select column.
     *
     * @param string $column the column name, index into various names.
     * @param int $index numerical index of the column.
     * @return string HTML fragment.
     */
    protected function show_hide_link($column, $index) {
        if ($index > 0 || !$this->hasgrade) {
            return parent::show_hide_link($column, $index);
        }
        return '';
    }
}