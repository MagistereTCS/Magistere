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

defined('MOODLE_INTERNAL') || die();

function xmldb_local_supervision_tool_upgrade($oldversion) {
    global $DB;

    $dbman  = $DB->get_manager();
    $result = true;
    
    if ($oldversion < 2020061100) {
        
        $table = new xmldb_table('local_supervision_filter_cfg');
        $field = new xmldb_field('trainer', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, 0);
        
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        
        // savepoint reached.
        upgrade_plugin_savepoint(true, 2020061100, 'local', 'supervision_tool');
    }

    return $result;
}
