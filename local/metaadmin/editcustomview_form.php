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
 * Edit custom view form.
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/local/magisterelib/databaseConnection.php');
require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot.'/local/metaadmin/lib.php');

class editcustomview_form extends moodleform {
    /**
     * The form definition.
     */
    public function definition() {
        global $CFG, $DB;

        $mform = $this->_form;
        $viewId = $this->_customdata['id'];
        $dispOpt = array("classical"    => get_string('view_disp_classical', 'local_metaadmin'),
            "bycourse"      => get_string('view_disp_bycourse', 'local_metaadmin'),
            "bycoursebyaca" => get_string('view_disp_bycoursebyaca', 'local_metaadmin'));
        $yesno = array(0 => get_string('view_no', 'local_metaadmin'),
            1 => get_string('view_yes', 'local_metaadmin'));
        $freqOpt = array("weekly" => get_string('view_weekly', 'local_metaadmin'),
            "monthly" => get_string('view_monthly', 'local_metaadmin'));
        $numDaysOpt = range(1, 31);
        $labelDaysOpt = array(1 => get_string('view_monday', 'local_metaadmin'),
            2 => get_string('view_tuesday', 'local_metaadmin'),
            3 => get_string('view_wednesday', 'local_metaadmin'),
            4 => get_string('view_thursday', 'local_metaadmin'),
            5 => get_string('view_friday', 'local_metaadmin'),
            6 => get_string('view_saturday', 'local_metaadmin'),
            7 => get_string('view_sunday', 'local_metaadmin'));

        $mform->addElement('text', 'view_name', get_string('customviewname'), array('size' => '100'));
        $mform->addRule('view_name', get_string('required'), 'required', null);
        $mform->setType('view_name', PARAM_TEXT);

        $mform->addElement('select', 'display_type', get_string('customviewdisplay'), $dispOpt);
        $mform->setDefault('display_type', 'classical');

        $mform->addElement('select', 'trainee_calc', get_string('customviewcalculation'), $yesno);
        $mform->setDefault('trainee_calc', 0);


        $courses = get_available_courses();
        $acourses = $courses['ref'];
        if ($viewId) {
            // Editing an existing custom view.
            $mform->addElement('hidden', 'id', $viewId);
            $strsubmit = get_string('savechanges');
            $view = get_custom_views_by_id($viewId);
            $scourses = array();
			foreach ($view->scourses as $courseuid) {
				if (isset($acourses[$courseuid])) {
					$scourses[$courseuid] = str_replace('*+%','_',$courseuid);
				}
			}

            $this->set_courses_lists($courses, $scourses);
            $this->definition_after_data();
        } else {

            // Making a new custom view.
            $mform->addElement('hidden', 'id', 0);
            $strsubmit = get_string('createcustomview');
            $this->set_courses_lists($courses);
        }
        $off ="";
        $ofp ="";
        foreach ($courses["offre"] as $key => $offre) {
            if($offre == CourseHub::PUBLISH_PUBLISHED){
                $off .= $key. ";";
            }else if($offre == CourseHub::PUBLISH_SHARED){
                $ofp .= $key. ";";
            }
        }
        $mform->addElement('hidden', 'ofp_list', $off);
        $mform->setType('ofp_list', PARAM_TEXT);
        $mform->addElement('hidden', 'off_list', $ofp);
        $mform->setType('off_list', PARAM_TEXT);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('select', 'send_report', get_string('customviewreport'), $yesno);
        $mform->setDefault('send_report', 0);

        $mform->addElement('select', 'frequency_report', get_string('customviewfrequency'), $freqOpt);
        $mform->setDefault('frequency_report', 1);
        $mform->disabledIf('frequency_report', 'send_report', 'eq', 0);

        $mform->addElement('select', 'nameday_report', get_string('customviewday'), $labelDaysOpt);
        $mform->setDefault('nameday_report', 0);
        $mform->disabledIf('nameday_report', 'send_report', 'eq', 0);

        $mform->addElement('select', 'numday_report', get_string('customviewday'), $numDaysOpt);
        $mform->setDefault('numday_report', 0);
        $mform->disabledIf('numday_report', 'send_report', 'eq', 0);

        $mform->addElement('text', 'emails', get_string('customviewemails'), array('size' => '100'));
        $mform->setType('emails', PARAM_TEXT);
        $mform->disabledIf('emails', 'send_report', 'eq', 0);

        $this->add_action_buttons(true, $strsubmit);
    }

