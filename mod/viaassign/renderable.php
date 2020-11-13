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
 * This file contains the definition for the renderable classes for the viaassignment
 *
 * @package   mod_viaassign
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Implements a renderable message notification
 * @package   mod_viaassign
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class viaassign_gradingmessage implements renderable {
    /** @var string $heading is the heading to display to the user */
    public $heading = '';
    /** @var string $message is the message to display to the user */
    public $message = '';
    /** @var int $coursemoduleid */
    public $coursemoduleid = 0;

    /**
     * Constructor
     * @param string $heading This is the heading to display
     * @param string $message This is the message to display
     * @param int $coursemoduleid
     */
    public function __construct($heading, $message, $coursemoduleid) {
        $this->heading = $heading;
        $this->message = $message;
        $this->coursemoduleid = $coursemoduleid;
    }
}

/**
 * Implements a renderable grading options form
 * @package   mod_viaassign
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class viaassign_form implements renderable {
    /** @var moodleform $form is the edit submission form */
    public $form = null;
    /** @var string $classname is the name of the class to viaassign to the container */
    public $classname = '';
    /** @var string $jsinitfunction is an optional js function to add to the page requires */
    public $jsinitfunction = '';

    /**
     * Constructor
     * @param string $classname This is the class name for the container div
     * @param moodleform $form This is the moodleform
     * @param string $jsinitfunction This is an optional js function to add to the page requires
     */
    public function __construct($classname, moodleform $form, $jsinitfunction = '') {
        $this->classname = $classname;
        $this->form = $form;
        $this->jsinitfunction = $jsinitfunction;
    }
}

/**
 * Implements a renderable user summary
 * @package   mod_viaassign
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class viaassign_user_summary implements renderable {
    /** @var stdClass $user suitable for rendering with user_picture and fullname(). */
    public $user = null;
    /** @var int $courseid */
    public $courseid;
    /** @var bool $viewfullnames */
    public $viewfullnames = false;
    /** @var int $uniqueidforuser */
    public $uniqueidforuser;
    /** @var array $extrauserfields */
    public $extrauserfields;
    /** @var bool $suspendeduser */
    public $suspendeduser;

    /**
     * Constructor
     * @param stdClass $user
     * @param int $courseid
     * @param bool $viewfullnames
     * @param int $uniqueidforuser
     * @param array $extrauserfields
     * @param bool $suspendeduser
     */
    public function __construct(stdClass $user,
                                $courseid,
                                $viewfullnames,
                                $uniqueidforuser,
                                $extrauserfields,
                                $suspendeduser = false) {
        $this->user = $user;
        $this->courseid = $courseid;
        $this->viewfullnames = $viewfullnames;
        $this->uniqueidforuser = $uniqueidforuser;
        $this->extrauserfields = $extrauserfields;
        $this->suspendeduser = $suspendeduser;
    }
}

/**
 * Implements a renderable feedback plugin feedback
 * @package   mod_viaassign
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class viaassign_feedback_plugin_feedback implements renderable {
    /** @var int SUMMARY */
    const SUMMARY                = 10;
    /** @var int FULL */
    const FULL                   = 20;

    /** @var viaassign_submission_plugin $plugin */
    public $plugin = null;
    /** @var stdClass $grade */
    public $grade = null;
    /** @var string $view */
    public $view = self::SUMMARY;
    /** @var int $coursemoduleid */
    public $coursemoduleid = 0;
    /** @var string returnaction The action to take you back to the current page */
    public $returnaction = '';
    /** @var array returnparams The params to take you back to the current page */
    public $returnparams = array();

    /**
     * Feedback for a single plugin
     *
     * @param viaassign_feedback_plugin $plugin
     * @param stdClass $grade
     * @param string $view one of feedback_plugin::SUMMARY or feedback_plugin::FULL
     * @param int $coursemoduleid
     * @param string $returnaction The action required to return to this page
     * @param array $returnparams The params required to return to this page
     */
    public function __construct(viaassign_feedback_plugin $plugin,
                                stdClass $grade,
                                $view,
                                $coursemoduleid,
                                $returnaction,
                                $returnparams) {
        $this->plugin = $plugin;
        $this->grade = $grade;
        $this->view = $view;
        $this->coursemoduleid = $coursemoduleid;
        $this->returnaction = $returnaction;
        $this->returnparams = $returnparams;
    }
}

