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
 * Completion Progress block overview page
 *
 * @package    block_completion_progress
 * @copyright  2018 Michael de Raadt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

ini_set("mysql.trace_mode", "0");

// Global variables needed
global $DB, $PAGE, $OUTPUT, $CFG;

// Include required files.
require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot.'/blocks/completion_progress/lib.php');
require_once($CFG->libdir.'/tablelib.php');
require_once($CFG->dirroot . '/local/gaia/lib/GaiaUtils.php');

$ajax_url = $CFG->wwwroot . '/blocks/completion_progress/ajax.php';
$ajax_redirect = $CFG->wwwroot . '/blocks/completion_progress/ajax_redirect.php';

const USER_SMALL_CLASS = 20;   // Below this is considered small.
const USER_LARGE_CLASS = 200;  // Above this is considered large.
const DEFAULT_PAGE_SIZE = 20;
const SHOW_ALL_PAGE_SIZE = 5000;

// Gather form data.
$id       = required_param('instanceid', PARAM_INT);
$courseid = required_param('courseid', PARAM_INT);
$page     = optional_param('page', 0, PARAM_INT); // Which page to show.
$perpage  = optional_param('perpage', DEFAULT_PAGE_SIZE, PARAM_INT); // How many per page.
$group    = optional_param('group', 0, PARAM_INT); // Group selected.
//$action = optional_param('action', 'none', PARAM_TEXT);
//
//if ($action == 'submit_termine') {
//    $fusers = required_param('users', PARAM_TEXT);
//    $courseid = required_param('courseid', PARAM_INT);
//    $id = required_param('instanceid', PARAM_INT);
//    $users = explode(',', $fusers);
//    set_progress_finished($courseid, $users);
//}

// Determine course and context.
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$context = CONTEXT_COURSE::instance($courseid);

// Find the role to display, defaulting to students.
$sql = "SELECT DISTINCT r.id, r.name, r.archetype
          FROM {role} r, {role_assignments} a
         WHERE a.contextid = :contextid
           AND r.id = a.roleid
           AND r.archetype = :archetype";
$params = array('contextid' => $context->id, 'archetype' => 'student');
$studentrole = $DB->get_record_sql($sql, $params);
if ($studentrole) {
    $studentroleid = $studentrole->id;
} else {
    $studentroleid = 0;
}
$roleselected = optional_param('role', $studentroleid, PARAM_INT);

// Get specific block config and context.
$block = $DB->get_record('block_instances', array('id' => $id), '*', MUST_EXIST);
$config = unserialize(base64_decode($block->configdata));
$blockcontext = CONTEXT_BLOCK::instance($id);

// Set up page parameters.
$PAGE->set_course($course);
$PAGE->requires->css('/blocks/completion_progress/styles.css');
$PAGE->set_url(
    '/blocks/completion_progress/overview.php',
    array(
        'instanceid' => $id,
        'courseid'   => $courseid,
        'page'       => $page,
        'perpage'    => $perpage,
        'group'      => $group,
        'sesskey'    => sesskey(),
        'role'       => $roleselected,
    )
);
$PAGE->set_context($context);
$title = get_string('overview', 'block_completion_progress');
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->navbar->add($title);
$PAGE->set_pagelayout('report');

$PAGE->requires->jquery();
$PAGE->requires->jquery_plugin('ui');
$PAGE->requires->jquery_plugin('ui-css');
$PAGE->requires->jquery_plugin('jtable-css');

// Check user is logged in and capable of accessing the Overview.
require_login($course, false);
require_capability('block/completion_progress:overview', $blockcontext);
confirm_sesskey();

// Start page output.
echo $OUTPUT->header();
echo $OUTPUT->heading($title, 2);
echo $OUTPUT->container_start('block_completion_progress');

echo html_writer::div('','',array('id' => 'generate_certificate', 'title' => 'Génération des attestations de présence', 'style' => 'display:none'));

// Check if activities/resources have been selected in config.
$activities = block_completion_progress_get_activities($courseid, $config);
if ($activities == null) {
    echo get_string('no_activities_message', 'block_completion_progress');
    echo $OUTPUT->container_end();
    echo $OUTPUT->footer();
    die();
}
if (empty($activities)) {
    echo get_string('no_visible_activities_message', 'block_completion_progress');
    echo $OUTPUT->container_end();
    echo $OUTPUT->footer();
    die();
}
$numactivities = count($activities);

