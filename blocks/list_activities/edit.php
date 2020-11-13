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



//ini_set("mysql.trace_mode", "0");


// Global variables needed
global $CFG, $DB, $PAGE, $OUTPUT;

// Include required files
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->dirroot.'/blocks/list_activities/lib.php');




$PAGE->requires->js_call_amd('block_list_activities/edit', 'init');

// Gather form data
$courseid= required_param('id', PARAM_INT);

$context = context_course::instance($courseid);

// Determine course and context
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);

// Set up page parameters
$PAGE->set_course($course);
$PAGE->set_url('/blocks/list_activities/edit.php', array('id' => $courseid));
$PAGE->set_context($context);
$title = get_string('edit', 'block_list_activities');
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->navbar->add($title);
$PAGE->set_pagelayout('admin');
$PAGE->requires->css(new moodle_url('/blocks/list_activities/edit.css'));

$submit = optional_param('submit', false, PARAM_ALPHA);
$treedata = optional_param('treedata', false, PARAM_RAW);


if ($submit != false && $treedata != false && ($data = json_decode($treedata)) != false)
{
    process_update_order($courseid, $data);
    rebuild_course_cache($courseid);
}


echo $OUTPUT->header();
echo $OUTPUT->heading($title, 2);
echo $OUTPUT->container_start('block_list_activities');


$block_list_activities_element = $DB->get_record('block_list_activities',array('courseid'=>$course->id));

$modinfo = get_fast_modinfo($course);
if($block_list_activities_element != null){



    $weight = unserialize_array(base64_decode($block_list_activities_element->weight));
    $notdisplay = unserialize_array(base64_decode($block_list_activities_element->notdisplay));

    echo "<div id=\"dd\" class=\"dd\">";
    echo "<ol class=\"dd-list\">";

    foreach ($weight as $id) {
        $activity = getActivity($id,$modinfo->cms);
        if($activity){
            $unactive = "";
            $iconExtend ="";
            if (in_array($id,$notdisplay)) {
                $iconExtend = "-slash ishidden";
            }

            //uservisible availableinfo available section
            if (!$activity->visible ||  !$activity->available) {
                $unactive = " - Non visible par les participants";
            }

            $icon = $OUTPUT->pix_icon('activities/' . $activity->modname, get_string('pluginname', $activity->modname), 'theme');

            echo '<li class="dd-item" data-id='.$activity->id.'>';
            echo '<div class="dd-itemdiv">';
            echo '<div class="dd-div">';
            echo '<button class="move fa fa-arrows-alt-v tooltipstered"></button>';
            echo '</div>';
            echo '<span class="dd-buttonsblockspacer"></span>';
            echo '<div class="dd-handle">'.$icon. $activity->name.$unactive.'</div>';
            echo '<div class="dd-buttonsblock">';
            echo '<span class="dd-buttonsblockspacer"></span>';
            echo '<button class="hide  fa fa-eye'. $iconExtend.' tooltipstered"></button>';
            echo '</div>';
            echo '</div>';
            echo '</li>';
        }


    }

    echo '</ol>';
    echo '</div>';
    echo'
    <form action="" method="POST">
    <input type="hidden" id="treedata" name="treedata" />
    <input type="submit" id="submit" name="submit" value="Enregistrer les modifications" />
    <a href="'.$CFG->wwwroot.'/course/view.php?id='.$courseid.'"><input type="button" id="return" value="Retourner au parcours"/></a>
    </form>';

}else{
    echo "course not found";
}

echo '<div id="main_form_div">' . $OUTPUT->container_end() . '</div>';

echo $OUTPUT->footer();

function process_update_order($courseid, $data){
    global $DB,$CFG;
    $block_list_activities_element = $DB->get_record('block_list_activities',array('courseid'=>$courseid));

    if($block_list_activities_element != null){
        if(count($block_list_activities_element)==0){
            createActivityToList($courseid,$data->weight_ids,$data->not_displayed_ids);
        }else{
            updateActivityToList($block_list_activities_element,$data->weight_ids,$data->not_displayed_ids);
        }
    }

    redirect($CFG->wwwroot.'/course/view.php?id='.$courseid);
}









