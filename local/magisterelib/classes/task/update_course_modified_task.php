<?php
namespace local_magisterelib\task;

class update_course_modified_task extends \core\task\scheduled_task
{      
    public function get_name() 
    {
        // Shown in admin screens
        return "Magistere Update Modified attribute of the course";
    }
                                                                     
    public function execute()
    {
        require_once($GLOBALS['CFG']->dirroot.'/local/magisterelib/magistereLib.php');
        
        \MagistereLib::update_course_modified();
    }
}