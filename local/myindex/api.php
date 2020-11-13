<?php

/**
 * Moodle MyIndex local plugin
 * This API use the class MyIndexApi to transmit data to the Frontend
 *
 * @package    local_myindex
 * @copyright  2020 TCS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');

require_once($CFG->dirroot.'/local/magisterelib/databaseConnection.php');
require_once($CFG->dirroot.'/local/myindex/MyIndexApi.php');

@ini_set('display_errors', '0');
$CFG->debug = false; //E_ALL | E_STRICT;   // DEBUG_DEVELOPER // NOT FOR PRODUCTION SERVERS!
$CFG->debugdisplay = false;


$PAGE->set_context(context_system::instance());
$PAGE->set_url('/my');
require_login();

$json_query = file_get_contents('php://input');

//header('Receive: '.$json_query);

$params = json_decode($json_query, false, 512, JSON_HEX_QUOT);

if ($params === null && !is_object($params))
{
    die("{'error':true}");
}

// {'module':'viewmod','mod':'grid'}   // View Mod : mod = grid/list
// {'module':'fav','ac':'ac-versailles','id':123,'action':true}   // Favoris : true = ajout & false = suppr
// {'module':'modal','ac':'ac-versailles','id':123}               // retour la modal du parcours

// {'module':'main','search':'my search','filter':'allcourse'}               // retourne la liste principale des parcours
// {'module':'main','search':'my search','filter':'parcoursdemo'}            // retourne la liste des parcours en demonstration
// {'module':'main','search':'my search','filter':'espacecollabo'}           // retourne la liste des parcours avec la collection espace collabo
// {'module':'main','search':'my search','filter':'seformer'}                // retourne la liste des parcours 
// {'module':'main','search':'my search','filter':'former'}                  // retourne la liste des parcours
// {'module':'main','search':'my search','filter':'concevoir'}               // retourne la liste des parcours
// {'module':'main','search':'my search','filter':'favoris'}                 // retourne la liste des parcours favoris non archivés
// {'module':'main','search':'my search','filter':'favoris','archive':true}  // retourne la liste des parcours favoris dans la catégorie archive
// {'module':'main','search':'my search','filter':'archive'}                 // retourne la liste des parcours archivé


$acas = get_magistere_academy_config();

if ( isset($params->module) && isset($params->mod) && 
    $params->module == 'viewmod' && ( $params->mod == 'grid' || $params->mod == 'list' ) )
{

    $view = get_user_preferences('local_myindex_viewmod','');
    if ($view == $params->mod)
    {
        die('{"error":false,"value":"'.$view.'"}');
    }else{
        set_user_preference('local_myindex_viewmod',$params->mod);
        $view = get_user_preferences('local_myindex_viewmod');
        
        die('{"error":false,"value":"'.$view.'"}');
    }
    
}else if ( isset($params->module) && isset($params->ac) && isset($params->id) && isset($params->action) &&
    $params->module == 'fav' && array_key_exists($params->ac, $acas) && $params->id > 0 && ($params->action === false || $params->action === true) )
{
    if ((databaseConnection::instance()->get($params->ac)) === false){error_log('api.php/'.$params->ac.'/Database_connection_failed'); die('{"error":true,"value":"db_error"}');}
    
    $localuser = databaseConnection::instance()->get($params->ac)->get_record('user', array('username'=>$USER->username));
    if ($localuser == false)
    {
        die('{"error":true,"value":"bad_user"}');
    }
    
    $localcourse = databaseConnection::instance()->get($params->ac)->get_record('course', array('id'=>$params->id));
    if ($localcourse == false)
    {
        die('{"error":true,"value":"bad_course"}');
    }
    
    $favoritecourse = databaseConnection::instance()->get($params->ac)->get_record('local_favoritecourses', array('courseid'=>$params->id,'userid'=>$localuser->id));
    
    if ($favoritecourse == false && $params->action == true)
    {
        $fav = new stdClass();
        $fav->courseid = $params->id;
        $fav->userid = $localuser->id;
        $fav->email = $localuser->email;
        $fav->username = $localuser->username;
        $fav->timecreated = time();
        databaseConnection::instance()->get($params->ac)->insert_record('local_favoritecourses', $fav);
        
        echo '{"error":false,"value":true}';
    }else if ($favoritecourse != false && $params->action == false){
        databaseConnection::instance()->get($params->ac)->delete_records('local_favoritecourses',array('id'=>$favoritecourse->id));
        echo '{"error":false,"value":false}';
    }else{
        echo '{"error":false,"value":'.($params->action?'true':'false').'}';
    }
}else if (isset($params->module) && isset($params->ac) && isset($params->id) &&
    $params->module == 'modal' && array_key_exists($params->ac, $acas) && $params->id > 0){

    $api = new MyIndexApi(MyIndexApi::MOD_MODAL, $params->ac, $params->id);
    echo $api->get_json();
    
}else if (isset($params->module) && isset($params->search) && isset($params->filter) &&
    $params->module == 'main' && in_array($params->filter, MyIndexApi::FILTERS)){

    if ($params->filter == MyIndexApi::FILTER_FAVORIS) {
        if (!isset($params->archive) || $params->archive != true) {$params->archive = false;}
        $api = new MyIndexApi(MyIndexApi::MOD_MAIN,$params->search, $params->filter, $params->archive);
        echo $api->get_json();
    }else{
        $api = new MyIndexApi(MyIndexApi::MOD_MAIN,$params->search, $params->filter);
        echo $api->get_json();
    }

}else{
    die('{"error":true}');
}



