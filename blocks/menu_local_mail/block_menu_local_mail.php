<?php

require_once($CFG->dirroot.'/blocks/menu_local_mail/lib.php');

class block_menu_local_mail extends block_base {

	protected $blockdisplay = TRUE;

	//initiation
    public function init() 
	{			
		$this->title = 'Menu messagerie';
    }		
	
	public function user_can_addto($page)
    {
        global $USER;
        return is_siteadmin($USER);
    }

    //override de get_content_for_output afin de cacher les blocs vides.
	public function get_content_for_output($output) {
        global $CFG;

        $bc = new block_contents($this->html_attributes());

        $bc->blockinstanceid = $this->instance->id;
        $bc->blockpositionid = $this->instance->blockpositionid;

        if ($this->instance->visible) {
            $bc->content = $this->formatted_contents($output);
            if (!empty($this->content->footer)) {
                $bc->footer = $this->content->footer;
            }
        } else {
            $bc->add_class('invisible');
        }

        if (!$this->hide_header()) {
            $bc->title = $this->title;
        }

        if ($this->page->user_is_editing()) {
			if($this->blockdisplay || is_page_site())
			{
				$bc->controls = $this->page->blocks->edit_controls($this);
			}
        } else {
            // we must not use is_empty on hidden blocks
            if ($this->is_empty() && !$bc->controls) {
                return null;
            }
        }

        if (empty($CFG->allowuserblockhiding)
                || (empty($bc->content) && empty($bc->footer))
                || !$this->instance_can_be_collapsed()) {
            $bc->collapsible = block_contents::NOT_HIDEABLE;
        } else if (get_user_preferences('block' . $bc->blockinstanceid . 'hidden', false)) {
            $bc->collapsible = block_contents::HIDDEN;
        } else {
            $bc->collapsible = block_contents::VISIBLE;
        }
        $bc->annotation = ''; 

		if($this->blockdisplay || is_page_site())
		{		
			return $bc;
		}	
    }	
	
	
	public function get_content(){
		$this->content         =  new stdClass;	
		if (display_block()){
			$this->content->text   =  menu_mail_content();		
		}
		elseif(is_page_site()){
			$this->title = 'Paramétrage menu messagerie';
			$this->blockdisplay = FALSE;
		}
		else{
			$this->title = '';
			$this->blockdisplay = FALSE;
		}
		
		$this->content->footer = '';		
		return $this->content;	
	}
}
?>