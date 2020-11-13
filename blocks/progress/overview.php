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
 * Progress Bar block overview page
 *
 * @package    contrib
 * @subpackage block_progress
 * @copyright  2010 Michael de Raadt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

ini_set("mysql.trace_mode", "0");

// Global variables needed
global $DB, $PAGE, $OUTPUT, $CFG;

// Include required files
require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/blocks/progress/lib.php');
require_once($CFG->libdir . '/tablelib.php');
require_once($CFG->dirroot . '/local/gaia/lib/GaiaUtils.php');

$action = optional_param('action', 'none', PARAM_TEXT);

if ($action == 'submit_termine') {// || $action == 'submit_message') {

    $fusers = required_param('users', PARAM_TEXT);
    $courseid = required_param('courseid', PARAM_INT);
    $id = required_param('id', PARAM_INT);
    
    $users = explode(',', $fusers);

    set_progress_finished($courseid, $users);
}

$PAGE->requires->jquery();
$PAGE->requires->jquery_plugin('ui');
$PAGE->requires->jquery_plugin('ui-css');
$PAGE->requires->jquery_plugin('jtable-css');

$ajax_url = $CFG->wwwroot . '/blocks/progress/ajax.php';
$ajax_redirect = $CFG->wwwroot . '/blocks/progress/ajax_redirect.php';


// Gather form data
$id = required_param('id', PARAM_INT);

$courseid = required_param('courseid', PARAM_INT);

// Determine course and context
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$context = context_course::instance($courseid);

// Set up page parameters
$PAGE->set_course($course);
$PAGE->requires->css('/blocks/progress/styles.css');
$PAGE->set_url('/blocks/progress/overview.php', array('id' => $id, 'courseid' => $courseid));
$PAGE->set_context($context);
$title = get_string('overview', 'block_progress');
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->navbar->add($title);
$PAGE->set_pagelayout('block_progress_overview');

// Check user is logged in and capable of grading
require_login($course, false);
require_capability('block/progress:overview', $context);

// Get specific block config
$block = $DB->get_record('block_instances', array('id' => $id));
$config = unserialize(base64_decode($block->configdata));

// Start page output
echo $OUTPUT->header();
echo $OUTPUT->heading($title, 2);
echo $OUTPUT->container_start('block_progress');
$img_calender = $OUTPUT->image_url('general/calendar','theme')->out();
//
echo html_writer::div('','',array('id' => 'generate_certificate', 'title' => 'Génération des attestations de présence', 'style' => 'display:none'));

// Get the modules to check progress on
$modules = modules_in_use();
if (empty($modules)) {
    echo get_string('no_events_config_message', 'block_progress');
    echo $OUTPUT->footer();
    die();
}

// Check if activities/resources have been selected in config
$events = event_information($config, $modules);
if ($events == null) {
    echo get_string('no_events_message', 'block_progress');
    echo $OUTPUT->footer();
    die();
}
if (empty($events)) {
    echo get_string('no_visible_events_message', 'block_progress');
    echo $OUTPUT->footer();
    die();
}
$numevents = count($events);

$roleselected = optional_param('role', 0, PARAM_INT);
$rolewhere = $roleselected != 0 ? "AND a.roleid = $roleselected" : '';

// Output group selector if there are groups in the course
echo $OUTPUT->container_start('progressoverviewmenus');
$groupuserid = 0;
if (!has_capability('moodle/site:accessallgroups', $context)) {
    $groupuserid = $USER->id;
}

// Output the groups menu
$groups = groups_get_all_groups($course->id);
$groupstodisplay = array(0 => get_string('allgroups'));
foreach ($groups as $group) {
    $groupstodisplay[$group->id] = $group->name;
}

// Output the roles menu
$sql = "SELECT DISTINCT r.id, r.name, r.shortname
          FROM {role} r, {role_assignments} a
         WHERE a.contextid = :contextid
           AND r.id = a.roleid";
$params = array('contextid' => $context->id);
$roles = role_fix_names($DB->get_records_sql($sql, $params), $context);
$rolestodisplay = array(0 => get_string('allparticipants'));
foreach ($roles as $role) {
    $rolestodisplay[$role->id] = $role->localname;
}


// Output the activity menu
$config_array = (array) $config;
$actis = array_keys($config_array);

