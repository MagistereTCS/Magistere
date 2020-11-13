<?php
$capabilities = array(
 
    'moodle/supervision:consult' => array(
        'riskbitmask' => RISK_XSS,
 
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'manager' => CAP_ALLOW
        ),
    )
	
);
?>