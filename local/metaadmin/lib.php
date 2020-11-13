<?php

require_once($CFG->dirroot.'/local/magisterelib/databaseConnection.php');
require_once($CFG->dirroot.'/local/coursehub/CourseHub.php');
$enable_mmcached = true;

/**
 * @param $academie
 * @param $userrole
 * @param $parcoursidentifiant_year
 * @param $gaia_origine
 * @param $parcoursidentifiant_name
 * @param $lastconnmin
 * @param $lastconnmax
 * @return bool|StdClass
 */
function get_aca_stats($academie, $userrole, $parcoursidentifiant_year, $gaia_origine, $parcoursidentifiant_name, $lastconnmin, $lastconnmax, $view_id = false,$select_off,$select_offlocales,$select_ofp,$select_no_pub = NULL)
{
    global $CFG, $DB, $enable_mmcached;

    $courses ="";
    if($view_id){
        $view = get_custom_views_by_id($view_id);
        foreach($view->scourses AS $course) {
            $courses += $course;
        }
    }
    $mkey = 'metaadmin_statsparticipants_search_'.hash('sha256', $academie.$userrole.$parcoursidentifiant_year.$gaia_origine.$parcoursidentifiant_name.$lastconnmin.$lastconnmax.$view_id.$select_off.$select_ofp.$select_no_pub.$courses.$select_offlocales);
	if ($enable_mmcached) {
		$cache = mmcached_get($mkey);
		if ( $cache !== false )	{
			return $cache;
		}
	}

    $parcoursidentifiant_sql = '';
    if (strlen($parcoursidentifiant_year) == 2 && $gaia_origine > 0 && strlen($parcoursidentifiant_name) > 1) {
        $parcoursidentifiant_sql = "AND index_year = '".$parcoursidentifiant_year."'
                                    AND index_origine_gaia_id = '".$gaia_origine."'
                                    AND index_title = '".$parcoursidentifiant_name."'";
    }


    $array_filter = [];

    if($select_off == 1)$array_filter[] = "( publish = 0 AND isalocalsession = 0 )";
    if($select_offlocales == 1)$array_filter[] = "( publish = 0 AND isalocalsession = 1 )";
    if($select_ofp == 1)$array_filter[] = "publish = 1";
    if($select_no_pub == 1)$array_filter[] = "publish IS NULL";

    $parcoursfilter = " AND ( ".join(" OR ",$array_filter)." ) ";



    //Si aucun filtre n'est sélectionné, on affiche rien
    if(!$select_no_pub){
        if($select_off == 0 && $select_ofp == 0 && $select_offlocales == 0){
            $parcoursfilter = " AND publish = -1 ";
        }
    }else{
        if($select_off == 0 && $select_ofp == 0 && $select_no_pub == 0 && $select_offlocales == 0){
            $parcoursfilter = " AND publish = -1 ";
        }
    }


    $lastconn_where = " AND timeaccess BETWEEN ".$lastconnmin." AND ".$lastconnmax;
	$DBC = get_centralized_db_connection();
	$years = get_years($lastconnmin, $lastconnmax);
	$union = array();
	$dbman = $DBC->get_manager();

	foreach($years as $year){
		if(!$dbman->table_exists('metaadmin_sp_'.$year)){
			continue;
		}

		// => CLEO - 2.1.0 - #2419 - 02/05/2018
		if ($view_id) {
			$customview = get_custom_views_by_id($view_id);
			$customview_where = array();
			foreach ($customview->scourses as $key => $val) {
				$iduniq = explode('*+%',$val);
				$customview_where[] = "(index_year = '".$iduniq[0]."' AND index_origine_gaia_id = '".$iduniq[1]."' AND index_title = '".str_replace("'","\\'",$iduniq[2])."')";
			}
			$union[] = "SELECT IF(public = '' AND degre = '', 'empty', IF(public IS NULL AND degre IS NULL, 'null', CONCAT(public,degre))) AS id, username, degre, public, temps_distant, temps_local
						FROM metaadmin_sp_".$year."
						WHERE academy_user = '".$academie."'".$parcoursfilter." AND role = '".$userrole."' ".$parcoursidentifiant_sql." ".$lastconn_where." AND (".implode(' OR ',$customview_where).")";
		} else {
			$union[] = "SELECT IF(public = '' AND degre = '', 'empty', IF(public IS NULL AND degre IS NULL, 'null', CONCAT(public,degre))) AS id, username, degre, public
						FROM metaadmin_sp_".$year."
						WHERE academy_user = '".$academie."' AND role = '".$userrole."' ".$parcoursidentifiant_sql." ".$lastconn_where.$parcoursfilter;

		}
		// <= CLEO - 2.1.0 - #2419 - 02/05/2018
	}

	if ($view_id) {
		$sql = 'SELECT t1.id, COUNT(DISTINCT t1.username) nb, SUM(t1.temps_distant) AS distant, SUM(t1.temps_local) AS local, t1.temps_distant AS basedistant, t1.temps_local AS baselocal
				FROM (('.implode(') UNION (', $union).')) t1
				GROUP BY t1.public, t1.degre';

	} else {
		$sql = 'SELECT t1.id, COUNT(DISTINCT t1.username) nb
				FROM (('.implode(') UNION (', $union).')) t1
				GROUP BY t1.public, t1.degre';

	}


	$rows = $DBC->get_records_sql($sql);
	if ($view_id) {
		$basedistant = 0;
		$baselocal = 0;
		$public1D = 0;
		$public1Dh = 0;
		$public2D = 0;
		$public2Dh = 0;
		$prive1D = 0;
		$prive1Dh = 0;
		$prive2D = 0;
		$prive2Dh = 0;
		$other = 0;
		$otherh = 0;

		foreach($rows as $row) {
			if ($row->baselocal !== null && $row->baselocal > 0) {
				$baselocal = $row->baselocal;
			}
			if ($row->basedistant !== null && $row->basedistant > 0) {
				$basedistant = $row->basedistant;
			}
			if ($row->id == 'PU1D') {
				$public1D = $row->nb;
				$public1Dh = ($row->distant+$row->local)/60;
			} else if ($row->id == 'PR1D') {
				$prive1D = $row->nb;
				$prive1Dh = ($row->distant+$row->local)/60;
			} else if ($row->id == 'PU2D') {
				$public2D = $row->nb;
				$public2Dh = ($row->distant+$row->local)/60;
			} else if ($row->id == 'PR2D') {
				$prive2D = $row->nb;
				$prive2Dh = ($row->distant+$row->local)/60;
			} else {
				$other += $row->nb;
				$otherh += ($row->distant+$row->local)/60;
			}
		}

		$result = new StdClass();
		$result->academy = $academie;
		$result->distanthours = $basedistant/60;
		$result->localhours = $baselocal/60;
		$result->public1D = $public1D;
		$result->prive1D = $prive1D;
		$result->total1D = $public1D+$prive1D;
		$result->total1Dh = $public1Dh+$prive1Dh;
		$result->total1Dj = number_format(($result->total1Dh)/6,2)+0;
		$result->public2D = $public2D;
		$result->prive2D = $prive2D;
		$result->total2D = $public2D+$prive2D;
		$result->total2Dh = $public2Dh+$prive2Dh;
		$result->total2Dj = number_format(($result->total2Dh)/6,2)+0;
		$result->other = $other;
		$result->otherh = $otherh;
		$result->otherj = number_format(($result->otherh)/6,2)+0;
		$result->total = $result->total1D+$result->total2D+$other;
		$result->totalh = $result->total1Dh+$result->total2Dh+$otherh;
		$result->totalj = number_format(($result->totalh)/6,2)+0;
	} else {
		$public1D = 0;
		$public2D = 0;
		$prive1D = 0;
		$prive2D = 0;
		$other = 0;

		foreach($rows as $row) {
			if ($row->id == 'PU1D') {
				$public1D = $row->nb;
			} else if ($row->id == 'PR1D') {
				$prive1D = $row->nb;
			} else if ($row->id == 'PU2D') {
				$public2D = $row->nb;
			 }else if ($row->id == 'PR2D') {
				$prive2D = $row->nb;
			} else {
				$other += $row->nb;
			}
		}

		$result = new StdClass();
		$result->academy = $academie;
		$result->public1D = $public1D;
		$result->prive1D = $prive1D;
		$result->total1D = $public1D+$prive1D;
		$result->public2D = $public2D;
		$result->prive2D = $prive2D;
		$result->total2D = $public2D+$prive2D;
		$result->other = $other;
		$result->total = $result->total1D+$result->total2D+$other;
	
	}
	
    if ($result->total > 0) {
        mmcached_set($mkey, $result,3600);
        return $result;
    }
    return false;
}

