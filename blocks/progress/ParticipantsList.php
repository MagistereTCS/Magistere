<?php
require_once($CFG->dirroot . '/blocks/progress/lib.php');
require_once($CFG->dirroot . '/local/gaia/lib/GaiaUtils.php');

class ParticipantList {
    /*
     * FILTERS
     */
    private $roleid;
    private $groupid;
    private $names;
    private $is_realized;
    private $activity;
    private $neverConnected;
    private $contextid;
    private $courseid;
    private $id;
    private $gaia_sessions;
    private $other_gaia_session;
    private $data_for;

    private $si;
    private $so;
    private $ps;

    private $result_count;
    private $sql_request; // raw sql request
    private $result_sql; // raw data from sql
    private $data; // data after processing

    private $config;
    private $modules;
    private $events;
    private $users_attempts;

    const DATA_FOR_PDF = 1;
    const DATA_FOR_JTABLE = 2;

    public function __construct(){
        $this->id = null;
        $this->roleid = null;
        $this->group = null;
        $this->names = array();
        $this->is_realized = 0;
        $this->neverConnected = false;
        $this->activity = array();
        $this->contextid = null;
        $this->courseid = null;
        $this->contextid = null;
        $this->other_gaia_session = false;
        $this->gaia_sessions = array();
        $this->users_attempts = array();

        $this->si = null;
        $this->so = null;
        $this->ps = null;

        $this->result_count = null;
        $this->result_sql = null;
        $this->sql_request = '';
        $this->data = null;

        $this->config = null;
        $this->modules = null;
        $this->events = null;

        $this->data_for = ParticipantList::DATA_FOR_JTABLE;
    }

    public function setContextId($contextid){
        $this->contextid = $contextid;
    }

    public function setCourseid($courseid){
        $this->courseid = $courseid;
    }

    public function setRoleId($roleid){
        $this->roleid = $roleid;
    }

    public function setGroupId($groupid){
        $this->groupid = $groupid;
    }

    public function addName($name){
        $this->names[] = $name;
    }

    public function setIsRealized(){
        $this->is_realized = 1;
    }

    public function setActivity($activityname, $activityid){
        $this->activity = array(
            array('name' => $activityname, 'id' => $activityid)
        );
    }

    public function setIsNeverConnected(){
        $this->neverConnected = true;
    }

    public function setStartIndex($si){
        $this->si = $si;
    }

    public function setSortOrder($so){
        $this->so = $so;
    }

    public function setPageSize($ps){
        $this->ps = $ps;
    }

    public function setId($id){
        $this->id = $id;
    }

    public function addGaiaSession($sid, $did, $mid){
        $this->gaia_sessions[] = array(
            'sid' => $sid,
            'did' => $did,
            'mid' => $mid
        );
    }

    public function setGaiaSession($sid, $did, $mid){
        $this->gaia_sessions = array(
            array(
                'sid' => $sid,
                'did' => $did,
                'mid' => $mid
            )
        );
    }

    public function resetGaiaSession(){
        $this->gaia_sessions = array();
    }

    public function setOtherGaiaSession(){
        $this->other_gaia_session = true;
    }

    public function setDataFor($for){
        $this->data_for = $for;
    }

    public function getData(){
        $this->buildSQL();
        $this->executeSQL();


        if($this->data_for == ParticipantList::DATA_FOR_JTABLE){
            $this->processData();
        }else{
            return $this->result_sql;
        }

        return $this->data;
    }

