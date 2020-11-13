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
 * Privacy Subsystem implementation for mod_choice.
 *
 * @package    mod_choice
 * @category   privacy
 * @copyright  2018 Jun Pataleta
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_favoritecourses\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\deletion_criteria;
use core_privacy\local\request\helper;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

/**
 * Implementation of the privacy subsystem plugin provider .
 */
class provider implements
        // This plugin stores personal data.
        \core_privacy\local\metadata\provider,

        // This plugin is a core_user_data_provider.
        \core_privacy\local\request\plugin\provider,

        // This plugin is capable of determining which users have data within it.
        \core_privacy\local\request\core_userlist_provider {
    /**
     * Return the fields which contain personal data.
     *
     * @param collection $items a reference to the collection to use to store the metadata.
     * @return collection the updated collection of metadata items.
     */
    public static function get_metadata(collection $items) : collection {
        //Ajoute une description de la table local_coursefavorite et de ses attributs dans l'objet $items de type collention
        //Cet objet sera alors utilisé afin d'afficher toutes les tables possédants des données utilisateurs
        $items->add_database_table(
            //Nom de la table
            'local_coursefavorite',
            //Description de chaque attribut de la table, la valeur à droite de la flèche est l'identifiant du
            //texte a afficher dans le plugin gérant les langues.
            [
                'courseid' => 'privacy:metadata:courseid',
                'userid' => 'privacy:metadata:userid',
                'email' => 'privacy:metadata:email',
                'username' => 'privacy:metadata:username',
                'timecreated' => 'privacy:metadata:timecreated',
            ],
            //Valeur de l'identifiant afin d'obtenir la description de la table
            'privacy:metadata:favoritecourses'
        );
        return $items;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid the userid.
     * @return contextlist the list of contexts containing user info for the user.
     */
    public static function get_contexts_for_userid(int $userid) : contextlist {
        //Préparation de la reqête qui récupère le contexte d'un utilisateur s'il possède des cours favoris
        $sql = "
            SELECT ctx.id FROM {local_favoritecourses} fc 
            JOIN {context} ctx ON ctx.instanceid = fc.courseid AND ctx.contextlevel = :userlevel
            WHERE fc.userid = :userid";
        //Préparation des paramètre de la requète. Ici on est dans un contexte utilisateur et souhaite les contextes de l'utilisateur $userid
        $params = [
            'userlevel' => CONTEXT_USER,
            'userid' => $userid,
        ];
        //Creation d'une contextlist, qui stocke tous les contextes que nous avons récupéré avec la reqûete précédente
        $contextlist = new contextlist();
        //La méthode ci-dessous execute la requête préparée avec les paramètres que nous avons définis. Ces contextes sont
        //ajoutés dans notre contextlist
        $contextlist->add_from_sql($sql, $params);
        return $contextlist;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param   userlist    $userlist   The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();
        if (!$context instanceof \context_course) {
            return;
        }

        // Fetch all choice answers.
        $sql = "
                SELECT fc.userid
                FROM {course} c 
                JOIN {local_favoritecourses} fc ON c.id = fc.courseid
                WHERE c.id = :cid";

        $params = [
            'cid'      => $context->instanceid,
        ];

        $userlist->add_from_sql('userid', $sql, $params);

    }

    /**
     * Export personal data for the given approved_contextlist. User and context information is contained within the contextlist.
     *
     * @param approved_contextlist $contextlist a list of contexts approved for export.
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        //récupération de la variable global gérant les appels en base de données
        global $DB;
        //La contextlist nous permet de récupérer l'id de l'utilisateur à exporter
        $userid = $contextlist->get_user()->id;
        //Nous récupérons ensuite son contexte
        $context = \context_user::instance($userid);
        //La ligne ci-dessous permet d'executer une requete SQL qui récupère toutes les lignes de la table local_favoritecourses
        //ayant pour userid l'id de l'utilisateur que l'on souhaite exporter
        $favoritecourses = $DB->get_records('local_favoritecourses', ['userid' => $userid]);

        if ($favoritecourses) {
            $favoritecoursesdata = [];
            //Pour chaque favoris
            foreach ($favoritecourses as $fav) {
                //Je créé un objet contenant les données du favoris. L'objet est ensuite ajouté à un tableau
                $favoritecoursesdata[] = (object) [
                    'courseid' => $fav->courseid,
                    'userid' => transform::user($fav->userid),
                    'email' => $fav->email,
                    'username' => $fav->username,
                    'timecreated' => transform::date($fav->timecreated)
                ];
            }
            //Cette méthode permet de générer une partie du document de l'export.
            //get_string permet de récupérer une traduction d'un champ.
            writer::with_context($context)->export_data([get_string('pluginname', 'local_favoritecourses')], (object) $favoritecoursesdata);
        }
    }


    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context the context to delete in.
     * @throws \dml_exception
     */
    public static function delete_data_for_all_users_in_context(\context $context) {

        global $DB;

        if (!$context instanceof \context_course) {
            return;
        }

        $DB->delete_records('local_favoritecourses', ['courseid' =>$context->instanceid]);

    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist a list of contexts approved for deletion.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        //récupération de la variable global gérant les appels en base de données
        global $DB;
        //S'il il n'y a pas de contextes, on ne fait rien
        if (empty($contextlist->count())) {
            return;
        }
        //La contextlist nous permet de récupérer l'id de l'utilisateur à exporter
        $userid = $contextlist->get_user()->id;
        //Pour chaque contexte dans la contextlist
        foreach ($contextlist->get_contexts() as $context) {
            //Je supprime l'entrée dans la table local_favoritecourses
            $DB->delete_records('local_favoritecourses', ['userid' => $userid , 'courseid' =>$context->instanceid]);
        }
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     * @throws \dml_exception
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();
        if (!$context instanceof \context_course) {
            return;
        }
        $userids = $userlist->get_userids();
        foreach ($userids as $userid) {
            $DB->delete_records('local_favoritecourses', ['userid' => $userid , 'courseid' =>$context->instanceid]);
        }
    }
}
