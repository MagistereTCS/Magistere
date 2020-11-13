<?php

require_once(dirname(__FILE__) . '/../../config.php');


$PAGE->set_context(context_system::instance());
$PAGE->set_title('Description parcours');
$PAGE->set_heading('Description parcours');
$PAGE->set_pagelayout('standard');
$PAGE->set_url('/blocks/completion_progress/pdfCertificate.php');

//require_login();

$courseid = required_param('courseid', PARAM_INT);


$data = required_param('data', PARAM_RAW);
$data = unserialize(base64_decode($data));

$course = get_course($courseid);
$id = $data['id'];

$data_file_path = $data['data_file_path'];
$data_unserialized = unserialize(file_get_contents($data_file_path));

$data_to_render = $data_unserialized['data_to_render'];
$sother_to_render = $data_unserialized['sother_to_render'];
$sgaia_to_render = $data_unserialized['sgaia_to_render'];
$formateurs = $data_unserialized['formateurs'];
$tuteurs = $data_unserialized['tuteurs'];

$gaia_html = '';
$sother_html = '';
$participants_html = '';

$color1 = '#EEEEEE';
$color2 = '#FFFFFF';

$color = $color2;
$count_presents = 0;
$bottom_border = '';
$nb_participant_inscrits = 0;

foreach($sgaia_to_render as $pos => $drender)
{
    $gaia_html .= render_cartouche_session_gaia($drender['sstartdate'],
        $drender['senddate'],
        $drender['did'],
        $drender['dispositif_name'],
        $drender['mid'],
        $drender['module_name'],
        ($pos != 0));

    $color = $color2;
    $gaia_html .= '<table cellspacing="0" cellpadding="0" style="width:100%;text-align:center;border: 1px solid black;">';

    $gaia_html .= '<tr style="background-color:#CCCCCC;border-bottom: 1px solid black;"><th style="width:20%">Nom Prénom</th><th style="width:88px">Etablissement</th><th style="width:88px">Présence</th></tr>';

    if(count($drender['participants']) == 0){
        $gaia_html .= '<tr><td colspan="3">Aucun participant</td></tr>';
    }

    foreach($drender['participants'] as $p){
        $color = ($color==$color1)?$color2:$color1;

        if ($p->is_complete == 1)
        {
            $count_presents++;
        }

        $etablissement = format_etablissement($p);
        $gaia_html .= render_participant($p->lastname, $p->firstname, $etablissement, $p->is_complete, $color);
    }

    $gaia_html .= '</table>';
    $nb_participant_inscrits += count($drender['participants']);
}


if(count($sother_to_render) > 0) {

    $sother_html .='<p style="width: 100%; border: 1px solid black;text-align: center;">
Autres participants non-inscrits à une session GAIA
</p>
<table cellspacing="0" cellpadding="0" style="width:100%;text-align:center;border: 1px solid black;">
<tr style="background-color:#CCCCCC;border-bottom: 1px solid black;">
    <th style="width:20%">Nom Prénom</th>
    <th style="width:88px">Etablissement</th>
    <th style="width:88px">Présence</th>
</tr>';


    foreach($sother_to_render as $drender)
    {
        // $drender = (array)$drender;

        $color = ($color==$color1)?$color2:$color1;

        if ($drender->is_complete == 1)
        {
            $count_presents++;
        }

        $etablissement = format_etablissement($drender);
        $sother_html .= render_participant($drender->lastname, $drender->firstname, $etablissement, $drender->is_complete, $color);

        $nb_participant_inscrits++;
    }
}

foreach ($data_to_render as $drender)
{
    // $drender = (array) $drender;

    $color = ($color==$color1)?$color2:$color1;

    if ($drender->is_complete == 1)
    {
        $count_presents++;
    }

    $etablissement = format_etablissement($drender);
    $participants_html .= render_participant($drender->lastname, $drender->firstname, $etablissement, $drender->is_complete, $color);

    $nb_participant_inscrits++;
}


setlocale (LC_TIME, 'fr_FR.utf8','fra');
// Remplissage des variables du certificat
$intitule = $course->fullname;
$reference = $course->shortname;
$date_de_debut = strftime("%d %b %Y",$course->startdate);
$date_de_fin = strftime("%d %b %Y",$data['end_date']);
$duree_a_distance_h = $data['duration_h'];
$duree_a_distance_m = $data['duration_m'];

