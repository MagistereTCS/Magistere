<?php
namespace block_course_migration\task;


class automatic_process_conversion extends \core\task\scheduled_task {
    public function get_name() {
        // Shown in admin screens
        return get_string('automaticprocessconversionname', 'block_course_migration');
    }

    public function execute() {
        global $CFG;

        require_once($CFG->dirroot.'/blocks/course_migration/lib.php');

        echo 'STARTING CONVERSION';

        $mag = new \MagistereAutomaticConversion();
        $mag->runTask();

        echo 'END CONVERSION';

    }
}