	public function getOtherFormateurs(){
        global $DB;

        return $DB->get_records_sql('SELECT DISTINCT(u.id), u.firstname, u.lastname, u.email
FROM {enrol} e
INNER JOIN {user_enrolments} ue ON ue.enrolid = e.id
INNER JOIN {user} u ON u.id = ue.userid
INNER JOIN {role_assignments} ra ON ra.userid = ue.userid
WHERE (
e.name IS NULL OR e.name NOT LIKE "GAIA%" 
) AND e.courseid = '.$this->courseid.' AND ra.contextid='.$this->contextid.' AND ra.roleid = (
    SELECT id FROM {role} r WHERE r.shortname="formateur"
 )');
    }
	
    public function getCountData(){
        return $this->result_count;
    }

    public function getGeneratedSQL(){
        return $this->sql_request;
    }

    protected function buildSQL(){
        // ROLE
        $role_where = '';
        if($this->roleid != null) {
            $role_where = 'AND a.roleid = '.$this->roleid;
        }

        // GROUP
        $group_from = '';
        $group_where = '';
        if($this->groupid != null){
            $group_from .= 'INNER JOIN {groups_members} gm ON (gm.userid = u.id)';
            $group_where  .= ' AND gm.groupid = '.$this->groupid;
        }

        // NAMES
        $name_where = '';
        foreach($this->names as $name){
            $name_where .= " AND (firstname LIKE '%".$name."%' OR lastname LIKE '%".$name."%')";
        }

        // NEVERCONNECTED
        $neverconnected_where = '';
        if($this->neverConnected){
            $neverconnected_where .= 'AND (ula.timeaccess = 0 OR ula.timeaccess IS NULL)';
        }

        // ACTIVITY
        $activity_from = '';
        $activity_where = '';
        $this->update_activities_data();
        if(count($this->activity) > 0) {
            foreach ($this->activity as $activity) {
                $activity_from .= "INNER JOIN {block_instances} bi ON (c.id = bi.parentcontextid AND blockname = 'progress')";

                $activity_where .= "AND bi.id IN (
SELECT instanceid 
FROM {progress_instance_activity} 
WHERE instanceid = bi.id 
AND userid = u.id 
AND name = '" . $activity['name'] . "' 
AND num = '" . $activity['id'] . "' 
AND completed = " . $this->is_realized . ")";
            }
        }

        // GAIA
        // for each session, allow only user from gaia session
        $includeids = array();
        $excludeids = array();
        foreach($this->gaia_sessions as $data){
            $includeids += GaiaUtils::get_participant_formateur_ids($this->courseid, $data['sid'], $data['did'], $data['mid']);
        }

        if($this->other_gaia_session){
            $allsessionsgaia = GaiaUtils::get_sessions($this->courseid);

            //exclude all gaia's people
            if(count($allsessionsgaia) > 0) {
                // for each session, allow only user from gaia session
                foreach ($allsessionsgaia as $data) {
                    $sid = $data->session_id; // session gaia id
                    $did = $data->dispositif_id; // dispositif id
                    $mid = $data->module_id; // module id

                    $excludeids += GaiaUtils::get_participant_formateur_ids($this->courseid, $sid, $did, $mid);
                }
            }
        }

        $gaiasql = '';
        if(count($excludeids) > 0 && count($includeids) > 0){
            $gaiasql .= ' AND ((u.id='.implode(' OR u.id=', $includeids).') OR (u.id<>' . implode(' AND u.id<>', $excludeids).'))';
        }else if(count($includeids) > 0){
            $gaiasql .= ' AND (u.id='.implode(' OR u.id=', $includeids).')';
        }else if(count($excludeids) > 0){
            $gaiasql .= ' AND (u.id<>'.implode(' AND u.id<>', $excludeids).')';
        }

        // MAIN REQUEST
        $select = '';
        if($this->data_for == ParticipantList::DATA_FOR_JTABLE){
            $select = "SELECT SQL_CALC_FOUND_ROWS DISTINCT u.id, u.firstname, u.lastname, IF(ula.timeaccess>0,CONCAT('/Date(',ula.timeaccess,'000)/'),'') as timeaccess, '' as suivi, '' as progression, IF(pc.is_complete IS NULL,'Non','Oui') as finished";
        }else if($this->data_for == ParticipantList::DATA_FOR_PDF){
            $select = "SELECT SQL_CALC_FOUND_ROWS DISTINCT u.id, u.firstname, u.lastname, u.email, pc.is_complete, ta.appelation_officielle, ta.ville";
        }

        $this->sql_request = $select."
         FROM {role_assignments} a
         INNER JOIN {user} u ON (a.userid = u.id)
         INNER JOIN {context} c ON (c.id = a.contextid)
         ".$group_from."
         ".$activity_from."
         LEFT JOIN {progress_complete} pc ON (c.instanceid = pc.courseid AND u.id = pc.userid)
         LEFT JOIN {user_lastaccess} ula ON (ula.userid = u.id AND ula.courseid = c.instanceid)
         LEFT JOIN mdl_user_info_data ui ON (u.id = ui.userid AND ui.fieldid = (SELECT id FROM mdl_user_info_field WHERE shortname = \"rne\"))
         LEFT JOIN mdl_t_uai ta ON ui.data = ta.code_rne
        WHERE a.contextid = ".$this->contextid."
          ".$role_where."
          ".$group_where."
          ".$name_where."
          ".$activity_where."
          ".$neverconnected_where."
          ".$gaiasql;

        if($this->so !== null){
            $this->sql_request .= ' ORDER BY ' . $this->so;
        }

        if($this->si !== null){
            $this->sql_request .= ' LIMIT '.$this->si;
        }

        if($this->ps !== null){
            $this->sql_request .= ','.$this->ps;
        }
    }

    protected function update_activities_data(){
        global $DB;

        $block = $DB->get_record('block_instances', array('id' => $this->id));

        // Use in processData
        $this->config = unserialize(base64_decode($block->configdata));
        $this->modules = modules_in_use_by_course_id($this->courseid);
        $this->events = event_information($this->config, $this->modules);

        $actii = array();
        foreach((array)$this->config as $acti=>$value)
        {
            $found = preg_match("/(monitor|date_time|action|position)_([a-zA-Z_]+?)([0-9]{1,9})/i",$acti,$match);

            if ( $found )
            {
                $field = $match[1];
                $activity2 = $match[2];
                $num = $match[3];

                if ($field == 'position' && $value == '')
                {
                    $value = 0;
                }

                $actii[$activity2][$num][$field] = $value;
            }
        }

        $sql = "SELECT DISTINCT u.id
             FROM {role_assignments} a
             INNER JOIN {user} u ON (a.userid = u.id)
            WHERE a.contextid = ".$this->contextid;

        $result = $DB->get_records_sql($sql);

        $rows = array_values($result);

        $this->users_attempts = array();

        for ($i = 0; $i < count($rows); $i++)
        {
            $attempts  = get_attempts($this->modules, $this->config, $this->events, $rows[$i]->id, $this->courseid);

            $this->users_attempts[$rows[$i]->id] = $attempts;

            foreach ($actii as $acti=>$nums)
            {
                foreach($nums as $num=>$params)
                {
                    if(isset($param['date_time'])){
                        $attempt = (isset($attempts[$acti.$num])&&$attempts[$acti.$num])?1:0;
                        $sql = 'INSERT INTO {progress_instance_activity}(instanceid, userid, num, name, monitor, date_time, action, position, completed)
		    		    VALUES("'.$this->id.'", "'.$rows[$i]->id.'", "'.$num.'", "'.$acti.'", "'.intval($params['monitor']).'", "'.intval($params['date_time']).'", "'.$params['action'].'", "'.intval($params['position']).'", "'.$attempt.'")
		    		    ON DUPLICATE KEY UPDATE monitor=VALUES(monitor), date_time=VALUES(date_time), action=VALUES(action), position=VALUES(position), completed=VALUES(completed)';
                        $DB->execute($sql);
                    }
                }
            }
        }
    }

    public function processData(){
        $this->data = array_values($this->result_sql);

        for ($i = 0; $i < count($this->data); $i++)
        {
            $attempts = array();

            if (isset($this->users_attempts[$this->data[$i]->id]))
            {
                $attempts = $this->users_attempts[$this->data[$i]->id];
            }else{
                $attempts = get_attempts($this->modules, $this->config, $this->events, $this->data[$i]->id, $this->courseid);
            }

            $progressbar   = progress_bar($this->modules, $this->config, $this->events, $this->data[$i]->id, $this->courseid, $attempts, true, true);
            $progressvalue = get_progess_percentage($this->events, $attempts);

            $this->data[$i]->suivi = $progressbar;
            $this->data[$i]->progression = $progressvalue.'%';
        }
    }

    public function executeSQL(){
        global $DB;

        $this->result_sql = $DB->get_records_sql($this->sql_request);
        $this->result_count = $DB->get_record_sql("SELECT FOUND_ROWS() AS found_rows")->found_rows;
    }

}