$activitiestodisplay = array();
foreach($actis as $acti)
{
    $found = preg_match("/(monitor)_([a-zA-Z_]+?)([0-9]{1,9})/i",$acti,$match);
    if ($found)
    {
    	if ($config_array[$acti] == 1)
    	{
	    	$activit = $match[2];
	    	$instanceid = $match[3];
	    	try{
	    	$record = $DB->get_record_sql("SELECT name FROM {".$activit."} WHERE id=".$instanceid);
	    	if ($record !== false)
	    	{
	    		$activitiestodisplay[$activit.'*'.$instanceid]= get_string($activit, 'block_progress').' - '.$record->name;
	    	}
	    	}catch(Exception $e){
	    		
	    	}
    	}
    }
}

asort($activitiestodisplay);

$activitiestodisplay = array('none' => get_string('select_an_activity', 'block_progress')) + $activitiestodisplay;


echo '
<form action="'.$PAGE->url.'" method="post" class="mform" id="filterform_form">
    <fieldset class="clearfix"  id="linkinghdr">
        <legend class="ftoggler">Filtre</legend>
        <div class="advancedbutton"></div>
        <div class="fcontainer clearfix">
            <div id="fitem_id_casesensitive" class="fitem">
                <div class="fitemtitle"><label>'.get_string('role').'</label></div>
                <div class="felement fcheckbox">'.html_writer::select($rolestodisplay, 'filterform_role', $roleselected, false, array('id'=>'filterform_role','style'=>'width:300px')).'</div>
            </div>
            <div id="fitem_id_usedynalink" class="fitem">
                <div class="fitemtitle"><label>'.get_string('group').'</label></div>
                <div class="felement fcheckbox">'.html_writer::select($groupstodisplay, 'filterform_group', $roleselected, false, array('id'=>'filterform_group','style'=>'width:300px')).'</div>
            </div>
            <div id="fitem_id_casesensitive" class="fitem">
                <div class="fitemtitle"><label>'.get_string('name').' / '.get_string('firstname').'</label></div>
                <div class="felement fcheckbox"><input type="text" name="filterform_name" id="filterform_name" value="" style="width:300px" onkeydown="if (event.keyCode == 13) return false"></div>
            </div>
            <div id="fitem_id_fullmatch" class="fitem">
                <div class="fitemtitle" style="float:left"><label>'.get_string('activity_status', 'block_progress').'</label></div>
                <div class="felement">
                    <span class="radiogroup">
                      <input id="filterform_realized" type="radio" name="filterform_realized" checked="checked" value="1"/>
                      <label for="filterform_realized">'.get_string('realized', 'block_progress').'</label>
                    </span>
                    <span class="radiogroup">
                      <input id="filterform_notrealized" type="radio" name="filterform_realized" value="0"/>
                      <label for="filterform_notrealized">'.get_string('notrealized', 'block_progress').'</label>
                    </span><br/>
                    '.html_writer::select($activitiestodisplay, 'filterform_activity', $roleselected, false, array('id'=>'filterform_activity','style'=>'width:300px')).'
                </div>
            </div>
            <div id="fitem_id_fullmatch" class="fitem">
                <div class="fitemtitle" style="float:left"><label>'.get_string('connected', 'block_progress').'</label></div>
                <div class="felement">
                    <span class="radiogroup">
                      <input id="filterform_neverconnected" type="checkbox" name="filterform_neverconnected" value="e"/>
                      <label for="filterform_neverconnected">'.get_string('neverconnected', 'block_progress').'</label>
                    </span>
                </div>
            </div>';

// Recuperation des session gaia liee
$gaia_sessions = GaiaUtils::get_sessions($course->id);

