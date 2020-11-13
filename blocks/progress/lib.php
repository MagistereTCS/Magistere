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
 * Progress Bar block common configuration and helper functions
 *
 * Instructions for adding new modules so they can be monitored
 * ================================================================================================
 * Activies that can be monitored (all resources are treated together) are defined in the $MODULES
 * array.
 *
 * Modules can be added with:
 *  - defaultTime (deadline from module if applicable),
 *  - actions (array if action-query pairs) and
 *  - defaultAction (selected by default in config page and needed for backwards compatability)
 *
 * The module name needs to be the same as the table name for module in the database.
 *
 * Queries need to produce at least one result for completeness to go green, ie there is a record
 * in the DB that indicates the user's completion.
 *
 * Queries may include the following placeholders that are substituted when the query is run. Note
 * that each placeholder can only be used once in each query.
 *  :eventid (the id of the activity in the DB table that relates to it, eg., an assignment id)
 *  :cmid (the course module id that identifies the instance of the module within the course),
 *  :userid (the current user's id) and
 *  :courseid (the current course id)
 *
 * When you add a new module, you need to add a translation for it in the lang files.
 * If you add new action names, you need to add a translation for these in the lang files.
 *
 * Note: Activity completion is automatically available when enabled (sitewide setting) and set for
 * an activity.
 *
 * If you have added a new module to this array and think other's may benefit from the query you
 * have created, please share it by sending it to michaeld@moodle.com
 * ================================================================================================
 *
 * @package    contrib
 * @subpackage block_progress
 * @copyright  2010 Michael de Raadt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Provides information about monitorable modules
 *
 * @return array
 */
function get_monitorable_modules() {
    global $DB;

    return array(
        'assign' => array(
            'defaultTime'=>'duedate',
            'actions'=>array(
            		
                'submitted'    => "SELECT id
                                     FROM {assign_submission}
                                    WHERE assignment = :eventid
                                      AND userid = :userid
                                      AND status = 'submitted'",
                'marked'       => "SELECT g.rawgrade
                                     FROM {grade_grades} g, {grade_items} i
                                    WHERE i.itemmodule = 'assign'
                                      AND i.iteminstance = :eventid
                                      AND i.id = g.itemid
                                      AND g.userid = :userid",
                'passed'       => "SELECT g.rawgrade
                                     FROM {grade_grades} g, {grade_items} i
                                    WHERE i.itemmodule = 'assign'
                                      AND i.iteminstance = :eventid
                                      AND i.id = g.itemid
                                      AND g.userid = :userid
                                      AND g.rawgrade >= i.gradepass",
            	
            	'submitted_group' => "SELECT id
										FROM mdl_assign_submission
										WHERE assignment = :eventid
										AND userid = :userid
										AND status = 'submitted'
										UNION
										SELECT id
										FROM mdl_assign_submission
										WHERE assignment = :eventid1
										AND groupid IN (SELECT groupid FROM mdl_groups_members WHERE userid = :userid1)
										AND status = 'submitted'",
                'marked_group'    => "SELECT g.rawgrade
										FROM {grade_grades} g, {grade_items} i
										WHERE i.itemmodule = 'assign'
										AND i.iteminstance = :eventid
										AND i.id = g.itemid
										AND g.userid IN (SELECT userid FROM mdl_groups_members WHERE groupid IN (SELECT groupid FROM mdl_groups_members WHERE userid = :userid))",
            	'passed_group'    => "SELECT g.rawgrade
										FROM {grade_grades} g, {grade_items} i
										WHERE i.itemmodule = 'assign'
										AND i.iteminstance = :eventid
										AND i.id = g.itemid
										AND g.userid IN (SELECT userid FROM mdl_groups_members WHERE groupid IN (SELECT groupid FROM mdl_groups_members WHERE userid = :userid))
										AND g.rawgrade >= i.gradepass"
            	
            	
            ),
            'defaultAction' => 'submitted'
        ),
		/*
        'assignment' => array(
            'defaultTime'=>'timedue',
            'actions'=>array(
                'submitted'    => "SELECT id
                                     FROM {assignment_submissions}
                                    WHERE assignment = :eventid
                                      AND userid = :userid
                                      AND (
                                          numfiles >= 1
                                          OR {$DB->sql_compare_text('data2')} <> ''
                                      )",
                'marked'       => "SELECT g.rawgrade
                                     FROM {grade_grades} g, {grade_items} i
                                    WHERE i.itemmodule = 'assignment'
                                      AND i.iteminstance = :eventid
                                      AND i.id = g.itemid
                                      AND g.userid = :userid",
                'passed'       => "SELECT g.rawgrade
                                     FROM {grade_grades} g, {grade_items} i
                                    WHERE i.itemmodule = 'assignment'
                                      AND i.iteminstance = :eventid
                                      AND i.id = g.itemid
                                      AND g.userid = :userid
                                      AND g.rawgrade >= i.gradepass"
            ),
            'defaultAction' => 'submitted'
        ),
		*/
		/*
        'book' => array(
            'actions'=>array(
                'viewed'       => "SELECT id
                                     FROM {log}
                                    WHERE course = :courseid
                                      AND module = 'book'
                                      AND action = 'view'
                                      AND cmid = :cmid
                                      AND userid = :userid"
            ),
            'defaultAction' => 'viewed'
        ),
        'certificate' => array(
            'actions'=>array(
                'awarded'    => "SELECT id
                                   FROM {certificate_issues}
                                  WHERE certificateid = :eventid
                                    AND userid = :userid"
            ),
            'defaultAction' => 'awarded'
        ),
		*/
    	
   		'centralizedresources' => array(
  				'actions'=>array(
 						'viewed' => "SELECT id
                                    FROM {progress_activities}
                                    WHERE course_id = :courseid
                                      AND course_module_id = :cmid
                                      AND user_id = :userid
									  AND status = 'viewed'
									  AND module_name = 'centralizedresources'"
    			),
    			'defaultAction' => 'viewed'
    	),
        'chat' => array(
            'actions'=>array(
                'posted_to'    => "SELECT id
                                     FROM {chat_messages}
                                    WHERE chatid = :eventid
                                      AND userid = :userid"
            ),
            'defaultAction' => 'posted_to'
        ),
        'choice' => array(
            'defaultTime'=>'timeclose',
            'actions'=>array(
                'answered'     => "SELECT id
                                     FROM {choice_answers}
                                    WHERE choiceid = :eventid
                                      AND userid = :userid"
            ),
            'defaultAction' => 'answered'
        ),/*
        'data' => array(
            'defaultTime'=>'timeviewto',
            'actions'=>array(
                'viewed'       => "SELECT id
                                     FROM {log}
                                    WHERE course = :courseid
                                      AND module = 'data'
                                      AND action = 'view'
                                      AND cmid = :cmid
                                      AND userid = :userid"
            ),
            'defaultAction' => 'viewed'
        ),*/
        'data' => array(
            'defaultTime'=>'timeviewto',
            'actions'=>array(
                'add'       => "SELECT id
                                     FROM {progress_activities}
                                    WHERE course_id = :courseid
                                      AND course_module_id = :cmid
                                      AND user_id = :userid
									  AND status = 'add'
									  AND module_name = 'data'"
            ),
            'defaultAction' => 'add'
        ),
        'etherpadlite' => array(
            'actions'=>array(
                'viewed'       => "SELECT id
                                     FROM {progress_activities}
                                    WHERE course_id = :courseid
                                      AND course_module_id = :cmid
                                      AND user_id = :userid
									  AND status = 'viewed'
									  AND module_name = 'etherpadlite'",
            	'viewed_group' => "SELECT id
                                     FROM {progress_activities}
                                    WHERE course_id = :courseid
                                      AND course_module_id = :cmid
                                      AND user_id IN (SELECT userid FROM mdl_groups_members WHERE groupid IN (SELECT groupid FROM mdl_groups_members WHERE userid = :userid))
									  AND status = 'viewed'
									  AND module_name = 'etherpadlite'"
            ),
            'defaultAction' => 'viewed'
        ),
		/*
        'feedback' => array(
            'defaultTime'=>'timeclose',
            'actions'=>array(
                'responded_to' => "SELECT id
                                     FROM {feedback_completed}
                                    WHERE feedback = :eventid
                                      AND userid = :userid"
            ),
            'defaultAction' => 'responded_to'
        ),
        'resource' => array(  // AKA file
            'actions'=>array(
                'viewed'       => "SELECT id
                                     FROM {log}
                                    WHERE course = :courseid
                                      AND module = 'resource'
                                      AND action = 'view'
                                      AND cmid = :cmid
                                      AND userid = :userid"
            ),
            'defaultAction' => 'viewed'
        ),
        'flashcardtrainer' => array(
            'actions'=>array(
                'viewed'       => "SELECT id
                                     FROM {log}
                                    WHERE course = :courseid
                                      AND module = 'flashcardtrainer'
                                      AND action = 'view'
                                      AND cmid = :cmid
                                      AND userid = :userid"
            ),
            'defaultAction' => 'viewed'
        ),
        'folder' => array(
            'actions'=>array(
                'viewed'       => "SELECT id
                                     FROM {log}
                                    WHERE course = :courseid
                                      AND module = 'folder'
                                      AND action = 'view'
                                      AND cmid = :cmid
                                      AND userid = :userid"
            ),
            'defaultAction' => 'viewed'
        ),
		*/
        'forum' => array(
            'defaultTime'=>'assesstimefinish',
            'actions'=>array(
                'posted_to'    => "SELECT id
                                     FROM {forum_posts}
                                    WHERE userid = :userid AND discussion IN (
                                          SELECT id
                                            FROM {forum_discussions}
                                           WHERE forum = :eventid
                                    )"
            ),
            'defaultAction' => 'posted_to'
        ),
//        'socialforum' => array(
//
//            'actions'=>array(
//                'posted_to'    => "SELECT id
//                                     FROM {sf_contributions}
//                                    WHERE userid = :userid AND socialforum = :eventid"
//            ),
//            'defaultAction' => 'posted_to'
//        ),
/*
        'glossary' => array(
            'actions'=>array(
                'viewed'       => "SELECT id
                                     FROM {log}
                                   WHERE course = :courseid
                                     AND module = 'glossary'
                                     AND action = 'view'
                                     AND cmid = :cmid
                                     AND userid = :userid"
            ),
            'defaultAction' => 'viewed'
        ),*/
		
        'glossary' => array(
            'actions'=>array(
                'add_entry'       => "SELECT id
                                     FROM {progress_activities}
                                    WHERE course_id = :courseid
                                      AND course_module_id = :cmid
                                      AND user_id = :userid
									  AND status = 'add_entry'
									  AND module_name = 'glossary'"
            ),
            'defaultAction' => 'add_entry'
        ),
		/*
        'hotpot' => array(
            'defaultTime'=>'timeclose',
            'actions'=>array(
                'attempted'    => "SELECT id
                                    FROM {hotpot_attempts}
                                   WHERE hotpotid = :eventid
                                     AND userid = :userid",
                'finished'     => "SELECT id
                                     FROM {hotpot_attempts}
                                    WHERE hotpotid = :eventid
                                      AND userid = :userid
                                      AND timefinish <> 0",
            ),
            'defaultAction' => 'finished'
        ),
		*/
	    'questionnaire' => array(
            'actions'=>array(
                'iscompleted'    => "SELECT id
                                    FROM {course_modules_completion}
                                   WHERE coursemoduleid = :cmid
                                     AND userid = :userid
                                     AND completionstate = 1",
            ),
            'defaultAction' => 'iscompleted'
        ),
    	/*
	    'questionnaire' => array(
            'actions'=>array(
                'attempted'    => "SELECT id
                                    FROM {questionnaire_attempts}
                                   WHERE qid = :eventid
                                     AND userid = :userid",
                'finished'     => "SELECT id
                                     FROM {questionnaire_response}
                                    WHERE complete = 'y'
                                      AND username = :userid
                                      AND survey_id = :eventid",
                'finished'     => "SELECT qr.id
                                    FROM {questionnaire_attempts} qa
                                    INNER JOIN {questionnaire_response} qr ON (qa.rid = qr.id)
                                   WHERE qa.qid = :eventid
                                     AND qa.userid = :userid
                                     AND qr.complete = 'y'",
            ),
            'defaultAction' => 'attempted'
        ),
        */
		/*
        'imscp' => array(
            'actions'=>array(
                'viewed'       => "SELECT id
                                    FROM {log}
                                   WHERE course = :courseid
                                     AND module = 'imscp'
                                     AND action = 'view'
                                     AND cmid = :cmid
                                     AND userid = :userid"
            ),
            'defaultAction' => 'viewed'
        ),
        'journal' => array(
            'actions'=>array(
                'posted_to'    => "SELECT id
                                     FROM {journal_entries}
                                    WHERE journal = :eventid
                                      AND userid = :userid"
            ),
            'defaultAction' => 'posted_to'
        ),
        'lesson' => array(
            'defaultTime'=>'deadline',
            'actions'=>array(
                'attempted'    => "SELECT id
                                     FROM {lesson_attempts}
                                    WHERE lessonid = :eventid
                                      AND userid = :userid
                                UNION ALL
                                   SELECT id
                                     FROM {lesson_branch}
                                    WHERE lessonid = :eventid1
                                      AND userid = :userid1",
                'graded'       => "SELECT g.rawgrade
                                     FROM {grade_grades} g, {grade_items} i
                                    WHERE i.itemmodule = 'lesson'
                                      AND i.iteminstance = :eventid
                                      AND i.id = g.itemid
                                      AND g.userid = :userid"
            ),
            'defaultAction' => 'attempted'
        ),
        'page' => array(
            'actions'=>array(
                'viewed'       => "SELECT id
                                     FROM {log}
                                    WHERE course = :courseid
                                      AND module = 'page'
                                      AND action = 'view'
                                      AND cmid = :cmid
                                      AND userid = :userid"
            ),
            'defaultAction' => 'viewed'
        ),
		*/
        'quiz' => array(
            'defaultTime'=>'timeclose',
            'actions'=>array(
                'attempted'    => "SELECT id
                                     FROM {quiz_attempts}
                                    WHERE quiz = :eventid
                                      AND userid = :userid",
                'finished'     => "SELECT id
                                     FROM {quiz_attempts}
                                    WHERE quiz = :eventid
                                      AND userid = :userid
                                      AND timefinish <> 0",
                'graded'       => "SELECT g.rawgrade
                                     FROM {grade_grades} g, {grade_items} i
                                    WHERE i.itemmodule = 'quiz'
                                      AND i.iteminstance = :eventid
                                      AND i.id = g.itemid
                                      AND g.userid = :userid",
                'passed'       => "SELECT g.rawgrade
                                     FROM {grade_grades} g, {grade_items} i
                                    WHERE i.itemmodule = 'quiz'
                                      AND i.iteminstance = :eventid
                                      AND i.id = g.itemid
                                      AND g.userid = :userid
                                      AND g.rawgrade >= i.gradepass"
            ),
            'defaultAction' => 'finished'
        ),
		
        'scorm' => array(
            'actions'=>array(
                'attempted'    => "SELECT id
                                     FROM {scorm_scoes_track}
                                    WHERE scormid = :eventid
                                      AND userid = :userid",
                'completed'    => "SELECT id
                                     FROM {scorm_scoes_track}
                                    WHERE scormid = :eventid
                                      AND userid = :userid
                                      AND element = 'cmi.core.lesson_status'
                                      AND {$DB->sql_compare_text('value')} = 'completed'",
                'passed'       => "SELECT id
                                     FROM {scorm_scoes_track}
                                    WHERE scormid = :eventid
                                      AND userid = :userid
                                      AND element = 'cmi.core.lesson_status'
                                      AND {$DB->sql_compare_text('value')} = 'passed'"
            ),
            'defaultAction' => 'attempted'
        ),/*
		'nanogong' => array(
            'actions'=>array(
				'hasSeen'       => "SELECT id
                                     FROM {nanogong_audios}
                                    WHERE userid = 0",
                'participated'       => "SELECT id
                                     FROM {nanogong_audios}
                                    WHERE userid = :userid
									AND nanogongid = :eventid",
				'postedamessage' =>"SELECT id
                                     FROM {nanogong_messages}
                                    WHERE userid = :userid"
            ),
            'defaultAction' => 'hasSeen'
        ),
        'url' => array(
            'actions'=>array(
                'viewed'       => "SELECT id
                                     FROM {log}
                                    WHERE course = :courseid
                                      AND module = 'url'
                                      AND action = 'view'
                                      AND cmid = :cmid
                                      AND userid = :userid"
            ),
            'defaultAction' => 'viewed'
        ),
		*/
		/*
        'wiki' => array(
            'actions'=>array(
                'viewed'       => "SELECT id
                                     FROM {log}
                                    WHERE course = :courseid
                                      AND module = 'wiki'
                                      AND action = 'view'
                                      AND cmid = :cmid
                                      AND userid = :userid"
            ),
            'defaultAction' => 'viewed'
        ),*/
		'wiki' => array(
            'actions'=>array(
                'add_page'       => "SELECT id
                                     FROM {progress_activities}
                                    WHERE course_id = :courseid
                                      AND course_module_id = :cmid
                                      AND user_id = :userid
									  AND (status = 'add_page' OR status = 'edit_page')
									  AND module_name = 'wiki'",
            	'comment'       => "SELECT id
                                     FROM {progress_activities}
                                    WHERE course_id = :courseid
                                      AND course_module_id = :cmid
                                      AND user_id = :userid
									  AND status = 'comment'
									  AND module_name = 'wiki'",
                'add_page_group'       => "SELECT id
                                     FROM {progress_activities}
                                    WHERE course_id = :courseid
                                      AND course_module_id = :cmid
                                      AND user_id IN (SELECT userid FROM mdl_groups_members WHERE groupid IN (SELECT groupid FROM mdl_groups_members WHERE userid = :userid))
									  AND (status = 'add_page' OR status = 'edit_page')
									  AND module_name = 'wiki'",
            	'comment_group'       => "SELECT id
                                     FROM {progress_activities}
                                    WHERE course_id = :courseid
                                      AND course_module_id = :cmid
                                      AND user_id IN (SELECT userid FROM mdl_groups_members WHERE groupid IN (SELECT groupid FROM mdl_groups_members WHERE userid = :userid))
									  AND status = 'comment'
									  AND module_name = 'wiki'"
            ),
            'defaultAction' => 'add_page'
        ),
		
        'workshop' => array(
            'defaultTime'=>'assessmentend',
            'actions'=>array(
                'submitted'    => "SELECT id
                                     FROM {workshop_submissions}
                                    WHERE workshopid = :eventid
                                      AND authorid = :userid",
                'assessed'     => "SELECT s.id
                                     FROM {workshop_assessments} a, {workshop_submissions} s
                                    WHERE s.workshopid = :eventid
                                      AND s.id = a.submissionid
                                      AND a.reviewerid = :userid
                                      AND a.grade IS NOT NULL",
                'graded'       => "SELECT g.rawgrade
                                     FROM {grade_grades} g, {grade_items} i
                                    WHERE i.itemmodule = 'workshop'
                                      AND i.iteminstance = :eventid
                                      AND i.id = g.itemid
                                      AND g.userid = :userid"
            ),
            'defaultAction' => 'submitted'
        ),/*
		 'scormcentralized' => array(
            'actions'=>array(
                'viewed'       => "SELECT id
                                     FROM {log}
                                    WHERE course = :courseid
                                      AND module = 'scormcentralized'
                                      AND action = 'view'
                                      AND cmid = :cmid
                                      AND userid = :userid"
            ),
            'defaultAction' => 'viewed'
        ),*/
        /*
		'scormcentralized' => array(
            'actions'=>array(
                'viewed'       => "SELECT id
                                     FROM {progress_activities}
                                    WHERE course_id = :courseid
                                      AND course_module_id = :cmid
                                      AND user_id = :userid
									  AND status = 'viewed'
									  AND module_name = 'scormcentralized'"
            ),
            'defaultAction' => 'viewed'
        ),*/
		// BBB
		/*
		'bigbluebuttonbn' => array(
            'actions'=>array(
                'viewed'       => "SELECT id
                                     FROM {log}
                                    WHERE course = :courseid
                                      AND module = 'bigbluebuttonbn'
                                      AND action = 'view'
                                      AND cmid = :cmid
                                      AND userid = :userid"
            ),
            'defaultAction' => 'viewed'
        ),
		*/
		/*
		// centralized ressources
		'centralizedresource' => array(
            'actions'=>array(
                'viewed'       => "SELECT id
                                     FROM {log}
                                    WHERE course = :courseid
                                      AND module = 'centralizedresource'
                                      AND action = 'view'
                                      AND cmid = :cmid
                                      AND userid = :userid"
            ),
            'defaultAction' => 'viewed'
        ),*/
		// VIA & VIAASSIGN
		'via' => array(
            'actions'=>array(
                'participated'       	=> "SELECT id
													FROM {via_presence}
													WHERE activityid IN (SELECT instance 
																									FROM {course_modules}
																									WHERE id = :cmid)
													AND userid = :userid
													AND status = 1"
            ),
            'defaultAction' => 'participated'
        ),
        
        'viaassign' => array(
        	'actions'=>array(
        		'participated'      => "SELECT p.id
													FROM {via_presence} p 
        											INNER JOIN {viaassign_submission} s
													ON s.viaid =  p.activityid
        											WHERE s.viaassignid IN (SELECT instance
																									FROM {course_modules}
																									WHERE id = :cmid)
													AND p.userid = :userid
													AND p.status = 1",
            	'created'       	=> "SELECT id
													FROM {viaassign_submission} 
													WHERE viaassignid IN (SELECT instance
																									FROM {course_modules}
																									WHERE id = :cmid)
													AND userid = :userid
													AND status = 'created'",
        	),
        	'defaultAction' => 'participated'
        ),
		
        'choicegroup' => array(
            'actions'=>array(
                'selected_group'       => "SELECT id
                                     FROM {progress_activities}
                                    WHERE course_id = :courseid
                                      AND course_module_id = :cmid
                                      AND user_id = :userid
									  AND status = 'selected'
									  AND module_name = 'choicegroup'"
            ),
            'defaultAction' => 'selected_group'
        ),
        
        
    );
}


/**
 * Checks if a variable has a value and returns a default value if it doesn't
 *
 * @param mixed $var The variable to check
 * @param mixed $def Default value if $var is not set
 * @return string
 */
function progress_default_value(&$var, $def = null) {
    return isset($var)?$var:$def;
}

/**
 * Filters the modules list to those installed in Moodle instance and used in current course
 *
 * @return array
 */
function modules_in_use() {
    global $COURSE, $DB;
    $dbmanager = $DB->get_manager(); // used to check if tables exist
    $modules = get_monitorable_modules();
    $modulesinuse = array();
    foreach ($modules as $module => $details) {

        if (
            $dbmanager->table_exists($module) &&
            $DB->record_exists($module, array('course'=>$COURSE->id))
        ) {

            $modulesinuse[$module] = $details;
        }
    }
    return $modulesinuse;
}

//ajout MRO
function modules_in_use_by_course_id($course_id)
{
    global $DB;

    $dbmanager = $DB->get_manager(); // used to check if tables exist
    $modules = get_monitorable_modules();
    $modulesinuse = array();

    foreach ($modules as $module => $details) {
        if (
            $dbmanager->table_exists($module) &&
            $DB->record_exists($module, array('course'=>$course_id))
        ) {
            $modulesinuse[$module] = $details;
        }
    }
    return $modulesinuse;
}



/**
 * Gets event information about modules monitored by an instance of a Progress Bar block
 *
 * @param stdClass $config  The block instance configuration values
 * @param array    $modules The modules used in the course
 * @return mixed   returns array of visible events monitored,
 *                 empty array if none of the events are visible,
 *                 null if all events are configured to "no" monitoring and
 *                 0 if events are available but no config is set
 */
function event_information($config, $modules) {
    global $COURSE, $DB;
    
    $dbmanager = $DB->get_manager(); // used to check if tables exist
    $events = array();
    $numevents = 0;
    $numeventsconfigured = 0;

    if(isset($config->orderby) && $config->orderby == 'orderbycourse') {
        $sections = $DB->get_records('course_sections', array('course'=>$COURSE->id), 'section', 'id,sequence');
        foreach ($sections as $section) {
            $section->sequence = explode(',', $section->sequence);
        }
    }

    // Check each known module (described in lib.php)
    foreach ($modules as $module => $details) {
        $fields = 'id, name';
        if (array_key_exists('defaultTime', $details)) {
            $fields .= ', '.$details['defaultTime'].' as due';
        }

        // Check if this type of module is used in the course, gather instance info
        $records = $DB->get_records($module, array('course'=>$COURSE->id), '', $fields);
        foreach ($records as $record) {

            // Is the module being monitored?
            if (isset($config->{'monitor_'.$module.$record->id})) {
                $numeventsconfigured++;
            }
            if (progress_default_value($config->{'monitor_'.$module.$record->id}, 0)==1) {
                $numevents++;
                
                
                
                // Check the time the module is due
                if (
                    isset($details['defaultTime']) &&
                    $record->due != 0 &&
                    progress_default_value($config->{'locked_'.$module.$record->id}, 0)
                ) {
                    $expected = progress_default_value($record->due);
                }
                else {
                    $expected = $config->{'date_time_'.$module.$record->id};
                }

                // Get the course module info
                $coursemodule = get_coursemodule_from_instance($module, $record->id, $COURSE->id);

                // Check if the module is visible, and if so, keep a record for it
                if ($coursemodule->visible==1) {
                    $event = array(
                        'expected'=>$expected,
                        'type'=>$module,
                        'id'=>$record->id,
                        'name'=>format_string($record->name),
                        'cmid'=>$coursemodule->id,
                    );
                    if(isset($config->orderby) && $config->orderby == 'orderbycourse') {
                        $event['section'] = $coursemodule->section;
                        $event['position'] = array_search($coursemodule->id, $sections[$coursemodule->section]->sequence);
                    }
                    //MRO: on recupere la position du module
                    $event['custom_position'] = is_numeric(trim($config->{'position_'.$module.$record->id})) ? (int) $config->{'position_'.$module.$record->id} : 999999;
                    $events[] = $event;
                }
            }
        }
    }

    if ($numeventsconfigured==0) {
        return 0;
    }
    if ($numevents==0) {
        return null;
    }

    // Sort by first value in each element, which is time due
/*    if(isset($config->orderby) && $config->orderby == 'orderbycourse') {
        usort($events, 'compare_events');
    }
    else {
        sort($events);
    }
 */
    //modif MRO
    usort($events, 'custom_compare_events');
    return $events;
}
//ajout MRO
function custom_event_information($config, $modules, $course_id)
{
	 global $COURSE, $DB;


    $dbmanager = $DB->get_manager(); // used to check if tables exist
    $events = array();
    $numevents = 0;
    $numeventsconfigured = 0;

    if(isset($config->orderby) && $config->orderby == 'orderbycourse') {
        $sections = $DB->get_records('course_sections', array('course'=>$course_id), 'section', 'id,sequence');
        foreach ($sections as $section) {
            $section->sequence = explode(',', $section->sequence);
        }
    }

    // Check each known module (described in lib.php)
    foreach ($modules as $module => $details) {
        $fields = 'id, name';
        if (array_key_exists('defaultTime', $details)) {
            $fields .= ', '.$details['defaultTime'].' as due';
        }

        // Check if this type of module is used in the course, gather instance info
        $records = $DB->get_records($module, array('course'=>$course_id), '', $fields);
        foreach ($records as $record) {

            // Is the module being monitored?
            if (isset($config->{'monitor_'.$module.$record->id})) {
                $numeventsconfigured++;
            }
            if (progress_default_value($config->{'monitor_'.$module.$record->id}, 0)==1) {
                $numevents++;

                // Check the time the module is due
                if (
                    isset($details['defaultTime']) &&
                    $record->due != 0 &&
                    progress_default_value($config->{'locked_'.$module.$record->id}, 0)
                ) {
                    $expected = progress_default_value($record->due);
                }
                else {
                    $expected = $config->{'date_time_'.$module.$record->id};
                }

                // Get the course module info
                $coursemodule = get_coursemodule_from_instance($module, $record->id, $course_id);

                // Check if the module is visible, and if so, keep a record for it
                if ($coursemodule->visible==1) {
                    $event = array(
                        'expected'=>$expected,
                        'type'=>$module,
                        'id'=>$record->id,
                        'name'=>format_string($record->name),
                        'cmid'=>$coursemodule->id,
                    );
                    if(isset($config->orderby) && $config->orderby == 'orderbycourse') {
                        $event['section'] = $coursemodule->section;
                        $event['position'] = array_search($coursemodule->id, $sections[$coursemodule->section]->sequence);
                    }
                    $events[] = $event;
                }
            }
        }
    }

    if ($numeventsconfigured==0) {
        return 0;
    }
    if ($numevents==0) {
        return null;
    }

    // Sort by first value in each element, which is time due
    if(isset($config->orderby) && $config->orderby == 'orderbycourse') {
        usort($events, 'compare_events');
    }
    else {
        sort($events);
    }
    return $events;
}

/**
 * Used to compare two activities/resources based on order on course page
 *
 * @param array $a array of event information
 * @param array $b array of event information
 * @return <0, 0 or >0 depending on order of activities/resources on course page
 */
function custom_compare_events($a, $b) {
    if ($a['custom_position'] == $b['custom_position']) {
        return 0;
    }
    return ($a['custom_position'] < $b['custom_position']) ? -1 : 1;
}

function compare_events($a, $b) {
    if($a['section'] != $b['section']) {
        return $a['section'] - $b['section'];
    }
    else {
        return $a['position'] - $b['position'];
    }
}

/**
 * Checked if a user has attempted/viewed/etc. an activity/resource
 *
 * @param array    $modules The modules used in the course
 * @param stdClass $config  The blocks configuration settings
 * @param array    $events  The possible events that can occur for modules
 * @param int      $userid  The user's id
 * @return array   an describing the user's attempts based on module+instance identifiers
 */
function get_attempts($modules, $config, $events, $userid, $instance)
{
    global $COURSE, $DB, $SESSION;
    $attempts = array();
    
	//TCS BEGIN 2015/02/03 - 585
	if ( isset($SESSION->block_progress_cache) )
	{
        if ( isset($SESSION->block_progress_cache[$userid]) )
        {
            if ( isset($SESSION->block_progress_cache[$userid][$COURSE->id]) )
            {
                if ( isset($SESSION->block_progress_cache[$userid][$COURSE->id]['data']) )
                {
                    return $SESSION->block_progress_cache[$userid][$COURSE->id]['data'];
                }
            }
        }
	}
	//TCS END 2015/02/03 - 585
	if($events){
        foreach ($events as $event) {
            $module = $modules[$event['type']];
            $uniqueid = $event['type'].$event['id'];

            // If activity completion is used, check completions table
            if (isset($config->{'action_'.$uniqueid}) &&
                $config->{'action_'.$uniqueid}=='activity_completion'
            ) {
                $query = 'SELECT id
                        FROM {course_modules_completion}
                       WHERE userid = :userid
                         AND coursemoduleid = :cmid
                         AND completionstate = 1';
            }

            // Determine the set action and develop a query
            else {
                $action = isset($config->{'action_'.$uniqueid})?
                    $config->{'action_'.$uniqueid}:
                    $details['defaultAction'];
                $query =  $module['actions'][$action];
            }
            $parameters = array('courseid' => $COURSE->id, 'courseid1' => $COURSE->id,
                'userid' => $userid, 'userid1' => $userid,
                'eventid' => $event['id'], 'eventid1' => $event['id'],
                'cmid' => $event['cmid'], 'cmid1' => $event['cmid'],
            );

            if (!empty($query) && isset($query) && $query != '')
            {
                // Check if the user has attempted the module
                $attempts[$uniqueid] = $DB->record_exists_sql($query, $parameters)?true:false;
            }
            else
            {
                $attempts[$uniqueid] = false;
            }
        }
    }
    
	//TCS BEGIN 2015/02/03 - 585
	if ( !isset($SESSION->block_progress_cache) ){ $SESSION->block_progress_cache = array(); }
    if ( !isset($SESSION->block_progress_cache[$userid]) ){ $SESSION->block_progress_cache[$userid] = array(); }
	if ( !isset($SESSION->block_progress_cache[$userid][$COURSE->id]) ){ $SESSION->block_progress_cache[$userid][$COURSE->id] = array(); }
    if ( !isset($SESSION->block_progress_cache['firstcache'])) { $SESSION->block_progress_cache['firstcache'] = time(); }
	$SESSION->block_progress_cache[$userid][$COURSE->id]['data'] = $attempts;
	$SESSION->block_progress_cache[$userid][$COURSE->id]['last_update'] = time();
	//TCS END 2015/02/03 - 585
	
	return $attempts;
}
//ajout MRO
function custom_get_attempts($modules, $config, $events, $userid, $instance) {
    global $DB;
    $attempts = array();
	
    foreach ($events as $event) {
        $module = $modules[$event['type']];
        $uniqueid = $event['type'].$event['id'];

        // If activity completion is used, check completions table
        if (isset($config->{'action_'.$uniqueid}) &&
            $config->{'action_'.$uniqueid}=='activity_completion'
        ) {
            $query = 'SELECT id
                        FROM {course_modules_completion}
                       WHERE userid = :userid
                         AND coursemoduleid = :cmid
                         AND completionstate = 1';
        }

        // Determine the set action and develop a query
        else {
            $action = isset($config->{'action_'.$uniqueid})?
                      $config->{'action_'.$uniqueid}:
                      $details['defaultAction'];
            $query =  $module['actions'][$action];
        }
        $parameters = array('courseid' => $instance, 'courseid1' => $instance,
                            'userid' => $userid, 'userid1' => $userid,
                            'eventid' => $event['id'], 'eventid1' => $event['id'],
                            'cmid' => $event['cmid'], 'cmid1' => $event['cmid'],
                      );

         // Check if the user has attempted the module
        $attempts[$uniqueid] = $DB->record_exists_sql($query, $parameters)?true:false;
    }

	return $attempts;
}

/**
 * Draws a progress bar
 *
 * @param array    $modules  The modules used in the course
 * @param stdClass $config   The blocks configuration settings
 * @param array    $events   The possible events that can occur for modules
 * @param int      $userid   The user's id
 * @param int      instance  The block instance (incase more than one is being displayed)
 * @param array    $attempts The user's attempts on course activities
 * @param bool     $simple   Controls whether instructions are shown below a progress bar
 */
function progress_bar($modules, $config, $events, $userid, $instance, $attempts, $simple = false, $nonow = false) {
	global $OUTPUT, $CFG, $PAGE;

	$now = time();
	$numevents = count($events);
	$dateformat = get_string('date_format', 'block_progress');
	$divoptions = array('class' => 'progressBarDiv');
	$content = HTML_WRITER::start_tag('div', $divoptions);
	
	// Place now arrow
	$enable_now = ((!isset($config->orderby) || $config->orderby=='orderbytime') && $config->displayNow==1 && !$simple);
	$isformattopics = (strpos($PAGE->bodyclasses, 'format-topics') !== false);

	if($isformattopics){
	    $enable_now = false;
    }

	// Start progress bar
	$width = 99/$numevents;
	$content .= HTML_WRITER::start_tag('ul');
	$nowpos = 0;
	if($nonow)	{
		$nowpos = -1;
	} else {
		while ($nowpos<$numevents && $now>$events[$nowpos]['expected']) {
			$nowpos++;
		}
	}

	foreach($events as $pos=>$event) {
		$attempted = $attempts[$event['type'].$event['id']];
		
		$cell_class = '';
		
		if($enable_now){
			if(($nowpos<$numevents/2) && $pos==$nowpos){
				$cell_class = ' progress-cursor';
			} else if ( ($nowpos>=$numevents/2) && $pos==$nowpos-1)	{
				$cell_class = ' progress-cursor right';
			}
		}
		
		// A cell in the progress bar
		$celloptions = array(
				'class' => 'progressBarCell'.$cell_class,
				'onclick' => 'document.location=\''.$CFG->wwwroot.'/mod/'.$event['type'].
				'/view.php?id='.$event['cmid'].'\';',
				'onmouseover' => 'M.block_progress.showInfo('.
				'\''.$event['type'].'\', '.
				'\''.get_string($event['type'], 'block_progress').'\', '.
				'\''.$event['cmid'].'\', '.
				'\''.addslashes($event['name']).'\', '.
				'\''.get_string($config->{'action_'.$event['type'].$event['id']}, 'block_progress').'\', '.
				'\''.userdate($event['expected'], $dateformat, $CFG->timezone).'\', '.
				'\''.$instance.'\', '.
				'\''.$userid.'\', '.
				'\''.($attempted?'tick':'cross').'\''.
				');',
				'style' => 'width:'.$width.'%;');
		if($attempted) {
			$cellcontent = '<div class="progressBarCellContent attemptedcolour">'.$OUTPUT->pix_icon(
					isset($config->progressBarIcons) && $config->progressBarIcons==1 ?
					'check' : 'blank', '', 'block_progress').'</div>';
		} else if ((!isset($config->orderby) || $config->orderby=='orderbytime') && $event['expected'] < $now) {
			$cellcontent = '<div class="progressBarCellContent notattemptedcolour"></div>';
		} else {
			$cellcontent = '<div class="progressBarCellContent futurenotattemptedcolour"></div>';
		}
		$content .= HTML_WRITER::tag('li', $cellcontent, $celloptions);
	}

	$content .= HTML_WRITER::end_tag('ul');
	$content .= HTML_WRITER::end_tag('div');

	// Add the info box below the table
	$divoptions = array('class' => 'progressEventInfo',
			'id'=>'progressBarInfo'.$instance.'user'.$userid);
	$content .= HTML_WRITER::start_tag('div', $divoptions);
	if(!$simple) {
		$content .= HTML_WRITER::start_tag('div', array('class'=>'progressEventInfo_default'));
		$content .= get_string('mouse_over_prompt', 'block_progress');
		$content .= HTML_WRITER::end_tag('div');
		if(isset($config->showpercentage) && $config->showpercentage==1) {
			$progress = get_progess_percentage($events, $attempts);
			$content .= HTML_WRITER::empty_tag('br');
			$content .= get_string('progress', 'block_progress').': ';
			$content .= $progress.'%'.HTML_WRITER::empty_tag('br');
		}
	}
	$content .= HTML_WRITER::end_tag('div');

	return $content;
}


/**
 * Calculates an overall percentage of progress
 *
 * @param array $events   The possible events that can occur for modules
 * @param array $attempts The user's attempts on course activities
 */
function get_progess_percentage($events, $attempts) {
    $attemptcount = 0;

    foreach ($events as $event) {
        if ($attempts[$event['type'].$event['id']]==1) {
            $attemptcount++;
        }
    }

    $progressvalue = $attemptcount==0?0:$attemptcount/count($events);

    return (int)($progressvalue*100);
}