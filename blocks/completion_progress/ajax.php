<?php

ini_set("mysql.trace_mode", "0");

require_once('../../config.php');
global $DB, $PAGE, $OUTPUT, $SESSION, $USER;

error_reporting(0);
ini_set("display_errors", 0);

require_once($CFG->dirroot . '/blocks/completion_progress/lib.php');
require_once($CFG->dirroot . '/local/gaia/lib/GaiaUtils.php');
require_once($CFG->dirroot . '/blocks/completion_progress/ParticipantsList.php');

$action = required_param('action', PARAM_ALPHA);

if($action == "refresh")
{
    if (isset($SESSION->block_progress_cache))
    {
        if ( (time() - $SESSION->block_progress_cache['firstcache']) > 60 )
        {
            unset($SESSION->block_progress_cache);
        }
    }
    die();
}
else if($action == "list")
{
    $participantList = new ParticipantList();

	$so = required_param('so', PARAM_TEXT);
	$si = required_param('si', PARAM_INT);
	$ps = required_param('ps', PARAM_INT);

    $id = required_param('id', PARAM_INT);
    $courseid = required_param('courseid', PARAM_INT);
    $contextid = required_param('contextid', PARAM_INT);
    $role = required_param('role', PARAM_INT);
    $group = optional_param('group',0, PARAM_INT);
    $realized = required_param('realized', PARAM_INT);
    $activity = required_param('activity', PARAM_TEXT);
    $name = required_param('name', PARAM_TEXT);
    $neverconnected = required_param('neverconnected', PARAM_TEXT);
    $sother = required_param('sgaiaother', PARAM_BOOL);


    $participantList->setId($id);
    $participantList->setContextId($contextid);
    $participantList->setSortOrder($so);
    $participantList->setStartIndex($si);
    $participantList->setPageSize($ps);
    $participantList->setCourseid($courseid);


    // json data
    $sgaia = required_param('sgaia', PARAM_TEXT);
    $sgaia = json_decode($sgaia);
    
    
    // Determine course and context
    $course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
    //$context = context_course::instance($courseid);
    
    require_login($course, false);
    
    $PAGE->set_course($course);

    // role
    if($role != 0){
        $participantList->setRoleId($role);
    }

    // group
    if ($group > 0){
        $participantList->setGroupId($group);
    }

    // name
    $name = str_replace("\\",'',str_replace("'",'',str_replace('%','',trim($name))));
    if ($name != '')
    {
    	$words = explode(' ', $name);

    	foreach($words as $word){
    	    $participantList->addName($word);
    	}
    }


    // activities
    if ($activity != 'none' && strlen($activity) > 2)
    {
        if($realized == 1){
            $participantList->setIsRealized();
        }

        $activity_ex = explode('*',$activity);
        $activityname = $activity_ex[0];
        $activityid = $activity_ex[1];

        $participantList->setActivity($activityname, $activityid);
    }

    // neverconnected
    if ($neverconnected == 'e')
    {
        $participantList->setIsNeverConnected();
    }

    if(count($sgaia) > 0){
        foreach($sgaia as $data){
            $d = explode('-', $data->value);
            $sid = $d[0]; // session gaia id
            $did = $d[1]; // dispositif id
            $mid = $d[2]; // module id

            $participantList->addGaiaSession($sid, $did, $mid);
        }
    }

    if($sother){
        $participantList->setOtherGaiaSession();
    }

    $result  = $participantList->getData();

    $result2 = $participantList->getCountData();

    //Return result to jTable
    $jTableResult = array();
    $jTableResult['Result'] = "OK";
    $jTableResult['TotalRecordCount'] = $result2;
    $jTableResult['Records'] = $result;
    
    print json_encode($jTableResult);
}
