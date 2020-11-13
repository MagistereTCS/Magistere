<?php

require_once($CFG->dirroot.'/blocks/course_migration/ConvertFlexpageToTopics.php');
require_once($CFG->dirroot.'/course/format/modular/lib.php');

class ConvertFlexpageToModular extends ConvertFlexpageToTopics
{

    protected $lastsection;
    protected $format;

    public function __construct()
    {
        $this->originalformat = 'flexpage';
        $this->convertedformat = 'modular';
        $this->maxdepthsummary = 3;

        $this->convertionname = 'modulaire';
    }

    protected function check_prerequisite($courseid)
    {
        parent::check_prerequisite($courseid);
    }

    protected function process_course_format_options()
    {
        global $DB;

        parent::process_course_format_options();

        $this->l('Conversion : change format to modular');

        $course = new stdClass();
        $course->id = $this->newcourseid;
        $course->format = 'modular';
        $DB->update_record('course', $course);

        // update course object
        $this->newcourse = $DB->get_record('course', array('id'=>$this->newcourseid),'*');

        $this->format = new format_modular('modular', $this->newcourseid);

        // update course object
        $this->newcourse = $DB->get_record('course', array('id'=>$this->newcourseid),'*');
    }

    protected function process_summary_data()
    {
        global $DB;

        $this->l('Conversion : move sections to module part');

        $introsection = array_slice($this->flexpagepage['tree'], 0, 1);
        $modulesection = array_slice($this->flexpagepage['tree'], 1, -1);
        $endsection = array_slice($this->flexpagepage['tree'], -1, 1);

        foreach($this->flexpagepage['nodes'] as $node)
        {
            $section = array_values($this->sections)[$node->sectionid];

            // Update section infos
            $section->name = $node->name;
            $section->visible = ($node->display>0?1:0);
            $section->sequence = '';
            $this->l('Conversion : Updating section "'.$node->sectionid.'" with data : ####'.print_r($section,true).'####');
            $DB->update_record('course_sections', $section);
        }


        $nodes = $introsection;
        for($i = 0; $i < count($nodes); $i++){
            $node = $nodes[$i];
            $parentid = ($node->parentid > 0 ? $this->flexpagepage['nodes'][$node->parentid]->sectionid : null);

            $this->l('Conversion : conversion de la section vers intro '.$node->name);

            $this->l(print_r($node, true));
            $this->l('parentid: '.$parentid);

            $this->format->move_to_intro($this->page_to_section[$node->id], $parentid);

            $nodes = array_merge($nodes, $node->child);
        }

        $nodes = $modulesection;
        for($i = 0; $i < count($nodes); $i++){
            $node = $nodes[$i];
            $parentid = ($node->parentid > 0 ? $this->flexpagepage['nodes'][$node->parentid]->sectionid : null);

            $this->l('Conversion : conversion de la section '.$node->name);

            $this->l(print_r($node, true));
            $this->l('parentid: '.$parentid);

            $this->format->move_to_module($this->page_to_section[$node->id], $parentid);

            $this->l(print_r($node->child, true));

            $nodes = array_merge($nodes, $node->child);
        }

        $nodes = $endsection;
        for($i = 0; $i < count($nodes); $i++){
            $node = $nodes[$i];
            $parentid = ($node->parentid > 0 ? $this->flexpagepage['nodes'][$node->parentid]->sectionid : null);

            $this->l('Conversion : conversion de la section '.$node->name);

            $this->l(print_r($node, true));
            $this->l('parentid: '.$parentid);

            $this->format->move_to_end($this->page_to_section[$node->id], $parentid);

            $nodes = array_merge($nodes, $node->child);
        }

        $this->l('Conversion : move orphan section to end part');

        $this->format->move_to_end($this->lastsection->id, 0);
    }

    protected function process_blocks_and_activities()
    {
        parent::process_blocks_and_activities();
    }

    protected function post_process($newcourseid)
    {
        parent::post_process($newcourseid);
        
        rebuild_course_cache($this->newcourseid, true);
    }

}