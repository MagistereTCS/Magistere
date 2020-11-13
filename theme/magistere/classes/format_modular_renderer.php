<?php

require_once($CFG->dirroot.'/course/format/modular/renderer.php');
class theme_magistere_format_modular_renderer extends format_modular_renderer {
    public function __construct(moodle_page $page, $target) {
        parent::__construct($page, $target);
        $this->courserenderer = $this;
        $this->defaultcourserender = $this->page->get_renderer('core', 'course');
    }

    public function course_section_cm_list($course, $section, $sectionreturn = null, $displayoptions = array()){
        global $USER;

        $output = '';
        $modinfo = get_fast_modinfo($course);
        if (is_object($section)) {
            $section = $modinfo->get_section_info($section->section);
        } else {
            $section = $modinfo->get_section_info($section);
        }
        $completioninfo = new completion_info($course);

        // check if we are currently in the process of moving a module with JavaScript disabled
        $ismoving = $this->page->user_is_editing() && ismoving($course->id);
        if ($ismoving) {
            $movingpix = new pix_icon('movehere', get_string('movehere'), 'moodle', array('class' => 'movetarget'));
            $strmovefull = strip_tags(get_string("movefull", "", "'$USER->activitycopyname'"));
        }

        // Get the list of modules visible to user (excluding the module being moved if there is one)
        $moduleshtml = array();
        if (!empty($modinfo->sections[$section->section])) {
            foreach ($modinfo->sections[$section->section] as $modnumber) {
                $mod = $modinfo->cms[$modnumber];

                if ($ismoving and $mod->id == $USER->activitycopy) {
                    // do not display moving mod
                    continue;
                }

                if ($modulehtml = $this->course_section_cm_list_item($course,
                    $completioninfo, $mod, $sectionreturn, $displayoptions)) {
                    $moduleshtml[$modnumber] = $modulehtml;
                }
            }
        }

        $sectionoutput = '';
        if (!empty($moduleshtml) || $ismoving) {
            foreach ($moduleshtml as $modnumber => $modulehtml) {
                if ($ismoving) {
                    $movingurl = new moodle_url('/course/mod.php', array('moveto' => $modnumber, 'sesskey' => sesskey()));
                    $sectionoutput .= html_writer::tag('li',
                        html_writer::link($movingurl, $this->output->render($movingpix), array('title' => $strmovefull)),
                        array('class' => 'movehere'));
                }

                $sectionoutput .= $modulehtml;
            }

            if ($ismoving) {
                $movingurl = new moodle_url('/course/mod.php', array('movetosection' => $section->id, 'sesskey' => sesskey()));
                $sectionoutput .= html_writer::tag('li',
                    html_writer::link($movingurl, $this->output->render($movingpix), array('title' => $strmovefull)),
                    array('class' => 'movehere'));
            }
        }

        // Always output the section module list.
        $output .= html_writer::tag('ul', $sectionoutput, array('class' => 'section img-text'));

        return $output;
    }

    public function course_section_cm_list_item($course,
                                                $completioninfo, $mod, $sectionreturn, $displayoptions){
        global $USER, $DB;

        $output = '';


        switch ($mod->icon) {
            case 'f/document-24':
                $resType = 'restexte';
                break;
            case 'f/spreadsheet-24':
                $resType = 'restableau';
                break;
            case 'f/powerpoint-24':
                $resType = 'respresentation';
                break;
            case 'f/writer-24':
                $resType = 'restexte';
                break;
            case 'f/calc-24':
                $resType = 'restableau';
                break;
            case 'f/impress-24':
                $resType = 'respresentation';
                break;
            case 'f/pdf-24':
                $resType = 'restexte';
                break;
            case 'f/audio-24':
                $resType = 'resmedia';
                break;
            case 'f/mp3-24':
                $resType = 'resmedia';
                break;
            case 'f/wav-24':
                $resType = 'resmedia';
                break;
            case 'f/avi-24':
                $resType = 'resmedia';
                break;
            case 'f/mpeg-24':
                $resType = 'resmedia';
                break;
            case 'f/wmv-24':
                $resType = 'resmedia';
                break;
            case 'f/video-24':
                $resType = 'resmedia';
                break;
            case 'f/text-24':
                $resType = 'restexte';
                break;
            case 'f/bmp-24':
                $resType = 'resmedia';
                break;
            case 'f/gif-24':
                $resType = 'resmedia';
                break;
            case 'f/jpeg-24':
                $resType = 'resmedia';
                break;
            case 'f/png-24':
                $resType = 'resmedia';
                break;
            case 'f/tiff-24':
                $resType = 'resmedia';
                break;
            default :
                $resType = '';
                break;
        }

        if ($modulehtml = $this->course_section_cm($course, $completioninfo, $mod, $sectionreturn, $displayoptions)) {
            $modclasses = $resType.' activity ' . $mod->modname . ' modtype_' . $mod->modname . ' aardvark-activity-section' . $mod->extraclasses;
            $output .= html_writer::tag('li', $modulehtml, array('class' => $modclasses, 'id' => 'module-' . $mod->id));
        }
        return $output;
    }

