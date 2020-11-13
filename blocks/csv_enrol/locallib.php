<?php

//  BRIGHTALLY CUSTOM CODE
//  Coder: Ted vd Brink
//  Contact: ted.vandenbrink@brightalley.nl
//  Date: 6 juni 2012
//
//  Description: Enrols users into a course by allowing a user to upload an csv file with only email adresses
//  Using this block allows you to use CSV files with only emailaddress
//  After running the upload you can download a txt file that contains a log of the enrolled and failed users.

//  License: GNU General Public License http://www.gnu.org/copyleft/gpl.html

function block_csv_enrol_enrol_users($courseid,$csvcontent,$formdata)
{
	global $DB, $CFG, $USER;
	require_once($CFG->libdir.'/enrollib.php');
	require_once($CFG->libdir.'/csvlib.class.php');
	require_once($CFG->libdir.'/accesslib.php');

	//get enrolment instance (manual and student)
	$instances = enrol_get_instances($courseid, false);
	$enrolment = "";
	foreach ($instances as $instance) {
		if ($instance->enrol === 'manual') {
		$enrolment = $instance;
					break;
		}
	}

	//get enrolment plugin
	$manual = enrol_get_plugin('manual');
	$context = context_course::instance($courseid);
	$returnurl = new moodle_url($CFG->wwwroot.'/course/view.php', array('id' => $courseid));

	$stats = new StdClass();
	$stats->success = $stats->failed = 0; //init counters
	$log = get_string('enrolling','block_csv_enrol')."\r\n";

	// Test du fichier (simple ou complexe)
	$cir = load_content_csv_enrol($csvcontent, $returnurl);
	$columns = $cir->get_columns();

	// User à supprimer si case du formulaire cochée
	if($formdata->delete_users){
		$list_email_course = array();
		$list_email_context = get_enrolled_users($context);
		foreach($list_email_context as $user){
			$list_email_course[] = $user->email;
		}
		$log .= delete_users_csv_enrol($list_email_course, $csvcontent,count($columns), $manual, $enrolment);
	}

	$v = "/^[a-zA-Z0-9_\-\.]+@[a-zA-Z0-9\-\.]+\.[a-zA-Z]+$/";

	// Si le fichier a plus d'une colonne, c'est un fichier complexe
	if(count($columns) > 1) {
		$lines = explode("\n",$csvcontent);
		foreach ($lines as $key=>$line) {
			if(trim($line)=="") continue;
			$rawuser = explode(";",$line); // email = $rawuser[0], role = $rawuser[1], $rawuser[n] = nom de groupe(s)
			if(trim($rawuser[0])!="" && trim($rawuser[1])!=""){ // Si l'email et / ou le role n'est pas vide on poursuit le traitement
				if($key == 0 && !(preg_match($v, trim($rawuser[0])))) continue; // Cas où le fichier contient une entete
				$user = $DB->get_record('user', array('email' => strtolower(trim($rawuser[0]))));
				$role = $DB->get_record('role', array('shortname' => trim($rawuser[1])), '*', MUST_EXIST);
				if($user) {
					if (!$user->deleted && !$user->suspended){
						if(is_enrolled($context,$user)) {
							$log .= get_string('alreadyenrolled','block_csv_enrol',fullname($user).' ('.$user->username.')')."\r\n";

							if($role){
								role_assign($role->id, $user->id, $context->id);
								group_and_user_management_for_complex_file_csv_enrol($rawuser, $user->id, $courseid);
								$stats->success++;
							} else {
								$log .= get_string('rolenotfound','block_csv_enrol',strtolower(trim($rawuser[0])))."\r\n";
								$stats->failed++;
							}

						} else { // Cas où le user n'est pas inscrit dans le parcours
							$log .= get_string('enrollinguser','block_csv_enrol',fullname($user).' ('.$user->username.')')."\r\n";

							// Ajout du rôle et du groupe pour un fichier simple
							if($role){
								$manual->enrol_user($enrolment,$user->id,$role->id,time());
								group_and_user_management_for_complex_file_csv_enrol($rawuser, $user->id, $courseid);
								$stats->success++;
							} else {
								$log .= get_string('rolenotfound','block_csv_enrol',strtolower(trim($rawuser[0])))."\r\n";
								$stats->failed++;
							}
						}
					}
					$stats->success++;
				} else { // Cas où le user n'existe pas sur la plateforme
                    $clean_username = clean_param($rawuser[0], PARAM_USERNAME);
                    $array = generate_firstname_lastname_using_email(strtolower(trim($rawuser[0]))); // Génération des noms et prénoms à partir de l'email.

					$record = new stdClass();
					$record->email = $clean_username;
					$record->password = generate_password(8);
					$record->country = 'FR';
					$record->lang = $CFG->lang;
					$record->confirmed = 1;
					$record->mnethostid = 1;
					$record->username = $clean_username;
					$record->firstname = ucfirst($array['firstname']);
                    $record->lastname = ucfirst($array['lastname']);
					$userid = user_create_user($record);
					$log .= get_string('enrollinguser','block_csv_enrol',fullname($record).' ('.$record->username.')')."\r\n";

					// Ajout du rôle et du groupe pour un fichier complexe
					$manual->enrol_user($enrolment,$userid,$role->id,time());
					group_and_user_management_for_complex_file_csv_enrol($rawuser, $userid, $courseid);

					$stats->success++;
				}
			}
		}
	}
	// End Evo 1233
	else
	{
		$lines = explode("\n",$csvcontent);
		$role = $DB->get_record('role', array('shortname' => $formdata->role), '*', MUST_EXIST);

		foreach ($lines as $key=>$line) {
			if(trim($line)=="") continue;
			if($key == 0 && !(preg_match($v, trim($line)))) continue; // Cas où le fichier contient une entete
			$user = $DB->get_record('user', array('email' => strtolower(trim($line))));
			if($user) {
				if (!$user->deleted && !$user->suspended)
				{
					if(is_enrolled($context,$user)) {
		                $log .= get_string('alreadyenrolled','block_csv_enrol',fullname($user).' ('.$user->username.')')."\r\n";

		                if($role){
		                	role_assign($role->id, $user->id, $context->id);
		                	if(isset($formdata->groups)){
		                		add_member_to_groups($formdata->groups,$user->id,$courseid);
		                	}
		                } else {
		                	$log .= get_string('rolenotfound','block_csv_enrol',trim($line))."\r\n";
		                	$stats->failed++;
		                }
					} else {
						$log .= get_string('enrollinguser','block_csv_enrol',fullname($user).' ('.$user->username.')')."\r\n";

						// Ajout du rôle et du groupe pour un fichier simple
						if($role){
							$manual->enrol_user($enrolment,$user->id,$role->id,time());
							if(isset($formdata->groups)){
								add_member_to_groups($formdata->groups,$user->id,$courseid);
							}
						} else {
	            			$log .= get_string('rolenotfound','block_csv_enrol',trim($line))."\r\n";
	            			$stats->failed++;
	            		}
					}
					$stats->success++;
				}
			} else {
                $line = clean_param($line, PARAM_USERNAME);
                $array = generate_firstname_lastname_using_email(strtolower(trim($line))); // Génération des noms et prénoms à partir de l'email.

	            $record = new stdClass();
	            $record->email = $line;
	            $record->password = generate_password(8);
	            $record->country = 'FR';
	            $record->lang = $CFG->lang;
	            $record->confirmed = 1;
	            $record->mnethostid = 1;
	            $record->username = $line;
                $record->firstname = ucfirst($array['firstname']);
                $record->lastname = ucfirst($array['lastname']);
	            $userid = user_create_user($record);//$DB->insert_record('user', $record, true);

	            $log .= get_string('enrollinguser','block_csv_enrol',$record->firstname .' '. $record->lastname .' ('.$record->username.')')."\r\n";

	            // Ajout du rôle et du groupe pour un fichier simple
	            $role = $DB->get_record('role', array('shortname' => $formdata->role), '*', MUST_EXIST); // La valeur role est celle donnée dans le formulaire

	            if($role){
		            $manual->enrol_user($enrolment,$userid,$role->id,time());
		            if(isset($formdata->groups)){
	            		add_member_to_groups($formdata->groups,$userid,$courseid);
		            }
	            	$stats->success++;
	            } else {
	            	$log .= get_string('rolenotfound','block_csv_enrol',trim($line))."\r\n";
	            	$stats->failed++;
	            }
			}
		}
	}
	$log .= get_string('done','block_csv_enrol')."\r\n";
	$log = get_string('status','block_csv_enrol',$stats).' '.get_string('enrolmentlog','block_csv_enrol')."\r\n\r\n".$log;
	return $log;
}

