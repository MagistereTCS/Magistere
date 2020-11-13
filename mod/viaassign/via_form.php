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
 * This file contains the submission confirmation form
 *
 * @package   mod_viaassign
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot . '/mod/viaassign/locallib.php');
require_once($CFG->dirroot . '/mod/via/lib.php');

/**
 * Assignment submission confirmation form
 *
 * @package   mod_viaassign
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class via_form extends moodleform {
    /**
     * Define the form - called by parent constructor
     */
    public function definition() {
        global $CFG, $DB, $USER, $PAGE;

        $mform = $this->_form;

        $viaassignment = $this->_customdata['va'];
        $cmid = $this->_customdata['cmid'];

        $ctx = context_course::instance($viaassignment->course);
        $cm = $DB->get_record('course_modules', array('id' => $cmid));

        if (isset($this->_customdata['viaactivity'])) {
            $viaactivity = $this->_customdata['viaactivity'];

            // These values will permit to disable fields!
            if (($viaactivity->datebegin + ($viaactivity->duration * 60)) < time()) {
                $mform->addElement('hidden', 'pastevent', 1);
            } else {
                $mform->addElement('hidden', 'pastevent', 0);
            }
            if (time() > $viaactivity->datebegin && time() < ($viaactivity->datebegin + ($viaactivity->duration * 60))) {
                $mform->addElement('hidden', 'nowevent', 1);
            } else {
                $mform->addElement('hidden', 'nowevent', 0);
            }

            $mform->setType('pastevent', PARAM_INT);
            $mform->setType('nowevent', PARAM_INT);

            $mform->addElement('hidden', 'viaid', $viaactivity->viaid);
            $mform->setType('viaid', PARAM_INT);

            $mform->addElement('hidden', 'viaactivityid', $viaactivity->viaactivityid);
            $mform->setType('viaactivityid', PARAM_TEXT);
        } else {
            $viaactivity = false;
        }

        $flag = $DB->get_record('viaassign_user_flags', array('viaassign' => $viaassignment->id, 'userid' => $USER->id));

        // General info.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Title!
        $mform->addElement('text', 'name', get_string('activitytitle', 'via'), array('size' => '64'));
        $mform->setType('name', PARAM_CLEANHTML);
        $mform->addRule('name', null, 'required', null, 'client');

        $editoroptions = array('maxfiles' => 0, 'maxbytes' => 0, 'forcehttps' => false);
        $mform->addElement('editor', 'intro_editor', get_string('description', 'viaassign'), null, $editoroptions);
        $mform->setType('intro_editor', PARAM_RAW);

        // DURATION!
        $mform->addElement('header', 'general', get_string('durationheader', 'viaassign'));
        $date = new stdClass();
        $date->start = userdate($viaassignment->allowsubmissionsfromdate);

        // Add extension if there is one!
        if ( isset($flag->extensionduedate) && $flag->extensionduedate != 0 ) {
            $dateend = $flag->extensionduedate;
            $date->end = userdate($dateend );
            $extension = ' <span class="alert">' . get_string('eventextensiongranted', 'viaassign') .'</span> ';
        } else {
            $dateend = $viaassignment->duedate;
            $date->end = userdate($dateend);
            $extension = ' ';
        }

        $mform->addElement('html', html_writer::tag('h6', get_string('potentialdates', 'viaassign', $date).
            $extension . get_string('maxduration_hr', 'viaassign', $viaassignment->maxduration),
            array('class' => 'subheader', 'id' => 'date')));

        // Start Date!
        $mform->addElement('date_time_selector', 'datebegin', get_string('availabledate', 'via'),
        array('optional' => false,
            'startyear' => date("Y", $viaassignment->allowsubmissionsfromdate),
            'stopyear'  => date("Y", $dateend)));
        $mform->setDefault('datebegin', (time() + (60 * 10)));
        $mform->disabledif('datebegin', 'nowevent', 'eq', 1);
        $mform->disabledif('datebegin', 'pastevent', 'eq', 1);

        // Duration!
        $mform->addElement('text', 'duration', get_string('duration', 'viaassign'), array('size' => 4, 'maxlength' => 4));
        $mform->setType('duration', PARAM_INT);
        $mform->setDefault('duration', $viaassignment->maxduration);
        $mform->disabledif('duration', 'pastevent', 'eq', 1);

        // Automatic reminders!
        $onehour = 60 * 60;
        $twohours = (60 * 60) * 2;
        $oneday = (60 * 60) * 24;
        $twosdays = (60 * 60) * 48;
        $oneweek = ((60 * 60) * 24) * 7;
        $roptions = array( 0 => get_string('norecall', 'via'),
                    $onehour => get_string('recallonehour', 'via'),
                    $twohours => get_string('recalltwohours', 'via'),
                    $oneday => get_string('recalloneday', 'via'),
                    $twosdays => get_string('recalltwodays', 'via'),
                    $oneweek => get_string('recalloneweek', 'via'));

        $mform->addElement('select', 'remindertime', get_string('sendrecall', 'via'), $roptions);
        $mform->setDefault('remindertime', 0);
        $mform->disabledif('remindertime', 'nowevent', 'eq', 1);
        $mform->disabledif('remindertime', 'pastevent', 'eq', 1);
        $mform->addHelpButton('remindertime', 'sendrecall', 'via');

        // Presence!
        if ($viaassignment->takepresence) {
            $mform->addElement('text', 'presence', get_string('presence', 'via'), array('size' => '4'));
            $mform->addHelpButton('presence', 'presence', 'via');
            $mform->setType('presence', PARAM_INT);
            $mform->setDefault('presence', $viaassignment->minpresence);
        } else {
            // Set hidden fields to add default values.
            $mform->addElement('hidden', 'presence', 0);
            $mform->setType('presence', PARAM_INT);
        }

        if ($viaassignment->recordingmode) {
            // SESSION PARAMETERS!
            $mform->addElement('header', 'general', get_string('sessionparameters', 'via'));

            // Session recordings!
            $recordoptions = array( 0 => get_string('notactivated', 'via'),
                1 => get_string('unified', 'via'),
                2 => get_string('multiple', 'via'));
            $mform->addElement('select', 'recordingmode', get_string('recordingmode', 'via'), $recordoptions);
            $mform->setDefault('recordingmode', $viaassignment->recordingmode);
            $mform->disabledif('recordingmode', 'pastevent', 'eq', 1);
            $mform->addHelpButton('recordingmode', 'recordingmode', 'via');

            $recordbehavioroptions = array( 1 => get_string('automatic', 'via'), 2 => get_string('manual', 'via'));
            $mform->addElement('select', 'recordmodebehavior', get_string('recordmodebehavior', 'via'), $recordbehavioroptions);
            $mform->setDefault('recordmodebehavior', 1);
            $mform->disabledif('recordmodebehavior', 'recordingmode', 'eq', 0);
            $mform->disabledif('recordmodebehavior', 'pastevent', 'eq', 1);
            $mform->addHelpButton('recordmodebehavior', 'recordmodebehavior', 'via');

            // Review playbacks!
            $recordoptions = array( 0 => get_string('playbackaccesstype0', 'via'),
                                    1 => get_string('playbackaccesstype1', 'via'),
                                    2 => get_string('playbackaccesstype2', 'via'));
            $mform->addElement('select', 'isreplayallowed', get_string('reviewactivity', 'viaassign'), $recordoptions);
            $mform->setDefault('isreplayallowed', $viaassignment->isreplayallowed);
            $mform->disabledif('isreplayallowed', 'recordingmode', 'eq', 0);
            $mform->addHelpButton('isreplayallowed', 'reviewactivity', 'via');
        } else {
            // Set hidden fields to add default values.
            $mform->addElement('hidden', 'recordingmode', 0);
            $mform->setType('recordingmode', PARAM_INT);

            $mform->addElement('hidden', 'recordmodebehavior', 1);
            $mform->setType('recordmodebehavior', PARAM_INT);

            $mform->addElement('hidden', 'isreplayallowed', 0);
            $mform->setType('isreplayallowed', PARAM_INT);
        }

        // Enrolment!
        $mform->addElement('header', 'general', get_string('enrolmentheader', 'via'));

        if (isset($cm->groupingid)) {
            $groupingid = $cm->groupingid;
        } else {
            $groupingid = 0;
        }

        if ($viaassignment->maxusers > 0) {
            $mform->addElement('html', html_writer::tag('h6',
                get_string('maxusers', 'viaassign') .' : '.
                $viaassignment->maxusers, array('class' => 'subheader', 'id' => 'maxusers')));

            $mform->addElement('html', html_writer::tag('span',
                get_string('toomanyusers', 'viaassign'), array('class' => 'error hide', 'id' => 'usererror')));

            // Enrolled users lists.
            // Permits us to create all the different user select boxes!
            $group = array();
            $sel = array();

            // If we are editing we need to get the host.
            $host = $USER->id;
            if ($viaactivity) {
                $host = $DB->get_record_sql('SELECT vp.*, u.firstname, u.lastname, u.username
                                        FROM {via_participants} vp
                                        LEFT JOIN {user} u ON vp.userid = u.id
                                        WHERE vp.participanttype = 2 AND vp.activityid = ' . $viaactivity->viaid .'
                                        ORDER BY u.lastname ASC');
                if (isset($host)) {
                    $host = $host->userid;
                }
            }
            if (isset($cm->groupid)) {
                $groupid = $cm->groupid;
            } else {
                $groupid = 0;
            }

            if ($CFG->version > 2014111012) {
                $info = $cm->availability;
                $structure = json_decode($info);

                if (isset($structure)&& isset($structure->c[0]) && $structure->op != "!&") {
					if ($structure->c[0]->type == "grouping") {
						$groupingid = $structure->c[0]->id;
						//$groupid = $structure->c[0]->id;
					} else if ($structure->c[0]->type == "group" && isset($structure->c[0]->id)) {
                        $groupid = $structure->c[0]->id; // à tester
                        }
                } else {
                    if (isset($structure) && $structure->c[0]->type == "grouping") {
                        // Prendre en compte la négation.
                    }
                }
            }
            if (groups_get_activity_groupmode($cm) == 0 && $groupingid == 0) {
                // There is not group mode!
                $users = get_enrolled_users($ctx);
            } else {
                $mform->addElement('html', html_writer::tag('h6',
                    get_string('groupmode_hr', 'viaassign'), array('class' => 'subheader', 'id' => 'date')));

                if ($groupingid != 0 && groups_get_activity_groupmode($cm) == 0) {
                    // There is grouping but no groups.
                    $allowedgroups = $DB->get_records_sql('SELECT g.* FROM {groupings_groups} gg
                                                            LEFT JOIN {groups} g ON g.id = gg.groupid
                                                            WHERE gg.groupingid = '.$groupingid);

                    // We need to create only one list with the users of all the groups.
                    $pusers = array();
                    foreach ($allowedgroups as $g) {
                        $groupusers = get_enrolled_users($ctx, null, $g->id);

                        foreach ($groupusers as $key => $value) {
                            // The actual user is added as host automaticaly!
                            if ($key != $host) {
                                $pusers[$key] = $value->lastname . ' ' . $value->firstname . ' (' . $value->username .')';
                            }
                        }
                    }
                    if ($viaactivity && $g->id == $viaactivity->groupid) {//&& $g->id == $groupid) {
                        $viausers = $DB->get_records_sql('SELECT vp.*, u.firstname, u.lastname, u.username
                                            FROM {via_participants} vp
                                            LEFT JOIN {user} u ON vp.userid = u.id
                                            WHERE vp.activityid = ' . $viaactivity->viaid .'
                                            ORDER BY u.lastname ASC');
                        foreach ($viausers as $u) {
                            unset($pusers[$u->userid]);
                        }
                    }
                    $sel[$groupingid] = $mform->createElement('select', 'groupusers'.$g->id, '',
                        $pusers, array('class' => 'viauserlists' ));
                    $sel[$groupingid]->setMultiple(true);
                    $group[] =& $sel[$groupingid];
                    $mform->setType('groupusers'.$groupingid, PARAM_TEXT);

                    // We set these to null as the grouping option ONLY had been picked!
                    $allowedgroups = false;
                } else if ($groupingid != 0 && groups_get_activity_groupmode($cm) != 0 ) {
                    // There is grouping and groups.
                    $allowedgroups = $DB->get_records_sql('SELECT g.* FROM {groupings_groups} gg
                                                            LEFT JOIN {groups_members} gm ON gg.groupid = gm.groupid
                                                            LEFT JOIN {groups} g ON g.id = gm.groupid
                                                            WHERE gg.groupingid = '.$groupingid.'
                                                            AND gm.userid = '.$host.'');
                } else {
                    // There are only groups.
                    $allowedgroups = groups_get_all_groups($viaassignment->course, $host);
                }

                $groupoptions = array();
                $count = 1;
                if ($allowedgroups) {
                    // We need to create lists for each group, we do not permit the user to select users from different groups.
                    foreach ($allowedgroups as $g) {
						if ( ($count == 1 && $viaactivity && !array_key_exists($viaactivity->groupid,$allowedgroups)) || ($viaactivity && $g->id == $viaactivity->groupid) ||( !$viaactivity && $count == 1 )) {
                            $hide = '';
                        } else {
                            $hide = 'hide';
                        }

                        $groupoptions[$g->id] = $g->name;
                        // There is more than one group, we need to prepare the potential user lists for the none selected groups!
                        $groupusers = get_enrolled_users($ctx, null, $g->id);
                        $pusers = array();
                        foreach ($groupusers as $key => $value) {
                            if ($key != $host) { // The actual user is added as host automaticaly!
                                $pusers[$key] = $value->lastname . ' ' . $value->firstname . ' (' . $value->username .')';
                            }
                        }

                        if ($viaactivity &&  $g->id == $viaactivity->groupid) {//&& $g->id == $groupid) {
                            $viausers = $DB->get_records_sql('SELECT vp.*, u.firstname, u.lastname, u.username
                                            FROM {via_participants} vp
                                            LEFT JOIN {user} u ON vp.userid = u.id
                                            WHERE vp.activityid = ' . $viaactivity->viaid .'
                                            ORDER BY u.lastname ASC');
                            foreach ($viausers as $u) {
                                unset($pusers[$u->userid]);
                            }
                        }

                        $sel[$g->id] = $mform->createElement('select', 'groupusers'.$g->id, '',
                                        $pusers, array('class' => 'viauserlists '.$hide.'' ));
                        $sel[$g->id]->setMultiple(true);
                        $group[] =& $sel[$g->id];
                        $mform->setType('groupusers'.$g->id, PARAM_TEXT);

                        $count++;
                    }
                    $mform->addElement('select', 'groupid', get_string('groupselect', 'viaassign'), $groupoptions);
                    $mform->addHelpButton('groupid', 'groupselect', 'viaassign');

                }
            }

            // We need to get the viaactivity id!
            if ($viaactivity) {
                $editing = true;
                $vusers = $DB->get_records_sql('SELECT vp.*, u.firstname, u.lastname, u.username
                                            FROM {via_participants} vp
                                            LEFT JOIN {user} u ON vp.userid = u.id
                                            WHERE vp.activityid = ' . $viaactivity->viaid .'
                                            ORDER BY u.lastname ASC');
                if ($vusers) {
                    foreach ($vusers as $u) {
                        if ($u->participanttype == 1) {
                            $participants[$u->userid] = $u->lastname . ' ' . $u->firstname. ' (' . $u->username .')';
                        } else if ($u->participanttype == 3) {
                            $animators[$u->userid]  = $u->lastname . ' ' . $u->firstname. ' (' . $u->username .')';
                        } else {
                            $host[$u->userid] = $u->lastname . ' ' . $u->firstname. ' (' . $u->username .')';
                        }
                        // Unset users from potential users list!
                        if (isset($users)) {
                            unset($users[$u->userid]);
                        } else {
                            unset($pusers[$u->userid]);
                        }
                    }

                }
            } else {
                $editing = false;
                $participants = '';
                $animators = '';
            }

            // If there are no users we set to empty rather than null, to avoid php errors.
            if (!isset($pusers)) {
                $pusers = '';
            }
            if (!isset($participants)) {
                $participants = '';
            }
            if (!isset($animators)) {
                $animators = '';
            }

            $mform->addElement('html', '<div class="fitem viausers">
                                <p class="three potentialusers">'.get_string('potentialusers', 'viaassign').'</p>
                                <p class="element three participants">'.get_string('participants', 'via').'</p>
                                <p class="three animators">'.get_string('animators', 'via').'</p></div>');

            if (groups_get_activity_groupmode($cm) == 0 && $groupingid == 0) {
                // The potential user selects have already been created above!
                $pusers = array();
                foreach ($users as $key => $value) {
                     // The actual user is added as host automaticaly!
                    if ($key != $USER->id) {
                        $pusers[$key] = $value->lastname . ' ' . $value->firstname. ' (' . $value->username .')';
                    }
                }

                $select1 = $mform->createElement('select', 'potentialusers', '', $pusers, array('class' => 'viauserlists'));
                $select1->setMultiple(true);
                $group[] =& $select1;
                $mform->setType('potentialusers', PARAM_TEXT);
            }

            $group[] =& $mform->createElement('button', 'participants_remove_btn', '<', 'onclick="remove_participants()"');
            $group[] =& $mform->createElement('button', 'participants_add_btn', '>', 'onclick="add_participants()"');

            $select2 = $mform->createElement('select', 'participants', '', $participants, array('class' => 'viauserlists'));
            $select2->setMultiple(true);
            $group[] =& $select2;
            $mform->setType('participants', PARAM_TEXT);

            $group[] =& $mform->createElement('button', 'animators_remove_btn', '<', 'onclick="remove_animators()"');
            $group[] =& $mform->createElement('button', 'animators_add_btn', '>', 'onclick="add_animators()"');

            $select3 = $mform->createElement('select', 'animators', '', $animators, array('class' => 'viauserlists'));
            $select3->setMultiple(true);
            $group[] =& $select3;
            $mform->setType('animators', PARAM_TEXT);

            $mform->addGroup($group, 'add_users', get_string('manageparticipants', 'via'), array(' '), false);
            if (!$editing) {
                $mform->disabledif ('add_users', 'enroltype', 'eq', 0);
            }

            $mform->addElement('text', 'searchpotentialusers',
                get_string('users_search', 'via'), array('class' => 'search'));
            $mform->setType('searchpotentialusers', PARAM_TEXT);

            $mform->addElement('text', 'searchparticipants',
                get_string('participants_search', 'via'), array('class' => 'search'));
            $mform->setType('searchparticipants', PARAM_TEXT);

        } else {
            // Individual activity, it is not possible to invite other users!
            $mform->addElement('html', html_writer::tag('h6',
                get_string('individualassignment', 'viaassign') , array('class' => 'subheader', 'id' => 'users')));
        }

        // We add the user id using jquery! To be saved in add_instance.
        $mform->addElement('text', 'save_participants', '');
        $mform->setType('save_participants', PARAM_TEXT);

        $mform->addElement('text', 'save_animators', '');
        $mform->setType('save_animators', PARAM_TEXT);

        $mform->addElement('hidden', 'save_host', $USER->id);
        $mform->setType('save_host', PARAM_INT);

        // We set a defualt value for the groupid.
        if (groups_get_activity_groupmode($cm) == 0) {
            $mform->addElement('hidden', 'groupid', 0);
            $mform->setType('groupid', PARAM_INT);
        }

        // All the hidden info is neccessary to save the new Via activity.
        $mform->addElement('hidden', 'waitingroomaccessmode', $viaassignment->waitingroomaccessmode);
        $mform->setType('waitingroomaccessmode', PARAM_INT);

        $mform->addElement('hidden', 'profilid', $viaassignment->multimediaquality);
        $mform->setType('profilid', PARAM_TEXT);

        $mform->addElement('hidden', 'allowsubmissionsfromdate', $viaassignment->allowsubmissionsfromdate);
        $mform->setType('allowsubmissionsfromdate', PARAM_TEXT);

        $mform->addElement('hidden', 'duedate', $dateend);
        $mform->setType('duedate', PARAM_TEXT);

        $mform->addElement('hidden', 'roomtype', $viaassignment->roomtype);
        $mform->setType('roomtype', PARAM_TEXT);

        $mform->addElement('hidden', 'course', $viaassignment->course);
        $mform->setType('course', PARAM_TEXT);

        $mform->addElement('hidden', 'maxusers', $viaassignment->maxusers);
        $mform->setType('maxusers', PARAM_TEXT);

        $mform->addElement('hidden', 'maxduration', $viaassignment->maxduration);
        $mform->setType('maxduration', PARAM_TEXT);

        $mform->addElement('hidden', 'viaassignid', $viaassignment->id);
        $mform->setType('viaassignid', PARAM_TEXT);

        $mform->addElement('hidden', 'enroltype', 1); // Manual!
        $mform->setType('enroltype', PARAM_INT);

        $mform->addElement('hidden', 'isnewvia', 1); // Always new!
        $mform->setType('isnewvia', PARAM_INT);

        $mform->addElement('hidden', 'audiotype', 1); // IP!
        $mform->setType('audiotype', PARAM_INT);

        $mform->addElement('hidden', 'activitytype', 0); // If I add 1 for normal it will be changed later to 2 (permanent)!
        $mform->setType('activitytype', PARAM_INT);

        $mform->addElement('hidden', 'needconfirmation', 0);
        $mform->setType('needconfirmation', PARAM_INT);

        $mform->addElement('hidden', 'showparticipants', 1);
        $mform->setType('showparticipants', PARAM_INT);

        $mform->addElement('hidden', 'creator', $USER->id);
        $mform->setType('creator', PARAM_INT);

        // We need to validate the groupid/groupingid!!!
        $mform->addElement('hidden', 'groupingid', $groupingid);
        $mform->setType('groupingid', PARAM_INT);

        $mform->addElement('hidden', 'cmid', $cmid);
        $mform->setType('cmid', PARAM_INT);

        $mform->addElement('hidden', 'action', 'savevia');
        $mform->setType('action', PARAM_ALPHA);

        $this->add_action_buttons(true);
    }

    /**
     * Perform minimal validation on the settings form
     * @param array $data
     * @param array $files
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        $partipants = $data['save_participants'] != "" ? explode(',', $data['save_participants']) : 0;
        $animators = $data['save_animators'] != "" ? explode(',', $data['save_animators']) : 0;
        $totalusers = 0;
        if ($partipants != 0) {
            $totalusers = $totalusers + count($partipants);
        }
        if ($animators != 0) {
            $totalusers = $totalusers + count($animators);
        }

        if ($data['datebegin'] < $data['allowsubmissionsfromdate'] || ($data['datebegin'] + $data['duration']) > $data['duedate']) {
            $errors['datebegin'] = get_string('datevalidation', 'viaassign');
        }

        if (($data['datebegin'] + ($data['duration'] * 60)) < time() && !$data['viaactivityid'] && $data['activitytype'] == 0) {
            $errors['datebegin'] = get_string('passdate', 'via');
        }

        if ($totalusers > $data['maxusers']) {
             $errors['add_users'] = get_string('toomanyusers', 'viaassign');
        }

        if ($data['duration'] > $data['maxduration']) {
             $errors['duration'] = get_string('durationerror', 'viaassign');
        }

        return $errors;
    }
}