function get_aca_courses_stats($academie, $parcoursidentifiant_year, $gaia_origine, $parcoursidentifiant_name, $userrole, $lastconnmin, $lastconnmax, $viewid,$select_off,$select_offlocales,$select_ofp) {
	GLOBAL $enable_mmcached;

	$courses ="";
	if($viewid){
        $view = get_custom_views_by_id($viewid);
        foreach($view->scourses AS $course) {
            $courses += $course;
        }
    }


	$mkey = 'metaadmin_aca_courses_stats_'.hash('sha256', $academie.$userrole.$parcoursidentifiant_year.$gaia_origine.$parcoursidentifiant_name.$lastconnmin.$lastconnmax.$viewid.$select_off.$select_ofp.$courses.$select_offlocales);

	if ($enable_mmcached) {
		$cache = mmcached_get($mkey);
		if ( $cache !== false )	{
			return $cache;
		}
	}
	
	$view = get_custom_views_by_id($viewid);
	$coursestats = array();
	foreach($view->scourses AS $course) {
		$uids=explode('*+%',$course);
		$coursestat = get_course_stats($uids[0], $uids[1], $uids[2], $userrole, $lastconnmin, $lastconnmax, $academie,$select_off,$select_offlocales,$select_ofp);
		if ($coursestat !== false) {
			$coursestats[] = $coursestat;
		}
	}

	mmcached_set($mkey, $coursestats,3600);
	return (count($coursestats)<1?false:$coursestats);
}

function get_view_courses_stats($parcoursidentifiant_year, $gaia_origine, $parcoursidentifiant_name, $userrole, $lastconnmin, $lastconnmax, $viewid,$select_off,$select_offlocales,$select_ofp){
	GLOBAL $enable_mmcached;

    $courses ="";
    if($viewid){
        $view = get_custom_views_by_id($viewid);
        foreach($view->scourses AS $course) {
            $courses += $course;
        }
    }

	$mkey = 'metaadmin_aca_courses_stats_'.hash('sha256', $userrole.$parcoursidentifiant_year.$gaia_origine.$parcoursidentifiant_name.$lastconnmin.$lastconnmax.$viewid.$select_off.$select_ofp.$courses.$select_offlocales);

	if ($enable_mmcached) {
		$cache = mmcached_get($mkey);
		if ( $cache !== false )	{
			return $cache;
		}
	}
	
	$view = get_custom_views_by_id($viewid);
	$coursestats = array();
	foreach($view->scourses AS $course) {
		$uids=explode('*+%',$course);
		$coursestat = get_course_stats($uids[0], $uids[1], $uids[2], $userrole, $lastconnmin, $lastconnmax,false,$select_off,$select_offlocales,$select_ofp);
		if ($coursestat !== false) {
			$coursestats[] = $coursestat;
		}
	}
	
	mmcached_set($mkey, $coursestats,3600);
	return $coursestats;
}


/**
 * @param $academie
 * @param $userrole
 * @param $parcoursidentifiant_year
 * @param $gaia_origine
 * @param $parcoursidentifiant_name
 * @param $lastconnmin
 * @param $lastconnmax
 * @return bool|StdClass
 */
function get_course_stats($parcoursidentifiant_year, $gaia_origine, $parcoursidentifiant_name, $userrole, $lastconnmin, $lastconnmax, $academie = false,$select_off,$select_offlocales,$select_ofp) {
    global $CFG, $DB, $enable_mmcached;

    $mkey = 'metaadmin_course_stats_'.hash('sha256', $academie.$userrole.$parcoursidentifiant_year.$gaia_origine.$parcoursidentifiant_name.$lastconnmin.$lastconnmax.$select_off.$select_ofp.$select_offlocales);
	if ($enable_mmcached) {
		$cache = mmcached_get($mkey);
		if ( $cache !== false )	{
			return $cache;
		}
	}

    $parcoursidentifiant_sql = '';
    if (strlen($parcoursidentifiant_year) == 2 && $gaia_origine > 0 && strlen($parcoursidentifiant_name) > 1) {
        $parcoursidentifiant_sql = "AND index_year = '".$parcoursidentifiant_year."'
                                    AND index_origine_gaia_id = '".$gaia_origine."'
                                    AND index_title = '".str_replace("'",'\\\'',$parcoursidentifiant_name)."'";
    }

    $lastconn_where = " AND timeaccess BETWEEN ".$lastconnmin." AND ".$lastconnmax;
	$acafilter = "";
	if ($academie !== false) {
		$acafilter = " AND academy_user = '".$academie."'";
	}

    $array_filter = [];
    if($select_off == 1)$array_filter[] = "( publish = 0 AND isalocalsession = 0 )";
    if($select_offlocales == 1)$array_filter[] = "( publish = 0 AND isalocalsession = 1 )";
    if($select_ofp == 1)$array_filter[] = "publish = 1";
    $parcoursfilter = " AND ( ".join(" OR ",$array_filter)." ) ";

    if($select_off == 0 && $select_ofp == 0){
        $parcoursfilter = " AND publish = -1 ";
    }

	$DBC = get_centralized_db_connection();
	$years = get_years($lastconnmin, $lastconnmax);
	$union = array();
	$dbman = $DBC->get_manager();

	foreach($years as $year) {
		if(!$dbman->table_exists('metaadmin_sp_'.$year)) {
			continue;
		}

		$union[] = "SELECT IF(public = '' AND degre = '', 'empty', IF(public IS NULL AND degre IS NULL, 'null', CONCAT(public,degre))) AS id, username, degre, public, index_year, index_origine_gaia_id, index_title, temps_distant, temps_local
					FROM metaadmin_sp_".$year."
					WHERE role = '".$userrole."' ".$parcoursidentifiant_sql." ".$lastconn_where." ".$acafilter.$parcoursfilter;
	}

	$sql = 'SELECT t1.id, COUNT(DISTINCT t1.username) nb, SUM(t1.temps_distant) AS distant, SUM(t1.temps_local) AS local, t1.temps_distant AS basedistant, t1.temps_local AS baselocal
			FROM (('.implode(') UNION (', $union).')) t1
			GROUP BY t1.index_year, t1.index_origine_gaia_id, t1.index_title, t1.public, t1.degre';
	
	$rows = $DBC->get_records_sql($sql);

	if (count($rows) == 0) {
		mmcached_set($mkey, false,3600);
		return false;
	}
	
	$baselocal = 0;
	$basedistant = 0;
	$public1D = 0;
	$public1Dh = 0;
	$public2D = 0;
	$public2Dh = 0;
	$prive1D = 0;
	$prive1Dh = 0;
	$prive2D = 0;
	$prive2Dh = 0;
	$other = 0;
	$otherh = 0;

	foreach($rows as $row) {
		if ($row->baselocal !== null && $row->baselocal > 0) {
			$baselocal = $row->baselocal;
		}
		if ($row->basedistant !== null && $row->basedistant > 0) {
			$basedistant = $row->basedistant;
		}
		if ($row->id == 'PU1D') {
			$public1D = $row->nb;
			$public1Dh = ($row->distant+$row->local)/60;
		} else if ($row->id == 'PR1D') {
			$prive1D = $row->nb;
			$prive1Dh = ($row->distant+$row->local)/60;
		} else if ($row->id == 'PU2D') {
			$public2D = $row->nb;
			$public2Dh = ($row->distant+$row->local)/60;
		} else if ($row->id == 'PR2D') {
			$prive2D = $row->nb;
			$prive2Dh = ($row->distant+$row->local)/60;
		} else {
			$other += $row->nb;
			$otherh += ($row->distant+$row->local)/60;
		}
	}

	$result = new StdClass();
	$result->courseuid = $parcoursidentifiant_year.'_'.$gaia_origine.'_'.$parcoursidentifiant_name;
	$result->academy = $academie;
	$result->distanthours = $basedistant/60;
	$result->localhours = $baselocal/60;
	$result->public1D = $public1D;
	$result->prive1D = $prive1D;
	$result->total1D = $public1D+$prive1D;
	$result->total1Dh = $public1Dh+$prive1Dh;
	$result->total1Dj = number_format(($result->total1Dh)/6,2)+0;
	$result->public2D = $public2D;
	$result->prive2D = $prive2D;
	$result->total2D = $public2D+$prive2D;
	$result->total2Dh = $public2Dh+$prive2Dh;
	$result->total2Dj = number_format(($result->total2Dh)/6,2)+0;
	$result->other = $other;
	$result->otherh = $otherh;
	$result->otherj = number_format(($result->otherh)/6,2)+0;
	$result->total = $result->total1D+$result->total2D+$other;
	$result->totalh = $result->total1Dh+$result->total2Dh+$otherh;
	$result->totalj = number_format(($result->totalh)/6,2)+0;

    if ($result->total > 0) {
        mmcached_set($mkey, $result,3600);
        return $result;
    }
    return false;
}



