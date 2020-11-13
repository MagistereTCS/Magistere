<?php

$capabilities = array(
    'block/menu_local_mail:addinstance' => array(
        'riskbitmask' => RISK_XSS,
 
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'coursecreator' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        	'student' => CAP_ALLOW
        ),
    ),
);
