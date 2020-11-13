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
 * This file contains the definition for the class viaassignment
 *
 * This class provides all the functionality for the new viaassign module.
 *
 * @package   mod_viaassign
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Assignment submission statuses.
define('VIAASSIGN_SUBMISSION_STATUS_REOPENED', 'reopened');
define('VIAASSIGN_SUBMISSION_STATUS_CREATED', 'created');

// Search filters for grading page.
define('VIAASSIGN_FILTER_SUBMITTED', 'submitted');
define('VIAASSIGN_FILTER_NOT_SUBMITTED', 'notsubmitted');
define('VIAASSIGN_FILTER_SINGLE_USER', 'singleuser');
define('VIAASSIGN_FILTER_REQUIRE_GRADING', 'require_grading');

// Marker filter for grading page.
define('VIAASSIGN_MARKER_FILTER_NO_MARKER', -1);

// Reopen attempt methods.
define('VIAASSIGN_ATTEMPT_REOPEN_METHOD_NONE', 'none');
define('VIAASSIGN_ATTEMPT_REOPEN_METHOD_MANUAL', 'manual');
define('VIAASSIGN_ATTEMPT_REOPEN_METHOD_UNTILPASS', 'untilpass');

// Special value means allow unlimited attempts.
define('VIAASSIGN_UNLIMITED_ATTEMPTS', -1);

require_once($CFG->libdir . '/accesslib.php');
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/repository/lib.php');
require_once($CFG->dirroot . '/mod/viaassign/mod_form.php');
require_once($CFG->libdir . '/gradelib.php');
require_once($CFG->dirroot . '/grade/grading/lib.php');
require_once($CFG->dirroot . '/mod/viaassign/feedbackplugin.php');
require_once($CFG->dirroot . '/mod/viaassign/renderable.php');
require_once($CFG->dirroot . '/mod/viaassign/gradingtable.php');
require_once($CFG->libdir . '/eventslib.php');
require_once($CFG->libdir . '/portfolio/caller.php');

