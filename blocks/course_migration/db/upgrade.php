<?php

function xmldb_block_course_migration_upgrade($oldversion=0) {
    global $DB;

    $dbman  = $DB->get_manager();
    $result = true;
    
    if ($oldversion < 2017062100)
    {
    	
    	// Define table block_course_migration to be created
    	$table = new xmldb_table('block_course_migration');
    	
    	// Adding fields to table progress_activities
    	$table->add_field('id', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
    	$table->add_field('flexcourseid', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null);
    	$table->add_field('stdcourseid', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null);
    	$table->add_field('status', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null);
    	$table->add_field('logs', XMLDB_TYPE_TEXT, '11', null, null, null, null);
    	
    	
    	// Adding keys to table block_course_migration
    	$table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
    	$table->add_key('u_flexstdid', XMLDB_KEY_UNIQUE, array('flexcourseid', 'stdcourseid'));
    	
    	// Conditionally launch create table for block_course_migration
    	if (!$dbman->table_exists($table)) {
    		$dbman->create_table($table);
    	}
    	
    	// progress savepoint reached
    	upgrade_block_savepoint(true, 2017062100, 'course_migration');
    }
    
    if ($oldversion < 2017062300)
    {
    	
    	// Define table block_course_migration to be created
    	$table = new xmldb_table('block_course_migration');
    	
    	$field_startdate = new xmldb_field('startdate', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null);
    	$field_enddate = new xmldb_field('enddate', XMLDB_TYPE_INTEGER, '11', null, null, null, null);
    	
    	
    	// Conditionally launch add field for block_course_migration
    	if (!$dbman->field_exists($table,$field_startdate)) {
    		$dbman->add_field($table, $field_startdate);
    	}
    	
    	if (!$dbman->field_exists($table,$field_enddate)) {
    		$dbman->add_field($table, $field_enddate);
    	}
    	
    	// progress savepoint reached
    	upgrade_block_savepoint(true, 2017062300, 'course_migration');
    }
    
    if ($oldversion < 2017062301) {
    	
    	// Changing nullability of field stdcourseid on table block_course_migration to not null.
    	$table = new xmldb_table('block_course_migration');
    	$field = new xmldb_field('stdcourseid', XMLDB_TYPE_INTEGER, '11', null, null, null, null, 'flexcourseid');
    	
    	
    	$key = new xmldb_key('u_flexstdid', XMLDB_KEY_UNIQUE, array('flexcourseid', 'stdcourseid'));
    	
    	// Launch drop key u_flexstdid.
    	$dbman->drop_key($table, $key);
    	
    	
    	
    	// Launch change of nullability for field stdcourseid.
    	$dbman->change_field_notnull($table, $field);
    	
    	
    	// Launch drop key u_flexstdid.
    	$dbman->add_key($table, $key);
    	
    	// Summary savepoint reached.
    	upgrade_block_savepoint(true, 2017062301, 'course_migration');
    }
    
    if ($oldversion < 2017070605) {
    	
    	// Changing nullability of field stdcourseid on table block_course_migration to not null.
    	$table = new xmldb_table('block_course_migration');
    	$field = new xmldb_field('userid', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, 0, 'stdcourseid');
    	
    	// Launch change of nullability for field stdcourseid.
    	if (!$dbman->field_exists($table,$field)) {
    		$dbman->add_field($table, $field);
    	}
    	
    	
    	// Summary savepoint reached.
    	upgrade_block_savepoint(true, 2017070605, 'course_migration');
    }
    
    if ($oldversion < 2018100204) {
        
        // Changing nullability of field stdcourseid on table block_course_migration to not null.
        $table = new xmldb_table('block_course_migration');
        
        $field_from = new xmldb_field('originalformat', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, 'flexpage');
        if (!$dbman->field_exists($table,$field_from)) {
            $dbman->add_field($table, $field_from);
        }
        
        $field_into = new xmldb_field('convertedformat', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, 'topics');
        if (!$dbman->field_exists($table,$field_into)) {
            $dbman->add_field($table, $field_into);
        }
        
        // Summary savepoint reached.
        upgrade_block_savepoint(true, 2018100204, 'course_migration');
    }
    
    if ($oldversion < 2019010200) {
        
        // Changing nullability of field stdcourseid on table block_course_migration to not null.
        $table = new xmldb_table('block_course_migration');
        
        $field_keepdata = new xmldb_field('keepdata', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, 0, 'convertedformat');
        if (!$dbman->field_exists($table,$field_keepdata)) {
            $dbman->add_field($table, $field_keepdata);
        }
        
        // Summary savepoint reached.
        upgrade_block_savepoint(true, 2019010200, 'course_migration');
    }
    
    if ($oldversion < 2019021100)
    {
        
        // Define table block_course_migration_via to be created
        $table = new xmldb_table('block_course_migration_via');
        
        // Adding fields to table block_course_migration_via
        $table->add_field('id', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('old_viaid', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null);
        $table->add_field('new_viaid', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null);
        
        
        // Adding keys to table block_course_migration_via
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('u_oldid', XMLDB_KEY_UNIQUE, array('old_viaid'));
        $table->add_key('u_newid', XMLDB_KEY_UNIQUE, array('new_viaid'));
        
        // Conditionally launch create table for block_course_migration_via
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        
        // progress savepoint reached
        upgrade_block_savepoint(true, 2019021100, 'course_migration');
    }
    
    if ($oldversion < 2019022000)
    {
        
        // Define table block_course_migration_via2 to be created
        $table = new xmldb_table('block_course_migration_via2');
        
        // Adding fields to table block_course_migration_via2
        $table->add_field('id', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('old_viaassignid', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null);
        $table->add_field('new_viaassignid', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null);
        
        
        // Adding keys to table block_course_migration_via2
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('u_oldid', XMLDB_KEY_UNIQUE, array('old_viaassignid'));
        $table->add_key('u_newid', XMLDB_KEY_UNIQUE, array('new_viaassignid'));
        
        // Conditionally launch create table for block_course_migration_via2
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        
        // progress savepoint reached
        upgrade_block_savepoint(true, 2019022000, 'course_migration');
    }
    
    if ($oldversion < 2019022100) {
        
        // Changing nullability of field stdcourseid on table block_course_migration to not null.
        $table = new xmldb_table('block_course_migration');
        
        $field_validationlogs = new xmldb_field('validationlogs', XMLDB_TYPE_TEXT, '', null, null, null, null, 'keepdata');
        if (!$dbman->field_exists($table,$field_validationlogs)) {
            $dbman->add_field($table, $field_validationlogs);
        }
        
        $field_validated = new xmldb_field('validated', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, 0, 'validationlogs');
        if (!$dbman->field_exists($table,$field_validated)) {
            $dbman->add_field($table, $field_validated);
        }
        
        // Summary savepoint reached.
        upgrade_block_savepoint(true, 2019022100, 'course_migration');
    }
    

    return $result;
}
