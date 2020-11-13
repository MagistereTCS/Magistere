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
 * This file contains a renderer for the viaassignment class
 *
 * @package   mod_viaassign
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/viaassign/locallib.php');

/**
 * A custom renderer class that extends the plugin_renderer_base and is used by the viaassign module.
 *
 * @package mod_viaassign
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_viaassign_renderer extends plugin_renderer_base {
    /**
     * Rendering viaassignment files
     *
     * @param context $context
     * @param int $userid
     * @param string $filearea
     * @param string $component
     * @return string
     */
    public function viaassign_files(context $context, $userid, $filearea, $component) {
        return $this->render(new viaassign_files($context, $userid, $filearea, $component));
    }

    /**
     * Rendering viaassignment files
     *
     * @param viaassign_files $tree
     * @return string
     */
    public function render_viaassign_files(viaassign_files $tree) {
        $this->htmlid = html_writer::random_id('viaassign_files_tree');
        $this->page->requires->js_init_call('M.mod_viaassign.init_tree', array(true, $this->htmlid));
        $html = '<div id="'.$this->htmlid.'">';
        $html .= $this->htmllize_tree($tree, $tree->dir);
        $html .= '</div>';

        if ($tree->portfolioform) {
            $html .= $tree->portfolioform;
        }
        return $html;
    }

    /**
     * Utility function to add a row of data to a table with 2 columns. Modified
     * the table param and does not return a value
     *
     * @param html_table $table The table to append the row of data to
     * @param string $first The first column text
     * @param string $second The second column text
     * @return void
     */
    private function add_table_row_tuple(html_table $table, $first, $second) {
        $row = new html_table_row();
        $cell1 = new html_table_cell($first);
        $cell2 = new html_table_cell($second);
        $row->cells = array($cell1, $cell2);
        $table->data[] = $row;
    }

    /**
     * Render a grading message notification
     * @param viaassign_gradingmessage $result The result to render
     * @return string
     */
    public function render_viaassign_gradingmessage(viaassign_gradingmessage $result) {
        $urlparams = array('id' => $result->coursemoduleid, 'action' => 'grading');
        $url = new moodle_url('/mod/viaassign/view.php', $urlparams);

        $o = '';
        $o .= $this->output->heading($result->heading, 4);
        $o .= $this->output->notification($result->message);
        $o .= $this->output->continue_button($url);
        return $o;
    }

    /**
     * Render the generic form
     * @param viaassign_form $form The form to render
     * @return string
     */
    public function render_viaassign_form(viaassign_form $form) {
        $o = '';
        if ($form->jsinitfunction) {
            $this->page->requires->js_init_call($form->jsinitfunction, array());
        }
        $o .= $this->output->box_start('boxaligncenter ' . $form->classname);
        $o .= $this->moodleform($form->form);
        $o .= $this->output->box_end();
        return $o;
    }

    /**
     * Render the user summary
     *
     * @param viaassign_user_summary $summary The user summary to render
     * @return string
     */
    public function render_viaassign_user_summary(viaassign_user_summary $summary) {
        $o = '';
        $supendedclass = '';
        $suspendedicon = '';

        if (!$summary->user) {
            return;
        }

        if ($summary->suspendeduser) {
            $supendedclass = ' usersuspended';
            $suspendedstring = get_string('userenrolmentsuspended', 'grades');
            $suspendedicon = ' ' . html_writer::empty_tag('img', array('src' => $this->image_url('i/enrolmentsuspended'),
                'title' => $suspendedstring, 'alt' => $suspendedstring, 'class' => 'usersuspendedicon'));
        }
        $o .= $this->output->container_start('usersummary');
        $o .= $this->output->box_start('boxaligncenter usersummarysection'.$supendedclass);
        $o .= $this->output->user_picture($summary->user);
        $o .= $this->output->spacer(array('width' => 30));
        $urlparams = array('id' => $summary->user->id, 'course' => $summary->courseid);
        $url = new moodle_url('/user/view.php', $urlparams);
        $fullname = fullname($summary->user, $summary->viewfullnames);
        $extrainfo = array();
        foreach ($summary->extrauserfields as $extrafield) {
            $extrainfo[] = $summary->user->$extrafield;
        }
        if (count($extrainfo)) {
            $fullname .= ' (' . implode(', ', $extrainfo) . ')';
        }
        $fullname .= $suspendedicon;
        $o .= $this->output->action_link($url, $fullname);

        $o .= $this->output->box_end();
        $o .= $this->output->container_end();

        return $o;
    }

    /**
     * Page is done - render the footer.
     *
     * @return void
     */
    public function render_footer() {
        return $this->output->footer();
    }

    /**
     * Render the header.
     *
     * @param viaassign_header $header
     * @return string
     */
    public function render_viaassign_header(viaassign_header $header) {
        $o = '';

        if ($header->subpage) {
            $this->page->navbar->add($header->subpage);
        }

        $this->page->set_title(get_string('pluginname', 'viaassign'));
        $this->page->set_heading($this->page->course->fullname);

        $o .= $this->output->header();
        $heading = format_string($header->viaassign->name, false, array('context' => $header->context));
        //Modification pour M@gistere pour afficher l'encart custom à la place du heading standard
			$o .= $this->output->add_encart_activity('<h2>'.$heading.'</h2>');
		//$o .= $this->output->heading($heading);

        if ($header->preface) {
            $o .= $header->preface;
        }

        if ($header->showintro) {
            $o .= $this->output->box_start('generalbox boxaligncenter', 'intro');
            $o .= format_module_intro('viaassign', $header->viaassign, $header->coursemoduleid);
            $o .= $this->output->box_end();
        }

        return $o;
    }

    /**
     * Render the header for an individual plugin.
     *
     * @param viaassign_plugin_header $header
     * @return string
     */
    public function render_viaassign_plugin_header(viaassign_plugin_header $header) {
        $o = $header->plugin->view_header();
        return $o;
    }

    /**
     * Render a table containing the current status of the grading process.
     *
     * @param viaassign_grading_summary $summary
     * @return string
     */
    public function render_viaassign_grading_summary(viaassign_grading_summary $summary) {
        global $DB, $USER;
        // Create a table for the data.
        $o = '';
        $o .= $this->output->container_start('gradingsummary');
        $o .= $this->output->box_start('boxaligncenter gradingsummarytable');
        $t = new html_table();

        $extension = $summary->extension != 0 ? ' <span class="extension">('.
                    get_string('userextensiondate', 'viaassign', userdate($summary->extension)) . ')</span>' : null;

        $this->add_table_row_tuple($t, get_string('duedate', 'viaassign'), userdate($summary->duedate) . $extension);

        $this->add_table_row_tuple($t, get_string('maxactivities', 'viaassign'), $summary->maxactivities);

        $this->add_table_row_tuple($t, get_string('maxduration', 'viaassign'), $summary->maxduration);

        if (has_any_capability(array('mod/viaassign:viewgrades', 'mod/viaassign:grade'), $this->page->cm->context)) {
            $this->add_table_row_tuple($t, get_string('numberofparticipants', 'viaassign'), $summary->participantcount);

            $this->add_table_row_tuple($t, get_string('numberofparticipantswithvia', 'viaassign'),
                                        $summary->submissionssubmittedcount);
        } else {
            $this->add_table_row_tuple($t, get_string('maxusers', 'viaassign'), $summary->maxusers);
        }

        // All done - write the table.
        $o .= html_writer::table($t);
        $o .= $this->output->box_end();

        $o .= $this->output->container_end();

        return $o;
    }

    /**
     * Render a table containing all the current grades and feedback.
     *
     * @param viaassign_feedback_status $status
     * @return string
     */
    public function render_viaassign_feedback_status(viaassign_feedback_status $status) {
        global $DB, $CFG;
        $o = '';

        $o .= $this->output->container_start('feedback');
        $o .= $this->output->heading(get_string('feedback', 'viaassign'), 5);
        $o .= $this->output->box_start('boxaligncenter feedbacktable');
        $t = new html_table();

        // Grade.
        foreach ($status->allgrades as $grade) {
            if (isset($grade->viainfo)) {
                $row = new html_table_row();
                $cell1 = new html_table_cell(get_string('title', 'viaassign'));
                $cell2 = new html_table_cell($grade->viainfo);
                $cell2->attributes['class'] = 'title';
                $row->cells = array($cell1, $cell2);
                $t->data[] = $row;
            }

            if (isset($grade->grade)) {
                if ($grade->gradefordisplay != '-') {
                    $row = new html_table_row();
                    $cell1 = new html_table_cell(get_string('grade'));
                    $cell2 = new html_table_cell($grade->gradefordisplay);
                    $row->cells = array($cell1, $cell2);
                    $t->data[] = $row;
                }

                // Grade date.
                $row = new html_table_row();
                if ($grade->gradefordisplay != '-') {
                    $cell1 = new html_table_cell(get_string('gradedon', 'viaassign'));
                } else {
                    $cell1 = new html_table_cell(get_string('commentedon', 'viaassign'));
                }
                $cell2 = new html_table_cell(userdate($grade->timecreated));
                $row->cells = array($cell1, $cell2);
                $t->data[] = $row;
            }

            if ($grade->grader) {
                // Grader.
                $row = new html_table_row();
                if ($grade->gradefordisplay != '-') {
                    $cell1 = new html_table_cell(get_string('gradedby', 'viaassign'));
                } else {
                    $cell1 = new html_table_cell(get_string('commentedby', 'viaassign'));
                }
                $userdescription = $this->output->user_picture($grade->grader) .
                    $this->output->spacer(array('width' => 30)) .
                    fullname($grade->grader);
                $cell2 = new html_table_cell($userdescription);
                $row->cells = array($cell1, $cell2);
                $t->data[] = $row;
            }

            foreach ($status->feedbackplugins as $plugin) {
                if ($plugin->is_enabled() && $plugin->is_visible() && $plugin->has_user_summary() && !empty($grade)) {
                        $row = new html_table_row();
                        $cell1 = new html_table_cell($plugin->get_name());
                        $displaymode = viaassign_feedback_plugin_feedback::SUMMARY;
                        $pluginfeedback = new viaassign_feedback_plugin_feedback($plugin,
                            $grade,
                            $displaymode,
                            $status->coursemoduleid,
                            $status->returnaction,
                            $status->returnparams);
                        $cell2 = new html_table_cell($this->render($pluginfeedback));
                        $row->cells = array($cell1, $cell2);
                        $t->data[] = $row;
                }
            }
            // This is only to make an empty line to distance each feedback.
            $row = new html_table_row();
            $cell1 = new html_table_cell('');
            $cell1->attributes['class'] = 'nobackground';
            $row->cells = array($cell1, $cell1);
            $t->data[] = $row;
        }

        $o .= html_writer::table($t);
        $o .= $this->output->box_end();

        $o .= $this->output->container_end();
        return $o;
    }

    /**
     * Render a table containing the current status of the submission.
     *
     * @param viaassign_submission_status $status
     * @return string
     */
    public function render_viaassign_submission_status(viaassign_submission_status $status) {
        $o = '';
        $o .= $this->output->container_start('submissionstatustable');

        $time = time();

        if ($status->allowsubmissionsfromdate && $time <= $status->allowsubmissionsfromdate) {
            $o .= $this->output->box_start('generalbox boxaligncenter submissionsalloweddates');

            $date = userdate($status->allowsubmissionsfromdate);
            $o .= get_string('allowsubmissionsanddescriptionfromdatesummary', 'viaassign', $date);

            $o .= $this->output->box_end();
        }

        $o .= $this->output->box_start('boxaligncenter submissionsummarytable');

        $t = new html_table();

        $row = new html_table_row();
        $cell1 = new html_table_cell(get_string('attemptnumber', 'viaassign'));

        $row->cells = array($cell1);
        $t->data[] = $row;

        $row = new html_table_row();
        $cell1 = new html_table_cell(get_string('submissionstatus', 'viaassign'));

        $row = new html_table_row();
        $cell1 = new html_table_cell(get_string('submissionstatus', 'viaassign'));

        $cell2 = new html_table_cell(get_string('nosubmission', 'viaassign'));
        if (!$status->submissionsenabled) {
            $cell2 = new html_table_cell(get_string('noonlinesubmissions', 'viaassign'));
        } else {
            $cell2 = new html_table_cell(get_string('nosubmission', 'viaassign'));
        }

        $row->cells = array($cell1, $cell2);
        $t->data[] = $row;

        // Is locked?
        if ($status->locked) {
            $row = new html_table_row();
            $cell1 = new html_table_cell();
            $cell2 = new html_table_cell(get_string('submissionslocked', 'viaassign'));
            $cell2->attributes = array('class' => 'submissionlocked');
            $row->cells = array($cell1, $cell2);
            $t->data[] = $row;
        }

        // Grading status.
        $row = new html_table_row();
        $cell1 = new html_table_cell(get_string('gradingstatus', 'viaassign'));

        if ($status->graded) {
            $cell2 = new html_table_cell(get_string('graded', 'viaassign'));
            $cell2->attributes = array('class' => 'submissiongraded');
        } else {
            $cell2 = new html_table_cell(get_string('notgraded', 'viaassign'));
            $cell2->attributes = array('class' => 'submissionnotgraded');
        }
        $row->cells = array($cell1, $cell2);
        $t->data[] = $row;

        $duedate = $status->duedate;
        if ($duedate > 0) {
            // Due date.
            $row = new html_table_row();
            $cell1 = new html_table_cell(get_string('duedate', 'viaassign'));
            $cell2 = new html_table_cell(userdate($duedate));
            $row->cells = array($cell1, $cell2);
            $t->data[] = $row;

            if ($status->extensionduedate) {
                // Extension date.
                $row = new html_table_row();
                $cell1 = new html_table_cell(get_string('extensionduedate', 'viaassign'));
                $cell2 = new html_table_cell(userdate($status->extensionduedate));
                $row->cells = array($cell1, $cell2);
                $t->data[] = $row;
                $duedate = $status->extensionduedate;
            }

            // Time remaining.
            $row = new html_table_row();
            $cell1 = new html_table_cell(get_string('timeremaining', 'viaassign'));
            if ($duedate - $time <= 0) {
                if (!$status->submission ||
                        $status->submission->status != VIAASSIGN_SUBMISSION_STATUS_CREATED) {
                    if ($status->submissionsenabled) {
                        $overduestr = get_string('overdue', 'viaassign', format_time($time - $duedate));
                        $cell2 = new html_table_cell($overduestr);
                        $cell2->attributes = array('class' => 'overdue');
                    } else {
                        $cell2 = new html_table_cell(get_string('duedatereached', 'viaassign'));
                    }
                } else {
                    if ($status->submission->timemodified > $duedate) {
                        $latestr = get_string('submittedlate',
                                              'viaassign',
                                              format_time($status->submission->timemodified - $duedate));
                        $cell2 = new html_table_cell($latestr);
                        $cell2->attributes = array('class' => 'latesubmission');
                    } else {
                        $earlystr = get_string('submittedearly',
                                               'viaassign',
                                               format_time($status->submission->timemodified - $duedate));
                        $cell2 = new html_table_cell($earlystr);
                        $cell2->attributes = array('class' => 'earlysubmission');
                    }
                }
            } else {
                $cell2 = new html_table_cell(format_time($duedate - $time));
            }
            $row->cells = array($cell1, $cell2);
            $t->data[] = $row;
        }

        // Show graders whether this submission is editable by students.
        if ($status->view == viaassign_submission_status::GRADER_VIEW) {
            $row = new html_table_row();
            $cell1 = new html_table_cell(get_string('editingstatus', 'viaassign'));
            if ($status->canedit) {
                $cell2 = new html_table_cell(get_string('submissioneditable', 'viaassign'));
                $cell2->attributes = array('class' => 'submissioneditable');
            } else {
                $cell2 = new html_table_cell(get_string('submissionnoteditable', 'viaassign'));
                $cell2->attributes = array('class' => 'submissionnoteditable');
            }
            $row->cells = array($cell1, $cell2);
            $t->data[] = $row;
        }

        // Grading criteria preview.
        if (!empty($status->gradingcontrollerpreview)) {
            $row = new html_table_row();
            $cell1 = new html_table_cell(get_string('gradingmethodpreview', 'viaassign'));
            $cell2 = new html_table_cell($status->gradingcontrollerpreview);
            $row->cells = array($cell1, $cell2);
            $t->data[] = $row;
        }

        // Last modified.
        $submission = $status->submission;
        if ($submission) {
            $row = new html_table_row();
            $cell1 = new html_table_cell(get_string('timemodified', 'viaassign'));
            $cell2 = new html_table_cell(userdate($submission->timemodified));
            $row->cells = array($cell1, $cell2);
            $t->data[] = $row;
        }

        $o .= html_writer::table($t);
        $o .= $this->output->box_end();

        // Links.
        if ($status->view == viaassign_submission_status::STUDENT_VIEW) {
            if ($status->canedit) {
                if (!$submission) {
                    $o .= $this->output->box_start('generalbox submissionaction');
                    $urlparams = array('id' => $status->coursemoduleid, 'action' => 'deletesubmission');
                    $o .= $this->output->single_button(new moodle_url('/mod/viaassign/view.php', $urlparams),
                                                       get_string('addsubmission', 'viaassign'), 'get');
                    $o .= $this->output->box_start('boxaligncenter submithelp');
                    $o .= get_string('deletesubmission_help', 'viaassign');
                    $o .= $this->output->box_end();
                    $o .= $this->output->box_end();
                } else if ($submission->status == VIAASSIGN_SUBMISSION_STATUS_REOPENED) {
                    $o .= $this->output->box_start('generalbox submissionaction');
                    $urlparams = array('id' => $status->coursemoduleid,
                                       'action' => 'editprevioussubmission',
                                       'sesskey' => sesskey());
                    $o .= $this->output->single_button(new moodle_url('/mod/viaassign/view.php', $urlparams),
                                                       get_string('addnewattemptfromprevious', 'viaassign'), 'get');
                    $o .= $this->output->box_start('boxaligncenter submithelp');
                    $o .= $this->output->box_end();
                    $o .= $this->output->box_end();
                    $o .= $this->output->box_start('generalbox submissionaction');
                    $urlparams = array('id' => $status->coursemoduleid, 'action' => 'deletesubmission');
                    $o .= $this->output->single_button(new moodle_url('/mod/viaassign/view.php', $urlparams),
                                                       get_string('addnewattempt', 'viaassign'), 'get');
                    $o .= $this->output->box_start('boxaligncenter submithelp');
                    $o .= $this->output->box_end();
                    $o .= $this->output->box_end();
                } else {
                    $o .= $this->output->box_start('generalbox submissionaction');
                    $urlparams = array('id' => $status->coursemoduleid, 'action' => 'confirm_delete_via');
                    $o .= $this->output->single_button(new moodle_url('/mod/viaassign/view.php', $urlparams),
                                                       get_string('deletesubmission', 'viaassign'), 'get');
                    $o .= $this->output->box_start('boxaligncenter submithelp');
                    $o .= get_string('deletesubmission_help', 'viaassign');
                    $o .= $this->output->box_end();
                    $o .= $this->output->box_end();
                }
            }

            if ($status->cansubmit) {
                $urlparams = array('id' => $status->coursemoduleid, 'action' => 'submit');
                $o .= $this->output->box_start('generalbox submissionaction');
                $o .= $this->output->single_button(new moodle_url('/mod/viaassign/view.php', $urlparams),
                                                   get_string('submitviaassignment', 'viaassign'), 'get');
                $o .= $this->output->box_start('boxaligncenter submithelp');
                $o .= $this->output->box_end();
                $o .= $this->output->box_end();
            }
        }

        $o .= $this->output->container_end();
        return $o;
    }

    /**
     * Output the attempt history for this viaassignment
     *
     * @param viaassign_history $history
     * @return string
     */
    public function render_viaassign_history(viaassign_history $history) {
        global $CFG, $DB;

        $o = '';

        $o .= $this->heading(get_string('viaassignhistory', 'viaassign'), 5);
        $o .= $this->box_start('viaassignhistory');

        if ($history->locked == 1) {
            $locked = ' <i class="fa fa-lock"></i>';
        } else {
            $locked = '';
        }

        $table = new html_table();
            $table->head  = array ('<span class="viatooltip1">' .get_string('title', 'viaassign') . '
                                <i class="fa fa-info-circle via" aria-hidden="true"></i>
                                <span class="viatooltiptext1">' . get_string('titleinfo1', 'viaassign').'</span></span>',
                                get_string('dateheader', 'viaassign'),
                                get_string('actionsheader', 'viaassign') . $locked,
                                '<span class="viatooltip2">' .get_string('access', 'viaassign') . '
                                <i class="fa fa-info-circle via" aria-hidden="true"></i>
                                <span class="viatooltiptext2">'.get_string('titleinfo2', 'viaassign').'</span></span>');
        $table->align = array ('left', 'left', 'left', 'left');
        $table->data = array();

        foreach ($history->submissions as $submission) {
            $via = $DB->get_record('via', array('id' => $submission->viaid));
            if ($via) {
                $cm = get_coursemodule_from_instance('viaassign', $submission->viaassignid, null, false, MUST_EXIST);

                // Link to activity.
                $cell1 = html_writer::link(new moodle_url($CFG->wwwroot.'/mod/via/view.php',
                        array('viaid' => $submission->viaid)), $via->name, array('title' => s($via->name)));

                // Date and duration of activity.
                $cell2 = userdate($via->datebegin). ', <br/> '. $via->duration;

                // Permitted actions depending on user and status.
                    $editurlparams = array('id' => $cm->id,
                        'viaid' => $submission->viaid,
                        'action' => 'editvia',
                        'sesskey' => sesskey(),
                        'userid' => $submission->userid);

                    $deleteurlparams = array('id' => $cm->id,
                        'viaid' => $submission->viaid,
                        'action' => 'confirm_delete_via',
                        'sesskey' => sesskey(),
                        'userid' => $submission->userid);

                // Edit activity button!
                if ($history->locked == 0) {
                        $modify = $this->single_button(new moodle_url('/mod/viaassign/view.php', $editurlparams),
                            get_string('edit'), 'post', array('class' => 'modifyvia'));
                } else {
                        $modify = $this->single_button(new moodle_url('/mod/viaassign/view.php'),
                            get_string('edit'), 'post', array('disabled' => true, 'class' => 'modifyvia'));
                }

                // Delete activity button!
                if (has_capability('mod/viaassign:deleteown',  $this->page->cm->context)) {
                    if ($history->locked == 0) {
                            $delete = $this->single_button(new moodle_url('/mod/viaassign/view.php', $deleteurlparams),
                                get_string('delete'), 'post', array('class' => 'deletevia'));
                    } else {
                            $delete = $this->single_button(new moodle_url('/mod/viaassign/view.php'),
                                get_string('delete'), 'post', array('disabled' => true, 'class' => 'deletevia'));
                    }
                } else {
                    $delete = '';
                }
                if ($via->activitytype != 4) {
                    $cell3 = $modify . $delete;

                    // Go to activity button!
                    if ($via->datebegin + ($via->duration * 60) < time()) {
                        // Validate if there are any recordings, if there are, add link to details page.
                        $playbacks = $DB->get_records('via_playbacks', array('activityid' => $via->id));
                        if ($playbacks) {
                            $status = html_writer::link(new moodle_url($CFG->wwwroot.'/mod/via/view.php', array(
                                'viaid' => $submission->viaid)), '<i class="fa fa-film via" aria-hidden="true"></i>'
                                    .get_string('recordings', 'viaassign'), array('title' => s($via->name)));
                        } else {
                            // Otherwise, add terminated or graded
                            // Grade!
                            $grade = $DB->get_record('viaassign_grades', array(
                                'viaassign' => $submission->viaassignid,
                                'viaid' => $submission->viaid,
                                'userid' => $submission->userid));
                            if ($grade) {
                                $status = get_string('graded', 'viaassign');
                            } else {
                                $status = get_string('submissionstatus_done', 'viaassign');
                            }
                        }
                    } else {
                        $status  = '<input class="accessvia" type="button" target="viewplayback" href="view.via.php"
                                onclick="this.target=\'viewplayback\';
                                return openpopup(null, {url:\'/mod/via/view.via.php?viaid='.$submission->viaid.'\',
                                name:\'viewplayback\',
                                options:\'menubar=0,location=0,scrollbars=yes,resizable=yes\'});
                                " value="'. get_string('gotoactivity', 'via').'"/>';
                    }

                    $cell4 = $status;
                } else {
                    $cell3 = $delete;
                    $cell4 = null;
                }
                $table->data[] = new html_table_row(array($cell1, $cell2, $cell3, $cell4));
            }
        }

        $o .= html_writer::table($table);
        $o .= $this->box_end();

        return $o;
    }

    /**
     * Output the attempt history for this viaassignment
     *
     * @param viaassign_participations $history
     * @return string
     */
    public function render_viaassign_participations(viaassign_participations $history) {
        global $CFG, $DB, $USER;
        $id = optional_param('id', null, PARAM_INT);
        $o = '';

        $o .= $this->heading(get_string('viaassignparticipation', 'viaassign'), 5);
        $o .= $this->box_start('viaassignparticipation');

        $table = new html_table();
            $table->head  = array ( '<span class="viatooltip3">' .
                                get_string('title', 'viaassign') . ' <i class="fa fa-info-circle via" aria-hidden="true"></i>
                                <span class="viatooltiptext3">' . get_string('titleinfo3', 'viaassign').'</span></span>',
                                get_string('dateheader', 'viaassign'),
                                get_string('creator', 'viaassign'),
                                '<span class="viatooltip4">' .get_string('access', 'viaassign') .
                                 ' <i class="fa fa-info-circle via" aria-hidden="true"></i>
                                <span class="viatooltiptext4">'.get_string('titleinfo4', 'viaassign').'</span></span>');
        $table->align = array ('left', 'left', 'left', 'left', 'left');
        $table->data = array();

        foreach ($history->submissions as $participation) {
            // Link to activity.
            $cell1 = html_writer::link(new moodle_url($CFG->wwwroot.'/mod/via/view.php',
                    array('viaid' => $participation->viaid)), $participation->name, array('title' => s($participation->name)));

            // Date and duration of activity.
            $cell2 = userdate($participation->datebegin). ' <br/> '. $participation->duration;

            // Owner/creator of the via activity.
            $owner = $DB->get_record('user', array('id' => $participation->userid));
            if (has_capability('mod/viaassign:managegrades',  $this->page->context)) {
                // If capability, then add like to user profile!
                $username = html_writer::link(new moodle_url($CFG->wwwroot.'/user/profile.php?',
                            array('id' => $participation->userid)), fullname($owner), array('title' => s(fullname($owner))));
            } else {
                $username = fullname($owner);
            }
            $cell3 = $username;

            // Go to activity button!
            if ($participation->datebegin + ($participation->duration * 60) < time()) {
                // Validate if there are any recordings, if there are, add link to details page.
                $allplaybacks = $DB->get_records('via_playbacks', array('activityid' => $participation->viaid));
                foreach ($allplaybacks as $p) {
                    if ($p->accesstype == 1 || $p->accesstype == 2) {
                        $playbacks = true;
                        break;
                    } else {
                        $playbacks = false;
                    }
                }
                if (isset($playbacks)) {
                    $status = html_writer::link(new moodle_url($CFG->wwwroot.'/mod/via/view.php',
                        array('viaid' => $participation->viaid)), '<i class="fa fa-film via" aria-hidden="true"></i>'.
                            get_string('recordings', 'viaassign'), array('title' => s($participation->name)));
                } else {
                    $status = get_string('submissionstatus_done', 'viaassign');
                }

            } else {
                if ($id) {
                    if (!($cm = get_coursemodule_from_id('viaassign',  $id))) {
                        print_error("Course module ID is incorrect");
                    }
                    if (!($context = via_get_module_instance($cm->id))) {
                        print_error("Module context is incorrect");
                    }
                }

                if ($host = $DB->get_record('via_participants',
                    array('userid' => $USER->id, 'activityid' => $participation->viaid, 'participanttype' => 2))
                        || has_capability('mod/viaassign:deleteothers', $context) || (time() > $participation->datebegin - (30*60))) {

                    $status  = '<input type="button" target="viewplayback" href="view.via.php"
                    onclick="this.target=\'viewplayback\';
                    return openpopup(null, {url:\'/mod/via/view.via.php?viaid='.$participation->viaid.'\',
                    name:\'viewplayback\',
                    options:\'menubar=0,location=0,scrollbars=yes,resizable=yes\'});
                    " value="'. get_string('gotoactivity', 'via').'"/>';
                    } else {
                        $status  = '<input type="button" disabled="true" value="'. get_string('gotoactivity', 'via').'"/>';
                }
            }

            $cell4 = $status;

            // Status!
            $now = time();
            if ($participation->datebegin > $now) {
                $status = get_string('submissionstatus_future', 'viaassign');
            } else if ($participation->datebegin < $now && ($participation->datebegin + $participation->duration) > $now) {
                $status = get_string('submissionstatus_now', 'viaassign');
            } else {
                $status = get_string('submissionstatus_done', 'viaassign');
            }

            $cell5 = $status;

            $table->data[] = new html_table_row(array($cell1, $cell2, $cell3, $cell4));
        }

        $o .= html_writer::table($table);
        $o .= $this->box_end();

        return $o;
    }

    /**
     * Output the attempt history for this viaassignment
     *
     * @param viaassign_playbacks $recordings
     * @return string
     */
    public function render_viaassign_playbacks(viaassign_playbacks $playbacks) {
        global $CFG, $DB, $USER;

        $o = '';

        $o .= $this->box_start('publicplaybacks');
        $o .= $this->heading(get_string('publicplaybacks', 'viaassign'), 5);

        $table = new html_table();
        $table->head  = array ( get_string('title', 'viaassign'),
                                get_string('recording', 'viaassign'),
                                get_string('creator', 'viaassign'),
                                '');
        $table->align = array ('left', 'left', 'left', 'left');
        $table->data = array();

        foreach ($playbacks->recordings as $playback) {
            // Link to activity.
            if ($playback->creator == $USER->id) {
                $title = html_writer::link(new moodle_url($CFG->wwwroot.'/mod/via/view.php',
                        array('viaid' => $playback->viaid)), $playback->name, array('title' => s($playback->name)));
            } else {
                $title = $playback->name;
            }
            $cell1 = $title;

            // Date and duration of activity.
            if (isset($playback->playbackidref)) {
                $img = '<img src="'.$CFG->wwwroot.'/mod/via/pix/arrow.gif" alt="workshop" style="vertical-align: top;"></img> ';
            } else {
                $img = '';
            }
            $cell2 = $img. $playback->title . '<br/>'.
            userdate($playback->creationdate). ' ('.gmdate("H:i:s",  $playback->duration).')' .'<br />'.
            get_string('playbackaccesstype'.$playback->accesstype , 'via');

            // Owner/creator of the via activity.
            $owner = $DB->get_record('user', array('id' => $playback->creator));
            if (has_capability('mod/viaassign:managegrades',  $this->page->context)) {
                // If capability, then add like to user profile!
                $username = html_writer::link(new moodle_url($CFG->wwwroot.'/user/profile.php?',
                            array('id' => $playback->creator)), fullname($owner), array('title' => s(fullname($owner))));
            } else {
                $username = fullname($owner);
            }
            $cell3 = $username;

            if (has_capability('mod/viaassign:grade', $this->page->context)) {
                $param = '&fa=1';
            } else {
                $param = '';
            }
            $activity = $DB->get_record('via', array('id' => $playback->viaid));
            if($activity->activitytype != 4) {
                // Go to activity button!
                $access  = '<input type="button" target="viewplayback" href="view.via.php"
                        onclick="this.target=\'viewplayback\';
                        return openpopup(null, {url:\'/mod/via/view.via.php?viaid='.
                    $playback->viaid.'&playbackid='. urlencode($playback->playbackid).'&review=1'.$param.'\',
                        name:\'viewplayback\',
                        options:\'menubar=0,location=0,scrollbars=yes,resizable=yes\'});
                        " value="'. get_string('gotorecording', 'via').'"/>';

                $cell4 = $access;
            } else {
                $cell4 = null;
            }

            $table->data[] = new html_table_row(array($cell1, $cell2, $cell3, $cell4));
        }

        $o .= html_writer::table($table);
        $o .= $this->box_end();

        return $o;
    }

    /**
     * Render the grading table.
     *
     * @param viaassign_grading_table $table
     * @return string
     */
    public function render_viaassign_grading_table(viaassign_grading_table $table) {
        $o = '';
        $o .= $this->output->box_start('boxaligncenter gradingtable');

        $this->page->requires->js_init_call('M.mod_viaassign.init_grading_table', array());
        $this->page->requires->string_for_js('nousersselected', 'viaassign');
        $this->page->requires->string_for_js('batchoperationconfirmgrantextension', 'viaassign');
        $this->page->requires->string_for_js('batchoperationconfirmlock', 'viaassign');
        $this->page->requires->string_for_js('batchoperationconfirmunlock', 'viaassign');
        $this->page->requires->string_for_js('editaction', 'viaassign');
        foreach ($table->plugingradingbatchoperations as $plugin => $operations) {
            foreach ($operations as $operation => $description) {
                $this->page->requires->string_for_js('batchoperationconfirm' . $operation,
                                                     'viaassignfeedback_' . $plugin);
            }
        }
        $o .= $this->flexible_table($table, $table->get_rows_per_page(), true);
        $o .= $this->output->box_end();

        return $o;
    }

    /**
     * Render a feedback plugin feedback
     *
     * @param viaassign_feedback_plugin_feedback $feedbackplugin
     * @return string
     */
    public function render_viaassign_feedback_plugin_feedback(viaassign_feedback_plugin_feedback $feedbackplugin) {
        $o = '';

        if ($feedbackplugin->view == viaassign_feedback_plugin_feedback::SUMMARY) {
            $showviewlink = false;
            $summary = $feedbackplugin->plugin->view_summary($feedbackplugin->grade, $showviewlink);

            $classsuffix = $feedbackplugin->plugin->get_subtype() .
                           '_' .
                           $feedbackplugin->plugin->get_type() .
                           '_' .
                           $feedbackplugin->grade->id;
            $o .= $this->output->box_start('boxaligncenter plugincontentsummary summary_' . $classsuffix);

            $link = '';
            if ($showviewlink) {
                $previewstr = get_string('viewfeedback', 'viaassign');
                $icon = $this->output->pix_icon('t/preview', $previewstr);

                $expandstr = get_string('viewfull', 'viaassign');
                $options = array('class' => 'expandsummaryicon expand_' . $classsuffix);
                $o .= $this->output->pix_icon('t/switch_plus', $expandstr, null, $options);

                $jsparams = array($feedbackplugin->plugin->get_subtype(),
                                  $feedbackplugin->plugin->get_type(),
                                  $feedbackplugin->grade->id);
                $this->page->requires->js_init_call('M.mod_viaassign.init_plugin_summary', $jsparams);

                $urlparams = array('id' => $feedbackplugin->coursemoduleid,
                                   'gid' => $feedbackplugin->grade->id,
                                   'plugin' => $feedbackplugin->plugin->get_type(),
                                   'action' => 'viewplugin' . $feedbackplugin->plugin->get_subtype(),
                                   'returnaction' => $feedbackplugin->returnaction,
                                   'returnparams' => http_build_query($feedbackplugin->returnparams));
                $url = new moodle_url('/mod/viaassign/view.php', $urlparams);
                $link .= '<noscript>';
                $link .= $this->output->action_link($url, $icon);
                $link .= '</noscript>';

                $link .= $this->output->spacer(array('width' => 15));
            }

            $o .= $link . $summary;
            $o .= $this->output->box_end();
            if ($showviewlink) {
                $o .= $this->output->box_start('boxaligncenter hidefull full_' . $classsuffix);
                $classes = 'expandsummaryicon contract_' . $classsuffix;
                $o .= $this->output->pix_icon('t/switch_minus',
                                              get_string('viewsummary', 'viaassign'),
                                              null,
                                              array('class' => $classes));
                $o .= $feedbackplugin->plugin->view($feedbackplugin->grade);
                $o .= $this->output->box_end();
            }
        } else if ($feedbackplugin->view == viaassign_feedback_plugin_feedback::FULL) {
            $o .= $this->output->box_start('boxaligncenter feedbackfull');
            $o .= $feedbackplugin->plugin->view($feedbackplugin->grade);
            $o .= $this->output->box_end();
        }

        return $o;
    }

    /**
     * Render a course index summary
     *
     * @param viaassign_course_index_summary $indexsummary
     * @return string
     */
    public function render_viaassign_course_index_summary(viaassign_course_index_summary $indexsummary) {
        $o = '';

        $strplural = get_string('modulenameplural', 'viaassign');
        $strsectionname  = $indexsummary->courseformatname;
        $strduedate = get_string('duedate', 'viaassign');
        $strsubmission = get_string('submission', 'viaassign');
        $strgrade = get_string('grade');

        $table = new html_table();
        if ($indexsummary->usesections) {
            $table->head  = array ($strsectionname, $strplural, $strduedate, $strsubmission, $strgrade);
            $table->align = array ('left', 'left', 'center', 'right', 'right');
        } else {
            $table->head  = array ($strplural, $strduedate, $strsubmission, $strgrade);
            $table->align = array ('left', 'left', 'center', 'right');
        }
        $table->data = array();

        $currentsection = '';
        foreach ($indexsummary->viaassignments as $info) {
            $params = array('id' => $info['cmid']);
            $link = html_writer::link(new moodle_url('/mod/viaassign/view.php', $params),
                                      $info['cmname']);
            $due = $info['timedue'] ? userdate($info['timedue']) : '-';

            $printsection = '';
            if ($indexsummary->usesections) {
                if ($info['sectionname'] !== $currentsection) {
                    if ($info['sectionname']) {
                        $printsection = $info['sectionname'];
                    }
                    if ($currentsection !== '') {
                        $table->data[] = 'hr';
                    }
                    $currentsection = $info['sectionname'];
                }
            }

            if ($indexsummary->usesections) {
                $row = array($printsection, $link, $due, $info['submissioninfo'], $info['gradeinfo']);
            } else {
                $row = array($link, $due, $info['submissioninfo'], $info['gradeinfo']);
            }
            $table->data[] = $row;
        }

        $o .= html_writer::table($table);

        return $o;
    }

    /**
     * Internal function - creates htmls structure suitable for YUI tree.
     *
     * @param viaassign_files $tree
     * @param array $dir
     * @return string
     */
    protected function htmllize_tree(viaassign_files $tree, $dir) {
        global $CFG;
        $yuiconfig = array();
        $yuiconfig['type'] = 'html';

        if (empty($dir['subdirs']) and empty($dir['files'])) {
            return '';
        }

        $result = '<ul>';
        foreach ($dir['subdirs'] as $subdir) {
            $image = $this->output->pix_icon(file_folder_icon(),
                                             $subdir['dirname'],
                                             'moodle',
                                             array('class' => 'icon'));
            $result .= '<li yuiConfig=\'' . json_encode($yuiconfig) . '\'>' .
                       '<div>' . $image . ' ' . s($subdir['dirname']) . '</div> ' .
                       $this->htmllize_tree($tree, $subdir) .
                       '</li>';
        }

        foreach ($dir['files'] as $file) {
            $filename = $file->get_filename();
            if ($CFG->enableplagiarism) {
                require_once($CFG->libdir.'/plagiarismlib.php');
                $plagiarismlinks = plagiarism_get_links(array('userid' => $file->get_userid(),
                                                             'file' => $file,
                                                             'cmid' => $tree->cm->id,
                                                             'course' => $tree->course));
            } else {
                $plagiarismlinks = '';
            }
            $image = $this->output->pix_icon(file_file_icon($file),
                                             $filename,
                                             'moodle',
                                             array('class' => 'icon'));
            $result .= '<li yuiConfig=\'' . json_encode($yuiconfig) . '\'>' .
                       '<div>' . $image . ' ' .
                                 $file->fileurl . ' ' .
                                 $plagiarismlinks .
                                 $file->portfoliobutton . '</div>' .
                       '</li>';
        }

        $result .= '</ul>';

        return $result;
    }

    /**
     * Helper method dealing with the fact we can not just fetch the output of flexible_table
     *
     * @param flexible_table $table The table to render
     * @param int $rowsperpage How many viaassignments to render in a page
     * @param bool $displaylinks - Whether to render links in the table
     *                             (e.g. downloads would not enable this)
     * @return string HTML
     */
    protected function flexible_table(flexible_table $table, $rowsperpage, $displaylinks) {
        $o = '';
        ob_start();
        $table->out($rowsperpage, $displaylinks);
        $o = ob_get_contents();
        ob_end_clean();

        return $o;
    }

        /**
         * Helper method dealing with the fact we can not just fetch the output of moodleforms
         *
         * @param moodleform $mform
         * @return string HTML
         */
    protected function moodleform(moodleform $mform) {
        $o = '';
        ob_start();
        $mform->display();
        $o = ob_get_contents();
        ob_end_clean();

        return $o;
    }
}