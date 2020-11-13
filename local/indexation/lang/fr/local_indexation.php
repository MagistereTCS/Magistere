<?php

/**
 * indexation local plugin
 *
 * Language file.
 *
 * @package    local
 * @subpackage autogroup
 * @author     TCS
 * @date       Aout 2019
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Indexation';

$string['coursefullname_label'] = 'Nom complet du parcours';
$string['intitule_label'] = 'Intitulé';
$string['description_label'] = 'Description';
$string['objectif_label'] = 'Objectif';
$string['achievement_mark'] = 'Afficher l\'indicateur d\'achèvement';
$string['entree_metier_label'] = 'Entrée dans le métier';
$string['certificat_label'] = 'Formation certifiante (facultatif)';
$string['keyword_label'] = 'Mots clés';
$string['thumbnail_label'] = 'Vignette (facultatif)';
$string['video_label'] = 'Vidéo de présentation (facultatif)';
$string['domaine_label'] = 'Domaine';
$string['domaine_label_help'] = 'Champs obligatoire pour la validation de l\'indexation.';
$string['collection_label'] = 'Collection';
$string['collection_label_help'] = 'La collection permet de préciser le type de formation envisagée. 
Les différentes actions qui vous seront proposées pour gérer le cycle de vie de votre parcours/formation dépendront du choix effectué sur la collection. 
Par exemple, la collection « autoformation » ou « e-reseau » n’impose pas de date de fin contrairement à une formation accompagnée par des formateurs.';

$string['choose_thumbnail_label'] = 'Choisir une vignette';
$string['upload_thumbnail_label'] = 'Déposer une vignette';
$string['thumbnail_group_label'] = 'Vignette <em>(facultatif)</em>';

$string['choose_video_label'] = 'Choisir une vidéo';
$string['upload_video_label'] = 'Déposer une vidéo';
$string['video_group_label'] = 'Vidéo de présentation <em>(facultatif)</em>';

$string['choose_certificat_label'] = 'Type de certification';
$string['choose_collection_label'] = 'Choix de la collection';
$string['choose_domain_label'] = 'Choix du domaine';

$string['temps_a_distance_label'] = 'Temps à distance';
$string['temps_a_distance_label_help'] = 'Champs obligatoire pour la validation de l\'indexation.';
$string['temps_en_presence_label'] = 'Temps en présence';
$string['temps_en_presence_label_help'] = 'Champs obligatoire pour la validation de l\'indexation.';
$string['public_cible_label'] = 'Public cible';
$string['public_cible_label_help'] = 'Champs obligatoire pour la validation de l\'indexation.';
$string['accompagnement_label'] = 'Accompagnement (250 caractères maximum)';
$string['error_accompagnement'] = 'Erreur : le texte dépasse les 250 caractères maximum.';
$string['rythme_formation_label'] = 'Rythme de la formation<br/>ex : 1h30 par semaine pendant 3 semaines';
$string['startdate_label'] = 'Date de début';
$string['startdate_label_help'] = 'Champs obligatoire pour la validation de l\'indexation.';
$string['enddate_label'] = 'Date de fin (facultatif)';
$string['enddate_label_help'] = 'Champs obligatoire pour la validation de l\'indexation.';

$string['organisme_title'] = 'Organisme de formation responsable';
$string['contact_title'] = 'Contact';
$string['mail_resp_label'] = 'Adresse email du responsable de la formation';
$string['origine_label'] = 'Origine du parcours';
$string['academie_label'] = 'Académie';
$string['departement_label'] = 'Département';
$string['espe_label'] = 'Espé';
$string['validate_by_label'] = 'Validé par';
$string['authors_label'] = 'Auteurs';
$string['authors_label_help'] = 'Champs obligatoire pour la validation de l\'indexation.';
$string['year_label'] = 'Année';
$string['year_label_help'] = 'Champs obligatoire pour l\'identifiant semi-automatique.

La valeur par défaut est l\'année courante';
$string['code_label'] = 'Origine';
$string['code_label_help'] = 'Champs obligatoire pour l\'identifiant semi-automatique.

La valeur par défaut est celle de votre académie.';
$string['intitule_label'] = 'Intitulé';
$string['intitule_label_help'] = 'Champs obligatoire pour l\'identifiant semi-automatique.

La valeur par défaut est l\'identifiant du parcours';
$string['version_label'] = 'Version';
$string['version_label_help'] = 'Champs obligatoire pour l\'identifiant semi-automatique.';
$string['matricule_label'] = 'Matricule';
$string['currentnote_label'] = 'Notes de la version en cours :';
$string['oldnote_label'] = 'Notes de la version {$a->version} ({$a->date})';


$string['thumbnail_bad_ratio'] = 'La vignette n\'est pas au format 16:9.';
$string['add_thumbnail_confirmation'] = 'La vignette a bien été ajoutée à l\'indexation';

$string['offer_title'] = 'Offre de formation';
$string['domain_title'] = 'Domaine de formation';

$string['general_tab'] = 'Général';
$string['organisme_tab'] = 'Organisme responsable';
$string['detail_tab'] = 'Détails de l\'action de formation';
$string['version_tab'] = 'Version';

$string['indexation'] = "Indexation";
$string['indexation_title'] = "Indexation de la formation";

$string['label_year'] = "2 caractères obligatoires";
$string['label_intitule'] = "15 caractères maximum";

$string['automaticprocessconversionname'] = "Conversion automatique des parcours";
$string['notification_hub_delete'] = "Supprimé - Donnéees du Hub (Indexation & Publication).";
$string['notification_indexation_delete'] = "Supprimé - Indexation du parcours";

$string['thumbnail_bad_extension'] = "Extension non autorisée.";

$string['indexation:index'] = 'Autoriser l\'accès à la l\'indexation d\'un parcours';
$string['default_description'] = 'Vignette du parcours "{$a}"';
$string['default_description_video'] = 'Vidéo de présentation du parcours "{$a}"';