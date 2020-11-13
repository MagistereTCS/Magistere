<?php

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');

//require_once($CFG->dirroot.'/local/magisterelib/databaseConnection.php');
require_once($CFG->dirroot.'/local/gaia/GaiaApi.php');

@ini_set('display_errors', '1'); // NOT FOR PRODUCTION SERVERS!
$CFG->debug = E_ALL | E_STRICT;         // DEBUG_DEVELOPER // NOT FOR PRODUCTION SERVERS!
$CFG->debugdisplay = true;


$PAGE->set_context(context_system::instance());
require_login();

$json_query = file_get_contents('php://input');

header('Receive: '.$json_query);

$params = json_decode($json_query, false, 512, JSON_HEX_QUOT);

if ($params === null && !is_object($params))
{
    die("{'error':true}");
}

// {"type":"dispositifs","courseid":1234,"startdate":123456464,"enddate":123456464,"dispositif":"18A025"}   // 
// {"type":"modules","courseid":1234,"startdate":123456464,"enddate":123456464,"dispositif":"18A0256181","module":"Enseigne"}   // 
// {"type":"sessions","courseid":1234,"startdate":123456464,"enddate":123456464,"dispositif":"18A0256181","module":12345,"session":"ESPE"}   // search the string "session" in the location of the sessions of the module "12345" in the dispositif "18A0256181"
// {"type":"session","dispositif":"18A0256181","module":12345,"session":12354}   // return one session if found

// {results:[{"dispositifid":"18A0207005","dispositifname":"Journées des IA IPR chargées de l'histoire des arts","dispositiforigin":"ACAAIX","sessioncount":20}]}

// {results:[{"dispositifid":"18A0207005","dispositifname":"Journées des IA IPR chargées de l'histoire des arts","dispositiforigin":"ACAAIX","sessioncount":20}]}

//$acas = get_magistere_academy_config();

//set_user_preference($name, $value)
//get_user_preferences()

if ( isset($params->type) && isset($params->courseid) && isset($params->startdate) && isset($params->enddate) && isset($params->dispositif) &&
    $params->type == 'dispositifs' && $params->courseid > 1 && $params->startdate > 1 && $params->enddate > 1 )
{
    $api = new GaiaApi();
    echo $api->search_dispositifs_json($params->dispositif, $params->startdate, $params->enddate);
}
else if ( isset($params->type) && isset($params->courseid) && isset($params->startdate) && isset($params->enddate) && isset($params->dispositif) && isset($params->module) &&
    $params->type == 'modules' && $params->courseid > 1 && $params->startdate > 1 && $params->enddate > 1 && strlen($params->dispositif) == 10 )
{
    $api = new GaiaApi();
    echo $api->search_modules_json($params->module,$params->dispositif, $params->startdate, $params->enddate);
}
else if ( isset($params->type) && isset($params->courseid) && isset($params->startdate) && isset($params->enddate) && isset($params->dispositif) && isset($params->module) && isset($params->session) &&
    $params->type == 'sessions' && $params->courseid > 1 && $params->startdate > 1 && $params->enddate > 1 && strlen($params->dispositif) == 10 && $params->module > 1 )
{
    $api = new GaiaApi();
    echo $api->search_sessions_json($params->session,$params->module,$params->dispositif, $params->startdate, $params->enddate);
}
else if ( isset($params->type) && isset($params->courseid) && isset($params->dispositif) && isset($params->module) && isset($params->session) &&
    $params->type == 'session' && $params->courseid > 1 && strlen($params->dispositif) == 10 && $params->module > 1 && $params->session > 1 )
{
    $api = new GaiaApi();
    echo $api->get_session_json($params->session,$params->module,$params->dispositif,$params->courseid);
}else{
    die('{"error":true}');
}



