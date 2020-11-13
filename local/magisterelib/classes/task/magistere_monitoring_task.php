<?php
namespace local_magisterelib\task;

class magistere_monitoring_task extends \core\task\scheduled_task 
{      
    public function get_name() 
    {
        // Shown in admin screens
        return "Magistere Monitoring Task";
    }
                                                                     
    public function execute()
    {
    	global $CFG;
    	
    	if ($CFG->academie_name == 'dgesco')
    	{
    	    require_once($CFG->dirroot.'/local/magisterelib/MagistereMonitoring.php');
    	    $magistereMonitoring = new \MagistereMonitoring();
    	    $magistereMonitoring->execute();
    	}else{
    	    echo '';
    	}
    }
}