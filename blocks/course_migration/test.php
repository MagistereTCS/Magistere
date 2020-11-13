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
 * Blog Menu Block page.
 *
 * @package    block
 * @subpackage course_migration
 * @copyright  2017 TCS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */



require_once(dirname(dirname(dirname(__FILE__))).'/config.php');

require_once($CFG->dirroot.'/blocks/course_migration/lib.php');




$mc = new MagistereConversion();
echo '<pre>START';
//echo '###'.$mc->convert_flexpage_to_topic(2826).'###';
echo '###'.$mc->convert_flexpage_to_topic(2571).'###';
// context= 322946   -> 347780
echo 'ERROR:'.$mc->get_lasterror();
