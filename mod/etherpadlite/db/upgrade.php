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
 * This file keeps track of upgrades to
 * the etherpadlite module
 *
 * @package    mod_etherpadlite
 *
 * @author     Timo Welde <tjwelde@gmail.com>
 * @copyright  2012 Humboldt-Universität zu Berlin <moodle-support@cms.hu-berlin.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// This file keeps track of upgrades to
// the etherpadlite module
//
// Sometimes, changes between versions involve
// alterations to database structures and other
// major things that may break installations.
//
// The upgrade function in this file will attempt
// to perform all the necessary actions to upgrade
// your older installtion to the current version.
//
// If there's something it cannot do itself, it
// will tell you what you need to do.
//
// The commands in here will all be database-neutral,
// using the functions defined in lib/ddllib.php.

defined('MOODLE_INTERNAL') || die();

function xmldb_etherpadlite_upgrade($oldversion=0) {

    global $CFG, $THEME, $DB;

    $dbman = $DB->get_manager(); // Loads ddl manager and xmldb classes.

    $result = true;

    if ($oldversion < 2013042901) {
        set_config("url", $CFG->etherpadlite_url, "etherpadlite");
        set_config("apikey", $CFG->etherpadlite_apikey, "etherpadlite");
        set_config("padname", $CFG->etherpadlite_padname, "etherpadlite");
        set_config("cookiedomain", $CFG->etherpadlite_cookiedomain, "etherpadlite");
        set_config("cookietime", $CFG->etherpadlite_cookietime, "etherpadlite");
        set_config("ssl", $CFG->etherpadlite_ssl, "etherpadlite");
        set_config("check_ssl", $CFG->etherpadlite_check_ssl, "etherpadlite");
        set_config("adminguests", $CFG->etherpadlite_adminguests, "etherpadlite");

        $DB->delete_records_select("config", "name LIKE 'etherpadlite_%'");

        upgrade_mod_savepoint(true, 2013042901, "etherpadlite");
	}
	
	if ($oldversion < 2016093001) {
	
		// Define table etherpadlite_groups to be created.
		$table = new xmldb_table('etherpadlite_groups');
	
		// Adding fields to table etherpadlite_groups.
		$table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
		$table->add_field('etherpadid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
		$table->add_field('groupid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
		$table->add_field('uri', XMLDB_TYPE_CHAR, '200', null, XMLDB_NOTNULL, null, null);
		$table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
		$table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
	
		// Adding keys to table etherpadlite_groups.
		$table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
	
		// Adding indexes to table etherpadlite_groups.
		$table->add_index('etherpadid', XMLDB_INDEX_NOTUNIQUE, array('etherpadid'));
	
		// Conditionally launch create table for etherpadlite_groups.
		if (!$dbman->table_exists($table)) {
			$dbman->create_table($table);
		}
	
		// Etherpadlite savepoint reached.
		upgrade_mod_savepoint(true, 2016093001, 'etherpadlite');
	}
	
	if ($oldversion < 2018031201)
	{
		set_config("urlapi", get_config("etherpadlite","url"), "etherpadlite");

		upgrade_mod_savepoint(true, 2018031201, "etherpadlite");
	}

    if ($oldversion < 2019110801) {

        // Define table etherpadlite_groups to be created.
        $table = new xmldb_table('etherpadlite');
        $field = new xmldb_field('participationisrequired', XMLDB_TYPE_INTEGER, '10', null,
            XMLDB_NOTNULL, null, 0);

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Etherpadlite savepoint reached.
        upgrade_mod_savepoint(true, 2019110801, 'etherpadlite');
    }

    return $result;
}
