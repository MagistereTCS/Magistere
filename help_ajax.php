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
 * Displays help via AJAX call
 *
 * @copyright 2013 onwards Andrew Nicols
 * @package   core
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('NO_MOODLE_COOKIES', true);
define('AJAX_SCRIPT', true);
require_once(__DIR__ . '/config.php');

$identifier = required_param('identifier', PARAM_STRINGID);
$component  = required_param('component', PARAM_COMPONENT);
$lang       = optional_param('lang', 'en', PARAM_LANG);
$dynamic    = optional_param('dynamic', '', PARAM_TEXT);
$a    = optional_param('a', '', PARAM_TEXT);

// We don't actually modify the session here as we have NO_MOODLE_COOKIES set.
$SESSION->lang = $lang;
$PAGE->set_url('/help_ajax.php');
$PAGE->set_context(context_system::instance());

$data = get_formatted_help_string($identifier, $component, true);
if($dynamic) {
    // Override the $data->text values from "get_formatted_help_string"
    $options = new stdClass();
    $options->trusted = false;
    $options->noclean = false;
    $options->smiley = false;
    $options->filter = false;
    $options->para = true;
    $options->newlines = false;
    $options->overflowdiv = true;

    $data->text = "";
    foreach (explode(",", $dynamic) as $string) {
        $line = get_string($identifier . '_' . $string, $component);
        if ($string == "aa") {
            $line .= $a;
        }
        $line .= "\n";
        $data->text .= format_text($line, FORMAT_MARKDOWN, $options);
    }
}
echo json_encode($data);
