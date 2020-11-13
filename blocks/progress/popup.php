<?php

require_once('../../config.php');
require_once($CFG->dirroot . '/local/gaia/lib/GaiaUtils.php');
require_once($CFG->dirroot . '/blocks/progress/ParticipantsList.php');

$courseid = required_param('courseid', PARAM_INT);
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);

// json data
$sgaia_data = required_param('sgaia', PARAM_TEXT);
$sgaia = json_decode($sgaia_data);
$sother = required_param('sgaiaother', PARAM_BOOL);

$id = required_param('id', PARAM_INT);
$contextid = required_param('contextid', PARAM_INT);
$role = required_param('role', PARAM_INT);
$group = required_param('group', PARAM_INT);
$name = required_param('name', PARAM_TEXT);
$realized = required_param('realized', PARAM_BOOL);
$activity = required_param('activity', PARAM_TEXT);
$neverconnected = required_param('neverconnected', PARAM_BOOL);

$role_formateur = $DB->get_record('role', array('shortname' => 'formateur'));
$role_participant = $DB->get_record('role', array('shortname' => 'participant'));
$participantList = new ParticipantList();
$participantList->setId($id);
$participantList->setContextId($contextid);
$participantList->setCourseid($courseid);
$participantList->setDataFor(ParticipantList::DATA_FOR_PDF);

// group
if ($group > 0){
    $participantList->setGroupId($group);
}

// name
$name = str_replace("\\",'',str_replace("'",'',str_replace('%','',trim($name))));
if ($name != '')
{
    $words = explode(' ', $name);

    foreach($words as $word){
        $participantList->addName($word);
    }
}

// activities
if ($activity != 'none' && strlen($activity) > 2)
{
    if($realized == 1){
        $participantList->setIsRealized();
    }

    $activity_ex = explode('*',$activity);
    $activityname = $activity_ex[0];
    $activityid = $activity_ex[1];

    $participantList->setActivity($activityname, $activityid);
}

// neverconnected
if ($neverconnected)
{
    $participantList->setIsNeverConnected();
}

$participants = array();
// Recuperation des ids des participants gaia
$formateurs = array();
if(count($sgaia) > 0){
    // for each session, allow only user from gaia session
    foreach($sgaia as $data){
        $d = explode('-', $data->value);
        $sid = $d[0]; // session gaia id
        $did = $d[1]; // dispositif id
        $mid = $d[2]; // module id

        $participantList->setGaiaSession($sid, $did, $mid);
        $participantList->setRoleId($role_participant->id);

        $participants += $participantList->getData();

        $participantList->setRoleId($role_formateur->id);
        $formateurs += $participantList->getData();
    }
}

if($sother) {
    $participantList->resetGaiaSession();
    $participantList->setOtherGaiaSession();
    $participantList->setRoleId($role_participant->id);
    $participants += $participantList->getData();

    $participantList->setRoleId($role_formateur->id);
    $formateurs += $participantList->getData();
}else if(count($sgaia) == 0){
    $participantList->setRoleId($role_participant->id);
    $participants = $participantList->getData();

    $participantList->setRoleId($role_formateur->id);
    $formateurs = $participantList->getData();
}

$indexation = $DB->get_record('local_indexation', array('courseid' => $courseid));

$count_presents = 0;
foreach ($participants AS $participant)
{
    if ($participant->is_complete == 1)
    {
        $count_presents++;
    }
}

setlocale (LC_TIME, 'fr_FR.utf8','fra');
// Remplissage des variables du certificat
$intitule = $course->fullname;
$reference = $course->shortname;
$date_de_debut = strftime("%d %b %Y",$course->startdate);
//$date_de_debut = date("d M Y",$course->startdate);
$date_de_fin = '';

if ($indexation)
{
    $duree_a_distance_h = floor($indexation->tps_a_distance/60);
    $duree_a_distance_m = $indexation->tps_a_distance%60;
}else{
    $duree_a_distance_h = '0';
    $duree_a_distance_m = '0';
}

$nb_participant_inscrits = count($participants);
$nb_participant_present = $count_presents;

$date_edition = strftime("%d %b %Y");


?>

