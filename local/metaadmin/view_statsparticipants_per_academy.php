<?php

require_once('../../config.php');
require_once('view_statsparticipants_per_academy_form.php');

require_login();


//$type = required_param('type', PARAM_TEXT);
//$courseid = optional_param('courseid', 0, PARAM_INTEGER);

$PAGE->requires->jquery();
$PAGE->requires->jquery_plugin('ui');
$PAGE->requires->jquery_plugin('ui-css');
$PAGE->requires->jquery_plugin('jtable-fr');
$PAGE->requires->jquery_plugin('jtable-css');

$PAGE->set_pagelayout('admin');
$PAGE->set_context(context_system::instance());

$pageparams = array();
$PAGE->set_url('/local/metaadmin/view_statsparticipants_per_academy.php', $pageparams);


// First create the form.
$args = array(
//    'type' => $type,
);

$view_statsparticipants_per_acedemy_form = new view_statsparticipants_per_academy_form(null, $args);


if ($view_statsparticipants_per_acedemy_form->is_cancelled()) {
    // The form has been cancelled, take them back to what ever the return to is.
    redirect($returnurl);
} else if ($data = $view_statsparticipants_per_acedemy_form->get_data()) {
	
    // Process data if submitted.
    

    if (isset($data->saveanddisplay)) {
        // Redirect user to newly created/updated course.
        redirect($courseurl);
    } else {
        // Save and return. Take them back to wherever.
        redirect($returnurl);
    }
}

// Print the form.

$site = get_site();



$pagedesc = get_string("createheaderform", 'local_metaadmin');
$title = $site->shortname.': '.$pagedesc;
$fullname = $site->fullname;
//$PAGE->navbar->add($pagedesc);


$PAGE->set_title($title);
$PAGE->set_heading($fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading($pagedesc);

echo '<i>Pour des raisons de performance, les données affichées sur cette page ne sont pas en temps réel.<br/>Elles sont mises à jour une fois par jour.<br/><br/></i>';

echo 'Cette requête permet de connaître le nombre de personnes inscrites à un parcours de formation sur les autres domaines académiques et nationaux et qui se sont connectées entre les dates sélectionnées.';
echo '<br/><br/>';
echo 'L’observation de la ligne permet de savoir combien de personnes se sont formées sur chaque domaine. L’observation de la colonne permet de savoir d’où viennent les personnes qui se sont formées sur un domaine particulier.';
echo '<br/><br/>';
echo 'Il est également possible d’utiliser un identifiant de parcours spécifique. Cet identifiant est défini lors de l’indexation du parcours. Il est composé de plusieurs parties';
echo '<br/><br/>';
echo 'En savoir plus : <a href="https://wiki.magistere.education.fr/Indexer_un_parcours">https://wiki.magistere.education.fr/Indexer_un_parcours</a>';
echo '<br/><br/>';

$view_statsparticipants_per_acedemy_form->display();

echo $OUTPUT->footer();
