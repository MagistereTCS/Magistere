<?php //$Id: block_rolespecifichtml.php,v 1.2 2012-07-10 16:42:00 vf Exp $

class block_rolespecifichtml extends block_base {

    function init() {
        $this->title = get_string('pluginname', 'block_rolespecifichtml');
    }

    function applicable_formats() {
        return array('all' => true, 'admin' => false);
    }

    function specialization() {
        $this->title = isset($this->config->title) ? format_string($this->config->title) : format_string(get_string('newhtmlblock', 'block_rolespecifichtml'));
    }

    function instance_allow_multiple() {
        return true;
    }

    function get_content() {
    	global $COURSE, $CFG, $USER;
    	
        if ($this->content !== NULL) {
            return $this->content;
        }

        $filteropt = new stdClass;
        $filteropt->overflowdiv = true;
        if ($this->content_is_trusted()) {
            // fancy html allowed only on course, category and system blocks.
            $filteropt->noclean = true;
        }
                        
        $this->content = new stdClass;

        $this->content->text = '';
        
        $context = context_course::instance($COURSE->id);
        if (is_role_switched($COURSE->id)){
        	$roleid = $USER->access['rsw'][$context->path]; 
        } else if (!empty($userroles)){
	        if ($userroles = get_user_roles($context, $USER->id, false)){
		        $roleid = array_pop(array_keys($userroles));
		    }
	    }
	    
		if ($this->config){
	        $this->content = new stdClass;
	        $tk = "text_all";
	        $this->config->$tk = file_rewrite_pluginfile_urls($this->config->$tk, 'pluginfile.php', $this->context->id, 'block_rolespecifichtml', 'content', NULL);
	        $this->content->text .= !empty($this->config->$tk) ? format_text($this->config->$tk, FORMAT_HTML, $filteropt) : '';
	        $tk = "text_{$roleid}";
	 		$this->config->$tk = file_rewrite_pluginfile_urls($this->config->$tk, 'pluginfile.php', $this->context->id, 'block_rolespecifichtml', $tk, NULL);
	        $this->content->text .= isset($this->config->$tk) ? format_text($this->config->$tk, FORMAT_HTML, $filteropt) : '';
	    }
        $this->content->footer = '';
        
        unset($filteropt); // memory footprint
        
        if (empty($this->content->text)) $this->content->text = '&nbsp;';

        return $this->content;
    }

    /**
     * Serialize and store config data
     */
    function instance_config_save($data, $nolongerused = false) {
        global $DB, $COURSE;

        $config = clone($data);
        // Move embedded files into a proper filearea and adjust HTML links to match
        $config->text_all = file_save_draft_area_files($data->text_all['itemid'], $this->context->id, 'block_rolespecifichtml', 'content', 0, array('subdirs' => true), $data->text_all['text']);
        $config->format_all = $data->text_all['format'];

		$roles = $this->get_usable_roles();
		if (!empty($roles)){
			foreach($roles as $r){
				$textkey = 'text_'.$r->id;
				$formatkey = 'format_'.$r->id;
	        	$config->{$textkey} = file_save_draft_area_files($data->{$textkey}['itemid'], $this->context->id, 'block_rolespecifichtml', $textkey, 0, array('subdirs' => true), $data->{$textkey}['text']);
	        	$config->{$formatkey} = $data->{$textkey}['format'];
	        }
		}

        parent::instance_config_save($config, $nolongerused);
    }

    function instance_delete() {
        global $DB;
        $fs = get_file_storage();
        $fs->delete_area_files($this->context->id, 'block_rolespecifichtml');
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

    /*
     * Hide the title bar when none set..
     */
    function hide_header(){
        return empty($this->config->title);
    }

	/** 
	* get highest role in course context
	*/
    function get_highest_role(){
    	global $COURSE, $USER;
    	
    	$context = context_course::instance($COURSE->id);
    	if ($roles = get_user_roles($context, $USER->id, false)){
    		$highest = next($roles);
    		return $highest->id;
    	}
    	return 0;
    }

    function get_usable_roles(){
    	global $DB;
    	
		$sql = "
			SELECT DISTINCT
				r.*
			FROM
				{role} r,
				{role_context_levels} rcl
			WHERE
				r.id = rcl.roleid AND
				contextlevel >= 50
		";
	    $roles = $DB->get_records_sql($sql);
	    
	    return $roles;
    }
}
?>