$nb_participant_present = $count_presents;

$date_edition = strftime("%d %b %Y");

$comment = $data['comment'];


$html = '';

$logo = $OUTPUT->image_url('general/magistere_logo', 'theme');

echo '<div style="margin-bottom:15px;text-align:center">
<img src="'.$logo.'" style="width:300px"/>
</div>
<div style="border:1px solid #000000;padding:10px;margin-bottom:15px">
    <p style="margin-top:0px">
        Intitulé : <b>'.$intitule.'</b><br/>
        Réf : <b>'.$reference.'</b>
    </p>
    <p>
        Du <b>'.$date_de_debut.'</b> au <b>'.$date_de_fin.'</b>
    </p>
    <p>
        Durée à distance : '.$duree_a_distance_h.'h '.$duree_a_distance_m.'m
    </p>
    <div style="float:left">Formateurs :</div>
    <ul style="list-style-type:none;margin-left:60px">';

if (count($formateurs) > 0)
{
    foreach($formateurs AS $formateur)
    {
        echo '<li>'.$formateur->firstname.' '.$formateur->lastname.' ('.$formateur->email.')</li>';
    }
}

echo '</ul>
<div style="float:left">Tuteurs :</div>
    <ul style="list-style-type:none;margin-left:60px">';

if (count($tuteurs) > 0)
{
    foreach($tuteurs AS $tuteur)
    {
        echo '<li>'.$tuteur->firstname.' '.$tuteur->lastname.' ('.$tuteur->email.')</li>';
    }
}

echo '</ul>
    <p>
        Nombre de participants inscrits : <b>'.$nb_participant_inscrits.'</b><br/>
        <span style="margin-left:72px">Nombre de présents : <b>'.$nb_participant_present.'</b></span>
    </p>
    <p>
        Rapport édité le : '.$date_edition.'
    </p>
    <p style="margin-bottom:0px">
        <b>Commentaire :</b><br/>'.nl2br(htmlspecialchars($comment)).'
    </p>
</div>';

if($participants_html != ''){
    echo '<table cellspacing="0" cellpadding="0" style="width:100%;text-align:center;border: 1px solid black;">
<tr style="background-color:#CCCCCC;border-bottom: 1px solid black;">
    <th style="width:20%">Nom Prénom</th>
    <th style="width:88px">Etablissement</th>
    <th style="width:88px">Présence</th>
</tr>';

    echo $participants_html;

    echo '</table>';
}

if($gaia_html != ''){
    echo $gaia_html;
}

if($sother_html != ''){
    echo $sother_html;
}

// Utils
function render_participant($lastname, $firstname, $etablissement, $is_complete, $background_color)
{
    if ($is_complete == 1)
    {
        $present = '<td>Présent(e)</td>';
    }else{
        $present = '<td style="color:#CC0000">Absent(e)</td>';
    }

    return '<tr style="background-color:'.$background_color.';height:25px; text-align: left;">
<td>'.$lastname.' '.$firstname.'</td>'.
        '<td style="border-left: 1px solid black; border-right:1px solid #000000; font-size: 10px">'.$etablissement.'</td>'.
        $present.
        '</tr>';
}

function render_cartouche_session_gaia($sstartdate, $senddate, $did, $dispositif_name, $mid, $module_name, $with_page_break){
    $sstartdate = date('d/m/Y H:i', $sstartdate);
    $senddate = date('d/m/Y H:i', $senddate);

    $page_break = $with_page_break ? 'page-break-before: always;' : '';

    return '
<p style="width: 100%; border: 1px solid black;'.$page_break.'">
<span style="font-weight: bold;">'.$did.': '.$dispositif_name.'</span><br/>
Module '.$mid.': '.$module_name.'<br/>
Session du '.$sstartdate.' au '.$senddate.'
</p>';
}

function format_etablissement($p)
{
    $pofficielle = '';
    if(isset($p->appelation_officielle)){
        $pofficielle .= $p->appelation_officielle;
    }

    if(isset($p->ville)){
        if(!empty($pofficielle)){
            $pofficielle .= ' - ';
        }

        $pofficielle .= $p->ville;
    }

    return $pofficielle;
}




