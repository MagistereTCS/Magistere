<?php

/**
 * Moodle MyIndex local plugin
 * Image ressource proxy to access the local file of other local moodle instance
 * Used by the local_magistere_offers
 *
 * @package    local_myindex
 * @copyright  2020 TCS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->dirroot.'/local/magisterelib/databaseConnection.php');

require_once($CFG->libdir.'/filelib.php');

require_login();

$relativepath = get_file_argument();

$args = explode('/',$relativepath);

$aca = clean_param($args[1], PARAM_ALPHAEXT);
$fid = clean_param($args[2], PARAM_INT);

$acas = get_magistere_academy_config();

if (!array_key_exists($aca, $acas) )
{
    send_file_not_found();
}


if (databaseConnection::instance()->get($aca) === false){error_log('local_myindex/image.php/'.$aca.'/Database_connection_failed'); send_file_not_found();}

$img = databaseConnection::instance()->get($aca)->get_record('files', array('id'=>$fid));

$dataroot = substr($CFG->dataroot,0,strrpos($CFG->dataroot, '/'));

$img_path = $dataroot.'/'.$acas[$aca]['shortname'].'/filedir/'.substr($img->contenthash,0,2).'/'.substr($img->contenthash,2,2).'/'.$img->contenthash;

if (file_exists($img_path))
{
    send_file($img_path, $img->filename);
}else{
    send_file_not_found();
}
