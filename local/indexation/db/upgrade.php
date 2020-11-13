<?php

/**
 * Fonction qui traite les mises à jour de la structure de base de données pour le plugin.
 *
 * @param $oldversion
 * @return bool
 * @throws ddl_exception
 * @throws ddl_table_missing_exception
 * @throws downgrade_exception
 * @throws upgrade_exception
 */
function xmldb_local_indexation_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if($oldversion < 2019082600) {
        // Define field dispositif_id to be added to parcours_sessiongaia.
        $table = new xmldb_table('local_indexation');
        $field = new xmldb_field('achievementmark', XMLDB_TYPE_NUMBER, '1', null, XMLDB_NOTNULL, null, 0, 'enddate');

        // Conditionally launch add field dispositif_id.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2019082600, 'local', 'indexation');
    }

    if($oldversion < 2019112104) {
        // Define table local_indexation_notes to be created
        $table = new xmldb_table('local_indexation_notes');

        // Adding fields to table progress_activities
        $table->add_field('id', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('indexationid', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null);
        $table->add_field('version', XMLDB_TYPE_CHAR, 5, null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null);
        $table->add_field('note', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);

        // Adding keys to table progress_activities
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('indexationid', XMLDB_KEY_FOREIGN, array('indexationid'), 'local_indexation', array('id'));

        // Conditionally launch create table for progress_activities
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2019112104, 'local', 'indexation');
    }

    return true;
}