<?php
    require_once('../../config.php');
    
    global $CFG, $DB, $SESSION, $USER;
    
    $courseid = optional_param('course', -1, PARAM_INT);
    $recipients = optional_param('user', '', PARAM_TEXT);
    $refresh = optional_param('refresh', -1, PARAM_INT);
    $action = optional_param('action', '', PARAM_TEXT);
    
    //TCS BEGIN 2015/02/03 - 585
    // remove progress bar cache
    if ($refresh == 1 && $courseid > 0 )
    { 
        if ( isset($SESSION->block_progress_cache) )
        {
            if ( isset($SESSION->block_progress_cache[$USER->id][$courseid]))
            {
                unset($SESSION->block_progress_cache[$USER->id][$courseid]);
                die('done');
            }
            die('nocoursecache');
        }
        die('nocache');
    }
    //TCS END 2015/02/03 - 585
    
    else if ( $action == 'submit_termine' )
    {
        $fusers = required_param('users', PARAM_TEXT);
        $courseid2 = required_param('courseid', PARAM_INT);
        $id = required_param('id', PARAM_INT);
        
        $users = explode(',', $fusers);
        
        foreach ($users as $userid) {
            $record              = new stdClass();
            $record->courseid    = $courseid2;
            $record->userid      = $userid;
            $record->is_complete = 1;
            $is_complete         = $DB->get_record('progress_complete', array('courseid' => $courseid2, 'userid' => $userid));
            if (!$is_complete) {
                $DB->insert_record('progress_complete', $record, false);
            }
        }
        
        //header('Location: ' . $CFG->wwwroot . '/blocks/progress/overview.php?id='.$id.'&courseid=' . $courseid);
        //die;
        
    }
    
    // selection du type de formulaire 
    else if ($courseid != -1 && $recipients != "")
    {        
        $url = new moodle_url('/local/mail/create.php', array('c' => $courseid, 'rs' => $recipients));
        echo $url;
    }
?>
