<?php

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');

require_once($CFG->dirroot . '/group/lib.php');
require_once($CFG->dirroot.'/local/gaia/lib/GaiaUtils.php');

$course_id = required_param('id', PARAM_INT);

$course = $DB->get_record('course', array('id'=>$course_id), '*', MUST_EXIST);
$context = context_course::instance($course->id);

require_course_login($course, true);



$currentMonth = intval(date('m'));
$currentYear = intval(date('Y'));
$currentTime = time();

$lastYear = $currentYear - 1;

// US format
$startdate = '09/01/'.$lastYear;
$enddate = '08/31/'.$currentYear;

if($currentTime >= strtotime($startdate) && $currentTime <= strtotime($enddate)){
    // FR format
    $startdate = '01/09/'.$lastYear;
    $enddate = '31/08/'.$currentYear;
}else{
    $startdate = '01/09/'.$currentYear;
    $enddate = '31/08/'.($currentYear+1);
}

$dispositif_id = optional_param('dispositifid', null, PARAM_ALPHANUM);
$module_id = optional_param('moduleid', null, PARAM_INT);
$session_id = optional_param('sessionid', null, PARAM_INT);
$gaia_formation = null;

if ($dispositif_id != null && $module_id != null && $session_id != null)
{
    if ( ! $DB->record_exists('local_gaia_session_course', array('dispositif_id'=>$dispositif_id,'module_id'=>$module_id,'session_id'=>$session_id,'course_id'=>$course_id)) ) {
        $dispositif_id = $module_id = $session_id = '';
        $gaia_formation = new stdClass();
        $gaia_formation->dispositif_name = '';
        $gaia_formation->module_name = '';
        $gaia_formation->formation_place = '';
    }else{
        $gaia_formation = $DB->get_record('local_gaia_formations', array('dispositif_id'=>$dispositif_id,'module_id'=>$module_id,'session_id'=>$session_id),'dispositif_name,module_name,formation_place');
    }
}else{
    $gaia_formation = new stdClass();
    $gaia_formation->dispositif_name = '';
    $gaia_formation->module_name = '';
    $gaia_formation->formation_place = '';
}

//$course_id = 1588;
//$dispositif_id = '18NDEN0310';
//$module_id = 45805;
//$session_id = 78576;

$isSubmited = optional_param('submitgeneral', false, PARAM_BOOL);
$isCanceled = optional_param('cancelgeneral', false, PARAM_BOOL);

if ($isCanceled) {
    $url = new moodle_url('/local/workflow/index.php',array('id'=>$course_id),'id_gaiaheader');
    redirect($url);
}
else if ($isSubmited) {
    
    $data = new stdClass();
    $data->course_id = required_param('course_id', PARAM_INT);
    
    $data->dispositif_id = required_param('dispositif_id', PARAM_ALPHANUM);
    $data->module_id = required_param('module_id', PARAM_INT);
    $data->session_id = required_param('session_id', PARAM_INT);
    $data->add_all_participants = optional_param('add_all_participants', false, PARAM_BOOL);
    $data->add_all_formateurs = optional_param('add_all_formateurs', false, PARAM_BOOL);
    $data->formateurs = optional_param_array('formateurs', array(), PARAM_INT);
    $data->group = required_param('group', PARAM_ALPHANUM);
    $data->new_group = required_param('new_group', PARAM_ALPHANUMEXT);
    $data->override_subscriptions = optional_param('override_subscriptions', false, PARAM_BOOL);
    $data->group = required_param('group', PARAM_ALPHANUM);
    //$data->grouping = required_param('grouping', PARAM_INT);
    $data->grouping = '';
    $data->via_id = null;
    
    process_postdata($data);
    
    $url = new moodle_url('/local/workflow/index.php',array('id'=>$course_id),'id_gaiaheader');
    redirect($url);
}



$PAGE->set_context($context);
$PAGE->set_pagelayout('login');
$PAGE->set_pagetype('course-view-' . $course->format);
$PAGE->set_url('/local/gaia/linkcourse.php', array('id' => $course_id));
$PAGE->set_course($course);


$PAGE->set_title("$course->shortname : ". get_string('pluginname', 'local_gaia'));
$PAGE->set_heading($course->fullname);


