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

$string['manageuploads'] = 'Manage uploaded files';
$string['pluginname'] = 'Enrol users with CSV';

$string['alreadyenrolled'] = 'User {$a} already enrolled into this course.';
$string['csv_enrol:addinstance'] = 'Autoriser l\'ajout du bloc Inscription par CSV';
$string['csvenrol'] = 'Enrol users';
$string['deleteduser'] = 'The user {$a} has been deleted into this course.';
$string['deleteusers'] = 'Remove the already registered users who are not present in the loaded file';
$string['description'] = 'You can upload your CSV file with email adresses of Moodle users here, 
		so that they can be enrolled into the course "{$a}".';
$string['done'] = 'Enrolling done.';
$string['downloadcomplexecsvfiletemplate'] = 'Download Complex File Template';
$string['downloadsimplecsvfiletemplate'] = 'Download Simple File Template';
$string['emailnotfound'] = 'Could not find email address {$a}.';
$string['enrolling'] = 'Enrolling users...';
$string['enrollinguser'] = 'Enrolling user {$a}.';
$string['enrolmentlog'] = 'Log of enrolment:';
$string['erroremailentry'] = 'Error on entering emails.';
$string['errorformatfile'] = 'This file has not a recognizable format.';
$string['errornocsvfile'] = 'This file is not a CSV file.';
$string['errornogroupentry'] = 'One or several groups present in the csv file does not exist. They will be created after the form validation.';
$string['errorroleentry'] = 'Error on entering roles.';
$string['previewcsvfile'] = 'Preview of your CSV file.';
$string['previewcsvfile_description'] = 'This is a preview of the first 10 users that will be added or updated
		in this course. To validate the file, click the Save button.';
$string['previewcsvfile_nogroup'] = 'No group has been selected for users in th csv file. They will all even added but will not be attached to a group.';
$string['resultfiles'] = 'Result of your CSV enrolment:';
$string['rolenotfound'] = 'Could not find the role for the email address {$a}.';
$string['status'] = 'Enrolling done. Result: {$a->success} succeeded or already enrolled, {$a->failed} failed.';
$string['title'] = 'Enrol users into {$a}';
$string['uploadcsv'] = 'Upload your CSV here:';
$string['uploadusers'] = 'Assign users';
$string['uploadusers_help'] = 'Vous pouvez inscrire à ce cours de nouveaux utilisateurs à l\'aide d\'un fichier CSV. 2 types de fichier sont possibles : </br>
		<ul><li><b>- Simple : </b> Ce type de fichier csv contient uniquement une liste d\'email sur une colonne. </li>
		<li><b>- Complexe : </b> Ce type de fichier contient les colonnes Email, Rôle, Groupe 1, Groupe 2, etc. A chaque ligne, vous devez préciser 
		obligatoirement l\'email et le rôle de la personne en revanche les groupes sont facultatifs. Le rôle doit être écrit de manière littérale (ex: participant). 
		A noter également que vous pouvez attribuer un ou plusieurs groupes. Dans le 2ème cas, vous devez séparer les groupes en plusieurs colonnes. 
		Si le groupe n\'existe pas dans le parcours, il sera créé avec le nom que vous aurez écrit dans votre fichier.</li></ul></br>
		Vous avez également la possibilité de télécharger ci-dessous les 2 templates pour vous aider à constituer correctement votre fichier csv.';
$string['uploadusersin'] = 'Assign users in {$a}.';