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
 * These are the settings for this module
 *
 * @package    mod_etherpadlite
 *
 * @author     Timo Welde <tjwelde@gmail.com>
 * @copyright  2012 Humboldt-Universität zu Berlin <moodle-support@cms.hu-berlin.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$settings->add(new admin_setting_configtext('mod_jupyternotebook/repositoryuser', get_string('repositoryuser', 'jupyternotebook'),
                   get_string('repositoryuserdesc', 'jupyternotebook'), "", PARAM_TEXT));

$settings->add(new admin_setting_configtext('mod_jupyternotebook/repositoryname', get_string('repositoryname', 'jupyternotebook'),
    get_string('repositorynamedesc', 'jupyternotebook'), "", PARAM_TEXT));

$settings->add(new admin_setting_configtext('mod_jupyternotebook/repositorybranch', get_string('repositorybranch', 'jupyternotebook'),
    get_string('repositorybranchdesc', 'jupyternotebook'), "", PARAM_TEXT));

$settings->add(new admin_setting_configtext('mod_jupyternotebook/repositorytoken', get_string('repositorytoken', 'jupyternotebook'),
    get_string('repositorytokendesc', 'jupyternotebook'), "", PARAM_TEXT));
