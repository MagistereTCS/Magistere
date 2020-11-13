<?php

global $CFG; // Required, do not remove
require_once($CFG->dirroot.'/local/magisterelib/databaseConnection.php');

class MagistereLib
{

    private function __construct(){}

    static function set_remote_user_preference($name,$value,$username = null){
        global $USER, $DB, $CFG;
        $userid = $USER->id;
        if ( $username == null){
            $username = $USER->username;
        }

        $user_is_manual = false;
        if($USER->id > 0) {
            if($USER->auth == 'manual'){
                $user_is_manual = true;
            }

        } else {
            if($username){
                $user = $DB->get_record('user', array("username" => $username));
                if($user){
                    $userid = $user->id;
                    if($user->auth == 'manual'){
                        $user_is_manual = true;
                    }
                }
            }
        }

        if($user_is_manual){
            $mainaca = $CFG->academie_name;
        } else {
            $mainaca = user_get_mainacademy($userid);
        }

        $academies = get_magistere_academy_config();

        $remoteuser = false;
        if ($mainaca !== false && array_key_exists($mainaca,$academies)) {
            $remoteuser = databaseConnection::instance()->get($mainaca)->get_record('user', array('username'=>$username));
        }
        
        if( !$mainaca || !array_key_exists($mainaca,$academies) || $remoteuser == false){
            return set_user_preference($name,$value);
        }
        
        if ($remoteuser !== false) {
            $exist_user_p = databaseConnection::instance()->get($mainaca)->record_exists("user_preferences",array("name" => $name,"userid" => $remoteuser->id));

            if($exist_user_p){
                databaseConnection::instance()->get($mainaca)->set_field("user_preferences","value",$value,array("name" => $name,"userid" => $remoteuser->id));
            }else{
                $up =  new stdClass();
                $up->userid = $remoteuser->id;
                $up->name = $name;
                $up->value = $value;
                databaseConnection::instance()->get($mainaca)->insert_record("user_preferences",$up);
            }
        }
    }
    
    static function get_remote_user_preference($name, $username = null){
        global $USER, $DB, $CFG;
        $userid = $USER->id;
        if ( $username == null){
            $username = $USER->username;
        }

        $user_is_manual = false;
        if($USER->id > 0) {
            if($USER->auth == 'manual'){
                $user_is_manual = true;
            }

        } else {
            if($username){
                $user = $DB->get_record('user', array("username" => $username));
                if($user){
                    $userid = $user->id;
                    if($user->auth == 'manual'){
                        $user_is_manual = true;
                    }
                }
            }
        }

        if($user_is_manual){
            $mainaca = $CFG->academie_name;
        } else {
            $mainaca = user_get_mainacademy($userid);
        }

        $academies = get_magistere_academy_config();
        
        $remoteuser = false;
        if ($mainaca !== false && array_key_exists($mainaca,$academies)) {
            $remoteuser = databaseConnection::instance()->get($mainaca)->get_record('user', array('username'=>$username));
        }
        
        if(!$mainaca || !array_key_exists($mainaca,$academies) || $remoteuser === false){
            return get_user_preferences($name);
        }

        return databaseConnection::instance()->get($mainaca)->get_field("user_preferences","value",array("userid" => $remoteuser->id,"name" => $name));
    }

    static function unset_user_preference($name, $user = null) {
        global $USER, $DB;

        if (empty($name) or is_numeric($name) or $name === '_lastloaded') {
            throw new coding_exception('Invalid preference name in unset_user_preference() call');
        }

        if (is_null($user)) {
            $user = $USER;
        } else if (isset($user->id)) {
            // It is a valid object.
        } else if (is_numeric($user)) {
            $user = (object)array('id' => (int)$user);
        } else {
            throw new coding_exception('Invalid $user parameter in unset_user_preference() call');
        }

        // Delete from DB.
        $DB->delete_records('user_preferences', array('userid' => $user->id, 'name' => $name));

        // Delete the preference from cache.
        unset($user->preference[$name]);
        // Update the $USER in case where we've not a direct reference to $USER.
        if ($user !== $USER && $user->id == $USER->id) {
            unset($USER->preference[$name]);
        }

        return true;
    }
    
