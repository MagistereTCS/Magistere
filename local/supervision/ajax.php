<?php

require_once('../../config.php');

if (isset($_POST['method']) && $_POST['method'] == 'init_group') {
	get_group_list();
} elseif (isset($_POST['select_id']) && $_POST['select_id'] == 'group_school') {
	get_school_list();
} elseif (isset($_POST['select_id']) && $_POST['select_id'] == 'school_name') {
	get_stagiaires();
}


function get_group_list() {
	global $DB, $CFG;
	$local_academie = $DB->get_record('t_academie', ['short_uri' => $CFG->academie_name]);
    if(empty($local_academie)){echo null; exit;}

    $code_academie = str_pad($local_academie->id, 2, '0', STR_PAD_LEFT);

	$circonscriptions = $DB->get_records('t_circonscription', ['code_academie'=> $code_academie]);
	$result = [];
	
	//TCS - 2015/04/02 - NNE 
	$result['select_all'] = 'Tous / Toutes';

	foreach($circonscriptions as $circonscription) {
		$result['circ_'.$circonscription->code] = 'Circonscription: '.$circonscription->libelle_long;
	}

	if(empty($circonscriptions)){echo null; exit;}
	
	echo json_encode($result); exit;
}

function get_school_list() {
	global $DB;

	//on récupère le code académie de la plateforme courante
	$local_academie = $DB->get_record('t_academie', ['is_local' => 1]);
	$code_academie = $local_academie->id;

	//on determine si la selection concerne un niveau ou une circonscription
	$result = [];
	$result['select_all'] = 'Tous / Toutes';
	
	//on récupère les valeurs pour les tester
	$select_value = $_POST['select_value']; 
	
	if (!empty($select_value)){
		foreach ($select_value as $data_sent) {
			if ($data_sent == 'select_all') {continue;};

			$data_sent = explode('_', $data_sent);
			if ($data_sent[0] == 'circ') {
				$schools = $DB->get_records_sql('
                  SELECT * FROM {t_uai} 
                  WHERE `circonscription` = "'.$data_sent[1].'" 
                  AND `academie` = '.$code_academie.' 
                  ORDER BY `ville`');
				foreach ($schools as $school) {
					$result[$school->code_rne] = $school->appelation_officielle.' - '.$school->ville;
				}
			} elseif ($data_sent[0] == 'niv') {
				$types_uai = $DB->get_records('t_type_uai', ['niveau' => $data_sent[1]]);
				$liste_types = '';
				foreach ($types_uai as $current_type) {
					if (empty($liste_types)) {
						$liste_types= '('.$current_type->code_nature;
					} else {
						$liste_types.= ','.$current_type->code_nature;
					}		
				}
				if (!empty($liste_types)) {
					$liste_types.=')';
					$schools = $DB->get_records_sql('
                      SELECT * FROM {t_uai} 
                      WHERE `nature_uai` IN '.$liste_types.' 
                      AND `academie` = '.$code_academie.' 
                      ORDER BY `ville`');
					foreach ($schools as $school) {
						$result[$school->code_rne] = $school->appelation_officielle.' - '.$school->ville;
					}
				}
			}
		}
	}
	echo json_encode($result); exit;
}

function get_stagiaires() {
	global $DB;
	$result = [];
	$result['select_all'] = 'Tous / Toutes';
	
	$select_value = $_POST['select_value'];
	if (!empty($select_value)){
		$code_rne_list = [];
		foreach ($select_value as $current_school_rne){
			//verification du selectAll
			if ($current_school_rne == 'select_all') {
				continue;
			} else {
                $code_rne_list[] = $current_school_rne;
			}
		}
        $code_rne_list = implode (", ", $code_rne_list);

		//dans le cas du selectAll
		if (!empty($code_rne_list)) {
            $sql = '
            SELECT u.username AS identifiant, u.firstname, u.lastname, uid.data AS code_rne
            FROM {user} u
            INNER JOIN {user_info_data} uid ON (uid.userid = u.id)
            WHERE uid.data IN (:code_rne_list)
            AND uid.fieldid = (SELECT id FROM {user_info_field} WHERE shortname = "rne")
            ORDER BY u.lastname';

            $stagiaires = $DB->get_records_sql($sql, ['code_rne_list' => $code_rne_list]);
			foreach ($stagiaires as $stagiaire) {
				if ((!empty($stagiaire->firstname)) && (!empty($stagiaire->lastname))) {
					$result[$stagiaire->identifiant] = $stagiaire->firstname.'  '.$stagiaire->lastname;
				}
			}	
		}	
	}
	echo json_encode($result); exit;
}