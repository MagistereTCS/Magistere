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
 * This file adds the settings pages to the navigation menu
 *
 * @package   mod_viaassign
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/mod/viaassign/adminlib.php');

$systemcontext = context_system::instance();

if ($ADMIN->fulltree) {
    $menu = array();
    foreach (core_component::get_plugin_list('viaassignfeedback') as $type => $notused) {
        $visible = !get_config('viaassignfeedback_' . $type, 'disabled');
        if ($visible) {
            $menu['viaassignfeedback_' . $type] = new lang_string('pluginname', 'viaassignfeedback_' . $type);
        }
    }

    // The default here is feedback_comments (if it exists).
    $name = new lang_string('feedbackplugin', 'mod_viaassign');
    $description = new lang_string('feedbackpluginforgradebook', 'mod_viaassign');
    $settings->add(new admin_setting_configselect('viaassign/feedback_plugin_for_gradebook',
                                                  $name,
                                                  $description,
                                                  'viaassignfeedback_comments',
                                                  $menu));

    $name = new lang_string('defaultsettings', 'mod_viaassign');
    $description = new lang_string('defaultsettings_help', 'mod_viaassign');
    $settings->add(new admin_setting_heading('defaultsettings', $name, $description));

    $name = new lang_string('allowsubmissionsfromdate', 'mod_viaassign');
    $description = new lang_string('allowsubmissionsfromdate_help', 'mod_viaassign');
    $setting = new admin_setting_configduration('viaassign/allowsubmissionsfromdate',
                                                    $name,
                                                    $description,
                                                    0);
    $setting->set_enabled_flag_options(admin_setting_flag::ENABLED, true);
    $settings->add($setting);

    $name = new lang_string('duedate', 'mod_viaassign');
    $description = new lang_string('duedate_help', 'mod_viaassign');
    $setting = new admin_setting_configduration('viaassign/duedate',
        $name,
        $description,
        604800);
    $setting->set_enabled_flag_options(admin_setting_flag::ENABLED, true);
    $settings->add($setting);

    $roles = get_role_names_with_caps_in_context($systemcontext, array('mod/viaassign:view'));
    if (!$roles) {
        $roles = array(0 => get_string('userrole_none_short', 'mod_viaassign'));
        $text = new lang_string('userrole_none', 'mod_viaassign');
    } else {
        $text = new lang_string('userrole_help', 'mod_viaassign');
    }
    $name = new lang_string('userrole', 'mod_viaassign');
    $description = $text;
    $setting = new admin_setting_configselect('viaassign/userrole',
        $name,
        $description,
        -1,
        $roles);
    $setting->set_locked_flag_options(admin_setting_flag::ENABLED, false);
    $settings->add($setting);

    // Constants from "locallib.php".
    $options = array_combine(range(1, 30), range(1, 30));
    $name = new lang_string('maxactivities', 'mod_viaassign');
    $description = new lang_string('maxactivities_help', 'mod_viaassign');
    $setting = new admin_setting_configselect('viaassign/maxactivities',
        $name,
        $description,
        -1,
        $options);
    $setting->set_locked_flag_options(admin_setting_flag::ENABLED, false);
    $settings->add($setting);

    $name = new lang_string('maxduration', 'mod_viaassign');
    $description = new lang_string('maxduration_help', 'mod_viaassign');
    $setting = new admin_setting_configtext('viaassign/maxduration',
                                            $name,
                                            $description,
                                            60);
    $setting->set_locked_flag_options(admin_setting_flag::ENABLED, false);
    $settings->add($setting);

    $name = new lang_string('maxusers', 'mod_viaassign');
    $description = new lang_string('maxusers_help', 'mod_viaassign');
    $setting = new admin_setting_configtext('viaassign/maxusers',
                                            $name,
                                            $description,
                                            0);
    $setting->set_locked_flag_options(admin_setting_flag::ENABLED, false);
    $settings->add($setting);

    $name = new lang_string('sendnotifications', 'mod_viaassign');
    $description = new lang_string('sendnotifications_help', 'mod_viaassign');
    $setting = new admin_setting_configcheckbox('viaassign/sendnotifications',
                                                $name,
                                                $description,
                                                0);
    $setting->set_locked_flag_options(admin_setting_flag::ENABLED, false);
    $settings->add($setting);

    $name = new lang_string('sendstudentnotificationsdefault', 'mod_viaassign');
    $description = new lang_string('sendstudentnotificationsdefault_help', 'mod_viaassign');
    $setting = new admin_setting_configcheckbox('viaassign/sendstudentnotifications',
                                                $name,
                                                $description,
                                                0);
    $setting->set_locked_flag_options(admin_setting_flag::ENABLED, false);
    $settings->add($setting);
}