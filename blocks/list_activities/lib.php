<?php


function updateActivityToList($block_list_activities_element,$weight,$notdisplay){
    global $DB;

    $record = new \stdClass();
    $record->id = $block_list_activities_element->id;
    $record->notdisplay = base64_encode(serialize($notdisplay));
    $record->weight =base64_encode(serialize($weight));

    return $DB->update_record('block_list_activities',$record);
}


function createActivityToList($courseid,$weight,$notdisplay){
    global $DB;

    $record = new \stdClass();
    $record->weight = base64_encode(serialize($weight));
    $record->notdisplay = base64_encode(serialize($notdisplay));
    $record->courseid = $courseid;

    $record->courseid = $courseid;


    return $DB->insert_record('block_list_activities',$record);
}

function getActivity($id,$activities){
    $item = null;
    foreach($activities as $struct) {
        if ($id == $struct->id) {
            if (!$struct->deletioninprogress) {
                $item = $struct;
                break;
            }
        }
    }

    return $item;
}

function createLabelAndIcon($cm){
    global $OUTPUT,$CFG;

    $icon = $OUTPUT->pix_icon('activities/' . $cm->modname, get_string('pluginname', $cm->modname), 'theme');

    return  '<p><a href="'.$CFG->wwwroot.'/mod/'.$cm->modname.'/view.php?id='.$cm->id.'">'.$icon.$cm->name.'</a></p>';
}

function isRessource($cm){
    return  plugin_supports('mod', $cm->modname, FEATURE_MOD_ARCHETYPE, MOD_ARCHETYPE_OTHER);
}