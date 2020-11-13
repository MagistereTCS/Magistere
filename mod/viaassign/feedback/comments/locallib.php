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
 * This file contains the definition for the library class for comment feedback plugin
 *
 * @package   viaassignfeedback_comments
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Library class for comment feedback plugin extending feedback plugin base class.
 *
 * @package   viaassignfeedback_comments
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class viaassign_feedback_comments extends viaassign_feedback_plugin {
    /**
     * Get the name of the online comment feedback plugin.
     * @return string
     */
    public function get_name() {
        return get_string('pluginname', 'viaassignfeedback_comments');
    }

    /**
     * Get the feedback comment from the database.
     *
     * @param int $gradeid
     * @return stdClass|false The feedback comments for the given grade if it exists.
     *                        False if it doesn't.
     */
    public function get_feedback_comments($gradeid) {
        global $DB;
        return $DB->get_record('viaassignfeedback_comments', array('grade' => $gradeid));
    }

    /**
     * Get quickgrading form elements as html.
     *
     * @param int $userid The user id in the table this quickgrading element relates to
     * @param mixed $grade - The grade data - may be null if there are no grades for this user (yet)
     * @return mixed - A html string containing the html form elements required for quickgrading
     */
    public function get_quickgrading_html($userid, $grade) {
        $commenttext = '';
        if ($grade) {
            $feedbackcomments = $this->get_feedback_comments($grade->id);
            if ($feedbackcomments) {
                $commenttext = $feedbackcomments->commenttext;
            }
        }

        $pluginname = get_string('pluginname', 'viaassignfeedback_comments');
        $labeloptions = array('for' => 'quickgrade_comments_' . $userid,
            'class' => 'accesshide');
        $textareaoptions = array('name' => 'quickgrade_comments_' . $userid,
            'id' => 'quickgrade_comments_' . $userid,
            'class' => 'quickgrade');
        return html_writer::tag('label', $pluginname, $labeloptions) .
            html_writer::tag('textarea', strip_tags($commenttext), $textareaoptions);
    }

    /**
     * Has the plugin quickgrading form element been modified in the current form submission?
     *
     * @param int $userid The user id in the table this quickgrading element relates to
     * @param stdClass $grade The grade
     * @return boolean - true if the quickgrading form element has been modified
     */
    public function is_quickgrading_modified($userid, $grade) {
        $commenttext = '';
        if ($grade) {
            $feedbackcomments = $this->get_feedback_comments($grade->id);
            if ($feedbackcomments) {
                $commenttext = $feedbackcomments->commenttext;
            }
        }
        // Note that this handles the difference between empty and not in the quickgrading
        // form at all (hidden column).
        $newvalue = optional_param('quickgrade_comments_' . $userid, false, PARAM_TEXT);
        return ($newvalue !== false) && ($newvalue != $commenttext);
    }

    /**
     * Override to indicate a plugin supports quickgrading.
     *
     * @return boolean - True if the plugin supports quickgrading
     */
    public function supports_quickgrading() {
        return true;
    }

    /**
     * Return a list of the text fields that can be imported/exported by this plugin.
     *
     * @return array An array of field names and descriptions. (name=>description, ...)
     */
    public function get_editor_fields() {
        return array('comments' => get_string('pluginname', 'viaassignfeedback_comments'));
    }

    /**
     * Get the saved text content from the editor.
     *
     * @param string $name
     * @param int $gradeid
     * @return string
     */
    public function get_editor_text($name, $gradeid) {
        if ($name == 'comments') {
            $feedbackcomments = $this->get_feedback_comments($gradeid);
            if ($feedbackcomments) {
                return $feedbackcomments->commenttext;
            }
        }

        return '';
    }

    /**
     * Get the saved text content from the editor.
     *
     * @param string $name
     * @param string $value
     * @param int $gradeid
     * @return string
     */
    public function set_editor_text($name, $value, $gradeid) {
        global $DB;

        if ($name == 'comments') {
            $feedbackcomment = $this->get_feedback_comments($gradeid);
            if ($feedbackcomment) {
                $feedbackcomment->commenttext = $value;
                return $DB->update_record('viaassignfeedback_comments', $feedbackcomment);
            } else {
                $feedbackcomment = new stdClass();
                $feedbackcomment->commenttext = $value;
                $feedbackcomment->commentformat = FORMAT_HTML;
                $feedbackcomment->grade = $gradeid;
                $feedbackcomment->viaassign = $this->viaassignment->get_instance()->id;
                return $DB->insert_record('viaassignfeedback_comments', $feedbackcomment) > 0;
            }
        }

        return false;
    }

    /**
     * Save quickgrading changes.
     *
     * @param int $userid The user id in the table this quickgrading element relates to
     * @param stdClass $grade The grade
     * @return boolean - true if the grade changes were saved correctly
     */
    public function save_quickgrading_changes($userid, $grade) {
        global $DB;
        $feedbackcomment = $this->get_feedback_comments($grade->id);
        $feedbackpresent = optional_param('quickgrade_comments_' . $userid, false, PARAM_TEXT) !== false;
        if (!$feedbackpresent) {
            // Nothing to save (e.g. hidden column).
            return true;
        }
        if ($feedbackcomment) {
            $feedbackcomment->commenttext = optional_param('quickgrade_comments_' . $userid, '', PARAM_TEXT);
            return $DB->update_record('viaassignfeedback_comments', $feedbackcomment);
        } else {
            $feedbackcomment = new stdClass();
            $feedbackcomment->commenttext = optional_param('quickgrade_comments_' . $userid, '', PARAM_TEXT);
            $feedbackcomment->commentformat = FORMAT_HTML;
            $feedbackcomment->grade = $grade->id;
            $feedbackcomment->viaassign = $this->viaassignment->get_instance()->id;
            return $DB->insert_record('viaassignfeedback_comments', $feedbackcomment) > 0;
        }
    }

    /**
     * Save the settings for feedback comments plugin
     *
     * @param stdClass $data
     * @return bool
     */
    public function save_settings(stdClass $data) {
        $this->set_config('commentinline', !empty($data->viaassignfeedback_comments_commentinline));
        return true;
    }

    /**
     * Get the default setting for feedback comments plugin
     *
     * @param MoodleQuickForm $mform The form to add elements to
     * @return void
     */
    public function get_settings(MoodleQuickForm $mform) {
        $default = $this->get_config('commentinline');
        $mform->addElement('selectyesno',
                            'viaassignfeedback_comments_commentinline',
                            get_string('commentinline', 'viaassignfeedback_comments'));
        $mform->addHelpButton('viaassignfeedback_comments_commentinline', 'commentinline', 'viaassignfeedback_comments');
        $mform->setDefault('viaassignfeedback_comments_commentinline', $default);
        // Disable comment online if comment feedback plugin is disabled.
        $mform->disabledIf('viaassignfeedback_comments_commentinline', 'viaassignfeedback_comments_enabled', 'notchecked');
    }

    /**
     * Convert the text from any submission plugin that has an editor field to
     * a format suitable for inserting in the feedback text field.
     *
     * @param stdClass $submission
     * @param stdClass $data - Form data to be filled with the converted submission text and format.
     * @return boolean - True if feedback text was set.
     */
    protected function convert_submission_text_to_feedback($submission, $data) {
        $format = false;
        $text = '';

        foreach ($this->viaassignment->get_submission_plugins() as $plugin) {
            $fields = $plugin->get_editor_fields();
            if ($plugin->is_enabled() && $plugin->is_visible() && !empty($fields)) {
                foreach ($fields as $key => $description) {
                    $rawtext = strip_pluginfile_content($plugin->get_editor_text($key, $submission->id));

                    $newformat = $plugin->get_editor_format($key, $submission->id);

                    if ($format !== false && $newformat != $format) {
                        // There are 2 or more editor fields using different formats, set to plain as a fallback.
                        $format = FORMAT_PLAIN;
                    } else {
                        $format = $newformat;
                    }
                    $text .= $rawtext;
                }
            }
        }

        $data->viaassignfeedbackcomments_editor['text'] = $text;
        $data->viaassignfeedbackcomments_editor['format'] = $format;
        return true;
    }

    /**
     * Get form elements for the grading page
     *
     * @param stdClass|null $grade
     * @param MoodleQuickForm $mform
     * @param stdClass $data
     * @return bool true if elements were added to the form
     */
    public function get_form_elements_for_user($grade, MoodleQuickForm $mform, stdClass $data, $userid) {
        $commentinlinenabled = $this->get_config('commentinline');
        $submission = $this->viaassignment->get_user_submission($userid, false);
        $feedbackcomments = false;

        if ($grade) {
            $feedbackcomments = $this->get_feedback_comments($grade->id);
        }

        if ($feedbackcomments && !empty($feedbackcomments->commenttext)) {
            $data->viaassignfeedbackcomments_editor['text'] = $feedbackcomments->commenttext;
            $data->viaassignfeedbackcomments_editor['format'] = $feedbackcomments->commentformat;
        } else {
            // No feedback given yet - maybe we need to copy the text from the submission?
            if (!empty($commentinlinenabled) && $submission) {
                $this->convert_submission_text_to_feedback($submission, $data);
            }
        }

        $mform->addElement('editor', 'viaassignfeedbackcomments_editor', $this->get_name(), null, null);

        return true;
    }

    /**
     * Saving the comment content into database.
     *
     * @param stdClass $grade
     * @param stdClass $data
     * @return bool
     */
    public function save(stdClass $grade, stdClass $data) {
        global $DB;
        $feedbackcomment = $this->get_feedback_comments($grade->id);
        if ($feedbackcomment) {
            $feedbackcomment->commenttext = $data->viaassignfeedbackcomments_editor['text'];
            $feedbackcomment->commentformat = $data->viaassignfeedbackcomments_editor['format'];
            return $DB->update_record('viaassignfeedback_comments', $feedbackcomment);
        } else {
            $feedbackcomment = new stdClass();
            $feedbackcomment->commenttext = $data->viaassignfeedbackcomments_editor['text'];
            $feedbackcomment->commentformat = $data->viaassignfeedbackcomments_editor['format'];
            $feedbackcomment->grade = $grade->id;
            $feedbackcomment->viaassign = $this->viaassignment->get_instance()->id;
            return $DB->insert_record('viaassignfeedback_comments', $feedbackcomment) > 0;
        }
    }

    /**
     * Display the comment in the feedback table.
     *
     * @param stdClass $grade
     * @param bool $showviewlink Set to true to show a link to view the full feedback
     * @return string
     */
    public function view_summary(stdClass $grade, & $showviewlink) {
        $feedbackcomments = $this->get_feedback_comments($grade->id);
        if ($feedbackcomments) {
            $text = format_text($feedbackcomments->commenttext,
                                $feedbackcomments->commentformat,
                                array('context' => $this->viaassignment->get_context()));
            $short = shorten_text($text, 140);

            // Show the view all link if the text has been shortened.
            $showviewlink = $short != $text;
            return $short;
        }
        return '';
    }

    /**
     * Display the comment in the feedback table.
     *
     * @param stdClass $grade
     * @return string
     */
    public function view(stdClass $grade) {
        $feedbackcomments = $this->get_feedback_comments($grade->id);
        if ($feedbackcomments) {
            return format_text($feedbackcomments->commenttext,
                               $feedbackcomments->commentformat,
                               array('context' => $this->viaassignment->get_context()));
        }
        return '';
    }

    /**
     * Return true if this plugin can upgrade an old Moodle 2.2 viaassignment of this type
     * and version.
     *
     * @param string $type old viaassignment subtype
     * @param int $version old viaassignment version
     * @return bool True if upgrade is possible
     */
    public function can_upgrade($type, $version) {
        if (($type == 'upload' || $type == 'uploadsingle' ||
             $type == 'online' || $type == 'offline') && $version >= 2011112900) {
            return true;
        }
        return false;
    }

    /**
     * Upgrade the settings from the old viaassignment to the new plugin based one
     *
     * @param context $oldcontext - the context for the old viaassignment
     * @param stdClass $oldviaassignment - the data for the old viaassignment
     * @param string $log - can be appended to by the upgrade
     * @return bool was it a success? (false will trigger a rollback)
     */
    public function upgrade_settings(context $oldcontext, stdClass $oldviaassignment, & $log) {
        if ($oldviaassignment->viaassignmenttype == 'online') {
            $this->set_config('commentinline', $oldviaassignment->var1);
            return true;
        }
        return true;
    }

    /**
     * Upgrade the feedback from the old viaassignment to the new one
     *
     * @param context $oldcontext - the database for the old viaassignment context
     * @param stdClass $oldviaassignment The data record for the old viaassignment
     * @param stdClass $oldsubmission The data record for the old submission
     * @param stdClass $grade The data record for the new grade
     * @param string $log Record upgrade messages in the log
     * @return bool true or false - false will trigger a rollback
     */
    public function upgrade(context $oldcontext,
                            stdClass $oldviaassignment,
                            stdClass $oldsubmission,
                            stdClass $grade,
                            & $log) {
        global $DB;

        $feedbackcomments = new stdClass();
        $feedbackcomments->commenttext = $oldsubmission->submissioncomment;
        $feedbackcomments->commentformat = FORMAT_HTML;

        $feedbackcomments->grade = $grade->id;
        $feedbackcomments->viaassignment = $this->viaassignment->get_instance()->id;
        if (!$DB->insert_record('viaassignfeedback_comments', $feedbackcomments) > 0) {
            $log .= get_string('couldnotconvertgrade', 'mod_viaassign', $grade->userid);
            return false;
        }

        return true;
    }

    /**
     * If this plugin adds to the gradebook comments field, it must specify the format of the text
     * of the comment
     *
     * Only one feedback plugin can push comments to the gradebook and that is chosen by the viaassignment
     * settings page.
     *
     * @param stdClass $grade The grade
     * @return int
     */
    public function format_for_gradebook(stdClass $grade) {
        $feedbackcomments = $this->get_feedback_comments($grade->id);
        if ($feedbackcomments) {
            return $feedbackcomments->commentformat;
        }
        return FORMAT_MOODLE;
    }

    /**
     * If this plugin adds to the gradebook comments field, it must format the text
     * of the comment
     *
     * Only one feedback plugin can push comments to the gradebook and that is chosen by the viaassignment
     * settings page.
     *
     * @param stdClass $grade The grade
     * @return string
     */
    public function text_for_gradebook(stdClass $grade) {
        $feedbackcomments = $this->get_feedback_comments($grade->id);
        if ($feedbackcomments) {
            return $feedbackcomments->commenttext;
        }
        return '';
    }

    /**
     * The viaassignment has been deleted - cleanup
     *
     * @return bool
     */
    public function delete_instance() {
        global $DB;
        // Will throw exception on failure.
        $DB->delete_records('viaassignfeedback_comments',
            array('viaassign' => $this->viaassignment->get_instance()->id));
        return true;
    }

    /**
     * Returns true if there are no feedback comments for the given grade.
     *
     * @param stdClass $grade
     * @return bool
     */
    public function is_empty(stdClass $grade) {
        return $this->view($grade) == '';
    }

    /**
     * Return a description of external params suitable for uploading an feedback comment from a webservice.
     *
     * @return external_description|null
     */
    public function get_external_parameters() {
        $editorparams = array('text' => new external_value(PARAM_TEXT, 'The text for this feedback.'),
                              'format' => new external_value(PARAM_INT, 'The format for this feedback'));
        $editorstructure = new external_single_structure($editorparams);
        return array('viaassignfeedbackcomments_editor' => $editorstructure);
    }
}