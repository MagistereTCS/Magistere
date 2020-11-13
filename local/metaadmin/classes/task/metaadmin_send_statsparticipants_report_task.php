<?php
namespace local_metaadmin\task;

class metaadmin_send_statsparticipants_report_task extends \core\task\scheduled_task
{      
    public function get_name() 
    {
        // Shown in admin screens
        return "Metaadmin Send StatsParticipants Report Task";
    }
                                                                     
    public function execute() 
    {
    	global $CFG;
    	require_once($CFG->dirroot.'/local/metaadmin/lib.php');
    	send_statsparticipants_report(time());
    }                                                                                                                               
} 