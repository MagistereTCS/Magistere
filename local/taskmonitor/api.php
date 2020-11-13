<?php

/**
 * Taskmonitor local plugin
 * This API use the class TaskMonitor to transmit data to the Frontend
 *
 * @package    local_taskmonitor
 * @copyright  2020 TCS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->dirroot.'/local/taskmonitor/TaskMonitor.php');
require_once($CFG->dirroot.'/local/magisterelib/databaseConnection.php');

/*
@ini_set('display_errors', '0');
$CFG->debug = false; //E_ALL | E_STRICT;   // DEBUG_DEVELOPER // NOT FOR PRODUCTION SERVERS!
$CFG->debugdisplay = false;
*/

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/local/taskmonitor/api.php');
require_login();

require_capability('local/taskmonitor:access', context_system::instance());

$priority = required_param('priority', PARAM_INT);
$view = required_param('view', PARAM_INT);
$platform = required_param('platform', PARAM_ALPHAEXT);
$tasks = optional_param_array('tasks', array(), PARAM_TEXT);

$startindex = required_param('startindex', PARAM_INT);
$pagesize = required_param('pagesize', PARAM_INT);

$sorting = optional_param('sorting', 'classname ASC', PARAM_TEXT);

if ($view == TaskMonitor::VIEW_ALL){
    if(!isset($CFG->academie_name) || $CFG->academie_name != 'dgesco'){
        echo TaskMonitor::jtableGetAllTasks($priority,$sorting,$startindex,$pagesize);
    }
}else if ($view == TaskMonitor::VIEW_PLATFORM){
    if(isset($CFG->academie_name) && $CFG->academie_name == 'dgesco'){
        echo TaskMonitor::jtableGetPlateformTasks($priority,$platform,$sorting,$startindex,$pagesize);
    }
}else if ($view == TaskMonitor::VIEW_TASK){
    echo TaskMonitor::jtableGetTasks($priority,$tasks,$sorting,$startindex,$pagesize);
}else if ($view == TaskMonitor::VIEW_TASKERROR){
    if(isset($CFG->academie_name) && $CFG->academie_name == 'dgesco'){
        echo TaskMonitor::jtableGetAllACATaskError($priority,$sorting,$startindex,$pagesize);
    }else{
        echo TaskMonitor::jtableGetTaskError($priority,$sorting,$startindex,$pagesize);
    }
}else{
    echo 'Invalid request';
}