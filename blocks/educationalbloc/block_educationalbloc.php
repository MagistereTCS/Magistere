<?php
 require_once($CFG->dirroot . '/blocks/educationalbloc/localib.php');
 
class block_educationalbloc extends block_base {

	public $block_user_content;
	public $current_type;
	
    function init() {
        $this->title = get_string('pluginname', 'block_educationalbloc');
    }

    function applicable_formats() {
        return array('all' => true);
    }

    function specialization() {
		
			//on recupere le type du bloc
		if(!empty($this->config->selecttype))
		{
			$this->current_type = $this->config->selecttype;
		}
		else
		{
			$this->current_type = false;
		}
		
		$this->title = $this->get_block_type_name();
    }

    function instance_allow_multiple() {
        return true;
    }

    function get_content() {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');

		$instanceid = $this->instance->id;
		if (is_first_instance_using($instanceid)){
			first_configuration($instanceid);			
		}
		
        if ($this->content !== NULL) {
            return $this->content;
        }

        $filteropt = new stdClass;
        $filteropt->overflowdiv = true;
        if ($this->content_is_trusted()) {
            // fancy educationalbloc allowed only on course, category and system blocks.
            $filteropt->noclean = true;
        }

        $this->content = new stdClass;
        $this->content->footer = '';
        if (isset($this->config->text)) {
            // rewrite url
            $this->config->text = file_rewrite_pluginfile_urls($this->config->text, 'pluginfile.php', $this->context->id, 'block_educationalbloc', 'content', NULL);
            // Default to FORMAT_educationalbloc which is what will have been used before the
            // editor was properly implemented for the block.
            $format = FORMAT_HTML;
            // Check to see if the format has been properly set on the config
            if (isset($this->config->format)) {
                $format = $this->config->format;
            }
            $this->content->text = format_text($this->config->text, $format, $filteropt);
        } else {
            $this->content->text = '';
        }
	
        unset($filteropt); // memory footprint

        return $this->content;
    }

	public function html_attributes() {
		global $USER;
		$attributes = parent::html_attributes(); // Get default values
		$attributes['class'] .= ' block_'. $this->name().'_'.$this->current_type;

		//si l'utilisateur connecté n'a pas le role correspondant au type de bloc, on n'affiche rien
		if($this->current_type){
			if($this->current_type=='type1'){
				has_capability('block/educationalbloc:presentationblockview', $this->context, $USER->id, TRUE)?$can_view_block=true:$can_view_block=false;
			}elseif($this->current_type=='type2'){
				has_capability('block/educationalbloc:succedblockview', $this->context, $USER->id, TRUE)?$can_view_block=true:$can_view_block=false;
			}elseif($this->current_type=='type3'){
				has_capability('block/educationalbloc:activityblockview', $this->context, $USER->id, TRUE)?$can_view_block=true:$can_view_block=false;
			}elseif($this->current_type=='type4'){
				has_capability('block/educationalbloc:noteblockview', $this->context, $USER->id, TRUE)?$can_view_block=true:$can_view_block=false;
			}elseif($this->current_type=='type5'){
				has_capability('block/educationalbloc:importantblockview', $this->context, $USER->id, TRUE)?$can_view_block=true:$can_view_block=false;
			}else{
				//le type n'est pas reconnu, le bloc ne doit pas s'afficher
				$can_view_block=false;
			}			
			if(!$can_view_block){
				//on vide le bloc
				unset($this->config->text);
				// unset($this->block_user_content['text']);
				$this->title = '';
				$attributes['class'] .= ' display_none_educationalbloc';
			}
		}
		return $attributes;
	}
	

    /**
     * Serialize and store config data
     */
    function instance_config_save($data, $nolongerused = false) {
        global $DB;

        $config = clone($data);
        // Move embedded files into a proper filearea and adjust educationalbloc links to match
        $config->text = file_save_draft_area_files($data->text['itemid'], $this->context->id, 'block_educationalbloc', 'content', 0, array('subdirs'=>true), $data->text['text']);
        $config->format = $data->text['format'];

        parent::instance_config_save($config, $nolongerused);
    }

    function instance_delete() {
        global $DB;
        $fs = get_file_storage();
        $fs->delete_area_files($this->context->id, 'block_educationalbloc');
        return true;
    }

    function content_is_trusted() {
        global $SCRIPT;

        if (!$context = context::instance_by_id($this->instance->parentcontextid)) {
            return false;
        }
        //find out if this block is on the profile page
        if ($context->contextlevel == CONTEXT_USER) {
            if ($SCRIPT === '/my/index.php') {
                // this is exception - page is completely private, nobody else may see content there
                // that is why we allow JS here
                return true;
            } else {
                // no JS on public personal pages, it would be a big security issue
                return false;
            }
        }

        return true;
    }

    /**
     * The block should only be dockable when the title of the block is not empty
     * and when parent allows docking.
     *
     * @return bool
     */
    public function instance_can_be_docked() {
        return (!empty($this->config->title) && parent::instance_can_be_docked());
    }
	
	
	public function get_block_type_name()	{
		if($this->current_type != '')
		{
			$conf_content = parse_ini_file('conf.ini', true);
			$types_names = array();

			foreach($conf_content['types_names'] as $key => $value)
			{
				$types_names[$key] = $value;
			}

			return $types_names[$this->current_type];
		}
		else
		{
			return 'Pedagogique';
		}
	}
}
