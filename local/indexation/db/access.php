<?php

/**
 * Plugin Capabilities
 *
 * @author TCS
 * @package local_indexation
 */
$capabilities = array(
    'local/indexation:index' => array(
        'riskbitmask' => RISK_XSS,

        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'teacher' => CAP_INHERIT,
            'editingteacher' => CAP_INHERIT,
            'coursecreator' => CAP_INHERIT,
            'manager' => CAP_INHERIT
        ),
    )
);