if(count($gaia_sessions) > 0) {
    echo '<div id="fitem_id_fullmatch" class="fitem">
    <div class="fitemtitle" style="float:left"><label>'.get_string('gaia_session', 'block_progress').$OUTPUT->help_icon('gaia_session', 'block_progress', '').'</label></div>
        <div class="felement">';

    //print_r($gaia_sessions);
    //echo '<table style="margin-left:153px;">';
    echo '<table>';

    foreach ($gaia_sessions as $gs) {
        $sid = $gs->id;
        $did = $gs->dispositif_id;
        $dname = $gs->dispositif_name;
        $sid = $gs->session_id;
        $sname = $gs->session_name;
        $mid = $gs->module_id;
        $mname = $gs->module_name;
        $sstartdate = date('d/m/Y H:i', $gs->startdate);
        $senddate = date('d/m/Y H:i', $gs->enddate);

        $url = new moodle_url('/blocks/gaia/session_description.php', array('sessiongaiaid' => $sid, 'dispositifid' => $did, 'moduleid' => $mid));

        echo '<tr>
<td>
    <input type="checkbox" id="sgaia-'.$sid.'" name="sgaia['.$sid.'-'.$did.'-'.$mid.']" value="'.$sid.'-'.$did.'-'.$mid.'"/>
</td>
<td>
    <label for="sgaia-'.$sid.'">
        <span style="font-weight: bold;">'.$did.' : '.$dname.'</span><br/>
        Module '.$mid.' : '.$mname.'<br/>
        <a href="'.$url.'">Session du '.$sstartdate.' au '.$senddate.'</a>
    </label>
</td>
</tr>';
    }



    echo '<tr>
<td><input type="checkbox" id="sother" name="sother" value="other"/></td>
<td><label for="sother">'.get_string('other_participants', 'block_progress').'</label></td>
</tr>
</table>
</div>';
}

echo'
            <div id="fitem_id_fullmatch" class="fitem" style="text-align:left">
                <div class="btn_action" style="float:left">
                    <button type="button" class="" id="filterform_submit">'.get_string('filter', 'block_progress').'</button>
                </div>
            </div>
        </div>
    </fieldset>
</form>
<hr style="display:block">
<div style="width:100%;color:orange;height:50px;background-color:#f7ead7;padding: 10px 25px; display: none;" id="page-blocks-progress-warning">'.get_string('warning_user_notimeaccess', 'block_progress').'</div>
<div style="width:100%;text-align:right;box-sizing:none;" class="progress_overview_header">
<button type="button" class="btn_action2" id="generate_certificate_btn">Générer les attestations de présence</button>' . $OUTPUT->help_icon('certificate_participation', 'block_progress', '') . '
</div>';


echo $OUTPUT->container_end();

echo '<div id="ProgressBarOverviewTable" style="width:100%"></div>';
echo '<div style="width:100%">';
echo '<div style="float:left"><button type="button" class="btn_action2" id="clean_selected_users">Réinitialiser la sélection</button></div>';
echo '<div id="selected_users" style="float:right;margin-top: 10px;">Nombre de participants sélectionnés : <span>0</span></div>';
echo '<div style="clear:both"></div>';
echo '</div>';

print_message_modal();

$form_finished_formation = '<FORM id="supervision_course_form" ACTION="' . $CFG->wwwroot . '/blocks/progress/overview.php?id='.$id.'&courseid='.$courseid.'" method="post" >';
$form_finished_formation .= '<input type="hidden" name="id" value="' . $id . '" />';
$form_finished_formation .= '<input type="hidden" name="courseid" value="' . $courseid . '" />';
$form_finished_formation .= '<input type="hidden" name="users" value="" /><input type="hidden" name="action" value="" /><input type="hidden" name="message_subject" value="" /><input type="hidden" name="message_content" value="" /></form>';
$form_finished_formation .= '<div>';
$form_finished_formation .= '<div class="" style="float:left">';
//$form_finished_formation .= '<button type="button" class="" id="refresh_cache"><img src="'.$OUTPUT->image_url('general/icon_refresh', 'theme').'" alt="" id="refresh_cache" style="cursor:pointer; float:right; margin:0px 2px 10px 0px" /></button>';
$form_finished_formation .= '<p style="text-align:left;font-size:8pt; padding:4px 2px 4px 7px"><img src="'.$OUTPUT->image_url('general/icon_refresh', 'theme').'" alt="" id="refresh_cache" style="cursor:pointer; float:right; margin:0px 2px 10px 0px" /></p>';
$form_finished_formation .= '</div>';

$form_finished_formation .= '<div class="btn_action" style="margin-top:10px">';
$form_finished_formation .= get_string('mass_action', 'block_progress').' <select id="select_submit"><option value="none">'.get_string('select_an_action', 'block_progress').'</option><option value="formation_finished">'.get_string('formation_finished', 'block_progress').'</option><option value="send_message">'.get_string('send_message', 'block_progress').'</option></select>';
$form_finished_formation .= '</div>';
$form_finished_formation .= '</div>';

