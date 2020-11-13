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
 * Web service for mod viaassign
 * @package    mod_viaassign
 * @subpackage db
 * @since      Moodle 2.4
 * @copyright  2012 Paul Charsley
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
$functions = array(

        'mod_viaassign_get_grades' => array(
                'classname'   => 'mod_viaassign_external',
                'methodname'  => 'get_grades',
                'classpath'   => 'mod/viaassign/externallib.php',
                'description' => 'Returns grades from the viaassignment',
                'type'        => 'read'
        ),

        'mod_viaassign_get_viaassignments' => array(
                'classname'   => 'mod_viaassign_external',
                'methodname'  => 'get_viaassignments',
                'classpath'   => 'mod/viaassign/externallib.php',
                'description' => 'Returns the courses and viaassignments for the users capability',
                'type'        => 'read'
        ),

        'mod_viaassign_get_submissions' => array(
                'classname' => 'mod_viaassign_external',
                'methodname' => 'get_submissions',
                'classpath' => 'mod/viaassign/externallib.php',
                'description' => 'Returns the submissions for viaassignments',
                'type' => 'read'
        ),

        'mod_viaassign_get_user_flags' => array(
                'classname' => 'mod_viaassign_external',
                'methodname' => 'get_user_flags',
                'classpath' => 'mod/viaassign/externallib.php',
                'description' => 'Returns the user flags for viaassignments',
                'type' => 'read'
        ),

        'mod_viaassign_set_user_flags' => array(
                'classname'   => 'mod_viaassign_external',
                'methodname'  => 'set_user_flags',
                'classpath'   => 'mod/viaassign/externallib.php',
                'description' => 'Creates or updates user flags',
                'type'        => 'write',
                'capabilities' => 'mod/viaassign:grade'
        ),

        'mod_viaassign_get_user_mappings' => array(
                'classname' => 'mod_viaassign_external',
                'methodname' => 'get_user_mappings',
                'classpath' => 'mod/viaassign/externallib.php',
                'description' => 'Returns the blind marking mappings for viaassignments',
                'type' => 'read'
        ),

        'mod_viaassign_lock_submissions' => array(
                'classname' => 'mod_viaassign_external',
                'methodname' => 'lock_submissions',
                'classpath' => 'mod/viaassign/externallib.php',
                'description' => 'Prevent students from making changes to a list of submissions',
                'type' => 'write'
        ),

        'mod_viaassign_unlock_submissions' => array(
                'classname' => 'mod_viaassign_external',
                'methodname' => 'unlock_submissions',
                'classpath' => 'mod/viaassign/externallib.php',
                'description' => 'Allow students to make changes to a list of submissions',
                'type' => 'write'
        ),

        'mod_viaassign_save_grade' => array(
                'classname' => 'mod_viaassign_external',
                'methodname' => 'save_grade',
                'classpath' => 'mod/viaassign/externallib.php',
                'description' => 'Save a grade update for a single student.',
                'type' => 'write'
        ),

        'mod_viaassign_save_grades' => array(
                'classname' => 'mod_viaassign_external',
                'methodname' => 'save_grades',
                'classpath' => 'mod/viaassign/externallib.php',
                'description' => 'Save multiple grade updates for an viaassignment.',
                'type' => 'write'
        ),

        'mod_viaassign_save_user_extensions' => array(
                'classname' => 'mod_viaassign_external',
                'methodname' => 'save_user_extensions',
                'classpath' => 'mod/viaassign/externallib.php',
                'description' => 'Save a list of viaassignment extensions',
                'type' => 'write'
        ),

);