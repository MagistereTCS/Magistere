<?php

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->dirroot.'/lib/completionlib.php');
require_once($CFG->dirroot.'/lib/modinfolib.php');

require_login();

$action = required_param('a', PARAM_TEXT);
$cmid = required_param('cmid', PARAM_INT);
$courseid = required_param('c', PARAM_INT);

$participation = 'vp'; // vp for validate participation

$allowedAction = [$participation];

if(!in_array($action, $allowedAction)){
    return '';
}

$course = $DB->get_record('course', ['id' => $courseid]);

if($course === false){
    return '';
}

$cm = $DB->get_record_sql('SELECT cm.*,
el.id etherpadliteid,
el.participationisrequired
FROM {course_modules} cm
INNER JOIN {modules} m ON m.id=cm.module
INNER JOIN {etherpadlite} el ON el.id=cm.instance
WHERE m.name="etherpadlite" AND cm.id=?', [$cmid]);

if($cm === false){
    return '';
}

switch($action){
    case $participation:
        validateParticipation($cm, $course, $USER->id);
        break;
    default: // NOOP
}

return '';

/**
 * @param $cmid
 * @param $couurseid
 * @param $userid
 */
function validateParticipation($cm, $course, $userid)
{
    $completion = new completion_info($course);

    if($completion->is_enabled($cm) == COMPLETION_TRACKING_AUTOMATIC && $cm->participationisrequired) {
        $state = COMPLETION_COMPLETE;
        $completion->update_state($cm, $state, $userid);

        rebuild_course_cache($course);
    }

}