/**
 * @return bool
 */
function update_tmp_table()
{
    global $CFG, $DB;

    error_reporting(E_ALL | E_STRICT);
    ini_set('display_errors', 1);

    $CFG->debug = E_ALL | E_STRICT; // 32767;
    $CFG->debugdisplay = true;
    /*
    $CFG->dboptions = array(
        'dbpersist' => false,
        'dbsocket'  => false,
        'dbport'    => '',
        'logall'=>true,
        'logslow'=>true,
        'logerrors'=>true
    );
    */
    require_once($CFG->dirroot.'/local/magisterelib/databaseConnection.php');

    $DB->execute("TRUNCATE ".$CFG->centralized_dbname.".metaadmin_statsparticipants");
    $academies = get_magistere_academy_config();
    $special_aca = array('reseau-canope','dgesco','efe','ih2ef','dne-foad');
    $roles_shortname = array('participant','formateur','tuteur');

    foreach($academies as $academy=>$daca) {
        if (substr($academy,0,3) != 'ac-' || $academy != 'ac-versailles') {
            continue;
        }
        foreach ($academies as $academy_name=>$aca_data) {
            if (substr($academy_name,0,3) != 'ac-' && !in_array($academy_name,$special_aca)) {
                continue;
            }

            echo str_pad('PROCESSING '.$academy.' => '.$academy_name,60);
            $start = microtime(true);

            unset($acaDB);
            if (($acaDB = databaseConnection::instance()->get($academy_name)) === false){error_log('local/metaadmin/lib.php/update_tmp_table()/'.$academy_name.'/Database_connection_failed'); continue;}

            $sql = "INSERT IGNORE INTO ".$CFG->centralized_dbname.".metaadmin_statsparticipants(academy_user,academy_enrol,username,timeaccess,rne,role,degre,public,index_year,index_origine_gaia_id,index_title, courseid) 
					SELECT '".$academy."', '".$academy_name."', u.username, ul.timeaccess, SUBSTRING(uid2.data,1,10), r.shortname, tuai.degre, tuai.public, im.year, im.codeorigineid, im.title, c.id courseid
					FROM {user} u
					INNER JOIN {user_info_data} uid ON (
					   uid.userid = u.id
					   AND uid.fieldid = (SELECT id FROM {user_info_field} WHERE shortname = 'codaca')
					   AND uid.data = (SELECT id FROM {t_academie} WHERE short_uri = '".$academy."')
					   )
					LEFT JOIN {user_info_data} uid2 ON (
					   uid2.userid = u.id
					   AND uid2.fieldid = (SELECT id FROM {user_info_field} WHERE shortname = 'rne')
					   )
					LEFT JOIN {t_uai} tuai ON (tuai.code_rne = uid2.data)

					INNER JOIN {user_enrolments} ue ON (ue.userid = u.id)
					INNER JOIN {enrol} e ON (e.id = ue.enrolid)
					INNER JOIN {course} c ON (c.id = e.courseid)
					LEFT JOIN {user_lastaccess} ul ON (ul.userid = u.id AND ul.courseid = c.id)
					LEFT JOIN {local_indexation} im ON (im.courseid = c.id)

					INNER JOIN {context} cx ON (contextlevel=50 AND c.id = cx.instanceid)
					INNER JOIN {role_assignments} ra ON (cx.id = ra.contextid AND ra.userid = u.id)
					INNER JOIN {role} r ON (r.id = ra.roleid)

					WHERE
					u.auth = 'shibboleth'
					AND u.deleted = 0
					AND u.suspended = 0

					AND cx.path NOT LIKE(SELECT CONCAT('%/',id,'/%') FROM {context} WHERE contextlevel = 40 AND instanceid = (SELECT id FROM {course_categories} WHERE name ='Corbeille'))

					AND r.shortname IN ('".implode("','",$roles_shortname)."')";

            try {
                $result  = $acaDB->execute($sql);
            }
            catch (moodle_exception $e) {
                echo "Query FAILED : ".$e->getMessage()."\n";
                echo '####'.$academy.' => '.$academy_name.' :: '.$DB->get_last_error().'####';
                error_log($CFG->academie_name.'##'.__FILE__.'##'.$e->getMessage());
            }
            $end = microtime(true);
            echo number_format($end - $start,6)."s\n";
        }
    }

    echo "START RNE Correction\n";

    $sql_correct_rne = "UPDATE ".$CFG->centralized_dbname.".metaadmin_statsparticipants, ".$CFG->db_prefix."frontal.mdl_user, ".$CFG->db_prefix."frontal.mdl_user_info_data, ".$CFG->db_prefix."frontal.mdl_t_uai
						SET ".$CFG->centralized_dbname.".metaadmin_statsparticipants.rne = ".$CFG->db_prefix."frontal.mdl_t_uai.code_rne,
						".$CFG->centralized_dbname.".metaadmin_statsparticipants.public = ".$CFG->db_prefix."frontal.mdl_t_uai.public,
						".$CFG->centralized_dbname.".metaadmin_statsparticipants.degre = ".$CFG->db_prefix."frontal.mdl_t_uai.degre,
						".$CFG->centralized_dbname.".metaadmin_statsparticipants.academy_user = (
								SELECT short_uri 
								FROM ".$CFG->db_prefix."dgesco.mdl_t_academie taca
								WHERE taca.id = (SELECT academie FROM ".$CFG->centralized_dbname.".mdl_t_uai WHERE code_rne=".$CFG->db_prefix."frontal.mdl_t_uai.code_rne)
						)
						WHERE ".$CFG->db_prefix."frontal.mdl_user_info_data.userid = ".$CFG->db_prefix."frontal.mdl_user.id
						AND ".$CFG->db_prefix."frontal.mdl_user_info_data.fieldid = (SELECT id FROM ".$CFG->db_prefix."frontal.mdl_user_info_field WHERE shortname = 'rne')
						AND ".$CFG->db_prefix."frontal.mdl_t_uai.code_rne = ".$CFG->db_prefix."frontal.mdl_user_info_data.data
						AND ".$CFG->centralized_dbname.".metaadmin_statsparticipants.username = ".$CFG->db_prefix."frontal.mdl_user.username
						AND ".$CFG->db_prefix."frontal.mdl_t_uai.academie IN (SELECT id FROM ".$CFG->db_prefix."dgesco.mdl_t_academie)";
    try {
        $DB->execute($sql_correct_rne);
        echo "End RNE correction\n";
    }
    catch (moodle_exception $e) {
        echo "RNE correction FAILED : ".$e->getMessage()."\n";
        echo '####'.$DB->get_last_error().'####';
        error_log($CFG->academie_name.'##'.__FILE__.'##'.$e->getMessage());
        return false;
        //throw $e;
    }
    echo "END METAADMIN CRON\n";
}

