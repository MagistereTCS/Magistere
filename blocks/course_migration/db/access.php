<?php
$capabilities = array(
	'block/course_migration:addinstance' => array(
			'riskbitmask' => RISK_XSS,
			
			'captype' => 'write',
			'contextlevel' => CONTEXT_SYSTEM,
			'archetypes' => array(
					'coursecreator' => CAP_ALLOW,
					'editingteacher' => CAP_ALLOW,
					'manager' => CAP_ALLOW
			),
	),
	'block/course_migration:showmigrationblock' => array(
			'riskbitmask' => RISK_XSS,
			'captype' => 'write',
			'contextlevel' => CONTEXT_SYSTEM,
			'archetypes' => array(
					'coursecreator' => CAP_ALLOW,
					'editingteacher' => CAP_ALLOW,
					'manager' => CAP_ALLOW
			),
	),
	'block/course_migration:removeflexpagecourse' => array(
			'riskbitmask' => RISK_XSS,
			'captype' => 'write',
			'contextlevel' => CONTEXT_SYSTEM,
			'archetypes' => array(
					'coursecreator' => CAP_ALLOW,
					'editingteacher' => CAP_ALLOW,
					'manager' => CAP_ALLOW
			),
	),
	'block/course_migration:convertcourse' => array(
			'riskbitmask' => RISK_XSS,
			'captype' => 'write',
			'contextlevel' => CONTEXT_SYSTEM,
			'archetypes' => array(
					'coursecreator' => CAP_ALLOW,
					'editingteacher' => CAP_ALLOW,
					'manager' => CAP_ALLOW
			),
	)
);