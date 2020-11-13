<?php
namespace local_magisterelib\task;

class cpforum_sync_task extends \core\task\scheduled_task 
{      
    public function get_name() 
    {
        // Shown in admin screens
        return "CP Forum Synchronisation Task";
    }
                                                                     
    public function execute() 
    {   
    	global $CFG;
    	require_once($CFG->dirroot.'/local/magisterelib/CPForumSync.php');
    	$forumSync = new \CPForumSync();
    	$forumSync->execute();
    }                                                                                                                               
} 