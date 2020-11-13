<?php

function xmldb_block_progress_upgrade($oldversion=0) {
    global $DB;

    $dbman  = $DB->get_manager();
    $result = true;

    
    
    if ($oldversion < 2013090601)
    {

        // Define table progress_activities to be created
        $table = new xmldb_table('progress_activities');

        // Adding fields to table progress_activities
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
		$table->add_field('course_id', XMLDB_TYPE_INTEGER, '9', null, XMLDB_NOTNULL, null, null);
        $table->add_field('course_module_id', XMLDB_TYPE_INTEGER, '9', null, XMLDB_NOTNULL, null, null);
        $table->add_field('user_id', XMLDB_TYPE_INTEGER, '9', null, XMLDB_NOTNULL, null, null);
		$table->add_field('add_date', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
		$table->add_field('module_name', XMLDB_TYPE_CHAR, '30', null, XMLDB_NOTNULL, null, null);
        $table->add_field('status', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, null);

        
        // Adding keys to table progress_activities
		$table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('unique_ccu', XMLDB_KEY_UNIQUE, array('course_id', 'course_module_id', 'user_id', 'status'));

        // Conditionally launch create table for progress_activities
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        
        
        
        
        unset($table);
        // Define table progress_instance_activity to be created
        $table = new xmldb_table('progress_instance_activity');

        // Adding fields to table progress_instance_activity
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('instanceid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('num', XMLDB_TYPE_INTEGER, '5', null, XMLDB_NOTNULL, null, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '30', null, XMLDB_NOTNULL, null, null);
        $table->add_field('monitor', XMLDB_TYPE_INTEGER, '3', null, XMLDB_NOTNULL, null, null);
        $table->add_field('date_time', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('action', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('position', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('completed', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table progress_instance_activity
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('unique_ii', XMLDB_KEY_UNIQUE, array('instanceid, userid, num, name'));

        // Conditionally launch create table for progress_instance_activity
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        

        // progress savepoint reached
        upgrade_block_savepoint(true, 2013090601, 'progress');
    }
    
    /*
    if ($oldversion < 2015030401)
    {
    	 
    	$table = new xmldb_table('progress_activities');
    	 
    	$table->deleteKey('unique_ccu');
    	$table->add_key('unique_ccu', XMLDB_KEY_UNIQUE, array('course_id', 'course_module_id', 'user_id', 'status'));
    	 
    	$dbman->
    	 
    	 
    }
*/
    return $result;
}