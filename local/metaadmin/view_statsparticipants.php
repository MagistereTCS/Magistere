<?php

require_once('../../config.php');
require_once('view_statsparticipants_form.php');

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
$PAGE->set_url('/local/metaadmin/view_statsparticipants.php', $pageparams);


// First create the form.
$args = array(
//    'type' => $type,
);

$view_statsparticipants_form = new view_statsparticipants_form(null, $args);


if ($view_statsparticipants_form->is_cancelled()) {
    // The form has been cancelled, take them back to what ever the return to is.
    redirect($returnurl);
} else if ($data = $view_statsparticipants_form->get_data()) {
	
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

echo 'Cette requête permet de connaître le nombre de personnes inscrites à un parcours de formation et qui se sont connectées entre les date sélectionnées. Une personne inscrite à plusieurs parcours n’est comptabilisée qu’une seule fois.<br/><br/>

Les critères de recherche sont les suivants :<br/>
<ul>
<li>La recherche s’effectue sur tous les parcours, sessions, archives et gabarits et ceci quelle que soit la catégorie dans laquelle il est rangé.</li>
<li>Seuls les comptes qui proviennent de la fédération d’identité sont comptabilisés à l’exclusion des comptes manuels</li>
<li>Les personnes provenant d’une autre académie ne sont pas comptabilisées</li>
</ul>
<br/><br/>
Il est également possible d’utiliser un identifiant de parcours spécifique. Cet identifiant est défini lors de l’indexation du parcours. Il est composé de plusieurs parties<br/><br/>
En savoir plus : <a href="https://wiki.magistere.education.fr/Indexer_un_parcours">https://wiki.magistere.education.fr/Indexer_un_parcours</a><br/><br/>';

$view_statsparticipants_form->display();

echo $OUTPUT->footer();
