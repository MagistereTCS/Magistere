<?php

$tasks = array(
    array(
        'classname' => 'block_course_migration\task\process_conversion',
        'blocking' => 0,
        'minute' => '*/5',
        'hour' => '*',
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*',
        'disabled' => 0
    ),
    array(
        'classname' => 'block_course_migration\task\automatic_process_conversion',
        'blocking' => 0,
        'minute' => '15',
        'hour' => '01',
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*',
        'disabled' => 1
    )
);