<form action="pdfCertificate.php" method="POST" target="hidden-form">
    <input type="hidden" name="courseid" value="<?php echo $courseid; ?>" />
    <input type="hidden" name="sgaia" value='<?php echo $sgaia_data; ?>' />
    <input type="hidden" name="sother" value="<?php echo $sother; ?>" />
    <input type="hidden" name="id" value="<?php echo $id; ?>"/>
    <input type="hidden" name="contextid" value="<?php echo $contextid; ?>"/>
    <input type="hidden" name="role" value="<?php echo $role; ?>"/>
    <input type="hidden" name="group" value="<?php echo $group; ?>"/>
    <input type="hidden" name="name" value="<?php echo $name; ?>"/>
    <input type="hidden" name="realized" value="<?php echo $realized; ?>"/>
    <input type="hidden" name="activity" value="<?php echo $activity; ?>"/>
    <input type="hidden" name="neverconnected" value="<?php echo $neverconnected; ?>"/>

    <div style="border:1px solid #000000;padding:10px;margin-bottom:15px">

        <p style="margin-top:0px">
            Intitulé : <b><?php echo $intitule; ?></b><br/>
            Réf : <b><?php echo $reference; ?></b>
        </p>

        <p>Du <b><?php echo $date_de_debut; ?></b> au <input type="text" name="end_date" value="<?php echo date("d/m/Y"); ?>" id="enddate_datepicker" style="width:95px"></p>

        <p>Durée à distance : <input type="text" name="duration_h" id="duration_h" value="<?php echo $duree_a_distance_h; ?>" style="width:25px"> h <input type="text" name="duration_m" value="<?php echo $duree_a_distance_m; ?>" style="width:25px"> m</p>


        <div style="float:left">Formateurs :</div>
        <ul style="list-style-type:none;margin-left:100px">

            <?php
            if (count($formateurs)>0)
            {
                foreach($formateurs AS $formateur)
                {
                    echo '<li>'.$formateur->firstname.' '.$formateur->lastname.' ('.$formateur->email.')</li>';
                }
            }else{
                echo '<li>Aucun formateur trouvé</li>';
            }
            ?>
        </ul>
        <p>
            Nombre de participants inscrits : <b><?php echo $nb_participant_inscrits; ?></b><br/>
            <span style="margin-left:72px">Nombre de présents : <b><?php echo $nb_participant_present; ?></b></span>
        </p>
        <p>Rapport édité le : <?php echo $date_edition; ?></p>
        <p style="margin-bottom:0px">
            Commentaire éventuel :<br/>
            <textarea name="comment" id="comment" cols="70" rows="6" style=""></textarea>
        </p>
    </div>


    <?php
    if(count($sgaia) > 0) {
        ?>
        <div style="border: 1px solid black; padding: 10px; margin-bottom: 15px;">
            <h3>Sessions Gaïa</h3>
            <?php
            foreach($sgaia as $s){
                $d = explode('-', $s->value);
                $sid = $d[0]; // session gaia id
                $did = $d[1]; // dispositif id
                $mid = $d[2]; // module id
                $info = GaiaUtils::get_session_info($courseid, $sid, $did, $mid);
                $sstartdate = date('d/m/Y H:i', $info->startdate);
                $senddate = date('d/m/Y H:i', $info->enddate);

                $url = new moodle_url('/blocks/gaia/session_description.php', array('sessiongaiaid' => $sid, 'dispositifid' => $did, 'moduleid' => $mid));
                ?>
                <p style="padding-left: 15px;">
                    <span style="font-weight: bold"><?php echo $info->dispositif_id; ?>: <?php echo $info->dispositif_name ?></span><br/>
                    Module <?php echo $info->module_id; ?>: <?php echo $info->module_name ?><br/>
                    <a href="<?php echo $url; ?>">Session du <?php echo $sstartdate; ?> au <?php echo $senddate; ?></a><br/><br/>
                </p>
                <?php
            }
            ?>
        </div>
        <?php

    }
    ?>

    <div style='width:100%;text-align:center'>
        <input name="validate" value="Télécharger le rapport" type="submit" id="bt_validate" style='margin-right:50px'>
        <input name="cancel" value="Annuler" type="button" id="bt_cancel" style='margin-left:50px'>
    </div>
</form>
</div>

<div id="generate_certificate_conf" class="felement fsubmit" style="display:none;text-align:center">Chargement...</div>
<iframe style="display:none" name="hidden-form"></iframe>