class viaassign_feedback_status implements renderable {
    /** @var stding $gradefordisplay the student grade rendered into a format suitable for display */
    public $gradefordisplay = '';
    /** @var mixed the graded date (may be null) */
    public $gradeddate = 0;
    /** @var mixed the grader (may be null) */
    public $grader = null;
    /** @var array feedbackplugins - array of feedback plugins */
    public $feedbackplugins = array();
    /** @var stdClass viaassign_grade record */
    public $grade = null;
    /** @var int coursemoduleid */
    public $coursemoduleid = 0;
    /** @var string returnaction */
    public $returnaction = '';
    /** @var array returnparams */
    public $returnparams = array();

    /**
     * Constructor
     * @param string $gradefordisplay
     * @param mixed $gradeddate
     * @param mixed $grader
     * @param array $feedbackplugins
     * @param mixed $grade
     * @param int $coursemoduleid
     * @param string $returnaction The action required to return to this page
     * @param array $returnparams The list of params required to return to this page
     */
    public function __construct($allgrades,
                                $feedbackplugins,
                                $coursemoduleid,
                                $returnaction,
                                $returnparams) {
        $this->allgrades = $allgrades;
        $this->feedbackplugins = $feedbackplugins;
        $this->coursemoduleid = $coursemoduleid;
        $this->returnaction = $returnaction;
        $this->returnparams = $returnparams;
    }
}

