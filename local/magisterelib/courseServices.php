<?php

require_once($CFG->dirroot.'/local/magisterelib/databaseConnection.php');

class OfferCourseList
{
	private $data = array();
	private $rawdata = array();
	private $rawdata_hub = array();
	private $filters = '';
	private $sqlFilters_local = '';
	private $sqlFilters_hub = '';
	private $course_count = 0;
	private $fieldsValues = array();
	private $collections = array();

	function __construct($filters)
	{
		$this->filters = $filters;
		$this->load_filters_local($filters);
		$this->load_filters_hub($filters);
		
		$this->load_hub_data();
		
		if (isset($filters['self_inscription']) && $filters['self_inscription'] > 0)
		{
		  $this->get_courses_list();
		  $this->merge_data();
		}
		
		$this->merge_aca();
		
		$this->sort_by_startdate();
		
		$this->course_count = count($this->data);
		
		$this->buildFields();
	}

    protected function load_filters_hub($filters)
    {
        $wheres = array();

        if (isset($filters['id'])) {
            $this->sqlFilters_hub = 'ind.course_id = '.$filters['id'];
            return;
        }

        // filtre sur Type de parcours.
        if($filters['self_inscription'] == 3){

            $wheres[] = 'lcc.inscription_method like "%self%"';
            $wheres[] = 'lcc.publish = '.CourseHub::PUBLISH_PUBLISHED;
            $wheres[] = 'lcc.isasession = 1';
            $wheres[] = 'lcc.enrolrole = "participant"';

            $currenttime = time();
            $wheres[] = "((lcc.enrolenddate = 0 AND lcc.enrolstartdate = 0) 
			OR (".$currenttime." <= lcc.enrolenddate AND lcc.enrolstartdate = 0) 
			OR (lcc.enrolstartdate <= ".$currenttime." AND ".$currenttime." <= lcc.enrolenddate) 
			OR (lcc.enrolenddate = 0 AND lcc.enrolstartdate <= ".$currenttime."))";

        }else{
            $wheres[] = 'lcc.publish = '.CourseHub::PUBLISH_SHARED;
        }

