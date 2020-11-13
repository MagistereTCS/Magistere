<?php

require_once($CFG->dirroot.'/blocks/course_migration/BaseConvertor.php');

class ConvertFlexpageToTopics extends BaseConvertor
{

    protected $lastsection;

    protected $maxdepthsummary;

    protected $flexpagepage;

    public function __construct()
    {
        $this->originalformat = 'flexpage';
        $this->convertedformat = 'topics';

        $this->maxdepthsummary = 2;

        $this->convertionname = 'standard';
    }

    protected function check_prerequisite($courseid)
    {
        global $DB;

        $course = $DB->get_record('course', array('id'=>$courseid),'*');
        if ($course === false)
        {
            $this->lasterror = 'Parcours introuvable!';
            $this->l('Check prerequisite error, course "'.$courseid.'" not found ');
            return false;
        }

        $context = context_course::instance($courseid);

        $summary = $DB->get_record('block_instances', array('parentcontextid'=>$context->id,'blockname'=>'summary'));
        if ($summary === false)
        {
            //$this->lasterror = 'Aucun block sommaire trouvé! Ce block est indispensable pour la Conversion du parcours.';
            $this->l('Check prerequisite : no summary block found (courseid="'.$courseid.'")');
            //return false;

            $sum = new stdClass();
            $sum->blockname = 'summary';
            $sum->parentcontextid = $context->id;
            $sum->showinsubcontexts = '1';
            $sum->pagetypepattern = '*';
            $sum->subpagepattern = null;
            $sum->defaultregion = 'side-pre';
            $sum->defaultweight = '-1000000';
            $sum->configdata = '';
            $sum->timecreated = time();
            $sum->timemodified = time();


            $sumid = $DB->insert_record('block_instances', $sum);

            if ($course->format == 'flexpage')
            {
                echo "Is flexpage course\n";
                $pages = $DB->get_records('format_flexpage_page',array('courseid'=>$courseid));

                foreach ($pages AS $page)
                {
                    $sump = new stdClass();
                    $sump->blockinstanceid = $sumid;
                    $sump->contextid = $context->id;
                    $sump->pagetype = 'course-view-flexpage';
                    $sump->subpage = $page->id;
                    $sump->visible = 0;
                    $sump->region = 'side-pre';
                    $sump->weight = '-1000000';
                    echo "Add hidden position record for page : ###".print_r($sump,true)."###\n";
                    $DB->insert_record('block_positions', $sump);
                }
            }
        }
        
        $this->l('Conversion : START EXECUTE DEDEDUP MODULE');
        $this->dededup_course_modules($courseid);
        $this->l('Conversion : FIN EXECUTE DEDEDUP MODULE');
    }

    protected function process_course_format_options()
    {
        global $DB;

        parent::process_course_format_options();

        $this->l('Conversion : get all format_flexpage_page records');
        $this->format_flexpage_pages = $DB->get_records('format_flexpage_page',array('courseid'=>$this->newcourseid), 'parentid,weight ASC');
        $this->l('Conversion : '.count($this->format_flexpage_pages).' records found');

        $count_hidden = 0;
        foreach($this->format_flexpage_pages as $format_flexpage_page)
        {
            if ($format_flexpage_page->display == 0)
            {
                $count_hidden = $count_hidden+1;
            }
        }

        $this->l('Conversion : The course have a total of '.count($this->format_flexpage_pages).' page including '.$count_hidden.' hidden pages');

        $this->numsection = count($this->format_flexpage_pages)+1;

        // Change new course format from flexpage to topics
        $course_format_options = new stdClass();
        $course_format_options->courseid = $this->newcourseid;
        $course_format_options->format = 'topics';
        $course_format_options->sectionid = 0;
        $course_format_options->name = 'numsections';
        $course_format_options->value = $this->numsection;

        $this->l('Conversion : insert new option "numsections" in "course_format_options" with ####'.print_r($course_format_options,true).'####');
        $DB->insert_record('course_format_options', $course_format_options);

        // Insert hiddensections params
        $course_format_options->name = 'hiddensections';
        $course_format_options->value = $count_hidden;

        $this->l('Conversion : insert new option "hiddensections" in "course_format_options" with ####'.print_r($course_format_options,true).'####');
        $DB->insert_record('course_format_options', $course_format_options);


        $course_format_options->name = 'coursedisplay';
        $course_format_options->value = 1;

        $this->l('Conversion : insert new option "coursedisplay" in "course_format_options" with ####'.print_r($course_format_options,true).'####');
        $DB->insert_record('course_format_options', $course_format_options);

        $this->newcourse->format = 'topics';

        $this->l('Conversion : updating course format and disable news forum with data ####'.print_r($course_format_options,true).'####');
        $DB->update_record('course', $this->newcourse);


        $this->l('Conversion : rebuilding course cache for "'.$this->newcourse->id.'"');
        rebuild_course_cache($this->newcourseid, true);

        // update course object
        $this->newcourse = $DB->get_record('course', array('id'=>$this->newcourseid),'*');
    }

