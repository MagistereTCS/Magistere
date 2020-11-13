<?php

$tasks = array(
	array(
		'classname' => 'local_magisterelib\task\indexation_stats_task',
		'blocking' => 0,
		'minute' => '30',
		'hour' => '00',
		'day' => '*',
		'dayofweek' => '*',
		'month' => '*',
		'disabled' => 1
	),
    array(
        'classname' => 'local_magisterelib\task\cpforum_sync_task',
        'blocking' => 0,
        'minute' => '0',
        'hour' => '12',
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*',
        'disabled' => 1
    ),
    array(
        'classname' => 'local_magisterelib\task\magistere_monitoring_task',
        'blocking' => 0,
        'minute' => '*',
        'hour' => '*',
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*',
        'disabled' => 1
    ),
    array(
        'classname' => 'local_magisterelib\task\update_course_modified_task',
        'blocking' => 0,
        'minute' => '*/15',
        'hour' => '*',
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*',
        'disabled' => 0
    ),
);