<?php
$capabilities = array(

 
    'block/course_management:addinstance' => array(
        'riskbitmask' => RISK_XSS, 
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
        'coursecreator' => CAP_ALLOW,
        'manager' => CAP_ALLOW
        ),
    ),
	'block/course_management:createblankgabarit' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS, 
        'captype' => 'write',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => array(
        'manager' => CAP_ALLOW
        ),
    ),
	'block/course_management:createparcoursfromgabarit' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS, 
        'captype' => 'write',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => array(
        'manager' => CAP_ALLOW
        ),
    ),
	'block/course_management:creategabaritfromparcours' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS, 
        'captype' => 'write',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => array(
            'manager' => CAP_ALLOW
        ),
    ),
	'block/course_management:createsessionfromparcours' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS, 
        'captype' => 'write',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => array(
            'editingteacher' => CAP_ALLOW,
			'coursecreator' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        ),
    ),	
	'block/course_management:createparcoursfromsession' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS, 
        'captype' => 'write',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => array(
            'manager' => CAP_ALLOW
        ),
    ),
	'block/course_management:index' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS, 
        'captype' => 'write',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => array(
            'manager' => CAP_ALLOW
        ),
    ),	
	'block/course_management:duplicate' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS, 
        'captype' => 'write',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => array(
            'manager' => CAP_ALLOW
        ),
    ),
	'block/course_management:unarchive' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS, 
        'captype' => 'write',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => array(
            'editingteacher' => CAP_ALLOW
        ),
    ),
	'block/course_management:archive' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS, 
        'captype' => 'write',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => array(
            'editingteacher' => CAP_ALLOW
        ),
    ),
	'block/course_management:discard' => array(
        'riskbitmask' => RISK_DATALOSS, 
        'captype' => 'write',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => array(
            'editingteacher' => CAP_ALLOW
        ),
    ),
	'block/course_management:restorefromtrash' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => array(
            'editingteacher' => CAP_ALLOW
        ),
    )
);
?>