/**
 * @param $userrole
 * @param $parcoursidentifiant_year
 * @param $gaia_origine
 * @param $parcoursidentifiant_name
 * @param $lastconnmin
 * @param $lastconnmax
 * @return array|bool
 */
function get_aca_stats_per_academy($userrole, $parcoursidentifiant_year, $gaia_origine, $parcoursidentifiant_name, $lastconnmin, $lastconnmax,$select_no_pub,$select_off,$select_offlocales,$select_ofp) {
    global $CFG, $DB, $enable_mmcached;

    $mkey = 'metaadmin_stats_per_academy_search_'.hash('sha256', $userrole.$parcoursidentifiant_year.$gaia_origine.$parcoursidentifiant_name.$lastconnmin.$lastconnmax.$select_no_pub.$select_off.$select_ofp.$select_offlocales);
	if ($enable_mmcached) {
		$cache = mmcached_get($mkey);
		if ( $cache !== false ) {
			return $cache;
		}
    }
    $parcoursidentifiant_sql = '';
    if ( strlen($parcoursidentifiant_year) == 2 && $gaia_origine > 0 && strlen($parcoursidentifiant_name) > 1) {
        $parcoursidentifiant_sql = "AND index_year = '".$parcoursidentifiant_year."'
									AND index_origine_gaia_id = '".$gaia_origine."'
									AND index_title = '".$parcoursidentifiant_name."'";
    }

    if($select_off == 1)$array_filter[] = "( publish = 0 AND isalocalsession = 0 )";
    if($select_offlocales == 1)$array_filter[] = "";

    $parcoursfilter = "";
    if($select_off == 0 || $select_ofp == 0 || $select_no_pub == 0 || $select_offlocales == 0){
        $parcoursfilter .= " AND (";

        $isfirst = true;
        if($select_off == 1){
            $parcoursfilter .= " ( publish = 0 AND isalocalsession = 0 ) ";
            $isfirst = false;
        }
        if($select_offlocales == 1){
            $parcoursfilter .= " ( publish = 0 AND isalocalsession = 1 ) ";
            $isfirst = false;
        }
        if($select_ofp == 1){
            if(!$isfirst) $parcoursfilter .= " OR";
            $parcoursfilter .= " publish = 1 ";
            $isfirst = false;
        }
        if($select_no_pub == 1){
            if(!$isfirst) $parcoursfilter .= " OR";
            $parcoursfilter .= " publish IS NULL ";
        }
        $parcoursfilter .= ")";
    }
    if($select_off == 0 && $select_ofp == 0 && $select_no_pub == 0 && $select_offlocales == 0){
        $parcoursfilter = " AND publish = -1 ";
    }

    $lastconn_where = ' AND timeaccess BETWEEN ' . $lastconnmin . ' AND ' . $lastconnmax;
    $DBC = get_centralized_db_connection();
    $years = get_years($lastconnmin, $lastconnmax);

    $union = array();
    $dbman = $DBC->get_manager();
    foreach($years as $year){
        if(!$dbman->table_exists('metaadmin_sp_'.$year)){
            continue;
        }
        $union[] = "SELECT id, academy_user, academy_enrol, username
					FROM metaadmin_sp_".$year."
					WHERE role='".$userrole."'
					".$parcoursidentifiant_sql."
					".$lastconn_where.$parcoursfilter;
    }
    $sql = 'SELECT t1.id, t1.academy_user, t1.academy_enrol, COUNT(DISTINCT t1.username) nb
			FROM (('.implode(') UNION (', $union).')) t1
			GROUP BY t1.academy_user, t1.academy_enrol';


    $rows = $DBC->get_records_sql($sql);
    $result = array();

    foreach($rows as $row){
        $data = new stdClass();
        if(!isset($result[$row->academy_user])){
            $result[$row->academy_user] = new stdClass();
            $result[$row->academy_user]->name = $CFG->academylist[$row->academy_user]["name"];
        }

        $result[$row->academy_user]->{$row->academy_enrol} = $row->nb;
    }
    if (count($result) > 0) {
        mmcached_set($mkey, $result,3600);
        return $result;
    }
    return false;
}

/**
 * @param $academie
 * @param $userrole
 * @param $parcoursidentifiant_year
 * @param $gaia_origine
 * @param $parcoursidentifiant_name
 * @param $lastconnmin
 * @param $lastconnmax
 * @return array|bool
 */