    protected function process_section()
    {
        global $DB;

        $this->l('Conversion : create the "'.$this->numsection.'" new sections');
        course_create_sections_if_missing($this->newcourseid, range(0, $this->numsection));

        // Get new sections
        $this->sections = $DB->get_records('course_sections',array('course'=>$this->newcourseid),'section ASC');

        $this->page_to_section = array();

        // Build relationnal array between flexpagepage and new section
        $this->l('Conversion : ### Build relationnal array between flexpagepage and new section');

        // build the summary tree from flexpage page
        $this->flexpagepage = array(
            'tree' => array(),
            'nodes' => array()
        );

        foreach($this->format_flexpage_pages as $ffp)
        {
            $this->flexpagepage['nodes'][$ffp->id] = $ffp;
            $this->flexpagepage['nodes'][$ffp->id]->child = array();
            $this->flexpagepage['nodes'][$ffp->id]->depth = 0;

            if($ffp->parentid == 0){
                $this->flexpagepage['tree'][$ffp->id] = &$this->flexpagepage['nodes'][$ffp->id];

                continue;
            }

            $parent = $ffp->parentid;

            $this->flexpagepage['nodes'][$parent]->child[$ffp->id] = &$this->flexpagepage['nodes'][$ffp->id];

            $depth = 0;
            $currentparentid = $parent;
            while($currentparentid > 0){
                $currentparentid = $this->format_flexpage_pages[$currentparentid]->parentid;
                $depth++;
            }

            $this->flexpagepage['nodes'][$ffp->id]->depth = $depth;
        }

        // remap children cause section aren't in the right order
        foreach($this->format_flexpage_pages as $ffp){
            $parent = $ffp->parentid;

            if($parent == 0){
                continue;
            }

            $this->flexpagepage['nodes'][$parent]->child[$ffp->id] = &$this->flexpagepage['nodes'][$ffp->id];
        }

        // flat the summary at the prefered depth
        foreach($this->flexpagepage['nodes'] as $node)
        {
            // if the depth node is the second last
            // we need to flat all children (and grand children...)
            if($node->depth == $this->maxdepthsummary-2){
                $newchild = array();
                foreach($node->child as $child){
                    $newchild = ($newchild + $this->flattentree($child));
                }

                // update the children parent id
                foreach($newchild as $child){
                    $child->parentid = $node->id;
                }

                $node->child = $newchild;
            }
        }

        $this->l('Summary after flattening');
        $this->l(print_r($this->flexpagepage['tree'], true));

        // now we will compute the section number for each node
        // we will traversing the tree according a BFS
        $sectionid = 1;
        foreach($this->flexpagepage['tree'] as $c){
            $this->affect_section($c, $sectionid);
        }

        $this->l('Conversion : Relationnal array between flexpagepage and new section built : ####'.print_r($this->page_to_section,true).'####');

        $this->lastsection = array_values($this->sections)[count($this->sections)-1];
        $this->lastsection->name = 'Activités orphelines';
        $this->lastsection->visible = 0;
        $this->l('Conversion : Update the last section for orphan activities : ####'.print_r($this->lastsection,true).'####');
        $DB->update_record('course_sections', $this->lastsection);


        // Remove sequence list from first sequence
        $this->l('Conversion : ### Remove sequence list from first sequence');
        $this->section0 = array_values($this->sections)[0];
        $this->section0->name = '';
        $this->section0->sequence = '';
        $this->l('Conversion : Update the first section (0), remove sequence and name : ####'.print_r($this->section0,true).'####');
        $DB->update_record('course_sections', $this->section0);
    }

    protected function process_summary_data()
    {
        global $DB;

        $this->l('Conversion : Begin updating section data with flexpage page data');

        foreach($this->flexpagepage['nodes'] as $node){
            $section = array_values($this->sections)[$node->sectionid];

            // Update section infos
            $section->name = $node->name;
            $section->visible = ($node->display>0?1:0);
            $section->sequence = '';
            $this->l('Conversion : Updating section "'.$node->sectionid.'" with data : ####'.print_r($section,true).'####');
            $DB->update_record('course_sections', $section);

            // Create summary record with parent info
            $summary = new stdClass();
            $summary->courseid = $this->newcourseid;
            $summary->sectionid = $section->id;
            $summary->parentid = (isset($node->parentid) && $node->parentid > 0?$this->page_to_section[$node->parentid]:null);
            $summary->weight = $node->sectionid;
            $this->l('Conversion : Insert block summary record for section "'.$node->sectionid.'" with data : ####'.print_r($summary,true).'####');


            // tricky case
            // certain utilisateurs ont modifier le format de leur parcours manuellement
            // donc il est possible qu'il existe des donnees du block sommaire
            $summaryDB = $DB->get_record('block_summary', array('courseid' => $summary->courseid, 'weight' => $summary->weight), 'id');
            if($summaryDB){
                $summary->id = $summaryDB->id;
                $DB->update_record('block_summary', $summary);
            }else{
                $DB->insert_record('block_summary', $summary);
            }
        }
    }

    protected function affect_section($node, &$sectionid)
    {
        $section = array_values($this->sections)[$sectionid];
        $this->page_to_section[$node->id] = $section->id;
        $this->flexpagepage['nodes'][$node->id]->sectionid = $sectionid;

        $this->l('---'.$node->name.'--- => section '.$sectionid);

        $sectionid++;

        foreach($node->child as $c){
            $this->affect_section($c, $sectionid);
        }
    }

