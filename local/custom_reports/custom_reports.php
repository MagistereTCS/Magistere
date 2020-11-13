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



require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->dirroot."/local/custom_reports/custom_reports_form.php");
require_once($CFG->dirroot."/blocks/configurable_reports/locallib.php");

require_login();

$context = context_system::instance();
if(! has_capability('block/configurable_reports:managereports', $context) && ! has_capability('block/configurable_reports:manageownreports', $context))
		print_error('badpermissions','block_configurable_reports');


$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_context(context_system::instance());

$title = get_string('query_stats_2020', 'local_custom_reports');

$PAGE->set_url('/local/custom_reports/custom_reports.php', null);


$PAGE->set_title($title);
$PAGE->set_heading($title);

$PAGE->requires->js_call_amd('local_custom_reports/custom_reports', 'init');

$action = optional_param("action", null, PARAM_ALPHANUMEXT);
$dialog = '';
if ($action == "validate") {

	$categoryId = required_param("categoryid", PARAM_INT);
	$fromDate = required_param_array("fromdate", PARAM_INT);
	$toDate = required_param_array("todate", PARAM_INT);
	$exportType = required_param("exporttype", PARAM_INT);

	// schedule task
	
	$task = new \local_custom_reports\task\adhoc_custom_reports();
	$task->set_custom_data(array(
		'category_id' => $categoryId,
		'from_date' => $fromDate,
		'to_date' => $toDate,
		'export_type' => $exportType,
		'user_id' => $USER->id,
        'timecreated' => time(),
	));

	$sheduleTime = strtotime("tomorrow 3 hours");
	$task->set_next_run_time($sheduleTime);
	\core\task\manager::queue_adhoc_task($task, true);
} 


$queryStatsForm = new \custom_reports_form();
if ($queryStatsForm->is_cancelled()) {
	redirect(new moodle_url('/my/'));
}

echo $OUTPUT->header();

$queryStatsForm->display();

echo $OUTPUT->footer();

// modal
if ($action == "validate") {
	echo 
	'
	<div id="dialog-querystats" title="Requête programmée" style="display:none">
	<p>
	La requête demandée a été programmée. Elle s’effectuera la nuit prochaine. 
	Vous recevrez une notification sur votre messagerie dès qu’elle sera terminée.
	</p>
	</div> 
	';
} 
