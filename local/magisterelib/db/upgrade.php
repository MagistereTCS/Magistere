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
 * @package    local-magisterelib
 * @author     TCS 2017
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function xmldb_local_magisterelib_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager(); // Loads ddl manager and xmldb classes.

    if ($oldversion < 2017091400) {
    	
    	//if (substr($CFG->academie_name,0,3) == 'ac-')

    	$table = new xmldb_table('forum_discussions');
    	
    	// Adding fields to table indexation.
    	$field = new xmldb_field('dgescosync', XMLDB_TYPE_INTEGER, '11', null, null, null, null, 'pinned');
    	if (!$dbman->field_exists($table, $field)) {
    		$dbman->add_field($table, $field);
    	}

    	// magisterelibsavepoint reached.
        upgrade_plugin_savepoint(true, 2017091400, 'local', 'magisterelib');
    }

    return true;
}
