<?php

require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot . '/mod/via/lib.php');


class GaiaUtils
{
	static public function is_a_session($course_id)
	{
		global $DB;

		$sessionFormationContext = $DB->get_record_sql('SELECT path FROM {course_categories} WHERE name ="Session de formation"');

		$data = $DB->get_record_sql('
SELECT cc.id, cc.path
FROM {course_categories} cc
JOIN {course} co ON co.category = cc.id
WHERE co.id='.$course_id.' AND
(cc.path = "' .$sessionFormationContext->path. '" OR cc.path LIKE "'.$sessionFormationContext->path.'/%")
' );

		return ($data !== false);
	}

	static public function get_sessions($session_id)
	{
		global $DB;

		$session_gaia_info = $DB->get_records_sql('
SELECT
	lgf.id,
	lgf.dispositif_id,
	lgf.dispositif_name,
	lgf.module_id,
	lgf.module_name,
	lgf.session_id,
	lgf.group_number,
	lgf.startdate,
	lgf.enddate,
	lgf.place_type,
	lgf.formation_place
FROM {local_gaia_session_course} lgsc
JOIN {local_gaia_formations} lgf ON (lgf.session_id = lgsc.session_id AND lgf.dispositif_id = lgsc.dispositif_id AND lgf.module_id = lgsc.module_id)
WHERE lgsc.course_id='.$session_id);

		return $session_gaia_info;
	}

	static public function get_gaia_via_info($via_id)
	{
		global $DB;

		$session_gaia_info = $DB->get_record_sql('
SELECT
	lgf.id,
	lgf.dispositif_id,
	lgf.dispositif_name,
	lgf.module_id,
	lgf.module_name,
	lgf.session_id,
	lgf.group_number,
	lgf.startdate,
	lgf.enddate,
	lgf.place_type,
	lgf.formation_place
FROM {local_gaia_session_via} lgsv
JOIN {local_gaia_formations} lgf ON (lgf.session_id = lgsv.session_id AND lgf.dispositif_id = lgsv.dispositif_id AND lgf.module_id = lgsv.module_id)
WHERE lgsv.via_id='.$via_id);

		return $session_gaia_info;
	}

	static public function get_via_block($courseid)
	{
		global $DB;

		$viaBlock = $DB->get_records_sql('
SELECT v.id, v.name, v.datebegin, v.duration
FROM {via} v
WHERE v.course='.$courseid);

		return $viaBlock;
	}

	static public function bind_session_with_gaia($course_id, $session_id, $dispositif_id, $module_id)
	{
		global $DB;

		$params = array(
				'course_id' 		=> $course_id,
				'session_id' 	=> $session_id,
				'dispositif_id' 	=> $dispositif_id,
				'module_id' 		=> $module_id
		);

		if(($record = $DB->get_record('local_gaia_session_course', $params)) !== false){
			return $record;
		}

		$data = new stdClass();
		$data->course_id 		= $course_id;
		$data->session_id 	= $session_id;
		$data->dispositif_id 	= $dispositif_id;
		$data->module_id 		= $module_id;

		return $DB->insert_record('local_gaia_session_course', $data);
	}

	static public function unbind_session_with_gaia($course_id, $session_id, $dispositif_id, $module_id)
	{
		global $DB;

		$params = array(
				'course_id' 		=> $course_id,
				'session_id' 	    => $session_id,
				'dispositif_id' 	=> $dispositif_id,
				'module_id' 		=> $module_id
		);

		return $DB->delete_records('local_gaia_session_course', $params);
	}

	static public function bind_activity_with_gaia($activity_id, $session_id, $dispositif_id, $module_id)
	{
		global $DB;

		$params = array(
            'via_id' 		    => $activity_id,
            'session_id' 	    => $session_id,
            'dispositif_id' 	=> $dispositif_id,
            'module_id' 		=> $module_id
		);

		if(($record = $DB->get_record('local_gaia_session_via', $params)) !== false){
            return $record;
		}

		$data = new stdClass();
		$data->via_id 		= $activity_id;
		$data->session_id 	= $session_id;
		$data->dispositif_id 	= $dispositif_id;
		$data->module_id 		= $module_id;

		return $DB->insert_record('local_gaia_session_via', $data);
	}

	static public function unbind_activity_with_gaia($via_id, $session_id, $dispositif_id, $module_id)
	{
		global $DB;

		$params = array(
				'via_id' 		=> $via_id,
				'session_id' 	=> $session_id,
				'dispositif_id' 	=> $dispositif_id,
				'module_id' 		=> $module_id
		);

		return $DB->delete_records('local_gaia_session_via', $params);
	}

	static public function get_dispositif_name($dispositif_id)
	{
		global $DB;

		$res = $DB->get_record_sql('SELECT gf.dispositif_name, gf.table_name FROM {local_gaia_formations} gf WHERE gf.dispositif_id = "' . $dispositif_id . '" LIMIT 0,1');

		if($res !== false){
			return array($res->dispositif_name, $res->table_name);
		}

		return array('', '');
	}

	static public function get_session_description($id, $dispositifid, $moduleid)
	{
		global $DB;

		$session_gaia_info = $DB->get_record('local_gaia_formations', array('session_id' => $id, 'dispositif_id' => $dispositifid, 'module_id' => $moduleid));

		if($session_gaia_info === false){
			return null;
		}else{
			$session = new stdClass();
			$session->id = 				$session_gaia_info->id;
			$session->dispositif_id = 	$session_gaia_info->dispositif_id;
			$session->dispositif_name = $session_gaia_info->dispositif_name;
			$session->module_id = 		$session_gaia_info->module_id;
			$session->module_name = 	$session_gaia_info->module_name;
			$session->session_id = 		$session_gaia_info->session_id;
			$session->group_number = 	$session_gaia_info->group_number;
			$session->startdate = 		$session_gaia_info->startdate;
			$session->enddate = 		$session_gaia_info->enddate;
			$session->place_type = 		$session_gaia_info->place_type;
			$session->formation_place = $session_gaia_info->formation_place;
			$session->table_name = $session_gaia_info->table_name;
			$session->intervenants = array();
			$session->stagiaires = array();

			$gaia_intervenants = $DB->get_records('local_gaia_intervenants', array('module_id' => $session_gaia_info->module_id, 'table_name' => $session_gaia_info->table_name));
			if(count($gaia_intervenants) > 0 ){
				foreach($gaia_intervenants as $gaia_intervenant)
				{
					$intervenant = new stdClass();
					$intervenant->name = $gaia_intervenant->name;
					$intervenant->firstname = $gaia_intervenant->firstname;
					$intervenant->email = $gaia_intervenant->email;
					$session->intervenants[$gaia_intervenant->id] = $intervenant;
				}
			}

			$gaia_stagiaires = $DB->get_records('local_gaia_stagiaires', array('session_id' => $session_gaia_info->session_id, 'table_name' => $session_gaia_info->table_name));
			if(count($gaia_stagiaires) > 0 ){
				foreach($gaia_stagiaires as $gaia_stagiaire)
				{
					$stagiaire = new stdClass();
					$stagiaire->name = $gaia_stagiaire->name;
					$stagiaire->firstname = $gaia_stagiaire->firstname;
					$stagiaire->email = $gaia_stagiaire->email;
					$session->stagiaires[$gaia_stagiaire->id] = $stagiaire;
				}
			}

			return $session;

		}
	}

	static public function subscribe_user($enrolConfig, $usersToSubscribe, $override_subscription, $groupid)
	{
		global $DB, $CFG;

		$enrolConfig->maxparticipant = count($usersToSubscribe);

		$inscription_method = GaiaUtils::create_inscription_method($enrolConfig);

		if($inscription_method === false){
			throw new Exception("Enrol method can't be create");
		}

		$user_enrolement = enrol_get_plugin('self');

		$usersAlreadyEnrolled = $DB->get_records_sql('
SELECT u.id, u.email
FROM {user_enrolments} ue
JOIN {user} u ON ue.userid = u.id
WHERE ue.enrolid='.$inscription_method->id);



		$emailUserSubscribed = array();
		// on inscrit les utilisateurs venant de gaia
		// si l'utilisateur possede un compte shibboleth on l'inscrit
		// sinon on cree un compte manuel qui sera mis a jour
		// a la prochaine connexion de l'utilisateur via shibboleth

        $userids = array();
		foreach($usersToSubscribe as $usergaia){
			$user = $DB->get_record('user', array('email' => $usergaia->email));
			if($user !== false) {
				if (!$user->deleted && !$user->suspended)
				{
					if(!isset($usersAlreadyEnrolled[$user->id])) {
						$user_enrolement->enrol_user($inscription_method, $user->id,  $inscription_method->roleid, time(),  0, NULL);
					}else{
						unset($usersAlreadyEnrolled[$user->id]);
					}
				}
			} else {
                $array = GaiaUtils::generate_firstname_lastname_using_email($usergaia->email); // Génération des noms et prénoms à partir de l'email.

				$user = new stdClass();
				$user->email = $usergaia->email;
				$user->password = generate_password(8);
				$user->country = 'FR';
				$user->lang = $CFG->lang;
				$user->confirmed = 1;
				$user->mnethostid = 1;
				$user->username = str_replace(array('@', '.', '-'), '', $usergaia->email);
                $user->firstname = ucfirst($array['firstname']);
                $user->lastname = ucfirst($array['lastname']);

				$user->id = user_create_user($user);

				$user_enrolement->enrol_user($inscription_method, $user->id,  $inscription_method->roleid, time(),  0, NULL);
			}

			$userids[] = $user->id;

			if($groupid != null){
				groups_add_member($groupid, $user->id);
			}

			$emailUserSubscribed[] = $usergaia->email;
		}

		// on desinscrit les utilisateurs restants
		// quand on est en mode ecrasement des inscriptions
		if($override_subscription)
		{
			foreach($usersAlreadyEnrolled as $user){
				$user_enrolement->unenrol_user($inscription_method, $user->id);
			}
		}

		return $userids;
	}

	static public function unsubscribe_user($enrolName, $courseid)
	{
		global $DB;

		$enrolMethod = $DB->get_record('enrol', array('enrol' => 'self', 'courseid' => $courseid, 'name' => $enrolName));

		if($enrolMethod === false) return;

		$user_enrolement = enrol_get_plugin('self');

		$user_enrolement->delete_instance($enrolMethod);
	}

	static public function create_inscription_method($enrolConfig)
	{
		global $DB;

		$name = $enrolConfig->name;
		$courseid = $enrolConfig->courseid;
		$roleshortname = $enrolConfig->roleshortname;

		$role = $DB->get_record('role', array('shortname' => $roleshortname));

		if($role === false) return false;

		$whereclauses = array('courseid'=>$courseid, 'enrol'=>'self', 'name' => $name, 'roleid' => $role->id);

		$inscriptionmethod = $DB->get_record('enrol', $whereclauses);

		if($inscriptionmethod !== false){
			$inscriptionmethod->customint3 = $enrolConfig->maxparticipant;
			$DB->update_record('enrol', $inscriptionmethod);
			return $inscriptionmethod;
		}

		$record_enrol = new stdClass();
		$record_enrol->enrol = 'self';
		$record_enrol->courseid = $courseid;
		$record_enrol->name = $name;
		$record_enrol->roleid = $role->id;
		$record_enrol->customint3 = $enrolConfig->maxparticipant;

		$countEnrol = $DB->count_records('enrol', array('courseid' => $courseid));

		$record_enrol->sortorder = $countEnrol+1;

		$DB->insert_record('enrol', $record_enrol, false);

		return $DB->get_record('enrol', $whereclauses);
	}

	static public function get_intervenant_gaia($sessiongaiaid, $dispositifid, $moduleid, $restrictedemail='')
	{
		global $DB;

		$sgaia = $DB->get_record('local_gaia_formations', array('session_id' => $sessiongaiaid, 'dispositif_id' => $dispositifid, 'module_id' => $moduleid));

		if($sgaia !== false){

		    $sql = 'SELECT gi.id, gi.email
FROM {local_gaia_intervenants} gi 
	JOIN {local_gaia_formations} gf ON gi.module_id = gf.module_id
WHERE gf.session_id='.$sessiongaiaid.' AND gf.table_name="'.$sgaia->table_name.'"';

		    if($restrictedemail != ''){
		        $sql .= ' AND gi.email <> "'.$restrictedemail.'"';
            }

			return $DB->get_records_sql($sql);

		}

		return false;
	}

	static public function get_stagiaire_gaia($sessiongaiaid, $dispositifid, $moduleid)
	{
		global $DB;

		return $DB->get_records_sql('
SELECT gi.id, gi.email
FROM {local_gaia_stagiaires} gi 
INNER JOIN {local_gaia_formations} gf ON gf.session_id=gi.session_id
WHERE gi.session_id='.$sessiongaiaid.' AND gf.dispositif_id="'.$dispositifid.'" AND module_id='.$moduleid.' AND gi.table_name=gf.table_name');
	}

    static public function get_participant($courseid, $sessiongaia, $dispositifid, $moduleid)
    {
        global $DB;

        $record = $DB->get_records_sql('
 SELECT ue.id, ue.userid
 FROM {enrol} e
 INNER JOIN {user_enrolments} ue ON ue.enrolid = e.id
 INNER JOIN {local_gaia_formations} 
 WHERE e.name = "GAIA-PARTICIPANT_'.$sessiongaia.'_'.$dispositifid.'" AND e.courseid = '.$courseid);

        return $record;
    }

	static public function get_participant_formateur_ids($courseid, $sessiongaia, $dispositifid, $moduleid)
	{
		global $DB;

		$records = $DB->get_records_sql('
SELECT ue.id, ue.userid
FROM {enrol} e
INNER JOIN {user_enrolments} ue ON ue.enrolid = e.id
WHERE (
e.name = "GAIA-PARTICIPANT_'.$sessiongaia.'_'.$dispositifid.'_'.$moduleid.'" 
 OR e.name = "GAIA-FORMATEUR_'.$sessiongaia.'_'.$dispositifid.'_'.$moduleid.'"
)
 AND e.courseid = '.$courseid);


		$result = array();

		foreach($records as $data){
		    $result[$data->userid] = $data->userid;
        }

		return $result;
	}

	static public function get_participant_progession($courseid, $sessiongaia, $dispositifid, $moduleid)
    {
        global $DB;

        $records = $DB->get_records_sql('SELECT u.id, u.firstname, u.lastname, u.email, pc.is_complete, ta.appelation_officielle
            FROM `mdl_user` u
            JOIN mdl_role_assignments ra ON u.id = ra.userid
            JOIN mdl_role r ON ra.roleid = r.id
            JOIN mdl_context c ON ra.contextid = c.id
            LEFT JOIN mdl_user_info_data ui ON (u.id = ui.userid AND ui.fieldid = (SELECT id FROM mdl_user_info_field WHERE shortname = "rne"))
            LEFT JOIN mdl_t_uai ta ON ui.data = ta.code_rne
            LEFT JOIN mdl_progress_complete pc ON (pc.courseid = c.instanceid AND pc.userid = u.id )
            WHERE c.contextlevel = 50
            AND c.instanceid = '.$courseid.'
            AND r.shortname = "participant"
            AND (u.id IN  (SELECT ue.userid
                FROM {enrol} e
                INNER JOIN {user_enrolments} ue ON ue.enrolid = e.id
                WHERE e.name = "GAIA-PARTICIPANT_'.$sessiongaia.'_'.$dispositifid.'_'.$moduleid.'"
                AND e.courseid = '.$courseid.')
            )
            ORDER BY u.lastname, u.firstname');

        return $records;
    }

    static public function get_other_progression($courseid)
    {
        global $DB;

        $records = $DB->get_records_sql('SELECT u.id, u.firstname, u.lastname, u.email, pc.is_complete, ta.appelation_officielle
            FROM `mdl_user` u
            JOIN mdl_role_assignments ra ON u.id = ra.userid
            JOIN mdl_role r ON ra.roleid = r.id
            JOIN mdl_context c ON ra.contextid = c.id
            LEFT JOIN mdl_user_info_data ui ON (u.id = ui.userid AND ui.fieldid = (SELECT id FROM mdl_user_info_field WHERE shortname = "rne"))
            LEFT JOIN mdl_t_uai ta ON ui.data = ta.code_rne
            LEFT JOIN mdl_progress_complete pc ON (pc.courseid = c.instanceid AND pc.userid = u.id )
            WHERE c.contextlevel = 50
            AND c.instanceid = '.$courseid.'
            AND r.shortname = "participant"
            AND (u.id NOT IN (SELECT ue.userid
                FROM {enrol} e
                INNER JOIN {user_enrolments} ue ON ue.enrolid = e.id
                WHERE e.name LIKE "GAIA-PARTICIPANT_%"
                AND e.courseid = '.$courseid.')
            )
            ORDER BY u.lastname, u.firstname');

        return $records;
    }

    static function get_formateurs($courseid, $sessiongaia, $dispositifid, $moduleid)
    {
        global $DB;

        $records = $DB->get_records_sql('SELECT u.id, u.firstname, u.lastname, u.email
FROM {enrol} e
INNER JOIN {user_enrolments} ue ON ue.enrolid = e.id
INNER JOIN {user} u ON u.id=ue.userid
WHERE e.name = "GAIA-FORMATEUR_'.$sessiongaia.'_'.$dispositifid.'_'.$moduleid.'"
AND e.courseid = '.$courseid.'
ORDER BY u.lastname, u.firstname');

        return $records;
    }
    static function get_all_other_formateurs($courseid)
    {
        global $DB;

        $records = $DB->get_records_sql('SELECT u.id, u.firstname, u.lastname, u.email
            FROM `mdl_user` u
            JOIN mdl_role_assignments ra ON u.id = ra.userid
            JOIN mdl_role r ON ra.roleid = r.id
            JOIN mdl_context c ON ra.contextid = c.id
            WHERE c.contextlevel = 50
            AND c.instanceid = '.$courseid.'
            AND r.shortname = "formateur"
            AND (u.id NOT IN (SELECT ue.userid
                FROM {enrol} e
                INNER JOIN {user_enrolments} ue ON ue.enrolid = e.id
                WHERE e.name LIKE "GAIA-FORMATEUR_%"
                AND e.courseid = '.$courseid.')
            )
            ORDER BY u.lastname, u.firstname');

        return $records;
    }

    static public function get_session_info($course_id, $session_id, $dispositif_id, $module_id)
    {
        global $DB;

        $session_gaia_info = $DB->get_record_sql('
SELECT
	lgf.id,
	lgf.dispositif_id,
	lgf.dispositif_name,
	lgf.module_id,
	lgf.module_name,
	lgf.session_id,
	lgf.group_number,
	lgf.startdate,
	lgf.enddate,
	lgf.place_type,
	lgf.formation_place
FROM {local_gaia_session_course} lgsc
JOIN {local_gaia_formations} lgf ON (lgf.session_id = lgsc.session_id AND lgf.dispositif_id = lgsc.dispositif_id AND lgf.module_id = lgsc.module_id)
WHERE lgsc.course_id='.$course_id.' AND lgsc.session_id='.$session_id.' AND lgsc.dispositif_id="'.$dispositif_id.'" AND lgsc.module_id='.$module_id);

        return $session_gaia_info;
    }

	static function get_table_name_for_dispositif($dispositif_id)
	{
		global $DB;

		$record = $DB->get_record('local_gaia_formations', array('dispositif_id' => $dispositif_id));

		if($record !== false){
			return $record->table_name;
		}

		return $record;
	}

	static public function generate_firstname_lastname_using_email($email){
        $posRawFirstLastName = strrpos($email, "@", -1);
        if($posRawFirstLastName != false) {
            $rawFirstLastName = substr($email, 0, $posRawFirstLastName);
            $rawFirstLastName = preg_replace("/[^A-Z-.a-z]/", "", $rawFirstLastName); // Si la combinaison nom prénom contient aussi des chiffres (ex: gerard.dupont45@magistere.fr)

            $posFirstLastName = strrpos($rawFirstLastName, ".");
            if ($posFirstLastName != false) {
                $firstName = substr($rawFirstLastName, 0, $posFirstLastName);
                $lastName = substr($rawFirstLastName, $posFirstLastName + 1);
            } else {
                // Cas ou il n'y a pas de prenom dans l'email.
                $firstName = "";
                $lastName = $rawFirstLastName;
            }

            return array('firstname' => ucfirst($firstName), 'lastname' => ucfirst($lastName));
        }
    }

    static public function subscribe_via_users($viaid, $animatorids, $participantids, $groupingid)
    {
        global $DB;

        $via = $DB->get_record('via', array('id' => $viaid));

        $cm = $DB->get_record_sql('SELECT cm.*
FROM {course_modules} cm 
INNER JOIN {modules} m ON m.id=cm.module 
WHERE cm.course='.$via->course.' AND cm.instance='.$via->id.' AND m.name="via"' );

        if($via === false){
            throw new Exception('Via id missing');
        }

        if($cm === false){
            throw new Exception('Course module is missing');
        }

        $via->enroltype = 1; // manual enrol
        $via->save_animators = implode(', ', $animatorids);
        $via->save_participants = implode(', ', $participantids);
        $via->groupingid = $groupingid;
        $via->save_host = '';

        // update mod
        $cm->groupmembersonly = 1;
        $cm->groupingid = $via->groupingid;

        $DB->update_record('course_modules', $cm);

        via_update_instance($via);
    }

    static public function unsubscribe_via_users($viaid)
    {
	    global $DB;

        $via = $DB->get_record('via', array('id' => $viaid));

        $cm = $DB->get_record_sql('SELECT cm.*
FROM {course_modules} cm 
INNER JOIN {modules} m ON m.id=cm.module 
WHERE cm.course='.$via->course.' AND cm.instance='.$via->id.' AND m.name="via"' );

        if($via === false){
            throw new Exception('Via id missing');
        }

        if($cm === false){
            throw new Exception('Course module is missing');
        }

        $via->save_animators = '';
        $via->save_participants = '';
        $via->save_host = '';

        via_update_instance($via);
    }
}
