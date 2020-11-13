<?php
// This file is part of mod_publication for Moodle - http://moodle.org/
//
// It is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// It is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * lang/fr/publication.php
 *
 * @package       mod_publication
 * @author        Andreas Hruska (andreas.hruska@tuwien.ac.at)
 * @author        Katarzyna Potocka (katarzyna.potocka@tuwien.ac.at)
 * @author        Philipp Hager (office@phager.at)
 * @author        Andreas Windbichler
 * @copyright     2014 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['modulename'] = 'Dossier participant';
$string['pluginname'] = 'Dossier participant';
$string['modulename_help'] = 'Le dossier participant offre les fonctions suivantes :

* Les participants peuvent déposer des documents et les rendre disponibles à d\'autres participants immédiatement, ou juste après les avoir vérifié et avoir donné leur autorisation.
* Un devoir peut être choisi comme base pour un dossier participant. Le formateur peut décider quels documents du devoir sont visibles ou non pour tous les participants. Les formateurs peuvent aussi laisser les participants choisir si leurs propres documents devraient être visibles aux autres.';
$string['modulenameplural'] = 'Dossiers participant';
$string['pluginadministration'] = 'Gestion du dossier participant';
$string['publication:addinstance'] = 'Ajouter un nouveau dossier participant';
$string['publication:view'] = 'Voir le dossier participant';
$string['publication:upload'] = 'Ajouter un fichier au dossier participant';
$string['publication:approve'] = 'Décider si les fichiers devraient être visibles pour chaque participant';
$string['publication:grantextension'] = 'Prolongation de la permission';
$string['name'] = 'Nom du dossier participant';
$string['obtainstudentapproval'] = 'Obtenir une autorisation';
$string['saveapproval'] = 'Sauvegarder l\'autorisation';
$string['configobtainstudentapproval'] = 'Les documents seront visibles après l\'autorisation du participant.';
$string['hideidnumberfromstudents'] = 'Cacher le numéro d\'identification';
$string['hideidnumberfromstudents_desc'] = 'Cacher la colonne du numéro d\'identification dans le tableau Fichiers Publiques pour les participants';
$string['obtainteacherapproval'] = 'Autorisé par défaut';
$string['configobtainteacherapproval'] = 'Par défaut, les documents des participants sont visibles à tous les autres participants.';
$string['maxfiles'] = 'Nombre maximum de pièces jointes';
$string['configmaxfiles'] = 'Nombre maximum de pièces jointes autorisé par défaut par utilisateurs.';
$string['maxbytes'] = 'Taille maximale de la pièce jointe';
$string['configmaxbytes'] = 'Taille maximale par défaut pour tous les fichiers dans le dossier participant.';

$string['reset_userdata'] = 'Toutes les données';

// Strings from the File  mod_form.
$string['autoimport'] = 'Synchronisation automatique avec le devoir';
$string['autoimport_help'] = 'Si activé, les nouvelles contributions correspondantes au devoir seront automatiquement importées dans le module Publication. (Optionnel) L\'autorisation du participant sera requise pour chaque nouveau fichier.';
$string['configautoimport'] = 'Si vous préférez que les contributions des participants soient automatiquement importées dans le module Publication. Cette fonctionnalité peut être activée/désactivée séparément pour chaque instance du module Publication.';
$string['availability'] = 'Période de temps pour déposerun fichier/donner son autorisation';

$string['allowsubmissionsfromdate'] = 'de';
$string['allowsubmissionsfromdateh'] = 'Période de temps pour déposer un fichier/donner son autorisation';
$string['allowsubmissionsfromdateh_help'] = 'Vous pouvez déterminer la période pendant laquelle chaque participant peut déposer ou donner son autorisation de publier des fichiers. Durant cette période, les participants peuvent éditer leurs fichiers et retirer leur autorisation de publication à leur guise.';
$string['allowsubmissionsfromdatesummary'] = 'Ce devoir acceptera les contributions à partir du <strong>{$a}</strong>';
$string['allowsubmissionsanddescriptionfromdatesummary'] = 'Les détails du devoir et le formulaire de contribution seront accessibles pour <strong>{$a}</strong>';
$string['alwaysshowdescription'] = 'Toujours montrer la description';
$string['alwaysshowdescription_help'] = 'Si désactivé, la description du devoir ci-dessus sera uniquement visible pour les participants à partir de la date "Autoriser les contributions à partir du".';

$string['duedate'] = 'jusqu\'au';
$string['duedate_help'] = 'Lorsque le devoir est arrivé à échéance. Les contributions seront toujours autorisées après cette date mais elles seront marquées comme "en retard". Afin de prévenir toute contribution après échéance, utiliser le paramètre de date limite.';
$string['duedatevalidation'] = 'La date limite doit se situer après la date d\'autorisation de contributions.';

