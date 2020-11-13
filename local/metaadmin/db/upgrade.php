<?php

defined('MOODLE_INTERNAL') || die();

function xmldb_local_metaadmin_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2019030701) {

        // Define table block_course_migration to be created
        $table = new xmldb_table('user_dayaccess');
        
        // Adding fields to table progress_activities
        $table->add_field('id', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timeaccess', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null);
        
        
        // Adding keys to table block_course_migration
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('mdl_userdayaccess_u', XMLDB_KEY_UNIQUE, array('userid', 'courseid','timeaccess'));
        $table->add_key('mdl_userdayaccess_user', XMLDB_INDEX_NOTUNIQUE, array('userid'));
        $table->add_key('mdl_userdayaccess_course', XMLDB_INDEX_NOTUNIQUE, array('courseid'));
        
        // Conditionally launch create table for block_course_migration
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // savepoint reached.
        upgrade_plugin_savepoint(true, 2019030701, 'local', 'metaadmin');
    }
    return true;
}