/**
 *
 * Permet d'ajouter un utilisateur dans un ou plusieurs groupes existant.
 *
 * @param array $groupids
 * @param int $userid
 * @return void
 */
function add_member_to_groups($groupids, $userid, $courseid)
{
	require_once('../../group/lib.php');

	if(is_string($groupids)){ // Cas spécifique où un seul groupe est sélectionné pour un fichier simple.
		$groupids = explode(',', $groupids);
	}
	if(count($groupids) > 0){
		foreach($groupids as $groupid){
			$group_obj = groups_get_group($groupid);
			if($group_obj){
				groups_add_member($group_obj->id, $userid);
			}
		}
	}
}

/**
 *
 * Créé un groupe connaissant le nom et l'id du parcours.
 *
 * @param string $groupname
 * @param int $courseid
 * @return mixed a fieldset object containing a matching record
 */
function create_group_csv_enrol($groupname, $courseid)
{
	require_once('../../group/lib.php');
	global $DB;

	if($groupname && $courseid){
		$newgroup = new stdClass();
		$newgroup->name = $groupname;
		$newgroup->courseid = $courseid;
		$newgroup->timecreated = time();
		$newid = groups_create_group($newgroup);
		return $DB->get_record('groups', array('id' => $newid), '*', MUST_EXIST);
	}
}