echo '<div id="main_form_div">' . $OUTPUT->container_end() . $form_finished_formation . '</div>';

// Organise access to JS
$jsmodule = array(
    'name'     => 'block_progress',
    'fullpath' => '/blocks/progress/module.js',
    'requires' => array(),
    'strings'  => array(
        array('time_expected', 'block_progress'),
    ),
);
$arguments = array($CFG->wwwroot, array_keys($modules));
$PAGE->requires->js_init_call('M.block_progress.init', $arguments, false, $jsmodule);

$PAGE->requires->js_call_amd('block_progress/generateCertificate', 'init', array($id, $context->id, $courseid, $img_calender));

$jtheader_firstname = get_string('jtheader_firstname', 'block_progress');;
$jtheader_lastname = get_string('jtheader_lastname', 'block_progress');;
$jtheader_lastlogin = get_string('jtheader_lastlogin', 'block_progress');;
$jtheader_suivi = get_string('jtheader_suivi', 'block_progress');;
$jtheader_progression = get_string('jtheader_progression', 'block_progress');;
$jtheader_finished = get_string('jtheader_finished', 'block_progress');;
$jttitle = get_string('jttitle', 'block_progress');

$PAGE->requires->js_call_amd('block_progress/resultSearchTable', 'init', array(
    $ajax_url, $ajax_redirect, $id, $context->id, $courseid, $jtheader_firstname, $jtheader_lastname, $jtheader_lastlogin,
    $jtheader_suivi, $jtheader_progression, $jtheader_finished, $jttitle
));



echo $OUTPUT->footer();

/**
 * Compares two table row elements for ordering
 *
 * @param  mixed $a element containing name, online time and progress info
 * @param  mixed $b element containing name, online time and progress info
 * @return order of pair expressed as -1, 0, or 1
 */
/*
function compare_rows($a, $b)
{
    global $sort;

    // Process each of the one or two orders
    $orders = explode(',', $sort);
    foreach ($orders as $order) {

        // Extract the order information
        $orderelements = explode(' ', trim($order));
        $aspect        = $orderelements[0];
        $ascdesc       = $orderelements[1];

        // Compensate for presented vs actual
        switch ($aspect) {
            case 'name':
                $aspect = 'lastname';
                break;
            case 'lastonline':
                $aspect = 'lastonlinetime';
                break;
            case 'progress':
                $aspect = 'progressvalue';
                break;
        }

        // Check of order can be established
        if ($a[$aspect] < $b[$aspect]) {
            return $ascdesc == 'ASC' ? 1 : -1;
        }
        if ($a[$aspect] > $b[$aspect]) {
            return $ascdesc == 'ASC' ? -1 : 1;
        }
    }

    // If previous ordering fails, consider values equal
    return 0;
}
*/

function send_email_to_users($post_users, $subject, $message, $from, $courseid)
{
    GLOBAL $DB, $CFG;
    $id = required_param('id', PARAM_INT);
    $moodle_users = $DB->get_records_list('user', 'id', $post_users);

    foreach ($moodle_users as $user_object) {
        $to      = $user_object->email;
        $headers = "From:" . $from;
        mail($to, $subject, $message, $headers);
    }
    //header('Location: ' . $CFG->wwwroot . '/course/view.php?id=' . $courseid);
    header('Location: ' . $CFG->wwwroot . '/blocks/progress/overview.php?id='.$id.'&courseid=' . $courseid);
    exit;
}


function set_progress_finished($courseid, $post_users)
{
    GLOBAL $DB, $CFG;
    
    $id = required_param('id', PARAM_INT);
    
    foreach ($post_users as $userid) {
        $record              = new stdClass();
        $record->courseid    = $courseid;
        $record->userid      = $userid;
        $record->is_complete = 1;
        $is_complete         = $DB->get_record('progress_complete', array('courseid' => $courseid, 'userid' => $userid));
        if (!$is_complete) {
            $DB->insert_record('progress_complete', $record, false);
        }
    }
    header('Location: ' . $CFG->wwwroot . '/blocks/progress/overview.php?id='.$id.'&courseid=' . $courseid);
    die;
}
function print_message_modal(){
    echo html_writer::start_div('', array('id' => "messagesmod"));
    echo html_writer::tag('textarea', '', array('id' => 'messagemodtext'));
    echo html_writer::end_div();
}
?>