function get_aca_first_degree_stats($academie, $userrole, $parcoursidentifiant_year, $gaia_origine, $parcoursidentifiant_name, $lastconnmin, $lastconnmax,$select_no_pub,$select_off,$select_offlocales,$select_ofp){
    global $CFG, $DB;
    $mkey = 'metaadmin_stats_first_degree_search_'.hash('sha256', $academie.$userrole.$parcoursidentifiant_year.$gaia_origine.$parcoursidentifiant_name.$lastconnmin.$lastconnmax.$select_no_pub.$select_off.$select_ofp.$select_offlocales);

    $parcoursidentifiant_sql = '';
    if (strlen($parcoursidentifiant_year) == 2 && $gaia_origine > 0 && strlen($parcoursidentifiant_name) > 1) {
        $parcoursidentifiant_sql = "AND index_year = '".$parcoursidentifiant_year."'
    AND index_origine_gaia_id = '".$gaia_origine."'
    AND index_title = '".$parcoursidentifiant_name."'";
    }

    $parcoursfilter = "";
    if($select_off == 0 || $select_ofp == 0 || $select_no_pub == 0 || $select_offlocales == 0){
        $parcoursfilter .= " AND (";

        $isfirst = true;
        if($select_off == 1){
            $parcoursfilter .= " ( publish = 0 AND isalocalsession = 0 ) ";
            $isfirst = false;
        }
        if($select_offlocales == 1){
            $parcoursfilter .= " ( publish = 0 AND isalocalsession = 1 ) ";
            $isfirst = false;
        }
        if($select_ofp == 1){
            if(!$isfirst) $parcoursfilter .= " OR";
            $parcoursfilter .= " publish = 1 ";
            $isfirst = false;
        }
        if($select_no_pub == 1){
            if(!$isfirst) $parcoursfilter .= " OR";
            $parcoursfilter .= " publish IS NULL ";
        }
        $parcoursfilter .= ")";
    }
    if($select_off == 0 && $select_ofp == 0 && $select_no_pub == 0 && $select_offlocales == 0){
        $parcoursfilter = " AND publish = -1 ";
    }

    $lastconn_where = " AND timeaccess BETWEEN ".$lastconnmin." AND ".$lastconnmax;

    $academy_where = '';
    if($academie != 'dgesco'){
        $academy_where = " AND academy_user='".$academie."' ";
    }

    $DBC = get_centralized_db_connection();
    $years = get_years($lastconnmin, $lastconnmax);

    $union = array();
    $dbman = $DBC->get_manager();
    foreach($years as $year){
        if(!$dbman->table_exists("metaadmin_sp_".$year)){
            continue;
        }

        $union[] = "SELECT meta.academy_user academy, meta.public public, meta.username, (SELECT departement FROM mdl_t_uai uai WHERE uai.code_rne = meta.rne) code_dpt
FROM metaadmin_sp_".$year." meta
					WHERE  meta.degre='1D' 
					AND LENGTH(meta.rne) = 8 
					".$academy_where." 
					AND role='".$userrole."'
					".$parcoursidentifiant_sql."
					".$lastconn_where.$parcoursfilter;
	}

    $sql = 'SELECT CONCAT(t1.academy,(SELECT libelle_long FROM mdl_t_departement WHERE code =  t1.code_dpt), t1.public) id, t1.academy, (SELECT libelle_long FROM mdl_t_departement WHERE code = t1.code_dpt) dpt, t1.public, COUNT(DISTINCT t1.username) nb, t1.code_dpt 
			FROM (('.implode(') UNION (', $union).')) t1
			GROUP BY t1.academy, t1.code_dpt, t1.public
			HAVING t1.code_dpt IS NOT NULL AND LENGTH(t1.code_dpt) > 1
			ORDER BY t1.academy, t1.code_dpt';


    $rows = $DBC->get_records_sql($sql);
    $result = array();
    $prevaca = '';
    foreach($rows as $row){
        if(!isset($result[$row->academy])){
            $result[$row->academy] = array();
        }

        if(!isset($result[$row->academy][$row->code_dpt])){
            $result[$row->academy][$row->code_dpt] = array();
            $result[$row->academy][$row->code_dpt]['name'] = $row->dpt;
            $result[$row->academy][$row->code_dpt]['total'] = 0;
        }

        if($row->public == 'PR'){
            $result[$row->academy][$row->code_dpt]['1d_private'] = $row->nb;
        }else{
            $result[$row->academy][$row->code_dpt]['1d_public'] = $row->nb;
        }

        $result[$row->academy][$row->code_dpt]['total'] += $row->nb;
    }

    $flatresult = array();
    foreach($result as $aca => $dpt){
        foreach($dpt as $dpt_code => $data){
            $row = new stdClass();

            $row->academy = $CFG->academylist[$aca]["name"];
            $row->dpt_code = $dpt_code;
            $row->dpt_name = $data['name'];
            $row->total = $data['total'];
            $row->public_1d = isset($data['1d_public']) ? $data['1d_public'] : '0';
            $row->private_1d = isset($data['1d_private']) ? $data['1d_private'] : '0';

            $flatresult[] = $row;
        }
    }

    if (count($flatresult) > 0) {
        mmcached_set($mkey, $flatresult,3600);
        return $flatresult;
    }
    return false;
}

/**
 * @param $lastconmin
 * @param $lastconmax
 * @return array
 */
function get_years($lastconmin, $lastconmax) {
    if($lastconmin > $lastconmax){
        $tmp = $lastconmin;
        $lastconmin = $lastconmax;
        $lastconmax = $tmp;
    }

    $lastconmin_d = getdate($lastconmin);
    $lastconmax_d = getdate($lastconmax);

    $years = array();
    $start_y = 0;
    $end_y = 0;
    if($lastconmin_d['mon'] >= 9){
        $start_y = $lastconmin_d['year'];
    }else{
        $start_y = $lastconmin_d['year'] - 1;
    }

    if($lastconmax_d['mon'] >= 9){
        $end_y = $lastconmax_d['year'];
    }else{
        $end_y = $lastconmax_d['year'] - 1;
    }

    for($i = $start_y; $i <= $end_y; $i++){
        $years[] = $i;
    }

    return $years;
}

/**
 * @return array
 */
function get_current_year_info()
{
    $current = time();
    $year = get_years($current, $current);

    $start = mktime(0, 0, 0, 9, 1, $year[0]);
    $end = mktime(23, 59, 59, 8, 31, $year[0]+1);

    return array(
        'start' => $start,
        'end' => $end,
        'year' => $year[0]
    );
}

/**
 * @param $now
 */