        $this->sqlFilters_hub = implode(' AND ', $wheres);
    }
	
	protected function load_filters_local($filters)
	{
		$wheres = array();
		
		if(isset($filters['self_inscription']) && ($filters['self_inscription'] == 1 || $filters['self_inscription'] == 2)){
			if($filters["self_inscription"] == 1){
				$wheres[] = 'ind.collection != "autoformation"';
			}else{
				$wheres[] = 'ind.collection = "autoformation"';
			}
            $this->sqlFilters_local = implode(' AND ', $wheres);
		}
	}

	protected function load_hub_data()
	{
		global $CFG;
		
		require_once($CFG->dirroot.'/local/coursehub/CourseHub.php');

		$hub = CourseHub::instance();
		
		$masterid = $hub->getMasterIdentifiant();
		
		if (databaseConnection::instance()->get($masterid) === false){return;}

		$sql =
"SELECT 
lcc.id              as hubcourseid,
lci.id              as ind_id,
lcc.*,
LCASE(lcs.identifiant)     as academy_name,
lcs.id              as site_id,
lcs.url             as url,
lcc.name    as fullname,
lcc.name    as ind_nom_parcours,
lci.objectif       as ind_objectifs,
lcc.summary     as ind_description,
lic.shortname      as ind_collection,
lci.tps_a_distance  as ind_tps_a_distance,
lci.tps_en_presence as ind_tps_en_presence,
lci.accompagnement  as ind_accompagnement,
lci.origin  as ind_origine,
lci.authors   as ind_liste_auteurs,
lci.contact as ind_contact,
lci.validateby      as ind_validation,
lci.updatedate    as ind_derniere_maj,
lci.departementid      as ind_department,
lci.academyid         as ind_academy,
lci.originespeid     as ind_origin_espe,
lci.year            as ind_year,
lci.codeorigineid as ind_origine_gaia_id,
lci.title           as ind_title,
lci.version         as ind_version,

t_dep.libelle_long  as dep_libelle_long,
t_espe.name         as espe_name,
t_aca.libelle       as aca_name,

lcc.inscription_method,
lcc.enrolstartdate,
lcc.enrolenddate,
lcc.isasession,
lcc.enrolrole,
lcc.maxparticipant,
lcc.hasakey
FROM {".CourseHub::TABLE_COURSE."} as lcc
LEFT JOIN {".CourseHub::TABLE_SLAVE."} as lcs ON (lcs.id = lcc.slaveid)
LEFT JOIN {".CourseHub::TABLE_INDEX."} lci ON (lci.publishid = lcc.id)
LEFT JOIN ".$CFG->centralized_dbname.".local_indexation_collections lic ON lic.id=lci.collectionid
LEFT JOIN {t_departement} t_dep ON (t_dep.id = lci.departementid)
LEFT JOIN {t_origine_espe} t_espe ON (t_espe.id = lci.originespeid)
LEFT JOIN {t_academie} t_aca ON (t_aca.id = lci.academyid)
WHERE
lcc.deleted = 0
".($this->sqlFilters_hub ? ' AND ' . $this->sqlFilters_hub : '')."
ORDER BY lcc.timemodified DESC";
		
		$hub_courses = databaseConnection::instance()->get($masterid)->get_records_sql($sql);
				
		
		$distant_site = array();
		foreach ($hub_courses as $key=>$course)
		{
			$academy_name = substr($course->url,strrpos($course->url,'/')+1);
			$distant_site[$academy_name]['url'] = $course->url;
			$distant_site[$academy_name]['courses'][$course->hubcourseid] = $course;
			$distant_site[$academy_name]['coursesids'][] = $course->hubcourseid;
		}
		
		
		if (isset($this->filters['self_inscription']) && $this->filters['self_inscription'] > 0)
		{
			unset($distant_site[$CFG->academie_name]);
		}
		
		$distant_site2 = array();
		foreach ($distant_site as $academy_name => $data)
		{
			if (databaseConnection::instance()->get($academy_name) === false){error_log('offerCourseList.php/load_hub_data()/'.$academy_name.'/Database_connection_failed'); continue;}
			
			$aca_query = 
"SELECT e.courseid, COUNT(ue.id) as nb
FROM {enrol} e 
LEFT JOIN {user_enrolments} ue ON (ue.enrolid = e.id)
WHERE e.enrol = 'self'
AND e.courseid IN (SELECT c.instanceid FROM {context} c WHERE c.path like CONCAT((SELECT path FROM {context} WHERE instanceid = (SELECT id FROM {course_categories} WHERE name = 'session en auto-inscription') AND contextlevel = 40),'%') AND c.instanceid IN (".implode(',',$data['coursesids']).") AND contextlevel = 50 )
GROUP BY e.courseid";
			
			$aca_result = databaseConnection::instance()->get($academy_name)->get_records_sql($aca_query);

			foreach($aca_result as $value)
			{
				$distant_site[$academy_name]['courses'][$value->courseid]->nb_participant = $value->nb;
				$distant_site2[$academy_name]['url'] = $distant_site[$academy_name]['url'];
				$distant_site2[$academy_name]['courses'][$value->courseid] = $distant_site[$academy_name]['courses'][$value->courseid];
				$distant_site2[$academy_name]['coursesids'][] = $value->courseid;
			}
		}
		
		
		if (isset($this->filters['self_inscription']) && ($this->filters['self_inscription'] == 1 || $this->filters['self_inscription'] == 2))
		{
			$this->rawdata_hub = $distant_site2;
		}else{
			$this->rawdata_hub = $distant_site;
		}
	}
	
	protected function get_courses_list()
	{
		global $DB, $USER, $CFG;

		$sql = 
"SELECT CONCAT(IFNULL(c.id,0),IFNULL(ind.id,0),IFNULL(e.id,0)) AS unirow, c.*, IF(e.password > '', 1, 0) as hasakey,
ind.id as ind_id,
c.fullname    as ind_nom_parcours,
ind.objectif       as ind_objectifs,
c.summary     as ind_description,
lic.shortname      as ind_collection,
ind.tps_a_distance  as ind_tps_a_distance,
ind.tps_en_presence as ind_tps_en_presence,
ind.accompagnement  as ind_accompagnement,
ind.origin  as ind_origine,
ind.authors   as ind_liste_auteurs,
ind.contact as ind_contact,
ind.validateby      as ind_validation,
ind.updatedate    as ind_derniere_maj,
ind.departementid      as ind_department,
ind.academyid         as ind_academy,
ind.originespeid     as ind_origin_espe,
ind.year            as ind_year,
ind.codeorigineid as ind_origine_gaia_id,
ind.title           as ind_title,
ind.version         as ind_version,

t_dep.libelle_long  as dep_libelle_long,
t_espe.name         as espe_name,
t_aca.libelle       as aca_name,

e.enrolenddate,
e.enrolstartdate,
e.customint3 as maxparticipant,
GROUP_CONCAT(DISTINCT(pub.publicid)) AS concat_publics

FROM {course} as c
INNER JOIN {course_categories} cc ON cc.id = c.category
INNER JOIN {enrol} e ON (e.courseid = c.id AND e.enrol like 'self' AND e.status = 0)
LEFT JOIN {local_indexation}       ind        ON ind.courseid = c.id
LEFT JOIN ".$CFG->centralized_dbname.".local_indexation_collections lic ON lic.id=ind.collectionid
LEFT JOIN {t_departement} t_dep ON (t_dep.id = ind.departementid)
LEFT JOIN {t_origine_espe} t_espe ON (t_espe.id = ind.originespeid)
LEFT JOIN {t_academie} t_aca ON (t_aca.id = ind.academyid)
LEFT JOIN {local_indexation_public} pub ON (pub.indexationid = ind.id) 
WHERE
cc.path like CONCAT((SELECT path FROM {course_categories} WHERE LCASE(name) = 'session en auto-inscription'),'%')
AND e.roleid = (SELECT id FROM {role} WHERE shortname = 'participant')
AND ((e.enrolenddate = 0 AND e.enrolstartdate <= UNIX_TIMESTAMP(NOW()))
OR (e.enrolenddate >= UNIX_TIMESTAMP(NOW()) AND e.enrolstartdate = 0)
OR (e.enrolenddate = 0 AND e.enrolstartdate = 0)
OR (e.enrolstartdate <= UNIX_TIMESTAMP(NOW()) AND e.enrolenddate >= UNIX_TIMESTAMP(NOW())))
AND (e.customint3 = 0 OR (SELECT COUNT(id) FROM {user_enrolments} ue WHERE ue.enrolid = e.id) < e.customint3)
AND e.customint6 = 1 AND e.status = 0
".($this->sqlFilters_local ? ' AND ' . $this->sqlFilters_local : '')."
         GROUP BY c.id
        ORDER BY c.timemodified DESC
       ";
		$courses_list = $DB->get_records_sql($sql);
		
		$this->rawdata = isset($courses_list) ? $courses_list : null;
	}
	
	protected function merge_data()
	{
		global $CFG;
		
		foreach($this->rawdata as $id=>$course)
		{
			if (!isset($this->rawdata_hub[$CFG->academie_name]['courses'][$id]))
			{
				$this->rawdata_hub[$CFG->academie_name]['courses'][$id] = $course;
			}
			else{
				
				foreach($course as $key=>$value)
				{
					$this->rawdata_hub[$CFG->academie_name]['courses'][$id]->{$key} = $value;
				}
			}
			$this->rawdata_hub[$CFG->academie_name]['courses'][$id]->source = 'local';
		}
	}
	
	protected function merge_aca()
	{
		foreach($this->rawdata_hub as $aca=>$courses)
		{
			foreach($courses['courses'] as $id=>$course)
			{
                $key = ($course->enrolstartdate==0?'99990101000000':date('YmdHis',$course->enrolstartdate))
                    .'_'.(isset($course->timepublished)?date('YmdHis',$course->timepublished):date('YmdHis',strtotime(substr($course->ind_derniere_maj, 0, 10))))
                    .'_'.(isset($course->startdate)?date('YmdHis',$course->startdate):0)
                    .'_'.(isset($course->hubcourseid)?$course->hubcourseid:'l'.$course->id);
                $this->data[$key] = $course;
			}
		}
	}

    protected function sort_by_startdate()
    {
        krsort($this->data);
    }
	
	function getCoursesCount()
	{
		return $this->course_count;
	}
	
	function getCourses($page=null,$itemPerPage=25)
	{
		if ($page !== null)
		{
			return array_slice($this->data,$page*$itemPerPage,$itemPerPage,true);
		}else{
			return $this->data;
		}
	}




    function getCoursesSortByFonction($page=null,$itemPerPage=25, $publics=null)
    {
        $courses = [];
        foreach ($this->data as $course){
            if(isset($course->concat_publics)){
                $course_publics = explode( ',', $course->concat_publics );
                foreach ($course_publics as $cp){
                    if(in_array($cp,$publics)){
                        $courses[] = $course;
                    }
                }
            }
        }

        if ($page !== null) {
            return array_slice($courses,$page*$itemPerPage,$itemPerPage,true);
        } else {
            return $courses;
        }
    }

	
	function getFilter($key,$default='')
	{
		if (isset($this->filters[$key]))
		{
			return $this->filters[$key];
		}
		return $default;
	}
	
	function filter_url($page)
	{
		$this->filters['page'] = $page;
		return http_build_query($this->filters);
	}
	
	
	function courseToHTML($course, $course_type=null)
	{
		global $OUTPUT;
		
		if ( isset($course->origine) )
		{
			echo '
			<div class="home-course" id="course '.$course->id.'">
				<div class="post-img collection-'.$course->collection.'"><img src="'.$OUTPUT->image_url('general/logo_'.$course->collection.'', 'theme').'" alt="'.$course->logo_label.'"><br>'.($course->logo_label!='empty'?$course->logo_label:'').'</div>
				<div class="desc">
					<div class="origine"><img src="'.$OUTPUT->image_url('general/logo_btn_acad', 'theme').'"><a class="aca_name">'.$course->origine['academie'].'</a></div>
					<p class="post-title"><a href="'.$course->origine['url'].'">'.$course->fullname.'</a></p>
					<p>';
					if ($course_type == 'SeFormer')
					{
            echo date('d/m/Y',$course->startdate);
					}
					else if ($course_type == 'conception')
					{
            echo $course->shortname;
					}
					else if ($course_type == 'Former')
					{
            echo $course->shortname;
	            if($course->startdate != 0){
	            	echo ' - '.date('d/m/Y',$course->startdate);
	            }
					}
					echo '
					</p>
				</div>
				<div class="clear"></div>
			</div>';
		}
		else if($course_type == 'conception')
		{
			echo '
			<div class="home-course" id="course '.$course->id.'">
				<div class="post-img collection-'.$course->collection.'"><img src="'.$OUTPUT->image_url('general/logo_'.$course->collection.'', 'theme').'" alt="'.$course->logo_label.'"><br>'.($course->logo_label!='empty'?$course->logo_label:'').'</div>
				<div class="desc">
					<p class="categorie collection-'.$course->collection.'">'.$course->ariane.'</p>
					<p class="post-title"><a href="course/view.php?id='.$course->id.'">'.$course->fullname.'</a></p>
					<p>'.$course->shortname.'</p>
				</div>
				<div class="clear"></div>
			</div>';
		}
		else if($course_type == 'SeFormer')
		{
			echo '
			<div class="home-course" id="course '.$course->id.'">
				<div class="post-img collection-'.$course->collection.'"><img src="'.$OUTPUT->image_url('general/logo_'.$course->collection.'', 'theme').'" alt="'.$course->logo_label.'"><br>'.($course->logo_label!='empty'?$course->logo_label:'').'</div>
				<div class="desc">
					<p class="categorie collection-'.$course->collection.'">&nbsp;</p>
					<p class="post-title"><a href="course/view.php?id='.$course->id.'">'.$course->fullname.'</a></p>
					<p>Début le '.date('d/m/Y',$course->startdate).'</p>
				</div>
				<div class="clear"></div>
			</div>';
		}
		else if($course_type == 'SeFormerCollabo')
		{
			echo '
			<div class="home-course" id="course '.$course->id.'">
				<div class="post-img collection-'.$course->collection.'"><img src="'.$OUTPUT->image_url('general/logo_'.$course->collection.'', 'theme').'" alt="'.$course->logo_label.'"><br>'.($course->logo_label!='empty'?$course->logo_label:'').'</div>
				<div class="desc">
					<p class="categorie collection-'.$course->collection.'">&nbsp;</p>
					<p class="post-title"><a href="course/view.php?id='.$course->id.'">'.$course->fullname.'</a></p>
					<p>&nbsp;</p>
				</div>
				<div class="clear"></div>
			</div>';
		}
		else
		{
			echo '
			<div class="home-course" id="course '.$course->id.'">
				<div class="post-img collection-'.$course->collection.'"><img src="'.$OUTPUT->image_url('general/logo_'.$course->collection.'', 'theme').'" alt="'.$course->logo_label.'"><br>'.($course->logo_label!='empty'?$course->logo_label:'').'</div>
				<div class="desc">
					<p class="categorie collection-'.$course->collection.'">'.$course->ariane.'</p>
					<p class="post-title"><a href="course/view.php?id='.$course->id.'">'.$course->fullname.'</a></p>';
			if($course->startdate != 0){
				echo '<p>'.$course->shortname.' - '.date('d/m/Y',$course->startdate).'</p>';
			}
			else{
				echo '<p>'.$course->shortname.'</p>';
			}
			echo '		
				</div>
				<div class="clear"></div>
			</div>';
		}
	}


	protected function buildFields()
	{
		$this->fieldsValues = array();
		//$fieldsValues['durations']

        $DBC = get_centralized_db_connection();
        $originesdb = $DBC->get_records('local_indexation_origins');
        $origines = array();

        foreach($originesdb as $key => $data){
            $origines[$data->shortname] = $data->name;
        }
        
		foreach($this->data as $key=>$course)
		{
			
			if ($course->ind_department != null && $course->ind_department != 0)
			{
				$this->fieldsValues['department'][$course->ind_department] = $course->dep_libelle_long;
			}
			
			if ($course->ind_origin_espe != null && $course->ind_origin_espe != 0)
			{
				$this->fieldsValues['origin_espe'][$course->ind_origin_espe] = $course->espe_name;
			}

			if ( isset($origines[$course->ind_origine]) )
			{
				$this->fieldsValues['origine'][$course->ind_origine] = $origines[$course->ind_origine];
			}
			
			if ( $course->ind_origine == 'academie')
			{
				$this->fieldsValues['academy'][$course->ind_academy] = $course->aca_name;
			}
		}
		
		$this->fieldsValues['durations'] = array(
				'-3'  => 'Moins de 3h',
				'3-6' => 'Entre 3 et 6h',
				'6-9' => 'Entre 6 et 9h',
				'9-'  => 'Plus de 9h'
		);
		
		$this->fieldsValues['level'][0] =
		$this->fieldsValues['target'][0] =
		$this->fieldsValues['domain'][0] =
		$this->fieldsValues['department'][0] =
		$this->fieldsValues['origin_espe'][0] =
		$this->fieldsValues['origine'][0] =
		$this->fieldsValues['academy'][0]='';
		
		ksort($this->fieldsValues['level']);
		ksort($this->fieldsValues['target']);
		ksort($this->fieldsValues['domain']);
		ksort($this->fieldsValues['department']);
		ksort($this->fieldsValues['origin_espe']);
		ksort($this->fieldsValues['origine'], SORT_NATURAL);
		ksort($this->fieldsValues['academy']);
	}
	
	
	function setFilter($key,$value)
	{
		$this->filters[$key] = $value;
	}
	
	function mergeFields($offerCourseList, $self_inscription = 1)
	{
		foreach($offerCourseList->getFields() as $category=>$fields)
		{
			foreach($fields as $name=>$value)
			{
				$this->fieldsValues[$category][$name] = $value;
			}
		}
	}
	
	function getFields()
	{
		return $this->fieldsValues;
	}
}