$string['cutoffdate'] = 'Date limite';
$string['cutoffdate_help'] = 'Si utilisée, le devoir n\'acceptera pas de contributions après cette date sans une prolongation.';
$string['cutoffdatevalidation'] = 'La date limite ne peut être située avant la date d\'échéance.';
$string['cutoffdatefromdatevalidation'] = 'La date limite doit être située après la date d\'autorisation de contributions.';

$string['mode'] = 'Mode';
$string['mode_help'] = 'Choisir si les participants peuvent déposer des documents dans le dossier ou si les documents d\'un devoir sont la source de ces fichiers.';
$string['modeupload'] = 'les participants peuvent déposer des documents';
$string['modeimport'] = 'prendre les documents d\'un devoir';

$string['courseuploadlimit'] = 'Limite de dépôt pour le parcours';
$string['allowedfiletypes'] = 'Types de fichiers autorisés (,)';
$string['allowedfiletypes_help'] = 'utiliser les types de fichiers autorisés pour déposer les tâches, les séparer par une virgule (,). ex : txt, jpg.
Si n\'importe quel type de fichier est autorisé, laisser le champ vide. Le filtre n\'est pas sensible à la casse, ainsi PDF est égal à pdf.';
$string['allowedfiletypes_err'] = 'Vérifier vos champs ! Séparateur ou extension de fichier non valide.';
$string['obtainteacherapproval_help'] = 'Choisir si les fichiers seront visibles ou non immédiatement après leur dépôt : <br><ul><li> oui - tous les fichiers seront visibles pour tout le monde juste après</li><li> non - les fichiers ne seront publiés qu\'après autorisation du formateur</li></ul>';
$string['assignment'] = 'Devoir';
$string['assignment_help'] = 'Choisir le devoir à partir duquel les fichiers seront importés. Actuellement, les tâches groupées ne sont pas supportées et ne sont donc pas sélectionnables.';
$string['obtainstudentapproval_help'] = 'Choisir si l\'autorisation des participants sera requise : <br><ul><li> oui - les fichiers seront visibles à tous uniquement si le participant a donné son autorisation. Le formateur peut sélectionner individuellement un participant ou un parcours pour lequel il souhaite obtenir une autorisation.</li><li> non - l\'autorisation du participant ne sera pas obtenue via Moodle. La visibilité du fichier dépend entièrement du formateur.</li></ul>';
$string['choose'] = 'Choisir...';
$string['importfrom_err'] = 'Vous devez choisir un devoir à partir duquel vous souhaitez importer.';

$string['warning_changefromobtainteacherapproval'] = 'Après avoir activé ce paramètre, tous les fichiers déposés (passés et à venir) seront visibles pour les autres participants. Vous pouvez manuellement rendre les fichiers invisbles à certaines personnes.';
$string['warning_changetoobtainteacherapproval'] = 'Après avoir désactivé ce paramètre, les fichiers déposés (passés et à venir) ne seront automatiquement plus visibles pour les autres participants. VOus pourrez sélectionner manuellement les fichiers à rendre visibles.';

$string['warning_changefromobtainstudentapproval'] = 'Si vous opérez ce changement, seul vous pourrez décider quels fichiers seront visibles par tous les participants. Leur autorisation ne sera pas requise. Tous les fichiers marqués comme autorisés deviendront visibles pour tous les participants indépendamment de leurs autorisations.';
$string['warning_changetoobtainstudentapproval'] = 'Si vous opérez ce changement, l\'autorisation des participants sera requise pour tous les fichiers marqués comme visibles. Les fichiers ne seront visibles qu\'après confirmation par le participant.';


// Strings from the File  mod_publication_grantextension_form.php.
$string['extensionduedate'] = 'Prolongation de la date d\'échéance';
$string['extensionnotafterduedate'] = 'La date de prolongation doit être située après la date d\'échéance';
$string['extensionnotafterfromdate'] = 'La date de prolongation doit être située après la date de début d\'autorisation de contributions';

// Strings from the File  index.php.
$string['nopublicationsincourse'] = 'Il n\'y a aucune instance de publication dans ce parcours.';

