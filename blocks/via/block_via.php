<?php 

/**
 * via Live! block.
 *
 * Allows students to manage their user information on the via Live!
 * server from Moodle and admins/teachers to add students and other users
 * to a remote via Live! server.
 *
 * @copyright 2011 - 2013 SVIeSolutions 
 */


class block_via extends block_list {

    function init() {
        $this->title   = get_string('modulename', 'via');
    }

    function get_content() {
        global $CFG, $USER, $COURSE, $DB;
        

        require_once($CFG->dirroot . '/mod/via/lib.php');

        if ($this->content !== NULL) {
            return $this->content;
        }
        $this->content        = new stdClass;
        $this->content->items = array();
        $this->content->icons = array();
        $this->content->footer = '';

        if (!isloggedin() || empty($this->instance)) {
            return $this->content;
        }
            
        $context = context_course::instance($COURSE->id);
        
        if (has_capability('mod/via:manage', $context)){
            
            if ($vias = get_all_instances_in_course('via', $COURSE)){
                    
                $recordingavailable = false;
                
                $this->content->items[] = '<div class="heading"><b>'.get_string("recentrecordings", "block_via").'</b></div>';
                $this->content->icons[] = '';
    
                    foreach ($vias as $via) {
                    
                        $playbacks = $DB->get_records_sql('select * from {via_playbacks} WHERE activityid = ' . $via->id . ' ORDER BY creationdate asc');
                        
                        if ($playbacks){
                            
                            foreach($playbacks as $playback){
                                
                            if (($via->recordingmode == 1 && ($via->isreplayallowed || $playback->accesstype > 0) && (($via->datebegin + (60 * $via->duration)) < time())) || ($via->recordingmode == 2 && ($via->isreplayallowed || $playback->accesstype > 0)))
                                {
                                if ($playback->accesstype > 0 || (has_capability('mod/via:manage', $context) && via_get_is_user_host($USER->id, $via->id)))
                                    {
                                        
                                        $private = ($playback->accesstype == 0 || !$via->visible)? "dimmed_text" : "";
                                        if ($private){
                                            $param = '&p=1';
                                        }else{
                                            $param = '';    
                                        }
                                            
                                        $link = '<span class="event '.$private.'">';
                                        $link .= '<img src="' . $CFG->wwwroot . '/mod/via/pix/recording_grey.png" width="25" height="25" alt="'.get_string('recentrecordings', 'block_via') . '" style="float:left; margin-bottom:10px;" />';
                                        $link .= '<a href="' . $CFG->wwwroot . '/mod/via/view.via.php?id='.$via->coursemodule.'&review=1&playbackid='.$playback->playbackid.$param.'" target="new">';
                                        $link .= $via->name." (".$playback->title . ')';
                                        $link .= '</a>';
                                                
                                        $link .= ' <div class="date dimmed_text" style="padding-left:22px; margin-bottom:10px">('.userdate($playback->creationdate).')</div></span>';
                                            
                                        $this->content->items[] = $link;
                                        $this->content->icons[] = '';
                                    
                                        $recordingavailable = true;
                                    }
                                }
                            }
                        }
                        
                        
                    }
                
                if (!$recordingavailable){
                    $this->content->items[] = '<div class="event dimmed_text"><i>'.get_string("norecording", "block_via").'</i></div>';
                    $this->content->icons[] = '';                
                }
        }
        
        if (has_capability('mod/via:view', $context)){
                
                $this->content->items[] = '<hr>';
                $this->content->icons[] = '';
                
                $this->content->items[] = '<span class="event" style="white-space:nowrap"><img src="' . $CFG->wwwroot . '/mod/via/pix/config_grey.png" ' . 'width="20" height="20" alt="' . get_string('recentrecordings', 'block_via') . ' style="float:left"" /><a target="configvia" href="' . $CFG->wwwroot . '/mod/via/view.assistant.php?redirect=7" onclick="this.target=\'configvia\'; return openpopup(null, {url:\'/mod/via/view.assistant.php?redirect=7\', name:\'configvia\', options:\'menubar=0,location=0,scrollbars,resizable,width=680,height=500\'});">' .
                get_string("configassist", "block_via").'</a></span>';
                $this->content->icons[] = '';
                        
                if (get_config('via', 'via_technicalassist_url') == null) {
                    $this->content->items[] = '<span class="event"><img src="' . $CFG->wwwroot . '/mod/via/pix/assistance_grey.png" ' . 'width="20" height="20" alt="' . get_string('recentrecordings', 'block_via') . ' style="float:left"" /><a target="configvia" href="' . $CFG->wwwroot . '/mod/via/view.assistant.php?redirect=6" onclick="this.target=\'configvia\'; return openpopup(null, {url:\'/mod/via/view.assistant.php?redirect=6\', name:\'configvia\', options:\'menubar=0,location=0,scrollbars,resizable,width=650,height=400\'});">' .
                        get_string("technicalassist", "block_via").'</a></span>';
                } else {
                    $this->content->items[] = '<span class="event"><img src="' . $CFG->wwwroot . '/mod/via/pix/assistance_grey.png" ' . 'width="20" height="20" alt="' . get_string('recentrecordings', 'block_via') . ' style="float:left"" /><a target="configvia" href="'.get_config('via', 'via_technicalassist_url').'?redirect=6" onclick="this.target=\'configvia\'; return openpopup(null, {url:\''.get_config('via', 'via_technicalassist_url').'?redirect=6\', name:\'configvia\', options:\'menubar=0,location=0,scrollbars,resizable,width=650,height=400\'});">' .
                        get_string("technicalassist", "block_via").'</a></span>';
                }
                
                $this->content->icons[] = '';
                
            }
    }

        return $this->content;
    }
    
    function applicable_formats() {
        return array('site' => false, 'course' => true);
    }

}

?>