    /**
     * Validates the data submit for this form.
     *
     * @param array $data An array of key,value data pairs.
     * @param array $files Any files that may have been submit as well.
     * @return array An array of errors.
     */
    public function validation($data, $files) {
        global $DB;
        $errors = parent::validation($data, $files);

        // email addresses validation
        if ($data['send_report'] == 1 && !$data['emails']) {
            $errors['emails'] = get_string('errorviewnomail');
        } elseif ($data['send_report'] == 1) {
            $tabEmails = explode(',', $data['emails']);
            foreach ($tabEmails as $mail) {
                if (!preg_match("#^[a-z0-9._-]+@[a-z0-9._-]{2,}\.[a-z]{2,4}$#", trim($mail))) {
                    $errors['emails'] = get_string('errorviewbadmail');
                }
            }
        }
        // selected courses validation
        if (!isset($data['scourses'])) {
            $errors['coursesgrp'] = get_string('errorviewnocourse');
        }
        return $errors;
    }

    /**
     * To set data when it is an update of the view
     */
    public function definition_after_data() {
        global $CFG, $DB;

        $mform = $this->_form;
        $viewId = $this->_customdata['id'];
        $view = get_custom_views_by_id($viewId);

        if ($viewId) {
            $mform->setDefault('view_name', $view->view_name);
            $mform->setDefault('display_type', $view->display_type);
            $mform->setDefault('trainee_calc', $view->trainee_calc);
            $mform->setDefault('send_report', $view->send_report);
            $mform->setDefault('frequency_report', $view->frequency_report);
            if ($view->frequency_report == "monthly") {
                $mform->setDefault('numday_report', $view->day_report--);
            } else {
                $mform->setDefault('nameday_report', $view->day_report);
            }
            $emails = str_replace(",", ", ", $view->emails);
            $mform->setDefault('emails', $emails);
        }
    }

    /**
     * To add lists of courses to select in the view, based on user_bulk_forms.php
     *
     * @param $acourses
     * @param $scourses
     */
    function set_courses_lists($courses, $scourses = array()) {
        global $CFG;
		$acourses = $courses['ref'];
		$ocourses = $courses['ownaca'];
		
		if (!has_capability('local/metaadmin:statsparticipants_viewallacademies', context_system::instance())) {
			$acourses2 = array();
			foreach($ocourses AS $oac) {
				$acourses2[$oac['id']] = $oac['label'];
			}
			$acourses = $acourses2;
		}
		
        $objs = array();
        $mform = $this->_form;
        $viewId = $this->_customdata['id'];

        asort($acourses);
        $availablefromgroup=array();
        $availablefromgroup[0] =& $mform->createElement('checkbox', 'offreff_on', '', 'Offre de parcours');
        $availablefromgroup[1] =& $mform->createElement('checkbox', 'offrefp_on', '', 'Offre de formation');
        $mform->setDefault('offreff_on', 1);
        $mform->setDefault('offrefp_on', 1);

        $objs[1] =&$mform->addGroup($availablefromgroup, 'filterfromgroup', "", ' ', false);

        $objs[1] =& $mform->createElement('select', 'acourses', get_string('viewcoursesavailable'), $acourses, 'size="10"');
        $objs[1]->setMultiple(true);

        $selectedCourses = array_keys($scourses);
        $scourses[0] = get_string('noselectedcourses');
        $scourses += $acourses;

        asort($selectedCourses);



        $objs[2] =& $mform->createElement('select', 'scourses', get_string('viewcoursesselected'), $scourses, 'size="10"');
        if ($viewId) {
            $objs[2]->setSelected($selectedCourses);
        }
        $objs[2]->setMultiple(true);

        $grp =& $mform->addElement('group', 'coursesgrp', get_string('coursesinview'), $objs, ' ', false);
        $mform->addRule('coursesgrp', get_string('required'), 'required', null);
		
        ////////Courses from the user academy only
        $mform->addElement('html', '<script>var acaCourses = '.json_encode($ocourses).';</script>');

        $objs = array();

        $objs[] =& $mform->createElement('submit', 'addsel', get_string('addsel', 'bulkusers'));
        $objs[] =& $mform->createElement('submit', 'removesel', get_string('removesel', 'bulkusers'));
        $objs[] =& $mform->createElement('submit', 'adduseraca', get_string('adduseraca'));


        $grp =& $mform->addElement('group', 'buttonsgrp','', $objs, array(' ', '<br />'), false);
        //$mform->addHelpButton('buttonsgrp', 'selectedlist', 'bulkusers');

        $renderer =& $mform->defaultRenderer();
        $template = '<label class="qflabel" style="vertical-align:top">{label}</label> {element}';
        $renderer->setGroupElementTemplate($template, 'coursesgrp');
    }
}