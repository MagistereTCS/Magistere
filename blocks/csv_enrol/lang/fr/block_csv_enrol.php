<?php

//  BRIGHTALLY CUSTOM CODE
//  Coder: Ted vd Brink
//  Contact: ted.vandenbrink@brightalley.nl
//  Date: 6 juni 2012
//
//  Description: Enrols users into a course by allowing a user to upload an csv file with only email adresses
//  Using this block allows you to use CSV files with only emailaddress
//  After running the upload you can download a txt file that contains a log of the enrolled and failed users.

//  License: GNU General Public License http://www.gnu.org/copyleft/gpl.html

$string['manageuploads'] = 'Gérer les fichiers uploadés';
$string['pluginname'] = 'Inscrire les utilisateurs via un CSV';

$string['alreadyenrolled'] = 'L\'utilisateur {$a} est déjà inscrit à ce cours.';
$string['csv_enrol:addinstance'] = 'Autoriser l\'ajout du bloc Inscription par CSV';
$string['csvenrol'] = 'Inscription par CSV';
$string['deleteduser'] = 'L\'utilisateur {$a} a été supprimé de ce cours.';
$string['deleteusers'] = 'Retirer les utilisateurs déjà inscrits n\'étant pas présents dans le fichier chargé.';
$string['description'] = 'Vous pouvez uploader votre fichier CSV contenant des adresses email ou des users moodle ici, 
		afin qu\'ils soient inscrits au cours "{$a}".';
$string['done'] = 'Inscription terminée.';
$string['downloadcomplexecsvfiletemplate'] = 'Télécharger le modèle d\'importation complexe';
$string['downloadsimplecsvfiletemplate'] = 'Télécharger le modèle d\'importation simple';
$string['emailnotfound'] = 'L\'adresse email {$a} n\'a pas été trouvée.';
$string['enrolling'] = 'Inscriptions en cours...';
$string['enrollinguser'] = 'Enregistre de l\'utilisateur {$a}.';
$string['enrolmentlog'] = 'Log d\'inscription:';
$string['erroremailentry'] = 'Erreur sur la saisie des emails.';
$string['errorformatfile'] = 'Ce fichier n\'a pas un format reconnu.';
$string['errornocsvfile'] = 'Ce fichier n\'est pas un fichier CSV.';
$string['errornogroupentry'] = 'Un ou plusieurs groupes présent dans le fichier csv n\'existent pas. Ils seront donc créés après la validation du formulaire.';
$string['errorroleentry'] = 'Erreur sur la saisie des rôles.';
$string['previewcsvfile'] = 'Prévisualisation de votre fichier CSV.';
$string['previewcsvfile_description'] = 'Ceci est une prévisualisation des 10 premiers utilisateurs qui seront ajoutés ou mises à jour 
		dans ce cours. Pour valider le fichier, cliquer sur le bouton Enregistrer';
$string['previewcsvfile_nogroup'] = 'Aucun groupe n\'a été sélectionné pour cet importation d\'utilisateurs. Ils seront tout même ajoutés mais ne seront pas rattachés à un groupe';
$string['resultfiles'] = 'Résultat de vos inscriptions par CSV:';
$string['rolenotfound'] = 'Le rôle pour l\'adresse email {$a} n\'a pas été trouvée.';
$string['status'] = 'Inscription terminée. Résultat: {$a->success} inscrit ou déjà inscrit, {$a->failed} a échoué.';
$string['title'] = 'Inscrire l\'utilisateur à {$a}';
$string['uploadcsv'] = 'Uploadez votre CSV ici:';
$string['uploadusers'] = 'Inscrire des utilisateurs';
$string['uploadusers_help'] = 'Vous pouvez inscrire à ce cours de nouveaux utilisateurs à l\'aide d\'un fichier CSV. 2 types de fichier sont possibles : </br>
		<ul><li><b>- Simple : </b> Ce type de fichier csv contient uniquement une liste d\'email sur une colonne. </li>
		<li><b>- Complexe : </b> Ce type de fichier contient les colonnes Email, Rôle, Groupe 1, Groupe 2, etc. A chaque ligne, vous devez préciser 
		obligatoirement l\'email et le rôle de la personne en revanche les groupes sont facultatifs. Le rôle doit être écrit de manière littérale (ex: participant). 
		A noter également que vous pouvez attribuer un ou plusieurs groupes. Dans le 2ème cas, vous devez séparer les groupes en plusieurs colonnes. 
		Si le groupe n\'existe pas dans le parcours, il sera créé avec le nom que vous aurez écrit dans votre fichier.</li></ul></br>
		Vous avez également la possibilité de télécharger ci-dessous les 2 templates pour vous aider à constituer correctement votre fichier csv.';
$string['uploadusersin'] = 'Inscrire les utilisateurs dans {$a}.';