function update_metaadmin_table($now)
{
    global $CFG, $DB;

    // Developer settings - not for production or staging!
    error_reporting(E_ALL | E_STRICT);
    ini_set('display_errors', 1);

    $CFG->debug = E_ALL | E_STRICT; // 32767;
    $CFG->debugdisplay = true;

    require_once($CFG->dirroot.'/local/magisterelib/databaseConnection.php');

    $messageText2 = $messageText = 'Erreur(s) SQL détectée(s) à l\'exécution de la tâche "metaadmin_update_statsparticipants_table_task.php" :';
    $messageHTML2 = $messageHTML = '<p>Erreur(s) SQL détecté(s) à l\'exécution de la tâche "metaadmin_update_statsparticipants_table_task.php" :<br />';
    $toSend = false;
    $toSend2 = false;

    $academies = get_magistere_academy_config();

    $special_aca = array('reseau-canope','dgesco','efe','ih2ef','dne-foad');
    $roles_shortname = array('participant','formateur','tuteur');

    $yesterday = strtotime('-1 day', $now);

    if($yesterday !== false){
        $yesterday = date($yesterday);
    }

    // on calcul les donnees de la veille
    $yesterday_m = date('n', $yesterday);
    $yesterday_d = date('j', $yesterday);
    $yesterday_y = date('Y', $yesterday);

    $lastconnmin = mktime(0, 0, 0, $yesterday_m, $yesterday_d, $yesterday_y);
    $lastconnmax = mktime(23, 59, 59, $yesterday_m, $yesterday_d, $yesterday_y);

    $tablebasename = "metaadmin_sp_";
    $year = $yesterday_m < 9 ? ($yesterday_y - 1) : $yesterday_y;

    $table_name = $tablebasename.$year;
    $DBC = get_centralized_db_connection();
    $dbman = $DBC->get_manager();

    if (!$dbman->table_exists($table_name)) {
        create_metaadmin_table($tablebasename,$year);
    }

    echo '$lastconnmin: '.date('d/m/Y G:i', $lastconnmin)."\n";
    echo '$lastconnmax: '.date('d/m/Y G:i', $lastconnmax)."\n";
    echo '$table_name: '.$table_name."\n";

    foreach($academies as $academy=>$daca)
    {
        if (substr($academy,0,3) != 'ac-')
        {
            continue;
        }
        foreach ($academies as $academy_name=>$aca_data)
        {
            if (substr($academy_name,0,3) != 'ac-' && !in_array($academy_name,$special_aca))
            {
                continue;
            }

            echo str_pad('PROCESSING '.$academy.' => '.$academy_name,60);
            $start = microtime(true);

            unset($acaDB);
            if (($acaDB = databaseConnection::instance()->get($academy_name)) === false){error_log('local/metaadmin/lib.php/update_tmp_table()/'.$academy_name.'/Database_connection_failed'); continue;}

            $hub = CourseHub::instance();

            if($hub->isMaster()){
                $master = $CFG->academie_name;
            }else{
                $master = $hub->getMaster();
            }

            $sql_select = "SELECT    '".$academy."', '".$academy_name."', u.username, ul.timeaccess, SUBSTRING(uid2.data,1,10) AS rne, r.shortname, tuai.degre, tuai.public, im.year, im.codeorigineid, im.title, IF(im.tps_a_distance IS NULL,0,im.tps_a_distance) AS tps_a_distance, IF(im.tps_en_presence IS NULL,0,im.tps_en_presence) AS tps_en_presence, c.id courseid, ch.publish AS publish, ch.isalocalsession AS isalocalsession
            FROM {user} u
            INNER JOIN {user_info_data} uid ON (
               uid.userid = u.id
               AND uid.fieldid = (SELECT id FROM {user_info_field} WHERE shortname = 'codaca')
               AND uid.data = (SELECT id FROM {t_academie} WHERE short_uri = '".$academy."')
               )
            LEFT JOIN {user_info_data} uid2 ON (
               uid2.userid = u.id
               AND uid2.fieldid = (SELECT id FROM {user_info_field} WHERE shortname = 'rne')
               )
            LEFT JOIN {t_uai} tuai ON (tuai.code_rne = uid2.data)
            
            INNER JOIN {user_enrolments} ue ON (ue.userid = u.id)
            INNER JOIN {enrol} e ON (e.id = ue.enrolid)
            INNER JOIN {course} c ON (c.id = e.courseid)
            LEFT JOIN {user_dayaccess} ul ON (ul.userid = u.id AND ul.courseid = c.id)
            LEFT JOIN {local_indexation} im ON (im.courseid = c.id)
            LEFT JOIN ".$CFG->db_prefix.$master.".{local_coursehub_slave} cs ON (cs.identifiant = '".$academy_name."' )
            LEFT JOIN ".$CFG->db_prefix.$master.".{local_coursehub_course} ch ON (ch.slaveid = cs.id AND ch.courseid = c.id AND ch.deleted = 0)
          
            
            INNER JOIN {context} cx ON (contextlevel=50 AND c.id = cx.instanceid)
            INNER JOIN {role_assignments} ra ON (cx.id = ra.contextid AND ra.userid = u.id)
            INNER JOIN {role} r ON (r.id = ra.roleid)
            
            WHERE
            u.auth = 'shibboleth'
            AND u.deleted = 0
            AND u.suspended = 0
            
            AND cx.path NOT LIKE(SELECT CONCAT('%/',id,'/%') FROM {context} WHERE contextlevel = 40 AND instanceid = (SELECT id FROM {course_categories} WHERE name ='Corbeille'))

            AND r.shortname IN ('".implode("','",$roles_shortname)."')
            AND ul.timeaccess >= ".$lastconnmin." AND ul.timeaccess <= ".$lastconnmax;
            
            
            $sql_sinsert = "INSERT INTO ".$CFG->centralized_dbname.".".$table_name."(academy_user,academy_enrol,username,timeaccess,rne,role,degre,public,index_year,index_origine_gaia_id,index_title, temps_distant, temps_local, courseid,publish,isalocalsession) 
            ".$sql_select.'
            ON DUPLICATE KEY UPDATE '.$CFG->centralized_dbname.'.'.$table_name.'.id='.$CFG->centralized_dbname.'.'.$table_name.'.id';

            $result = false;
            $res = false;
            try
            {
                $result  = $acaDB->execute($sql_sinsert);
            }
            catch (moodle_exception $e) {
                echo "Query FAILED : ".$e->getMessage()."\n";
                echo '####'.$academy.' => '.$academy_name.' :: '.$acaDB->get_last_error().'####'."\nSQL QUERY :\n####################\n".$sql_sinsert."\n####################\n";
                error_log($CFG->academie_name.'##'.__FILE__.'##'.$e->getMessage());
                
                $toSend2 = true;
                
                $sql_select = str_replace('SELECT    ', 'SELECT @rowid:=@rowid+1 as rowid,', $sql_select);
                $sql_select = str_replace('INNER JOIN {role} r ON (r.id = ra.roleid)', 'INNER JOIN {role} r ON (r.id = ra.roleid), (SELECT @rowid:=0) as init ', $sql_select);
                
                $records = $acaDB->get_records_sql($sql_select);
                
                if ($records !== false)
                {
                    $result = true;
                    foreach($records AS $record)
                    {
                        
                        $sql_insertb = "INSERT INTO ".$CFG->centralized_dbname.".".$table_name."(academy_user,academy_enrol,username,timeaccess,rne,role,degre,public,index_year,index_origine_gaia_id,index_title, temps_distant, temps_local, courseid,publish,isalocalsession)
VALUES(:academy_user,:academy_enrol,:username,:timeaccess,:rne,:shortname,:degre,:public,:year,:codeorigineid,:title,:tps_a_distance,:tps_en_presence,:courseid,:publish,:isalocalsession)
ON DUPLICATE KEY UPDATE ".$CFG->centralized_dbname.'.'.$table_name.'.id='.$CFG->centralized_dbname.'.'.$table_name.'.id';
                        
                        $params = array(
                            'academy_user'   => $academy,
                            'academy_enrol'  => $academy_name,
                            'username'       => $record->username,
                            'timeaccess'     => $record->timeaccess,
                            'rne'            => $record->rne,
                            'shortname'      => $record->shortname,
                            'degre'          => $record->degre,
                            'public'         => $record->public,
                            'year'           => $record->year,
                            'codeorigineid'  => $record->codeorigineid,
                            'title'          => $record->title,
                            'tps_a_distance' => $record->tps_a_distance,
                            'tps_en_presence'=> $record->tps_en_presence,
                            'courseid'       => $record->courseid,
                            'publish'       => $record->publish,
                            'isalocalsession'       => $record->isalocalsession
                        );
                        
                        try
                        {
                            $res = get_centralized_db_connection()->execute($sql_insertb,$params);
                        }
                        catch (moodle_exception $e) {
                            if (!$res)
                            {
                                $toSend2 = true;
                            }
                            echo "QueryB FAILED : ".$e->getMessage()."\n";
                            echo '####'.$academy.' => '.$academy_name.' :: '.$acaDB->get_last_error().'####'."\nSQL QUERY :\n####################\n".$sql_insertb."\n####################\n".print_r($params,true)."\n####################\n";
                            error_log($CFG->academie_name.'##'.__FILE__.'##'.$e->getMessage());
                        }
                    }
                }
            }

            if (!$result) {
                $toSend = true;
                $messageText .= ' Erreur lors de la generation des stats : '.$academy.' => '.$academy_name."\n";
                $messageHTML .= '- Erreur lors de la generation des stats : '.$academy.' => '.$academy_name.'.<br />';
                $messageText2 .= ' Erreur lors de la generation des stats : '.$academy.' => '.$academy_name."\n";
                $messageHTML2 .= '- Erreur lors de la generation des stats : '.$academy.' => '.$academy_name.'.<br />';
            }
            
            if (!$res)
            {
                $messageText2 .= ' Erreur lors de la seconde generation des stats : '.$academy.' => '.$academy_name."\n";
                $messageHTML2 .= '- Erreur lors de la seconde generation des stats : '.$academy.' => '.$academy_name.'.<br />';
            }

            $end = microtime(true);
            echo number_format($end - $start,6)."s\n";
        }
    }
    
    if ($toSend) {
        $subject = "Magistere : ".str_replace('https://','',$CFG->magistere_domaine)." : Erreur SQL dans le CRON";
        $userfrom = \core_user::get_noreply_user();
        $receivers = array("quentin.gourbeault@tcs.com", "valentin.sevaille@tcs.com","jeanbaptiste.lefevre@tcs.com", "clement.larrive@reseau-canope.fr");
        foreach ($receivers as $key => $val) {
            $userto = setCustomTo($val);
            $hasBeenSent = email_to_user($userto, $userfrom, $subject, $messageText, $messageHTML.'</p>');
        }
    }else if ($toSend2) {
        $subject = "Magistere : ".str_replace('https://','',$CFG->magistere_domaine)." : Erreur SQL dans le CRON";
        $userfrom = \core_user::get_noreply_user();
        $receivers = array("valentin.sevaille@tcs.com");
        foreach ($receivers as $key => $val) {
            $userto = setCustomTo($val);
            $hasBeenSent = email_to_user($userto, $userfrom, $subject, $messageText2, $messageHTML2.'</p>');
        }
    }

    echo "END METAADMIN CRON\n";
}

