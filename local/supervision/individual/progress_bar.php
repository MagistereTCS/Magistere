<?php

function generate_progress_bar($course_id)
{
	global $DB, $USER;

	$context  = context_course::instance($course_id);
	$block = $DB->get_record('block_instances', array('parentcontextid' => $context->id, 'blockname'=>'progress'));
	if(!$block) return false;
	$config = unserialize(base64_decode($block->configdata));

	//on calcule le pourcentage d'avancement
	$modules = modules_in_use_by_course_id($course_id);
	if(empty($modules)) return false;
	$events = custom_event_information($config, $modules, $course_id);
	if(is_null($events)) return false;
	$attempts = custom_get_attempts($modules, $config, $events, $USER->id, $course_id);

	$progressbar = progress_bar($modules, $config, $events, $USER->id, $course_id, $attempts, true);
	$progression =get_progess_percentage($events, $attempts);
	return array('progress_bar' => $progressbar, 'progression' => $progression.'%');
}






?>