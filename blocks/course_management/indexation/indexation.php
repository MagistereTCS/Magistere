<?php
require_once('../../../config.php');

global $DB;

ini_set('display_errors', true);
error_reporting(E_ALL);

$course_id = required_param('course_id', PARAM_INT);
// on verifie la non existence de données d'indexation pour ce cours
$course = $DB->get_record('course', array('id' => $course_id));
if (!$course) {
    die('The provided course id is not valid');
}

$isNew = false;

$index = $DB->get_record('indexation_moodle', array('course_id' => $course_id));
if (!$index) {
    $isNew = true;
    $index = new stdClass();

    $index->course_id    = $course_id;
}
$code_gaia = $DB->get_record('origine_gaia', array('code' => $_POST['origin_gaia']));

try{
	$index->objectifs       		= required_param('objectifs', PARAM_TEXT);
	$index->collection      		= required_param('collection', PARAM_TEXT);


	$h = optional_param('tps_a_distance_hour', 0, PARAM_INT);
	$m = optional_param('tps_a_distance_min', 0, PARAM_INT);
	$index->tps_a_distance  		= $h*60 + $m;
	
	$h = optional_param('tps_en_presence_hour', 0, PARAM_INT);
	$m = optional_param('tps_en_presence_min', 0, PARAM_INT);
	$index->tps_en_presence 		= $h*60+$m;
	
	$index->accompagnement  		= optional_param('accompagnement', '', PARAM_TEXT);;
	$index->origine         		= required_param('origin', PARAM_TEXT);;
	$index->liste_auteurs   		= optional_param('liste_auteurs', '', PARAM_TEXT);;
	$index->validation      		= optional_param('validation', '', PARAM_TEXT);;
	$index->academy        			= optional_param('academy', '', PARAM_TEXT);
	$index->department      		= optional_param('department', '', PARAM_TEXT);;
	$index->origin_espe     		= optional_param('origin_espe', '', PARAM_TEXT);
	$index->shared_offer    		= required_param('shared_offer', PARAM_INT);
	$index->nom_parcours 			= $course->fullname;
    $index->description  			= $course->summary;
	
	$email					 		= optional_param('contact_auteurs', '', PARAM_TEXT);
	if(!empty($email)){
		$index->contact_auteurs 		= filter_var($email, FILTER_VALIDATE_EMAIL);
		
		if($index->contact_auteurs === false){
			throw new Exception('invalid email');
		}
	}
	
	$index->year 					= required_param('year', PARAM_TEXT);
	$index->origine_gaia_id 		= $code_gaia->id;
	$index->title 					= strtoupper(required_param('title', PARAM_TEXT));
	$index->version 				= required_param('version', PARAM_TEXT);
	$index->course_identification 	= required_param('course_identification', PARAM_TEXT);
	
	$levels = required_param('levels', PARAM_TEXT);
	$domains = required_param('domains', PARAM_TEXT);
	$targets = required_param('targets', PARAM_TEXT);
}catch(Exception $e){
	redirect( $CFG->wwwroot . '/blocks/course_management/indexation/?id=' . $course_id);
}


// keywords
$keywords = array_unique(
    array_map(
        function ($word) {
            return trim(strtolower($word));
        },
        explode(',', $_POST['keywords'])
    )
);
sort($keywords);
$index->keywords = '|' . implode('|', $keywords) . '|';

// mise à jour
$index->derniere_maj = date('Y-m-d H:i:s');

$relations = array(
    'level'  => $levels,
    'domain' => $domains,
    'target' => $targets
);

$t = $DB->start_delegated_transaction();
try {
    if ($isNew) {
        $index->id = $DB->insert_record('indexation_moodle', $index);
    } else {
        $DB->update_record('indexation_moodle', $index);
    }

    foreach ($relations as $name => $data) {
        update_relation($DB, $index->id, $name, $data);
    }

    $t->allow_commit();
} catch (Exception $e) {
    $t->rollback($e);
}

http_response_code(302);
header('Location: ' . $CFG->wwwroot . '/course/view.php?id=' . $course_id);

function update_relation(moodle_database $db, $id, $name, array $data) {
    $relationTablename = 'indexation_index_' . $name;

    $db->delete_records('indexation_index_' . $name, array('indexation_id' => $id));

    foreach ($data as $value) {
        $db->execute(
            sprintf('INSERT INTO %s (indexation_id, %s) VALUES (?, ?)', $db->get_prefix() . $relationTablename, $name . '_id'),
            array(
                $id,
                $value
            )
        );
    }
}
