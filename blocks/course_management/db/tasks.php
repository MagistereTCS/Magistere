<?php

$tasks = array(
    array(
        'classname' => 'block_course_management\task\purge_trashed_courses',
        'blocking' => 0,
        'minute' => '*/5',
        'hour' => '*',
        'day' => '*',
        'dayofweek' => '*',
        'month' => '1',
        'disabled' => 1
    )
);