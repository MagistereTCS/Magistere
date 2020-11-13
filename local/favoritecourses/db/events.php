<?php

/**
 * @package local-favoritecourses
 * @author TCS
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$observers = array(
    array(
        'eventname' => 'core\event\user_enrolment_deleted',
        'callback'  => 'local_favoritecourses_observer::user_enrolment_deleted',
    ),
);
