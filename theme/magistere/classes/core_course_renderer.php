<?php

require_once($CFG->dirroot . '/course/renderer.php');

class theme_magistere_core_course_renderer extends core_course_renderer {

    function __construct(moodle_page $page, $target)
    {
        parent::__construct($page, $target);
    }

    public function course_section_cm_list_item($course, &$completioninfo, cm_info $mod, $sectionreturn, $displayoptions = array()) {

        if(!in_array($course->format, ['modular', 'topics','magistere_topics'])){
            return parent::course_section_cm_list_item($course, $completioninfo, $mod, $sectionreturn, $displayoptions);
        }

        $rendererName = 'theme_magistere_format_'.$course->format.'_renderer';
        $renderer = new $rendererName($this->page, $this->target);

        return $renderer->course_section_cm_list_item($course, $completioninfo, $mod, $sectionreturn, $displayoptions);
    }

}