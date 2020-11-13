<?php

defined('MOODLE_INTERNAL') || die();

$string['modulename'] = 'Plugin local ressources centralisées';
$string['modulenameplural'] = 'Plugin local ressources centralisées';
$string['pluginadministration'] = 'Administration';
$string['pluginname'] = 'Plugin local ressources centralisées';

$string['local_cr_return_button_label'] = "Retour";
$string['local_cr_jtable_title'] = 'Résultat de la recherche';
$string['local_cr_add_resource_button_label'] = "Ajouter";

$string['local_cr_add_resource_save_button_label'] = "Enregistrer";
$string['local_cr_add_resource_save_return_button_label'] = 'Enregistrer et revenir au parcours';
$string['local_cr_add_resource_cancel_button_label'] = 'Annuler';

$string['local_cr_add_resource_header_label'] = 'Ressources';
$string['local_cr_add_resource_title_label'] = 'Titre';
$string['local_cr_add_resource_description_label'] = 'Description';
$string['local_cr_add_resource_video_options_label'] = 'Option vidéo';
$string['local_cr_add_resource_audio_options_label'] = 'Option audio';
$string['local_cr_add_resource_select_pos_thumbnail_label'] = 'Position de la miniature (en seconde)';
$string['local_cr_add_resource_subtitle_file'] = 'Ajout d\'un fichier sous-titre';
$string['local_cr_add_resource_subtitle_file_help'] = 'Le format .srt est accepté. 
Pour en savoir plus, consulter l\'article du Wiki M@gistère suivant : 
<a href= "https://wiki.magistere.education.fr/index.php?title=Ajouter_les_sous-titres_%C3%A0_une_vid%C3%A9o" target = "_blank">Intégrer les sous-titres à une vidéo</a>';
$string['local_cr_add_resource_chapter_file'] = 'Ajout d\'un fichier chapitre';
$string['local_cr_add_resource_chapter_file_help'] = 'Le format .vtt est accepté. 
Pour en savoir plus, consulter l\'article du Wiki M@gistère suivant : 
 <a href= "https://wiki.magistere.education.fr/index.php?title=Ajouter_le_chapitrage_%C3%A0_une_vid%C3%A9o" target = "_blank">Intégrer le chapitrage à une vidéo</a>';
$string['local_cr_add_resource_type_header'] = 'Type de ressources';
$string['local_cr_add_resource_explain_text'] = "Précisez s'il s'agit d'une ressource (Exemple : une vidéo, image, son, document, ou archive)
				ou d'une activité multimédia (Exemple : Diaporama, animation, activité interactive)";
$string['local_cr_add_resource_video_warning_text'] = "Votre fichier vidéo fera l'objet d'un traitement afin d'optimiser son format pour une compatibilité optimale avec tous les navigateurs.<br/>
Si votre vidéo est dans un format HD, deux flux seront créés, un en SD et un en HD selon la qualité de connexion des visiteurs.<br/>
Cette opération aura lieu dans la nuit prochaine pour ne pas perturber la plateforme.";
$string['local_cr_add_resource_audio_warning_text'] = "Votre fichier audio fera l'objet d'un traitement afin d'optimiser son format pour une compatibilité optimale avec tous les navigateurs.<br/>
Cette opération aura lieu dans la nuit prochaine pour ne pas perturber la plateforme.";
$string['local_cr_add_resource_type_resource_label'] = 'Ressource';
$string['local_cr_add_resource_type_multimedia_file_label'] = 'Activité multimédia';
$string['local_cr_add_resource_update_explain_text'] = 'Sélectionner un nouveau fichier mettra à jour la ressource en écrasant le fichier associé à cette dernière.';
$string['local_cr_add_resource_update_label'] = 'Fichier attaché à la ressource';
$string['local_cr_add_resource_validation_text'] = 'Ressource créée.';
$string['local_cr_domainrestricted_label'] = 'Ne pas partager cette ressource avec les autres domaines';
$string['local_cr_domainrestricted_label_help'] = 'Permet de limiter le référencement de cette ressource à votre domaine.<br/>
Si la case est cochée, les concepteurs des autres domaines ne pourront pas trouver cette ressources.<br/>
Cela n\'a pas de conséquence sur l\'accès à la ressource en lecture sur ce domaine ou un autre dans le cas de publication du parcours.';

$string['local_cr_manage_resource_header_label'] = 'Filtres de recherche';
$string['local_cr_manage_resource_creator_label'] = 'Créateur';
$string['local_cr_manage_resource_search_creator_label'] = 'Nom du créateur de la ressource';
$string['local_cr_manage_resource_creator_is_me_label'] = 'Uniquement les ressources dont je suis le créateur';
$string['local_cr_manage_resource_domain_restriction_header_label'] = 'Domaine d\'origine';
$string['local_cr_manage_resource_domain_restriction_label'] = 'Restreindre à ce domaine';
$string['local_cr_manage_resource_update_date_header_label'] = 'Date de modification';
$string['local_cr_manage_resource_update_date_label'] = 'A partir du';
$string['local_cr_manage_resource_keyword_header_label'] = 'Mots-clés';
$string['local_cr_manage_resource_keyword_search_label'] = 'Saisissez un ou plusieurs mots-clés';
$string['local_cr_update_resource_validation_text'] = 'Ressource mise à jour.';
$string['local_cr_manage_resource_resource_type_header_label'] = 'Type de ressource';
$string['local_cr_manage_resource_video_resource_label'] = 'Vidéo';
$string['local_cr_manage_resource_sound_resource_label'] = 'Audio';
$string['local_cr_manage_resource_picture_resource_label'] = 'Image';
$string['local_cr_manage_resource_document_resource_label'] = 'Document';
$string['local_cr_manage_resource_archive_resource_label'] = 'Archive';
$string['local_cr_manage_resource_multimedia_activity_resource_label'] = 'Diaporama';
$string['local_cr_manage_resource_other_resource_label'] = 'Autre';

$string['local_cr_manage_resource_search_label'] = 'Rechercher';
$string['local_cr_manage_resource_reset_label'] = 'Effacer tous les filtres';

$string['centralizedresources:manage'] = 'Accès à l\'interface de gestion des ressources centralisées';
$string['centralizedresources:addresource'] = 'Ajouter une ressource';
$string['centralizedresources:editownressource'] = 'Editer les ressources dont je suis le créateur';
$string['centralizedresources:editressource'] = 'Editer les ressources quelque soit le créateur';

$string['closepreview'] = 'Fermer la prévisualisation';
$string['previewtitle'] = 'Prévisualisation: {$a}';

$string['downloadlink'] = 'Télécharger la ressource';

$string['privacy:metadata:firstname'] = 'Prenom de l\'utilisateur';
$string['privacy:metadata:lastname'] = 'Nom de l\'utilisateur';
$string['privacy:metadata:email'] = 'Email de l\'utilisateur';
$string['privacy:metadata:numen'] = 'Numen de l\'utilisateur';
$string['privacy:metadata:academie'] = 'Ac&demie de l\'utilisateur';

$string['privacy:metadata:cr_contributor'] = 'Table d un contributeur d une ressource centralisée';
