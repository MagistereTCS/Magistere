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
 * CLI task execution.
 *
 * @package    tool_task
 * @copyright  2020 TCS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../../config.php');
require_once("$CFG->libdir/clilib.php");
require_once("$CFG->libdir/cronlib.php");

list($options, $unrecognized) = cli_get_params(
    array('help' => false, 'list' => false, 'execute' => false, 'showsql' => false, 'showdebugging' => false),
    array('h' => 'help')
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help'] || !$options['execute']) {
    $help =
"Adhoc cron tasks.

Options:
--execute=\\\\some\\\\task  Execute adhoc task manually
--showsql             Show sql queries before they are executed
--showdebugging       Show developer level debugging information
-h, --help            Print out this help

Example:
\$sudo -u www-data /usr/bin/php admin/tool/task/cli/adhoc_task.php --execute=\\\\core\\\\task\\\\session_cleanup_task

";

    echo $help;
    die;
}

if ($options['showdebugging']) {
    set_debugging(DEBUG_DEVELOPER, true);
}

if ($options['showsql']) {
    $DB->set_debug(true);
}

if ($execute = $options['execute']) {
    if (moodle_needs_upgrading()) {
        mtrace("Moodle upgrade pending, cannot execute tasks.");
        exit(1);
    }

    // Increase memory limit.
    raise_memory_limit(MEMORY_EXTRA);

    // Emulate normal session - we use admin account by default.
    cron_setup_user();

    
    \core\task\manager::static_caches_cleared_since(time());
    
    $tasks = \core\task\manager::get_adhoc_tasks($execute);
    
    foreach ($tasks AS $task){
            
        
        $predbqueries = $DB->perf_get_queries();
        $pretime = microtime(true);
    
        $fullname = '(' . get_class($task) . ')';
        mtrace('Execute adhoc task: ' . $fullname);
        // NOTE: it would be tricky to move this code to \core\task\manager class,
        //       because we want to do detailed error reporting.
        $cronlockfactory = \core\lock\lock_config::get_lock_factory('cron');
        if (!$cronlock = $cronlockfactory->get_lock('core_cron', 10)) {
            mtrace('Cannot obtain cron lock');
            exit(129);
        }
        if (!$lock = $cronlockfactory->get_lock('adhoc_' . $task->get_id(), 5)) {
            $cronlock->release();
            mtrace('Cannot obtain task lock');
            exit(130);
        }
    
        $task->set_lock($lock);
        if (!$task->is_blocking()) {
            $cronlock->release();
        } else {
            $task->set_cron_lock($cronlock);
        }
    
        try {
            get_mailer('buffer');
            
            cron_run_inner_adhoc_task($task);
            
            if (isset($predbqueries)) {
                mtrace("... used " . ($DB->perf_get_queries() - $predbqueries) . " dbqueries");
                mtrace("... used " . (microtime(1) - $pretime) . " seconds");
            }
            mtrace('Adhoc task complete: ' . $fullname);
            \core\task\manager::adhoc_task_complete($task);
            get_mailer('close');
        } catch (Exception $e) {
            if ($DB->is_transaction_started()) {
                $DB->force_transaction_rollback();
            }
            mtrace("... used " . ($DB->perf_get_queries() - $predbqueries) . " dbqueries");
            mtrace("... used " . (microtime(true) - $pretime) . " seconds");
            mtrace('Adhoc task failed: ' . $fullname . ',' . $e->getMessage());
            if ($CFG->debugdeveloper) {
                if (!empty($e->debuginfo)) {
                    mtrace("Debug info:");
                    mtrace($e->debuginfo);
                }
                mtrace("Backtrace:");
                mtrace(format_backtrace($e->getTrace(), true));
            }
            \core\task\manager::adhoc_task_failed($task);
            get_mailer('close');
        }
    }
}