/**
 *
 * Fonction qui gère la création ou non de groupe + ajout d'un user dans ce même groupe.
 *
 * @param array $rawuser
 * @param int $userid
 * @param int $courseid
 */
function group_and_user_management_for_complex_file_csv_enrol($rawuser, $userid, $courseid)
{
	global $DB;

	if($rawuser && $userid && $courseid){
		$groupids = array(); // on créé une liste d'id de groupe

		for($i = 2; $i <= count($rawuser)-1; ++$i) { // on démarre à 2 car les 2 premiers sont l'email et le role.
			if(trim($rawuser[$i])=="") continue;
			$group = $DB->get_record('groups', array('name' => trim($rawuser[$i]), 'courseid' => $courseid));
			if(!($group)){ // on crée le groupe
				$group = create_group_csv_enrol(trim($rawuser[$i]), $courseid);
			}
			$groupids[] = $group->id;
		}
		// on affecte le user dedans
		add_member_to_groups($groupids, $userid, $courseid);
	}
}

/**
 *
 * Utilisation des fonctions de chargement d'un fichier csv et vérification des erreurs.
 *
 * @param array $csvcontent
 * @param string $returnurl
 * @return csv_import_reader
 */
function load_content_csv_enrol($csvcontent, $returnurl)
{
	global $CFG;
	require_once($CFG->libdir.'/csvlib.class.php');

	$iid = csv_import_reader::get_new_iid('uploaduser');
	$cir = new csv_import_reader($iid, 'uploaduser');

	$cir->load_csv_content($csvcontent, 'UTF-8', 'semicolon');

	//$csvloaderror = $cir->get_error();
	unset($csvcontent);

// 	if (!is_null($csvloaderror)) {
// 		print_error('csvloaderror', '', $returnurl, $csvloaderror);
// 	}

	$columns = $cir->get_columns();
	if (empty($columns)) {
		$cir->close();
		$cir->cleanup();
		print_error('cannotreadtmpfile', 'error', $returnurl);
	}

	return $cir;
}