    public function course_section_cm($course, $completioninfo, $mod, $sectionreturn, $displayoptions){
        global $CFG, $DB, $OUTPUT;
        $output = '';
        // We return empty string (because course module will not be displayed at all)
        // if:
        // 1) The activity is not visible to users
        // and
        // 2) The 'availableinfo' is empty, i.e. the activity was
        //     hidden in a way that leaves no info, such as using the
        //     eye icon.
        if ((!$mod->uservisible && empty($mod->availableinfo)) || !$mod-> is_visible_on_course_page()) {
            return $output;
        }


        $visibleclass = '';
        /*
        if ($mod->visible == 0)
        {
            $visibleclass = ' dimmed_text';
        }
        */
        $output .= html_writer::start_tag('div');

        if ($this->page->user_is_editing()) {
            $output .= course_get_cm_move($mod, $sectionreturn);
        }

        $output .= html_writer::start_tag('div', array('class' => 'mod-indent-outer'));

        $output .= html_writer::tag('div', '', array('class' => 'mod-indent mod-indent-'.$mod->indent.''));

        // Start a wrapper for the actual content to keep the indentation consistent
        $output .= html_writer::start_tag('div', array('class' => 'main-content'.$visibleclass));

        $accessURLAttributes = array();
        // get custom url (eg : centralizedresources) if exists
        if(file_exists($CFG->dirroot.'/mod/'.$mod->modname.'/renderer.php')){
            require_once($CFG->dirroot.'/mod/'.$mod->modname.'/renderer.php');

            $modrendername = 'mod_'.$mod->modname.'_renderer';
            if(class_exists($modrendername)) {
                $r = new $modrendername($this->page, $this->target);
                if (method_exists($r, 'aardvark_custom_access_url')) {
                    $accessURLAttributes = $r->aardvark_custom_access_url($mod);
                }
            }
        }

        if(!isset($accessURLAttributes['href'])){
            $accessURLAttributes['href'] = $mod->url;
        }

        $cmname = '';

        if($mod->modname != 'educationallabel' && $mod->modname != 'label' && $mod->modname != 'completionmarker') {
            $cmname = html_writer::div('', 'activityicon');
            $cmname .= html_writer::span($mod->name, 'instancename');

            if(!empty($accessURLAttributes['href'])) {
                $cmname = html_writer::tag('a', $cmname, $accessURLAttributes);
            }
        }

        $headerclass = 'noheader';
        if($this->page->user_is_editing() && !empty($cmname)){
            $headerclass = 'header-editing';
        }


        // $output .= '<img src="'.$iconurl.'" class="iconlarge activityicon"/>';
        if (!empty($cmname)) {
            $output .= html_writer::start_tag('div', array('class' => $headerclass));
            // Start the div for the activity title, excluding the edit icons.
            $output .= $cmname;

            if($this->page->user_is_editing()){
                $output .= html_writer::img($OUTPUT->image_url('t/editstring'), '', array('class' => 'editbutton'));
            }

            // Module can put text after the link (e.g. forum unread)
            $output .= $mod->afterlink;

            // display access button if href is set

            $output .= html_writer::start_tag('div', array('class' => 'activityinstance'));

            if(!empty($accessURLAttributes['href'])) {
                if (isset($accessURLAttributes['class'])) {
                    $accessURLAttributes['class'] .= ' button';
                } else {
                    $accessURLAttributes['class'] = 'button';
                }
                $output .= html_writer::start_tag('a', $accessURLAttributes);
                $output .= '<i class="fa fa-play" aria-hidden="true"></i>';
                $output .= 'Accéder';

                $output .= html_writer::end_tag('a');
            }


            // Closing the tag which contains everything but edit icons. Content part of the module should not be part of this.
            $output .= html_writer::end_tag('div'); // .activityinstance

            $output .= html_writer::end_tag('div');
        }

        // modification icons
        $modicons = '';


        if ($this->page->user_is_editing()) {
            $editactions = course_get_cm_edit_actions($mod, $mod->indent, $sectionreturn);
            $modicons .= ' '. $this->defaultcourserender->course_section_cm_edit_actions($editactions, $mod, $displayoptions);
            $modicons .= $mod->afterediticons;
        }

        $modicons .= $this->defaultcourserender->course_section_cm_completion($course, $completioninfo, $mod, $displayoptions);

        if (!empty($modicons)) {
            $output .= html_writer::span($modicons, 'actions');
        }


        // load renderer if exists and get the custom content
        if(file_exists($CFG->dirroot.'/mod/'.$mod->modname.'/renderer.php')){
            require_once($CFG->dirroot.'/mod/'.$mod->modname.'/renderer.php');

            $modrendername = 'mod_'.$mod->modname.'_renderer';
            if(class_exists($modrendername)) {
                $r = new $modrendername($this->page, $this->target);
                if (method_exists($r, 'aardvark_custom_section_content')) {
                    $output .= html_writer::start_div('custom-content');
                    $output .= $r->aardvark_custom_section_content($mod);
                    // Ajout de la description pour les URL
                    if($mod->modname == "url"){
                        $output .= $mod->content;
                    }
                    $output .= html_writer::end_div();
                }else{
                    $output .= $this->defaultcourserender->course_section_cm_text($mod, $displayoptions);
                }
            }else{
                $output .= $this->defaultcourserender->course_section_cm_text($mod, $displayoptions);
            }
        }else{
            $output .= $this->defaultcourserender->course_section_cm_text($mod, $displayoptions);
        }

        // show availability info (if module is not available)
        $output .= $this->defaultcourserender->course_section_cm_availability($mod, $displayoptions);

        $output .= html_writer::end_tag('div'); // $indentclasses

        // End of indentation div.
        $output .= html_writer::end_tag('div');

        $output .= html_writer::end_tag('div');
        return $output;
    }


    function course_section_add_cm_control($course, $section, $sectionreturn = null, $displayoptions = array()) {
        return $this->defaultcourserender->course_section_add_cm_control($course, $section, $sectionreturn, $displayoptions);
    }

    function course_section_text($mod, $displayoptions){

    }

    public function availability_info($text, $additionalclasses = '') {
        global $CFG;

        require_once($CFG->dirroot.'/course/renderer.php');
        $corecourserenderer = new core_course_renderer($this->page, $this->target);
        return $corecourserenderer->availability_info($text, $additionalclasses);
    }

}