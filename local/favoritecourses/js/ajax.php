<?php
require_once('../../../config.php');
require_once('../lib.php');

require_login();
global $DB, $USER;

$id = required_param("id", PARAM_INT);
$aca = optional_param('aca', '', PARAM_TEXT);

$data_array = array('success' => false);

if(empty($aca)) {
    $favorite_course = $DB->get_record('local_favoritecourses', array('courseid' => $id, 'userid' => $USER->id));
}else{
    if (($acaDB = databaseConnection::instance()->get($aca)) === false){
        error_log('course_list_lib.php/load_hub_data/'.$aca.'/Database_connection_failed');
        $data = json_encode($data_array);
        echo $data;
    }

    if(($acaUser = $acaDB->get_record('user', array('username' => $USER->username))) === false){
		error_log('no username found for user ' . $user->username . ' on ' . $aca);
		return false;
	}

    $favorite_course = $acaDB->get_record('local_favoritecourses', array('courseid' => $id, 'userid' => $acaUser->id));
}

// Lorsque l'on clique sur un bouton Favoris
if($favorite_course){
    $data_array['success'] = delete_favorite_course($favorite_course->id, $aca);;
} else {
    $data_array['success'] = add_favorite_course($id, $USER, $aca);
}

// Envoi des données selon le cas
$data = json_encode($data_array);
echo $data;