/**
 *
 * Création du tableau de prévisualisation des utilisateurs qui seront ajoutés dans le parcours.
 *
 * @param csv_import_reader $csvcontent
 * @param object $formdata
 * @param string $returnurl
 * @return html_table
 */
function create_preview_csv_enrol_table($csvcontent, $formdata, $returnurl)
{
	global $DB;

	// Test du fichier (simple ou complexe)
	$cir = load_content_csv_enrol($csvcontent, $returnurl);
	$columns = $cir->get_columns();

	$data = array();
	$cir->init();
	$linenum = 0; // column header is first line
	$previewrows = 10; // On affiche les 10 premieres valeurs.

	$data = array();

	// Si le fichier a plus d'une colonne, c'est un fichier complexe
	$v = "/^[a-zA-Z0-9_\-\.]+@[a-zA-Z0-9\-\.]+\.[a-zA-Z]+$/";
	$lines = explode("\n",$csvcontent);
	foreach ($lines as $key=>$line) {
		$linenum++;
		if($linenum > $previewrows) break;
		if(trim($line)=="") continue;

		$rowcols = array();

		if (count($columns) > 1) {
			$rawuser = explode(";",$line);
			if($key == 0 && !(preg_match($v, trim($rawuser[0])))) continue; // Cas où le fichier contient une entete
			$rowcols['email'] = strtolower($rawuser[0]);
			$rowcols['role'] = $rawuser[1];
			$rowcols['groups'] = array();
			for($i = 2; $i <= count($rawuser)-1; ++$i) {
				$rowcols['groups'][$i] = $rawuser[$i];
			}
		}
		else{
			if($key == 0 && !(preg_match($v, trim($line)))) continue; // Cas où le fichier contient une entete
			$rowcols['email'] = strtolower($line);
			$rowcols['role'] = $formdata->role;
			$rowcols['groups'] = array();
			if(isset($formdata->groups)){
				foreach ($formdata->groups as $groupid){
					$group = $DB->get_record('groups', array('id'=>$groupid));
					$rowcols['groups'][]= $group->name;
				}
			}
		}
		$rowcols['groups'] = implode('<br />', $rowcols['groups']);
		$data[] = $rowcols;
	}

	$cir->close();

	$table = new html_table();
	$table->id = "preview_user_csv_enrol";
	$table->attributes['class'] = 'generaltable';
	$table->tablealign = 'center';
	$table->summary = get_string('uploaduserspreview', 'tool_uploaduser');
	$table->head = array("Email","Rôle","Groupe(s)");
	$table->data = $data;
	return $table;
}

/**
 *
 * Fonction qui récupère le delta entre la liste des utilisateurs dans un fichier csv et celle d'un cours
 * puis la suppression des affectations des utilisateurs sur un parcours.
 *
 * @param array $list_email_course
 * @param array $list_email_csv
 * @param int $nb_columns
 * @param enrol_plugin $manual
 * @param string $enrolment
 * @return string $log
 */

function delete_users_csv_enrol(array $list_email_course, $list_email_csv, $nb_columns, $manual, $enrolment)
{
	global $DB, $USER;

	$log = "";
	$v = "/^[a-zA-Z0-9_\-\.]+@[a-zA-Z0-9\-\.]+\.[a-zA-Z]+$/";
	if($nb_columns && $list_email_course && $list_email_csv){
		$lines = explode("\n",$list_email_csv);
		if($nb_columns > 1){
			$list_email_csv_complex = array();
			foreach($lines as $key=>$line){
				if(trim($line)=="") continue;
				$rawuser = explode(";",$line);
				if(trim($rawuser[0])=="") continue;
				if($key == 0 && !(preg_match($v, trim($rawuser[0])))) continue; // Cas où le fichier contient une entete
				$list_email_csv_complex[] = strtolower(trim($rawuser[0]));
			}
			$users_email_to_delete = array_diff($list_email_course, $list_email_csv_complex);
		}else{
			$users_email = array();
			foreach($lines as $key=>$line){
				if($key == 0 && !(preg_match($v, trim($line)))) continue; // Cas où le fichier contient une entete
				$users_email[] = strtolower($line);
			}
			$users_email_to_delete = array_diff($list_email_course, $users_email);
		}
		if(count($users_email_to_delete) > 0){
			foreach($users_email_to_delete as $user_email){
				$user = $DB->get_record('user', array('email' => $user_email));
				if(isset($user) && $USER->id != $user->id){ // Pour ne pas supprimer le user logué
					$manual->unenrol_user($enrolment, $user->id);
					$log .= get_string('deleteduser','block_csv_enrol',fullname($user).' ('.$user->username.')')."\r\n";
				}
			}
		}
	}
	return $log;
}

