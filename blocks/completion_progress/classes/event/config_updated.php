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
 * block_completion_progress config updated event.
 *
 * @package    block_completion_progress
 * @copyright  2020 TCS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_completion_progress\event;
defined('MOODLE_INTERNAL') || die();

/**
 * block_completion_progress config updated event.
 *
 * @package    block_completion_progress
 * @since      Moodle 2.7
 * @copyright  2020 TCS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class config_updated extends \core\event\base {
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['objecttable'] = 'block_instances';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }
    // No need to override any method.
}
