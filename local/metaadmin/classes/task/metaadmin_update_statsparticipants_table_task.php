<?php
namespace local_metaadmin\task;

class metaadmin_update_statsparticipants_table_task extends \core\task\scheduled_task 
{      
    public function get_name() 
    {
        // Shown in admin screens
        return "Metaadmin Update StatsParticipants Task";
    }
                                                                     
    public function execute() 
    {
    	global $CFG;
    	require_once($CFG->dirroot.'/local/metaadmin/lib.php');
    	update_metaadmin_table(time());
    }                                                                                                                               
} 