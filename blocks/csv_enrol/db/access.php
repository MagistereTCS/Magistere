<?php
$capabilities = array(
    'mod/csv_enrol:uploadcsv' => array(
        'riskbitmask'  => RISK_PERSONAL | RISK_MANAGETRUST,
        'captype'      => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes'   => array(
            'teacher'        => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'admin'          => CAP_ALLOW
        )
    ),
    'block/csv_enrol:addinstance' => array(
        'riskbitmask' => RISK_XSS, 
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
        'coursecreator' => CAP_ALLOW,
        'manager' => CAP_ALLOW
        )
    )
);
