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
 * Definition of log events
 *
 * @package   mod_viaassign
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$logs = array(
    array('module' => 'viaassign', 'action' => 'add', 'mtable' => 'viaassign', 'field' => 'name'),
    array('module' => 'viaassign', 'action' => 'delete mod', 'mtable' => 'viaassign', 'field' => 'name'),
    array('module' => 'viaassign', 'action' => 'download all submissions', 'mtable' => 'viaassign', 'field' => 'name'),
    array('module' => 'viaassign', 'action' => 'grade submission', 'mtable' => 'viaassign', 'field' => 'name'),
    array('module' => 'viaassign', 'action' => 'lock submission', 'mtable' => 'viaassign', 'field' => 'name'),
    array('module' => 'viaassign', 'action' => 'reveal identities', 'mtable' => 'viaassign', 'field' => 'name'),
    array('module' => 'viaassign', 'action' => 'revert submission to draft', 'mtable' => 'viaassign', 'field' => 'name'),
    array('module' => 'viaassign', 'action' => 'submission statement accepted', 'mtable' => 'viaassign', 'field' => 'name'),
    array('module' => 'viaassign', 'action' => 'submit', 'mtable' => 'viaassign', 'field' => 'name'),
    array('module' => 'viaassign', 'action' => 'submit for grading', 'mtable' => 'viaassign', 'field' => 'name'),
    array('module' => 'viaassign', 'action' => 'unlock submission', 'mtable' => 'viaassign', 'field' => 'name'),
    array('module' => 'viaassign', 'action' => 'update', 'mtable' => 'viaassign', 'field' => 'name'),
    array('module' => 'viaassign', 'action' => 'upload', 'mtable' => 'viaassign', 'field' => 'name'),
    array('module' => 'viaassign', 'action' => 'view', 'mtable' => 'viaassign', 'field' => 'name'),
    array('module' => 'viaassign', 'action' => 'view all', 'mtable' => 'course', 'field' => 'fullname'),
    array('module' => 'viaassign', 'action' => 'view confirm submit viaassignment form',
          'mtable' => 'viaassign', 'field' => 'name'),
    array('module' => 'viaassign', 'action' => 'view grading form', 'mtable' => 'viaassign', 'field' => 'name'),
    array('module' => 'viaassign', 'action' => 'view submission', 'mtable' => 'viaassign', 'field' => 'name'),
    array('module' => 'viaassign', 'action' => 'view submission grading table', 'mtable' => 'viaassign', 'field' => 'name'),
    array('module' => 'viaassign', 'action' => 'view submit viaassignment form', 'mtable' => 'viaassign', 'field' => 'name'),
    array('module' => 'viaassign', 'action' => 'view feedback', 'mtable' => 'viaassign', 'field' => 'name'),
);
