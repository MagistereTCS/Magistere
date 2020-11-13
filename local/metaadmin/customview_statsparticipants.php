<?php

require_once('../../config.php');
require_once('customview_statsparticipants_form.php');
require_once('deletecustomview_form.php');
require_once('lib.php');
require_login();

$PAGE->requires->jquery();
$PAGE->requires->jquery_plugin('ui');
$PAGE->requires->jquery_plugin('ui-css');
$PAGE->requires->jquery_plugin('jtable-fr');
$PAGE->requires->jquery_plugin('jtable-css');

$pageparams = array();
$context = context_system::instance();
$site = get_site();

$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_url('/local/metaadmin/customview_statsparticipants.php', $pageparams);

$id = required_param('id', PARAM_INT);

global $CFG,$USER;

$sql = 'SELECT mv.*
FROM metaadmin_customview mv
WHERE mv.id = :id
AND mv.user_id = :userid
AND user_academy = :academy_name';

$checkview = get_centralized_db_connection()->get_record_sql($sql,array("id" => $id, "userid" => $USER->id, "academy_name" => $CFG->academie_name));
if ($checkview === false) {
  print_error('Wrong custom view ID.');
}

$view = get_custom_views_by_id($id);
$pagedesc = get_string("view_createheaderform", "local_metaadmin", $view->view_name);
$title = $site->shortname.': '.$pagedesc;
$fullname = $site->fullname;

$PAGE->set_title($title);
$PAGE->set_heading($fullname);

$mform = new customview_statsparticipants_form(null, array(
    'id' => $id,
    'context' => $context
));

$delForm = new deletecustomview_form(null, array(
    'id' => $id,
    'name' => $view->view_name
));

if ($delData = $delForm->get_data()) {
    if (isset($delData->deleteyes)) {
        $delurl = new moodle_url('/my/');
        delete_customview($delData->id);
        redirect($delurl);
    }
}

if ($sentData = $mform->get_data()) {
    //Case of view modification
    if (isset($sentData->modview)) {
        $modurl = new moodle_url('/local/metaadmin/editcustomview.php?id='.$id);
        redirect($modurl);
    }
    //Case of view deletion
    elseif (isset($sentData->delview)) {
        $mform = new deletecustomview_form(null, array(
            'id' => $sentData->id,
            'name' => $sentData->name
        ));
    }
}

// TODO: JUSTE POUR TESTER, EFFACER APRES !
//$date = new DateTime();
//send_statsparticipants_report($date);
///////////////////////////////////////////

echo $OUTPUT->header();
echo $OUTPUT->heading($pagedesc);

/*echo '<i>Pour des raisons de performance, les données affichées sur cette page ne sont pas en temps réel.<br/>Elles sont mises à jour une fois par jour.<br/><br/></i>';
echo 'Cette requête permet de connaître le nombre de personnes inscrites à un parcours de formation et qui se sont connectées entre les date sélectionnées. Une personne inscrite à plusieurs parcours n’est comptabilisée qu’une seule fois.<br/><br/>
Les critères de recherche sont les suivants :<br/>
<ul>
    <li>La recherche s’effectue sur tous les parcours, sessions, archives et gabarits et ceci quelle que soit la catégorie dans laquelle il est rangé.</li>
    <li>Seuls les comptes qui proviennent de la fédération d’identité sont comptabilisés à l’exclusion des comptes manuels</li>
    <li>Les personnes provenant d’une autre académie ne sont pas comptabilisées</li>
</ul>
<br/><br/>
Il est également possible d’utiliser un identifiant de parcours spécifique. Cet identifiant est défini lors de l’indexation du parcours. Il est composé de plusieurs parties<br/><br/>
En savoir plus : <a href="https://wiki.magistere.education.fr/Indexer_un_parcours">https://wiki.magistere.education.fr/Indexer_un_parcours</a><br/><br/>';*/

$mform->display();
echo $OUTPUT->footer();
