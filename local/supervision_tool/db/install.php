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

require_once($CFG->dirroot.'/local/magisterelib/MessageOutput.php');

defined('MOODLE_INTERNAL') || die();

function xmldb_local_supervision_tool_install() {
    $output = new MessageOutput(MessageOutput::WARNING);

    $output->start_display();

    $sql_purge_cfg_table = 'TRUNCATE {local_supervision_filter_cfg}';
    $sql_purge_com_table = 'TRUNCATE {local_supervision_tool_comm}';

    $sql_copy_cfg = "INSERT IGNORE INTO {local_supervision_filter_cfg} (`id`, `userid`, `publicationtype`, `publicationdate`, `startdate`, `enddate`, `migrationdate`, `pagecount`, `depth`, `formateurcount`, `participantcount`, `lastaccess`, `comment`, `migrationstatus`) SELECT `id`, `userid`, `publicationtype`, `publicationdate`, `startdate`, `enddate`, `migrationdate`, `pagecount`, `depth`, `formateurcount`, `participantcount`, `lastaccess`, `comment`, `migrationstatus` FROM {block_supervision_filter_cfg}";
    $sql_copy_com = "INSERT IGNORE INTO {local_supervision_tool_comm} (`id`, `courseid`, `comment`) SELECT `id`, `courseid`, `comment` FROM {block_supervision_tool_comm}";

    execute_with_echo('Purge Filter CFG', $sql_purge_cfg_table, $output);

    execute_with_echo('Purge Comment', $sql_purge_com_table, $output);

    execute_with_echo('Copy Filter CFG', $sql_copy_cfg, $output);

    execute_with_echo('Copy Filter CFG', $sql_copy_com, $output);

    $output->end_display();

    $output->start_display();

    migrate_capabilities($output);

    $output->end_display();
}

function migrate_capabilities($output)
{
    global $DB;

    $capabilitiesmapping = [
        'block/supervision_tool:view' => 'local/supervision_tool:view',
        'block/supervision_tool:viewowncourses' => 'local/supervision_tool:viewowncourses',
        'block/supervision_tool:viewallcourses'  => 'local/supervision_tool:viewallcourses',
        'block/supervision_tool:migratemodular' => 'local/supervision_tool:migratemodular',
        'block/supervision_tool:migratetopics' => 'local/supervision_tool:migratetopics',
        'block/supervision_tool:archivecourse' => 'local/supervision_tool:archivecourse',
        'block/supervision_tool:movetotrash' => 'local/supervision_tool:movetotrash'
    ];

    // purge any new capabilities (if any)
    foreach($capabilitiesmapping as $oldcapa => $newcapa){
        $DB->delete_records('role_capabilities', ['capability' => $newcapa]);
        $output->display('PURGE CAPABILITY "'.$newcapa.'"');
    }

    $now = time();
    $idxbulk = 0;
    $idxtotalcount = 0;
    $maxbulk = 2000;
    $capabulk = [];

    foreach($capabilitiesmapping as $oldcapa => $newcapa){
        $capatomigrate = $DB->get_recordset('role_capabilities', ['capability' => $oldcapa]);

        $output->display('START MIGRATE "'.$oldcapa. '" TO "'.$newcapa.'"');

        foreach($capatomigrate as $capa){
            unset($capa->id);
            $capa->timemodified = $now;
            $capa->capability = $newcapa;

            $capabulk[] = $capa;
            $idxbulk++;

            if($idxbulk > $maxbulk){
                $DB->insert_records('role_capabilities', $capabulk);
                $output->display('INSERT '.$idxbulk. ' NEW CAPABILITIES');
                $idxtotalcount += $idxbulk;
                $idxbulk = 0;
            }
        }

        if($idxbulk > 0){
            $DB->insert_records('role_capabilities', $capabulk);
            $output->display('INSERT '.$idxbulk. ' NEW CAPABILITIES');
            $idxtotalcount += $idxbulk;
        }

        $idxbulk = 0;
        $capabulk = [];

        $output->display('END MIGRATE "'.$oldcapa. '" TO "'.$newcapa."\" ($idxtotalcount PROCESSED)");
    }
}

function execute_with_echo($text, $sql, $output)
{
    global $DB;
    $output->display($text, false);
    $DB->execute($sql);
    $output->display(' DONE');
}