    protected function flattentree($node)
    {
        $res = array($node->id => $node);

        foreach($node->child as $child)
        {
            $res = ($res + $this->flattentree($child));
        }

        $node->child = array();

        return $res;
    }

    protected function process_blocks_and_activities()
    {
        global $DB;
        rebuild_course_cache($this->newcourseid, true);
        $this->single_forum_migration();

// Move old block from flexpage page to course root
        $this->l('Conversion : ### Move old block from flexpage page to course root');
        $general_blocks = $DB->get_records_sql('
SELECT bi.*
FROM mdl_block_instances bi
WHERE bi.parentcontextid = '.$this->newcoursecontext->id.'
AND bi.blockname NOT IN ("flexpagenav","flexpagemod","html","rolespecifichtml","educationalbloc")');

        if ($general_blocks !== false)
        {
            foreach($general_blocks AS $gb)
            {
                $gb->subpagepattern = null;

                $this->l('Conversion : Updating block_instances to remove "subpagepattern" with data : ####'.print_r($gb,true).'####');
                $DB->update_record('block_instances', $gb);
            }
        }
        
        
        // Move old flexpagemod block modules to their sections
        $this->l('Conversion : ### Move old flexpagemod block modules to their sections');
        $flexpagemods = $DB->get_records_sql('
SELECT bi.*, bf.id AS "bfid", bf.cmid, bp.id AS bpid, IF(bp.weight IS NULL,bi.defaultweight,bp.weight) AS weight
FROM mdl_block_instances bi 
LEFT JOIN mdl_block_flexpagemod bf ON (bf.instanceid=bi.id)
LEFT JOIN mdl_block_positions bp ON (bi.id = bp.blockinstanceid AND bi.parentcontextid = bp.contextid)
WHERE bi.parentcontextid = '.$this->newcoursecontext->id.'
AND bi.blockname = "flexpagemod"
');
        if ($flexpagemods !== false)
        {
            
            $mods = array();
            foreach($flexpagemods AS $fpm)
            {
                $mods[$fpm->cmid][$fpm->id] = true;
            }
            $this->l('Conversion : ### Flexpagemod blocks for each activity');
            $this->l('Mods list : '.print_r($mods,true));
            foreach($flexpagemods AS $fpm)
            {
                // Update section ID

                /* #2612 - TCS - Bloc sans activité */
                if ($fpm->cmid) {
                    $cm = $DB->get_record('course_modules', array('id'=>$fpm->cmid));

                    if ($cm !== false)
                    {
                        $this->l('Conversion : processing flexpagemod block : ####'.print_r($fpm,true).'####');

                        if (!isset($this->page_to_section[$fpm->subpagepattern])) {
                            $cm->section= $this->lastsection->id;
                        }else{
                            $cm->section= $this->page_to_section[$fpm->subpagepattern];
                        }

                        $cm->idnumber = $fpm->weight;

                        $this->l('Conversion : Updating course_modules : ####'.print_r($cm,true).'####');

                        $DB->update_record('course_modules', $cm);
                    }
                }

                $this->l('Conversion : Deleting old block_instances : ####'.print_r($fpm->id,true).'####');
                $DB->delete_records('block_instances',array('id'=>$fpm->id));

                $this->l('Conversion : Deleting old block_flexpagemod : ####'.print_r($fpm->id,true).'####');
                $DB->delete_records('block_flexpagemod',array('id'=>$fpm->bfid));

                if ($fpm->bpid != null && $fpm->bpid > 0) {
                    $this->l('Conversion : Deleting old block_positions : ####'.print_r($fpm->id,true).'####');
                    $DB->delete_records('block_positions',array('id'=>$fpm->bpid));
                }
            }
        }

        $flexpagenavblocks = $DB->get_records('block_instances',array('parentcontextid'=>$this->newcoursecontext->id,'blockname'=>'flexpagenav'), '', 'id');
        $flexpagenavblocks = array_keys($flexpagenavblocks);

        $DB->delete_records_list('block_positions', 'blockinstanceid', $flexpagenavblocks);

        $DB->delete_records('block_instances',array('parentcontextid'=>$this->newcoursecontext->id,'blockname'=>'flexpagenav'));


        // Convert html block in label mod
        $this->l('Conversion : ### Convert html block in label mod');
        $htmlblocks = $DB->get_records_sql('
SELECT bi.*, bp.id AS bpid, IF(bp.weight IS NULL,bi.defaultweight,bp.weight) AS weight
FROM mdl_block_instances bi
LEFT JOIN mdl_block_positions bp ON (bi.id = bp.blockinstanceid AND bi.parentcontextid = bp.contextid)
WHERE bi.parentcontextid = '.$this->newcoursecontext->id.'
AND bi.blockname = "html"
');
        if ($htmlblocks !== false)
        {
            $module_label = $DB->get_record('modules', array('name'=>'label'));

            $this->l('Conversion : found "'.count($htmlblocks).'" html blocks');
            foreach($htmlblocks AS $hb)
            {
                $this->l('Conversion : processing html block : ####'.print_r($hb,true).'####');
                // Get block configdata
                $configdata = unserialize(base64_decode($hb->configdata));

                // remove all 4 byte characters...
                $configdata->text = preg_replace('/[\xF0-\xF7].../s', '', $configdata->text);
                $configdata->title = mb_substr($configdata->title, 0, 255);

                //$block_context = context_block::instance_by_id($hb->id);
                $block_context = $DB->get_record('context', array('instanceid'=>$hb->id,'contextlevel'=>CONTEXT_BLOCK));

                $this->l('Conversion : block configdata ##'.print_r($configdata,true).'##');

                // Create new label activity
                $label = new stdClass();
                $label->course = $this->newcourseid;
                $label->name = (isset($configdata->title)&&!empty($configdata->title)&&strlen($configdata->title)>0?$configdata->title:'');
                $label->intro = '<h3>'.$label->name.'</h3><br/>'.$configdata->text;
                $label->introformat = (!empty($configdata->format)&&strlen($configdata->format)?$configdata->format:'');
                $label->timemodified = time();

                $this->l('Conversion : insert new label activity : ####'.print_r($label,true).'####');
                $label->id = $DB->insert_record('label', $label);

                $section = $this->page_to_section[$hb->subpagepattern];
                if ($section < 1)
                {
                    $section = $this->section0->id;
                }

                $cm = new stdClass();
                $cm->course = $this->newcourseid;
                $cm->module = $module_label->id;
                $cm->instance = $label->id;
                $cm->section = $section;
                $cm->visible = 1;
                $cm->visibleold = 1;
                $cm->idnumber = $hb->weight;

                $this->l('Conversion : insert new label activity in course modules : ####'.print_r($cm,true).'####');

                $newid = $DB->insert_record('course_modules', $cm);

                $module_context = $DB->get_record('context', array('contextlevel'=>CONTEXT_MODULE,'instanceid'=>$newid));

                if ($module_context === false)
                {
                    $module_context = new stdClass();
                    $module_context->contextlevel = CONTEXT_MODULE;
                    $module_context->instanceid = $newid;
                    $module_context->depth = $block_context->depth;
                    $module_context->path = '';

                    $module_context->id = $DB->insert_record('context', $module_context);

                    $module_context->path = substr($block_context->path,0,strrpos($block_context->path, '/')).'/'.$module_context->id;

                    $DB->update_record('context', $module_context);
                }

                $this->l('Conversion : New course modules ID : ####'.print_r($newid,true).'####');

                $this->l('Conversion : Move html block files : ####'.print_r($newid,true).'####');



                $files = $DB->get_records_sql("SELECT f.* FROM {files} f 
WHERE f.component = 'block_html' AND contextid = '".$block_context->id."'");

                foreach($files AS $file)
                {
                    $this->l('Conversion : Processing file ####'.print_r($file,true).'####');

                    $file->component = 'mod_label';
                    $file->contextid = $module_context->id;
                    $file->filearea = 'intro';
                    $file->pathnamehash = file_storage::get_pathname_hash($file->contextid,$file->component,$file->filearea,$file->itemid,$file->filepath,$file->filename);

                    $this->l('Conversion : Updating file ####'.print_r($file,true).'####');
                    $DB->update_record('files', $file);
                }



                $this->l('Conversion : Deleting old block_instances : ####'.print_r($hb->id,true).'####');
                $DB->delete_records('block_instances',array('id'=>$hb->id));

                if ($hb->bpid != null && $hb->bpid > 0)
                {
                    $this->l('Conversion : Deleting old block_positions : ####'.print_r($hb->id,true).'####');
                    $DB->delete_records('block_positions',array('id'=>$hb->bpid));
                }

            }
        }

        // Convert educationalbloc block in educationallabel mod
        $this->l('Conversion : ### Convert educationalbloc block in educationallabel mod');
        $educationalblocblocks = $DB->get_records_sql('
SELECT bi.*, bp.id AS bpid, IF(bp.weight IS NULL,bi.defaultweight,bp.weight) AS weight
FROM mdl_block_instances bi
LEFT JOIN mdl_block_positions bp ON (bi.id = bp.blockinstanceid AND bi.parentcontextid = bp.contextid)
WHERE bi.parentcontextid = '.$this->newcoursecontext->id.'
AND bi.blockname = "educationalbloc"
');
        if ($educationalblocblocks !== false)
        {

            $edulabtypes = array(
                "1"=>"Présentation de votre formation",
                "2"=>"Comment réussir votre formation ?",
                "3"=>"Activité à réaliser",
                "4"=>"Note aux formateurs",
                "5"=>"Important"
            );

            $module_educationallabel = $DB->get_record('modules', array('name'=>'educationallabel'));

            $this->l('Conversion : found "'.count($educationalblocblocks).'" educationalbloc blocks');
            foreach($educationalblocblocks AS $elb)
            {
                $this->l('Conversion : processing educationalbloc block : ####'.print_r($elb,true).'####');

                // Get block configdata
                $configdata = unserialize(base64_decode($elb->configdata));
                // remove all 4 byte characters...
                $configdata->text = preg_replace('/[\xF0-\xF7].../s', '', $configdata->text);
                //$configdata->text = mb_substr($configdata->text, 0, 255);

                $block_context = $DB->get_record('context', array('instanceid'=>$elb->id,'contextlevel'=>CONTEXT_BLOCK));

                // Create new label activity
                $educationallabel= new stdClass();
                $educationallabel->course = $this->newcourseid;
                $educationallabel->intro = (!empty($configdata->text)&&strlen($configdata->text)>0?$configdata->text:'');
                $educationallabel->introformat = (!empty($configdata->format)&&strlen($configdata->format)>0?$configdata->format:'');
                $educationallabel->type = str_replace('type','',$configdata->selecttype);
                $educationallabel->name = (!empty($edulabtypes[$educationallabel->type])&&strlen($edulabtypes[$educationallabel->type])>0?$edulabtypes[$educationallabel->type]:'');
                $educationallabel->timemodified = time();

                $this->l('Conversion : insert new educationallabel activity : ####'.print_r($educationallabel,true).'####');
                $educationallabel->id = $DB->insert_record('educationallabel', $educationallabel);


                $section = $this->page_to_section[$elb->subpagepattern];
                if ($section < 1)
                {
                    $section = $this->section0->id;
                }

                $cm = new stdClass();
                $cm->course = $this->newcourseid;
                $cm->module = $module_educationallabel->id;
                $cm->instance = $educationallabel->id;
                $cm->section = $section;
                $cm->visible = 1;
                $cm->visibleold = 1;
                $cm->idnumber = $elb->weight;

                $this->l('Conversion : insert new educationallabel activity in course modules : ####'.print_r($cm,true).'####');

                $newid = $DB->insert_record('course_modules', $cm);

                $this->l('Conversion : New course modules ID : ####'.print_r($newid,true).'####');

                $module_context = $DB->get_record('context', array('contextlevel'=>CONTEXT_MODULE,'instanceid'=>$newid));

                if ($module_context === false)
                {
                    $module_context = new stdClass();
                    $module_context->contextlevel = CONTEXT_MODULE;
                    $module_context->instanceid = $newid;
                    $module_context->depth = $block_context->depth;
                    $module_context->path = '';

                    $module_context->id = $DB->insert_record('context', $module_context);

                    $module_context->path = substr($block_context->path,0,strrpos($block_context->path, '/')).'/'.$module_context->id;

                    $DB->update_record('context', $module_context);
                }

                $this->l('Conversion : New course modules ID : ####'.print_r($newid,true).'####');

                $this->l('Conversion : Move html block files : ####'.print_r($newid,true).'####');



                $files = $DB->get_records_sql("SELECT f.* FROM {files} f
WHERE f.component = 'block_educationalbloc' AND contextid = '".$block_context->id."'");

                foreach($files AS $file)
                {
                    $this->l('Conversion : Processing file ####'.print_r($file,true).'####');

                    $file->component = 'mod_educationallabel';
                    $file->contextid = $module_context->id;
                    $file->filearea = 'intro';
                    $file->pathnamehash = file_storage::get_pathname_hash($file->contextid,$file->component,$file->filearea,$file->itemid,$file->filepath,$file->filename);

                    $this->l('Conversion : Updating file ####'.print_r($file,true).'####');
                    $DB->update_record('files', $file);
                }




                $this->l('Conversion : Deleting old block_instances : ####'.print_r($elb->id,true).'####');
                $DB->delete_records('block_instances',array('id'=>$elb->id));

                if ($elb->bpid != null && $elb->bpid > 0)
                {
                    $this->l('Conversion : Deleting old block_positions : ####'.print_r($elb->id,true).'####');
                    $DB->delete_records('block_positions',array('id'=>$elb->bpid));
                }
            }
        }



        // Convert rolespecifichtml block in label mod
        $this->l('Conversion : ### Convert rolespecifichtml block in label mod');
        $rolespecifichtmlblocks = $DB->get_records_sql(
            'SELECT bi.*, bp.id AS bpid, IF(bp.weight IS NULL,bi.defaultweight,bp.weight) AS weight
FROM mdl_block_instances bi
LEFT JOIN mdl_block_positions bp ON (bi.id = bp.blockinstanceid AND bi.parentcontextid = bp.contextid)
WHERE bi.parentcontextid = '.$this->newcoursecontext->id.'
AND bi.blockname = "rolespecifichtml"'
        );
        if ($rolespecifichtmlblocks !== false)
        {
            $module_label = $DB->get_record('modules', array('name'=>'label'));

            $this->l('Conversion : found "'.count($educationalblocblocks).'" rolespecifichtml blocks');
            foreach($rolespecifichtmlblocks AS $rb)
            {
                $this->l('Conversion : processing rolespecifichtml block : ####'.print_r($elb,true).'####');


                if (!empty($rb->configdata) && strlen($rb->configdata) < 2)
                {

                    // Get block configdata
                    $configdata = unserialize(base64_decode($rb->configdata));
                    $block_context = $DB->get_record('context', array('instanceid'=>$rb->id,'contextlevel'=>CONTEXT_BLOCK));

                    //$roles = explode(',',$configdata->textids);

                    $roles = $DB->get_records_sql('SELECT * FROM {role} WHERE id IN ('.$configdata->textids.') ORDER BY sortorder ASC');

                    $this->l('Conversion : configdata ##'.print_r($configdata,true).'##');

                    $text = $configdata->text_all."\n\n";
                    foreach($roles AS $role)
                    {
                        if (isset($configdata->{'text_'.$role->id}) && strlen($configdata->{'text_'.$role->id}) > 2)
                        {
                            if (strlen($text) > 2){$text .= "<br/><br/>";}
                            $text .= 'Pour le rôle '.$role->name.' :<br/>';
                            $text .= $configdata->{'text_'.$role->id};
                        }
                    }

                    $text = '"'.$text.'"';

                    // Create new label activity
                    $label = new stdClass();
                    $label->course = $this->newcourseid;
                    $label->name = (isset($configdata->title)&&!empty($configdata->title)?$configdata->title:'');
                    $label->intro = (strlen($text)>1&&!empty($text)?$text:'');
                    $label->introformat = (!empty($configdata->format_all)&&strlen($configdata->format_all)?$configdata->format_all:'');
                    $label->timemodified = time();

                    $this->l('Conversion : insert new label activity : ####'.print_r($label,true).'####');
                    $labelid = $DB->insert_record('label', $label);

                    $section = $this->page_to_section[$rb->subpagepattern];
                    if ($section < 1)
                    {
                        $section = $this->section0->id;
                    }


                    $cm = new stdClass();
                    $cm->course = $this->newcourseid;
                    $cm->module = $module_label->id;
                    $cm->instance = $labelid;
                    $cm->section = $section;
                    $cm->visible = 1;
                    $cm->visibleold = 1;
                    $cm->idnumber = $rb->weight;

                    $this->l('Conversion : insert new label activity in course modules : ####'.print_r($cm,true).'####');

                    $newid = $DB->insert_record('course_modules', $cm);

                    $this->l('Conversion : New course modules ID : ####'.print_r($newid,true).'####');
                }


                $this->l('Conversion : Deleting old block_instances : ####'.print_r($rb->id,true).'####');
                $DB->delete_records('block_instances',array('id'=>$rb->id));

                if ($rb->bpid != null && $rb->bpid > 0)
                {
                    $this->l('Conversion : Deleting old block_positions : ####'.print_r($rb->id,true).'####');
                    $DB->delete_records('block_positions',array('id'=>$rb->bpid));
                }
            }
        }

        // => CLEO - evol #2143 2.0.9 : conversion page en label
        $this->l('Conversion : ### Convert page activity into label activity');
        $coursePages = $DB->get_records_sql('SELECT * FROM {page} WHERE course = '.$this->newcourseid);
        if ($coursePages !== false) {
            foreach ($coursePages AS $cp) {
                $this->l('Conversion : processing page : ####'.print_r($cp,true).'####');

                if (!empty($cp->displayoptions)) {
                    // Create new label activity
                    $label = new stdClass();
                    $label->course = $this->newcourseid;
                    $titleHead = "";
                    $titleDesc = "";
                    $pageContent = "";

                    // New label content depend on display options
                    $displayOptions = unserialize($cp->displayoptions);
                    if ($displayOptions['printheading'] == 1)
                        $titleHead = "<h3>".$cp->name."</h3><br />";
                    if ($displayOptions['printintro'] == 1)
                        $titleDesc = "<h5>".$cp->intro."</h5><br />";
                    $this->l('Conversion : display options OK');

                    $coursemodule = $DB->get_record_sql('SELECT * FROM {course_modules} WHERE instance = '.$cp->id
                        .' AND module = (SELECT id FROM {modules} WHERE name = "page") 
                                                            AND course = '.$this->newcourseid);

                    $module_context = $DB->get_record('context', array('contextlevel'=>CONTEXT_MODULE,'instanceid'=>$coursemodule->id));

                    $files = $DB->get_records_sql("SELECT f.* FROM {files} f WHERE f.component = 'mod_page' AND contextid = '".$module_context->id."'");

                    foreach($files AS $file)
                    {
                        $this->l('Conversion : Processing file ####'.print_r($file,true).'####');

                        $file->component = 'mod_label';
                        $file->contextid = $module_context->id;
                        $file->filearea = 'intro';
                        $file->pathnamehash = file_storage::get_pathname_hash($file->contextid,$file->component,$file->filearea,$file->itemid,$file->filepath,$file->filename);

                        $this->l('Conversion : Updating file ####'.print_r($file,true).'####');


                        if($DB->get_record('files', array('pathnamehash' => $file->pathnamehash))){
                            continue;
                        }

                        $DB->update_record('files', $file);
                    }

                    $pageContent = $cp->content;
                    $labelContent = $titleHead.$titleDesc.$pageContent;
                    $label->name = mb_substr($labelContent, 0, 250, 'UTF-8');
                    $label->intro = $labelContent;
                    $label->introformat = 1;
                    $label->timemodified = time();
                    $this->l('Conversion : insert new label activity : ####'.print_r($label,true).'####');
                    $labelid = $DB->insert_record('label', $label);


                    $coursemodule->instance = $labelid;
                    $moduleId = $DB->get_record('modules', array('name' => 'label'));
                    $coursemodule->module = $moduleId->id;
                    $this->l('Conversion : update record ####'.print_r($coursemodule,true).'####');
                    $DB->update_record('course_modules', $coursemodule);
                    $DB->delete_records('page',array('id'=>$cp->id));
                }
            }
        }
        // <= CLEO

        
        $this->l('Conversion : ### Update all via activity module to display the description');
        $viamodule = $DB->get_record('modules', array('name'=>'via'));
        $courseVias = $DB->get_records('course_modules',array('course'=>$this->newcourseid,'module'=>$viamodule->id));
        if ($coursePages)
        {
            foreach ($courseVias AS $courseVia)
            {
                $this->l('Conversion : ### Updating via block : '.print_r($courseVia,true));
                $courseVia->showdescription = 1;
                $DB->update_record('course_modules', $courseVia);
            }
        }
        
        
        
        
        $this->l('Conversion : rebuilding course cache for "'.$this->newcourseid.'"');
        rebuild_course_cache($this->newcourseid, true);
    }

    protected function update_sequence_module()
    {
        global $DB;

        // Build section module sequence list
        $this->l('Conversion : ### building course section sequences "'.$this->newcourseid.'"');
        $modules = $DB->get_records('course_modules', array('course'=>$this->newcourseid), 'idnumber*1 ASC');

        if ($modules !== false)
        {
            $this->l('Conversion : Modules list : ####'.print_r($modules,true).'####');
            $sections = $DB->get_records('course_sections',array('course'=>$this->newcourseid),'section ASC');

            $section_modules = array();
            foreach($modules AS $module)
            {
                $section_modules[$module->section][] = $module->id;
            }

            $this->l('Conversion : Modules list per section : ####'.print_r($section_modules,true).'####');

            foreach($section_modules AS $sectionid=>$modulesid)
            {
                $this->l('Conversion : processing section "'.$sectionid.'" : ####'.print_r($modulesid,true).'####');
                $sequence = implode(',',$modulesid);
                if (isset($sections[$sectionid]))
                {
                    if ($sections[$sectionid]->section == 0)
                    {
                        $lastsection = array_values($sections)[count($sections)-1];
                        $sectionid = $lastsection->id;
                        foreach($modulesid as $moduleid)
                        {
                            $modules[$moduleid]->section = $sectionid;
                            $DB->update_record('course_modules', $modules[$moduleid]);
                        }
                    }
                    $sections[$sectionid]->sequence = $sequence;
                    $this->l('updating section with "'.$sectionid.'" : SECTIONID='.$sectionid.' -> SEQUENCE='.$sections[$sectionid]->sequence);
                    $DB->update_record('course_sections', $sections[$sectionid]);
                }
            }

        }
    }

    protected function post_process($newcourseid)
    {
        global $DB;

        $this->l('Conversion : Checking block summary visibility "'.$this->newcourseid.'"');
        $summaries = $DB->get_records('block_instances', array('blockname'=>'summary','parentcontextid'=>$this->newcoursecontext->id));

        foreach($summaries as $summary)
        {
            $sums = $DB->get_records('block_positions', array('blockinstanceid'=>$summary->id,'visible'=>0));

            if (count($sums) == 0)
            {
                $this->l('Conversion : No hidden summary found!');
                continue;
            }

            $this->l('Conversion : Found '.count($sums).' hidden summary!');

            foreach($sums AS $sum)
            {
                $sum->visible = 1;
                $this->l('Conversion : UPDATE "block_positions" record with '.print_r($sum,true).'');
                $DB->update_record('block_positions',$sum);
            }
        }
        
        
        // Get new sections
        $this->sections = $DB->get_records('course_sections',array('course'=>$this->newcourseid),'section ASC');
        
        $this->l('Conversion : ### We hide the teachers specific sections (pages)');
        $hidden_pages = array(
            'page formateur',
            'pages formateur',
            'page formateurs',
            'pages formateurs',
            'page des formateurs',
            'pages des formateurs',
            'page tuteur',
            'pages tuteur',
            'page tuteurs',
            'pages tuteurs',
            'page des tuteurs',
            'pages des tuteurs',
            'page concepteur',
            'pages concepteur',
            'page concepteurs',
            'pages concepteurs',
            'page des concepteurs',
            'pages des concepteurs'
        );
        
        $parentsections = array();
        
        foreach($this->sections AS $section)
        {
            $this->l('Conversion : ### Process section : '.print_r($section,true));
            if (in_array(strtolower($section->name),$hidden_pages))
            {
                $this->hideSectionAndChilds($section->id);
            }
        }
        
        $this->l('Conversion : rebuilding course cache for "'.$this->newcourseid.'"');
        rebuild_course_cache($this->newcourseid, true);
    }
    
    
    private function hideSectionAndChilds($sectionid)
    {
        global $DB;
        
        $section = $DB->get_record('course_sections', array('id' => $sectionid));
        $course = get_course($section->course);
        
        $this->l('Conversion : ### Hidding section : '.print_r($section,true));
        $section->visible = 0;
        $DB->update_record('course_sections', $section);
        
        if ($course->format == 'topics')
        {
            $childs = $DB->get_records('block_summary',array('courseid'=>$course->id,'parentid'=>$section->id));
            
            if (count($childs) > 0)
            {
                foreach ($childs AS $child)
                {
                    $this->hideSectionAndChilds($child->sectionid);
                }
            }
        }
        else if ($course->format == 'modular')
        {
            $childs = $DB->get_records_sql('SELECT * FROM {course_format_options} WHERE courseid = :courseid AND format = :format AND name = :name AND value = :value',array('courseid'=>$course->id,'format'=>'modular','name'=>'parentid','value'=>$section->section));
            
            if (count($childs) > 0)
            {
                foreach ($childs AS $child)
                {
                    $this->hideSectionAndChilds($child->sectionid);
                }
            }
        }
    }

    private function single_forum_migration()
    {
        global $DB;

        $userid = $this->migration->userid;

        $forums = $DB->get_records('forum', array('course' => $this->newcourseid, 'type' => 'single'));
        foreach($forums as $forum)
        {
            $discussion = $DB->get_record('forum_discussions', array('course' => $this->newcourseid, 'forum' => $forum->id));
            $discussion->userid = $userid;
            $DB->update_record('forum_discussions', $discussion);

            $firstpost = $DB->get_record('forum_posts', array('id' => $discussion->firstpost));
            $firstpost->userid = $userid;
            $DB->update_record('forum_posts', $firstpost);
        }
    }
    
    
    function dededup_course_modules($courseid)
    {
        global $DB, $CFG;

        require_once($CFG->dirroot.'/course/lib.php');

        $course = get_course($courseid);
        
        $coursecontext = context_course::instance($courseid);

        $activities = array('folder','label','educationallabel','resource','book','completionmarker','page','imscp','centralizedresources','url');
        
        // Move old flexpagemod block modules to their sections
        $this->l('Conversion : ### Move old flexpagemod block modules to their sections');
        $flexpagemods = $DB->get_records_sql('
SELECT bi.*, bf.id AS "bfid", bf.cmid, bp.id AS bpid, IF(bp.weight IS NULL,bi.defaultweight,bp.weight) AS weight
FROM mdl_block_instances bi
LEFT JOIN mdl_block_flexpagemod bf ON (bf.instanceid=bi.id)
LEFT JOIN mdl_block_positions bp ON (bi.id = bp.blockinstanceid AND bi.parentcontextid = bp.contextid)
WHERE bi.parentcontextid = '.$coursecontext->id.'
AND bi.blockname = "flexpagemod"
');
        if ($flexpagemods !== false)
        {
            
            $mods = array();
            $mods_track = array();
            foreach($flexpagemods AS $fpm)
            {
                $mods[$fpm->cmid][$fpm->id] = true;
                $mods_track[$fpm->cmid][$fpm->id] = 'todo';
            }
            $this->l('Conversion : ### Flexpagemod blocks for each activity');
            $this->l('Mods list : '.print_r($mods_track,true));
            foreach($flexpagemods AS $fpm)
            {
                // Update section ID
                
                /* #2612 - TCS - Bloc sans activité */
                if ($fpm->cmid) {
                    
                    $cm_old = get_coursemodule_from_id('', $fpm->cmid, 0, true, IGNORE_MISSING);
                    if (in_array($cm_old->modname,$activities) && count($mods[$fpm->cmid]) > 1)
                    {
                        try {
                            global $USER;
                            $USER = $DB->get_record('user', array('id'=>2));
                            $this->l('Conversion : More than one flexpagemod block found for module "'.$fpm->cmid.'" in course "'.$courseid.'" : ####'.print_r($mods[$fpm->cmid],true).'####');
                            $course = get_course($courseid);
                            $this->l('Conversion : Module to duplicate "'.$fpm->cmid.'" : ####'.print_r($cm_old,true).'####');
                            $cm_new = duplicate_module($course, $cm_old);
                            $this->l('Conversion : Duplicated module "'.$cm_new->id.'" : ####'.print_r($cm_new,true).'####');
                            $cm = $DB->get_record('course_modules', array('id'=>$cm_new->id));
                            $this->l('Conversion : Removing the block from the list : ####'.print_r($mods,true).'####');
                            
                            $fpmod = $DB->get_record('block_flexpagemod', array('id'=>$fpm->bfid));
                            $fpmod->cmid = $cm->id;
                            $DB->update_record('block_flexpagemod', $fpmod);
                            
                            $bi = $DB->get_record('block_instances', array('id'=>$fpm->id));
                            
                            $data = unserialize(base64_decode($bi->configdata));
                            
                            $data->cmid = $cm->id;
                            
                            $bi->configdata = base64_encode(serialize($data));
                            
                            $DB->update_record('block_instances', $bi);
                            
                            $this->l('Dedup: flexpagemod "'.$fpm->id.'"=>"'.$fpm->bfid.'" succeed');
                            $mods_track[$fpm->cmid][$fpm->id] = 'success';
                            unset($mods[$fpm->cmid][$fpm->id]);
                            
                        } catch (Exception $e) {
                            $this->l('Dedup: flexpagemod "'.$fpm->id.'"=>"'.$fpm->bfid.'" failed');
                            $this->l('Exception: '.print_r($e,true));
                            $mods_track[$fpm->cmid][$fpm->id] = 'failed';
                        }
                        
                    }else{
                        $mods_track[$fpm->cmid][$fpm->id] = 'skiped';
                        continue;
                    }
                }
            }
            $this->l('Result mods list : '.print_r($mods_track,true));
        }
    }
}