/**
 * Standard base class for mod_viaassign (viaassignment types).
 *
 * @package   mod_viaassign
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class viaassign {
    /** @var stdClass the viaassignment record that contains the global settings for this viaassign instance */
    private $instance;

    /** @var stdClass the grade_item record for this viaassign instance's primary grade item. */
    private $gradeitem;

    /** @var context the context of the course module for this viaassign instance
     *               (or just the course if we are creating a new one)
     */
    private $context;

    /** @var stdClass the course this viaassign instance belongs to */
    private $course;

    /** @var stdClass the admin config for all viaassign instances  */
    private $adminconfig;

    /** @var viaassign_renderer the custom renderer for this module */
    private $output;

    /** @var stdClass the course module for this viaassign instance */
    private $coursemodule;

    /** @var array cache for things like the coursemodule name or the scale menu -
     *             only lives for a single request.
     */
    private $cache;

    /** @var array list of the installed submission plugins */
    private $submissionplugins;

    /** @var array list of the installed feedback plugins */
    private $feedbackplugins;

    /** @var string action to be used to return to this page
     *              (without repeating any form submissions etc).
     */
    private $returnaction = 'view';

    /** @var array params to be used to return to this page */
    private $returnparams = array();

    /** @var string modulename prevents excessive calls to get_string */
    private static $modulename = null;

    /** @var string modulenameplural prevents excessive calls to get_string */
    private static $modulenameplural = null;

    /** @var bool whether to exclude users with inactive enrolment */
    private $showonlyactiveenrol = null;

    /** @var array list of suspended user IDs in form of ([id1] => id1) */
    public $susers = null;

    /** @var array cached list of participants for this viaassignment. The cache key will be group, showactive and the context id */
    private $participants = array();

    /**
     * Constructor for the base viaassign class.
     *
     * @param mixed $coursemodulecontext context|null the course module context
     *                                   (or the course context if the coursemodule has not been
     *                                   created yet).
     * @param mixed $coursemodule the current course module if it was already loaded,
     *                            otherwise this class will load one from the context as required.
     * @param mixed $course the current course  if it was already loaded,
     *                      otherwise this class will load one from the context as required.
     */
    public function __construct($coursemodulecontext, $coursemodule, $course) {
        $this->context = $coursemodulecontext;
        $this->coursemodule = $coursemodule;
        $this->course = $course;

        // Temporary cache only lives for a single request - used to reduce db lookups.
        $this->cache = array();

        $this->feedbackplugins = $this->load_plugins('viaassignfeedback');
    }

    /**
     * Set the action and parameters that can be used to return to the current page.
     *
     * @param string $action The action for the current page
     * @param array $params An array of name value pairs which form the parameters
     *                      to return to the current page.
     * @return void
     */
    public function register_return_link($action, $params) {
        global $PAGE;
        $params['action'] = $action;
        $currenturl = $PAGE->url;

        $currenturl->params($params);
        $PAGE->set_url($currenturl);
    }

    /**
     * Return an action that can be used to get back to the current page.
     *
     * @return string action
     */
    public function get_return_action() {
        global $PAGE;

        $params = $PAGE->url->params();

        if (!empty($params['action'])) {
            return $params['action'];
        }
        return '';
    }

    /**
     * Based on the current viaassignment settings should we display the intro.
     *
     * @return bool showintro
     */
    protected function show_intro() {
        if (time() > $this->get_instance()->allowsubmissionsfromdate) {
            return true;
        }
        return false;
    }

    /**
     * Return a list of parameters that can be used to get back to the current page.
     *
     * @return array params
     */
    public function get_return_params() {
        global $PAGE;

        $params = $PAGE->url->params();
        unset($params['id']);
        unset($params['action']);
        return $params;
    }

    /**
     * Set the submitted form data.
     *
     * @param stdClass $data The form data (instance)
     */
    public function set_instance(stdClass $data) {
        $this->instance = $data;
    }

    /**
     * Set the context.
     *
     * @param context $context The new context
     */
    public function set_context(context $context) {
        $this->context = $context;
    }

    /**
     * Set the course data.
     *
     * @param stdClass $course The course data
     */
    public function set_course(stdClass $course) {
        $this->course = $course;
    }

    /**
     * Get list of feedback plugins installed.
     *
     * @return array
     */
    public function get_feedback_plugins() {
        return $this->feedbackplugins;
    }

    /**
     * Get list of submission plugins installed.
     *
     * @return array
     */
    public function get_submission_plugins() {
        return $this->submissionplugins;
    }

    /**
     * Does an viaassignment have submission(s) or grade(s) already?
     *
     * @return bool
     */
    public function has_submissions_or_grades() {
        $allgrades = $this->count_grades();
        $allsubmissions = $this->count_submissions();
        if (($allgrades == 0) && ($allsubmissions == 0)) {
            return false;
        }
        return true;
    }

    /**
     * Get a specific submission plugin by its type.
     *
     * @param string $subtype viaassignfeedback
     * @param string $type
     * @return mixed viaassign_plugin|null
     */
    public function get_plugin_by_type($subtype, $type) {
        $shortsubtype = substr($subtype, strlen('viaassign'));
        $name = $shortsubtype . 'plugins';
        if ($name != 'feedbackplugins') {
            return null;
        }
        $pluginlist = $this->$name;
        foreach ($pluginlist as $plugin) {
            if ($plugin->get_type() == $type) {
                return $plugin;
            }
        }
        return null;
    }

    /**
     * Get a feedback plugin by type.
     *
     * @param string $type - The type of plugin e.g comments
     * @return mixed viaassign_feedback_plugin|null
     */
    public function get_feedback_plugin_by_type($type) {
        return $this->get_plugin_by_type('viaassignfeedback', $type);
    }

    /**
     * Load the plugins from the sub folders under subtype.
     *
     * @param string $subtype - either submission or feedback
     * @return array - The sorted list of plugins
     */
    protected function load_plugins($subtype) {
        global $CFG;
        $result = array();

        $names = core_component::get_plugin_list($subtype);

        foreach ($names as $name => $path) {
            if (file_exists($path . '/locallib.php')) {
                require_once($path . '/locallib.php');

                $shortsubtype = substr($subtype, strlen('viaassign'));
                $pluginclass = 'viaassign_' . $shortsubtype . '_' . $name;

                $plugin = new $pluginclass($this, $name);

                if ($plugin instanceof viaassign_plugin) {
                    $idx = $plugin->get_sort_order();
                    while (array_key_exists($idx, $result)) {
                        $idx += 1;
                    }
                    $result[$idx] = $plugin;
                }
            }
        }
        ksort($result);
        return $result;
    }

    /**
     * Display the viaassignment, used by view.php
     *
     * The viaassignment is displayed differently depending on your role,
     * the settings for the viaassignment and the status of the viaassignment.
     *
     * @param string $action The current action if any.
     * @return string - The page output.
     */
    public function view($action='') {
        $o = '';
        $mform = null;
        $notices = array();
        $nextpageparams = array();

        if (!empty($this->get_course_module()->id)) {
            $nextpageparams['id'] = $this->get_course_module()->id;
        }

        if ($action == 'deletesubmission') {
            $submissionid = optional_param('submissionid', 0, PARAM_INT);
            $viaid = optional_param('viaid', 0, PARAM_INT);
            $this->process_delete_submission($submissionid, $viaid);
            $action = 'redirect';
            $nextpageparams['action'] = 'grading';
        } else if ($action == 'savevia') {
            $this->process_save_via($mform, optional_param('viaid', 0, PARAM_INT));
            $action = 'redirect';
            $nextpageparams['action'] = 'grading';
        } else if ($action == 'lock') {
            $this->process_lock_submission();
            $action = 'redirect';
            $nextpageparams['action'] = 'grading';
        } else if ($action == 'unlock') {
            $this->process_unlock_submission();
            $action = 'redirect';
            $nextpageparams['action'] = 'grading';
        } else if ($action == 'gradingbatchoperation') {
            $action = $this->process_grading_batch_operation($mform);
            if ($action == 'grading') {
                $action = 'redirect';
                $nextpageparams['action'] = 'grading';
            }
        } else if ($action == 'submitgrade') {
            if (optional_param('saveandshownext', null, PARAM_RAW)) {
                // Save and show next.
                $action = 'grade';
                if ($this->process_save_grade($mform)) {
                    $action = 'redirect';
                    $nextpageparams['action'] = 'grade';
                    $nextpageparams['rownum'] = optional_param('rownum', 0, PARAM_INT) + 1;
                    $nextpageparams['useridlistid'] = optional_param('useridlistid', time(), PARAM_INT);
                }
            } else if (optional_param('nosaveandprevious', null, PARAM_RAW)) {
                $action = 'redirect';
                $nextpageparams['action'] = 'grade';
                $nextpageparams['rownum'] = optional_param('rownum', 0, PARAM_INT) - 1;
                $nextpageparams['useridlistid'] = optional_param('useridlistid', time(), PARAM_INT);
            } else if (optional_param('nosaveandnext', null, PARAM_RAW)) {
                $action = 'redirect';
                $nextpageparams['action'] = 'grade';
                $nextpageparams['rownum'] = optional_param('rownum', 0, PARAM_INT) + 1;
                $nextpageparams['useridlistid'] = optional_param('useridlistid', time(), PARAM_INT);
            } else if (optional_param('savegrade', null, PARAM_RAW)) {
                // Save changes button.
                $action = 'grade';
                if ($this->process_save_grade($mform)) {
                    $action = 'redirect';
                    $nextpageparams['action'] = 'savegradingresult';
                }
            } else {
                // Cancel button.
                $action = 'redirect';
                $nextpageparams['action'] = 'grading';
            }
        } else if ($action == 'quickgrade') {
            $message = $this->process_save_quick_grades();
            $action = 'quickgradingresult';
        } else if ($action == 'saveoptions') {
            $this->process_save_grading_options();
            $action = 'redirect';
            $nextpageparams['action'] = 'grading';
        } else if ($action == 'saveextension') {
            if ($this->process_save_extension($mform)) {
                $action = 'redirect';
                $nextpageparams['action'] = 'grading';
            }
        }

        $returnparams = array('rownum' => optional_param('rownum', 0, PARAM_INT),
                                'useridlistid' => optional_param('useridlistid', 0, PARAM_INT),
                                'viaid' => optional_param('viaid', 0, PARAM_INT),
                                'userid' => optional_param('userid', 0, PARAM_INT));
        $this->register_return_link($action, $returnparams);

        // Now show the right view page.
        if ($action == 'redirect') {
            $nextpageurl = new moodle_url('/mod/viaassign/view.php', $nextpageparams);
            redirect($nextpageurl);
            return;
        } else if ($action == 'savegradingresult') {
            $message = get_string('gradingchangessaved', 'viaassign');
            $o .= $this->view_savegrading_result($message);
        } else if ($action == 'quickgradingresult') {
            $mform = null;
            $o .= $this->view_quickgrading_result($message);
        } else if ($action == 'grade') {
            $o .= $this->view_single_grade_page($mform);
        } else if ($action == 'feedback') {
            $o .= $this->view_single_feedback_page($mform);
        } else if ($action == 'grantextension') {
            $o .= $this->view_grant_extension($mform);
        } else if ($action == 'submitvia') {
            $o .= $this->view_submit_via_page($mform);
            $o .= $this->view_footer();
        } else if ($action == 'confirm_delete_via') {
            $o .= $this->view_delete_via_page($returnparams);
        } else if ($action == 'editvia') {
            $o .= $this->view_edit_via_page($returnparams['viaid']);
        } else if ($action == 'viewpluginpage') {
             $o .= $this->view_plugin_page();
        } else if ($action == 'viewcourseindex') {
             $o .= $this->view_course_index();
        } else {
            // Standard view page - depending on the role and capacity different options will be displayed!
            $o .= $this->view_submission_page();
            if (has_capability('mod/viaassign:grade', $this->context)) {
                $o .= $this->view_grading_page();
            }
            $o .= $this->view_footer();
        }

        return $o;
    }

    /**
     * Add this instance to the database.
     *
     * @param stdClass $formdata The data submitted from the form
     * @param bool $callplugins This is used to skip the plugin code
     *             when upgrading an old viaassignment to a new one (the plugins get called manually)
     * @return mixed false if an error occurs or the int id of the new instance
     */
    public function add_instance(stdClass $formdata, $callplugins) {
        global $DB;
        $adminconfig = $this->get_admin_config();

        $err = '';

        // Add the database record.
        $update = new stdClass();
        $update->course = $formdata->course;
        $update->name = $formdata->name;
        $update->intro = $formdata->intro;
        $update->introformat = $formdata->introformat;
        $update->duedate = $formdata->duedate;
        $update->allowsubmissionsfromdate = $formdata->allowsubmissionsfromdate;
        $update->grade = $formdata->grade;
        $update->timemodified = time();
        $update->timecreated = time();
        $update->completionsubmit = !empty($formdata->completionsubmit) ? 1 : 0;
        $update->userrole = implode(',', $formdata->userrole);
        $update->maxactivities = $formdata->maxactivities;
        $update->maxduration = $formdata->maxduration;
        $update->maxusers = $formdata->maxusers;
        $update->recordingmode = $formdata->recordingmode;
        $update->isreplayallowed = $formdata->isreplayallowed;
        $update->waitingroomaccessmode = $formdata->waitingroomaccessmode;
        $update->roomtype = $formdata->roomtype;
        $update->multimediaquality = $formdata->multimediaquality;
        $update->takepresence = $formdata->takepresence;
        if ($formdata->takepresence == 0) {
            $update->minpresence = 0;
        } else {
            $update->minpresence = $formdata->minpresence;
        }
        $update->sendnotifications = $formdata->sendnotifications;
        $update->sendstudentnotifications = $adminconfig->sendstudentnotifications;
        if (isset($formdata->sendstudentnotifications)) {
            $update->sendstudentnotifications = $formdata->sendstudentnotifications;
        }

        $returnid = $DB->insert_record('viaassign', $update);
        $this->instance = $DB->get_record('viaassign', array('id' => $returnid), '*', MUST_EXIST);
        // Cache the course record.
        $this->course = $DB->get_record('course', array('id' => $formdata->course), '*', MUST_EXIST);

        if ($callplugins) {
            foreach ($this->feedbackplugins as $plugin) {
                if (!$this->update_plugin_instance($plugin, $formdata)) {
                    print_error($plugin->get_error());
                    return false;
                }
            }

            // In the case of upgrades the coursemodule has not been set,
            // so we need to wait before calling these two.
            $this->update_calendar($formdata->coursemodule);
            $this->update_gradebook(false, $formdata->coursemodule);
        }

        return $returnid;
    }

    /**
     * Delete all grades from the gradebook for this viaassignment.
     *
     * @return bool
     */
    protected function delete_grades() {
        global $CFG;

        $result = grade_update('mod/viaassign',
                                $this->get_course()->id,
                                'mod',
                                'viaassign',
                                $this->get_instance()->id,
                                0,
                                null,
                                array('deleted' => 1));
        return $result == GRADE_UPDATE_OK;
    }

    /**
     * Delete this instance from the database.
     *
     * @return bool false if an error occurs
     */
    public function delete_instance() {
        global $DB;
        $result = true;
        if (isset($this->submissionplugins)) {
            foreach ($this->submissionplugins as $plugin) {
                if (!$plugin->delete_instance()) {
                    print_error($plugin->get_error());
                    $result = false;
                }
            }
        }
        
        foreach ($this->feedbackplugins as $plugin) {
            if (!$plugin->delete_instance()) {
                print_error($plugin->get_error());
                $result = false;
            }
        }

        // Delete files associated with this viaassignment.
        $fs = get_file_storage();
        if (! $fs->delete_area_files($this->context->id) ) {
            $result = false;
        }

        // Delete_records will throw an exception if it fails - so no need for error checking here.
        $DB->delete_records('viaassign_submission', array('viaassignid' => $this->get_instance()->id));
        $DB->delete_records('viaassign_grades', array('viaassign' => $this->get_instance()->id));
        $DB->delete_records('viaassign_plugin_config', array('viaassign' => $this->get_instance()->id));

        // Delete items from the gradebook.
        if (! $this->delete_grades()) {
            $result = false;
        }

        // Delete the instance.
        $DB->delete_records('viaassign', array('id' => $this->get_instance()->id));

        return $result;
    }

    /**
     * Actual implementation of the reset course functionality, delete all the
     * viaassignment submissions for course $data->courseid.
     *
     * @param stdClass $data the data submitted from the reset course.
     * @return array status array
     */
    public function reset_userdata($data) {
        global $CFG, $DB;

        $componentstr = get_string('modulenameplural', 'viaassign');
        $status = array();

        $fs = get_file_storage();
        if (!empty($data->reset_viaassign_submissions)) {
            // Delete files associated with this viaassignment.
            foreach ($this->submissionplugins as $plugin) {
                $fileareas = array();
                $plugincomponent = $plugin->get_subtype() . '_' . $plugin->get_type();
                $fileareas = $plugin->get_file_areas();
                foreach ($fileareas as $filearea) {
                    $fs->delete_area_files($this->context->id, $plugincomponent, $filearea);
                }

                if (!$plugin->delete_instance()) {
                    $status[] = array('component' => $componentstr,
                                      'item' => get_string('deleteallsubmissions', 'viaassign'),
                                      'error' => $plugin->get_error());
                }
            }

            foreach ($this->feedbackplugins as $plugin) {
                $fileareas = array();
                $plugincomponent = $plugin->get_subtype() . '_' . $plugin->get_type();
                $fileareas = $plugin->get_file_areas();
                foreach ($fileareas as $filearea) {
                    $fs->delete_area_files($this->context->id, $plugincomponent, $filearea);
                }

                if (!$plugin->delete_instance()) {
                    $status[] = array('component' => $componentstr,
                                      'item' => get_string('deleteallsubmissions', 'viaassign'),
                                      'error' => $plugin->get_error());
                }
            }

            $viaassignssql = 'SELECT a.id FROM {viaassign} a
                              WHERE a.course=:course';
            $params = array('course' => $data->courseid);

            $DB->delete_records_select('viaassign_submission', "viaassignid IN ($viaassignssql)", $params);

            $status[] = array('component' => $componentstr,
                              'item' => get_string('deleteallsubmissions', 'viaassign'),
                              'error' => false);

            if (!empty($data->reset_gradebook_grades)) {
                $DB->delete_records_select('viaassign_grades', "viaassign IN ($viaassignssql)", $params);
                // Remove all grades from gradebook.
                require_once($CFG->dirroot.'/mod/viaassign/lib.php');
                viaassign_reset_gradebook($data->courseid);
            }
        }
        // Updating dates - shift may be negative too.
        if ($data->timeshift) {
            shift_course_mod_dates('viaassign',
                    array('duedate', 'allowsubmissionsfromdate'),
                                    $data->timeshift,
                                    $data->courseid, $this->get_instance()->id);
            $status[] = array('component' => $componentstr,
                              'item' => get_string('datechanged'),
                              'error' => false);
        }

        return $status;
    }

    /**
     * Update the settings for a single plugin.
     *
     * @param viaassign_plugin $plugin The plugin to update
     * @param stdClass $formdata The form data
     * @return bool false if an error occurs
     */
    protected function update_plugin_instance(viaassign_plugin $plugin, stdClass $formdata) {
        if ($plugin->is_visible()) {
            $enabledname = $plugin->get_subtype() . '_' . $plugin->get_type() . '_enabled';
            if (!empty($formdata->$enabledname)) {
                $plugin->enable();
                if (!$plugin->save_settings($formdata)) {
                    print_error($plugin->get_error());
                    return false;
                }
            } else {
                $plugin->disable();
            }
        }
        return true;
    }

    /**
     * Update the gradebook information for this viaassignment.
     *
     * @param bool $reset If true, will reset all grades in the gradbook for this viaassignment
     * @param int $coursemoduleid This is required because it might not exist in the database yet
     * @return bool
     */
    public function update_gradebook($reset, $coursemoduleid) {
        global $CFG;

        require_once($CFG->dirroot.'/mod/viaassign/lib.php');
        $viaassign = clone $this->get_instance();
        $viaassign->cmidnumber = $coursemoduleid;

        // Set viaassign gradebook feedback plugin status (enabled and visible).
        $viaassign->gradefeedbackenabled = $this->is_gradebook_feedback_enabled();

        $param = null;
        if ($reset) {
            $param = 'reset';
        }

        return viaassign_grade_item_update($viaassign, $param);
    }

    /**
     * Load and cache the admin config for this module.
     *
     * @return stdClass the plugin config
     */
    public function get_admin_config() {
        if ($this->adminconfig) {
            return $this->adminconfig;
        }
        $this->adminconfig = get_config('viaassign');
        return $this->adminconfig;
    }

    /**
     * Update the calendar entries for this viaassignment.
     *
     * @param int $coursemoduleid - Required to pass this in because it might
     *                              not exist in the database yet.
     * @return bool
     */
    public function update_calendar($coursemoduleid) {
        global $DB, $CFG;
        require_once($CFG->dirroot.'/calendar/lib.php');

        // Special case for add_instance as the coursemodule has not been set yet.
        $instance = $this->get_instance();

        if ($instance->duedate) {
            $event = new stdClass();

            $params = array('modulename' => 'viaassign', 'instance' => $instance->id);
            $event->id = $DB->get_field('event', 'id', $params);
            $event->name = $instance->name;
            $event->timestart = $instance->duedate;

            if ($event->id) {
                $calendarevent = calendar_event::load($event->id);
                $calendarevent->update($event);
            } else {
                unset($event->id);
                $event->courseid    = $instance->course;
                $event->groupid     = 0;
                $event->userid      = 0;
                $event->modulename  = 'viaassign';
                $event->instance    = $instance->id;
                $event->eventtype   = 'due';
                $event->timeduration = 0;
                calendar_event::create($event);
            }
        } else {
            $DB->delete_records('event', array('modulename' => 'viaassign', 'instance' => $instance->id));
        }
    }

    /**
     * Update this instance in the database.
     *
     * @param stdClass $formdata - the data submitted from the form
     * @return bool false if an error occurs
     */
    public function update_instance($formdata) {
        global $DB;
        $adminconfig = $this->get_admin_config();

        $update = new stdClass();
        $update->id = $formdata->instance;
        $update->course = $formdata->course;
        $update->name = $formdata->name;
        $update->intro = $formdata->intro;
        $update->introformat = $formdata->introformat;
        $update->duedate = $formdata->duedate;
        $update->allowsubmissionsfromdate = $formdata->allowsubmissionsfromdate;
        $update->grade = $formdata->grade;
        $update->timemodified = time();

        $update->completionsubmit = !empty($formdata->completionsubmit);
        $update->userrole = implode(',', $formdata->userrole);
        $update->maxactivities = $formdata->maxactivities;
        $update->maxduration = $formdata->maxduration;
        $update->maxusers = $formdata->maxusers;
        $update->recordingmode = $formdata->recordingmode;
        $update->isreplayallowed = $formdata->isreplayallowed;
        $update->waitingroomaccessmode = $formdata->waitingroomaccessmode;
        $update->roomtype = $formdata->roomtype;
        $update->multimediaquality = $formdata->multimediaquality;
        $update->takepresence = $formdata->takepresence;
        if ($formdata->takepresence == 0) {
            $update->minpresence = 0;
        } else {
            $update->minpresence = $formdata->minpresence;
        }
        $update->sendnotifications = $formdata->sendnotifications;
        $update->sendstudentnotifications = $adminconfig->sendstudentnotifications;
        if (isset($formdata->sendstudentnotifications)) {
            $update->sendstudentnotifications = $formdata->sendstudentnotifications;
        }

        $update->grade = $formdata->grade;
        if (!empty($formdata->completionunlocked)) {
            $update->completionsubmit = !empty($formdata->completionsubmit);
        }

        $result = $DB->update_record('viaassign', $update);
        $this->instance = $DB->get_record('viaassign', array('id' => $update->id), '*', MUST_EXIST);

        foreach ($this->feedbackplugins as $plugin) {
            if (!$this->update_plugin_instance($plugin, $formdata)) {
                print_error($plugin->get_error());
                return false;
            }
        }

        $this->update_calendar($this->get_course_module()->id);
        $this->update_gradebook(false, $this->get_course_module()->id);

        return $result;
    }

    /**
     * Add elements in grading plugin form.
     *
     * @param mixed $grade stdClass|null
     * @param MoodleQuickForm $mform
     * @param stdClass $data
     * @param int $userid - The userid we are grading
     * @return void
     */
    protected function add_plugin_grade_elements($grade, MoodleQuickForm $mform, stdClass $data, $userid) {
        foreach ($this->feedbackplugins as $plugin) {
            if ($plugin->is_enabled() && $plugin->is_visible()) {
                $plugin->get_form_elements_for_user($grade, $mform, $data, $userid);
            }
        }
    }

    /**
     * Add one plugins settings to edit plugin form.
     *
     * @param viaassign_plugin $plugin The plugin to add the settings from
     * @param MoodleQuickForm $mform The form to add the configuration settings to.
     *                               This form is modified directly (not returned).
     * @param array $pluginsenabled A list of form elements to be added to a group.
     *                              The new element is added to this array by this function.
     * @return void
     */
    protected function add_plugin_settings(viaassign_plugin $plugin, MoodleQuickForm $mform, & $pluginsenabled) {
        global $CFG;
        if ($plugin->is_visible() && !$plugin->is_configurable() && $plugin->is_enabled()) {
            $name = $plugin->get_subtype() . '_' . $plugin->get_type() . '_enabled';
            $pluginsenabled[] = $mform->createElement('hidden', $name, 1);
            $mform->setType($name, PARAM_BOOL);
            $plugin->get_settings($mform);
        } else if ($plugin->is_visible() && $plugin->is_configurable()) {
            $name = $plugin->get_subtype() . '_' . $plugin->get_type() . '_enabled';
            $label = $plugin->get_name();
            $label .= ' ' . $this->get_renderer()->help_icon('enabled', $plugin->get_subtype() . '_' . $plugin->get_type());
            $pluginsenabled[] = $mform->createElement('checkbox', $name, '', $label);

            $default = get_config($plugin->get_subtype() . '_' . $plugin->get_type(), 'default');
            if ($plugin->get_config('enabled') !== false) {
                $default = $plugin->is_enabled();
            }
            $mform->setDefault($plugin->get_subtype() . '_' . $plugin->get_type() . '_enabled', $default);

            $plugin->get_settings($mform);
        }
    }

    /**
     * Add settings to edit plugin form.
     *
     * @param MoodleQuickForm $mform The form to add the configuration settings to.
     *                               This form is modified directly (not returned).
     * @return void
     */
    public function add_all_plugin_settings(MoodleQuickForm $mform) {
        $mform->addElement('header', 'submissiontypes', get_string('submissiontypes', 'viaassign'));

        $submissionpluginsenabled = array();
        $group = $mform->addGroup(array(), 'submissionplugins', get_string('submissiontypes', 'viaassign'), array(' '), false);
        foreach ($this->submissionplugins as $plugin) {
            $this->add_plugin_settings($plugin, $mform, $submissionpluginsenabled);
        }
        $group->setElements($submissionpluginsenabled);

        $mform->addElement('header', 'feedbacktypes', get_string('feedbacktypes', 'viaassign'));
        $feedbackpluginsenabled = array();
        $group = $mform->addGroup(array(), 'feedbackplugins', get_string('feedbacktypes', 'viaassign'), array(' '), false);
        foreach ($this->feedbackplugins as $plugin) {
            $this->add_plugin_settings($plugin, $mform, $feedbackpluginsenabled);
        }
        $group->setElements($feedbackpluginsenabled);
        $mform->setExpanded('submissiontypes');
    }

    /**
     * Allow each plugin an opportunity to update the defaultvalues
     * passed in to the settings form (needed to set up draft areas for
     * editor and filemanager elements)
     *
     * @param array $defaultvalues
     */
    public function plugin_data_preprocessing(&$defaultvalues) {
        foreach ($this->feedbackplugins as $plugin) {
            if ($plugin->is_visible()) {
                $plugin->data_preprocessing($defaultvalues);
            }
        }
    }

    /**
     * Get the name of the current module.
     *
     * @return string the module name (Assignment)
     */
    protected function get_module_name() {
        if (isset(self::$modulename)) {
            return self::$modulename;
        }
        self::$modulename = get_string('modulename', 'viaassign');
        return self::$modulename;
    }

    /**
     * Get the plural name of the current module.
     *
     * @return string the module name plural (Assignments)
     */
    protected function get_module_name_plural() {
        if (isset(self::$modulenameplural)) {
            return self::$modulenameplural;
        }
        self::$modulenameplural = get_string('modulenameplural', 'viaassign');
        return self::$modulenameplural;
    }

    /**
     * Has this viaassignment been constructed from an instance?
     *
     * @return bool
     */
    public function has_instance() {
        return $this->instance || $this->get_course_module();
    }

    /**
     * Get the settings for the current instance of this viaassignment
     *
     * @return stdClass The settings
     */
    public function get_instance() {
        global $DB;
        if ($this->instance) {
            return $this->instance;
        }
        if ($this->get_course_module()) {
            $params = array('id' => $this->get_course_module()->instance);
            $this->instance = $DB->get_record('viaassign', $params, '*', MUST_EXIST);
        }
        if (!$this->instance) {
            throw new coding_exception('Improper use of the viaassignment class. ' .
                                       'Cannot load the viaassignment record.');
        }
        return $this->instance;
    }

    /**
     * Get the primary grade item for this viaassign instance.
     *
     * @return stdClass The grade_item record
     */
    public function get_grade_item() {
        if ($this->gradeitem) {
            return $this->gradeitem;
        }
        $instance = $this->get_instance();
        $params = array('itemtype' => 'mod',
                        'itemmodule' => 'viaassign',
                        'iteminstance' => $instance->id,
                        'courseid' => $instance->course,
                        'itemnumber' => 0);
        $this->gradeitem = grade_item::fetch($params);
        if (!$this->gradeitem) {
            throw new coding_exception('Improper use of the viaassignment class. ' .
                                       'Cannot load the grade item.');
        }
        return $this->gradeitem;
    }

    /**
     * Get the context of the current course.
     *
     * @return mixed context|null The course context
     */
    public function get_course_context() {
        if (!$this->context && !$this->course) {
            throw new coding_exception('Improper use of the viaassignment class. ' .
                                       'Cannot load the course context.');
        }
        if ($this->context) {
            return $this->context->get_course_context();
        } else {
            return context_course::instance($this->course->id);
        }
    }

    /**
     * Get the current course module.
     *
     * @return mixed stdClass|null The course module
     */
    public function get_course_module() {
        if ($this->coursemodule) {
            return $this->coursemodule;
        }
        if (!$this->context) {
            return null;
        }

        if ($this->context->contextlevel == CONTEXT_MODULE) {
            $this->coursemodule = get_coursemodule_from_id('viaassign',
                                                           $this->context->instanceid,
                                                           0,
                                                           false,
                                                           MUST_EXIST);
            return $this->coursemodule;
        }
        return null;
    }

    /**
     * Get context module.
     *
     * @return context
     */
    public function get_context() {
        return $this->context;
    }

    /**
     * Get the current course.
     *
     * @return mixed stdClass|null The course
     */
    public function get_course() {
        global $DB;

        if ($this->course) {
            return $this->course;
        }

        if (!$this->context) {
            return null;
        }
        $params = array('id' => $this->get_course_context()->instanceid);
        $this->course = $DB->get_record('course', $params, '*', MUST_EXIST);

        return $this->course;
    }

    /**
     * Return a grade in user-friendly form, whether it's a scale or not.
     *
     * @param mixed $grade int|null
     * @param boolean $editing Are we allowing changes to this grade?
     * @param int $userid The user id the grade belongs to
     * @param int $modified Timestamp from when the grade was last modified
     * @return string User-friendly representation of grade
     */
    public function display_grade($grade, $editing, $userid=0, $modified=0) {
        global $DB;

        static $scalegrades = array();

        $o = '';

        if ($this->get_instance()->grade >= 0) {
            // Normal number.
            if ($editing && $this->get_instance()->grade > 0) {
                if ($grade < 0) {
                    $displaygrade = '';
                } else {
                    $displaygrade = format_float($grade, 2);
                }
                $o .= '<label class="accesshide" for="quickgrade_' . $userid . '">' .
                       get_string('usergrade', 'viaassign') .
                       '</label>';
                $o .= '<input type="text"
                              id="quickgrade_' . $userid . '"
                              name="quickgrade_' . $userid . '"
                              value="' .  $displaygrade . '"
                              size="6"
                              maxlength="10"
                              class="quickgrade"/>';
                $o .= '&nbsp;/&nbsp;' . format_float($this->get_instance()->grade, 2);
                return $o;
            } else {
                if ($grade == -1 || $grade === null) {
                    $o .= '-';
                } else {
                    $item = $this->get_grade_item();
                    $o .= grade_format_gradevalue($grade, $item);
                    if ($item->get_displaytype() == GRADE_DISPLAY_TYPE_REAL) {
                        // If displaying the raw grade, also display the total value.
                        $o .= '&nbsp;/&nbsp;' . format_float($this->get_instance()->grade, 2);
                    }
                }
                return $o;
            }
        } else {
            // Scale.
            if (empty($this->cache['scale'])) {
                if ($scale = $DB->get_record('scale', array('id' => -($this->get_instance()->grade)))) {
                    $this->cache['scale'] = make_menu_from_list($scale->scale);
                } else {
                    $o .= '-';
                    return $o;
                }
            }
            if ($editing) {
                $o .= '<label class="accesshide"
                              for="quickgrade_' . $userid . '">' .
                      get_string('usergrade', 'viaassign') .
                      '</label>';
                $o .= '<select name="quickgrade_' . $userid . '" class="quickgrade">';
                $o .= '<option value="-1">' . get_string('nograde') . '</option>';
                foreach ($this->cache['scale'] as $optionid => $option) {
                    $selected = '';
                    if ($grade == $optionid) {
                        $selected = 'selected="selected"';
                    }
                    $o .= '<option value="' . $optionid . '" ' . $selected . '>' . $option . '</option>';
                }
                $o .= '</select>';
                return $o;
            } else {
                $scaleid = (int)$grade;
                if (isset($this->cache['scale'][$scaleid])) {
                    $o .= $this->cache['scale'][$scaleid];
                    return $o;
                }
                $o .= '-';
                return $o;
            }
        }
    }

    /**
     * Load a list of users enrolled in the current course with the specified permission and group.
     * 0 for no group.
     *
     * @param int $currentgroup
     * @param bool $idsonly
     * @return array List of user records
     */
    public function list_participants($currentgroup, $idsonly, $currentgrouping = 0) {
        global $DB;

        if ($currentgroup) {
            $join = "LEFT JOIN {groups_members} gm ON gm.userid = u.id";
            $gwhere = ' AND gm.groupid = '.$currentgroup.'';
        } else if ($currentgrouping != 0) {
            $join = "LEFT JOIN {groups_members} gm ON gm.userid = u.id LEFT JOIN {groupings_groups} gg ON gg.groupid = gm.groupid";
            $gwhere = ' AND gg.groupingid = '.$currentgrouping.'';
        } else {
            $join = '';
            $gwhere = '';
        }

        $key = $this->context->id . '-' . $currentgroup . '-' . $this->show_only_active_users();

        $viaassign = $DB->get_record('viaassign', array('id' => $this->coursemodule->instance));

        $roles = explode(',', $viaassign->userrole);
        for ($x = 0; $x <= count($roles) - 1; $x++) {
            if ($x == 0) {
                $where = ' AND (ra.roleid = '. $roles[$x];
            } else {
                $where .= ' OR ra.roleid = '. $roles[$x];
            }
        }

        $coursecontext = context_course::instance($this->course->id);

        $users = $DB->get_records_sql('SELECT DISTINCT u.id, u.* FROM {role_assignments} ra
                                        LEFT JOIN {user} u ON u.id = ra.userid '.$join. '
                                        WHERE ra.contextid = '.$coursecontext->id . $where .')' . $gwhere);

        $this->participants[$key] = $users;

        if ($idsonly) {
            $idslist = array();
            foreach ($this->participants[$key] as $id => $user) {
                $idslist[$id] = new stdClass();
                $idslist[$id]->id = $id;
            }
            return $idslist;
        }
        return $this->participants[$key];
    }

    /**
     * Load a count of active users enrolled in the current course with the specified permission and group.
     * 0 for no group.
     *
     * @param int $currentgroup
     * @return int number of matching users
     */
    public function count_participants($currentgroup) {
        return count($this->list_participants($currentgroup, true));
    }

    /**
     * Load a count of active users submissions in the current module that require grading
     * This means the submission modification time is more recent than the
     * grading modification time and the status is SUBMITTED.
     *
     * @return int number of matching submissions
     */
    public function count_submissions_need_grading() {
        global $DB;

        $currentgroup = groups_get_activity_group($this->get_course_module(), true);

        list($esql, $params) = $viaassignment->list_participants($currentgroup, true);

        $params['viaassignid'] = $this->get_instance()->id;
        $params['viaassignid2'] = $this->get_instance()->id;
        $params['viaassignid3'] = $this->get_instance()->id;
        $params['submitted'] = VIAASSIGN_SUBMISSION_STATUS_CREATED;

        $sql = 'SELECT COUNT(s.viaid)
                   FROM {viaassign_submission} s
                   LEFT JOIN {viaassign_grades} g ON
                        s.viaassignid = g.viaassign AND
                        s.userid = g.userid AND
                        s.viaid = g.viaid
                   WHERE
                        g.id IS NULL';

        return $DB->count_records_sql($sql, $params);
    }

    /**
     * Load a count of grades.
     *
     * @return int number of grades
     */
    public function count_grades() {
        global $DB;

        if (!$this->has_instance()) {
            return 0;
        }

        $currentgroup = groups_get_activity_group($this->get_course_module(), true);
        list($esql, $params) = $viaassignment->list_participants($currentgroup, true);

        $params['viaassignid'] = $this->get_instance()->id;

        $sql = 'SELECT COUNT(g.userid)
                   FROM {viaassign_grades} g
                   JOIN(' . $esql . ') e ON e.id = g.userid
                   WHERE g.viaassign = :viaassignid';

        return $DB->count_records_sql($sql, $params);
    }

    /**
     * Load a count of submissions.
     *
     * @return int number of submissions
     */
    public function count_submissions() {
        global $DB;

        if (!$this->has_instance()) {
            return 0;
        }

        $params = array();

        $currentgroup = groups_get_activity_group($this->get_course_module(), true);
        list($esql, $params) = $viaassignment->list_participants($currentgroup, true);

        $params['viaassignid'] = $this->get_instance()->id;

        $sql = 'SELECT COUNT(DISTINCT s.userid)
                    FROM {viaassign_submission} s
                    JOIN(' . $esql . ') e ON e.id = s.userid
                    WHERE
                        s.viaassignid = :viaassignid AND
                        s.timemodified IS NOT NULL';

        return $DB->count_records_sql($sql, $params);
    }

    /**
     * Load a count of submissions with a specified status.
     *
     * @param string $status The submission status - should match one of the constants
     * @return int number of matching submissions
     */
    public function count_submissions_with_status($status) {
        global $DB;

        $params['viaassignid'] = $this->get_instance()->id;
        $params['submissionstatus'] = $status;

        $sql = 'SELECT COUNT(s.viaid)
                        FROM {viaassign_submission} s
                        WHERE
                            s.viaassignid = :viaassignid AND
                            s.timemodified IS NOT NULL AND
                            s.status = :submissionstatus';

        return $DB->count_records_sql($sql, $params);
    }

    /**
     * Utility function to get the userid for every row in the grading table
     * so the order can be frozen while we iterate it.
     *
     * @return array An array of userids
     */
    protected function get_grading_userid_list() {
        $filter = get_user_preferences('viaassign_filter', '');
        $table = new viaassign_grading_table($this, 0, $filter, 0, false);

        $useridlist = $table->get_column_data('userid');

        return $useridlist;
    }

    /**
     * Generate zip file from array of given files.
     *
     * @param array $filesforzipping - array of files to pass into archive_to_pathname.
     *                                 This array is indexed by the final file name and each
     *                                 element in the array is an instance of a stored_file object.
     * @return path of temp file - note this returned file does
     *         not have a .zip extension - it is a temp file.
     */
    protected function pack_files($filesforzipping) {
        global $CFG;
        // Create path for new zip file.
        $tempzip = tempnam($CFG->tempdir . '/', 'viaassignment_');
        // Zip files.
        $zipper = new zip_packer();
        if ($zipper->archive_to_pathname($filesforzipping, $tempzip)) {
            return $tempzip;
        }
        return false;
    }

    /**
     * Finds all viaassign notifications that have yet to be mailed out, and mails them.
     *
     * Cron function to be run periodically according to the moodle cron.
     *
     * @return bool
     */
    public static function cron() {
        global $DB;

        // Submissions are excluded if the viaassignment is hidden in the gradebook.
        $sql = 'SELECT distinct g.id as gradeid, a.course, a.name,
                       g.*, g.timemodified as lastmodified
                 FROM {viaassign} a
                 JOIN {viaassign_grades} g ON g.viaassign = a.id
            LEFT JOIN {viaassign_user_flags} uf ON uf.viaassign = a.id AND uf.userid = g.userid
                 JOIN {course_modules} cm ON cm.course = a.course
                 JOIN {modules} md ON md.id = cm.module
                 JOIN {grade_items} gri ON gri.iteminstance = a.id AND gri.courseid = a.course AND gri.itemmodule = md.name
                 WHERE uf.mailed = 0 AND gri.hidden = 0';

        $submissions = $DB->get_records_sql($sql);

        if (empty($submissions)) {
            return true;
        }

        mtrace('Processing ' . count($submissions) . ' viaassignment submissions ...');

        // Preload courses we are going to need those.
        $courseids = array();
        foreach ($submissions as $submission) {
            $courseids[] = $submission->course;
        }

        // Filter out duplicates.
        $courseids = array_unique($courseids);
        $ctxselect = context_helper::get_preload_record_columns_sql('ctx');
        list($courseidsql, $params) = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED);
        $sql = 'SELECT c.*, ' . $ctxselect .
                  ' FROM {course} c
             LEFT JOIN {context} ctx ON ctx.instanceid = c.id AND ctx.contextlevel = :contextlevel
                 WHERE c.id ' . $courseidsql;

        $params['contextlevel'] = CONTEXT_COURSE;
        $courses = $DB->get_records_sql($sql, $params);

        // Clean up... this could go on for a while.
        unset($courseids);
        unset($ctxselect);
        unset($courseidsql);
        unset($params);

        // Simple array we'll use for caching modules.
        $modcache = array();

        // Message students about new feedback.
        foreach ($submissions as $submission) {
            mtrace("Processing viaassignment submission $submission->id ...");

            // Do not cache user lookups - could be too many.
            if (!$user = $DB->get_record('user', array('id' => $submission->userid))) {
                mtrace('Could not find user ' . $submission->userid);
                continue;
            }

            // Use a cache to prevent the same DB queries happening over and over.
            if (!array_key_exists($submission->course, $courses)) {
                mtrace('Could not find course ' . $submission->course);
                continue;
            }
            $course = $courses[$submission->course];
            if (isset($course->ctxid)) {
                // Context has not yet been preloaded. Do so now.
                context_helper::preload_from_record($course);
            }

            // Override the language and timezone of the "current" user, so that
            // mail is customised for the receiver.
            cron_setup_user($user, $course);

            // Context lookups are already cached.
            $coursecontext = context_course::instance($course->id);
            if (!is_enrolled($coursecontext, $user->id)) {
                $courseshortname = format_string($course->shortname,
                                                 true,
                                                 array('context' => $coursecontext));
                mtrace(fullname($user) . ' not an active participant in ' . $courseshortname);
                continue;
            }

            if (!$grader = $DB->get_record('user', array('id' => $submission->grader))) {
                mtrace('Could not find grader ' . $submission->grader);
                continue;
            }

            if (!array_key_exists($submission->viaassign, $modcache)) {
                $mod = get_coursemodule_from_instance('viaassign', $submission->viaassign, $course->id);
                if (empty($mod)) {
                    mtrace('Could not find course module for viaassignment id ' . $submission->viaassign);
                    continue;
                }
                $modcache[$submission->viaassign] = $mod;
            } else {
                $mod = $modcache[$submission->viaassign];
            }
            // Context lookups are already cached.
            $contextmodule = context_module::instance($mod->id);

            if (!$mod->visible) {
                // Hold mail notification for hidden viaassignments until later.
                continue;
            }

            // Need to send this to the student.
            $messagetype = 'feedbackavailable';
            $eventtype = 'viaassign_notification';
            $updatetime = $submission->lastmodified;
            $modulename = get_string('modulename', 'viaassign');

            $uniqueid = 0;
            self::send_viaassign_notification($grader,
                                               $user,
                                               $messagetype,
                                               $eventtype,
                                               $updatetime,
                                               $mod,
                                               $contextmodule,
                                               $course,
                                               $modulename,
                                               $submission->name,
                                               $uniqueid);

            $flags = $DB->get_record('viaassign_user_flags', array('userid' => $user->id, 'viaassign' => $submission->viaassign));
            if ($flags) {
                $flags->mailed = 1;
                $DB->update_record('viaassign_user_flags', $flags);
            } else {
                $flags = new stdClass();
                $flags->userid = $user->id;
                $flags->viaassign = $submission->viaassign;
                $flags->mailed = 1;
                $DB->insert_record('viaassign_user_flags', $flags);
            }

            mtrace('Done');
        }
        mtrace('Done processing ' . count($submissions) . ' viaassign submissions');

        cron_setup_user();

        // Free up memory just to be sure.
        unset($courses);
        unset($modcache);

        return true;
    }

    /**
     * Mark in the database that this grade record should have an update notification sent by cron.
     *
     * @param stdClass $grade a grade record keyed on id
     * @return bool true for success
     */
    public function notify_grade_modified($grade) {
        global $DB;

        $flags = $this->get_user_flags($grade->userid, true);
        $flags->mailed = 0;

        return $this->update_user_flags($flags);
    }

    /**
     * Update user flags for this user in this viaassignment.
     *
     * @param stdClass $flags a flags record keyed on id
     * @return bool true for success
     */
    public function update_user_flags($flags) {
        global $DB;
        if ($flags->userid <= 0 || $flags->viaassign <= 0 || $flags->id <= 0) {
            return false;
        }

        $result = $DB->update_record('viaassign_user_flags', $flags);

        return $result;
    }

    /**
     * Update a grade in the grade table for the viaassignment and in the gradebook.
     *
     * @param stdClass $grade a grade record keyed on id
     * @return bool true for success
     */
    public function update_grade($grade) {
        global $DB;

        $grade->timemodified = time();

        if ($grade->grade && $grade->grade != -1) {
            if ($this->get_instance()->grade > 0) {
                if (!is_numeric($grade->grade)) {
                    return false;
                } else if ($grade->grade > $this->get_instance()->grade) {
                    return false;
                } else if ($grade->grade < 0) {
                    return false;
                }
            } else {
                // This is a scale.
                if ($scale = $DB->get_record('scale', array('id' => -($this->get_instance()->grade)))) {
                    $scaleoptions = make_menu_from_list($scale->scale);
                    if (!array_key_exists((int) $grade->grade, $scaleoptions)) {
                        return false;
                    }
                }
            }
        }

        $result = $DB->update_record('viaassign_grades', $grade);

        $submission = $this->get_user_submission($grade->userid, false, $grade->viaid);
        // If it,s ascale grade we only keep the last one!
        if (!isset($scale)) {
            $allgrades = $DB->get_records('viaassign_grades', array('viaassign' => $grade->viaassign, 'userid' => $grade->userid));
            $divideby = count($allgrades);
            $average = 0;
            foreach ($allgrades as $g) {
                $average = $average + $g->grade;
            }
            $averagegrade = $average / $divideby;
        } else {
            $averagegrade = $grade->grade;
        }

        // Not the latest attempt.
        if ($submission && $submission->viaid != $grade->viaid) {
            return true;
        }

        if ($result) {
            // Upgrade gradebook with average of all grades, for all submissions.
            $newgrade = $grade;
            $newgrade->grade = $averagegrade;
            $this->gradebook_item_update(null, $newgrade);
            \mod_viaassign\event\submission_graded::create_from_grade($this, $grade)->trigger();
        }
        return $result;
    }

    /**
     * View the grant extension date page.
     *
     * Uses url parameters 'userid'
     * or from parameter 'selectedusers'
     *
     * @param moodleform $mform - Used for validation of the submitted data
     * @return string
     */
    protected function view_grant_extension($mform) {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/mod/viaassign/extensionform.php');

        $o = '';
        $batchusers = optional_param('selectedusers', '', PARAM_SEQUENCE);
        $data = new stdClass();
        $data->extensionduedate = null;
        $userid = 0;
        if (!$batchusers) {
            $userid = required_param('userid', PARAM_INT);

            $grade = $this->get_user_grade($userid, false);

            $user = $DB->get_record('user', array('id' => $userid), '*', MUST_EXIST);

            if ($grade) {
                $data->extensionduedate = $grade->extensionduedate;
            }
            $data->userid = $userid;
        } else {
            $data->batchusers = $batchusers;
        }
        $header = new viaassign_header($this->get_instance(),
                                        $this->get_context(),
                                        $this->show_intro(),
                                        $this->get_course_module()->id,
                                        get_string('grantextension', 'viaassign'));
        $o .= $this->get_renderer()->render($header);

        if (!$mform) {
            $formparams = array($this->get_course_module()->id,
                                $userid,
                                $batchusers,
                                $this->get_instance(),
                                $data);
            $mform = new mod_viaassign_extension_form(null, $formparams);
        }
        $o .= $this->get_renderer()->render(new viaassign_form('extensionform', $mform));
        $o .= $this->view_footer();

        return $o;

    }

    /**
     * Get a list of the users in the same group as this user.
     *
     * @param int $groupid The id of the group whose members we want or 0 for the default group
     * @param bool $onlyids Whether to retrieve only the user id's
     * @return array The users (possibly id's only)
     */
    public function get_submission_group_members($groupid, $onlyids) {
        $members = array();
        if ($groupid != 0) {
            if ($onlyids) {
                $allusers = groups_get_members($groupid, 'u.id');
            } else {
                $allusers = groups_get_members($groupid);
            }
            foreach ($allusers as $user) {
                if ($this->get_submission_group($user->id)) {
                    $members[] = $user;
                }
            }
        } else {
            $allusers = $this->list_participants(null, $onlyids);
            foreach ($allusers as $user) {
                if ($this->get_submission_group($user->id) == null) {
                    $members[] = $user;
                }
            }
        }
        // Exclude suspended users, if user can't see them.
        if (!has_capability('moodle/course:viewsuspendedusers', $this->context)) {
            foreach ($members as $key => $member) {
                if (!$this->is_active_user($member->id)) {
                    unset($members[$key]);
                }
            }
        }
        return $members;
    }

    /**
     * View a summary listing of all viaassignments in the current course.
     *
     * @return string
     */
    private function view_course_index() {
        global $USER;

        $o = '';

        $course = $this->get_course();
        $strplural = get_string('modulenameplural', 'viaassign');

        if (!$cms = get_coursemodules_in_course('viaassign', $course->id, 'm.duedate')) {
            $o .= $this->get_renderer()->notification(get_string('thereareno', 'moodle', $strplural));
            $o .= $this->get_renderer()->continue_button(new moodle_url('/course/view.php', array('id' => $course->id)));
            return $o;
        }

        $strsectionname = '';
        $usesections = course_format_uses_sections($course->format);
        $modinfo = get_fast_modinfo($course);

        if ($usesections) {
            $strsectionname = get_string('sectionname', 'format_'.$course->format);
            $sections = $modinfo->get_section_info_all();
        }
        $courseindexsummary = new viaassign_course_index_summary($usesections, $strsectionname);

        $timenow = time();

        $currentsection = '';
        foreach ($modinfo->instances['viaassign'] as $cm) {
            if (!$cm->uservisible) {
                continue;
            }

            $timedue = $cms[$cm->id]->duedate;

            $sectionname = '';
            if ($usesections && $cm->sectionnum) {
                $sectionname = get_section_name($course, $sections[$cm->sectionnum]);
            }

            $submitted = '';
            $context = context_module::instance($cm->id);

            $viaassignment = new viaassign($context, $cm, $course);

            if (has_capability('mod/viaassign:grade', $context)) {
                $submitted = $viaassignment->count_submissions_with_status(VIAASSIGN_SUBMISSION_STATUS_CREATED);
            } else if (has_capability('mod/viaassign:submit', $context) ||
                $this->can_create_via($USER->id, $viaassignment->userrole)) {
                $usersubmission = $viaassignment->get_user_submission($USER->id, false);

                if (!empty($usersubmission->status)) {
                    $submitted = get_string('submissionstatus_' . $usersubmission->status, 'viaassign');
                } else {
                    $submitted = get_string('submissionstatus_', 'viaassign');
                }
            }
            $gradinginfo = grade_get_grades($course->id, 'mod', 'viaassign', $cm->instance, $USER->id);
            if (isset($gradinginfo->items[0]->grades[$USER->id]) &&
                    !$gradinginfo->items[0]->grades[$USER->id]->hidden ) {
                $grade = $gradinginfo->items[0]->grades[$USER->id]->str_grade;
            } else {
                $grade = '-';
            }

            $courseindexsummary->add_viaassign_info($cm->id, $cm->name, $sectionname, $timedue, $submitted, $grade);
        }

        $o .= $this->get_renderer()->render($courseindexsummary);
        $o .= $this->view_footer();

        return $o;
    }

    /**
     * View a page rendered by a plugin.
     *
     * Uses url parameters 'pluginaction', 'pluginsubtype', 'plugin', and 'id'.
     *
     * @return string
     */
    protected function view_plugin_page() {
        global $USER;

        $o = '';

        $pluginsubtype = required_param('pluginsubtype', PARAM_ALPHA);
        $plugintype = required_param('plugin', PARAM_TEXT);
        $pluginaction = required_param('pluginaction', PARAM_ALPHA);

        $plugin = $this->get_plugin_by_type($pluginsubtype, $plugintype);
        if (!$plugin) {
            print_error('invalidformdata', '');
            return;
        }

        $o .= $plugin->view_page($pluginaction);

        return $o;
    }

    /**
     * Display the submission that is used by a plugin.
     *
     * Uses url parameters 'sid', 'gid' and 'plugin'.
     *
     * @param string $pluginsubtype
     * @return string
     */
    protected function view_plugin_content($pluginsubtype) {
        $o = '';

        $submissionid = optional_param('sid', 0, PARAM_INT);
        $gradeid = optional_param('gid', 0, PARAM_INT);
        $plugintype = required_param('plugin', PARAM_TEXT);
        $item = null;

        $plugin = $this->get_feedback_plugin_by_type($plugintype);
        if ($gradeid <= 0) {
            throw new coding_exception('Grade id should not be 0');
        }
        $item = $this->get_grade($gradeid);
        // Check permissions.
        $this->require_view_submission($item->userid);
        $o .= $this->get_renderer()->render(new viaassign_header($this->get_instance(),
                                                            $this->get_context(),
                                                            $this->show_intro(),
                                                            $this->get_course_module()->id,
                                                            $plugin->get_name()));
        $o .= $this->get_renderer()->render(new viaassign_feedback_plugin_feedback($plugin,
                                                            $item,
                                                            viaassign_feedback_plugin_feedback::FULL,
                                                            $this->get_course_module()->id,
                                                            $this->get_return_action(),
                                                            $this->get_return_params()));

        // Trigger event for viewing feedback.
        \mod_viaassign\event\feedback_viewed::create_from_grade($this, $item)->trigger();

        $o .= $this->view_return_links();

        $o .= $this->view_footer();

        return $o;
    }

    /**
     * Render the content in editor that is often used by plugin.
     *
     * @param string $filearea
     * @param int  $submissionid
     * @param string $plugintype
     * @param string $editor
     * @param string $component
     * @return string
     */
    public function render_editor_content($filearea, $submissionid, $plugintype, $editor, $component) {
        global $CFG;

        $result = '';

        $plugin = $this->get_submission_plugin_by_type($plugintype);

        $text = $plugin->get_editor_text($editor, $submissionid);
        $format = $plugin->get_editor_format($editor, $submissionid);

        $finaltext = file_rewrite_pluginfile_urls($text,
                                                  'pluginfile.php',
                                                  $this->get_context()->id,
                                                  $component,
                                                  $filearea,
                                                  $submissionid);
        $params = array('overflowdiv' => true, 'context' => $this->get_context());
        $result .= format_text($finaltext, $format, $params);

        if ($CFG->enableportfolios) {
            require_once($CFG->libdir . '/portfoliolib.php');

            $button = new portfolio_add_button();
            $portfolioparams = array('cmid' => $this->get_course_module()->id,
                                    'sid' => $submissionid,
                                    'plugin' => $plugintype,
                                    'editor' => $editor,
                                    'area' => $filearea);
            $button->set_callback_options('viaassign_portfolio_caller', $portfolioparams, 'mod_viaassign');
            $fs = get_file_storage();

            if ($files = $fs->get_area_files($this->context->id,
                                             $component,
                                             $filearea,
                                             $submissionid,
                                             'timemodified',
                                             false)) {
                $button->set_formats(PORTFOLIO_FORMAT_RICHHTML);
            } else {
                $button->set_formats(PORTFOLIO_FORMAT_PLAINHTML);
            }
            $result .= $button->to_html();
        }
        return $result;
    }

    /**
     * Display a continue page.
     *
     * @param string $message - The message to display
     * @return string
     */
    protected function view_savegrading_result($message) {
        $o = '';
        $o .= $this->get_renderer()->render(new viaassign_header($this->get_instance(),
                                                      $this->get_context(),
                                                      $this->show_intro(),
                                                      $this->get_course_module()->id,
                                                      get_string('savegradingresult', 'viaassign')));
        $gradingresult = new viaassign_gradingmessage(get_string('savegradingresult', 'viaassign'),
                                                   $message,
                                                   $this->get_course_module()->id);
        $o .= $this->get_renderer()->render($gradingresult);
        $o .= $this->view_footer();
        return $o;
    }
    /**
     * Display a grading error.
     *
     * @param string $message - The description of the result
     * @return string
     */
    protected function view_quickgrading_result($message) {
        $o = '';
        $o .= $this->get_renderer()->render(new viaassign_header($this->get_instance(),
                                                      $this->get_context(),
                                                      $this->show_intro(),
                                                      $this->get_course_module()->id,
                                                      get_string('quickgradingresult', 'viaassign')));
        $gradingresult = new viaassign_gradingmessage(get_string('quickgradingresult', 'viaassign'),
                                                   $message,
                                                   $this->get_course_module()->id);
        $o .= $this->get_renderer()->render($gradingresult);
        $o .= $this->view_footer();
        return $o;
    }

    /**
     * Display the page footer.
     *
     * @return string
     */
    protected function view_footer() {
        // When viewing the footer during PHPUNIT tests a set_state error is thrown.
        if (!PHPUNIT_TEST) {
            return $this->get_renderer()->render_footer();
        }

        return '';
    }

    /**
     * Throw an error if the permissions to view this users submission are missing.
     *
     * @throws required_capability_exception
     * @return none
     */
    public function require_view_submission($userid) {
        if (!$this->can_view_submission($userid)) {
            throw new required_capability_exception($this->context, 'mod/viaassign:viewgrades', 'nopermission', '');
        }
    }

    /**
     * Throw an error if the permissions to view grades in this viaassignment are missing.
     *
     * @throws required_capability_exception
     * @return none
     */
    public function require_view_grades() {
        if (!$this->can_view_grades()) {
            throw new required_capability_exception($this->context, 'mod/viaassign:viewgrades', 'nopermission', '');
        }
    }

    /**
     * Does this user have view grade or grade permission for this viaassignment?
     *
     * @return bool
     */
    public function can_view_grades() {
        // Permissions check.
        if (!has_any_capability(array('mod/viaassign:viewgrades', 'mod/viaassign:grade'), $this->context)) {
            return false;
        }

        return true;
    }

    /**
     * Does this user have grade permission for this viaassignment?
     *
     * @return bool
     */
    public function can_grade() {
        // Permissions check.
        if (!has_capability('mod/viaassign:grade', $this->context)) {
            return false;
        }

        return true;
    }

    /**
     * Util function to add a message to the log.
     *
     * @deprecated since 2.7 - Use new events system instead.
     *             (see http://docs.moodle.org/dev/Migrating_logging_calls_in_plugins).
     *
     * @param string $action The current action
     * @param string $info A detailed description of the change. But no more than 255 characters.
     * @param string $url The url to the viaassign module instance.
     * @param bool $return If true, returns the arguments, else adds to log. The purpose of this is to
     *                     retrieve the arguments to use them with the new event system (Event 2).
     * @return void|array
     */
    public function add_to_log($action = '', $info = '', $url='', $return = false) {
        global $USER;

        $fullurl = 'view.php?id=' . $this->get_course_module()->id;
        if ($url != '') {
            $fullurl .= '&' . $url;
        }

        $args = array(
            $this->get_course()->id,
            'viaassign',
            $action,
            $fullurl,
            $info,
            $this->get_course_module()->id
        );

        if ($return) {
            // We only need to call debugging when returning a value. This is because the call to
            // call_user_func_array('add_to_log', $args) will trigger a debugging message of it's own.
            debugging('The mod_viaassign add_to_log() function is now deprecated.', DEBUG_DEVELOPER);
            return $args;
        }
        call_user_func_array('add_to_log', $args);
    }

    /**
     * Lazy load the page renderer and expose the renderer to plugins.
     *
     * @return viaassign_renderer
     */
    public function get_renderer() {
        global $PAGE;
        if ($this->output) {
            return $this->output;
        }
        $this->output = $PAGE->get_renderer('mod_viaassign');
        return $this->output;
    }

    /**
     * Load the submission object for a particular user, optionally creating it if required.
     *
     *
     * @param int $userid The id of the user whose submission we want or 0 in which case USER->id is used
     * @param bool $create optional - defaults to false. If set to true a new submission object
     *                     will be created in the database.
     * @param int $viaid -
     * @return stdClass The submission
     */
    public function get_user_submission($userid, $create, $viaid = null) {
        global $DB, $USER;

        $submission = null;
        if (!$userid) {
            // If the userid is not null then use userid.
            $userid = $USER->id;
        }

        $params['viaassignid'] = $this->get_instance()->id;
        $params['userid'] = $userid;
        $params['viaid'] = $viaid;

        // Is the poitn to get all submissions or all?!?
        $sql = 'SELECT s.* FROM {viaassign_submission} s
                LEFT JOIN {via} v ON v.id = s.viaid
                WHERE s.viaassignid = :viaassignid AND s.userid = :userid AND s.viaid = :viaid
                ORDER BY v.datebegin DESC';
        $submissions = $DB->get_records_sql($sql, $params);

        if ($submissions) {
            $submission = reset($submissions);
        }

        if ($submission) {
            return $submission;
        }

        return false;
    }

    /**
     * Load the submission object from it's id.
     *
     * @param int $submissionid The id of the submission we want
     * @return stdClass The submission
     */
    protected function get_submission($submissionid) {
        global $DB;

            $params = array('viaassignid' => $this->get_instance()->id, 'id' => $submissionid);
        return $DB->get_record('viaassign_submission', $params, '*', MUST_EXIST);
    }

    /**
     * This will retrieve a user flags object from the db optionally creating it if required.
     * The user flags was split from the user_grades table in 2.5.
     *
     * @param int $userid The user we are getting the flags for.
     * @param bool $create If true the flags record will be created if it does not exist
     * @return stdClass The flags record
     */
    public function get_user_flags($userid, $create) {
        global $DB, $USER;

        // If the userid is not null then use userid.
        if (!$userid) {
            $userid = $USER->id;
        }

        $params = array('viaassign' => $this->get_instance()->id, 'userid' => $userid);

        $flags = $DB->get_record('viaassign_user_flags', $params);

        if ($flags) {
            return $flags;
        }
        if ($create) {
            $flags = new stdClass();
            $flags->viaassign = $this->get_instance()->id;
            $flags->userid = $userid;
            $flags->locked = 0;
            $flags->extensionduedate = 0;

            // The mailed flag can be one of 2 values: 0 is unsent, 1 is sent.
            $flags->mailed = 0;

            $fid = $DB->insert_record('viaassign_user_flags', $flags);
            $flags->id = $fid;
            return $flags;
        }
        return false;
    }

    /**
     * This will retrieve a grade object from the db, optionally creating it if required.
     *
     * @param int $userid The user we are grading
     * @param bool $create If true the grade will be created if it does not exist
     * @param int $viaid.
     * @return stdClass The grade record
     */
    public function get_user_grade($userid, $create, $viaid = null) {
        global $DB, $USER;

        // If the userid is not null then use userid.
        if (!$userid) {
            $userid = $USER->id;
        }

        if (isset($viaid)) {
            $params = array('viaassign' => $this->get_instance()->id, 'userid' => $userid, 'viaid' => $viaid);
        } else {
            $params = array('viaassign' => $this->get_instance()->id, 'userid' => $userid, 'viaid' => null);
        }

        $grade = $DB->get_records('viaassign_grades', $params, 'viaid DESC');

        if ($grade) {
            return reset($grade);
        }

        if ($create) {
            $grade = new stdClass();
            $grade->viaassign   = $this->get_instance()->id;
            $grade->userid       = $userid;
            $grade->timecreated = time();
            $grade->timemodified = $grade->timecreated;
            $grade->grade = -1;
            $grade->grader = $USER->id;
            $grade->viaid = $viaid;

            $gid = $DB->insert_record('viaassign_grades', $grade);
            $grade->id = $gid;
            return $grade;
        }
        return false;
    }

    public function get_alluser_grades($userid, $create, $viaid = null) {
        global $DB, $USER;

        // If the userid is not null then use userid.
        if (!$userid) {
            $userid = $USER->id;
        }

        $params = array('viaassign' => $this->get_instance()->id, 'userid' => $userid);
        if ($viaid) {
            $params['viaid'] = $viaid;
        }

        // We getting all grades, not just one!
        $grades = $DB->get_records('viaassign_grades', $params, 'viaid DESC');

        if ($grades) {
            return $grades;
        }
    }

    /**
     * This will retrieve a grade object from the db.
     *
     * @param int $gradeid The id of the grade
     * @return stdClass The grade record
     */
    protected function get_grade($gradeid) {
        global $DB;

            $params = array('viaassign' => $this->get_instance()->id, 'id' => $gradeid);
        return $DB->get_record('viaassign_grades', $params, '*', MUST_EXIST);
    }

    /**
     * Print the grading page for a single user submission.
     *
     * @param moodleform $mform
     * @return string
     */
    protected function view_single_grade_page($mform) {
        global $DB, $CFG;

        $o = '';
        $instance = $this->get_instance();

        require_once($CFG->dirroot . '/mod/viaassign/gradeform.php');

        // Need submit permission to submit an viaassignment.
        require_capability('mod/viaassign:grade', $this->context);

        $header = new viaassign_header($instance,
                                    $this->get_context(),
                                    false,
                                    $this->get_course_module()->id,
                                    get_string('grading', 'viaassign'));
        $o .= $this->get_renderer()->render($header);

        // If userid is passed - we are only grading a single student.
        $rownum = required_param('rownum', PARAM_INT);
        $useridlistid = optional_param('useridlistid', time(), PARAM_INT);
        $userid = optional_param('userid', 0, PARAM_INT);
        $viaid = optional_param('viaid', 0, PARAM_INT);

        $cache = cache::make_from_params(cache_store::MODE_SESSION, 'mod_viaassign', 'useridlist');

        if (!$useridlist = $cache->get($this->get_course_module()->id . '_' . $useridlistid)) {
            $useridlist = $this->get_grading_userid_list();
        }
        if ($userid == 0) {
            $userid = $useridlist[$rownum];
        }

        if ($rownum == count($useridlist) - 1) {
            $last = true;
        } else {
            $last = false;
        }

        // Get this via, must be the same oder!
        if (!$viaid) {
            $allsubs = $DB->get_records('viaassign_submission', array('userid' => $userid, 'viaassignid' => $instance->id));
            if ($allsubs) {
                if (count($allsubs) > 1) {
                    $subs = array();
                    $i = 0;
                    foreach ($allsubs as $k => $v) {
                        $subs[$i] = $v;
                        $i++;
                    }
                    if ($subs[$rownum]) {
                        $viaid = $subs[$rownum]->viaid;
                    }
                } else {
                    $sub = reset($allsubs);
                    $viaid = $sub->viaid;
                }
            }
        }
        $user = $DB->get_record('user', array('id' => $userid));
        if ($user) {
            $viewfullnames = has_capability('moodle/site:viewfullnames', $this->get_course_context());
            $usersummary = new viaassign_user_summary($user,
                                                   $this->get_course()->id,
                                                   $viewfullnames,
                                                   $this->get_uniqueid_for_user($user->id),
                                                   get_extra_user_fields($this->get_context()),
                                                   !$this->is_active_user($userid));
            $o .= $this->get_renderer()->render($usersummary);
        }
        $submission = $this->get_user_submission($userid, false, $viaid);
        $submissiongroup = null;

        $notsubmitted = array();

        // Get the requested grade.
        $grade = $this->get_user_grade($userid, false, $viaid);
        // Maybe there an old comment...
        if (!$grade) {
            $grade = $this->get_user_grade($userid, false, 0);
            if ($grade && $viaid != 0) {
                // A via activity was created since the comment was added, so we update the line!
                $grade->viaid = $viaid;
                $DB->update_record('viaassign_grades', $grade);
            }
        }
        $flags = $this->get_user_flags($userid, false);
        if ($this->can_view_submission($userid)) {
            $gradelocked = ($flags && $flags->locked) || $this->grading_disabled($userid);
            $extensionduedate = null;
            if ($flags) {
                $extensionduedate = $flags->extensionduedate;
            }
            $showedit = $this->submissions_open($userid) && ($this->is_any_submission_plugin_enabled());
            $viewfullnames = has_capability('moodle/site:viewfullnames', $this->get_course_context());
        }

        if ($grade) {
            $data = new stdClass();
            if ($grade->grade !== null && $grade->grade >= 0) {
                $data->grade = format_float($grade->grade, 2);
            }
        } else {
            $data = new stdClass();
            $data->grade = '';
        }

        // Now show the grading form.
        if (!$mform) {
                $pagination = array('rownum' => $rownum,
                    'useridlistid' => $useridlistid,
                    'last' => $last,
                    'userid' => optional_param('userid', 0, PARAM_INT),
                    'viaid' => $viaid);
                $formparams = array($this, $data, $pagination);
                $mform = new mod_viaassign_grade_form(null,
                                                   $formparams,
                                                   'post',
                                                   '',
                                                   array('class' => 'gradeform'));
        }

        $o .= $this->get_renderer()->render(new viaassign_form('gradingform', $mform));

        \mod_viaassign\event\grading_form_viewed::create_from_user($this, $user)->trigger();

        $o .= $this->view_footer();
        return $o;
    }

    /**
     * This is method view_single_feedback_page
     *
     * @param protected $mform This is a description
     * @return mixed This is the return value description
     *
     */
    protected function view_single_feedback_page($mform) {
        global $DB, $CFG;

        $o = '';
        $instance = $this->get_instance();

        require_once($CFG->dirroot . '/mod/viaassign/gradeform.php');

        // Need submit permission to submit an viaassignment.
        require_capability('mod/viaassign:grade', $this->context);

        $header = new viaassign_header($instance,
                                    $this->get_context(),
                                    false,
                                    $this->get_course_module()->id,
                                    get_string('grading', 'viaassign'));
        $o .= $this->get_renderer()->render($header);

        // If userid is passed - we are only grading a single student.
        $rownum = required_param('rownum', PARAM_INT);
        $useridlistid = optional_param('useridlistid', time(), PARAM_INT);
        $userid = optional_param('userid', 0, PARAM_INT);
        $viaid = optional_param('viaid', 0, PARAM_INT);

        $cache = cache::make_from_params(cache_store::MODE_SESSION, 'mod_viaassign', 'useridlist');

        if (!$useridlist = $cache->get($this->get_course_module()->id . '_' . $useridlistid)) {
            $useridlist = $this->get_grading_userid_list();
            $cache->set($this->get_course_module()->id . '_' . $useridlistid, $useridlist);
        }

        if ($rownum == count($useridlist) - 1) {
            $last = true;
        } else {
            $last = false;
        }
        $user = $DB->get_record('user', array('id' => $userid));
        if ($user) {
            $viewfullnames = has_capability('moodle/site:viewfullnames', $this->get_course_context());
            $usersummary = new viaassign_user_summary($user,
                                                   $this->get_course()->id,
                                                   $viewfullnames,
                                                   $this->get_uniqueid_for_user($user->id),
                                                   get_extra_user_fields($this->get_context()),
                                                   !$this->is_active_user($userid));
            $o .= $this->get_renderer()->render($usersummary);
        }
        $submission = $this->get_user_submission($userid, false, $viaid); // Do I need pass viaid?!
        $submissiongroup = null;

        $notsubmitted = array();

        // Get the requested grade.
        $grade = $this->get_user_grade($userid, false, $viaid);

        $flags = $this->get_user_flags($userid, false);
        if ($this->can_view_submission($userid)) {
            $gradelocked = ($flags && $flags->locked) || $this->grading_disabled($userid);
            $extensionduedate = null;
            if ($flags) {
                $extensionduedate = $flags->extensionduedate;
            }
            $showedit = $this->submissions_open($userid) && ($this->is_any_submission_plugin_enabled());
            $viewfullnames = has_capability('moodle/site:viewfullnames', $this->get_course_context());
        }

        if ($grade) {
            $data = new stdClass();
            if ($grade->grade !== null && $grade->grade >= 0) {
                $data->grade = format_float($grade->grade, 2);
            }
        } else {
            $data = new stdClass();
            $data->grade = '';
        }

        // Now show the grading form.
        if (!$mform) {
            $pagination = array('rownum' => $rownum,
                    'useridlistid' => $useridlistid,
                    'last' => $last,
                    'userid' => $userid,
                    'viaid' => $viaid);
            $formparams = array($this, $data, $pagination);
            $mform = new mod_viaassign_grade_form(null,
                                               $formparams,
                                               'post',
                                                '',
                                                array('class' => 'gradeform'));
        }

        $o .= $this->get_renderer()->render(new viaassign_form('gradingform', $mform));

        \mod_viaassign\event\grading_form_viewed::create_from_user($this, $user)->trigger();

        $o .= $this->view_footer();
        return $o;
    }

    /**
     * View a link to go back to the previous page. Uses url parameters returnaction and returnparams.
     *
     * @return string
     */
    protected function view_return_links() {
        $returnaction = optional_param('returnaction', '', PARAM_ALPHA);
        $returnparams = optional_param('returnparams', '', PARAM_TEXT);

        $params = array();
        $returnparams = str_replace('&amp;', '&', $returnparams);
        parse_str($returnparams, $params);
        $newparams = array('id' => $this->get_course_module()->id, 'action' => $returnaction);
        $params = array_merge($newparams, $params);

        $url = new moodle_url('/mod/viaassign/view.php', $params);
        return $this->get_renderer()->single_button($url, get_string('back'), 'get');
    }

    /**
     * View the grading table of all submissions for this viaassignment.
     *
     * @return string
     */
    protected function view_grading_table() {
        global $USER, $CFG;

        // Include grading options form.
        require_once($CFG->dirroot . '/mod/viaassign/gradingoptionsform.php');
        require_once($CFG->dirroot . '/mod/viaassign/quickgradingform.php');
        require_once($CFG->dirroot . '/mod/viaassign/gradingbatchoperationsform.php');

        $o = '';
        $cmid = $this->get_course_module()->id;

        $links = array();
        if (has_capability('gradereport/grader:view', $this->get_course_context()) &&
            has_capability('moodle/grade:viewall', $this->get_course_context())) {
            $gradebookurl = '/grade/report/grader/index.php?id=' . $this->get_course()->id;
            $links[$gradebookurl] = get_string('viewgradebook', 'viaassign');
        }

        foreach ($this->get_feedback_plugins() as $plugin) {
            if ($plugin->is_enabled() && $plugin->is_visible()) {
                foreach ($plugin->get_grading_actions() as $action => $description) {
                    $url = '/mod/viaassign/view.php' .
                           '?id=' .  $cmid .
                           '&plugin=' . $plugin->get_type() .
                           '&pluginsubtype=viaassignfeedback' .
                           '&action=viewpluginpage&pluginaction=' . $action;
                    $links[$url] = $description;
                }
            }
        }

        // Sort links alphabetically based on the link description.
        core_collator::asort($links);

        $gradingactions = new url_select($links);
        $gradingactions->set_label(get_string('choosegradingaction', 'viaassign'));

        $gradingmanager = get_grading_manager($this->get_context(), 'mod_viaassign', 'submissions');

        $perpage = get_user_preferences('viaassign_perpage', 20);
        $filter = get_user_preferences('viaassign_filter', '');

        $controller = $gradingmanager->get_active_controller();
        $showquickgrading = empty($controller) && $this->can_grade() && $this->instance->grade != 0;
        $quickgrading = get_user_preferences('viaassign_quickgrading', false);

        // Print options for changing the filter and changing the number of results per page.
        $gradingoptionsformparams = array('cm' => $cmid,
                                          'contextid' => $this->context->id,
                                          'userid' => $USER->id,
                                          'submissionsenabled' => $this->is_any_submission_plugin_enabled(),
                                          'showquickgrading' => $showquickgrading,
                                          'quickgrading' => $quickgrading,

                                        );

        $classoptions = array('class' => 'gradingoptionsform');
        $gradingoptionsform = new mod_viaassign_grading_options_form(null,
                                                                  $gradingoptionsformparams,
                                                                  'post',
                                                                  '',
                                                                  $classoptions);
        $batchformparams = array('cm' => $cmid,
                                'duedate' => $this->get_instance()->duedate,
                                'feedbackplugins' => $this->get_feedback_plugins(),
                                'context' => $this->get_context()
                                );
                            $classoptions = array('class' => 'gradingbatchoperationsform');

        $gradingbatchoperationsform = new mod_viaassign_grading_batch_operations_form(null,
                                                                                    $batchformparams,
                                                                                    'post',
                                                                                    '',
                                                                                    $classoptions);

        $gradingoptionsdata = new stdClass();
        $gradingoptionsdata->perpage = $perpage;
        $gradingoptionsdata->filter = $filter;

        $gradingoptionsform->set_data($gradingoptionsdata);

        $currenturl = $CFG->wwwroot  . '/mod/viaassign/view.php?id=' . $this->get_course_module()->id . '&action=grading';
        $groupingid = $this->get_course_module()->groupingid;

        if ($CFG->version > 2014111012) {
            $info = $this->get_course_module()->availability;
            $structure = json_decode($info);

            if (isset($structure)&& isset($structure->c[0]) && $structure->op != "!&") {
                if ($structure->c[0]->type == "grouping") {
                        $groupingid = $structure->c[0]->id;
                        $this->get_course_module()->groupingid = $groupingid;
                }
            }
        }
        $o .= groups_print_activity_menu($this->get_course_module(), $currenturl, true);

        // Plagiarism update status apearring in the grading book.
        if (!empty($CFG->enableplagiarism)) {
            require_once($CFG->libdir . '/plagiarismlib.php');
            $o .= plagiarism_update_status($this->get_course(), $this->get_course_module());
        }

        // Load and print the table of submissions.
        if ($showquickgrading && $quickgrading) {
            $gradingtable = new viaassign_grading_table($this, $perpage, $filter, 0, true);
            $table = $this->get_renderer()->render($gradingtable);
            $quickformparams = array('cm' => $this->get_course_module()->id,
                                     'gradingtable' => $table,
                                     'sendstudentnotifications' => $this->get_instance()->sendstudentnotifications);
            $quickgradingform = new mod_viaassign_quick_grading_form(null, $quickformparams);

            $o .= $this->get_renderer()->render(new viaassign_form('quickgradingform', $quickgradingform));
        } else {
            $gradingtable = new viaassign_grading_table($this, $perpage, $filter, 0, false);
            $o .= $this->get_renderer()->render($gradingtable);
        }

        $currentgroup = groups_get_activity_group($this->get_course_module(), true);
        $users = array_keys($this->list_participants($currentgroup, true));
        if (count($users) != 0 && $this->can_grade()) {
            // If no enrolled user in a course then don't display the batch operations feature.
            $assignform = new viaassign_form('gradingbatchoperationsform', $gradingbatchoperationsform);
            $o .= $this->get_renderer()->render($assignform);
        }

        $viaassignform = new viaassign_form('gradingoptionsform',
                                      $gradingoptionsform,
                                      'M.mod_viaassign.init_grading_options');
        $o .= $this->get_renderer()->render($viaassignform);

        return $o;
    }

    /**
     * View entire grading page.
     *
     * @return string
     */
    protected function view_grading_page() {
        global $CFG;

        $o = '';
        // Need submit permission to submit an viaassignment.
        $this->require_view_grades();
        require_once($CFG->dirroot . '/mod/viaassign/gradeform.php');

        // Only load this if it is.
        $o .= $this->view_grading_table();

        \mod_viaassign\event\grading_table_viewed::create_from_viaassign($this)->trigger();

        return $o;
    }

    /**
     * Message for students when viaassignment submissions have been closed.
     *
     * @param string $title The page title
     * @param array $notices The array of notices to show.
     * @return string
     */
    protected function view_notices($title, $notices) {
        global $CFG;

        $o = '';

        $header = new viaassign_header($this->get_instance(),
                                    $this->get_context(),
                                    $this->show_intro(),
                                    $this->get_course_module()->id,
                                    $title);
        $o .= $this->get_renderer()->render($header);

        foreach ($notices as $notice) {
            $o .= $this->get_renderer()->notification($notice);
        }

        $url = new moodle_url('/mod/viaassign/view.php', array('id' => $this->get_course_module()->id, 'action' => 'view'));
        $o .= $this->get_renderer()->continue_button($url);

        $o .= $this->view_footer();

        return $o;
    }

    /**
     * Get the name for a user - hiding their real name if blind marking is on.
     *
     * @param stdClass $user The user record as required by fullname()
     * @return string The name.
     */
    public function fullname($user) {
        return fullname($user);
    }

    /**
     * View edit submissions page.
     *
     * @param moodleform $mform
     * @param array $notices A list of notices to display at the top of the
     *                       edit submission form (e.g. from plugins).
     * @return string The page output.
     */
    protected function view_delete_via_page($params) {
        global $CFG, $USER, $DB;

        require_once($CFG->dirroot . '/mod/viaassign/submission_form.php');
        require_once($CFG->dirroot . '/mod/viaassign/gradeform.php');

        $o = '';

        $rownum = $params['rownum'];
        $useridlistid = $params['useridlistid'];
        $viaid = $params['viaid'];
        $userid = $params['userid'];

        $cache = cache::make_from_params(cache_store::MODE_SESSION, 'mod_viaassign', 'useridlist');
        if (!$useridlist = $cache->get($this->get_course_module()->id . '_' . $useridlistid)) {
            $useridlist = $this->get_grading_userid_list();
        }
        $cache->set($this->get_course_module()->id . '_' . $useridlistid, $useridlist);

        if ($rownum < 0 || $rownum > count($useridlist)) {
            throw new coding_exception('Row is out of bounds for the current grading table: ' . $rownum);
        }

        if ($userid == 0) {
            $userid = $useridlist[$rownum];
        }

        $submission = $DB->get_record('viaassign_submission', array(
                    'viaassignid' => $this->coursemodule->instance,
                    'viaid' => $viaid,
                    'userid' => $userid));
        $user = $DB->get_record('user', array('id' => $userid), '*', MUST_EXIST);

        $via = $DB->get_record('via', array('id' => $viaid));
        $text = new stdClass();
        $text->vianame = $via->name;

        if ($userid == $USER->id) {
            $confirmation = get_string('deletesubmissionown', 'viaassign', $text);
        } else {
            $text->username = $this->fullname($user);
            $confirmation = get_string('deletesubmissionother', 'viaassign', $text);
        }

        $title = '<p>'. $confirmation. '</p>';

        $o .= $this->get_renderer()->render(new viaassign_header($this->get_instance(),
                                                    $this->get_context(),
                                                    false,
                                                    $this->get_course_module()->id,
                                                    get_string('deletesubmission', 'viaassign'),
                                                    $title));

        $urlparams = array( 'action' => 'deletesubmission',
                            'sesskey' => sesskey(),
                            'submissionid' => $submission->id,
                            'viaid' => $via->id,
                            'userid' => $userid);
        $url = new moodle_url('/mod/viaassign/view.php?id=' . $this->get_course_module()->id, $urlparams);
        $o .= $this->get_renderer()->single_button($url, get_string('delete'), 'post', array('class' => 'deletesubmission'));

        $urlparams = array(    'action' => '');
        $url = new moodle_url('/mod/viaassign/view.php?id=' . $this->get_course_module()->id, $urlparams);
        $o .= $this->get_renderer()->single_button($url, get_string('cancel'), 'post', array('class' => 'cancelsubmission'));

        $o .= $this->view_footer();

        return $o;
    }

    /**
     * View edit submissions page.
     *
     * @param moodleform $mform
     * @param array $notices A list of notices to display at the top of the
     * edit submission form (e.g. from plugins).
     * @return string The page output.
     */
    public function view_submit_via_page($mform) {
        global $CFG, $USER, $DB;

        $o = '';
        require_once($CFG->dirroot . '/mod/viaassign/via_form.php');

        $instance = $this->get_instance();
        $user = $DB->get_record('user', array('id' => $USER->id));

        // User is editing their own submission.
        if (!$this->can_create_via($user->id, $instance->userrole)) {
            print_error('can not create activity');
        }
        $title = get_string('addsubmission', 'viaassign');

        $o .= $this->get_renderer()->render(new viaassign_header($instance,
                                            $this->get_context(),
                                            false,
                                            $this->get_course_module()->id,
                                            $title));

        $url = $CFG->wwwroot.'/mod/viaassign/view.php?id='.$this->get_context()->instanceid;

        $mform = new via_form($url,  array('va' => $instance, 'cmid' => $this->get_context()->instanceid), 'post');

        $o .= $this->get_renderer()->render(new viaassign_form('viaform', $mform));

        return $o;
    }

    /**
     * This is method view_edit_via_page
     *
     * @param protected $viaid This is a description
     * @return mixed This is the return value description
     *
     */
    protected function view_edit_via_page($viaid) {
        global $CFG, $USER, $DB;

        $o = '';
        require_once($CFG->dirroot . '/mod/viaassign/via_form.php');

        $instance = $this->get_instance();
        $user = $DB->get_record('user', array('id' => $USER->id));

        // User is editing their own submission. // admins can also edit others activities!
        if (!$this->can_create_via($USER->id, $instance->userrole) &&
            !has_capability('mod/viaassign:addinstance', $this->context) ) {
            print_error('nopermission', 'viaassign');
        }

        $flag = $this->get_user_flags($user->id, false);

        if (isset($flag->locked) && $flag->locked == 1) {
            redirect($CFG->wwwroot .'/mod/viaassign/view.php?id='.$this->context->instanceid);
        }

        $title = get_string('addsubmission', 'viaassign');

        $o .= $this->get_renderer()->render(new viaassign_header($instance,
                                            $this->get_context(),
                                            false,
                                            $this->get_course_module()->id,
                                            $title));

        $url = $CFG->wwwroot.'/mod/viaassign/view.php?id='.$this->get_context()->instanceid;

        $viaactivity = $DB->get_record('via', array('id' => $viaid));
        $viaactivity->viaid = $viaactivity->id;
        $viaactivity->intro_editor['text'] = $viaactivity->intro;
        $viaactivity->intro_editor['format'] = $viaactivity->introformat;

        $mform = new via_form($url,
            array('va' => $instance, 'cmid' => $this->get_context()->instanceid, 'viaactivity' => $viaactivity), 'post');

        $mform->set_data($viaactivity);

        $o .= $this->get_renderer()->render(new viaassign_form('viaform', $mform));

        $o .= $this->view_footer();

        return $o;
    }

    /**
     * See if this viaassignment has a grade yet.
     *
     * @param int $userid
     * @return bool
     */
    protected function is_graded($userid) {
        $grade = $this->get_user_grade($userid, false);
        if ($grade) {
            return ($grade->grade !== null && $grade->grade >= 0);
        }
        return false;
    }

    /**
     * Perform an access check to see if the current $USER can view this group submission.
     *
     * @param int $groupid
     * @return bool
     */
    public function can_view_group_submission($groupid) {
        global $USER;

        if (has_capability('mod/viaassign:grade', $this->context)) {
            return true;
        }
        if (!is_enrolled($this->get_course_context(), $USER->id)) {
            return false;
        }
        $members = $this->get_submission_group_members($groupid, true);
        foreach ($members as $member) {
            if ($member->id == $USER->id) {
                return true;
            }
        }
        return false;
    }

    /**
     * Perform an access check to see if the current $USER can view this users submission.
     *
     * @param int $userid
     * @return bool
     */
    public function can_view_submission($userid) {
        global $USER;

        if (!$this->is_active_user($userid) && !has_capability('moodle/course:viewsuspendedusers', $this->context)) {
            return false;
        }
        if (has_capability('mod/viaassign:managegrades', $this->context)) {
            return true;
        }
        if (!is_enrolled($this->get_course_context(), $userid)) {
            return false;
        }
        if ($userid == $USER->id && (has_capability('mod/viaassign:submit', $this->context))) {
            return true;
        }
        $instance = $this->get_instance();
        if ($this->can_create_via($USER->id, $instance->userrole)) {
            return true;
        }
        return false;
    }

    public function can_create_via($userid, $permitedroles) {
        $roles = get_user_roles($this->context);
        $permitedroles = explode(',', $permitedroles);

        foreach ($roles as $role) {
            if (in_array($role->roleid, $permitedroles)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Allows the plugin to show a batch grading operation page.
     *
     * @param moodleform $mform
     * @return none
     */
    protected function view_plugin_grading_batch_operation($mform) {
        require_capability('mod/viaassign:grade', $this->context);
        $prefix = 'plugingradingbatchoperation_';

        if ($data = $mform->get_data()) {
            $tail = substr($data->operation, strlen($prefix));
            list($plugintype, $action) = explode('_', $tail, 2);

            $plugin = $this->get_feedback_plugin_by_type($plugintype);
            if ($plugin) {
                $users = $data->selectedusers;
                $userlist = explode(',', $users);
                echo $plugin->grading_batch_operation($action, $userlist);
                return;
            }
        }
        print_error('invalidformdata', '');
    }

    /**
     * Ask the user to confirm they want to perform this batch operation
     *
     * @param moodleform $mform Set to a grading batch operations form
     * @return string - the page to view after processing these actions
     */
    protected function process_grading_batch_operation(&$mform) {
        global $CFG;
        require_once($CFG->dirroot . '/mod/viaassign/gradingbatchoperationsform.php');
        require_sesskey();

        $batchformparams = array('cm' => $this->get_course_module()->id,
                                 'duedate' => $this->get_instance()->duedate,
                                 'feedbackplugins' => $this->get_feedback_plugins(),
                                 'context' => $this->get_context());
        $formclasses = array('class' => 'gradingbatchoperationsform');
        $mform = new mod_viaassign_grading_batch_operations_form(null,
                                                              $batchformparams,
                                                              'post',
                                                              '',
                                                              $formclasses);

        if ($data = $mform->get_data()) {
            // Get the list of users.
            $users = $data->selectedusers;
            $userlist = explode(',', $users);

            $prefix = 'plugingradingbatchoperation_';

            if ($data->operation == 'grantextension') {
                // Reset the form so the grant extension page will create the extension form.
                $mform = null;
                return 'grantextension';
            } else if (strpos($data->operation, $prefix) === 0) {
                $tail = substr($data->operation, strlen($prefix));
                list($plugintype, $action) = explode('_', $tail, 2);

                $plugin = $this->get_feedback_plugin_by_type($plugintype);
                if ($plugin) {
                    return 'plugingradingbatchoperation';
                }
            }

            foreach ($userlist as $userid) {
                if ($data->operation == 'lock') {
                    $this->process_lock_submission($userid);
                } else if ($data->operation == 'unlock') {
                    $this->process_unlock_submission($userid);
                }
            }

        }

        return 'grading';
    }


    /**
     * Print 2 tables of information with no action links -
     * the submission summary and the grading summary.
     *
     * @param stdClass $user the user to print the report for
     * @param bool $showlinks - Return plain text or links to the profile
     * @return string - the html summary
     */
    public function view_student_summary($user, $showlinks) {
        global $CFG, $DB, $PAGE;

        $instance = $this->get_instance();
        $flags = $this->get_user_flags($user->id, false);
        $locked = $flags && $flags->locked ? $flags->locked : 0;

        $o = '';

        if ($this->can_create_via($user->id, $instance->userrole)) {
            // Grading criteria preview.
            $gradingmanager = get_grading_manager($this->context, 'mod_viaassign', 'submissions');
            $gradingcontrollerpreview = '';
            if ($gradingmethod = $gradingmanager->get_active_method()) {
                $controller = $gradingmanager->get_controller($gradingmethod);
                if ($controller->is_form_defined()) {
                    $gradingcontrollerpreview = $controller->render_preview($PAGE);
                }
            }

            $extensionduedate = null;
            if ($flags) {
                $extensionduedate = $flags->extensionduedate;
            }
            $viewfullnames = has_capability('moodle/site:viewfullnames', $this->get_course_context());

            $allsubmissions = $this->get_all_submissions($user->id);

            // Create button, if maxactivities has not ye been reached AND that the cut off date has not been reached.
            if ((!$flags || $locked == 0) && count($allsubmissions) < $instance->maxactivities && ($instance->duedate > time()
                    || (isset($flags->extensionduedate) && $flags->extensionduedate > time())) ) {

                $urlparams = array(
                    'action' => 'submitvia',
                    'sesskey' => sesskey(),
                    'userid' => $user->id);
                $url = new moodle_url('/mod/viaassign/view.php?id=' . $this->get_course_module()->id, $urlparams);
                $o .= $this->get_renderer()->single_button($url, get_string('addvia', 'viaassign'), 'post');

            } else {
                if ($flags && $locked == 1) {
                    $o .= $this->get_renderer()->notification(get_string('submissionslockedshort', 'viaassign'));
                } else if (count($allsubmissions) >= $instance->maxactivities) {
                    $o .= $this->get_renderer()->notification(get_string('maxactivitiesreached', 'viaassign'));
                } else {
                    $o .= $this->get_renderer()->notification(get_string('duedatereached', 'viaassign'));
                }
                $urlparams = array();
                $url = new moodle_url('/mod/viaassign/view.php?id=' . $this->get_course_module()->id, $urlparams);
                $o .= $this->get_renderer()->single_button($url,
                    get_string('addvia', 'viaassign'), 'post', array('disabled' => true));
            }

            // List of vias user created!
            if (count($allsubmissions) >= 1) {
                $history = new viaassign_history($allsubmissions,
                    $this->get_feedback_plugins(),
                    $this->get_course_module()->id,
                    $this->get_return_action(),
                    $this->get_return_params(),
                    false,
                    0,
                    0,
                    $locked);

                $o .= $this->get_renderer()->render($history);
            }
        }

        $allparticipations = $this->get_all_participations($user->id);
        // List of vias user is either participant or animator.
        if (count($allparticipations) >= 1) {
            $history = new viaassign_participations($allparticipations,
                $this->get_course_module()->id,
                $this->get_return_action(),
                $this->get_return_params(),
                false);

            $o .= $this->get_renderer()->render($history);
        } else {
            if (!$this->can_create_via($user->id, $instance->userrole)) {
                if (!has_capability('mod/viaassign:managegrades', $this->context)) {
                    // Only display if the user does not have an editing role. otherwise their page is empty!
                    $o .= '<h5>'.get_string('viaassignparticipation', 'viaassign').'</h5>';
                    $o .= '<p>'.get_string('viaassignparticipation_none', 'viaassign').'</p><br/><br/>';
                }
            }
        }

            // List of all public recordings.
        $recordings = $this->get_public_playbacks();
        if (count($recordings) >= 1) {
            $playbacks = new viaassign_playbacks($recordings,
                                                    $this->get_course_module()->id,
                                                    $this->get_return_action(),
                                                    $this->get_return_params(),
                                                    false);

            $o .= $this->get_renderer()->render($playbacks);
        }

        require_once($CFG->libdir.'/gradelib.php');
        require_once($CFG->dirroot.'/grade/grading/lib.php');

        // List all the grades, one per activity!
        $allgrades = $this->get_all_grades($user->id);

        $gradinginfo = grade_get_grades($this->get_course()->id,
            'mod',
            'viaassign',
            $instance->id,
            $user->id);

        $gradingitem = null;
        $gradebookgrade = null;
        if (isset($gradinginfo->items[0])) {
            $gradingitem = $gradinginfo->items[0];
            $gradebookgrade = $gradingitem->grades[$user->id];
        }

        $cangrade = has_capability('mod/viaassign:grade', $this->get_context());
        // If there is a visible grade, show the summary and feeback.
        if (count($allgrades) > 0 && ($cangrade || !$gradebookgrade->hidden)) {
            $gradefordisplay = null;
            $gradeddate = null;
            $grader = null;
            $gradingmanager = get_grading_manager($this->get_context(), 'mod_viaassign', 'submissions');

            // Only show the grade if it is not hidden in gradebook.
            if (!empty($gradebookgrade->grade) && ($cangrade || !$gradebookgrade->hidden)) {
                if ($controller = $gradingmanager->get_active_controller()) {
                    $menu = make_grades_menu($this->get_instance()->grade);
                    $controller->set_grade_range($menu, $this->get_instance()->grade > 0);
                    $gradefordisplay = $controller->render_grade($PAGE,
                                                                    $grade->id,
                                                                    $gradingitem,
                                                                    $gradebookgrade->str_long_grade,
                                                                    $cangrade);
                } else {
                    $gradefordisplay = $this->display_grade($gradebookgrade->grade, false);
                }
                $gradeddate = $gradebookgrade->dategraded;
                if (isset($grade->grader)) {
                    $grader = $DB->get_record('user', array('id' => $grade->grader));
                }
            }
            $feedbackstatus = new viaassign_feedback_status($allgrades,
                                                            $this->get_feedback_plugins(),
                                                            $this->get_course_module()->id,
                                                            $this->get_return_action(),
                                                            $this->get_return_params());
            $o .= $this->get_renderer()->render($feedbackstatus);
        }
        return $o;
    }

    /**
     * Get the grades for all previous attempts.
     * For each grade - the grader is a full user record,
     * and gradefordisplay is added (rendered from grading manager).
     *
     * @param int $userid If not set, $USER->id will be used.
     * @return array $grades All grade records for this user.
     */
    protected function get_all_grades($userid) {
        global $DB, $USER, $PAGE;

        // If the userid is not null then use userid.
        if (!$userid) {
            $userid = $USER->id;
        }

        $params = array('viaassign' => $this->get_instance()->id, 'userid' => $userid);

        $grades = $DB->get_records('viaassign_grades', $params);

        $gradercache = array();
        $cangrade = has_capability('mod/viaassign:grade', $this->get_context());

        // Need gradingitem and gradingmanager.
        $gradingmanager = get_grading_manager($this->get_context(), 'mod_viaassign', 'submissions');
        $controller = $gradingmanager->get_active_controller();

        $gradinginfo = grade_get_grades($this->get_course()->id,
                                        'mod',
                                        'viaassign',
                                        $this->get_instance()->id,
                                        $userid);

        $gradingitem = null;
        if (isset($gradinginfo->items[0])) {
            $gradingitem = $gradinginfo->items[0];
        }

        foreach ($grades as $grade) {
            // First lookup the grader info.
            if (isset($gradercache[$grade->grader])) {
                $grade->grader = $gradercache[$grade->grader];
            } else {
                // Not in cache - need to load the grader record.
                $grade->grader = $DB->get_record('user', array('id' => $grade->grader));
                $gradercache[$grade->grader->id] = $grade->grader;
            }

            // Now get the gradefordisplay.
            if ($controller) {
                $controller->set_grade_range(make_grades_menu($this->get_instance()->grade), $this->get_instance()->grade > 0);
                $grade->gradefordisplay = $controller->render_grade($PAGE,
                                                                     $grade->id,
                                                                     $gradingitem,
                                                                     $grade->grade,
                                                                     $cangrade);
            } else {
                $grade->gradefordisplay = $this->display_grade($grade->grade, false);
            }
            $viainfo = $DB->get_record('via', array('id' => $grade->viaid));
            $grade->viainfo = $viainfo ? $viainfo->name : get_string('nosubmission', 'viaassign');
        }

        return $grades;
    }

    /**
     * Get the submissions for all previous attempts.
     *
     * @param int $userid If not set, $USER->id will be used.
     * @return array $submissions All submission records for this user (or group).
     */
    protected function get_all_submissions($userid) {
        global $DB, $USER;

        // If the userid is not null then use userid.
        if (!$userid) {
            $userid = $USER->id;
        }

        // Params to get the user submissions.
        $params = array('viaassignid' => $this->get_instance()->id, 'userid' => $userid);

        // Return the submissions ordered by timecreated.
        $submissions = $DB->get_records('viaassign_submission', $params, 'timecreated DESC');

        return $submissions;
    }

    /**
     * Get the submissions for all previous attempts.
     *
     * @param int $userid If not set, $USER->id will be used.
     * @return array $submissions All submission records for this user (or group).
     */
    protected function get_all_participations($userid) {
        global $DB, $USER;

        // If the userid is not null then use userid.
        if (!$userid) {
            $userid = $USER->id;
        }

        $participations = $DB->get_records_sql(
                        'SELECT va.id, va.userid, v.name, v.id as viaid, v.datebegin, v.duration FROM {viaassign_submission} va
                        LEFT JOIN {via} v ON v.id = va.viaid
                        LEFT JOIN {via_participants} vp ON v.id = vp.activityid
                        WHERE viaassignid = '.$this->get_instance()->id.' AND vp.userid = '.$userid.' AND vp.participanttype <> 2');

        return $participations;
    }

    protected function get_public_playbacks() {
        global $DB, $USER;

        $where = '';

        if (groups_get_activity_groupmode($this->coursemodule) == 1) { // Seperate group mode is selected!
            $allowedgroups = groups_get_all_groups($this->get_instance()->course, $USER->id);
            $count = 1;
            if ($allowedgroups) {
                foreach ($allowedgroups as $group) {
                    if ($count == 1) {
                        $where .= ' AND (v.groupid = '.$group->id;
                    } else {
                        $where .= ' OR v.groupid = '.$group->id;
                    }
                    $count++;
                }
                $where .= ')';
            }
        }

        $publicplaybacks = $DB->get_records_sql(
                'SELECT vp.*, v.id as viaid, v.name, vs.userid as creator FROM {viaassign_submission} vs
            JOIN {via_playbacks} vp ON vp.activityid = vs.viaid
            JOIN {via} v ON v.id = vs.viaid
            WHERE vs.viaassignid = '.$this->get_instance()->id. $where. ' AND vp.accesstype = 2 AND vp.deleted = 0');// 2 = public.

        return $publicplaybacks;
    }

    /**
     * View submissions page (contains details of current submission).
     *
     * @return string
     */
    protected function view_submission_page() {
        global $CFG, $DB, $USER, $PAGE;

        $instance = $this->get_instance();

        $this->sync_viaassign_playbacks($instance->id);

        $o = '';
        $o .= $this->get_renderer()->render(new viaassign_header($instance,
                                                      $this->get_context(),
                                                      $this->show_intro(),
                                                      $this->get_course_module()->id));

        // Gernal table with basic info on the activity!
        $submitted = VIAASSIGN_SUBMISSION_STATUS_CREATED;

        if ($this->can_create_via($USER->id, $instance->userrole)) {
            // See if user has an extension.
            $flag = $DB->get_record('viaassign_user_flags', array('userid' => $USER->id, 'viaassign' => $instance->id));
            $extension = $flag ? $flag->extensionduedate : 0;
        } else {
            $extension = 0;
        }
        $summary = new viaassign_grading_summary(
                                            $instance->duedate,
                                            $extension,
                                            $instance->maxactivities,
                                            $instance->maxduration,
                                            $instance->maxusers,
                                            $this->count_participants(0),
                                            $this->count_submissions_with_status($submitted),
                                            $this->get_course_module()->id);

        if ($this->can_create_via($USER->id, $instance->userrole) || has_capability('mod/viaassign:managegrades', $this->context)) {
            $o .= $this->get_renderer()->render($summary);
        }

        $o .= $this->view_student_summary($USER, true);

        \mod_viaassign\event\submission_status_viewed::create_from_viaassign($this)->trigger();

        return $o;
    }

    /**
     * View submissions page (contains details of current submission).
     *
     * @return string
     */
    private function sync_viaassign_playbacks($viaassignid) {
        global $CFG, $DB;

        $activities = $DB->get_records_sql("SELECT v.id, v.viaactivityid, playbacksync, v.timecreated,
                                            v.isreplayallowed, v.recordingmode, v.activitytype
                                            FROM {viaassign_submission} vs
                                            JOIN {via} v ON vs.viaid = v.id
                                            WHERE viaassignid = ".$viaassignid."
                                            ORDER BY v.timecreated");

        if (count($activities) > 0) {
            $lowestsync = 0;
            foreach ($activities as $a) {
                $valuetocheck = 0;
                if ($a->playbacksync) {
                    $valuetocheck = $a->playbacksync;
                } else {
                    $a->playbacksync = $a->timecreated;
                    $valuetocheck = $a->timecreated;
                }

                if ($lowestsync == 0 || $valuetocheck < $lowestsync) {
                    $lowestsync = $valuetocheck;
                    continue;
                }
            }
                if ($a->activitytype != 4) { 
                // If the activity is not desactivated
                require_once($CFG->dirroot.'/mod/via/api.class.php');
                $api = new mod_via_api();
                $playbacks = $api->get_latest_added_playbacks($lowestsync);
                $timesync = time();

                if ($playbacks['PlaybackSearch']) {
                    if (isset($playbacks['PlaybackSearch']['PlaybackMatch']) && count($playbacks['PlaybackSearch']) == 1) {
                        $playbacks = $playbacks['PlaybackSearch']['PlaybackMatch'];
                    } else {
                        $playbacks = $playbacks['PlaybackSearch'];
                    }

                    foreach ($playbacks as $p) {
                        if (isset($p['PlaybackID'])) {
                            // Verify that the playback's activity is in the viassign.
                            $neededobject = null;
                            foreach ($activities as $a) {
                                if ($p['ActivityID'] == $a->viaactivityid) {
                                    if ($a->recordingmode == 1) {
                                        $a->playbacksync = 0;
                                    }
                                    $neededobject = $a;
                                    break;
                                }
                            }

                            // No activity found for this playback or if the recording is not activitated.
                            if (!$neededobject) {
                                continue;
                            }

                            // This playback is already synced for this activity.
                            if ($neededobject->playbacksync >= strtotime($p['CreationDate'])) {
                                continue;
                            }

                            // Sync all of this activity's playbacks after his own playbacksync value.
                            via_sync_activity_playbacks($neededobject);

                            // Set the playbacksync value in the array so that it isn't resync'd twice in the same call.
                            $activities[$neededobject->id]->playbacksync = time();
                        }
                    }
                }

                // Update playbacksync on each of the activities that didn't have any playbacks to sync.
                foreach ($activities as $a) {
                    if ($timesync > $a->playbacksync) {
                        $a->playbacksync = $timesync;
                        $DB->update_record("via", $a);
                    }
                }
            }
        }
    }

    /**
     * Convert the final raw grade(s) in the grading table for the gradebook.
     *
     * @param stdClass $grade
     * @return array
     */
    protected function convert_grade_for_gradebook(stdClass $grade) {
        $gradebookgrade = array();
        if ($grade->grade >= 0) {
            $gradebookgrade['rawgrade'] = $grade->grade;
        }
        // Allow "no grade" to be chosen.
        if ($grade->grade == -1) {
            $gradebookgrade['rawgrade'] = null;
        }
        $gradebookgrade['userid'] = $grade->userid;
        $gradebookgrade['usermodified'] = $grade->grader;
        $gradebookgrade['datesubmitted'] = null;
        $gradebookgrade['dategraded'] = $grade->timemodified;
        if (isset($grade->feedbackformat)) {
            $gradebookgrade['feedbackformat'] = $grade->feedbackformat;
        }
        if (isset($grade->feedbacktext)) {
            $gradebookgrade['feedback'] = $grade->feedbacktext;
        }

        return $gradebookgrade;
    }

    /**
     * Convert submission details for the gradebook.
     *
     * @param stdClass $submission
     * @return array
     */
    protected function convert_submission_for_gradebook(stdClass $submission) {
        $gradebookgrade = array();

        $gradebookgrade['userid'] = $submission->userid;
        $gradebookgrade['usermodified'] = $submission->userid;
        $gradebookgrade['datesubmitted'] = $submission->timemodified;

        return $gradebookgrade;
    }

    /**
     * Update grades in the gradebook.
     *
     * @param mixed $submission stdClass|null
     * @param mixed $grade stdClass|null
     * @return bool
     */
    protected function gradebook_item_update($submission=null, $grade=null) {
        global $CFG;

        require_once($CFG->dirroot.'/mod/viaassign/lib.php');

        if ($submission != null) {
            if ($submission->userid == 0) {
                // This is a group submission update.
                $team = groups_get_members($submission->groupid, 'u.id');

                foreach ($team as $member) {
                    $membersubmission = clone $submission;
                    $membersubmission->groupid = 0;
                    $membersubmission->userid = $member->id;
                    $this->gradebook_item_update($membersubmission, null);
                }
                return;
            }

            $gradebookgrade = $this->convert_submission_for_gradebook($submission);
        } else {
            $gradebookgrade = $this->convert_grade_for_gradebook($grade);
        }
        // Grading is disabled, return.
        if ($this->grading_disabled($gradebookgrade['userid'])) {
            return false;
        }
        $viaassign = clone $this->get_instance();
        $viaassign->cmidnumber = $this->get_course_module()->idnumber;
        // Set viaassign gradebook feedback plugin status (enabled and visible).
        $viaassign->gradefeedbackenabled = $this->is_gradebook_feedback_enabled();
        return viaassign_grade_item_update($viaassign, $gradebookgrade);
    }

    /**
     * Update grades in the gradebook based on submission time.
     *
     * @param stdClass $submission
     * @param int $userid
     * @param bool $updatetime
     * @return bool
     */
    protected function update_submission(stdClass $submission, $userid, $updatetime) {
        global $DB;

        if ($updatetime) {
            $submission->timemodified = time();
        }
        $result = $DB->update_record('viaassign_submission', $submission);
        if ($result) {
            $this->gradebook_item_update($submission);
        }
        return $result;
    }

    /**
     * Is this viaassignment open for submissions?
     *
     * Check the due date,
     * prevent late submissions,
     * has this person already submitted,
     * is the viaassignment locked?
     *
     * @param int $userid - Optional userid so we can see if a different user can submit
     * @param bool $skipenrolled - Skip enrollment checks (because they have been done already)
     * @param stdClass $submission - Pre-fetched submission record (or false to fetch it)
     * @param stdClass $flags - Pre-fetched user flags record (or false to fetch it)
     * @param stdClass $gradinginfo - Pre-fetched user gradinginfo record (or false to fetch it)
     * @return bool
     */
    public function submissions_open($userid = 0,
                                     $skipenrolled = false,
                                     $submission = false,
                                     $flags = false,
                                     $gradinginfo = false) {
        global $USER;

        if (!$userid) {
            $userid = $USER->id;
        }

        $time = time();
        $dateopen = true;
        $finaldate = false;

        if ($flags === false) {
            $flags = $this->get_user_flags($userid, false);
        }
        if ($flags && $flags->locked) {
            return false;
        }

        // User extensions.
        if ($finaldate) {
            if ($flags && $flags->extensionduedate) {
                // Extension can be before cut off date.
                if ($flags->extensionduedate > $finaldate) {
                    $finaldate = $flags->extensionduedate;
                }
            }
        }

        if ($finaldate) {
            $dateopen = ($this->get_instance()->allowsubmissionsfromdate <= $time && $time <= $finaldate);
        } else {
            $dateopen = ($this->get_instance()->allowsubmissionsfromdate <= $time);
        }

        if (!$dateopen) {
            return false;
        }

        // Now check if this user has already submitted etc.
        if (!$skipenrolled && !is_enrolled($this->get_course_context(), $userid)) {
            return false;
        }
        // Note you can pass null for submission and it will not be fetched.
        if ($submission === false) {
            $submission = $this->get_user_submission($userid, false);
        }

        // See if this user grade is locked in the gradebook.
        if ($gradinginfo === false) {
            $gradinginfo = grade_get_grades($this->get_course()->id,
                                            'mod',
                                            'viaassign',
                                            $this->get_instance()->id,
                                            array($userid));
        }
        if ($gradinginfo &&
                isset($gradinginfo->items[0]->grades[$userid]) &&
                $gradinginfo->items[0]->grades[$userid]->locked) {
            return false;
        }

        return true;
    }

    /**
     * Render the files in file area.
     *
     * @param string $component
     * @param string $area
     * @param int $submissionid
     * @return string
     */
    public function render_area_files($component, $area, $submissionid) {
        global $USER;

        $fs = get_file_storage();
        $browser = get_file_browser();
        $files = $fs->get_area_files($this->get_context()->id,
                                     $component,
                                     $area,
                                     $submissionid,
                                     'timemodified',
                                     false);
        return $this->get_renderer()->viaassign_files($this->context, $submissionid, $area, $component);
    }

    /**
     * Capability check to make sure this grader can edit this submission.
     *
     * @param int $userid - The user whose submission is to be edited
     * @param int $graderid (optional) - The user who will do the editing (default to $USER->id).
     * @return bool
     */
    public function can_edit_submission($userid, $graderid = 0) {
        global $USER;

        if (empty($graderid)) {
            $graderid = $USER->id;
        }

        if ($userid == $graderid && $this->submissions_open($userid) &&
                (has_capability('mod/viaassign:submit', $this->context, $graderid)
                || $this->can_create_via($USER->id, $this->get_instance()->userrole))) {

            // User can edit their own submission.
            return true;
        }

        $cm = $this->get_course_module();
        if (groups_get_activity_groupmode($cm) == SEPARATEGROUPS) {
            // These arrays are indexed by groupid.
            $studentgroups = array_keys(groups_get_activity_allowed_groups($cm, $userid));
            $gradergroups = array_keys(groups_get_activity_allowed_groups($cm, $graderid));

            return count(array_intersect($studentgroups, $gradergroups)) > 0;
        }
        return true;
    }

    /**
     * Returns a list of teachers that should be grading given submission.
     *
     * @param int $userid The submission to grade
     * @return array
     */
    protected function get_graders($userid) {
        // Potential graders should be active users only.
        $potentialgraders = get_enrolled_users($this->context, "mod/viaassign:grade", null, 'u.*', null, null, null, true);

        $graders = array();
        if (groups_get_activity_groupmode($this->get_course_module()) == SEPARATEGROUPS) {
            if ($groups = groups_get_all_groups($this->get_course()->id, $userid, $this->get_course_module()->groupingid)) {
                foreach ($groups as $group) {
                    foreach ($potentialgraders as $grader) {
                        if ($grader->id == $userid) {
                            // Do not send self.
                            continue;
                        }
                        if (groups_is_member($group->id, $grader->id)) {
                            $graders[$grader->id] = $grader;
                        }
                    }
                }
            } else {
                // User not in group, try to find graders without group.
                foreach ($potentialgraders as $grader) {
                    if ($grader->id == $userid) {
                        // Do not send self.
                        continue;
                    }
                    if (!groups_has_membership($this->get_course_module(), $grader->id)) {
                        $graders[$grader->id] = $grader;
                    }
                }
            }
        } else {
            foreach ($potentialgraders as $grader) {
                if ($grader->id == $userid) {
                    // Do not send self.
                    continue;
                }
                // Must be enrolled.
                if (is_enrolled($this->get_course_context(), $grader->id)) {
                    $graders[$grader->id] = $grader;
                }
            }
        }
        return $graders;
    }

    /**
     * Format a notification for plain text.
     *
     * @param string $messagetype
     * @param stdClass $info
     * @param stdClass $course
     * @param stdClass $context
     * @param string $modulename
     * @param string $viaassignmentname
     */
    protected static function format_notification_message_text($messagetype,
                                                             $info,
                                                             $course,
                                                             $context,
                                                             $modulename,
                                                             $viaassignmentname) {
        $formatparams = array('context' => $context->get_course_context());
        $posttext  = format_string($course->shortname, true, $formatparams) .
                     ' -> ' .
                     $modulename .
                     ' -> ' .
                     format_string($viaassignmentname, true, $formatparams) . "\n";
        $posttext .= '---------------------------------------------------------------------' . "\n";
        $posttext .= get_string($messagetype . 'text', 'viaassign', $info)."\n";
        $posttext .= "\n---------------------------------------------------------------------\n";
        return $posttext;
    }

    /**
     * Format a notification for HTML.
     *
     * @param string $messagetype
     * @param stdClass $info
     * @param stdClass $course
     * @param stdClass $context
     * @param string $modulename
     * @param stdClass $coursemodule
     * @param string $viaassignname
     */
    protected static function format_notification_message_html($messagetype,
                                                                $info,
                                                                $course,
                                                                $context,
                                                                $modulename,
                                                                $coursemodule,
                                                                $viaassignname) {
        global $CFG;
        $formatparams = array('context' => $context->get_course_context());
        $posthtml  = '<p><font face="sans-serif">' .
                     '<a href="' . $CFG->wwwroot . '/course/view.php?id=' . $course->id . '">' .
                     format_string($course->shortname, true, $formatparams) .
                     '</a> ->' .
                     '<a href="' . $CFG->wwwroot . '/mod/viaassign/index.php?id=' . $course->id . '">' .
                     $modulename .
                     '</a> ->' .
                     '<a href="' . $CFG->wwwroot . '/mod/viaassign/view.php?id=' . $coursemodule->id . '">' .
                     format_string($viaassignname, true, $formatparams) .
                     '</a></font></p>';
        $posthtml .= '<hr /><font face="sans-serif">';
        $posthtml .= '<p>' . get_string($messagetype . 'html', 'viaassign', $info) . '</p>';
        $posthtml .= '</font><hr />';
        return $posthtml;
    }

    /**
     * Message someone about something (static so it can be called from cron).
     *
     * @param stdClass $userfrom
     * @param stdClass $userto
     * @param string $messagetype
     * @param string $eventtype
     * @param int $updatetime
     * @param stdClass $coursemodule
     * @param stdClass $context
     * @param stdClass $course
     * @param string $modulename
     * @param string $viaassignname
     * @param bool $blindmarking
     * @param int $uniqueidforuser
     * @return void
     */
    public static function send_viaassign_notification($userfrom,
                                                        $userto,
                                                        $messagetype,
                                                        $eventtype,
                                                        $updatetime,
                                                        $coursemodule,
                                                        $context,
                                                        $course,
                                                        $modulename,
                                                        $viaassignname,
                                                        $uniqueidforuser) {
        global $CFG;

        $info = new stdClass();

        $info->username = fullname($userfrom, true);

        $info->viaassign = format_string($viaassignname, true, array('context' => $context));
        $info->url = $CFG->wwwroot.'/mod/viaassign/view.php?id='.$coursemodule->id;
        $info->timeupdated = userdate($updatetime, get_string('strftimerecentfull'));

        $postsubject = get_string($messagetype . 'small', 'viaassign', $info);
        $posttext = self::format_notification_message_text($messagetype,
                                                           $info,
                                                           $course,
                                                           $context,
                                                           $modulename,
                                                           $viaassignname);
        $posthtml = '';
        if ($userto->mailformat == 1) {
            $posthtml = self::format_notification_message_html($messagetype,
                                                               $info,
                                                               $course,
                                                               $context,
                                                               $modulename,
                                                               $coursemodule,
                                                               $viaassignname);
        }

        $eventdata = new stdClass();
        $eventdata->modulename       = 'viaassign';
        $eventdata->userfrom         = $userfrom;
        $eventdata->userto           = $userto;
        $eventdata->subject          = $postsubject;
        $eventdata->fullmessage      = $posttext;
        $eventdata->fullmessageformat = FORMAT_PLAIN;
        $eventdata->fullmessagehtml  = $posthtml;
        $eventdata->smallmessage     = $postsubject;

        $eventdata->name            = $eventtype;
        $eventdata->component       = 'mod_viaassign';
        $eventdata->notification    = 1;
        $eventdata->contexturl      = $info->url;
        $eventdata->contexturlname  = $info->viaassign;

        message_send($eventdata);
    }

    /**
     * Message someone about something.
     *
     * @param stdClass $userfrom
     * @param stdClass $userto
     * @param string $messagetype
     * @param string $eventtype
     * @param int $updatetime
     * @return void
     */
    public function send_notification($userfrom,
                                      $userto,
                                      $messagetype,
                                      $eventtype,
                                      $updatetime) {
        self::send_viaassign_notification($userfrom,
                                           $userto,
                                           $messagetype,
                                           $eventtype,
                                           $updatetime,
                                           $this->get_course_module(),
                                           $this->get_context(),
                                           $this->get_course(),
                                           $this->get_module_name(),
                                           $this->get_instance()->name,
                                           $this->get_uniqueid_for_user($userfrom->id));
    }

    /**
     * Send notifications to graders upon student submissions.
     *
     * @param stdClass $submission
     * @return void
     */
    protected function notify_graders(stdClass $submission) {
        global $DB, $USER;

        $instance = $this->get_instance();

        $late = $instance->duedate && ($instance->duedate < time());

        if (!$instance->sendnotifications) {
            // No need to do anything.
            return;
        }

        if ($submission->userid) {
            $user = $DB->get_record('user', array('id' => $submission->userid), '*', MUST_EXIST);
        } else {
            $user = $USER;
        }
        if ($teachers = $this->get_graders($user->id)) {
            foreach ($teachers as $teacher) {
                $this->send_notification($user,
                                         $teacher,
                                         'gradersubmission',
                                         'viaassign_notification',
                                         $submission->timemodified);
            }
        }
    }

    /**
     * Save the extension date for a single user.
     *
     * @param int $userid The user id
     * @param mixed $extensionduedate Either an integer date or null
     * @return boolean
     */
    public function save_user_extension($userid, $extensionduedate) {
        global $DB, $USER;

        // Need submit permission to submit an viaassignment.
        require_capability('mod/viaassign:grantextension', $this->context);

        if (!is_enrolled($this->get_course_context(), $userid)) {
            return false;
        }

        if ($this->get_instance()->duedate && $extensionduedate) {
            if ($this->get_instance()->duedate > $extensionduedate) {
                return false;
            }
        }
        if ($this->get_instance()->allowsubmissionsfromdate && $extensionduedate) {
            if ($this->get_instance()->allowsubmissionsfromdate > $extensionduedate) {
                return false;
            }
        }

        $flags = $this->get_user_flags($userid, true);
        $flags->extensionduedate = $extensionduedate;

        $result = $this->update_user_flags($flags);

        if ($result) {
            \mod_viaassign\event\extension_granted::create_from_viaassign($this, $userid)->trigger();
        }
        return $result;
    }

    /**
     * Save extension date.
     *
     * @param moodleform $mform The submitted form
     * @return boolean
     */
    protected function process_save_extension(&$mform) {
        global $DB, $CFG;

        // Include extension form.
        require_once($CFG->dirroot . '/mod/viaassign/extensionform.php');
        require_sesskey();

        $batchusers = optional_param('selectedusers', '', PARAM_SEQUENCE);
        $userid = 0;
        if (!$batchusers) {
            $userid = required_param('userid', PARAM_INT);
            $user = $DB->get_record('user', array('id' => $userid), '*', MUST_EXIST);
        }
        $mform = new mod_viaassign_extension_form(null, array($this->get_course_module()->id,
                                                           $userid,
                                                           $batchusers,
                                                           $this->get_instance(),
                                                           null));

        if ($mform->is_cancelled()) {
            return true;
        }

        if ($formdata = $mform->get_data()) {
            if ($batchusers) {
                $users = explode(',', $batchusers);
                $result = true;
                foreach ($users as $userid) {
                    $result = $this->save_user_extension($userid, $formdata->extensionduedate) && $result;
                }
                return $result;
            } else {
                return $this->save_user_extension($userid, $formdata->extensionduedate);
            }
        }
        return false;
    }

    /**
     * Save quick grades.
     *
     * @return string The result of the save operation
     */
    protected function process_save_quick_grades() {
        global $USER, $DB, $CFG;

        // Need grade permission.
        require_capability('mod/viaassign:grade', $this->context);
        require_sesskey();

        // Make sure advanced grading is disabled.
        $gradingmanager = get_grading_manager($this->get_context(), 'mod_viaassign', 'submissions');
        $controller = $gradingmanager->get_active_controller();
        if (!empty($controller)) {
            return get_string('errorquickgradingvsadvancedgrading', 'viaassign');
        }

        $users = array();
        // First check all the last modified values.

        $participants = $this->list_participants(false, true);

        // Gets a list of possible users and look for values based upon that.
        foreach ($participants as $userid => $unused) {
            $modified = optional_param('grademodified_' . $userid, -1, PARAM_INT);
            $viaid = optional_param('viamodified_' . $userid, -1, PARAM_INT);
            $newgrade = unformat_float(optional_param('quickgrade_' . $userid, null, PARAM_TEXT));
            $newcomment = optional_param('quickgrade_comments_' . $userid, null, PARAM_TEXT);

            // Gather the userid, updated grade and last modified value.
            $record = new stdClass();
            $record->userid = $userid;
            $record->viaid = $viaid;
            $record->lastmodified = $modified;

            $oldgrade = $this->get_user_grade($userid, false, $viaid);

            if (isset($newgrade)) {
                if ($oldgrade == false || isset($newgrade) && $oldgrade->grade != $newgrade) {
                    $record->grade = $newgrade;
                    $modifiedusers[$userid] = $record;
                }
            }

            if ($newcomment) {
                if ($oldgrade == false) {
                    // Create an empty grade = we need the id!
                    $grade = new stdClass();
                    $grade->viaassign = $this->coursemodule->instance;
                    $grade->userid = $userid;
                    $grade->viaid = $viaid;
                    $grade->timecreated = time();
                    $grade->timemodified = time();
                    $grade->grader = $USER->id;

                    $grade = $DB->insert_record('viaassign_grades', $grade);

                    $modifiedusers[$userid] = $record;
                } else {
                    // Impossible to have a comment without a grade!
                    $oldcomment = $DB->get_record('viaassignfeedback_comments',
                        array('viaassign' => $this->coursemodule->instance, 'grade' => $oldgrade->id));
                    if ((isset($oldcomment) && $oldcomment->commenttext != $newcomment) || !$oldcomment) {
                        $modifiedusers[$userid] = $record;
                    }
                }
            } else {
                // What if the comment was deleted/erased!
                // Impossible to have a comment without a grade!
                if ($oldgrade) {
                    $oldcomment = $DB->get_record('viaassignfeedback_comments',
                        array('viaassign' => $this->coursemodule->instance, 'grade' => $oldgrade->id));
                    if ($oldcomment) {
                        $modifiedusers[$userid] = $record;
                    }
                }
            }

        }

        if (isset($modifiedusers)) {
            $adminconfig = $this->get_admin_config();
            $gradebookplugin = $adminconfig->feedback_plugin_for_gradebook;

            // Ok - ready to process the updates.
            foreach ($modifiedusers as $userid => $modified) {

                $grade = $this->get_user_grade($userid, false, $modified->viaid);
                if (!$grade) {
                    $grade = $this->get_user_grade($userid, true, $modified->viaid);
                }

                $flags = $this->get_user_flags($userid, true);
                if (isset($modified->grade)) {
                    $grade->grade = grade_floatval(unformat_float($modified->grade));
                }
                $grade->grader = $USER->id;
                $gradecolpresent = optional_param('quickgrade_' . $userid, false, PARAM_INT) !== false;

                // Save plugins data.
                foreach ($this->feedbackplugins as $plugin) {
                    if ($plugin->is_visible() && $plugin->is_enabled() && $plugin->supports_quickgrading()) {
                        $plugin->save_quickgrading_changes($userid, $grade);
                        if (('viaassignfeedback_' . $plugin->get_type()) == $gradebookplugin) {
                            // This is the feedback plugin chose to push comments to the gradebook.
                            $grade->feedbacktext = $plugin->text_for_gradebook($grade);
                            $grade->feedbackformat = $plugin->format_for_gradebook($grade);
                        }
                    }
                }

                $this->update_grade($grade);
                // Allow teachers to skip sending notifications.
                if (optional_param('sendstudentnotifications', true, PARAM_BOOL)) {
                    $this->notify_grade_modified($grade);
                }

                // Save outcomes.
                if ($CFG->enableoutcomes) {
                    $data = array();
                    foreach ($modified->gradinginfo->outcomes as $outcomeid => $outcome) {
                        $oldoutcome = $outcome->grades[$modified->userid]->grade;
                        $paramname = 'outcome_' . $outcomeid . '_' . $modified->userid;
                        // This will be false if the input was not in the quickgrading
                        // form (e.g. column hidden).
                        $newoutcome = optional_param($paramname, false, PARAM_INT);
                        if ($newoutcome !== false && ($oldoutcome != $newoutcome)) {
                            $data[$outcomeid] = $newoutcome;
                        }
                    }
                    if (count($data) > 0) {
                        grade_update_outcomes('mod/viaassign',
                            $this->course->id,
                            'mod',
                            'viaassign',
                            $this->get_instance()->id,
                            $userid,
                            $data);
                    }
                }
            }

            return get_string('quickgradingchangessaved', 'viaassign');
        } else {
            return get_string('quickgradingchangesnotsaved', 'viaassign');
        }
    }

    /**
     * Save grading options.
     *
     * @return void
     */
    protected function process_save_grading_options() {
        global $USER, $CFG;

        // Include grading options form.
        require_once($CFG->dirroot . '/mod/viaassign/gradingoptionsform.php');

        // Need submit permission to submit an viaassignment.
        require_capability('mod/viaassign:grade', $this->context);
        require_sesskey();

        // Is advanced grading enabled?
        $gradingmanager = get_grading_manager($this->get_context(), 'mod_viaassign', 'submissions');
        $controller = $gradingmanager->get_active_controller();
        $showquickgrading = empty($controller);
        if (!is_null($this->context)) {
            $showonlyactiveenrolopt = has_capability('moodle/course:viewsuspendedusers', $this->context);
        } else {
            $showonlyactiveenrolopt = false;
        }

        $gradingoptionsparams = array('cm' => $this->get_course_module()->id,
                                      'contextid' => $this->context->id,
                                      'userid' => $USER->id,
                                      'submissionsenabled' => $this->is_any_submission_plugin_enabled(),
                                      'showquickgrading' => $showquickgrading,
                                      'quickgrading' => false,
                                      'showonlyactiveenrolopt' => $showonlyactiveenrolopt,
                                      'showonlyactiveenrol' => $this->show_only_active_users());

        $mform = new mod_viaassign_grading_options_form(null, $gradingoptionsparams);
        if ($formdata = $mform->get_data()) {
            set_user_preference('viaassign_perpage', $formdata->perpage);
            if (isset($formdata->filter)) {
                set_user_preference('viaassign_filter', $formdata->filter);
            }
            if (isset($formdata->markerfilter)) {
                set_user_preference('viaassign_markerfilter', $formdata->markerfilter);
            }
            if ($showquickgrading) {
                set_user_preference('viaassign_quickgrading', isset($formdata->quickgrading));
            }
            if (!empty($showonlyactiveenrolopt)) {
                $showonlyactiveenrol = isset($formdata->showonlyactiveenrol);
                set_user_preference('grade_report_showonlyactiveenrol', $showonlyactiveenrol);
                $this->showonlyactiveenrol = $showonlyactiveenrol;
            }
        }
    }

    /**
     * Take a grade object and print a short summary for the log file.
     * The size limit for the log file is 255 characters, so be careful not
     * to include too much information.
     *
     * @deprecated since 2.7
     *
     * @param stdClass $grade
     * @return string
     */
    public function format_grade_for_log(stdClass $grade) {
        global $DB;

        $user = $DB->get_record('user', array('id' => $grade->userid), '*', MUST_EXIST);

        $info = get_string('gradestudent', 'viaassign', array('id' => $user->id, 'fullname' => fullname($user)));
        if ($grade->grade != '') {
            $info .= get_string('grade') . ': ' . $this->display_grade($grade->grade, false) . '. ';
        } else {
            $info .= get_string('nograde', 'viaassign');
        }
        return $info;
    }

    /**
     * Take a submission object and print a short summary for the log file.
     * The size limit for the log file is 255 characters, so be careful not
     * to include too much information.
     *
     * @deprecated since 2.7
     *
     * @param stdClass $submission
     * @return string
     */
    public function format_submission_for_log(stdClass $submission) {
        global $DB;

        $info = '';
        if ($submission->userid) {
            $user = $DB->get_record('user', array('id' => $submission->userid), '*', MUST_EXIST);
            $name = fullname($user);
        } else {
            $group = $DB->get_record('groups', array('id' => $submission->groupid), '*', MUST_EXIST);
            $name = $group->name;
        }
        $status = get_string('submissionstatus_' . $submission->status, 'viaassign');
        $params = array('id' => $submission->userid, 'fullname' => $name, 'status' => $status);
        $info .= get_string('submissionlog', 'viaassign', $params) . ' <br>';

        foreach ($this->submissionplugins as $plugin) {
            if ($plugin->is_enabled() && $plugin->is_visible()) {
                $info .= '<br>' . $plugin->format_for_log($submission);
            }
        }

        return $info;
    }


    protected function process_delete_submission($submissionid, $viaid) {
        global $CFG, $USER, $DB;

        require_once($CFG->dirroot . '/mod/via/lib.php');
        $result = true;

        try {
            // Delete via in table via and in via portal.
            // Delete participants associated with the via via_participants.
            $deleted = via_delete_instance($viaid);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        // Delete comments before grade!
        $grades = $DB->get_records('viaassign_grades', array('viaid' => $viaid));
        foreach ($grades as $g) {
            $DB->delete_records('viaassignfeedback_comments', array('grade' => $g->id));
        }
        if (!$DB->delete_records('viaassign_grades', array('viaid' => $viaid))) {
            $result = false;
        }
        if (!$DB->delete_records('viaassign_submission', array('id' => $submissionid))) {
            $result = false;
        }

        $eventdata = array(
            'objectid' => $viaid,
            'context' => $this->context,
            'userid' => $USER->id
            );
        $event = \mod_viaassign\event\submission_deleted::create($eventdata);
        $event->trigger();

        return $result;
    }

    /**
     * This is method process_save_via
     *
     * @param protected $mform This is a description
     * @return mixed This is the return value description
     *
     */
    protected function process_save_via(&$mform, $viaid) {
        global $CFG, $USER, $DB, $OUTPUT;

        // Include submission form.
        require_once($CFG->dirroot . '/mod/viaassign/via_form.php');

        $formparams = array('va' => $this->get_instance(),
                            'cmid' => $this->get_context()->instanceid);

        if ($viaid) {
                $viaactivity = $DB->get_record('via', array('id' => $viaid));
                $viaactivity->viaid = $viaactivity->id;
                $formparams['viaactivity'] = $viaactivity;
        }

        $mform = new via_form(null, $formparams, 'post', '', array('class' => 'test'));

        if ($mform->is_cancelled()) {
            // We pressed cancel we need to go back to main page!
            redirect($CFG->wwwroot . '/mod/viaassign/view.php?id='.$this->context->instanceid);
        }

        $formdata = $mform->get_data();

        if (!$formdata) {
            echo $OUTPUT->header();
            $mform->display();
            echo $OUTPUT->footer();
            exit;
        } else {
            if (!$viaid) {
                try {
                    $saved = $this->save_via($formdata);
                    if (strpos($saved, 'error') == 'false') {
                            throw new Exception($saved);
                    } else {
                            return true;
                    }
                } catch (Exception $e) {
                    return $e->getMessage();
                }
            } else {
                try {
                    $this->update_via($formdata);
                    return true;
                } catch (Exception $e) {
                    return $e->getMessage();
                }
            }
        }

            return true;
    }

    /**
     * This is method save_via
     *
     * @param public $formdata via data to be saved
     * @return mixed true/false or error
     *
     */
    public function save_via($formdata) {
        global $CFG, $USER, $DB;

        require_once($CFG->dirroot . '/mod/via/lib.php');

        try {
            // For intro with wysiwyg!
            $temp = clean_text($formdata->intro_editor['text'], FORMAT_HTML);
            unset($formdata->intro_editor);
            $formdata->intro = $temp;

            $newviaid = via_add_instance($formdata);

            if ($newviaid) {
                $submission = new stdClass();
                $submission->viaassignid = $formdata->viaassignid;
                $submission->viaid = $newviaid;
                $submission->userid = $USER->id;
                $submission->timecreated = time();
                $submission->timemodified = time();
                $submission->status = 'created';
                if (isset( $formdata->groupid)) {
                    $submission->groupid = $formdata->groupid;
                } else {
                    $submission->groupid = 0;
                    }
                try {
                    $submission = $DB->insert_record('viaassign_submission', $submission);
                    if ($submission) {
                        $sub = $DB->get_record('viaassign_submission', array('id' => $submission));
                        $this->notify_graders($sub);
                    }

                    $eventdata = array(
                        'objectid' => $newviaid,
                        'context' => $this->context,
                        'userid' => $USER->id
                        );
                    $event = \mod_viaassign\event\submission_created::create($eventdata);
                    $event->trigger();

                    return true;

                } catch (Exception $e) {
                    return $e->getMessage();
                }
            }
        } catch (Exception $e) {
            return $e->getMessage();
        }

        return false;
    }

    public function update_via($formdata) {
        global $CFG, $USER, $DB;

        require_once($CFG->dirroot . '/mod/via/lib.php');

        try {
            // Set all values needed to update Via!
            $formdata->id = $formdata->viaid;
            $formdata->ish264 = 1;
            // For intro with wysiwyg!
            $temp = clean_text($formdata->intro_editor['text'], FORMAT_HTML);
            unset($formdata->intro_editor);
            $formdata->intro = $temp;
            $formdata->save_host = '';

            $updated = via_update_instance($formdata);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        if ($updated) {
            // Get submission and update time modified.
            $submission = $DB->get_record('viaassign_submission', array('viaid' => $formdata->viaid));

            $submission->timemodified = time();

            $submission = $DB->update_record('viaassign_submission', $submission);
        }

        $eventdata = array(
            'objectid' => $formdata->viaid,
            'context' => $this->context,
            'userid' => $USER->id
            );
        $event = \mod_viaassign\event\submission_updated::create($eventdata);
        $event->trigger();

        return true;
    }

    /**
     * Determine if this users grade can be edited.
     *
     * @param int $userid - The student userid
     * @return bool $gradingdisabled
     */
    public function grading_disabled($userid) {
        global $CFG;

        $gradinginfo = grade_get_grades($this->get_course()->id,
                                        'mod',
                                        'viaassign',
                                        $this->get_instance()->id,
                                        array($userid));
        if (!$gradinginfo) {
            return false;
        }

        if (!isset($gradinginfo->items[0]->grades[$userid])) {
            return false;
        }
        $gradingdisabled = $gradinginfo->items[0]->grades[$userid]->locked ||
                           $gradinginfo->items[0]->grades[$userid]->overridden;
        return $gradingdisabled;
    }

    /**
     * Get an instance of a grading form if advanced grading is enabled.
     * This is specific to the viaassignment, marker and student.
     *
     * @param int $userid - The student userid
     * @param stdClass|false $grade - The grade record
     * @param bool $gradingdisabled
     * @return mixed gradingform_instance|null $gradinginstance
     */
    protected function get_grading_instance($userid, $grade, $gradingdisabled) {
        global $CFG, $USER;

        $grademenu = make_grades_menu($this->get_instance()->grade);
        $allowgradedecimals = $this->get_instance()->grade > 0;

        $advancedgradingwarning = false;
        $gradingmanager = get_grading_manager($this->context, 'mod_viaassign', 'submissions');
        $gradinginstance = null;
        if ($gradingmethod = $gradingmanager->get_active_method()) {
            $controller = $gradingmanager->get_controller($gradingmethod);
            if ($controller->is_form_available()) {
                $itemid = null;
                if ($grade) {
                    $itemid = $grade->id;
                }
                if ($gradingdisabled && $itemid) {
                    $gradinginstance = $controller->get_current_instance($USER->id, $itemid);
                } else if (!$gradingdisabled) {
                    $instanceid = optional_param('advancedgradinginstanceid', 0, PARAM_INT);
                    $gradinginstance = $controller->get_or_create_instance($instanceid,
                                                                           $USER->id,
                                                                           $itemid);
                }
            } else {
                $advancedgradingwarning = $controller->form_unavailable_notification();
            }
        }
        if ($gradinginstance) {
            $gradinginstance->get_controller()->set_grade_range($grademenu, $allowgradedecimals);
        }
        return $gradinginstance;
    }

    /**
     * Add elements to grade form.
     *
     * @param MoodleQuickForm $mform
     * @param stdClass $data
     * @param array $params
     * @return void
     */
    public function add_grade_form_elements(MoodleQuickForm $mform, stdClass $data, $params) {
        global $USER, $CFG, $DB;
        $settings = $this->get_instance();

        $rownum = $params['rownum'];
        $last = $params['last'];
        $useridlistid = $params['useridlistid'];
        $userid = $params['userid'];
        $viaid = $params['viaid'];
        $cache = cache::make_from_params(cache_store::MODE_SESSION, 'mod_viaassign', 'useridlist');

        if (!$useridlist = $cache->get($this->get_course_module()->id . '_' . $useridlistid)) {
            $useridlist = $this->get_grading_userid_list();
            $cache->set($this->get_course_module()->id . '_' . $useridlistid, $useridlist);
        }

        $grade = $this->get_user_grade($userid, false, $viaid);
        if (!$grade) {
            $grade = $this->get_user_grade($userid, false, 0);
            if ($grade) {
                // A via activity was created since the comment was added, so we update the line!
                $grade->viaid = $viaid;
                $DB->update_record('viaassign_grades', $grade);
            }
        }

        // Add advanced grading.
        $gradingdisabled = $this->grading_disabled($userid);
        $gradinginstance = $this->get_grading_instance($userid, $grade, $gradingdisabled);
        $via = $DB->get_record('via', array('id' => $viaid));
        if ($via) {
            $header = $via->name;
        } else {
            $header = get_string('novccreated', 'viaassign');
        }

        $mform->addElement('header', 'gradeheader', get_string('gradeheader', 'viaassign') . ' : ' . $header);
        if ($gradinginstance) {
            $gradingelement = $mform->addElement('grading',
                                                 'advancedgrading',
                                                 get_string('grade').':',
                                                 array('gradinginstance' => $gradinginstance));
            if ($gradingdisabled) {
                $gradingelement->freeze();
            } else {
                $mform->addElement('hidden', 'advancedgradinginstanceid', $gradinginstance->get_id());
                $mform->setType('advancedgradinginstanceid', PARAM_INT);
            }
        } else {
            // Use simple direct grading.
            if ($this->get_instance()->grade > 0) {
                $name = get_string('gradeoutof', 'viaassign', $this->get_instance()->grade);
                if (!$gradingdisabled) {
                    $gradingelement = $mform->addElement('text', 'grade', $name);
                    $mform->addHelpButton('grade', 'gradeoutofhelp', 'viaassign');
                    $mform->setType('grade', PARAM_RAW);
                } else {
                    $mform->addElement('hidden', 'grade', $name);
                    $mform->hardFreeze('grade');
                    $mform->setType('grade', PARAM_RAW);
                    $strgradelocked = get_string('gradelocked', 'viaassign');
                    $mform->addElement('static', 'gradedisabled', $name, $strgradelocked);
                    $mform->addHelpButton('gradedisabled', 'gradeoutofhelp', 'viaassign');
                }
            } else {
                $grademenu = array(-1 => get_string("nograde")) + make_grades_menu($this->get_instance()->grade);
                if (count($grademenu) > 1) {
                    $gradingelement = $mform->addElement('select', 'grade', get_string('grade') . ':', $grademenu);

                    // The grade is already formatted with format_float so it needs to be converted back to an integer.
                    if (!empty($data->grade)) {
                        $data->grade = (int)unformat_float($data->grade);
                    }
                    $mform->setType('grade', PARAM_INT);
                    if ($gradingdisabled) {
                        $gradingelement->freeze();
                    }
                }
            }
        }

        $gradinginfo = grade_get_grades($this->get_course()->id,
                                        'mod',
                                        'viaassign',
                                        $this->get_instance()->id,
                                        $userid);

        if (!empty($CFG->enableoutcomes)) {
            foreach ($gradinginfo->outcomes as $index => $outcome) {
                $options = make_grades_menu(-$outcome->scaleid);
                if ($outcome->grades[$userid]->locked) {
                    $options[0] = get_string('nooutcome', 'grades');
                    $mform->addElement('static',
                                       'outcome_' . $index . '[' . $userid . ']',
                                       $outcome->name . ':',
                                       $options[$outcome->grades[$userid]->grade]);
                } else {
                    $options[''] = get_string('nooutcome', 'grades');
                    $attributes = array('id' => 'menuoutcome_' . $index );
                    $mform->addElement('select',
                                       'outcome_' . $index . '[' . $userid . ']',
                                       $outcome->name.':',
                                       $options,
                                       $attributes);
                    $mform->setType('outcome_' . $index . '[' . $userid . ']', PARAM_INT);
                    $mform->setDefault('outcome_' . $index . '[' . $userid . ']',
                                       $outcome->grades[$userid]->grade);
                }
            }
        }

        $capabilitylist = array('gradereport/grader:view', 'moodle/grade:viewall');
        if (has_all_capabilities($capabilitylist, $this->get_course_context())) {
            $urlparams = array('id' => $this->get_course()->id);
            $url = new moodle_url('/grade/report/grader/index.php', $urlparams);
            $usergrade = '-';
            if (isset($grade) && $grade != false) {
                if ($grade->grade != '-1.00000') {
                    $usergrade = format_float($grade->grade, 2);
                }
            }
            $gradestring = $this->get_renderer()->action_link($url, $usergrade);
        } else {
            $usergrade = '-';
            if (isset($grade) && $grade != false) {
                if ($grade->grade != '-1.00000') {
                    $usergrade = format_float($grade->grade, 2);
                }
            }
            $gradestring = $usergrade;
        }

        if ($this->instance->grade != 0) {
            $mform->addElement('static', 'currentgrade', get_string('currentgrade', 'viaassign'), $gradestring);
        }

        if (count($useridlist) > 1) {
            $strparams = array('current' => $rownum + 1, 'total' => count($useridlist));
            $name = get_string('outof', 'viaassign', $strparams);
            $mform->addElement('static', 'gradingstudent', get_string('gradingstudent', 'viaassign'), $name);
        }

        // Let feedback plugins add elements to the grading form.
        $this->add_plugin_grade_elements($grade, $mform, $data, $userid);

        // Hidden params.
        $mform->addElement('hidden', 'id', $this->get_course_module()->id);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'viaid', $viaid);
        $mform->setType('viaid', PARAM_INT);
        $mform->addElement('hidden', 'rownum', $rownum);
        $mform->setType('rownum', PARAM_INT);
        $mform->setConstant('rownum', $rownum);
        $mform->addElement('hidden', 'useridlistid', $useridlistid);
        $mform->setType('useridlistid', PARAM_INT);
        $mform->addElement('hidden', 'ajax', optional_param('ajax', 0, PARAM_INT));
        $mform->setType('ajax', PARAM_INT);

        $mform->addElement('selectyesno', 'sendstudentnotifications', get_string('sendstudentnotifications', 'viaassign'));
        $mform->setDefault('sendstudentnotifications', $this->get_instance()->sendstudentnotifications);

        $mform->addElement('hidden', 'action', 'submitgrade');
        $mform->setType('action', PARAM_ALPHA);

        $buttonarray = array();
        $name = get_string('savechanges', 'viaassign');
        $buttonarray[] = $mform->createElement('submit', 'savegrade', $name);
        if (!$last) {
            $name = get_string('savenext', 'viaassign');
            $buttonarray[] = $mform->createElement('submit', 'saveandshownext', $name);
        }
        $buttonarray[] = $mform->createElement('cancel', 'cancelbutton', get_string('cancel'));
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
        $buttonarray = array();

        if ($rownum > 0) {
            $name = get_string('previous', 'viaassign');
            $buttonarray[] = $mform->createElement('submit', 'nosaveandprevious', $name);
        }

        if (!$last) {
            $name = get_string('nosavebutnext', 'viaassign');
            $buttonarray[] = $mform->createElement('submit', 'nosaveandnext', $name);
        }
        if (!empty($buttonarray)) {
            $mform->addGroup($buttonarray, 'navar', '', array(' '), false);
        }
        // The grading form does not work well with shortforms.
        $mform->setDisableShortforms();
    }

    /**
     * Add elements in submission plugin form.
     *
     * @param mixed $submission stdClass|null
     * @param MoodleQuickForm $mform
     * @param stdClass $data
     * @param int $userid The current userid (same as $USER->id)
     * @return void
     */
    protected function add_plugin_submission_elements($submission,
                                                    MoodleQuickForm $mform,
                                                    stdClass $data,
                                                    $userid) {
        foreach ($this->submissionplugins as $plugin) {
            if ($plugin->is_enabled() && $plugin->is_visible() && $plugin->allow_submissions()) {
                $plugin->get_form_elements_for_user($submission, $mform, $data, $userid);
            }
        }
    }

    /**
     * Check if feedback plugins installed are enabled.
     *
     * @return bool
     */
    public function is_any_feedback_plugin_enabled() {
        if (!isset($this->cache['any_feedback_plugin_enabled'])) {
            $this->cache['any_feedback_plugin_enabled'] = false;
            foreach ($this->feedbackplugins as $plugin) {
                if ($plugin->is_enabled() && $plugin->is_visible()) {
                    $this->cache['any_feedback_plugin_enabled'] = true;
                    break;
                }
            }
        }

        return $this->cache['any_feedback_plugin_enabled'];
    }

    /**
     * Check if submission plugins installed are enabled.
     *
     * @return bool
     */
    public function is_any_submission_plugin_enabled() {
        $this->cache['any_submission_plugin_enabled'] = true;

        return $this->cache['any_submission_plugin_enabled'];
    }

    /**
     * Prevent student updates to this submission
     *
     * @param int $userid
     * @return bool
     */
    public function lock_submission($userid) {
        global $USER, $DB;

        // Need grade permission.
        require_capability('mod/viaassign:grade', $this->context);

        $flags = $this->get_user_flags($userid, true);
        $flags->locked = 1;
        $this->update_user_flags($flags);

        $user = $DB->get_record('user', array('id' => $userid), '*', MUST_EXIST);

        \mod_viaassign\event\submission_locked::create_from_user($this, $user)->trigger();

        return true;
    }

    /**
     * Prevent student updates to this submission.
     * Uses url parameter userid.
     *
     * @param int $userid
     * @return void
     */
    protected function process_lock_submission($userid = 0) {
        require_sesskey();

        if (!$userid) {
            $userid = required_param('userid', PARAM_INT);
        }

        return $this->lock_submission($userid);
    }

    /**
     * Unlock the student submission.
     *
     * @param int $userid
     * @return bool
     */
    public function unlock_submission($userid) {
        global $USER, $DB;

        // Need grade permission.
        require_capability('mod/viaassign:grade', $this->context);

        $flags = $this->get_user_flags($userid, true);
        $flags->locked = 0;
        $this->update_user_flags($flags);

        $user = $DB->get_record('user', array('id' => $userid), '*', MUST_EXIST);
        \mod_viaassign\event\submission_unlocked::create_from_user($this, $user)->trigger();
        return true;
    }

    /**
     * Unlock the student submission.
     * Uses url parameter userid.
     *
     * @param int $userid
     * @return bool
     */
    protected function process_unlock_submission($userid = 0) {
        require_sesskey();

        if (!$userid) {
            $userid = required_param('userid', PARAM_INT);
        }

        return $this->unlock_submission($userid);
    }

    /**
     * Apply a grade from a grading form to a user (may be called multiple times for a group submission).
     *
     * @param stdClass $formdata - the data from the form
     * @param int $userid - the user to apply the grade to
     * @param int $viaid
     * @return void
     */
    protected function apply_grade_to_user($formdata, $userid, $viaid) {
        global $USER, $CFG, $DB;

        $grade = $this->get_user_grade($userid, true, $viaid);
        $gradingdisabled = $this->grading_disabled($userid);
        $gradinginstance = $this->get_grading_instance($userid, $grade, $gradingdisabled);
        if (!$gradingdisabled) {
            if ($gradinginstance) {
                $grade->grade = $gradinginstance->submit_and_get_grade($formdata->advancedgrading,
                                                                       $grade->id);
            } else {
                // Handle the case when grade is set to No Grade.
                if (isset($formdata->grade)) {
                    $grade->grade = grade_floatval(unformat_float($formdata->grade));
                }
            }
        }
        $grade->grader = $USER->id;

        $adminconfig = $this->get_admin_config();
        $gradebookplugin = $adminconfig->feedback_plugin_for_gradebook;

        // Call save in plugins.
        foreach ($this->feedbackplugins as $plugin) {
            if ($plugin->is_enabled() && $plugin->is_visible()) {
                if (!$plugin->save($grade, $formdata)) {
                    $result = false;
                    print_error($plugin->get_error());
                }
                if (('viaassignfeedback_' . $plugin->get_type()) == $gradebookplugin) {
                    // This is the feedback plugin chose to push comments to the gradebook.
                    $grade->feedbacktext = $plugin->text_for_gradebook($grade);
                    $grade->feedbackformat = $plugin->format_for_gradebook($grade);
                }
            }
        }
        $this->update_grade($grade);
        // Note the default if not provided for this option is true (e.g. webservices).
        // This is for backwards compatibility.
        if (!isset($formdata->sendstudentnotifications) || $formdata->sendstudentnotifications) {
            $this->notify_grade_modified($grade);
        }
    }

    /**
     * Save outcomes submitted from grading form
     *
     * @param int $userid
     * @param stdClass $formdata
     */
    protected function process_outcomes($userid, $formdata) {
        global $CFG, $USER;

        if (empty($CFG->enableoutcomes)) {
            return;
        }
        if ($this->grading_disabled($userid)) {
            return;
        }

        require_once($CFG->libdir.'/gradelib.php');

        $data = array();
        $gradinginfo = grade_get_grades($this->get_course()->id,
                                        'mod',
                                        'viaassign',
                                        $this->get_instance()->id,
                                        $userid);

        if (!empty($gradinginfo->outcomes)) {
            foreach ($gradinginfo->outcomes as $index => $oldoutcome) {
                $name = 'outcome_'.$index;
                if (isset($formdata->{$name}[$userid]) &&
                        $oldoutcome->grades[$userid]->grade != $formdata->{$name}[$userid]) {
                    $data[$index] = $formdata->{$name}[$userid];
                }
            }
        }
        if (count($data) > 0) {
            grade_update_outcomes('mod/viaassign',
                                  $this->course->id,
                                  'mod',
                                  'viaassign',
                                  $this->get_instance()->id,
                                  $userid,
                                  $data);
        }
    }

    /**
     * Save grade update.
     *
     * @param int $userid
     * @param  stdClass $data
     * @return bool - was the grade saved
     */
    public function save_grade($userid, $data) {
        // Need grade permission.
        require_capability('mod/viaassign:grade', $this->context);

        $instance = $this->get_instance();

        $submission = $this->get_user_submission($userid, false, $data->viaid);

        $this->apply_grade_to_user($data, $userid, $data->viaid);

        $this->process_outcomes($userid, $data);

        $shouldreopen = false;

        return true;
    }

    /**
     * Save grade.
     *
     * @param  moodleform $mform
     * @return bool - was the grade saved
     */
    protected function process_save_grade(&$mform) {
        global $CFG;
        // Include grade form.
        require_once($CFG->dirroot . '/mod/viaassign/gradeform.php');

        require_sesskey();

        $instance = $this->get_instance();
        $rownum = required_param('rownum', PARAM_INT);
        $viaid = optional_param('viaid', 0, PARAM_INT);
        $useridlistid = optional_param('useridlistid', time(), PARAM_INT);
        $userid = optional_param('userid', 0, PARAM_INT);
        $cache = cache::make_from_params(cache_store::MODE_SESSION, 'mod_viaassign', 'useridlist');

        if (!$useridlist = $cache->get($this->get_course_module()->id . '_' . $useridlistid)) {
            $useridlist = $this->get_grading_userid_list();
            $cache->set($this->get_course_module()->id . '_' . $useridlistid, $useridlist);
        }

        if ($rownum == count($useridlist) - 1) {
            $last = true;
        } else {
            $last = false;
        }
        if (!$userid) {
            $userid = $useridlist[$rownum];
        }

        $data = new stdClass();

        $gradeformparams = array('rownum' => $rownum,
                'useridlistid' => $useridlistid,
                'last' => false,
                'viaid' => $viaid,
                'userid' => $userid);
        $mform = new mod_viaassign_grade_form(null,
                                           array($this, $data, $gradeformparams),
                                           'post',
                                           '',
                                           array('class' => 'gradeform'));

        if ($formdata = $mform->get_data()) {
            return $this->save_grade($userid, $formdata);
        } else {
            return false;
        }
    }

    /**
     * This function is a static wrapper around can_upgrade.
     *
     * @param string $type The plugin type
     * @param int $version The plugin version
     * @return bool
     */
    public static function can_upgrade_viaassignment($type, $version) {
        $viaassignment = new viaassign(null, null, null);
        return $viaassignment->can_upgrade($type, $version);
    }

    /**
     * This function returns true if it can upgrade an viaassignment from the 2.2 module.
     *
     * @param string $type The plugin type
     * @param int $version The plugin version
     * @return bool
     */
    public function can_upgrade($type, $version) {
        if ($type == 'offline' && $version >= 2011112900) {
            return true;
        }
        foreach ($this->submissionplugins as $plugin) {
            if ($plugin->can_upgrade($type, $version)) {
                return true;
            }
        }
        foreach ($this->feedbackplugins as $plugin) {
            if ($plugin->can_upgrade($type, $version)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get an upto date list of user grades and feedback for the gradebook.
     *
     * @param int $userid int or 0 for all users
     * @return array of grade data formated for the gradebook api
     *         The data required by the gradebook api is userid,
     *                                                   rawgrade,
     *                                                   feedback,
     *                                                   feedbackformat,
     *                                                   usermodified,
     *                                                   dategraded,
     *                                                   datesubmitted
     */
    public function get_user_grades_for_gradebook($userid) {
        global $DB, $CFG;
        $grades = array();
        $viaassignmentid = $this->get_instance()->id;

        $adminconfig = $this->get_admin_config();
        $gradebookpluginname = $adminconfig->feedback_plugin_for_gradebook;
        $gradebookplugin = null;

        // Find the gradebook plugin.
        foreach ($this->feedbackplugins as $plugin) {
            if ($plugin->is_enabled() && $plugin->is_visible()) {
                if (('viaassignfeedback_' . $plugin->get_type()) == $gradebookpluginname) {
                    $gradebookplugin = $plugin;
                }
            }
        }
        if ($userid) {
            $where = ' WHERE u.id = :userid ';
        } else {
            $where = ' WHERE u.id != :userid ';
        }

        $submissionattempt = 'SELECT mxs.userid, mxs.viaid
                                 FROM {viaassign_submission} mxs
                                 WHERE mxs.viaassignid = :viaassignid1 GROUP BY mxs.userid';
        $gradeattempt = 'SELECT mxg.userid, mxg.viaid
                            FROM {viaassign_grades} mxg
                            WHERE mxg.viaassign = :viaassignid2 GROUP BY mxg.userid';

        // When the gradebook asks us for grades - only return the last attempt for each user.
        $params = array('viaassignid1' => $viaassignmentid,
                        'viaassignid2' => $viaassignmentid,
                        'viaassignid3' => $viaassignmentid,
                        'viaassignid4' => $viaassignmentid,
                        'userid' => $userid);
            $graderesults = $DB->get_recordset_sql('SELECT
                                                    u.id as userid,
                                                    s.timemodified as datesubmitted,
                                                    g.grade as rawgrade,
                                                    g.timemodified as dategraded,
                                                    g.grader as usermodified
                                                FROM {user} u
                                                LEFT JOIN ( ' . $submissionattempt . ' ) smx ON u.id = smx.userid
                                                LEFT JOIN ( ' . $gradeattempt . ' ) gmx ON u.id = gmx.userid
                                                LEFT JOIN {viaassign_submission} s
                                                    ON u.id = s.userid and s.viaassignid = :viaassignid3 AND
                                                    s.viaid = smx.viaid
                                                JOIN {viaassign_grades} g
                                                    ON u.id = g.userid and g.viaassign = :viaassignid4 AND
                                                    g.viaid = gmx.viaid' .
                $where, $params);

        foreach ($graderesults as $result) {
            $gradebookgrade = clone $result;
            // Now get the feedback.
            if ($gradebookplugin) {
                $grade = $this->get_user_grade($result->userid, false);
                if ($grade) {
                    $gradebookgrade->feedbacktext = $gradebookplugin->text_for_gradebook($grade);
                    $gradebookgrade->feedbackformat = $gradebookplugin->format_for_gradebook($grade);
                }
            }
            $grades[$gradebookgrade->userid] = $gradebookgrade;
        }

        $graderesults->close();
        return $grades;
    }

    /**
     * Call the static version of this function
     *
     * @param int $userid The userid to lookup
     * @return int The unique id
     */
    public function get_uniqueid_for_user($userid) {
        return self::get_uniqueid_for_user_static($this->get_instance()->id, $userid);
    }

    /**
     * Foreach participant in the course - viaassign them a random id.
     *
     * @param int $viaassignid The viaassignid to lookup
     */
    public static function allocate_unique_ids($viaassignid) {
        global $DB;

        $cm = get_coursemodule_from_instance('viaassign', $viaassignid, 0, false, MUST_EXIST);
        $context = context_module::instance($cm->id);

        $currentgroup = groups_get_activity_group($cm, true);
        $users = get_enrolled_users($context, "mod/viaassign:submit", $currentgroup, 'u.id');

        // Shuffle the users.
        shuffle($users);

        $record = new stdClass();
        $record->viaassignment = $viaassignid;
        foreach ($users as $user) {
            $record = $DB->get_record('viaassign_user_mapping',
                                      array('viaassign' => $viaassignid, 'userid' => $user->id),
                                     'id');
            if (!$record) {
                $record = new stdClass();
                $record->userid = $user->id;
                $record->viaassign = $viaassignid;
                $DB->insert_record('viaassign_user_mapping', $record);
            }
        }
    }

    /**
     * Lookup this user id and return the unique id for this viaassignment.
     *
     * @param int $viaassignid The viaassignment id
     * @param int $userid The userid to lookup
     * @return int The unique id
     */
    public static function get_uniqueid_for_user_static($viaassignid, $userid) {
        global $DB;

        // Search for a record.
        $params = array('viaassign' => $viaassignid, 'userid' => $userid);
        if ($record = $DB->get_record('viaassign_user_mapping', $params, 'id')) {
            return $record->id;
        }

        // Be a little smart about this - there is no record for the current user.
        // We should ensure any unallocated ids for the current participant
        // list are distrubited randomly.
        self::allocate_unique_ids($viaassignid);

        // Retry the search for a record.
        if ($record = $DB->get_record('viaassign_user_mapping', $params, 'id')) {
            return $record->id;
        }

        // The requested user must not be a participant. Add a record anyway.
        $record = new stdClass();
        $record->viaassignment = $viaassignid;
        $record->userid = $userid;

        return $DB->insert_record('viaassign_user_mapping', $record);
    }

    /**
     * Call the static version of this function.
     *
     * @param int $uniqueid The uniqueid to lookup
     * @return int The user id or false if they don't exist
     */
    public function get_user_id_for_uniqueid($uniqueid) {
        return self::get_user_id_for_uniqueid_static($this->get_instance()->id, $uniqueid);
    }

    /**
     * Lookup this unique id and return the user id for this viaassignment.
     *
     * @param int $viaassignid The id of the viaassignment this user mapping is in
     * @param int $uniqueid The uniqueid to lookup
     * @return int The user id or false if they don't exist
     */
    public static function get_user_id_for_uniqueid_static($viaassignid, $uniqueid) {
        global $DB;

        // Search for a record.
        if ($record = $DB->get_record('viaassign_user_mapping',
                                      array('viaassign' => $viaassignid, 'id' => $uniqueid),
                                      'userid',
                                      IGNORE_MISSING)) {
            return $record->userid;
        }

        return false;
    }

    /**
     * Check is only active users in course should be shown.
     *
     * @return bool true if only active users should be shown.
     */
    public function show_only_active_users() {
        global $CFG;

        if (is_null($this->showonlyactiveenrol)) {
            $defaultgradeshowactiveenrol = !empty($CFG->grade_report_showonlyactiveenrol);
            $this->showonlyactiveenrol = get_user_preferences('grade_report_showonlyactiveenrol', $defaultgradeshowactiveenrol);

            if (!is_null($this->context)) {
                $this->showonlyactiveenrol = $this->showonlyactiveenrol ||
                            !has_capability('moodle/course:viewsuspendedusers', $this->context);
            }
        }
        return $this->showonlyactiveenrol;
    }

    /**
     * Return true is user is active user in course else false
     *
     * @param int $userid
     * @return bool true is user is active in course.
     */
    public function is_active_user($userid) {
        if (is_null($this->susers) && !is_null($this->context)) {
            $this->susers = get_suspended_userids($this->context);
        }
        return !in_array($userid, $this->susers);
    }

    /**
     * Returns true if gradebook feedback plugin is enabled
     *
     * @return bool true if gradebook feedback plugin is enabled and visible else false.
     */
    public function is_gradebook_feedback_enabled() {
        // Get default grade book feedback plugin.
        $adminconfig = $this->get_admin_config();
        $gradebookplugin = $adminconfig->feedback_plugin_for_gradebook;
        $gradebookplugin = str_replace('viaassignfeedback_', '', $gradebookplugin);

        // Check if default gradebook feedback is visible and enabled.
        $gradebookfeedbackplugin = $this->get_feedback_plugin_by_type($gradebookplugin);

        if ($gradebookfeedbackplugin->is_visible() && $gradebookfeedbackplugin->is_enabled()) {
            return true;
        }

        // Gradebook feedback plugin is either not visible/enabled.
        return false;
    }
}

/**
 * Portfolio caller class for mod_viaassign.
 *
 * @package   mod_viaassign
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class viaassign_portfolio_caller extends portfolio_module_caller_base {
    /** @var int callback arg - the id of submission we export */
    protected $sid;

    /** @var string component of the submission files we export*/
    protected $component;

    /** @var string callback arg - the area of submission files we export */
    protected $area;

    /** @var int callback arg - the id of file we export */
    protected $fileid;

    /** @var int callback arg - the cmid of the viaassignment we export */
    protected $cmid;

    /** @var string callback arg - the plugintype of the editor we export */
    protected $plugin;

    /** @var string callback arg - the name of the editor field we export */
    protected $editor;

    /**
     * Callback arg for a single file export.
     */
    public static function expected_callbackargs() {
        return array(
            'cmid' => true,
            'sid' => false,
            'area' => false,
            'component' => false,
            'fileid' => false,
            'plugin' => false,
            'editor' => false,
        );
    }

    /**
     * The constructor.
     *
     * @param array $callbackargs
     */
    public function __construct($callbackargs) {
        parent::__construct($callbackargs);
        $this->cm = get_coursemodule_from_id('viaassign', $this->cmid, 0, false, MUST_EXIST);
    }

    /**
     * Load data needed for the portfolio export.
     *
     * If the viaassignment type implements portfolio_load_data(), the processing is delegated
     * to it. Otherwise, the caller must provide either fileid (to export single file) or
     * submissionid and filearea (to export all data attached to the given submission file area)
     * via callback arguments.
     *
     * @throws     portfolio_caller_exception
     */
    public function load_data() {
        $context = context_module::instance($this->cmid);

        if (empty($this->fileid)) {
            if (empty($this->sid) || empty($this->area)) {
                throw new portfolio_caller_exception('invalidfileandsubmissionid', 'mod_viaassign');
            }
        }

        // Export either an area of files or a single file (see function for more detail).
        // The first arg is an id or null. If it is an id, the rest of the args are ignored.
        // If it is null, the rest of the args are used to load a list of files from get_areafiles.
        $this->set_file_and_format_data($this->fileid,
                                        $context->id,
                                        $this->component,
                                        $this->area,
                                        $this->sid,
                                        'timemodified',
                                        false);
    }

    /**
     * Prepares the package up before control is passed to the portfolio plugin.
     *
     * @throws portfolio_caller_exception
     * @return mixed
     */
    public function prepare_package() {
        if ($this->plugin && $this->editor) {
            $options = portfolio_format_text_options();
            $context = context_module::instance($this->cmid);
            $options->context = $context;

            $plugin = $this->get_submission_plugin();

            $text = $plugin->get_editor_text($this->editor, $this->sid);
            $format = $plugin->get_editor_format($this->editor, $this->sid);

            $html = format_text($text, $format, $options);
            $html = portfolio_rewrite_pluginfile_urls($html,
                                                      $context->id,
                                                      'mod_viaassign',
                                                      $this->area,
                                                      $this->sid,
                                                      $this->exporter->get('format'));

            $exporterclass = $this->exporter->get('formatclass');
            if (in_array($exporterclass, array(PORTFOLIO_FORMAT_PLAINHTML, PORTFOLIO_FORMAT_RICHHTML))) {
                if ($files = $this->exporter->get('caller')->get('multifiles')) {
                    foreach ($files as $file) {
                        $this->exporter->copy_existing_file($file);
                    }
                }
                return $this->exporter->write_new_file($html, 'viaassignment.html', !empty($files));
            } else if ($this->exporter->get('formatclass') == PORTFOLIO_FORMAT_LEAP2A) {
                $leapwriter = $this->exporter->get('format')->leap2a_writer();
                $entry = new portfolio_format_leap2a_entry($this->area . $this->cmid,
                                                           $context->get_context_name(),
                                                           'resource',
                                                           $html);

                $entry->add_category('web', 'resource_type');
                $entry->author = $this->user;
                $leapwriter->add_entry($entry);
                if ($files = $this->exporter->get('caller')->get('multifiles')) {
                    $leapwriter->link_files($entry, $files, $this->area . $this->cmid . 'file');
                    foreach ($files as $file) {
                        $this->exporter->copy_existing_file($file);
                    }
                }
                return $this->exporter->write_new_file($leapwriter->to_xml(),
                                                       $this->exporter->get('format')->manifest_name(),
                                                       true);
            } else {
                debugging('invalid format class: ' . $this->exporter->get('formatclass'));
            }
        }

        if ($this->exporter->get('formatclass') == PORTFOLIO_FORMAT_LEAP2A) {
            $leapwriter = $this->exporter->get('format')->leap2a_writer();
            $files = array();
            if ($this->singlefile) {
                $files[] = $this->singlefile;
            } else if ($this->multifiles) {
                $files = $this->multifiles;
            } else {
                throw new portfolio_caller_exception('invalidpreparepackagefile',
                                                     'portfolio',
                                                     $this->get_return_url());
            }

            $entryids = array();
            foreach ($files as $file) {
                $entry = new portfolio_format_leap2a_file($file->get_filename(), $file);
                $entry->author = $this->user;
                $leapwriter->add_entry($entry);
                $this->exporter->copy_existing_file($file);
                $entryids[] = $entry->id;
            }
            if (count($files) > 1) {
                $baseid = 'viaassign' . $this->cmid . $this->area;
                $context = context_module::instance($this->cmid);

                // If we have multiple files, they should be grouped together into a folder.
                $entry = new portfolio_format_leap2a_entry($baseid . 'group',
                                                           $context->get_context_name(),
                                                           'selection');
                $leapwriter->add_entry($entry);
                $leapwriter->make_selection($entry, $entryids, 'Folder');
            }
            return $this->exporter->write_new_file($leapwriter->to_xml(),
                                                   $this->exporter->get('format')->manifest_name(),
                                                   true);
        }
        return $this->prepare_package_file();
    }

     /**
      * Calculate the time to transfer either a single file or a list
      * of files based on the data set by load_data.
      *
      * @return int
      */
    public function expected_time() {
        return $this->expected_time_file();
    }

    /**
     * Calculate a sha1 has of either a single file or a list
     * of files based on the data set by load_data.
     *
     * @return string
     */
    public function get_sha1() {
        if ($this->plugin && $this->editor) {
            $plugin = $this->get_submission_plugin();
            $options = portfolio_format_text_options();
            $options->context = context_module::instance($this->cmid);

            $text = format_text($plugin->get_editor_text($this->editor, $this->sid),
                $plugin->get_editor_format($this->editor, $this->sid),
                $options);
            $textsha1 = sha1($text);
            $filesha1 = '';
            try {
                $filesha1 = $this->get_sha1_file();
            } catch (portfolio_caller_exception $e) {
                // No files.
            }
            return sha1($textsha1 . $filesha1);
        }
        return $this->get_sha1_file();
    }


    /**
     * Checking the permissions.
     *
     * @return bool
     */
    public function check_permissions() {
        $context = context_module::instance($this->cmid);
        return has_capability('mod/viaassign:exportownsubmission', $context);
    }

    /**
     * Display a module name.
     *
     * @return string
     */
    public static function display_name() {
        return get_string('modulename', 'viaassign');
    }

    /**
     * Return array of formats supported by this portfolio call back.
     *
     * @return array
     */
    public static function base_supported_formats() {
        return array(PORTFOLIO_FORMAT_FILE, PORTFOLIO_FORMAT_LEAP2A);
    }
}