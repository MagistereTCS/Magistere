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
 * Blog Menu Block page.
 *
 * @package    block
 * @subpackage course_migration
 * @copyright  2017 TCS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->dirroot.'/blocks/course_migration/lib.php');

$a = optional_param('a', null, PARAM_TEXT);
$id = required_param('id', PARAM_INT);
$keepdata = optional_param('keepdata', false, PARAM_BOOL);

require_login($id);

$context = context_course::instance($id);


if (!has_capability('block/course_migration:showmigrationblock',$context) )
{
	echo json_encode(array("result"=>false));
	die;
}


// Validate migration and delete source course
if ($a == 'va' && $id > 1 )
{
    $res = false;
    
    if (!has_capability('block/course_migration:removeflexpagecourse', $context))
    {
        echo json_encode(array("result"=>false));
        die;
    }
    
    $course = $DB->get_record('course', array('id'=>$id));
    
    if ($course === false)
    {
        echo json_encode(array("result"=>false));
        die;
    }
    
    if ($course->visible != 1)
    {
        $course->visible = 1;
        $DB->update_record('course', $course);
    }
    
    $task = $DB->get_record_sql('SELECT id,flexcourseid,stdcourseid,userid,status,startdate,enddate,originalformat,convertedformat,keepdata,validationlogs,validated FROM {block_course_migration} WHERE stdcourseid=? AND status = 1', array($course->id));
    
    if($task === false){
        echo json_encode(array("result"=>false));
        die;
    }
    
    // Swap des activités via
    define('MIG_VAL_NO_DEBUG',true);
    $mv = new migrationValidation();
    $error = $mv->migrate_via($task);
    
    if ($error === false)
    {
        $res = delete_course($task->flexcourseid, false);
    }else{
        echo json_encode(array("result"=>false));
        die;
    }
    
    // Remove course_migration block from converted course
    $course_context = context_course::instance($course->id);
    
    $migration_blocks = $DB->get_records('block_instances', array('parentcontextid'=>$course_context->id,'blockname'=>'course_migration'));
    
    foreach($migration_blocks AS $migration_block)
    {
        $DB->delete_records('block_instances',array('id'=>$migration_block->id));
        $DB->delete_records('block_positions',array('blockinstanceid'=>$migration_block->id));
    }
    
    echo json_encode(array("result"=>$res));
}
// Remove source course
else if ($a == 'rs' && $id > 1 )
{
    $res = false;
    
    if (!has_capability('block/course_migration:removeflexpagecourse', $context))
    {
        echo json_encode(array("result"=>false));
        die;
    }
    
    $course = $DB->get_record('course', array('id'=>$id), '*');
    
    if ($course === false)
    {
        echo json_encode(array("result"=>false));
        die;
    }

    if ($course->visible != 1)
    {
        $course->visible = 1;
        $DB->update_record('course', $course);
    }
    
    $migration = $DB->get_record_sql('SELECT * FROM {block_course_migration} WHERE stdcourseid=? AND status = 1', array($course->id));
    
    if($migration === false){
        echo json_encode(array("result"=>false));
        die;
    }
    $res = delete_course($migration->flexcourseid, false);
    
    
    // Remove course_migration block from converted course
    $course_context = context_course::instance($course->id);
    
    $migration_blocks = $DB->get_records('block_instances', array('parentcontextid'=>$course_context->id,'blockname'=>'course_migration'));
    
    foreach($migration_blocks AS $migration_block)
    {
        $DB->delete_records('block_instances',array('id'=>$migration_block->id));
        $DB->delete_records('block_positions',array('blockinstanceid'=>$migration_block->id));
    }
    
    echo json_encode(array("result"=>$res));
}
// Remove converted course
else if ($a == 'rc' && $id > 1 )
{
    $res = false;
    
    if (!has_capability('block/course_migration:removeflexpagecourse', $context))
    {
        echo json_encode(array("result"=>false));
        die;
    }
    
    $course = $DB->get_record('course', array('id'=>$id), '*');
    
    if ($course === false)
    {
        echo json_encode(array("result"=>false));
        die;
    }
    
    $migration = $DB->get_record_sql('SELECT * FROM {block_course_migration} WHERE stdcourseid=? AND status = 1', array($course->id));
    
    if($migration === false){
        echo json_encode(array("result"=>false));
        die;
    }
    
    $res = delete_course($migration->stdcourseid, false);
    
    $url = new moodle_url('/course/view.php',array('id'=>$migration->flexcourseid));
    
    echo json_encode(array('result'=>$res,'url'=>$url->out(true)));
}
else if ($a != 'd' && $id > 1 )
{
	$res = true;
	
	if (!has_capability('block/course_migration:convertcourse', $context))
	{
		echo json_encode(array("result"=>false));
		die;
	}
	
	$course = $DB->get_record('course', array('id'=>$id), '*');
	
	if ($course === false)
	{
		echo json_encode(array("result"=>false));
		die;
	}

    $convertorclass = 'Convert'.ucfirst($course->format).'To'.ucfirst($a);
    if(file_exists($CFG->dirroot.'/blocks/course_migration/'.$convertorclass.'.php'))
    {
        require_once($CFG->dirroot.'/blocks/course_migration/'.$convertorclass.'.php');
        $convertor = new $convertorclass();
        $convertor->add_conversion_task($id,$keepdata);
    }else{
        $res = false;
    }
	
	echo json_encode(array("result"=>$res));
}
