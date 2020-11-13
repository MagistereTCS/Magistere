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

function xmldb_local_gaia_install() {

  global $DB;

  $sql_purge_tables1 = 'TRUNCATE {local_gaia_formations}';
  $sql_purge_tables2 = 'TRUNCATE {local_gaia_intervenants}';
  $sql_purge_tables3 = 'TRUNCATE {local_gaia_stagiaires}';
  $sql_purge_tables4 = 'TRUNCATE {local_gaia_session_course}';
  $sql_purge_tables5 = 'TRUNCATE {local_gaia_session_via}';
  
  $sql_copy_formations = "INSERT IGNORE INTO {local_gaia_formations} (`id`, `table_name`, `dispositif_id`, `dispositif_name`, `module_id`, `module_name`, `session_id`, `group_number`, `startdate`, `enddate`, `place_type`, `formation_place`) SELECT `id`, `table_name`, `dispositif_id`, `dispositif_name`, `module_id`, `module_name`, `session_id`, `group_number`, `startdate`, `enddate`, `place_type`, `formation_place` FROM {gaia_formations}";
  $sql_copy_intervenants = "INSERT IGNORE INTO {local_gaia_intervenants} (`id`, `table_name`, `module_id`, `name`, `firstname`, `email`) SELECT `id`, `table_name`, `module_id`, `name`, `firstname`, `email` FROM {gaia_intervenants}";
  $sql_copy_stagiaires = "INSERT IGNORE INTO {local_gaia_stagiaires} (`id`, `table_name`, `session_id`, `name`, `firstname`, `email`) SELECT `id`, `table_name`, `session_id`, `name`, `firstname`, `email` FROM {gaia_stagiaires}";
  
  $sql_copy_course = "INSERT IGNORE INTO {local_gaia_session_course} (`id`, `course_id`, `session_id`, `dispositif_id`, `module_id`) SELECT `id`, `session_id`, `session_gaia_id`, `dispositif_id`, `module_id` FROM {parcours_sessiongaia}";
  $sql_copy_via = "INSERT IGNORE INTO {local_gaia_session_via} (`id`, `via_id`, `session_id`, `dispositif_id`, `module_id`) SELECT `id`, `activity_id`, `session_gaia_id`, `dispositif_id`, `module_id` FROM {via_sessiongaia}";
  
  echo 'Purge Formation ';
  $DB->execute($sql_purge_tables1);
  echo "DONE<br/>\n";
  echo 'Purge Intervenants ';
  $DB->execute($sql_purge_tables2);
  echo "DONE<br/>\n";
  echo 'Purge Stagiaires ';
  $DB->execute($sql_purge_tables3);
  echo "DONE<br/>\n";
  echo 'Purge session course ';
  $DB->execute($sql_purge_tables4);
  echo "DONE<br/>\n";
  echo 'Purge session via ';
  $DB->execute($sql_purge_tables5);
  echo "DONE<br/>\n";
  
  
  echo 'Copy Formation ';
  $DB->execute($sql_copy_formations);
  echo "DONE<br/>\n";
  echo 'Copy Intervenants ';
  $DB->execute($sql_copy_intervenants);
  echo "DONE<br/>\n";
  
  
  echo 'Copy session course ';
  $DB->execute($sql_copy_course);
  echo "DONE<br/>\n";
  echo 'Copy session via ';
  $DB->execute($sql_copy_via);
  echo "DONE<br/>\n";
  
  echo 'Copy Stagiaires ';
  $DB->execute($sql_copy_stagiaires);
  echo "DONE<br/>\n";
}
