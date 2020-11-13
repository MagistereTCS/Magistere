<?php
$capabilities = array(
    'local/gaia:view' => array(
        'riskbitmask' => RISK_XSS,
        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE
    ),
    'local/gaia:viewallsessions' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE
    )
);
