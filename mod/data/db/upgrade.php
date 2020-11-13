<?php
// This file keeps track of upgrades to
// the data module
//
// Sometimes, changes between versions involve
// alterations to database structures and other
// major things that may break installations.
//
// The upgrade function in this file will attempt
// to perform all the necessary actions to upgrade
// your older installation to the current version.
//
// If there's something it cannot do itself, it
// will tell you what you need to do.
//
// The commands in here will all be database-neutral,
// using the methods of database_manager class
//
// Please do not forget to use upgrade_set_timeout()
// before any action that may take longer time to finish.

defined('MOODLE_INTERNAL') || die();

function xmldb_data_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2016090600) {

        // Define field config to be added to data.
        $table = new xmldb_table('data');
        $field = new xmldb_field('config', XMLDB_TYPE_TEXT, null, null, null, null, null, 'timemodified');

        // Conditionally launch add field config.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Data savepoint reached.
        upgrade_mod_savepoint(true, 2016090600, 'data');
    }

    // Automatically generated Moodle v3.2.0 release upgrade line.
    // Put any upgrade step following this.

    if ($oldversion < 2017032800) {

        // Define field completionentries to be added to data. Require a number of entries to be considered complete.
        $table = new xmldb_table('data');
        $field = new xmldb_field('completionentries', XMLDB_TYPE_INTEGER, '10', null, null, null, '0', 'config');

        // Conditionally launch add field timemodified.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Data savepoint reached.
        upgrade_mod_savepoint(true, 2017032800, 'data');
    }

    if ($oldversion < 2018032800) {

        // Define fields notifrecord and notiffeedback to be added to data.
        $table1 = new xmldb_table('data');
        $field1 = new xmldb_field('notifrecord', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'timemodified');
        $field2 = new xmldb_field('notiffeedback', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'notifrecord');

        // Conditionally launch add field notifrecord.
        if (!$dbman->field_exists($table1, $field1)) {
            $dbman->add_field($table1, $field1);
        }

        // Conditionally launch add field notiffeedback.
        if (!$dbman->field_exists($table1, $field2)) {
            $dbman->add_field($table1, $field2);
        }

        // Define table data_notif_roles to be created.
        $table2 = new xmldb_table('data_notif_roles');

        // Adding fields to table data_notif_roles.
        $table2->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table2->add_field('recordid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table2->add_field('roleid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

        // Adding keys to table data_notif_roles.
        $table2->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table2->add_key('recordid', XMLDB_KEY_FOREIGN, array('recordid'), 'data', array('id'));
        $table2->add_key('roleid', XMLDB_KEY_FOREIGN, array('roleid'), 'role', array('id'));

        // Conditionally launch create table for data_notif_roles.
        if (!$dbman->table_exists($table2)) {
            $dbman->create_table($table2);
        }

        // Data savepoint reached.
        upgrade_mod_savepoint(true, 2018032800, 'data');
    }

    if($oldversion < 2018040303){
        $table = new xmldb_table('data_notif_logs');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $table->add_field('recordid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('senddate', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('touser', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('subject', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('messagetext', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('messagehtml', XMLDB_TYPE_TEXT, null, null, null, null, null);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('userid', XMLDB_KEY_FOREIGN, array('touser'), 'user', array('id'));

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_mod_savepoint(true, 2018040303, 'data');
    }

    // Automatically generated Moodle v3.3.0 release upgrade line.
    // Put any upgrade step following this.

    // Automatically generated Moodle v3.4.0 release upgrade line.
    // Put any upgrade step following this.

    // Automatically generated Moodle v3.5.0 release upgrade line.
    // Put any upgrade step following this.

    if ($oldversion < 2018051401) {

        // Define field completionentries to be added to data. Require a number of entries to be considered complete.
        $table = new xmldb_table('data_notif_logs');
        $field = new xmldb_field('commentid', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');

        // Conditionally launch add field timemodified.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Data savepoint reached.
        upgrade_mod_savepoint(true, 2018051401, 'data');
    }

    return true;
}
