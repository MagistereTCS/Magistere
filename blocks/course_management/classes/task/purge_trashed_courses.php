<?php 
namespace block_course_management\task;


class purge_trashed_courses extends \core\task\scheduled_task {
	public function get_name() {
		// Shown in admin screens
		return get_string('purge_trashed_courses_name', 'block_course_management');
	}
	
	public function execute() {
		global $DB;
		
		echo 'STARTING PURGE';
		
		$sql = "SELECT c.id, updatetime
FROM `mdl_course` c
INNER JOIN mdl_course_trash_category ct ON (ct.course_id=c.id)
INNER JOIN mdl_context cx ON (cx.instanceid=c.id)
WHERE
ct.updatetime < ".((time()-16070400))."
AND ct.updatetime IS NOT NULL
AND cx.contextlevel = 50
AND cx.path LIKE(SELECT CONCAT('%/',id,'/%') FROM mdl_context WHERE contextlevel = 40 AND instanceid = (SELECT id FROM mdl_course_categories WHERE name ='Corbeille'))";
		
		$courses = $DB->get_records_sql($sql);
		throw new \Exception();
		// Iterate over the courses
		foreach ($courses as $course) {
		    echo "ID=".$course->id."&lastaccess=".$course->lastaccess."\n";
		    //delete_course($course->id); // 16070400
		}
		
		echo 'END PURGE';
	}
}