/**
 * @param $dbman
 * @param $tablename
 * @param $copytable
 * @return mixed
 */
function create_metaadmin_table($tablebasename,$year)
{
    global $CFG;

    $tabletocopy = null;
    if (get_centralized_db_connection()->get_manager()->table_exists($tablebasename.($year-1))){
        $tabletocopy = $tablebasename.($year-1);
    }elseif(get_centralized_db_connection()->get_manager()->table_exists($tablebasename.($year-2))){
        $tabletocopy = $tablebasename.($year-2);
    }

    if($tabletocopy){
        $sql = 'CREATE TABLE {'.$tablebasename.$year.'} LIKE {'.$tabletocopy.'}';
        get_centralized_db_connection()->execute($sql);
    }else{
        return;
    }

    return;
}

/**
 * To create a "fake user" from a simple email address
 *
 * @param $emailaddress
 * @return stdClass
 */
function setCustomTo($emailaddress)
{
    $user = new stdClass();
    $user->id = -99;
    $user->email = $emailaddress;
    $user->deleted = 0;
    $user->suspended = 0;
    $user->auth = 'shibboleth';
    $user->lang = 'fr';
    $user->calendartype = 'gregorian';
    $user->timezone = 99;
    $user->mailformat = 1;
    $user->maildigest = 0;
    $user->maildisplay = 2;

    return $user;
}


/**
 * To get a view from the metaadmin_customview table
 *
 * @param $id
 * @return null
 */
function get_custom_views_by_id($id) {
    $DBC = get_centralized_db_connection();

    $sql = 'SELECT vc.id AS vcid, mv.*, vc.view_id, vc.index_year, vc.index_origine_gaia_id, vc.index_title
            FROM metaadmin_customview mv
            INNER JOIN metaadmin_customview_course vc ON vc.view_id = mv.id
            WHERE mv.id = '.$id;

    $res = $DBC->get_records_sql($sql);
	if (count($res) == 0 || $res === false){
		return false;
	}
	
	$view = current($res);
    $view->scourses = array();

    foreach ($res as $val) {
        if ($val->index_year && $val->index_origine_gaia_id && $val->index_title) {
            $view->scourses[] = $val->index_year.'*+%'.$val->index_origine_gaia_id.'*+%'.$val->index_title;
        }
    }
    return $view;
}

/**
 * To get a view from the metaadmin_customview table
 *
 * @param $userId
 * @return null
 */
function get_custom_views_by_user($userId,$academy_name) {
    $DBC = get_centralized_db_connection();
    $sql = 'SELECT mv.*
            FROM metaadmin_customview mv
            WHERE mv.user_id = :userid
            AND user_academy = :academy_name';
    return $DBC->get_records_sql($sql,array("userid" => $userId, "academy_name" => $academy_name));
}

/**
 * To get the available courses (in hub)
 *
 * @return mixed
 */
function get_available_courses() {
	global $DB, $CFG;

    $hub = CourseHub::instance();

    if ($hub->isNoConfig()){return array();}


    if($hub->isMaster()){
        $master = $CFG->academie_name;
    }else{
        if (empty($hub->getMaster())){return array();}
        $master = $hub->getMaster();
    }


    $sql = "SELECT 
    lcc.id              as course_hubid,
    ind.year            as ind_year,
    lic.id as   ind_origine_gaia_id,
	lic.code as ind_origine_gaia_code,
    ind.title           as ind_title,
	ind.academyid         as ind_academy,
	ind.origin as ind_origin,
	lcc.publish as publish_type
    
    FROM {local_coursehub_slave} as lcs
    LEFT JOIN {local_coursehub_course}    lcc        ON lcc.slaveid = lcs.id
    LEFT JOIN ".$CFG->db_prefix.$CFG->academie_shortname.".{local_indexation}       ind        ON ind.courseid = lcc.courseid
    LEFT JOIN ".$CFG->centralized_dbname.".local_indexation_codes lic ON lic.id=ind.codeorigineid
    LEFT JOIN {t_academie} t_aca ON (t_aca.id = ind.academyid)
    WHERE lcs.trusted = 1
    AND lcs.deleted = 0
    AND lcc.deleted = 0
    ORDER BY lcc.id ASC";


    $hub_courses = databaseConnection::instance()->get($master)->get_records_sql($sql,array());

	//echo '<pre>';print_r($hub_courses);die;
	$tabHub = array();
	$tabHub['ref'] = array();
	$tabHub['ownaca'] = array();

	$useraca = $DB->get_record('t_academie',array('short_uri'=>$CFG->academie_name));
    $acaspe = array('efe' => 'ife', 'ih2ef' => 'ih2ef');

    foreach($hub_courses as $val) {
        if (isset($val->ind_year) && isset($val->ind_origine_gaia_id) && isset($val->ind_title)) {
            $tabHub['ref'][$val->ind_year.'*+%'.$val->ind_origine_gaia_id.'*+%'.$val->ind_title] = $val->ind_year.'_'.$val->ind_origine_gaia_code.'_'.$val->ind_title;
            $tabHub['offre'][$val->ind_year.'*+%'.$val->ind_origine_gaia_id.'*+%'.$val->ind_title] = $val->publish_type;
            $acakey = $val->ind_year.'*+%'.$val->ind_origine_gaia_id.'*+%'.$val->ind_title;
            if ($val->ind_origin == 'academie' && isset($useraca->id) && $useraca->id == $val->ind_academy && !array_key_exists($acakey,$tabHub['ownaca']) 
            		|| $val->ind_origin == $CFG->academie_name || isset($acaspe[$CFG->academie_name]) && $val->ind_origin == $acaspe[$CFG->academie_name]
            	)
            {
                $tabHub['ownaca'][$acakey]['id'] = $acakey;
                $tabHub['ownaca'][$acakey]['label'] = $val->ind_year.'_'.$val->ind_origine_gaia_code.'_'.$val->ind_title;
            }
        }
    }

    asort($tabHub['ownaca']);

    return $tabHub;
}

