<?php

function xmldb_local_supervision_upgrade($oldversion) {
    global $CFG, $DB;
 
 	$dbman = $DB->get_manager();
 
    $result = TRUE;
 echo $oldversion;
  if ($oldversion < 2013100801) {
        // Define table progress_complete to be created
        $table = new xmldb_table('progress_complete');
        // Adding fields to table progress_complete
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null);
        $table->add_field('is_complete', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        // Conditionally launch create table for indexation_moodle
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

		

        // course_management savepoint reached
        upgrade_plugin_savepoint(true, 2013100801, 'local',  'supervision');
    }

	
	
    return $result;
}

?>