$PAGE->requires->js_call_amd("local_gaia/linkCourse", "init", array($CFG->wwwroot.'/local/gaia/api.php',$gaia_formation->dispositif_name,$gaia_formation->module_name,$gaia_formation->formation_place));

echo $OUTPUT->header();



echo '
<label for="" id="from_label">DE : </label>
<div id="datefilter">
    <div class="startdate">
        <input type="text" id="startdate" name="startdate" value="'.$startdate.'" />
    </div>
    <div class="enddate">
        <div >À : </div>
        <input type="text" id="enddate" name="enddate" value="'.$enddate.'" />
    </div>
</div>

<div style="clear: both"></div>
<label for="" id="dispositif_label">DISPOSITIF : </label>
<div id="dispositif">
    <div class="custom_input">
        <div class="wrapper">
            <input type="text" id="dispositif_input" name="dispositif" value="" />
            <button id="dispositif_reset_btn"><i class="fas fa-times"></i></button>
            <button id="dispositif_search_btn"><i class="fas fa-chevron-down"></i></button>
        </div>
    </div>
    
    <div id="dispositif_res"></div>
</div>

<label for="" id="module_label" style="display:none">MODULES : </label>
<div id="module" style="display:none">
    <div class="custom_input">
        <div class="wrapper">
            <input type="text" id="module_input" name="module" value="" />
            <button id="module_reset_btn"><i class="fas fa-times"></i></button>
            <button id="module_search_btn"><i class="fas fa-chevron-down"></i></button>
        </div>
    </div>
    
    <div id="module_res"></div>
</div>

<label for="" id="session_label" style="display:none">SESSIONS : </label>
<div id="session" style="display:none">
    <div class="custom_input">
        <div class="wrapper">
            <input type="text" id="session_input" name="session" value="" />
            <button id="session_reset_btn"><i class="fas fa-times"></i></button>
            <button id="session_search_btn"><i class="fas fa-chevron-down"></i></button>
        </div>
    </div>
    
    <div id="session_res"></div>
</div>

<div id="session_data" style="display:none">
    <form autocomplete="off" action="" method="post" id="form">
        <input type="hidden" id="course_id" name="course_id" value="'.$course_id.'" />
        <input type="hidden" id="dispositif_id" name="dispositif_id" value="'.$dispositif_id.'" />
        <input type="hidden" id="module_id" name="module_id" value="'.$module_id.'" />
        <input type="hidden" id="session_id" name="session_id" value="'.$session_id.'" />
        <div id="participants">
            <div class="list">
                <span></span>
                <ul>
                </ul>
                <div id="show_participants" class="show"><i class="fas fa-chevron-down"></i> Voir tous</div>
            </div>
            <div>
                <input type="checkbox" id="add_all_participants" name="add_all_participants" />
                <label for="add_all_participants"> Inscrire tous les participants</label>
            </div>
        </div>
        
        <div id="formateurs">
            <select multiple="multiple" name="formateurs[]"></select>
            <div>
                <input type="checkbox" id="add_all_formateurs" name="add_all_formateurs" />
                <label for="add_all_formateurs"> Inscrire tous les formateurs</label>
            </div>
        </div>
        <div style="clear: both"></div>
        <div id="groups">
            <label>Associer les participants à un groupe</label>
            <select name="group" id="group_select"></select>
        </div>
        
        <div id="new_group">
            Nouveau groupe <input type="text" name="new_group" />
        </div>
        <div style="clear: both"></div>
        <div>
            <input type="checkbox" id="override_subscriptions" name="override_subscriptions" /><label for="override_subscriptions"> Ecraser les inscriptions. <span>Attention, les utilisateurs non listés seront désinscrits</span></label>
        </div>

<div class="felement fgroup" id="subbuttons">
<input type="submit" name="cancelgeneral" value="Annuler" class="btn-cancel" id="id_cancelgeneral"> 
<input type="submit" name="submitgeneral" value="Enregistrer" id="id_submitgeneral">
</div>
    </form>
</div>





';


echo $OUTPUT->footer();






