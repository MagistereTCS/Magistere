<?php
/**
 * 
 *
 * @package    local
 * @subpackage taskmonitor
 * @author     TCS
 * @date       2020
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->dirroot.'/local/taskmonitor/TaskMonitor.php');

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/local/taskmonitor/');

require_login();

require_capability('local/taskmonitor:access', context_system::instance());

$PAGE->set_title(get_string('pluginname', 'local_taskmonitor'));
$PAGE->set_heading(get_string('pluginname', 'local_taskmonitor'));

$PAGE->requires->jquery();
$PAGE->requires->jquery_plugin('ui');
$PAGE->requires->jquery_plugin('ui-css');

$PAGE->requires->js_call_amd("local_taskmonitor/main", "init");

echo $OUTPUT->header();

echo '<div id="taskmonitor"><h2>'.get_string('pluginname', 'local_taskmonitor').'</h2>';

echo TaskMonitor::getHTML();

echo html_writer::end_div();

echo $OUTPUT->footer();
