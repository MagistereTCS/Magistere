<?php

function xmldb_local_myindex_upgrade($oldversion) {
    global $DB;
 
 	$dbman = $DB->get_manager();

  if ($oldversion < 2020033100) {
        // Define table local_myindex_course_progress to be created
        $table = new xmldb_table('local_myindex_courseprogress');
        // Adding fields to local_myindex_course_progress
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null);
        $table->add_field('progress', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('courseid_userid', XMLDB_KEY_UNIQUE, array('courseid', 'userid'));
        // Conditionally launch create table
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // courseprogress savepoint reached
        upgrade_plugin_savepoint(true, 2020033100, 'local',  'myindex');
    }

    return true;
}
