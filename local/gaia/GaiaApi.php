<?php

class GaiaApi
{

    private $data = array();
    private $rawdata = array();
    private $json = '';

    const HIGHLIGHT_BEGIN = '<span style="font-weight:bold">';
    const HIGHLIGHT_END = '</span>';

    const TYPE_COURSE = 'course';
    const TYPE_VIA = 'via';

    function __construct()
    {
    }


    function search_dispositifs($search,$startdate,$enddate)
    {
        global $DB, $USER;

        if (!$USER->id) {
            return false;
        }

        $search = preg_replace("/[^A-Za-z0-9 ]/", ' ', $search);

        $where = '';
        $words_highlight_source = array();
        $words_highlight_replace = array();
        if (strlen(trim($search)) > 2) {

            $search_words = explode(' ', trim($search));
            $words_compare = array();
            foreach ($search_words AS $search_word) {
                $search_word = trim($search_word);
                if (strlen($search_word) > 2) {
                    $words_compare[] = "CONCAT(gf.dispositif_id,' ',gf.dispositif_name) LIKE '%".$search_word."%'";
                    $words_highlight_source[] = '/('.$search_word.')/i';
                    $words_highlight_replace[] = self::HIGHLIGHT_BEGIN.'$1'.self::HIGHLIGHT_END;
                }
            }

            if (count($words_compare) > 0) {
                $where = implode(' AND ', $words_compare).' AND ';
            }
        }

        $dispositifs = $DB->get_records_sql(
       "SELECT gf.id, gf.dispositif_id AS dispositif_id, gf.dispositif_name AS dispositif_name, gf.table_name AS dispositif_origin, COUNT(DISTINCT module_id) AS modulecount
		FROM {local_gaia_formations} gf
		WHERE ".$where."
        gf.startdate BETWEEN ? AND ?
		GROUP BY gf.dispositif_id
		ORDER BY gf.dispositif_id ASC
        LIMIT 100",array($startdate,$enddate));

        foreach ($dispositifs AS $key=>$dispositif)
        {
            $dispositifs[$key]->dispositif_name = str_replace('"','',$dispositif->dispositif_name);
            $dispositifs[$key]->dispositif_id_high = preg_replace($words_highlight_source, $words_highlight_replace, $dispositif->dispositif_id);
            $dispositifs[$key]->dispositif_name_high = preg_replace($words_highlight_source, $words_highlight_replace, $dispositifs[$key]->dispositif_name);
        }

        if ($dispositifs !== false) {
            return $dispositifs;
        }
        return false;
    }


    function search_dispositifs_json($search,$startdate,$enddate)
    {
        $dispositifs = $this->search_dispositifs($search,$startdate,$enddate,true);
        if ($dispositifs == false){return '{"dispositifs":[]}';}

        $i = 0;
        $json_dispositifs = array();
        foreach($dispositifs AS $dispositif)
        {
            $json_dispositifs[$i] = new stdClass();
            $json_dispositifs[$i]->dispositif_id = $dispositif->dispositif_id;
            $json_dispositifs[$i]->dispositif_id_high = $dispositif->dispositif_id_high;
            $json_dispositifs[$i]->dispositif_name = $dispositif->dispositif_name;
            $json_dispositifs[$i]->dispositif_name_high = $dispositif->dispositif_name_high;
            $json_dispositifs[$i]->dispositif_origin = $dispositif->dispositif_origin;
            $json_dispositifs[$i]->modulecount = $dispositif->modulecount;
            $i++;
        }

        $json = new stdClass();
        $json->dispositifs = $json_dispositifs;

        return json_encode($json);
    }

    function search_modules($search,$dispositifid,$startdate,$enddate)
    {
        global $DB, $USER;

        if (!$USER->id) {
            return false;
        }

        $search = preg_replace("/[^A-Za-z0-9 ]/", ' ', $search);

        $words_highlight_source = array();
        $words_highlight_replace = array();
        $where = '';
        if (strlen(trim($search)) > 2) {

            $search_words = explode(' ', trim($search));
            $words_compare = array();
            foreach ($search_words AS $search_word) {
                if (strlen($search_word) > 2) {
                    $words_compare[] = "CONCAT(gf.module_id,' ',gf.module_name) LIKE '%".$search_word."%'";
                    $words_highlight_source[] = '/('.$search_word.')/i';
                    $words_highlight_replace[] = self::HIGHLIGHT_BEGIN.'$1'.self::HIGHLIGHT_END;
                }
            }

            if (count($words_compare) > 0) {
                $where = implode(' AND ', $words_compare).' AND ';
            }
        }

        $modules = $DB->get_records_sql(
       "SELECT gf.id, gf.dispositif_id AS dispositif_id, gf.module_id AS module_id, gf.module_name AS module_name, COUNT(DISTINCT session_id) AS sessioncount
		FROM {local_gaia_formations} gf
		WHERE ".$where."
        gf.dispositif_id = ? AND gf.startdate BETWEEN ? AND ?
		GROUP BY gf.module_id
		ORDER BY gf.module_id ASC",array($dispositifid,$startdate,$enddate));

        foreach ($modules AS $key=>$module)
        {
            $modules[$key]->module_name = str_replace('"','',$module->module_name);
            $modules[$key]->module_id_high = preg_replace($words_highlight_source, $words_highlight_replace, $module->module_id);
            $modules[$key]->module_name_high = preg_replace($words_highlight_source, $words_highlight_replace, $modules[$key]->module_name);
        }

        if ($modules !== false) {
            return $modules;
        }
        return false;
    }


    function search_modules_json($search,$dispositifid,$startdate,$enddate)
    {
        $modules = $this->search_modules($search,$dispositifid,$startdate,$enddate);
        if ($modules === false){return false;}

        $i = 0;
        $json_modules = array();
        foreach($modules AS $module)
        {
            $json_modules[$i] = new stdClass();
            $json_modules[$i]->dispositif_id = $module->dispositif_id;
            $json_modules[$i]->module_id = $module->module_id;
            $json_modules[$i]->module_id_high = $module->module_id_high;
            $json_modules[$i]->module_name = $module->module_name;
            $json_modules[$i]->module_name_high = $module->module_name_high;
            $json_modules[$i]->sessioncount = $module->sessioncount;
            $i++;
        }

        $json = new stdClass();
        $json->modules = $json_modules;

        return json_encode($json);
    }

    function search_sessions($search,$moduleid,$dispositifid,$startdate,$enddate)
    {
        global $DB, $USER;

        if (!$USER->id) {
            return false;
        }

        $search = preg_replace("/[^A-Za-z0-9 ]/", ' ', $search);

        $words_highlight_source = array();
        $words_highlight_replace = array();
        $where = '';
        if (strlen(trim($search)) > 2) {

            $search_words = explode(' ', trim($search));
            $words_compare = array();
            foreach ($search_words AS $search_word) {
                if (strlen($search_word) > 2) {
                    $words_compare[] = "gf.formation_place LIKE '%".$search_word."%'";
                    $words_highlight_source[] = '/('.$search_word.')/i';
                    $words_highlight_replace[] = self::HIGHLIGHT_BEGIN.'$1'.self::HIGHLIGHT_END;
                }
            }

            if (count($words_compare) > 0) {
                $where = implode(' AND ', $words_compare).' AND ';
            }
        }

        $sessions = $DB->get_records_sql(
            "SELECT gf.id, gf.dispositif_id AS dispositif_id, gf.module_id AS module_id, gf.session_id AS session_id, gf.formation_place AS formation_place, FROM_UNIXTIME(gf.startdate, '%d/%m/%Y %H:%i') AS startdate, FROM_UNIXTIME(gf.enddate, '%d/%m/%Y %H:%i') AS enddate,
        (
            SELECT COUNT(*) FROM {local_gaia_stagiaires} lgs WHERE lgs.session_id = gf.session_id AND lgs.table_name = gf.table_name
        ) AS participants,
        (
            SELECT COUNT(*) FROM {local_gaia_intervenants} lgs WHERE lgs.module_id = gf.module_id AND lgs.table_name = gf.table_name
        ) AS formateurs,
        IFNULL((
            SELECT course_id FROM {local_gaia_session_course} sc WHERE sc.dispositif_id = gf.dispositif_id AND sc.module_id = gf.module_id AND sc.session_id = gf.session_id
        ),0) session_course,
        IFNULL((
            SELECT via_id FROM {local_gaia_session_via} sv WHERE sv.dispositif_id = gf.dispositif_id AND sv.module_id = gf.module_id AND sv.session_id = gf.session_id
        ),0) session_via
		FROM {local_gaia_formations} gf
		WHERE ".$where."
        gf.dispositif_id = ? AND gf.module_id = ? AND gf.startdate BETWEEN ? AND ?
		ORDER BY gf.session_id ASC",array($dispositifid,$moduleid,$startdate,$enddate));

        foreach ($sessions AS $key=>$session) {
            $sessions[$key]->formation_place = str_replace('"','',$session->formation_place);
            $sessions[$key]->formation_place_high = preg_replace($words_highlight_source, $words_highlight_replace, $sessions[$key]->formation_place);
        }

        if ($sessions !== false) {
            return $sessions;
        }
        return false;
    }


    function search_sessions_json($search,$moduleid,$dispositifid,$startdate,$enddate)
    {
        $sessions = $this->search_sessions($search,$moduleid,$dispositifid,$startdate,$enddate);
        if ($sessions === false){return false;}

        $i = 0;
        $json_sessions = array();
        foreach($sessions AS $session) {
            $json_sessions[$i] = new stdClass();
            $json_sessions[$i]->dispositif_id = $session->dispositif_id;
            $json_sessions[$i]->module_id = $session->module_id;
            $json_sessions[$i]->session_id = $session->session_id;
            $json_sessions[$i]->formation_place = $session->formation_place;
            $json_sessions[$i]->formation_place_high = $session->formation_place_high;
            $json_sessions[$i]->startdate = $session->startdate;
            $json_sessions[$i]->enddate = $session->enddate;
            $json_sessions[$i]->participants = $session->participants;
            $json_sessions[$i]->formateurs = $session->formateurs;
            $json_sessions[$i]->linked = false;

            if ($session->session_course > 0) {
                $json_sessions[$i]->linked = true;
                $json_sessions[$i]->linkedurl = (new moodle_url('/course/view.php',array('id'=>$session->session_course)))->out();
            }
            else if ($session->session_via > 0) {
                $json_sessions[$i]->linked = true;
                $json_sessions[$i]->linkedurl = (new moodle_url('/mod/via/view.php',array('id'=>$session->session_via)))->out();
            }

            $i++;
        }
        $json = new stdClass();
        $json->sessions = $json_sessions;

        return json_encode($json);
    }

    function get_session($sessionid,$moduleid,$dispositifid,$courseid)
    {
        global $DB, $USER;

        if (!$USER->id) {
            return false;
        }

        $session = $DB->get_record_sql(
       "SELECT gf.id, gf.dispositif_id AS dispositif_id, gf.module_id AS module_id, gf.session_id AS session_id, gf.formation_place AS formation_place, FROM_UNIXTIME(gf.startdate, '%d/%m/%Y %H:%i') AS startdate, FROM_UNIXTIME(gf.enddate, '%d/%m/%Y %H:%i') AS enddate,
        (
            SELECT GROUP_CONCAT(CONCAT(lgs.id,'|',lgs.name,'|',lgs.firstname) SEPARATOR '[&]') FROM {local_gaia_stagiaires} lgs WHERE lgs.session_id = gf.session_id AND lgs.table_name = gf.table_name
        ) AS participants,
        (
            SELECT GROUP_CONCAT(CONCAT(lgi.id,'|',lgi.name,'|',lgi.firstname) SEPARATOR '[&]') FROM {local_gaia_intervenants} lgi WHERE lgi.module_id = gf.module_id AND lgi.table_name = gf.table_name
        ) AS formateurs,
        (
            SELECT GROUP_CONCAT(CONCAT(g.id,'|',g.name) ORDER BY g.name SEPARATOR '[&]') FROM {groups} g WHERE g.courseid = ? 
        ) AS groups,
        (
            SELECT GROUP_CONCAT(CONCAT(g.id,'|',g.name) SEPARATOR '[&]') FROM {groupings} g WHERE g.courseid = ?
        ) AS groupings
		FROM {local_gaia_formations} gf
		WHERE gf.dispositif_id = ? AND gf.module_id = ? AND gf.session_id = ?
		ORDER BY gf.session_id ASC",array($courseid,$courseid,$dispositifid,$moduleid,$sessionid));

        return $session;
    }


    function get_session_json($sessionid,$moduleid,$dispositifid,$courseid)
    {
        $session = $this->get_session($sessionid,$moduleid,$dispositifid,$courseid);
        if ($session == false){echo 'fefz';return false;}

        $participants_json = array();
        if (strlen($session->participants) > 2)
        {
            $participants = explode('[&]', $session->participants);

            foreach($participants AS $participant)
            {
                $participant_data = explode('|', $participant);
                $participant_json = new stdClass();
                $participant_json->id = $participant_data[0];
                $participant_json->lastname = $participant_data[1];
                $participant_json->firstname = $participant_data[2];

                $participants_json[] = $participant_json;
            }
        }

        $formateurs_json = array();
        if (strlen($session->formateurs) > 2)
        {
            $formateurs = explode('[&]', $session->formateurs);

            foreach($formateurs AS $formateur)
            {
                $formateur_data = explode('|', $formateur);
                $formateur_json = new stdClass();
                $formateur_json->id = $formateur_data[0];
                $formateur_json->lastname = $formateur_data[1];
                $formateur_json->firstname = $formateur_data[2];

                $formateurs_json[] = $formateur_json;
            }
        }

        $groups_json = array();
        if (strlen($session->groups) > 2)
        {
            $groups = explode('[&]', $session->groups);

            foreach($groups AS $group)
            {
                $group_data = explode('|', $group);
                $group_json = new stdClass();
                $group_json->id = $group_data[0];
                $group_json->name = $group_data[1];

                $groups_json[] = $group_json;
            }
        }

        $groupings_json = array();
        if (strlen($session->groupings) > 2)
        {
            $groupings = explode('[&]', $session->groupings);

            foreach($groupings AS $grouping)
            {
                $grouping_data = explode('|', $grouping);
                $grouping_json = new stdClass();
                $grouping_json->id = $grouping_data[0];
                $grouping_json->name = $grouping_data[1];

                $groupings_json[] = $grouping_json;
            }
        }

        $json_session = new stdClass();
        $json_session->dispositif_id = $session->dispositif_id;
        $json_session->module_id = $session->module_id;
        $json_session->session_id = $session->session_id;
        $json_session->formation_place = $session->formation_place;
        $json_session->participants = $participants_json;
        $json_session->formateurs = $formateurs_json;
        $json_session->groups = $groups_json;
        $json_session->groupings = $groupings_json;

        $json = new stdClass();
        $json->session = $json_session;

        return json_encode($json);
    }


}