/**
 * Renderable submission status
 * @package   mod_viaassign
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class viaassign_submission_status implements renderable {
    /** @var int STUDENT_VIEW */
    const STUDENT_VIEW     = 10;
    /** @var int GRADER_VIEW */
    const GRADER_VIEW      = 20;

    /** @var int allowsubmissionsfromdate */
    public $allowsubmissionsfromdate = 0;
    /** @var stdClass the submission info (may be null) */
    public $submission = null;
    /** @var bool submissionsenabled */
    public $submissionsenabled = false;
    /** @var bool locked */
    public $locked = false;
    /** @var bool graded */
    public $graded = false;
    /** @var int duedate */
    public $duedate = 0;
    /** @var array submissionplugins - the list of submission plugins */
    public $submissionplugins = array();
    /** @var string returnaction */
    public $returnaction = '';
    /** @var string returnparams */
    public $returnparams = array();
    /** @var int courseid */
    public $courseid = 0;
    /** @var int coursemoduleid */
    public $coursemoduleid = 0;
    /** @var int the view (STUDENT_VIEW OR GRADER_VIEW) */
    public $view = self::STUDENT_VIEW;
    /** @var bool canviewfullnames */
    public $canviewfullnames = false;
    /** @var bool canedit */
    public $canedit = false;
    /** @var bool cansubmit */
    public $cansubmit = false;
    /** @var int extensionduedate */
    public $extensionduedate = 0;
    /** @var context context */
    public $context = 0;
    /** @var string gradingcontrollerpreview */
    public $gradingcontrollerpreview = '';
    /** @var array userrole */
    public $userrole = array();
    /** @var int maxactivities */
    public $maxactivities = 1;
    /** @var int maxduration */
    public $maxduration = 60;
    /** @var int maxusers */
    public $maxusers = 60;
    /** @var int recordingmode */
    public $recordingmode = 0;
    /** @var int waitingroomaccessmode */
    public $waitingroomaccessmode = 60;
    /** @var int isreplayallowed */
    public $isreplayallowed = 0;
    /** @var int roomtype */
    public $roomtype = 0;
    /** @var string multimediaquality */
    public $multimediaquality = '';
    /** @var int minpresence */
    public $minpresence = 0;
    /** @var int takepresence */
    public $takepresence = 0;

    /**
     * Constructor
     *
     * @param int $allowsubmissionsfromdate
     * @param bool $alwaysshowdescription
     * @param stdClass $submission
     * @param bool $teamsubmissionenabled
     * @param stdClass $teamsubmission
     * @param int $submissiongroup
     * @param array $submissiongroupmemberswhoneedtosubmit
     * @param bool $submissionsenabled
     * @param bool $locked
     * @param bool $graded
     * @param int $duedate
     * @param int $cutoffdate
     * @param array $submissionplugins
     * @param string $returnaction
     * @param array $returnparams
     * @param int $coursemoduleid
     * @param int $courseid
     * @param string $view
     * @param bool $canedit
     * @param bool $cansubmit
     * @param bool $canviewfullnames
     * @param int $maxactivities
     * @param int $extensionduedate - Any extension to the due date granted for this user
     * @param context $context - Any extension to the due date granted for this user
     * @param bool $blindmarking - Should we hide student identities from graders?
     * @param string $gradingcontrollerpreview
     * @param string $attemptreopenmethod - The method of reopening student attempts.
     * @param int multimediaquality - multimediaquality
     * @param int roomtype
     * @param int maxusers
     */
    public function __construct($allowsubmissionsfromdate,
        $submission,
        $submissiongroup,
        $submissionsenabled,
        $locked,
        $graded,
        $duedate,
        $submissionplugins,
        $returnaction,
        $returnparams,
        $coursemoduleid,
        $courseid,
        $view,
        $canedit,
        $cansubmit,
        $canviewfullnames,
        $extensionduedate,
        $context,
        $gradingcontrollerpreview,
        $userrole,
        $maxactivities,
        $maxduration,
        $maxusers,
        $recordingmode,
        $waitingroomaccessmode,
        $isreplayallowed,
        $roomtype,
        $multimediaquality,
        $minpresence,
        $takepresence    ) {
        $this->allowsubmissionsfromdate = $allowsubmissionsfromdate;
        $this->submission = $submission;
        $this->submissionsenabled = $submissionsenabled;
        $this->locked = $locked;
        $this->graded = $graded;
        $this->duedate = $duedate;
        $this->submissionplugins = $submissionplugins;
        $this->returnaction = $returnaction;
        $this->returnparams = $returnparams;
        $this->coursemoduleid = $coursemoduleid;
        $this->courseid = $courseid;
        $this->view = $view;
        $this->canedit = $canedit;
        $this->cansubmit = $cansubmit;
        $this->canviewfullnames = $canviewfullnames;
        $this->extensionduedate = $extensionduedate;
        $this->context = $context;
        $this->gradingcontrollerpreview = $gradingcontrollerpreview;
        $this->userrole = $userrole;
        $this->maxactivities = $maxactivities;
        $this->maxduration = $maxduration;
        $this->maxusers = $maxusers;
        $this->recordingmode = $recordingmode;
        $this->waitingroomaccessmode = $waitingroomaccessmode;
        $this->isreplayallowed = $isreplayallowed;
        $this->roomtype = $roomtype;
        $this->multimediaquality = $multimediaquality;
        $this->minpresence = $minpresence;
        $this->takepresence = $takepresence;
    }
}

/**
 * Used to output the attempt history for a particular viaassignment.
 *
 * @package mod_viaassign
 * @copyright 2012 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class viaassign_history implements renderable {
    /** @var array submissions - The list of previous attempts */
    public $submissions = array();
    /** @var array feedbackplugins - The list of feedback plugins to render the previous attempts */
    public $feedbackplugins = array();
    /** @var int coursemoduleid - The cmid for the viaassignment */
    public $coursemoduleid = 0;
    /** @var string returnaction - The action for the next page. */
    public $returnaction = '';
    /** @var string returnparams - The params for the next page. */
    public $returnparams = array();
    /** @var bool cangrade - Does this user have grade capability? */
    public $cangrade = false;
    /** @var string useridlistid - Id of the useridlist stored in cache, this plus rownum determines the userid */
    public $useridlistid = 0;
    /** @var int rownum - The rownum of the user in the useridlistid - this plus useridlistid determines the userid */
    public $rownum = 0;
    /** @var int locked  */
    public $locked = 0;

    /**
     * Constructor
     *
     * @param array $submissions
     * @param array $grades
     * @param array $submissionplugins
     * @param array $feedbackplugins
     * @param int $coursemoduleid
     * @param string $returnaction
     * @param array $returnparams
     * @param bool $cangrade
     * @param int $useridlistid
     * @param int $rownum
     * @param int $locked
     */
    public function __construct($submissions,
                                $feedbackplugins,
                                $coursemoduleid,
                                $returnaction,
                                $returnparams,
                                $cangrade,
                                $useridlistid,
                                $rownum,
                                $locked) {
        $this->submissions = $submissions;
        $this->feedbackplugins = $feedbackplugins;
        $this->coursemoduleid = $coursemoduleid;
        $this->returnaction = $returnaction;
        $this->returnparams = $returnparams;
        $this->cangrade = $cangrade;
        $this->useridlistid = $useridlistid;
        $this->rownum = $rownum;
        $this->locked = $locked;
    }
}