$roleselected = optional_param('role', 0, PARAM_INT);
$rolewhere = $roleselected != 0 ? "AND a.roleid = $roleselected" : '';

// Output group selector if there are groups in the course
echo $OUTPUT->container_start('progressoverviewmenus');

// Output the groups menu
$groups = groups_get_all_groups($course->id);

if (!has_capability('moodle/site:accessallgroups', $context)) {
    $groupstodisplay = null;
    foreach ($groups as $group) {
        if(groups_is_member($group->id, $USER->id)){
            $groupstodisplay[$group->id] = $group->name;
        }
    }
} else {
    $groupstodisplay = array(0 => get_string('allgroups'));
    foreach ($groups as $group) {
        $groupstodisplay[$group->id] = $group->name;
    }
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
foreach($activities as $acti){
    $activitiestodisplay[$acti['type'].'*'.$acti['id']]= $acti['modulename'].' - '.$acti['name'];
}

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
';
if($groupstodisplay != null){
echo '
            <div id="fitem_id_usedynalink" class="fitem">
                <div class="fitemtitle"><label>'.get_string('group').'</label></div>
                <div class="felement fcheckbox">'.html_writer::select($groupstodisplay, 'filterform_group', $roleselected, false, array('id'=>'filterform_group','style'=>'width:300px')).'</div>
            </div>
';
}
echo '
            <div id="fitem_id_casesensitive" class="fitem">
                <div class="fitemtitle"><label>'.get_string('name').' / '.get_string('firstname').'</label></div>
                <div class="felement fcheckbox"><input type="text" name="filterform_name" id="filterform_name" value="" style="width:300px" onkeydown="if (event.keyCode == 13) return false"></div>
            </div>
            <div id="fitem_id_fullmatch" class="fitem">
                <div class="fitemtitle""><label>'.get_string('activity_status', 'block_progress').'</label></div>
                <div class="felement"">
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
                <div class="fitemtitle""><label>'.get_string('connected', 'block_progress').'</label></div>
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
    echo "<table>";

    foreach ($gaia_sessions as $gs) {
        $sid = $gs->id;
        $did = $gs->dispositif_id;
        $dname = $gs->dispositif_name;
        $sid = $gs->session_id;
        $mid = $gs->module_id;
        $mname = $gs->module_name;
        $sstartdate = date('d/m/Y H:i', $gs->startdate);
        $senddate = date('d/m/Y H:i', $gs->enddate);

        $url = new moodle_url('/blocks/gaia/session_description.php', array('sessiongaiaid' => $sid, 'dispositifid' => $did, 'moduleid' => $mid));

        echo '<tr>
                <td>
                    '.'<br />'.'<input type="checkbox" id="sgaia-'.$sid.'" name="sgaia['.$sid.'-'.$did.'-'.$mid.']" value="'.$sid.'-'.$did.'-'.$mid.'"/>
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
            <div class="form-buttons">
                <div class="btn_action" style="float:left">
                    <button type="button" class="form-submit" id="filterform_submit">'.get_string('filter', 'block_progress').'</button>
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
print_formation_finished_modal();

$form_finished_formation = '<FORM id="supervision_course_form" ACTION="' . $CFG->wwwroot . '/blocks/progress/overview.php?id='.$id.'&courseid='.$courseid.'" method="post" >';
$form_finished_formation .= '<input type="hidden" name="id" value="' . $id . '" />';
$form_finished_formation .= '<input type="hidden" name="courseid" value="' . $courseid . '" />';
$form_finished_formation .= '<input type="hidden" name="users" value="" /><input type="hidden" name="action" value="" /><input type="hidden" name="message_subject" value="" /><input type="hidden" name="message_content" value="" /></form>';
$form_finished_formation .= '<div>';
$form_finished_formation .= '<div class="" style="float:left">';
//$form_finished_formation .= '<button type="button" class="" id="refresh_cache"><img src="'.$OUTPUT->pix_url('general/icon_refresh', 'theme').'" alt="" id="refresh_cache" style="cursor:pointer; float:right; margin:0px 2px 10px 0px" /></button>';
$form_finished_formation .= '<p style="text-align:left;font-size:8pt; padding:4px 2px 4px 7px"><img src="'.$OUTPUT->image_url('general/icon_refresh', 'theme').'" alt="" id="refresh_cache" style="cursor:pointer; float:right; margin:0px 2px 10px 0px" /></p>';
$form_finished_formation .= '</div>';

$form_finished_formation .= '<div class="btn_action" style="margin-top:10px;float:right;">';
$form_finished_formation .= get_string('mass_action', 'block_progress').' <select id="select_submit"><option value="none">'.get_string('select_an_action', 'block_progress').'</option><option value="formation_finished">'.get_string('formation_finished', 'block_progress').'</option><option value="send_message">'.get_string('send_message', 'block_progress').'</option></select>';
$form_finished_formation .= '</div>';
$form_finished_formation .= '</div>';

echo '<div id="main_form_div">' . $OUTPUT->container_end() . $form_finished_formation . '</div>';

// Organise access to JS for progress bars.
$jsmodule = array('name' => 'block_completion_progress', 'fullpath' => '/blocks/completion_progress/module.js');
$arguments = array(array($block->id), array($USER->id));

$PAGE->requires->js_init_call('M.block_completion_progress.setupScrolling', array(), false, $jsmodule);
$PAGE->requires->js_init_call('M.block_completion_progress.init', $arguments, false, $jsmodule);

$PAGE->requires->js_call_amd('block_completion_progress/generateCertificate', 'init', array($id, $context->id, $courseid));

$jtheader_firstname = get_string('jtheader_firstname', 'block_completion_progress');;
$jtheader_lastname = get_string('jtheader_lastname', 'block_completion_progress');;
$jtheader_lastlogin = get_string('jtheader_lastlogin', 'block_completion_progress');;
$jtheader_suivi = get_string('jtheader_suivi', 'block_completion_progress');;
$jtheader_progression = get_string('jtheader_progression', 'block_completion_progress');;
$jtheader_finished = get_string('jtheader_finished', 'block_completion_progress');;
$jttitle = get_string('jttitle', 'block_completion_progress');;

$PAGE->requires->js_call_amd('block_completion_progress/resultSearchTable', 'init', array(
    $ajax_url, $ajax_redirect, $id, $context->id, $courseid, $jtheader_firstname, $jtheader_lastname, $jtheader_lastlogin,
    $jtheader_suivi, $jtheader_progression, $jtheader_finished, $jttitle
));

//echo $OUTPUT->container_end();
echo $OUTPUT->footer();

/**
 * Compares two table row elements for ordering.
 *
 * @param  mixed $a element containing name, online time and progress info
 * @param  mixed $b element containing name, online time and progress info
 * @return order of pair expressed as -1, 0, or 1
 */
function block_completion_progress_compare_rows($a, $b) {
    global $sort;

    // Process each of the one or two orders.
    $orders = explode(',', $sort);
    foreach ($orders as $order) {

        // Extract the order information.
        $orderelements = explode(' ', trim($order));
        $aspect = $orderelements[0];
        $ascdesc = $orderelements[1];

        // Compensate for presented vs actual.
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

        // Check of order can be established.
        // Check of order can be established.
        if (is_array($a)) {
            $first = $a[$aspect];
            $second = $b[$aspect];
        } else {
            $first = $a->$aspect;
            $second = $b->$aspect;
        }

        if ($first < $second) {
            return $ascdesc == 'ASC' ? 1 : -1;
        }
        if ($first > $second) {
            return $ascdesc == 'ASC' ? -1 : 1;
        }
    }

    // If previous ordering fails, consider values equal.
    return 0;
}

function print_message_modal(){
    echo html_writer::start_div('', array('id' => "messagesmod"));
    echo html_writer::tag('textarea', '', array('id' => 'messagemodtext'));
    echo html_writer::end_div();
}

function print_formation_finished_modal() {
    echo
'
<div id="dialog-formation-finished" title="Formation terminée" style="display:none">
<p>
Souhaitez-vous valider la formation comme terminée pour les participants ci-dessous ?
<br/>Note : Cette opération n\'est pas réversible.
<div id="selected-participants-list">
    <ul>
    </ul>
</div>
</p>
</div>
';
}

    
