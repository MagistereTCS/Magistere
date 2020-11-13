<?php
//======================================================================
// Classe d'alimentation des statistiques des parcours indexes
//======================================================================
global $CFG;
require_once($CFG->dirroot.'/local/magisterelib/databaseConnection.php');

class StatsIndexation
{
	private $rawdata = array();
	
	const CAT_PARCOURS ='Parcours de formation';
	const CAT_SESSION = 'Session de formation';
	const CAT_ARCHIVE = 'Archive';
	
	function __construct()
	{

	}
	
	function execute()
	{
		$this->truncate_stats_indexation_table();
		$this->merge_data();
		if(count($this->rawdata)!=0){
			$this->insert_data();
		}
	}
	
	/**
	 * Find every indexed courses in moodle_indexation with a new course_identification and located in 
	 * 'Parcours de formation', 'Session de formation' or 'Archive' and stock datas in $rawdata.
	 *
	 * @access protected
	 * @param $acaDB which mixed Return the moodle_database instance or false is the connection failed
	 * @return void
	 */
	protected function get_courseindexation_list_from_aca($acaDB)
	{
        global $CFG;
        $sql = "SELECT CONCAT(im.year, lic.code, im.title, im.version,c.path) as fakeid,
				CONCAT(im.year, '_', lic.code, '_', im.title) as course_identification,
				(SELECT name FROM {course_categories} cc
				 INNER JOIN {context} c2 ON (c2.instanceid = cc.id) 
				 WHERE c2.id = SUBSTRING_INDEX(SUBSTRING_INDEX(c.path, '/', 3), '/', -1)
				) AS category_name,
				COUNT(im.id) as index_count
				FROM {local_indexation} im
				INNER JOIN ".$CFG->centralized_dbname.".local_indexation_codes lic ON lic.id=im.codeorigineid
				INNER JOIN {context} c ON (c.instanceid = im.courseid) 
				WHERE c.contextlevel = 50
				AND (
				      c.path LIKE(SELECT CONCAT('%/',id,'/%') FROM {context} WHERE contextlevel = 40 AND instanceid = (SELECT id FROM {course_categories} WHERE name ='".StatsIndexation::CAT_PARCOURS."' AND parent = 0))
				   OR c.path LIKE(SELECT CONCAT('%/',id,'/%') FROM {context} WHERE contextlevel = 40 AND instanceid = (SELECT id FROM {course_categories} WHERE name ='".StatsIndexation::CAT_SESSION."' AND parent = 0))
				   OR c.path LIKE(SELECT CONCAT('%/',id,'/%') FROM {context} WHERE contextlevel = 40 AND instanceid = (SELECT id FROM {course_categories} WHERE name ='".StatsIndexation::CAT_ARCHIVE."' AND parent = 0))
				)
				AND im.year IS NOT NULL AND im.year != ''
				AND lic.code IS NOT NULL
				AND im.title IS NOT NULL AND im.title != ''
				GROUP BY category_name, course_identification";

		$courses_list = $acaDB->get_records_sql($sql);

		print_r($courses_list);

		if(isset($courses_list)){
			foreach($courses_list as $course)
			{
				if(count($this->rawdata)!=0){
					unset($course_identification_founded);
					$course_identification_founded = 0;
					foreach($this->rawdata as $key => $data) // On boucle sur les donnees stockees dans $rawdata au fur et e mesure
					{
						if($data->course_identification == $course->course_identification) // Dans ce cas on additionne les count trouve avec ceux deje present dans $rawdata
						{
							if($course->category_name == StatsIndexation::CAT_PARCOURS)
							{
								$data->courses_number += isset($course->index_count) ? $course->index_count : 0;
							}
							if($course->category_name == StatsIndexation::CAT_SESSION)
							{
								$data->active_sessions_number += isset($course->index_count) ? $course->index_count : 0;
							}
							if($course->category_name == StatsIndexation::CAT_ARCHIVE)
							{
								$data->archived_sessions_number += isset($course->index_count) ? $course->index_count : 0;
							}
							$course_identification_founded = 1;
							break;				
						}
					}
					if($course_identification_founded == 0) // Dans ce cas le course identification n'est pas trouve donc on insere une nouvelle valeur dans $rawdata
					{
						$newdata = new stdClass();
						$newdata->course_identification = $course->course_identification;
						$newdata->courses_number = 0;
						$newdata->active_sessions_number = 0;
						$newdata->archived_sessions_number = 0;
						if($course->category_name == StatsIndexation::CAT_PARCOURS)
						{
							$newdata->courses_number = isset($course->index_count) ? $course->index_count : 0;
						}
						if($course->category_name == StatsIndexation::CAT_SESSION)
						{
							$newdata->active_sessions_number = isset($course->index_count) ? $course->index_count : 0;
						}
						if($course->category_name == StatsIndexation::CAT_ARCHIVE)
						{
							$newdata->archived_sessions_number = isset($course->index_count) ? $course->index_count : 0;
						}
						array_push($this->rawdata, $newdata);
					}
				}
				else //Cas ou on se retrouve sans donnee dans $rawdata
				{
					$newdata = new stdClass();
					$newdata->course_identification = $course->course_identification;
					$newdata->courses_number = 0;
					$newdata->active_sessions_number = 0;
					$newdata->archived_sessions_number = 0;
					if($course->category_name == StatsIndexation::CAT_PARCOURS)
					{
						$newdata->courses_number = isset($course->index_count) ? $course->index_count : 0;
					}
					if($course->category_name == StatsIndexation::CAT_SESSION)
					{
						$newdata->active_sessions_number = isset($course->index_count) ? $course->index_count : 0;
					}
					if($course->category_name == StatsIndexation::CAT_ARCHIVE)
					{
						$newdata->archived_sessions_number = isset($course->index_count) ? $course->index_count : 0;
					}
					array_push($this->rawdata, $newdata);
				}
			}	
		}
	}
	