/**
 * Used to output the attempt history for a particular viaassignment.
 *
 * @package mod_viaassign
 * @copyright 2012 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class viaassign_participations implements renderable {
    /** @var array submissions - The list of previous attempts */
    public $submissions = array();
    /** @var int coursemoduleid - The cmid for the viaassignment */
    public $coursemoduleid = 0;
    /** @var string returnparams - The params for the next page. */
    public $returnparams = array();
    /** @var bool cangrade - Does this user have grade capability? */
    public $cangrade = false;

    /**
     * Constructor
     *
     * @param array $submissions
     * @param int $coursemoduleid
     * @param array $returnparams
     * @param bool $cangrade
     */
    public function __construct($submissions,
        $coursemoduleid,
        $returnparams,
        $cangrade) {
            $this->submissions = $submissions;
            $this->coursemoduleid = $coursemoduleid;
            $this->returnparams = $returnparams;
            $this->cangrade = $cangrade;
    }
}

/**
 * Used to output the public playbacks for a particular viaassignment.
 *
 * @package mod_viaassign
 * @copyright 2012 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class viaassign_playbacks implements renderable {
    /** @var array submissions - The list of previous attempts */
    public $recordings = array();
    /** @var int coursemoduleid - The cmid for the viaassignment */
    public $coursemoduleid = 0;
    /** @var string returnaction - The action for the next page. */
    public $returnaction = '';
    /** @var string returnparams - The params for the next page. */
    public $returnparams = array();
    /** @var bool cangrade - Does this user have grade capability? */
    public $cangrade = false;

    /**
     * Constructor
     *
     * @param array $recordings
     * @param int $coursemoduleid
     * @param string $returnaction
     * @param array $returnparams
     * @param bool $cangrade
     */
    public function __construct($recordings,
                                $coursemoduleid,
                                $returnaction,
                                $returnparams,
                                $cangrade) {
        $this->recordings = $recordings;
        $this->coursemoduleid = $coursemoduleid;
        $this->returnaction = $returnaction;
        $this->returnparams = $returnparams;
        $this->cangrade = $cangrade;
    }
}

/**
 * Renderable header
 * @package   mod_viaassign
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class viaassign_header implements renderable {
    /** @var stdClass the viaassign record  */
    public $viaassign = null;
    /** @var mixed context|null the context record  */
    public $context = null;
    /** @var bool $showintro - show or hide the intro */
    public $showintro = false;
    /** @var int coursemoduleid - The course module id */
    public $coursemoduleid = 0;
    /** @var string $subpage optional subpage (extra level in the breadcrumbs) */
    public $subpage = '';
    /** @var string $preface optional preface (text to show before the heading) */
    public $preface = '';

    /**
     * Constructor
     *
     * @param stdClass $viaassign  - the viaassign database record
     * @param mixed $context context|null the course module context
     * @param bool $showintro  - show or hide the intro
     * @param int $coursemoduleid  - the course module id
     * @param string $subpage  - an optional sub page in the navigation
     * @param string $preface  - an optional preface to show before the heading
     */
    public function __construct(stdClass $viaassign,
                                $context,
                                $showintro,
                                $coursemoduleid,
                                $subpage='',
                                $preface='') {
        $this->viaassign = $viaassign;
        $this->context = $context;
        $this->showintro = $showintro;
        $this->coursemoduleid = $coursemoduleid;
        $this->subpage = $subpage;
        $this->preface = $preface;
    }
}

