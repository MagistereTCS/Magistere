<?php

namespace local_gaia\event;
defined('MOODLE_INTERNAL') || die();


class gaia_nothing_todo extends \core\event\base {

    /**
     * Init method.
     */
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
    	global $CFG;
        return "Nothing todo for ".$CFG->academie_name;
    }

    /**
     * Returns localised general event name.
     *
     * @return string
     */
    public static function get_name() {
        global $CFG;
        return "Nothing todo for ".$CFG->academie_name;
    }

    /**
     * Returns relevant URL.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return null;
    }
}