	/**
	 * Execute get_courseindexation_list_from_aca function on every academy.
	 *
	 * @access protected
	 * @return void
	 */
	protected function merge_data()
	{
		$list_academy = $this->list_academy();
		
		foreach ($list_academy as $key => $academy)
		{
			unset($acaDB);
			if (($acaDB = databaseConnection::instance()->get($academy)) === false){echo 'Erreur connexion '.$academy.'' ; continue;}else{echo 'Connexion reussie '.$academy.' ' ;}
			$this->get_courseindexation_list_from_aca($acaDB);
		}			
	}
	
	/**
	 * Inserted all datas stocked in $rawdata to the centralized table 'cr_stats_indexation'.
	 *
	 * @access protected
	 * @return void
	 */
	protected function insert_data(){
		$dbconn = get_centralized_db_connection();
		$dbconn->insert_records('cr_stats_indexation', $this->rawdata);
	}
	
	/**
	 * Recreated the list of academies using the config file which list all academies 
	 * and other organizations which have not necessarily a database.
	 *
	 * @access protected
	 * @return array
	 */
	protected function list_academy()
	{
		$list_raw_aca = get_magistere_academy_config();
		$list_aca = array();
		foreach($list_raw_aca as $key => $aca)
		{
			if($list_raw_aca[$key]['name'] != "reseau-canope" && $list_raw_aca[$key]['name'] != "dgesco" && $list_raw_aca[$key]['name'] != "efe" && $list_raw_aca[$key]['name'] != "ih2ef" && substr($list_raw_aca[$key]['name'], 0, 3) != "ac-"){
				continue;
			}
			array_push($list_aca,$list_raw_aca[$key]['name']);
		}
		return $list_aca;
	}
	
	/**
	 * Deleted all datas stocked in the centralized table 'cr_stats_indexation'.
	 *
	 * @access protected
	 * @return void
	 */
	protected function truncate_stats_indexation_table()
	{
		$dbconn = get_centralized_db_connection();
		$dbconn->execute("TRUNCATE TABLE cr_stats_indexation");
	}
	
	/**
	 * Remove the version in the course identification.
	 * 
	 * @access protected
	 * @param $courseIdentification The global course identification with the version
	 * @return string
	 */
	protected function substr_course_identification($courseIdentification)
	{
		$pos = strrpos($courseIdentification, "_");
		return substr($courseIdentification, 0, $pos);
	} 

}