function process_postdata($data)
{
    global $DB, $USER;
    
    $groupid = null;
    $groupdata = null;
    if($data->group == 'newgroup')
    {
        $groupdata = new stdClass();
        $groupdata->name = $data->new_group;
        
        $groups = groups_get_all_groups($data->course_id);
        
        $num = -1;
        foreach ($groups as $g) {
            if ($g->name == $groupdata->name){
                $num = 1;
            }else if(strpos($g->name, $groupdata->name . '_') === 0 && $num == 1) {
                $num++;
            }
        }
        
        if($num > 0){
            $groupdata->name .= '_'.$num;
        }
        
        $groupdata->courseid = $data->course_id;
        $groupid = groups_create_group($groupdata);
    }else if($data->group !== 'nogroup'){
        $groupid = $data->group;
        $groupdata = $DB->get_record('groups', array('id' => $groupid));
    }
    
    
    $groupingid = 0;
    if($groupid) {
        if ($data->grouping == 'newgrouping') {
            // on cree un nouveau groupement
            // en verifiant qu'il n'y ai pas de collision
            // de nom
            $groupings = groups_get_all_groupings($data->course_id);
            
            $num = -1;
            foreach ($groupings as $gr) {
                if ($gr->name == $groupdata->name){
                    $num = 1;
                }else if(strpos($gr->name, $groupdata->name . '_') === 0 && $num == 1) {
                    $num++;
                }
            }
            
            $groupingname = $groupdata->name;
            
            // cas de collision
            if ($num > 0) {
                $groupingname .= '_'.$num;
            }
            
            $groupingdata = new stdClass();
            $groupingdata->name = $groupingname;
            $groupingdata->courseid = $data->course_id;
            $groupingid = groups_create_grouping($groupingdata);
        } else if ($data->grouping) {
            $groupingid = $data->grouping;
        }
        
        if($groupingid > 0) {
            groups_assign_grouping($groupingid, $groupid);
        }
    }
    
    if($data->course_id !== null){
        GaiaUtils::bind_session_with_gaia($data->course_id, $data->session_id, $data->dispositif_id, $data->module_id);
    }
    
    $participantids = array();
    $animatorids = array();
    
    if(count($data->formateurs) > 0){
        $enrolConfig = new stdClass();
        $enrolConfig->name = 'GAIA-FORMATEUR_'.$data->session_id.'_'.$data->dispositif_id.'_'.$data->module_id;
        $enrolConfig->roleshortname = 'formateur';
        $enrolConfig->courseid = $data->course_id;
        
        // if current user is a tuteur, remove him from the users to subscribe
        $roles = get_user_roles(context_course::instance($data->course_id),  $USER->id);
        $istuteur = false;
        foreach($roles as $role){
            $istuteur |= ($role->shortname == 'tuteur');
        }
        
        $restrictedemail = '';
        if($istuteur){
            $restrictedemail = $USER->email;
        }
        
        $usergaias = GaiaUtils::get_intervenant_gaia($data->session_id, $data->dispositif_id, $data->module_id, $restrictedemail);
        $users = array();
        if ($data->add_all_formateurs) {
            $users = $usergaias;
        }else{
            foreach($usergaias as $id => $data2){
                if(in_array($id, $data->formateurs)){
                    $users[$id] = $data2;
                }
            }
        }
        
        
        $animatorids = GaiaUtils::subscribe_user($enrolConfig, $users, $data->override_subscriptions, $groupid);
    }
    
    if($data->add_all_participants){
        $enrolConfig = new stdClass();
        $enrolConfig->name = 'GAIA-PARTICIPANT_'.$data->session_id.'_'.$data->dispositif_id.'_'.$data->module_id;
        $enrolConfig->roleshortname = 'participant';
        $enrolConfig->courseid = $data->course_id;
        
        $users = GaiaUtils::get_stagiaire_gaia($data->session_id, $data->dispositif_id, $data->module_id);
        
        $participantids = GaiaUtils::subscribe_user($enrolConfig, $users, $data->override_subscriptions, $groupid);
    }
    
    if($data->via_id !== null) {
        GaiaUtils::subscribe_via_users($data->via_id, $animatorids, $participantids, $groupingid);
    }
    
    return true;
}


