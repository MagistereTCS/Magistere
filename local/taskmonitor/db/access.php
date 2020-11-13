<?php

/**
 * Plugin Capabilities
 *
 * @author TCS
 * @package local_workflow
 */
$capabilities = array(
    'local/taskmonitor:access' => array(
        'riskbitmask' => RISK_XSS,
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'teacher' => CAP_PREVENT,
            'editingteacher' => CAP_PREVENT,
            'coursecreator' => CAP_PREVENT,
            'manager' => CAP_PREVENT
        ),
    ),  
);