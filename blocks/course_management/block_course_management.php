<?php

require_once($CFG->dirroot . '/blocks/course_management/lib/block_lib.php');

class block_course_management extends block_base {

	protected $links_type;
	protected $coursecat;

	//initiation
    public function init(){
		$this->title = 'Gestion des parcours';
    }

	// fonction overrider de localisation
	function applicable_formats() {
        return array('all' => false, 'site' => true, 'course-view' => true, 'mod' => false, 'admin' => false);
    }

	function instance_allow_multiple() {
		return false;
	}
	
	function is_empty() {
		// get the main category
		$this->coursecat = get_course_main_category();
		//définition des types de lien à afficher
		$this->links_type = get_action_links($this->coursecat, $this->context);
		if(empty($this->links_type)){
			return true;
		}
		return false;
	}
	
	//	fonction d'affichage du contenu
	public function get_content(){
		if ($this->content !== null){
			return $this->content;
		}
		
		global $PAGE;
		
		$PAGE->requires->jquery();
		$PAGE->requires->jquery_plugin('ui');
		$PAGE->requires->jquery_plugin('ui-css');

        $PAGE->requires->js_call_amd('block_course_management/dialog', 'init');
        $PAGE->requires->js_call_amd('block_course_management/datepicker', 'init');
		
		// get the main category
		$this->coursecat = get_course_main_category();
		//définition des types de lien à afficher
		$this->links_type = get_action_links($this->coursecat, $this->context);
		
		//création d'un contenu vide
		$this->content         =  new stdClass;
		$this->content->text   =  action_content($this->links_type, $this->coursecat, $this->context->instanceid );
		
		return $this->content;
	}
}