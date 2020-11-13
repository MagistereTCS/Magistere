<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Strings for component 'block_course_migration', language 'en', branch 'MOODLE_20_STABLE'
 *
 * @package   block_course_migration
 * @copyright 2017 TCS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Migration de parcours';
$string['warning_message'] = "Ce bloc ne fonctionne que sur une page de parcours.";

$string['edit'] = 'Edition du sommaire';
$string['hidesection'] = 'Cacher la section';
$string['showsection'] = 'Afficher la section';

$string['deletesection'] = 'Supprimer la section';

$string['automaticprocessconversionname'] = 'Migration de parcours automatique';
$string['processconversionname'] = 'Migration de parcours';

$string['topicsaccesslabel'] = 'Accéder à la copie au format standard';
$string['modularaccesslabel'] = 'Accéder à la copie au format modulaire';

$string['topicserrorlabel'] = 'Échec. L’équipe technique vient d’être contactée, relancer la convertion ? (Standard)';
$string['modularerrorlabel'] = 'Échec. L’équipe technique vient d’être contactée, relancer la convertion ? (Modulaire)';

$string['topicswiplabel'] = 'Conversion en cours. (Standard)';
$string['modularwiplabel'] = 'Conversion en cours. (Modulaire)';

$string['topicsplannedlabel'] = 'Conversion planifiée. (Standard)';
$string['modularplannedlabel'] = 'Conversion planifiée. (Modulaire)';

$string['topicsconvlabel'] = 'Convertir le parcours au format standard';
$string['modularconvlabel'] = 'Convertir le parcours au format modulaire';

$string['keepconvertedcourseanddeleteoldcourse'] = 'Conserver le parcours converti et détruire le parcours original';
$string['removeconvertedcourse'] = 'Détruire le parcours converti qui ne me convient pas';
$string['validateconversion'] = 'Valider la conversion (automatique dans {$a} jour(s))';

$string['validationsubject'] = 'Validation de parcours sur M@gistère';

$string['validationmessageoktxt'] = 'Les demandes de validation pour les parcours suivants ont bien été effectuées :\n\n{$a}';
$string['validationmessageokhtml'] = 'Les demandes de validation pour les parcours suivants ont bien été effectuées :<br/><br/>{$a}';

$string['validationmessagefailtxt'] = 'Les demandes de validation suivantes n\'ont pas pu être effectuées :\n\n{$a}';
$string['validationmessagefailhtml'] ='Les demandes de validation suivantes n\'ont pas pu être effectuées :<br/><br/>{$a}';

$string['failedstatusotherlabel'] = 'Relance automatique suite à un échec (questionnaire/quiz)';

$string['course_migration:addinstance'] = 'Autoriser l\'ajout d\'une instance de bloc Migration de Parcours';
$string['course_migration:convertcourse'] = 'Autoriser la conversion d\'un parcours';
$string['course_migration:removeflexpagecourse'] = 'Autoriser la suppression d\'un parcours type Flexpage';
$string['course_migration:showmigrationblock'] = 'Autoriser la visibilité du bloc Migration de Parcours';