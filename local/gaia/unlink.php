<?php

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->dirroot . '/local/gaia/lib/GaiaUtils.php');


require_login();

$courseid = required_param('courseid', PARAM_INT);
$viaid = optional_param('viaid', null, PARAM_INT);
$returnurl = required_param('returnurl', PARAM_URL);
$sessionid = required_param('sessionid', PARAM_INT);
$dispositifid = required_param('dispositifid', PARAM_TEXT);
$moduleid = required_param('moduleid', PARAM_TEXT);

if($viaid !== null){
    GaiaUtils::unbind_activity_with_gaia($viaid, $sessionid, $dispositifid, $moduleid);
    GaiaUtils::unsubscribe_via_users($viaid);
}else{
    GaiaUtils::unbind_session_with_gaia($courseid, $sessionid, $dispositifid, $moduleid);
}

GaiaUtils::unsubscribe_user('GAIA-FORMATEUR_'.$sessionid.'_'.$dispositifid.'_'.$moduleid, $courseid);
GaiaUtils::unsubscribe_user('GAIA-PARTICIPANT_'.$sessionid.'_'.$dispositifid.'_'.$moduleid, $courseid);

redirect($returnurl, get_string('ajax_gaia_unbind', 'local_workflow'), null, \core\output\notification::NOTIFY_SUCCESS);