/**
 * To create a view in metaadmin_customview
 *
 * @param $data
 * @param $dataCourses
 * @return bool|int
 */
function create_customview($data, $dataCourses) {
    $DBC = get_centralized_db_connection();
    $viewId = $DBC->insert_record('metaadmin_customview', $data, true);

    foreach ($dataCourses as $dat) {
        $dat->view_id = $viewId;
    }
    $DBC->insert_records('metaadmin_customview_course', $dataCourses);

    return $viewId;
}

/**
 * To update a view in metaadmin_customview
 *
 * @param $data
 * @param $dataCourses
 */
function update_customview($data, $dataCourses) {
    $DBC = get_centralized_db_connection();

    if (!$data->send_report) {
        $data->frequency_report = "";
        $data->day_report = 0;
        $data->emails = "";
    }
    $DBC->update_record('metaadmin_customview', $data);
    $DBC->delete_records('metaadmin_customview_course', array('view_id' => $data->id));
    foreach ($dataCourses as $dat) {
        $dat->view_id = $data->id;
    }

    $DBC->insert_records('metaadmin_customview_course', $dataCourses);
}

/**
 * To delete custom views and their courses relations.
 *
 * @param $id
 */
function delete_customview($id) {
    $DBC = get_centralized_db_connection();
    $DBC->delete_records('metaadmin_customview_course', array('view_id' => $id));
    $DBC->delete_records('metaadmin_customview', array('id' => $id));
}

/**
 * To get the academy of the course in the hub.
 *
 * @param $id
 * @return null
 */
function get_hub_course_academy($id) {
    $hubDB = databaseConnection::instance()->get('hub');

    $sql = "SELECT hcd.id as id, hcd.demourl as url
            FROM {hub_site_directory} as hsd
            LEFT JOIN {hub_course_directory} as hcd ON hcd.siteid = hsd.id
            WHERE hcd.id = ".$id;

    $course = $hubDB->get_record_sql($sql);

    if ($course->url != "") {
        $pieces = explode('/', $course->url);
        $academy = $pieces[3]; //academie du parcours
    } else {
        $academy = null;
    }
    return $academy;
}

/**
 * To get the academy of the course in the hub.
 *
 * @param $id
 * @return null
 */
function get_hub_course_realid($id) {
    $hubDB = databaseConnection::instance()->get('hub');
    $sql = "SELECT hcd.id as id, hcd.sitecourseid as real_id
            FROM {hub_site_directory} as hsd
            LEFT JOIN {hub_course_directory} as hcd ON hcd.siteid = hsd.id
            WHERE hcd.id = ".$id;

    $course = $hubDB->get_record_sql($sql);

    if ($course->real_id) {
        $realId = $course->real_id;
    } else {
        $realId = null;
    }
    return $realId;
}

/**
 * To get ids of lines to take in metaadmin stats if a custom view is used.
 *
 * @param $course_aca
 * @param $course_id
 * @param $year
 * @return mixed
 */
function get_ids_to_keep($course_aca, $course_id, $year) {
    $DBC = get_centralized_db_connection();
    $sql = 'SELECT id
            FROM metaadmin_sp_'.$year.'
            WHERE academy_enrol = "'.$course_aca.'"
            AND courseid = '.$course_id;

    $res = $DBC->get_records_sql($sql);
    $tab = array_keys($res);
    return $tab;
}


/**
 *
 * To send statsparticipants reports to emails lists in metaadmin_customview table.
 * @param $now
 */
function send_statsparticipants_report($now) {
	global $CFG;
    $DBC = get_centralized_db_connection();
    $allViews = $DBC->get_records("metaadmin_customview");
	$subject = get_string('statsreport_subject', 'local_metaadmin');
    $userfrom = \core_user::get_noreply_user();
	
	require_once($CFG->dirroot.'/local/magisterelib/databaseConnection.php');
	
	foreach ($allViews as $view) {
		if ($view->send_report) {
			$today = 0;
			$last_send = 0;
			$nbDays = 0;
			$day = $view->day_report;
			if ($view->frequency_report == "weekly") {
				$today = date_format($now, 'N');
			} else {
				$today = date_format($now, 'j');
				$nbDays = date('t');
				if ($today == $nbDays && $day >= $nbDays) {
					$day = $nbDays;
				}
			}
			
			if ($day == $today) {
				$year = date("Y");
				$month = date("m");
				if ($month < 9) {
					$year--;
				}
				$lastconnmin = mktime(0,0,0,9,1,$year);
				$lastconnmax = mktime(0,0,0,8,31,$year+1);
				$role = 'participant';
				
				$pkey = $CFG->metaadmin_customview_reports_export_ownaca_key;
				$result = databaseConnection::instance()->get($view->user_academy)->get_record_sql("SELECT permission FROM mdl_role_assignments ra INNER JOIN mdl_role_capabilities rc ON (rc.roleid = ra.roleid AND rc.contextid = ra.contextid) WHERE ra.userid = ".$view->user_id." AND ra.contextid = ".context_system::instance()->id." AND rc.capability = ?",array('local/metaadmin:statsparticipants_viewallacademies'));
				if ($result !== false) {
					if ($result->permission == 1) {
						$pkey = $CFG->metaadmin_customview_reports_export_allaca_key;
					} else {
						$adminscsv = databaseConnection::instance()->get($view->user_academy)->get_record_sql("SELECT value FROM mdl_config c WHERE name = 'siteadmins'");
						$admins = explode(',',$adminscsv->value);
						if (in_array($view->user_id,$admins)) {
							$pkey = $CFG->metaadmin_customview_reports_export_allaca_key;
						}
					}
				}
				
				$attachfilename = 'export_'.$view->id.'_'.date("Y-m-d").'.csv';
				$attachfile = $CFG->tempdir.'/'.$attachfilename;
				$expurl = $CFG->wwwroot.'/local/metaadmin/customview_statsparticipants_export.php?action=export&format=csv&key='.$pkey.'&so=academy%20ASC&lastconnmax='.($lastconnmax*1000).'&lastconnmin='.($lastconnmin*1000).'&parcoursidentifiant_year=&gaia_origine=1&parcoursidentifiant_name=&userrole=participant&view_id='.$view->id;
				file_put_contents($attachfile, file_get_contents($expurl));
				
                $params = new \stdClass();
				$params->viewaca = $view->user_academy;
				if (substr($view->user_academy, 0, 3) == "ac-") {
					$params->viewaca = substr($view->user_academy, 3);
				}
                $params->viewname = $view->view_name;
				$contenuTXT = get_string('statsreport_contentTXT', 'local_metaadmin', $params);
				$contenuHTML = get_string('statsreport_contentHTML', 'local_metaadmin', $params);
				
				$emails = explode(',', $view->emails);
				foreach ($emails as $mail) {
					$userto = setCustomTo($mail);
					$hasBeenSent = email_to_user($userto, $userfrom, $subject, $contenuTXT, $contenuHTML.'</p>',$attachfile, $attachfilename);
				}
				if ($hasBeenSent) {
					$last_send = $now->getTimestamp();
				}
				$DBC->update_record("metaadmin_customview", array("id" => $view->id, "last_send_time" => $last_send));
			}
		}
	}
}