    static function update_course_modified($echolog = true, $courseid = null) {
        
        global $DB;
        
        $modulesavailable = $DB->get_records_sql('SELECT id, name FROM {modules} WHERE visible=1');
        $selecttimemodified = [];
        foreach($modulesavailable as $m) {
            $selecttimemodified[] = 'IFNULL((SELECT timemodified FROM {'.$m->name.'} WHERE id=cm.instance AND cm.module='.$m->id.'), 0)';
        }
        
        $sqltmmod = '(SELECT MAX(GREATEST('.implode(",\n ", $selecttimemodified).'))
FROM {course_modules} cm
WHERE cm.course = c.id
GROUP BY cm.course)';
        
        // this part will retrieve the last modification on each blocks for each course
        $sqltmblocks = '(
	SELECT MAX(bi.timemodified) timemodified
	FROM {context} ctx2
	INNER JOIN {block_instances} bi ON bi.id=ctx2.instanceid
	WHERE ctx2.contextlevel=80 AND ctx2.path LIKE CONCAT(ctxcourse.path, \'%\')
	GROUP BY ctx2.contextlevel
)';
        
        $coursepublishjoin = '';
        $courseidwhere = '';
        if ($courseid == null) {
            $coursepublishjoin = 'INNER JOIN {local_coursehub_published} lcp ON (lcp.courseid = c.id)';
        }else{
            $courseidwhere = ' AND c.id = '.$courseid;
        }
        
        $sql = 'SELECT c.id, GREATEST('
            .$sqltmblocks.', '.$sqltmmod.') timemodified
FROM {course} c
'.$coursepublishjoin.'
INNER JOIN {context} ctxcourse ON ctxcourse.instanceid=c.id
WHERE ctxcourse.contextlevel=50 AND c.id > 1'.$courseidwhere.'
GROUP BY c.id';
            
        $stime = time();
        if ($echolog) {
            echo 'COMPUTE DATA '.date('H:i:s', time())."\n";
        }
        
        $records = $DB->get_records_sql($sql);
        $eltime = (time() - $stime);
        
        if ($echolog) {
            echo 'END COMPUTE DATA ('.$eltime.'s)'.date('H:i:s', time())."\n";
        }
        
        $stime = time();
        if ($echolog) {
            echo 'START UPDATE DATA '.date('H:i:s', time())."\n";
        }
        foreach($records as $record){
            if($record->timemodified === null){
                continue;
            }
            
            $DB->update_record('course', $record);
        }
        $eltime = (time() - $stime);
        if ($echolog) {
            echo 'END UPDATE DATA ('.$eltime.'s)'.date('H:i:s', time())."\n";
        }
    }
    
    static function hasCourseBeenUpdated($courseid)
    {
        global $CFG, $DB;
        
        $course = $DB->get_record('course', array('id'=>$courseid));
        
        if ($course === false ) {
            return false;
        }
        
        $hub = CourseHub::instance();
        
        $published = $hub->getPublishedCourse($CFG->academie_name,$courseid,CourseHub::PUBLISH_PUBLISHED);
        $shared = $hub->getPublishedCourse($CFG->academie_name,$courseid,CourseHub::PUBLISH_SHARED);
        
        $publish = null;
        if ($published !== false) {
            $publish = $published;
        }else if ($shared !== false) {
            $publish = $shared;
        }else{
            return false;
        }
        
        return ($course->timemodified != $publish->timecoursemodified);
    }
    
    static function hasIndexBeenUpdated($courseid)
    {
        global $CFG, $DB;
        
        $course = $DB->get_record('course', array('id'=>$courseid));
        
        if ($course === false ) {
            return false;
        }
        
        $index = $DB->get_record('local_indexation', array('courseid'=>$courseid));
        
        $hub = CourseHub::instance();
        
        $published = $hub->getPublishedCourseIndexation($CFG->academie_name,$courseid,CourseHub::PUBLISH_PUBLISHED);
        $shared = $hub->getPublishedCourseIndexation($CFG->academie_name,$courseid,CourseHub::PUBLISH_SHARED);
        
        $publish = null;
        if ($published !== false) {
            $publish = $published;
        }else if ($shared !== false) {
            $publish = $shared;
        }else{
            return false;
        }
        
        return ($index->updatedate != $publish->updatedate);
    }
}
