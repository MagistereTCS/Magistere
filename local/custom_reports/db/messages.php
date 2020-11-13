<?php
/**
 * Defines message providers (types of messages being sent)
 *
 * @package local_custom_reports
 * @copyright 2020 TCS {@link http://www.tcs.com}
 */
defined('MOODLE_INTERNAL') || die();
$messageproviders = [

    // Ordinary custom reports submissions.
    'send_notification' => [
        'defaults' => [
            'popup' => MESSAGE_PERMITTED + MESSAGE_DEFAULT_LOGGEDIN + MESSAGE_DEFAULT_LOGGEDOFF,
            'email' => MESSAGE_PERMITTED + MESSAGE_DEFAULT_LOGGEDIN + MESSAGE_DEFAULT_LOGGEDOFF
        ],
    ]

];