<?php

defined('MOODLE_INTERNAL') || die();

$string['modulename'] = 'Metaadmin';
$string['modulenameplural'] = 'Metaadmins';
$string['pluginadministration'] = 'Administration';
$string['pluginname'] = 'Metaadmin';

$string['jtheader_courseuid'] = 'Parcours';
$string['jtheader_academy'] = 'Académie';
$string['jtheader_public1D'] = '1D public';
$string['jtheader_prive1D'] = '1D privé';
$string['jtheader_total1D'] = 'Total 1D';
$string['jtheader_public2D'] = '2D public';
$string['jtheader_prive2D'] = '2D privé';
$string['jtheader_total2D'] = 'Total 2D';
$string['jtheader_other'] = 'Autre';
$string['jtheader_total'] = 'Total';

$string['jtheader_localhours'] = 'Heure prés.';
$string['jtheader_distanthours'] = 'Heure dist.';
$string['jtheader_total1Dh'] = 'Heure stag. 1D';
$string['jtheader_total1Dj'] = 'Jour stag. 1D';
$string['jtheader_total2Dh'] = 'Heure stag. 2D';
$string['jtheader_total2Dj'] = 'Jour stag. 2D';
$string['jtheader_otherh'] = 'Heure stag. autre';
$string['jtheader_otherj'] = 'Jour stag. autre';
$string['jtheader_totalh'] = 'Heure stag. total';
$string['jtheader_totalj'] = 'Jour stag. total';

$string['jttitle'] = 'Résultats';

$string['createheaderform'] = 'Meta-Administration';
$string['createparcourssession'] = 'Statistiques sur la participation des utilisateurs aux parcours';
$string['showresults'] = 'Afficher les résultats';


$string['lastconnmin'] = 'Connexion entre le';
// $string['lastconnmin'] = 'Dernière connexion entre le';
//$string['lastconnmin_help'] = 'L\'utilisateur doit s\'être connecté au parcours après cette date.<br/>Les deux dates doivent être différentes!<br/>Dans le cas contraire elles ne seront pas prises en comptes.';
$string['lastconnmin_help'] = 'L\'utilisateur doit s\'être connecté au parcours entre les dates.';
$string['lastconnmax'] = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;et le';
$string['lastconnmax_help'] = 'L\'utilisateur doit s\'être connecté au parcours avant cette date.<br/>Les deux dates doivent être différentes!<br/>Dans le cas contraire elles ne seront pas prises en comptes.';
$string['parcoursidentifiant'] = 'Identifiant du parcours';
$string['parcoursidentifiant_help'] = 'Identifiant unique du parcours composé de :<ul><li style="list-style-type:circle">l\'année à deux chiffres</li><li style="list-style-type:circle">l\'identifiant de l\'académie de création</li><li style="list-style-type:circle">le nom du parcours</li></ul>';
$string['userrole'] = 'Rôle de l\'utilisateur';
$string['userrole_help'] = 'Rôle que l\'utilisateur doit avoir sur le parcours';

$string['delete_confirm'] = 'Êtes-vous sûr de vouloir supprimer la vue "{$a->viewname}"?';
$string['export'] = 'Exporter les données';
$string['error_nbviews'] = "La limite de 5 vues personnalisées a été atteinte !";

$string['html_export'] = 'Afficher en HTML';
$string['copy_table'] = 'Copier le tableau';
$string['downloadcsv'] = 'Télécharger en CSV';

$string['noresults'] = 'Aucun résultats';
$string['jtheader_dpt_name'] = 'Département';
$string['jtheader_dpt_code'] = 'Code';

$string['statsreport_subject'] = "Votre rapport automatique de statistiques sur vue personnalisée";
$string['statsreport_contentTXT'] = 'Bonjour,\n\nVous trouverez ci-joint un rapport statistiques M@gistère de l\'année en cours sur la vue personnalisée "{$a->viewname}".\nSi vous recevez ce rapport, c\'est parce que vous avez été inscrit à la liste des destinataires par un utilisateur de l\'académie de {$a->viewaca}.\n\nCordialement.';
$string['statsreport_contentHTML'] = 'Bonjour,<br /><br />Vous trouverez ci-joint un rapport statistiques M@gistère de l\'année en cours sur la vue personnalisée "{$a->viewname}".<br />Si vous recevez ce rapport, c\'est parce que vous avez été inscrit à la liste des destinataires par un utilisateur de l\'académie de {$a->viewaca}.<br /><br />Cordialement.';

$string['view_buttongrp'] = 'Boutons d\'actions';
$string['view_createheaderform'] = 'Meta-administration : {$a}';
$string['view_createparcourssession'] = 'Statistiques sur la participation des utilisateurs aux parcours de la vue';
$string['view_delete'] = 'Supprimer la vue';
$string['view_modify'] = 'Modifier la vue';
$string['view_resume'] = 'Résumé de la vue';
$string['view_scourses'] = 'Parcours sélectionnés';
$string['view_disp_bycourse'] = 'Parcours';
$string['view_disp_bycoursebyaca'] = 'Parcours par académie';
$string['view_disp_classical'] = 'Classique';
$string['view_no'] = 'Non';
$string['view_yes'] = 'Oui';
$string['view_monthly'] = 'Mensuel';
$string['view_weekly'] = 'Hebdomadaire';
$string['view_monday'] = 'Lundi';
$string['view_tuesday'] = 'Mardi';
$string['view_wednesday'] = 'Mercredi';
$string['view_thursday'] = 'Jeudi';
$string['view_friday'] = 'Vendredi';
$string['view_saturday'] = 'Samedi';
$string['view_sunday'] = 'Dimanche';

$string['warning_archive_message'] = 'Information : les données que vous exploitez proviennent d’une base de données d’archive';

$string['metaadmin:statsparticipants_manageviews'] = 'Autoriser à gérer les différentes vues';
$string['metaadmin:statsparticipants_viewallacademies'] = 'Autoriser à voir les données de toutes les académies/institutions';
$string['metaadmin:statsparticipants_viewownacademy'] = 'Autoriser à voir les données de sa propre académie/institution';