function download_template_files_table_csv_enrol()
{
	$html = "<table>";
	$dbconn = get_centralized_db_connection();
	$sql = "SELECT * FROM `cr_resources` WHERE `filename` LIKE '%template_csv_enrol%' AND `type` = 'file'";
	$templates = $dbconn->get_records_sql($sql);
	$html .= "<tr>";
	foreach ($templates as $template){
		$keytrad = get_type_csv_enrol_file_template($template->filename);
		$html .= "<td>".get_cr_template_csv_enrol($template->id, $keytrad)."</td>";
	}
	$html .= "</tr>";
	$html .= "</table>";
	return $html;

}

function get_cr_template_csv_enrol($id, $keytrad)
{
	global $CFG;

	$dbconn = get_centralized_db_connection();

	$cr_resource = $dbconn->get_record("cr_resources", array('id'=> $id));
	if(isset($cr_resource)){
		$cleanname = $cr_resource->cleanname;
		$url_resource = '/'.$CFG->centralizedresources_media_types['file'].'/'.$cleanname;

		$resource_link  = get_resource_centralized_secure_url($url_resource, $cr_resource->hashname.$cr_resource->createdate, $CFG->secure_link_timestamp_default);
		$title = get_string("download".$keytrad."csvfiletemplate", 'block_csv_enrol');
		$button = "<button onclick='window.location.href = \"$resource_link\";'>$title</button>";

		return $button;
	}
	return "";
}

function get_type_csv_enrol_file_template($filename)
{
	$posext = strrpos($filename, ".", -1); // Extension du fichier
	$filename = substr($filename, 0, $posext);
	$posext2 = strrpos($filename, "_", -1); // Type de fichier

	return substr($filename, $posext2+1);
}

function generate_firstname_lastname_using_email($email){
    $posRawFirstLastName = strrpos($email, "@", -1);
    if($posRawFirstLastName != false) {
        $rawFirstLastName = substr($email, 0, $posRawFirstLastName);
        $rawFirstLastName = preg_replace("/[^A-Z-.a-z]/", "", $rawFirstLastName); // Si la combinaison nom prénom contient aussi des chiffres (ex: gerard.dupont45@magistere.fr)

        $posFirstLastName = strrpos($rawFirstLastName, ".");
        if ($posFirstLastName != false) {
            $firstName = substr($rawFirstLastName, 0, $posFirstLastName);
            $lastName = substr($rawFirstLastName, $posFirstLastName + 1);
        } else {
            // Cas où il n'y a pas de prénom dans l'email.
            $firstName = "";
            $lastName = $rawFirstLastName;
        }

        return array('firstname' => ucfirst($firstName), 'lastname' => ucfirst($lastName));
    }
}

function file_get_contents_utf8($draftid) {
	global $USER;
	$fs = get_file_storage();
	$context = context_user::instance($USER->id);
	$draftareaFiles = file_get_drafarea_files($draftid, false);
	$fileinfo = $draftareaFiles->list[0];
	file_save_draft_area_files($draftid, $context->id, 'block_csv_enrol', 'transfered', 0);
	$file = $fs->get_file($context->id, 'block_csv_enrol', 'transfered',
			0, $fileinfo->filepath, $fileinfo->filename);


    return $file->get_content();
}