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
 * The mod_viaassign abstract base event.
 *
 * @package    mod_viaassign
 * @copyright  2014 Mark Nelson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_viaassign\event;

defined('MOODLE_INTERNAL') || die();

/**
 * The mod_viaassign abstract base event class.
 *
 * Most mod_viaassign events can extend this class.
 *
 * @package    mod_viaassign
 * @since      Moodle 2.7
 * @copyright  2014 Mark Nelson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class base extends \core\event\base {

    /** @var \viaassign */
    protected $viaassign;

    /**
     * Legacy log data.
     *
     * @var array
     */
    protected $legacylogdata;

    /**
     * Set viaassign instance for this event.
     * @param \viaassign $viaassign
     * @throws \coding_exception
     */
    public function set_viaassign(\viaassign $viaassign) {
        if ($this->is_triggered()) {
            throw new \coding_exception('set_viaassign() must be done before triggerring of event');
        }
        if ($viaassign->get_context()->id != $this->get_context()->id) {
            throw new \coding_exception('Invalid viaassign isntance supplied!');
        }
        $this->viaassign = $viaassign;
    }

    /**
     * Get viaassign instance.
     *
     * NOTE: to be used from observers only.
     *
     * @throws \coding_exception
     * @return \viaassign
     */
    public function get_viaassign() {
        if ($this->is_restored()) {
            throw new \coding_exception('get_viaassign() is intended for event observers only');
        }
        if (!isset($this->viaassign)) {
            debugging('viaassign property should be initialised in each event', DEBUG_DEVELOPER);
            global $CFG;
            require_once($CFG->dirroot . '/mod/viaassign/locallib.php');
            $cm = get_coursemodule_from_id('viaassign', $this->contextinstanceid, 0, false, MUST_EXIST);
            $course = get_course($cm->course);
            $this->viaassign = new \viaassign($this->get_context(), $cm, $course);
        }
        return $this->viaassign;
    }


    /**
     * Returns relevant URL.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/mod/viaassign/view.php', array('id' => $this->contextinstanceid));
    }

    /**
     * Sets the legacy event log data.
     *
     * @param string $action The current action
     * @param string $info A detailed description of the change. But no more than 255 characters.
     * @param string $url The url to the viaassign module instance.
     */
    public function set_legacy_logdata($action = '', $info = '', $url = '') {
        $fullurl = 'view.php?id=' . $this->contextinstanceid;
        if ($url != '') {
            $fullurl .= '&' . $url;
        }

        $this->legacylogdata = array($this->courseid, 'viaassign', $action, $fullurl, $info, $this->contextinstanceid);
    }

    /**
     * Return legacy data for add_to_log().
     *
     * @return array
     */
    protected function get_legacy_logdata() {
        if (isset($this->legacylogdata)) {
            return $this->legacylogdata;
        }

        return null;
    }

    /**
     * Custom validation.
     *
     * @throws \coding_exception
     */
    protected function validate_data() {
        parent::validate_data();

        if ($this->contextlevel != CONTEXT_MODULE) {
            throw new \coding_exception('Context level must be CONTEXT_MODULE.');
        }
    }
}
