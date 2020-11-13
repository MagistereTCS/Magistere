<?php
namespace local_magisterelib\task;

class indexation_stats_task extends \core\task\scheduled_task 
{      
    public function get_name() 
    {
        // Shown in admin screens
        return "Indexation Stats Task";
    }
                                                                     
    public function execute() 
    {   
    	global $CFG;
    	require_once($CFG->dirroot.'/local/magisterelib/updateStatsIndexationData.php');
    	$statsindexation = new \StatsIndexation();
    	$statsindexation->execute();
    }                                                                                                                               
} 