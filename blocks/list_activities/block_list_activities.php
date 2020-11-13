<?php

require_once($CFG->dirroot.'/blocks/list_activities/lib.php');

class block_list_activities extends block_base {

    public function init() {
        $this->title = get_string('simplehtml', 'block_list_activities');

    }

    public function get_content() {
        global $DB, $COURSE,$OUTPUT,$CFG;


        if ($this->content !== null) {
            return $this->content;
        }
        $this->content = new stdClass;
        $this->content->text="";

        $course = $this->page->course;

        $block_list_activities_element = $DB->get_record('block_list_activities',array('courseid'=>$course->id));
        $modinfo = get_fast_modinfo($course);
        if($block_list_activities_element != null){
            $weight = unserialize_array(base64_decode($block_list_activities_element->weight));
            $notdisplay = unserialize_array(base64_decode($block_list_activities_element->notdisplay));

            $weightToRemove = [];
            foreach ($weight as $id) {
                $activity = getActivity($id,$modinfo->cms);
                if($activity){
                    if(!in_array($id,$notdisplay) && $activity->visible && $activity->available){
                        $this->content->text .= createLabelAndIcon($activity);           }
                }else{
                    $weightToRemove[] = $id;
                }

            }
            foreach($modinfo->cms as $cm) {
                if ($cm->deletioninprogress || !$cm->visible || isRessource($cm)) {
                    continue;
                }
                if(!in_array($cm->id,$weight)){
                    //$this->content->text .= createLabelAndIcon($cm);
                    $weight[] = $cm->id;
                    $notdisplay[] =  $cm->id;
                }
            }
            $weight = array_diff($weight, $weightToRemove);
            updateActivityToList($block_list_activities_element,$weight,$notdisplay);

        }else{

            $weight = [];
            foreach($modinfo->cms as $cm) {
                if ($cm->deletioninprogress || !$cm->visible || isRessource($cm)) {
                    continue;
                }
                $weight[] = $cm->id;
            }
            createActivityToList($course->id,$weight,$weight);
        }


        if (has_capability('block/list_activities:managepages', $this->context))
        {
            $a = html_writer::tag('a', '&Eacute;dition',array('href'=>new moodle_url('/blocks/list_activities/edit.php',array('id'=>$COURSE->id))));
            $li = html_writer::tag('li', $a);
            $this->content->text .= html_writer::tag('ul', $li,array('class'=>'editbutton'));
        }




        return $this->content;
    }
}