// Strings from the File  view.php.
$string['allowsubmissionsfromdate_upload'] = 'Possibilité de déposer à partir du';
$string['allowsubmissionsfromdate_import'] = 'Autorisation à partir du';
$string['duedate_upload'] = 'Possibilité de déposer jusqu\'au';
$string['duedate_import'] = 'Autorisation jusqu\'au';
$string['cutoffdate_upload'] = 'Date limite de dépôt jusqu\'au';
$string['cutoffdate_import'] = 'Date limite d\'autorisation jusqu\'au';
$string['extensionto'] = 'Prolongation jusqu\'au';
$string['assignment_notfound'] = 'Le devoir sélectionné pour importer est introuvable.';
$string['assignment_notset'] = 'Aucun devoir n\'a été sélectionné.';
$string['updatefiles'] = 'Mettre à jour les fichiers';
$string['updatefileswarning'] = 'Les fichiers d\'un participant individuel seront mis à jour dans le dossier participant avec sa contribution au devoir. Les fichiers déjà visibles seront aussi remplacés, s\'ils sont supprimés ou actualisés - les paramètres du participant, tout comme leur visibilité, ne se seront pas modifiés.';
$string['myfiles'] = 'Fichiers personnels';
$string['add_uploads'] = 'Ajouter des fichiers';
$string['edit_uploads'] = 'modifier/déposer des fichiers';
$string['edit_timeover'] = 'Les fichiers peuvent être modifiés uniquement pendant la période autorisée.';
$string['approval_timeover'] = 'Votre autorisation peut être modifiée uniquement pendant la période autorisée.';
$string['nofiles'] = 'Aucun fichier disponible';
$string['notice'] = 'Attention :';
$string['notice_uploadrequireapproval'] = 'Tous les fichiers déposés seront visibles uniquement après révision du formateur';
$string['notice_uploadnoapproval'] = 'Tous les fichiers seront immédiatement visibles pour tous après leur dépôt. Le formateur se réserve le droit de cacher des fichiers publiés à tout moment.';
$string['notice_importrequireapproval'] = 'Choisir si vos fichiers seront visibles par tout le monde.';
$string['notice_importnoapproval'] = 'The following files are visible to all.';
$string['teacher_pending'] = 'confirmation en cours';
$string['teacher_approved'] = 'visible (autorisé)';
$string['teacher_rejected'] = 'refusé';
$string['student_approve'] = 'autoriser';
$string['student_approved'] = 'autorisé';
$string['student_pending'] = 'non visible (non autorisé)';
$string['student_reject'] = 'rejeter';
$string['student_rejected'] = 'rejeté';
$string['visible'] = 'visible';
$string['hidden'] = 'caché';

$string['allfiles'] = 'Tous les fichiers';
$string['publicfiles'] = 'Fichiers publiques';
$string['downloadall'] = 'Télécharger une archive (ZIP) de tous les fichiers';
$string['optionalsettings'] = 'Options';
$string['entiresperpage'] = 'Participants affichés par page';
$string['nothingtodisplay'] = 'Aucune donnée à afficher';
$string['nofilestozip'] = 'Aucun fichier à ajouter à l\'archive';
$string['status'] = 'Status';
$string['studentapproval'] = 'Status'; // Previous 'Student approval'.
$string['studentapproval_help'] = 'La colonne status indique la réponse des participants pour la demande d\'autorisation :

* ? - autorisation en cours
* ✓ - autorisation accordée
* ✖ - autorisation refusée';
$string['teacherapproval'] = 'Autorisation';
$string['visibility'] = 'visible pour tous';
$string['visibleforstudents'] = 'visible par tous';
$string['visibleforstudents_yes'] = 'Les participants peuvent voir ce fichier';
$string['visibleforstudents_no'] = 'Ce fichier n\'est pas visible pour les participants';
$string['resetstudentapproval'] = 'Réinitialiser le status'; // Previous 'Reset student approval'.
$string['savestudentapprovalwarning'] = 'Êtes-vous sûr de vouloir sauvegarder ces changements ? Vous ne pourrez plus modifier le status une fois sauvegardé.';

$string['go'] = 'Aller';
$string['withselected'] = 'Pour les utilisateurs sélectionnés...';
$string['zipusers'] = "Télécharger une archive (ZIP)";
$string['approveusers'] = "Visible pour tous";
$string['rejectusers'] = "Non visible pour tous";
$string['grantextension'] = 'Prolongation de la permission';
$string['saveteacherapproval'] = 'Enregistrer l\'autorisation';
$string['reset'] = 'Annuler';

// Strings from the File  upload.php.
$string['guideline'] = 'visible pour tout le monde :';
$string['published_immediately'] = 'oui immédiatement, sans autorisation d\'un formateur';
$string['published_aftercheck'] = 'non, uniquement après l\'autorisation d\'un formateur';
$string['save_changes'] = 'Enregistrer les changements';

// Deprecated since Moodle 2.9!
$string['requiremodintro'] = 'La description de l\'activité est requise';
$string['configrequiremodintro'] = 'Désactiver cette option si vous ne voulez pas forcer les utilisateurs à entrer une description pour chaque activité.';