/**
 * Renderable header related to an individual subplugin
 * @package   mod_viaassign
 * @copyright 2014 Henning Bostelmann
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class viaassign_plugin_header implements renderable {
    /** @var viaassign_plugin $plugin */
    public $plugin = null;

    /**
     * Header for a single plugin
     *
     * @param viaassign_plugin $plugin
     */
    public function __construct(viaassign_plugin $plugin) {
        $this->plugin = $plugin;
    }
}

/**
 * Renderable grading summary
 * @package   mod_viaassign
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class viaassign_grading_summary implements renderable {
    /** @var int duedate - The viaassignment due date (if one is set) */
    public $duedate = 0;
    /** @var int extension - The viaassignment due date (if one is set) */
    public $extension = 0;
    /** @var int maxactivities - max number of activities the user may create */
    public $maxactivities = 0;
    /** @var int maxduration - max duration per activity */
    public $maxduration = 0;
    /** @var int maxduration - max duration per activity */
    public $maxdusers = 0;
    /** @var int participantcount - The number of users who can submit to this viaassignment */
    public $participantcount = 0;
    /** @var int submissionssubmittedcount - The number of submissions in submitted status */
    public $submissionssubmittedcount = 0;
    /** @var int coursemoduleid - The viaassignment course module id */
    public $coursemoduleid = 0;

    /**
     * constructor
     *
     * @param int $duedate
     * @param int $extension
     * @param int $maxactivities
     * @param int $maxduration
     * @param int $participantcount
     * @param int $submissionssubmittedcount
     * @param int $coursemoduleid
     *
     */
    public function __construct($duedate,
                                $extension,
                                $maxactivities,
                                $maxduration,
                                $maxusers,
                                $participantcount,
                                $submissionssubmittedcount,
                                $coursemoduleid) {
        $this->duedate = $duedate;
        $this->extension = $extension;
        $this->maxactivities = $maxactivities;
        $this->maxduration = $maxduration;
        $this->maxusers = $maxusers;
        $this->participantcount = $participantcount;
        $this->submissionssubmittedcount = $submissionssubmittedcount;
        $this->coursemoduleid = $coursemoduleid;
    }
}

/**
 * Renderable course index summary
 * @package   mod_viaassign
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class viaassign_course_index_summary implements renderable {
    /** @var array viaassignments - A list of course module info and submission counts or statuses */
    public $viaassignments = array();
    /** @var boolean usesections - Does this course format support sections? */
    public $usesections = false;
    /** @var string courseformat - The current course format name */
    public $courseformatname = '';

    /**
     * constructor
     *
     * @param boolean $usesections - True if this course format uses sections
     * @param string $courseformatname - The id of this course format
     */
    public function __construct($usesections, $courseformatname) {
        $this->usesections = $usesections;
        $this->courseformatname = $courseformatname;
    }

    /**
     * Add a row of data to display on the course index page
     *
     * @param int $cmid - The course module id for generating a link
     * @param string $cmname - The course module name for generating a link
     * @param string $sectionname - The name of the course section (only if $usesections is true)
     * @param int $timedue - The due date for the viaassignment - may be 0 if no duedate
     * @param string $submissioninfo - A string with either the number of submitted viaassignments, or the
     *                                 status of the current users submission depending on capabilities.
     * @param string $gradeinfo - The current users grade if they have been graded and it is not hidden.
     */
    public function add_viaassign_info($cmid, $cmname, $sectionname, $timedue, $submissioninfo, $gradeinfo) {
        $this->viaassignments[] = array('cmid' => $cmid,
                                        'cmname' => $cmname,
                                        'sectionname' => $sectionname,
                                        'timedue' => $timedue,
                                        'submissioninfo' => $submissioninfo,
                                        'gradeinfo' => $gradeinfo);
    }
}