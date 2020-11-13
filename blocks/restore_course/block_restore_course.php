<?php


class block_restore_course extends block_base {
    	
		protected $blocktype;
		protected $blockdisplay = TRUE;

    public function init() {
        $this->title = get_string('plugintitle', 'block_restore_course');
	}
	
	//permet de n'avoir qu'une instance du bloc par page
	function instance_allow_multiple() {
		return false;
	}
	
	//override de get_content
	public function get_content() {
		global $CFG, $USER;
        $download = optional_param('download', 0, PARAM_INT);
        $backupsize = optional_param('backupsize', 0, PARAM_INT);
        // OLE : Uniquement pour les utilisateurs qui peuvent restaurer un cours.
        $context = context_system::instance();
        $usercanrestore = has_capability('moodle/restore:restorecourse', $context);
        if($usercanrestore){
            if(!$download){

                if ($this->content !== null) {
                    return $this->content;
                }

                $url = $CFG->wwwroot.'/blocks/community/communitycourse.php?sesskey='.$USER->sesskey.'&download=1&confirmed=1&courseid=1&downloadcourseid='.$_GET['id'].'&huburl='.urlencode($CFG->hubserver_url).'&backupsize='.$backupsize;
                $this->content         =  new stdClass;
                $this->content->text   = '<a href="'.$url.'">Cliquez ici pour restaurer ce cours</a>';

                return $this->content;
            }

        }
	}
}