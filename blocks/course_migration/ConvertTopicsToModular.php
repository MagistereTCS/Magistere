<?php

require_once($CFG->dirroot.'/blocks/course_migration/BaseConvertor.php');
require_once($CFG->dirroot.'/course/format/modular/lib.php');

class ConvertTopicsToModular extends BaseConvertor
{

    protected $lastsection;
    protected $format;

    public function __construct()
    {
        $this->originalformat = 'topics';
        $this->convertedformat = 'modular';

        $this->convertionname = 'modulaire';
    }

    protected function check_prerequisite($courseid)
    {

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

    protected function process_section()
    {
        global $DB;

        $tree = array(
            'tree' => array(),
            'nodes' => array()
        );

        $tree['nodes'] = $DB->get_records_sql('SELECT
  cs.id,
  cs.name,
  cs.section numsection,
  bs.parentid,
  bs.weight 
FROM {course_sections} cs 
INNER JOIN {block_summary} bs ON bs.sectionid=cs.id
WHERE cs.course=?
ORDER BY bs.parentid, bs.weight', array($this->newcourseid));

        if(count($tree['nodes']) <= 0){
            $tree['nodes'] = $this->get_course_tree_when_empty($this->newcourseid);

        }

        foreach($tree['nodes'] as &$section){
            if($section->parentid == null){
                $tree['tree'][] = $section;
                continue;
            }

            if(!isset($tree['nodes'][$section->parentid]->child)){
                $tree['nodes'][$section->parentid]->child = array();
            }

            $tree['nodes'][$section->parentid]->child[] = $section;
        }

        $introsection = array_slice($tree['tree'], 0, 1);
        $modulesection = array_slice($tree['tree'], 1, -1);
        $endsection = array_slice($tree['tree'], -1, 1);


        $nodes = $introsection;
        for($i = 0; $i < count($nodes); $i++){
            $node = $nodes[$i];
            $parentid = ($node->parentid > 0 ? $tree['nodes'][$node->parentid]->numsection : null);

            $this->l('Conversion : conversion de la section vers intro '.$node->name);

            $this->l(print_r($node, true));
            $this->l('parentid: '.$parentid);

            $this->format->move_to_intro($node->id, $parentid);

            if(isset($node->child)){
                $nodes = array_merge($nodes, $node->child);
            }
        }

        $nodes = $modulesection;
        for($i = 0; $i < count($nodes); $i++){
            $node = $nodes[$i];
            $parentid = ($node->parentid > 0 ? $tree['nodes'][$node->parentid]->numsection : null);

            $this->l('Conversion : conversion de la section '.$node->name);

            $this->l(print_r($node, true));
            $this->l('parentid: '.$parentid);

            $this->format->move_to_module($node->id, $parentid);

            if(isset($node->child)){
                $this->l(print_r($node->child, true));
                $nodes = array_merge($nodes, $node->child);
            }
        }

        $nodes = $endsection;
        for($i = 0; $i < count($nodes); $i++){
            $node = $nodes[$i];
            $parentid = ($node->parentid > 0 ? $tree['nodes'][$node->parentid]->numsection : null);

            $this->l('Conversion : conversion de la section '.$node->name);

            $this->l(print_r($node, true));
            $this->l('parentid: '.$parentid);

            $this->format->move_to_end($node->id, $parentid);

            if(isset($node->child)){
                $nodes = array_merge($nodes, $node->child);
            }
        }
    }

    protected function update_sequence_module()
    {

    }

    protected function post_process($newcourseid)
    {
        rebuild_course_cache($this->newcourseid, true);
    }

    function get_course_tree_when_empty($courseid)
    {
        global $DB;
        $sections = $DB->get_records_sql('
SELECT cs.id, cs.id AS sectionid, cs.course AS courseid, bs.parentid, bs.weight, cs.name, cs.visible, cs.section
FROM {course_sections} cs
LEFT JOIN {block_summary} bs ON (cs.id = bs.sectionid)
WHERE cs.course = '.$courseid.' AND cs.section > 0  ORDER BY bs.parentid,cs.section ASC');

        // Get section details
        $modinfo = get_fast_modinfo($courseid);
        $coursesections = $modinfo->get_section_info_all();

        $sec = array();
        foreach($sections AS $key=>$section)
        {
            $sec[$key] = new stdClass();
            $sec[$key]->id = $section->sectionid;
            $sec[$key]->parentid = $section->parentid;
            $sec[$key]->section = $section->section;
            $sec[$key]->courseid = $section->courseid;
            $sec[$key]->name = $section->name;
            $sec[$key]->visible = $section->visible;
            $sec[$key]->uservisible = $coursesections[$section->section]->uservisible;
            $sec[$key]->weight = $section->section;
        }



        foreach($sec AS $key=>$se)
        {
            if ($sec[$key]->parentid != null && $sec[$key]->parentid > 0)
            {
                if (isset($sec[$sec[$key]->parentid]->children) && !is_array($sec[$sec[$key]->parentid]->children))
                {
                    $sec[$sec[$key]->parentid]->children = array();
                }

                if(!$sec[$sec[$key]->parentid]->uservisible && $sec[$key]->uservisible){
                    $sec[$key]->uservisible = false;
                }


                $sec[$sec[$key]->parentid]->children[$key] = $sec[$key];
                unset($sec[$key]);
            }

        }

        return $sec;
    }

}