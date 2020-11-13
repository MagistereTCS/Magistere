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
 * autogroup local plugin
 *
 * These strings are used throughout the user interface and can
 * be overridden by the user through the language customisation
 * tool.
 *
 * @package    local
 * @subpackage autogroup
 * @author     Mark Ward (me@moodlemark.com)
 * @date       December 2014
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname']  = 'Auto Group';

// Course Settings
$string['actions'] = 'Actions';

$string['coursesettings']  = 'Auto Groups';
$string['coursesettingstitle']  = 'Auto Groups: {$a}';

$string['autogroupdescription'] = '"Auto Groups" assignera automatiquement vos utilisateurs à des groupes dans le cadre d\’un cours en fonction des informations contenues dans leur profil d\’utilisateur.';
$string['newsettingsintro'] = 'Pour commencer à grouper vos utilisateurs, sélectionnez simplement un champ de profil dans l’option "Grouper par" ci-dessous et cliquez sur "Enregistrer les changements".';
$string['updatesettingsintro'] = 'Ce cours regroupe déjà les utilisateurs avec {$a} règle(s). Vous pouvez modifier ou supprimer ces ensembles de règles, ou en ajouter de nouvelles au parcours.';

$string['dontgroup'] = "Dégrouper les utilisateurs.";
$string['cleanupold'] = 'Nettoyer les anciens groupes?';

$string['set_type'] = 'Type de paramètrage';
$string['set_groups'] = 'Nombre de groupes';
$string['set_roles'] = 'Rôles concernés';
$string['set_groupby'] = 'Groupe';

$string['confirmdelete'] = 'Êtes-vous sûr de vouloir supprimer ce paramétrage?';

$string['create'] = 'Créer une nouvelle règle :';

// Admin Settings
$string['addtonewcourses'] = 'Ajouter aux nouveaux parcours';
$string['addtorestoredcourses'] = 'Ajouter aux parcours restaurés';
$string['defaults'] = 'paramètres par défaut';
$string['defaultroles'] = 'Rôles concernés - par défaut';
$string['enabled'] = 'Autorisé';
$string['general'] = 'Configuration générale';
$string['events'] = 'déclencheur';
$string['events_help'] = 'Personnaliser les événements écoutés par AutoGroup pour améliorer les performances du site et adapter le comportement à l’utilisation de celui-ci';
$string['listenforrolechanges'] = 'Attribution de Rôle';
$string['listenforrolechanges_help'] = 'Écoutez les nouvelles affectations de rôles ou les changements apportés aux affectations de rôles dans un parcours.';
$string['listenforuserprofilechanges'] = 'Profils utilisateur';
$string['listenforuserprofilechanges_help'] = 'Écoutez les modifications de profil d’un utilisateur pouvant avoir une incidence sur l’appartenance à un groupe.';
$string['listenforuserpositionchanges'] = 'User Position';
$string['listenforuserpositionchanges_help'] = 'Écoutez les changements de poste, comme une nouvelle organisation ou une nouvelle affectation à un poste.';
$string['listenforgroupchanges'] = 'Groupes';
$string['listenforgroupchanges_help'] = 'Écoutez les modifications apportées aux groupes sur un parcours. Cela peut prévenir les problèmes causés par des modifications manuelles, mais ralentira également AutoGroup qui double ses propres actions.';
$string['listenforgroupmembership'] = 'Membre de groupe';
$string['listenforgroupmembership_help'] = 'Écoutez les changements apportés aux membres du groupe. Cela peut prévenir les problèmes causés par des modifications manuelles, mais ralentira également AutoGroup qui double ses propres actions.';

// Capabilities
$string['autogroup:managecourse']  = 'gérer les paramètres autogroup du parcours';

// Sort profile field options
$string['auth'] = "Méthode d'authentification";
$string['department'] = "Département";
$string['institution'] = "Institution";
$string['lang'] = "Langue";

// Sort module names
$string['sort_module:profile_field'] = 'Champ du profil';
$string['sort_module:user_info_field'] = 'Champ personnalisé du profil';
