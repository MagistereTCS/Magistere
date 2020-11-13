<?php 
namespace block_course_migration\task;


class process_conversion extends \core\task\scheduled_task {
	public function get_name() {
		// Shown in admin screens
		return get_string('processconversionname', 'block_course_migration');
	}
	 
	public function execute() {
		global $CFG;
		
		require_once($CFG->dirroot.'/blocks/course_migration/lib.php');
		
		echo 'STARTING CONVERTION';
		
		$mag = new \MagistereConversion();
		$mag->runTask();
		
		echo 'END CONVERTION';
		
	}
}
