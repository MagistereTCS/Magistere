<?php
/**
 * Moodle MyIndex local plugin
 * This class is used by the api 
 *
 * @package    local_myindex
 * @copyright  2020 TCS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once($CFG->dirroot.'/local/magisterelib/databaseConnection.php');
require_once($CFG->dirroot.'/filter/mediaplugin/filter.php');

class MyIndexApi
{
    
    private $data = array();
    private $rawdata = array();
    private $json = '';
    private $allowed_role = array("participant","formateur","concepteur");
    private $mod = self::MOD_MAIN;
    private $color = array("light-blue", "light-orange", "light-yellow", "light-purple", "light-green", "light-pink", "light-red", "light-blue", "light-orange", "light-yellow");

    const MOD_MAIN = 1;
    const MOD_MODAL = 2;

    const FILTER_ALLCOURSE = 'allcourse';
    const FILTER_SEFORMER = 'seformer';
    const FILTER_FORMER = 'former';
    const FILTER_CONCEVOIR = 'concevoir';
    const FILTER_ESPACECOLLABO = 'espacecollabo';
    const FILTER_PARCOURSDEMO = 'parcoursdemo';
    const FILTER_FAVORIS = 'favoris';
    const FILTER_ARCHIVE = 'archive';
    
    const FILTERS = array(
        self::FILTER_ALLCOURSE,
        self::FILTER_SEFORMER,
        self::FILTER_FORMER,
        self::FILTER_CONCEVOIR,
        self::FILTER_ESPACECOLLABO,
        self::FILTER_PARCOURSDEMO,
        self::FILTER_FAVORIS,
        self::FILTER_ARCHIVE
    );
    
    /***
     * The constructor will calculate all the results when called
     * @param int $mod Switch between the Main and the Modal mod. The Main mod is design to return multiple course. The Modal mod will only return one course with more informations 
     * @param string $aca_search When the mod is Main, this field is the value to search to filter the results returns. When the mod is Modal, this field is the name of the academie hosting the course requested
     * @param int/string $courseid__filter When the mod is Main, this contains the "FILTERS" used to filter the results
     * @param boolean $archive If true return the archive courses, if false return the main courses
     */
    function __construct($mod = self::MOD_MAIN, $aca_search = '', $courseid__filter = self::FILTER_ALLCOURSE, $archive = false)
    {
        $this->mod = $mod;
        
        if ($mod == self::MOD_MODAL)
        {
            if ($this->is_subscribed($aca_search,$courseid__filter))
            {
                $this->get_course_modal($aca_search,$courseid__filter);
            }else{
                $this->get_course_favorite_modal($aca_search,$courseid__filter);
            }
            $this->generate_json();
        }else{
            self::set_selected_filter($courseid__filter);
            if ($courseid__filter == self::FILTER_FAVORIS) {
                $this->get_courses_favorite_list($aca_search,$archive,true);
            }else if ($courseid__filter != self::FILTER_ALLCOURSE){
                $this->get_courses_list($aca_search,$courseid__filter);
            }else{
                $this->get_courses_list($aca_search,$courseid__filter);
                $this->get_courses_favorite_list($aca_search,false);
            }
            
            $this->sortall();
            
            $this->generate_json();
        }
    }
    
    /***
     * Return the rawdata attribut
     * @return Object[]
     */
    function query()
    {
        return $this->rawdata;
    }
    
    /***
     * Return the generated JSON code
     * @return string JSON code
     */
    function get_json()
    {
        return $this->json;
    }
    
    /***
     * Return the data attribut
     * @return Object[]
     */
    function get_data()
    {
        return $this->data;
    }
    
    /***
     * Return the current user filter preference.
     * @return string
     */
    static function get_selected_filter()
    {
        $filter = get_user_preferences('local_myindex_selected_filter',self::FILTER_ALLCOURSE);
        if (!in_array($filter,self::FILTERS)){return self::FILTER_ALLCOURSE;}
        return $filter;
    }
    
    /***
     * Set the current user filter preference
     * @param string $filter
     */
    static function set_selected_filter($filter)
    {
        set_user_preference('local_myindex_selected_filter',$filter);
    }
    
    /***
     * Return the list of filter available to the user to avoid empty results
     * @return void|stdClass Return the list of filter or void if the user is not connected
     */
    static function get_courses_filters()
    {
        global $CFG, $USER;
        
        if (!$USER->id)
        {
            return;
        }
        
        $filters = new stdClass();
        foreach(self::FILTERS AS $filter)
        {
            $filters->{$filter} = 0;
        }
        
        $magistere_academies = get_magistere_academy_config();
        
        $i = 1;
        foreach ($magistere_academies as $academy_name => $data)
        {
            if($academy_name == 'frontal' || $academy_name == 'hub' || $academy_name == 'cndp'){continue;}
            if ((databaseConnection::instance()->get($academy_name)) === false){error_log('MyIndexApi.php/get_courses_filters/'.$academy_name.'/Database_connection_failed'); continue;}
            
            $courses_list_aca = databaseConnection::instance()->get($academy_name)->get_records_sql(
                "
SELECT uni.courseid, uni.role, uni.collection, uni.isfav, uni.categorie
FROM (
                SELECT cx.instanceid AS courseid, GROUP_CONCAT(r.shortname) AS role, clic.shortname AS collection, IF(lfc.id IS NULL,FALSE,TRUE) AS isfav,
                    (
                        SELECT cc.name
                        FROM {context} cx2
                        INNER JOIN {course_categories} cc ON (cc.id = cx2.instanceid)
                        WHERE cx2.id = substring_index(substring_index(cx.path,'/',3),'/',-1)
                        AND cx2.contextlevel = 40
                    ) AS categorie
        		FROM {context} cx
        		INNER JOIN {role_assignments} ra ON ra.contextid = cx.id
        		INNER JOIN {role} r ON ra.roleid = r.id
        		INNER JOIN {user} u ON ra.userid = u.id
                LEFT JOIN {local_favoritecourses} lfc ON lfc.userid = u.id AND lfc.courseid = cx.instanceid
                LEFT JOIN {local_indexation} li ON li.courseid = cx.instanceid
                LEFT JOIN ".$CFG->centralized_dbname.".local_indexation_collections clic ON clic.id=li.collectionid
        		WHERE cx.contextlevel = 50
        		 AND r.shortname in ('participant','formateur', 'tuteur')
        		 AND ". (($academy_name==$CFG->academie_name)?"u.id = '".$USER->id."'":"(u.username = '".$USER->username."')") ."
                 AND cx.path NOT LIKE(SELECT CONCAT('%/',id,'/%') FROM {context} WHERE contextlevel = 40 AND instanceid = (SELECT id FROM {course_categories} WHERE name ='Corbeille' AND depth = 1))
        		GROUP BY cx.id

                UNION DISTINCT
                SELECT lfc.courseid AS courseid, '' AS role, '' AS collection, TRUE AS isfav, '' AS categorie
                FROM {local_favoritecourses} lfc
                INNER JOIN {context} cx2 ON (cx2.instanceid = lfc.courseid)
                WHERE cx2.contextlevel = 50
                 AND cx2.path NOT LIKE(SELECT CONCAT('%/',id,'/%') FROM {context} WHERE contextlevel = 40 AND instanceid = (SELECT id FROM {course_categories} WHERE name ='Corbeille' AND depth = 1)) 
                 AND ". (($academy_name==$CFG->academie_name)?"lfc.userid = '".$USER->id."'":"(lfc.username = '".$USER->username."')") ."
) uni
GROUP BY uni.courseid
",array());
            if ($courses_list_aca !== false)
            {
                foreach($courses_list_aca AS $course)
                {
                    $filters->{self::FILTER_ALLCOURSE} += 1;
                    if ($course->isfav > 0) {
                        $filters->{self::FILTER_FAVORIS} += 1;
                    }
                    
                    if ($course->categorie == 'Archive' && strlen($course->role) > 0) {
                        $filters->{self::FILTER_ARCHIVE} += 1;
                    }
                    
                    if ($course->collection == 'espacecollab' && $course->categorie != 'Archive') {
                        $filters->{self::FILTER_ESPACECOLLABO} += 1;
                    }
                    
                    if (isset($course->role) && strlen($course->role) > 0 && strpos($course->role,'participant') !== false) {
                        if ($course->categorie == 'Parcours de formation') {
                            $filters->{self::FILTER_PARCOURSDEMO} += 1;
                        } else if ($course->categorie == 'Session de formation' && $course->collection != 'espacecollab') {
                            $filters->{self::FILTER_SEFORMER} += 1;
                        }
                    }
                    if (isset($course->role) && strlen($course->role) > 0 && (strpos($course->role,'formateur') !== false || strpos($course->role,'tuteur') !== false)) {
                        if ($course->categorie == 'Parcours de formation' && $course->collection != 'espacecollab') {
                            $filters->{self::FILTER_CONCEVOIR} += 1;
                        } else if ($course->categorie == 'Session de formation' && $course->collection != 'espacecollab') {
                            $filters->{self::FILTER_FORMER} += 1;
                        }
                    }
                }
            }
            $i++;
        }
        return $filters;
    }
    
    /***
     * Return the value of get_courses_groups() formated in JSON
     * @return string The JSON result
     */
    static function get_courses_filters_json()
    {
        $filters = self::get_courses_groups();
        $filters_json = new stdClass();
        $filters_json->groups = $filters;
        return json_encode($filters_json);
    }
    
    /***
     * Return the list of course with the given parameters
     * @param string $search The string to search in the course field
     * @param string $filter The filter to apply to the result
     */
    function get_courses_list($search,$filter)
    {
        global $CFG, $USER;
        
        if (!$USER->id)
        {
            return;
        }
        $search = str_replace('"', '', $search);
        
        $search_having = '';
        if (strlen(trim($search)) > 2)
        {
            $search_words = explode(' ', trim($search));
            
            $words_compare = array();
            foreach ($search_words AS $search_word)
            {
                if (strlen($search_word) > 2)
                {
                    $words_compare[] = "CONCAT(IFNULL(course_fullname,''),' ',IFNULL(index_keywords,''),' ',IFNULL(course_gaia,'')) LIKE '%".$search_word."%'";
                }
            }
            
            if (count($words_compare) > 0)
            {
                $search_having = "HAVING ".implode(' AND ', $words_compare);
            }
        }
        
        $sql_filter = '';
        if ($filter == self::FILTER_PARCOURSDEMO) {
            $sql_filter = " AND cx.path LIKE(SELECT CONCAT('%/',id,'/%') FROM mdl_context WHERE contextlevel = 40 AND instanceid = (SELECT id FROM mdl_course_categories WHERE name ='Parcours de formation' AND depth = 1))
                            AND r.shortname = 'participant'";
        }else if ($filter == self::FILTER_ESPACECOLLABO) {
            $sql_filter = " AND clic.shortname = 'espacecollab'
                            AND r.shortname IN ('participant','formateur', 'tuteur')
                            AND cx.path NOT LIKE(SELECT CONCAT('%/',id,'/%') FROM mdl_context WHERE contextlevel = 40 AND instanceid = (SELECT id FROM mdl_course_categories WHERE name ='Archive' AND depth = 1))";
        }else if ($filter == self::FILTER_SEFORMER) {
            $sql_filter = " AND cx.path LIKE(SELECT CONCAT('%/',id,'/%') FROM mdl_context WHERE contextlevel = 40 AND instanceid = (SELECT id FROM mdl_course_categories WHERE name ='Session de formation' AND depth = 1))
                            AND r.shortname = 'participant'
                            AND (clic.shortname IS NULL OR clic.shortname != 'espacecollab')";
        }else if ($filter == self::FILTER_FORMER) {
            $sql_filter = " AND cx.path LIKE(SELECT CONCAT('%/',id,'/%') FROM mdl_context WHERE contextlevel = 40 AND instanceid = (SELECT id FROM mdl_course_categories WHERE name ='Session de formation' AND depth = 1))
                            AND r.shortname IN ('formateur', 'tuteur')
                            AND (clic.shortname IS NULL OR clic.shortname != 'espacecollab')";
        }else if ($filter == self::FILTER_CONCEVOIR) {
            $sql_filter = " AND cx.path LIKE(SELECT CONCAT('%/',id,'/%') FROM mdl_context WHERE contextlevel = 40 AND instanceid = (SELECT id FROM mdl_course_categories WHERE name ='Parcours de formation' AND depth = 1))
                            AND r.shortname IN ('formateur', 'tuteur')
                            AND (clic.shortname IS NULL OR clic.shortname != 'espacecollab')";
        }else if ($filter == self::FILTER_ARCHIVE) {
            $sql_filter = "AND r.shortname IN ('participant','formateur', 'tuteur')
             AND cx.path LIKE(SELECT CONCAT('%/',id,'/%') FROM mdl_context WHERE contextlevel = 40 AND instanceid = (SELECT id FROM mdl_course_categories WHERE name ='Archive' AND depth = 1))";
        }else{
            $sql_filter = "AND r.shortname IN ('participant','formateur', 'tuteur')
             AND cx.path NOT LIKE(SELECT CONCAT('%/',id,'/%') FROM mdl_context WHERE contextlevel = 40 AND instanceid = (SELECT id FROM mdl_course_categories WHERE name ='Archive' AND depth = 1))";
        }
        
        
        $magistere_academies = get_magistere_academy_config();
        
        $i = 1;
        foreach ($magistere_academies as $academy_name => $data)
        {
            if($academy_name == 'frontal' || $academy_name == 'hub' || $academy_name == 'cndp'){continue;}
            if ((databaseConnection::instance()->get($academy_name)) === false){error_log('MyIndexApi.php/get_courses_list/'.$academy_name.'/Database_connection_failed'); continue;}
            
            $courses_list_aca = databaseConnection::instance()->get($academy_name)->get_records_sql(
                "SELECT CONCAT('".$i."',c.id,r.id) as unid,
                
IF(fav.timecreated IS NULL,0,fav.timecreated) AS sort_favtimecreated,
IF(c.startdate IS NULL,0,IF(c.startdate<UNIX_TIMESTAMP(),0,c.startdate)) AS sort_startdate,
IF(ul.timeaccess IS NULL,0,ul.timeaccess) AS sort_timeaccess,
c.id AS course_id,
c.fullname AS course_fullname,
c.shortname AS course_shortname,
c.summary AS course_description,
c.timecreated AS course_timecreated,
'".$academy_name."' AS course_academy,
CONCAT('".$CFG->magistere_domaine."/".$academy_name."/course/view.php',?,'id=',c.id) AS course_url,
c.startdate AS course_startdate,
GROUP_CONCAT(r.shortname) AS user_role,
cx.path,
cc.id AS category_id,
cc.name AS category_name,
c.visible AS visible,
IF(fav.id IS NOT NULL, 1,0) AS favorite,
clic.shortname AS index_collection_name,
(
     SELECT GROUP_CONCAT(CONCAT(u2.id,'|',u2.lastname,'|',u2.firstname) SEPARATOR '&')
     FROM {user} u2
     INNER JOIN {role_assignments} ra2 ON(ra2.userid = u2.id)
     INNER JOIN {role} r2 ON(r2.id = ra2.roleid)
     WHERE r2.shortname = 'formateur' AND ra2.contextid = cx.id
) AS formateurs,
                
(
     SELECT GROUP_CONCAT(CONCAT(gf.dispositif_id,'[|]',gf.dispositif_name,'[|]',gf.module_id,'[|]',gf.module_name) SEPARATOR '[&]')
     FROM {local_gaia_session_course} lgsc
     INNER JOIN {local_gaia_formations} gf ON (gf.session_id = lgsc.session_id AND gf.dispositif_id = lgsc.dispositif_id AND gf.module_id = lgsc.module_id)
     WHERE lgsc.course_id = c.id
) AS course_gaia,
(
     SELECT GROUP_CONCAT(CONCAT(b.name,'|','".$CFG->wwwroot."/local/myindex/image.php/".$academy_name."/',f.id) SEPARATOR '&')
     FROM {badge_issued} bi
     INNER JOIN {badge} b ON (b.id = bi.badgeid)
     INNER JOIN {files} f ON (f.itemid = bi.badgeid)
     WHERE bi.userid = u.id AND b.courseid = c.id AND f.component = 'badges' AND f.filearea = 'userbadge' AND f.filesize > 0 AND f.contextid = (SELECT id FROM {context} cx5 WHERE cx5.contextlevel = 30 AND cx5.instanceid = u.id )
) AS badges,
( SELECT CONCAT(path,'/') FROM mdl_context WHERE contextlevel = 40 AND instanceid = (SELECT id FROM mdl_course_categories WHERE name ='Parcours de formation' AND depth = 1) ) AS cat_parcours_formation_path,
( SELECT CONCAT(path,'/') FROM mdl_context WHERE contextlevel = 40 AND instanceid = (SELECT id FROM mdl_course_categories WHERE name ='Session de formation' AND depth = 1) ) AS cat_session_formation_path,
( SELECT CONCAT(path,'/') FROM mdl_context WHERE contextlevel = 40 AND instanceid = (SELECT id FROM mdl_course_categories WHERE name ='Corbeille' AND depth = 1) ) AS cat_corbeille_path,
( SELECT CONCAT(path,'/') FROM mdl_context WHERE contextlevel = 40 AND instanceid = (SELECT id FROM mdl_course_categories WHERE name ='Archive' AND depth = 1) ) AS cat_archive_path,
li.thumbnailid AS index_picture,
crimg.type AS index_picture_type,
crimg.hashname AS index_picture_hashname,
crimg.createdate AS index_picture_createdate,
crimg.cleanname AS index_picture_cleanname,
li.videoid AS index_video,
crvid.type AS index_video_type,
crvid.hashname AS index_video_hashname,
crvid.createdate AS index_video_createdate,
crvid.cleanname AS index_video_cleanname,
li.objectif AS index_objectifs,
li.origin AS index_origin,
li.achievementmark AS index_achievement,
clio.name AS index_origin_name,
ta.short_uri AS index_origin_aca_shortname,
ta.libelle AS index_origin_aca_name,
lmc.progress AS user_progression,
IF(c.startdate>0,from_unixtime(c.startdate, '%d/%m/%Y'),NULL) AS index_startdate,
IF(c.enddate>0,from_unixtime(c.enddate, '%d/%m/%Y'),NULL) AS index_enddate,
IF(lw.id IS NOT NULL,1,0) AS has_wf,
IF (li.tps_en_presence=0,'',IF (li.tps_en_presence>60,CONCAT(FLOOR(li.tps_en_presence/60),' heures ',MOD(li.tps_en_presence,60),' minutes'),CONCAT(li.tps_en_presence,' minutes'))) AS index_timepresence,
IF (li.tps_a_distance=0,'',IF (li.tps_a_distance>60,CONCAT(FLOOR(li.tps_a_distance/60),' heures ',MOD(li.tps_a_distance,60),' minutes'),CONCAT(li.tps_a_distance,' minutes'))) AS index_timedistance,
li.rythme_formation AS index_rythme,
(
     SELECT name FROM ".$CFG->centralized_dbname.".local_indexation_domains lid WHERE lid.id = li.domainid
) AS index_domain,
li.domainid AS index_domain_id,
(
     SELECT GROUP_CONCAT( lik.keyword SEPARATOR '[&]') FROM {local_indexation_keywords} lik WHERE lik.indexationid = li.id
) AS index_keywords,
(
    SELECT count(ntmp.id)
    FROM (
        SELECT *, SUBSTRING_INDEX(SUBSTRING_INDEX(contexturl, 'd=', -1),'#p',1) AS discussionid
        FROM mdl_notifications
        WHERE component = 'mod_forum'
        AND timeread is NULL
        AND eventtype = ''
        AND id IN (SELECT notificationid FROM {message_popup_notifications})
    ) ntmp
    INNER JOIN {forum_discussions} fd ON (fd.id = ntmp.discussionid)
    WHERE
    fd.course = c.id
    AND useridto = u.id
) AS forums_notifications
                
    		FROM {course} as c
    		INNER JOIN {context} as cx ON cx.instanceid = c.id
    		INNER JOIN {role_assignments} as ra ON ra.contextid = cx.id
    		INNER JOIN {role} as r ON ra.roleid = r.id
    		INNER JOIN {user} as u ON ra.userid = u.id
    		LEFT JOIN {context} as cx2 ON cx2.id = SUBSTRING_INDEX(SUBSTRING_INDEX(cx.path, '/', 3), '/', -1)
    		LEFT JOIN {course_categories} as cc ON cc.id = cx2.instanceid
    		LEFT JOIN {local_indexation} as li ON li.courseid = c.id
    	    LEFT JOIN ".$CFG->centralized_dbname.".local_indexation_collections clic ON clic.id=li.collectionid
            LEFT JOIN ".$CFG->centralized_dbname.".local_indexation_origins clio ON clio.shortname=li.origin
    		LEFT JOIN {local_favoritecourses} as fav ON fav.courseid = c.id AND fav.userid=u.id
    		LEFT JOIN ".$CFG->centralized_dbname.".cr_resources as crimg ON crimg.resourceid = li.thumbnailid
    		LEFT JOIN ".$CFG->centralized_dbname.".cr_resources as crvid ON crvid.resourceid = li.videoid
            LEFT JOIN {t_academie} as ta ON ta.id = li.academyid
            LEFT JOIN {user_lastaccess} ul ON (ul.userid = u.id AND ul.courseid = c.id)
            LEFT JOIN {local_workflow} lw ON (lw.courseid=c.id)
            LEFT JOIN {local_myindex_courseprogress} lmc ON (lmc.userid = u.id AND lmc.courseid = c.id)
    		WHERE cx.contextlevel = 50
    		 AND cx2.contextlevel = 40
    		 AND ". (($academy_name==$CFG->academie_name)?"u.id = '".$USER->id."'":"(u.username = '".$USER->username."')") ."
             AND cx.path NOT LIKE(SELECT CONCAT('%/',id,'/%') FROM mdl_context WHERE contextlevel = 40 AND instanceid = (SELECT id FROM mdl_course_categories WHERE name ='Corbeille' AND depth = 1))
".$sql_filter."
    		GROUP BY c.id
".$search_having."
    		ORDER BY unid ASC",array('?'));
            
            if ($courses_list_aca !== false)
            {

                foreach($courses_list_aca as $key => $course){
                    
                    $courses_list_aca[$key]->user_is_participant = 0;
                    
                    $course->is_demo = false;
                    // set 'concepteur' fake role
                    if(strpos($course->user_role,'formateur') !== false && strpos($course->path, $course->cat_parcours_formation_path) === 0){
                        $courses_list_aca[$key]->user_role = 'concepteur';
                    }else if (strpos($course->user_role,'formateur') !== false)
                    {
                        $courses_list_aca[$key]->user_role = 'formateur';
                    }else if (strpos($course->user_role,'tuteur') !== false)
                    {
                        $courses_list_aca[$key]->user_role = 'tuteur';
                    }else{
                        $courses_list_aca[$key]->user_role = 'participant';
                        $courses_list_aca[$key]->user_is_participant = 1;
                        
                        if(strpos($course->path, $course->cat_parcours_formation_path) === 0 && $course->index_collection_name != 'espacecollab'){
                            $course->is_demo = true;
                        }
                    }
                    
                    $course->is_coming = false;
                    if(strpos($course->path, $course->cat_session_formation_path) === 0){
                        $course->is_coming = (($course->visible=="0")?true:false);
                    }

                    if($course->user_progression == 0){
                        $course->user_progression = null;
                    }

                    if(!$course->index_achievement){
                        $course->user_progression = null;
                    }


                }
                $this->rawdata = $this->rawdata + $courses_list_aca;
            }
            $i++;
        }
        
    }
    
    /***
     * Return the list of favorite course on which the user is not enrolled
     * @param string $search The string to search in the course field
     * @param string $archive If true, search in the archive, else search in the active courses
     * @param boolean $returnsubscriptions if true, return all the favorite course including the course where the user is enrolled
     */
    function get_courses_favorite_list($search,$archive,$returnsubscriptions=false)
    {
        global $CFG, $USER;
        
        if (!$USER->id) {
            return;
        }
        
        $search = str_replace('"', '', $search);
        
        $search_having = '';
        if (strlen(trim($search)) > 2)
        {
            $search_words = explode(' ', trim($search));
            
            $words_compare = array();
            foreach ($search_words AS $search_word)
            {
                if (strlen($search_word) > 2)
                {
                    $words_compare[] = "CONCAT(IFNULL(course_fullname,''),' ',IFNULL(index_keywords,''),' ',IFNULL(course_gaia,'')) LIKE '%".$search_word."%'";
                }
            }
            
            if (count($words_compare) > 0)
            {
                $search_having = "HAVING ".implode(' AND ', $words_compare);
            }
        }
        
        
        $magistere_academies = get_magistere_academy_config();
        
        $i = 1;
        foreach ($magistere_academies as $academy_name => $data)
        {
            if($academy_name == 'frontal' || $academy_name == 'hub' || $academy_name == 'cndp'){continue;}
            if ((databaseConnection::instance()->get($academy_name)) === false){error_log('MyIndexApi.php/get_courses_list/'.$academy_name.'/Database_connection_failed'); continue;}
            
            $courses_list_aca = databaseConnection::instance()->get($academy_name)->get_records_sql(
                "SELECT CONCAT('".$i."',c.id,fav.id) as unid,
                
IF(fav.timecreated IS NULL,0,fav.timecreated) AS sort_favtimecreated,
IF(c.startdate IS NULL,0,IF(c.startdate<UNIX_TIMESTAMP(),0,c.startdate)) AS sort_startdate,
IF(ul.timeaccess IS NULL,0,ul.timeaccess) AS sort_timeaccess,
c.id AS course_id,
c.fullname AS course_fullname,
c.shortname AS course_shortname,
c.summary AS course_description,
c.timecreated AS course_timecreated,
'".$academy_name."' AS course_academy,
CONCAT('".$CFG->magistere_domaine."/".$academy_name."/course/view.php',?,'id=',c.id) AS course_url,
c.startdate AS course_startdate,
cx.path,
cc.id AS category_id,
cc.name AS category_name,
c.visible AS visible,
IF(fav.id IS NOT NULL, 1,0) AS favorite,
clic.shortname AS index_collection_name,
(
     SELECT GROUP_CONCAT(CONCAT(u2.id,'|',u2.lastname,'|',u2.firstname) SEPARATOR '&')
     FROM {user} u2
     INNER JOIN {role_assignments} ra2 ON(ra2.userid = u2.id)
     INNER JOIN {role} r2 ON(r2.id = ra2.roleid)
     WHERE r2.shortname = 'formateur' AND ra2.contextid = cx.id
) AS formateurs,
                
(
     SELECT GROUP_CONCAT(CONCAT(gf.dispositif_id,'[|]',gf.dispositif_name,'[|]',gf.module_id,'[|]',gf.module_name) SEPARATOR '[&]')
     FROM {local_gaia_session_course} lgsc
     INNER JOIN {local_gaia_formations} gf ON (gf.session_id = lgsc.session_id AND gf.dispositif_id = lgsc.dispositif_id AND gf.module_id = lgsc.module_id)
     WHERE lgsc.course_id = c.id
) AS course_gaia,
(
     SELECT GROUP_CONCAT(CONCAT(b.name,'|','".$CFG->wwwroot."/local/myindex/image.php/".$academy_name."/',f.id) SEPARATOR '&')
     FROM {badge_issued} bi
     INNER JOIN {badge} b ON (b.id = bi.badgeid)
     INNER JOIN {files} f ON (f.itemid = bi.badgeid)
     WHERE bi.userid = u.id AND b.courseid = c.id AND f.component = 'badges' AND f.filearea = 'userbadge' AND f.filesize > 0 AND f.contextid = (SELECT id FROM {context} cx5 WHERE cx5.contextlevel = 30 AND cx5.instanceid = u.id )
) AS badges,
( SELECT CONCAT(path,'/') FROM mdl_context WHERE contextlevel = 40 AND instanceid = (SELECT id FROM mdl_course_categories WHERE name ='Parcours de formation' AND depth = 1) ) AS cat_parcours_formation_path,
( SELECT CONCAT(path,'/') FROM mdl_context WHERE contextlevel = 40 AND instanceid = (SELECT id FROM mdl_course_categories WHERE name ='Session de formation' AND depth = 1) ) AS cat_session_formation_path,
( SELECT CONCAT(path,'/') FROM mdl_context WHERE contextlevel = 40 AND instanceid = (SELECT id FROM mdl_course_categories WHERE name ='Corbeille' AND depth = 1) ) AS cat_corbeille_path,
( SELECT CONCAT(path,'/') FROM mdl_context WHERE contextlevel = 40 AND instanceid = (SELECT id FROM mdl_course_categories WHERE name ='Archive' AND depth = 1) ) AS cat_archive_path,
li.thumbnailid AS index_picture,
crimg.type AS index_picture_type,
crimg.hashname AS index_picture_hashname,
crimg.createdate AS index_picture_createdate,
crimg.cleanname AS index_picture_cleanname,
li.videoid AS index_video,
crvid.type AS index_video_type,
crvid.hashname AS index_video_hashname,
crvid.createdate AS index_video_createdate,
crvid.cleanname AS index_video_cleanname,
li.objectif AS index_objectifs,
li.origin AS index_origin,
li.achievementmark AS index_achievement,
clio.name AS index_origin_name,
ta.short_uri AS index_origin_aca_shortname,
ta.libelle AS index_origin_aca_name,
IF(c.startdate>0,from_unixtime(c.startdate, '%d/%m/%Y'),NULL) AS index_startdate,
IF(c.enddate>0,from_unixtime(c.enddate, '%d/%m/%Y'),NULL) AS index_enddate,
IF(lw.id IS NOT NULL,1,0) AS has_wf,
IF (li.tps_en_presence=0,'',IF (li.tps_en_presence>60,CONCAT(FLOOR(li.tps_en_presence/60),' heures ',MOD(li.tps_en_presence,60),' minutes'),CONCAT(li.tps_en_presence,' minutes'))) AS index_timepresence,
IF (li.tps_a_distance=0,'',IF (li.tps_a_distance>60,CONCAT(FLOOR(li.tps_a_distance/60),' heures ',MOD(li.tps_a_distance,60),' minutes'),CONCAT(li.tps_a_distance,' minutes'))) AS index_timedistance,
li.rythme_formation AS index_rythme,
(
     SELECT name FROM ".$CFG->centralized_dbname.".local_indexation_domains lid WHERE lid.id = li.domainid
) AS index_domain,
li.domainid AS index_domain_id,
(
     SELECT GROUP_CONCAT( lik.keyword SEPARATOR '[&]') FROM {local_indexation_keywords} lik WHERE lik.indexationid = li.id
) AS index_keywords
            
    		FROM {course} as c
            INNER JOIN {local_favoritecourses} fav ON fav.courseid = c.id 
    		INNER JOIN {context} as cx ON cx.instanceid = c.id
    		INNER JOIN {user} as u ON fav.userid = u.id
            LEFT JOIN {role_assignments} as ra ON ra.contextid = cx.id AND ra.userid = fav.userid
    		LEFT JOIN {context} as cx2 ON cx2.id = SUBSTRING_INDEX(SUBSTRING_INDEX(cx.path, '/', 3), '/', -1)
    		LEFT JOIN {course_categories} as cc ON cc.id = cx2.instanceid
    		LEFT JOIN {local_indexation} as li ON li.courseid = c.id
    	    LEFT JOIN ".$CFG->centralized_dbname.".local_indexation_collections clic ON clic.id=li.collectionid
            LEFT JOIN ".$CFG->centralized_dbname.".local_indexation_origins clio ON clio.shortname=li.origin
    		LEFT JOIN ".$CFG->centralized_dbname.".cr_resources as crimg ON crimg.resourceid = li.thumbnailid
    		LEFT JOIN ".$CFG->centralized_dbname.".cr_resources as crvid ON crvid.resourceid = li.videoid
            LEFT JOIN {t_academie} as ta ON ta.id = li.academyid
            LEFT JOIN {user_lastaccess} ul ON (ul.userid = u.id AND ul.courseid = c.id)
            LEFT JOIN {local_workflow} lw ON (lw.courseid=c.id)
    		WHERE cx.contextlevel = 50
    		 AND cx2.contextlevel = 40
             ".($returnsubscriptions?'':'AND ra.id IS NULL')."
             AND fav.userid = u.id
    		 AND ". (($academy_name==$CFG->academie_name)?"u.id = '".$USER->id."'":"(u.username = '".$USER->username."' OR u.email = '".$USER->email."')") ."
             AND cx.path ". ($archive?'': 'NOT ')."LIKE(SELECT CONCAT('%/',id,'/%') FROM mdl_context WHERE contextlevel = 40 AND instanceid = (SELECT id FROM mdl_course_categories WHERE name ='Archive' AND depth = 1))
             AND cx.path NOT LIKE(SELECT CONCAT('%/',id,'/%') FROM mdl_context WHERE contextlevel = 40 AND instanceid = (SELECT id FROM mdl_course_categories WHERE name ='Corbeille' AND depth = 1))
    		GROUP BY c.id
".$search_having."
    		ORDER BY unid ASC",array('?'));
            
            if ($courses_list_aca !== false)
            {
                
                foreach($courses_list_aca as $key => $course){
                    
                    $courses_list_aca[$key]->user_is_participant = 0;
                    
                    $course->is_demo = null;
                    $course->user_role = null;
                    $course->user_progression = null;
                    $course->forums_notifications = null;
                    
                    $course->is_coming = false;
                    if(strpos($course->path, $course->cat_session_formation_path) === 0){
                        $course->is_coming = (($course->visible=="0")?true:false);
                    }
                    
                    
                }
                $this->rawdata = $this->rawdata + $courses_list_aca;
            }
            $i++;
        }
        
    }
    
    /***
     * Return true if the user is enrolled on the course
     * @param string $academy_name The name of the academie of the course
     * @param int $courseid The id of the course
     * @return void|boolean Return true if the user is enrolled, false if not. Can return void if the user is not connected
     */
    function is_subscribed($academy_name,$courseid)
    {
        global $USER;
        
        if (!$USER->id) {
            return;
        }
        
        $course = databaseConnection::instance()->get($academy_name)->get_record_sql(
        "SELECT c.id
FROM {course} as c
INNER JOIN {context} as cx ON cx.instanceid = c.id
INNER JOIN {role_assignments} as ra ON ra.contextid = cx.id
INNER JOIN {user} as u ON ra.userid = u.id
WHERE cx.contextlevel = 50
AND u.username = ?
AND c.id = ?
GROUP BY c.id",
        array($USER->username,$courseid));
        return ($course !== false);
    }
    
    /***
     * Return the modal data of the course given in the parameters
     * @param string $academy_name The name of the academie of the course
     * @param int $courseid The id of the course
     */
    function get_course_modal($academy_name,$courseid)
    {
        global $CFG, $USER;
        
        if (!$USER->id)
        {
            return;
        }
        
        if ((databaseConnection::instance()->get($academy_name)) === false){error_log('MyIndexApi.php/get_course_modal/'.$academy_name.'/Database_connection_failed'); die;}
        
        $localuser = databaseConnection::instance()->get($academy_name)->get_record('user', array('username'=>$USER->username));
        
        $courses_modal = databaseConnection::instance()->get($academy_name)->get_records_sql(
                "SELECT c.id AS course_id,
c.fullname AS course_fullname,
c.shortname AS course_shortname,
c.summary AS course_description,
c.timecreated AS course_timecreated,
'".$academy_name."' AS course_academy,
CONCAT('".$CFG->magistere_domaine."/".$academy_name."/course/view.php',?,'id=',c.id) AS course_url,
c.startdate AS course_startdate,
GROUP_CONCAT(r.shortname) AS user_role,
cx.path,
cc.id AS category_id,
cc.name AS category_name,
c.visible AS visible,
IF(fav.id IS NOT NULL, 1,0) AS favorite,
clic.shortname AS index_collection_name,
(
     SELECT GROUP_CONCAT(CONCAT(u2.id,'|',u2.lastname,'|',u2.firstname) SEPARATOR '&')
     FROM {user} u2
     INNER JOIN {role_assignments} ra2 ON(ra2.userid = u2.id)
     INNER JOIN {role} r2 ON(r2.id = ra2.roleid)
     WHERE r2.shortname = 'formateur' AND ra2.contextid = cx.id
) AS formateurs,
                
(
     SELECT GROUP_CONCAT(CONCAT(gf.dispositif_id,'[|]',gf.dispositif_name,'[|]',gf.module_id,'[|]',gf.module_name) SEPARATOR '[&]')
     FROM {local_gaia_session_course} lgsc
     INNER JOIN {local_gaia_formations} gf ON (gf.session_id = lgsc.session_id AND gf.dispositif_id = lgsc.dispositif_id AND gf.module_id = lgsc.module_id)
     WHERE lgsc.course_id = c.id
) AS course_gaia,
(
     SELECT GROUP_CONCAT(CONCAT(b.name,'|','".$CFG->wwwroot."/local/myindex/image.php/".$academy_name."/',f.id) SEPARATOR '&')
     FROM {badge_issued} bi
     INNER JOIN {badge} b ON (b.id = bi.badgeid)
     INNER JOIN {files} f ON (f.itemid = bi.badgeid)
     WHERE bi.userid = u.id AND b.courseid = c.id AND f.component = 'badges' AND f.filearea = 'userbadge' AND f.filesize > 0 AND f.contextid = (SELECT id FROM {context} cx5 WHERE cx5.contextlevel = 30 AND cx5.instanceid = u.id )
) AS badges,
( SELECT CONCAT(path,'/') FROM mdl_context WHERE contextlevel = 40 AND instanceid = (SELECT id FROM mdl_course_categories WHERE name ='Parcours de formation' AND depth = 1) ) AS cat_parcours_formation_path,
( SELECT CONCAT(path,'/') FROM mdl_context WHERE contextlevel = 40 AND instanceid = (SELECT id FROM mdl_course_categories WHERE name ='Session de formation' AND depth = 1) ) AS cat_session_formation_path,
( SELECT CONCAT(path,'/') FROM mdl_context WHERE contextlevel = 40 AND instanceid = (SELECT id FROM mdl_course_categories WHERE name ='Corbeille' AND depth = 1) ) AS cat_corbeille_path,
( SELECT CONCAT(path,'/') FROM mdl_context WHERE contextlevel = 40 AND instanceid = (SELECT id FROM mdl_course_categories WHERE name ='Archive' AND depth = 1) ) AS cat_archive_path,
li.thumbnailid AS index_picture,
crimg.type AS index_picture_type,
crimg.hashname AS index_picture_hashname,
crimg.createdate AS index_picture_createdate,
crimg.cleanname AS index_picture_cleanname,
li.videoid AS index_video,
crvid.type AS index_video_type,
crvid.hashname AS index_video_hashname,
crvid.createdate AS index_video_createdate,
crvid.cleanname AS index_video_cleanname,
li.objectif AS index_objectifs,
li.origin AS index_origin,
clio.name AS index_origin_name,
li.achievementmark AS index_achievement,
ta.short_uri AS index_origin_aca_shortname,
ta.libelle AS index_origin_aca_name,
IF(c.startdate>0,from_unixtime(c.startdate, '%d/%m/%Y'),NULL) AS index_startdate,
IF(c.enddate>0,from_unixtime(c.enddate, '%d/%m/%Y'),NULL) AS index_enddate,
IF(lw.id IS NOT NULL,1,0) AS has_wf,
IF(c.startdate < UNIX_TIMESTAMP(),1,0) AS index_hasstarted,
IF (li.tps_en_presence=0,'',CONCAT(IF (li.tps_en_presence>60,CONCAT(FLOOR(li.tps_en_presence/60),' heures '),''),IF(MOD(li.tps_en_presence,60)>0,CONCAT(MOD(li.tps_en_presence,60),' minutes'),''))) AS index_timepresence,
IF (li.tps_a_distance=0,'',CONCAT(IF (li.tps_a_distance>60,CONCAT(FLOOR(li.tps_a_distance/60),' heures '),''),IF(MOD(li.tps_a_distance,60)>0,CONCAT(MOD(li.tps_a_distance,60),' minutes'),''))) AS index_timedistance,
li.rythme_formation AS index_rythme,
(
     SELECT name FROM ".$CFG->centralized_dbname.".local_indexation_domains lid WHERE lid.id = li.domainid
) AS index_domain,
li.domainid AS index_domain_id,
(
     SELECT GROUP_CONCAT( lik.keyword SEPARATOR '[&]') FROM {local_indexation_keywords} lik WHERE lik.indexationid = li.id
) AS index_keywords,
FLOOR((
SELECT
(
    SELECT COUNT(*)
    FROM mdl_course_modules cm6
    INNER JOIN mdl_course_modules_completion cmc ON (cmc.coursemoduleid = cm6.id)
    WHERE cm6.course = c.id AND cm6.completion > 0 AND cmc.userid = u.id
)/(
    SELECT COUNT(*)
    FROM mdl_course_modules cm7
    WHERE cm7.course = c.id AND cm7.completion > 0
))*100) AS user_progression,
(
    SELECT count(ntmp.id)
    FROM (
        SELECT *, SUBSTRING_INDEX(SUBSTRING_INDEX(contexturl, 'd=', -1),'#p',1) AS discussionid
        FROM mdl_notifications
        WHERE component = 'mod_forum'
        AND timeread is NULL
        AND eventtype = ''
        AND id IN (SELECT notificationid FROM {message_popup_notifications})
    ) ntmp
    INNER JOIN {forum_discussions} fd ON (fd.id = ntmp.discussionid)
    WHERE
    fd.course = c.id
    AND useridto = u.id
) AS forums_notifications
                
		FROM {course} as c
		INNER JOIN {context} as cx ON cx.instanceid = c.id
		INNER JOIN {role_assignments} as ra ON ra.contextid = cx.id
		INNER JOIN {role} as r ON ra.roleid = r.id
		INNER JOIN {user} as u ON ra.userid = u.id
		LEFT JOIN {context} as cx2 ON cx2.id = SUBSTRING_INDEX(SUBSTRING_INDEX(cx.path, '/', 3), '/', -1)
		LEFT JOIN {course_categories} as cc ON cc.id = cx2.instanceid
		LEFT JOIN {local_indexation} as li ON li.courseid = c.id
	    LEFT JOIN ".$CFG->centralized_dbname.".local_indexation_collections clic ON clic.id=li.collectionid
        LEFT JOIN ".$CFG->centralized_dbname.".local_indexation_origins clio ON clio.shortname=li.origin
		LEFT JOIN {local_favoritecourses} as fav ON fav.courseid = c.id AND fav.userid=u.id
		LEFT JOIN ".$CFG->centralized_dbname.".cr_resources as crimg ON crimg.resourceid = li.thumbnailid
		LEFT JOIN ".$CFG->centralized_dbname.".cr_resources as crvid ON crvid.resourceid = li.videoid
        LEFT JOIN {t_academie} as ta ON ta.id = li.academyid
        LEFT JOIN {user_lastaccess} ul ON (ul.userid = u.id AND ul.courseid = c.id)
        LEFT JOIN {local_workflow} lw ON (lw.courseid=c.id)
		WHERE cx.contextlevel = 50
		 AND cx2.contextlevel = 40
		 AND r.shortname in ('participant','formateur','tuteur')
		 AND u.id = '".$localuser->id."'
         AND c.id = '".$courseid."'
         AND cx.path NOT LIKE(SELECT CONCAT('%/',id,'/%') FROM mdl_context WHERE contextlevel = 40 AND instanceid = (SELECT id FROM mdl_course_categories WHERE name ='Corbeille' AND depth = 1))
		GROUP BY c.id",array('?'));
        
        if ($courses_modal !== false)
        {
            
            foreach($courses_modal as $key => $course){
                
                $courses_modal[$key]->user_is_participant = 0;
                
                $course->is_demo = false;
                // set 'concepteur' fake role
                if(strpos($course->user_role,'formateur') !== false && strpos($course->path, $course->cat_parcours_formation_path) === 0){
                    $courses_modal[$key]->user_role = 'concepteur';
                }else if (strpos($course->user_role,'formateur') !== false)
                {
                    $courses_modal[$key]->user_role = 'formateur';
                }else if (strpos($course->user_role,'tuteur') !== false)
                {
                    $courses_modal[$key]->user_role = 'tuteur';
                }else{
                    $courses_modal[$key]->user_role = 'participant';
                    $courses_modal[$key]->user_is_participant = 1;
                    
                    if(strpos($course->path, $course->cat_parcours_formation_path) === 0 && $course->index_collection_name != 'espacecollab'){
                        $course->is_demo = true;
                    }
                }
                
                $course->is_coming = false;
                if(strpos($course->path, $course->cat_session_formation_path) === 0){
                    $course->is_coming = (($course->visible=="0")?true:false);
                }
                
                if($course->user_progression == 0){
                    $course->user_progression = null;
                }
                
                if(!$course->index_achievement){
                    $course->user_progression = null;
                }
                
                
            }
            $this->rawdata = $courses_modal;
        }
        
        $this->rawdata = $courses_modal;
    }
    
    /***
     * Return the modal data of the favorite course given in the parameters
     * @param string $academy_name The name of the academie of the course
     * @param int $courseid The id of the course
     */
    function get_course_favorite_modal($academy_name,$courseid)
    {
        global $CFG, $USER;
        
        if (!$USER->id)
        {
            return;
        }
        
        if ((databaseConnection::instance()->get($academy_name)) === false){error_log('MyIndexApi.php/get_course_modal/'.$academy_name.'/Database_connection_failed'); die;}
        
        $localuser = databaseConnection::instance()->get($academy_name)->get_record('user', array('username'=>$USER->username));
        
        $courses_modal = databaseConnection::instance()->get($academy_name)->get_records_sql(
            "SELECT c.id AS course_id,
c.fullname AS course_fullname,
c.shortname AS course_shortname,
c.summary AS course_description,
c.timecreated AS course_timecreated,
'".$academy_name."' AS course_academy,
CONCAT('".$CFG->magistere_domaine."/".$academy_name."/course/view.php',?,'id=',c.id) AS course_url,
c.startdate AS course_startdate,
cx.path,
cc.id AS category_id,
cc.name AS category_name,
c.visible AS visible,
IF(fav.id IS NOT NULL, 1,0) AS favorite,
clic.shortname AS index_collection_name,
(
     SELECT GROUP_CONCAT(CONCAT(u2.id,'|',u2.lastname,'|',u2.firstname) SEPARATOR '&')
     FROM {user} u2
     INNER JOIN {role_assignments} ra2 ON(ra2.userid = u2.id)
     INNER JOIN {role} r2 ON(r2.id = ra2.roleid)
     WHERE r2.shortname = 'formateur' AND ra2.contextid = cx.id
) AS formateurs,
            
(
     SELECT GROUP_CONCAT(CONCAT(gf.dispositif_id,'[|]',gf.dispositif_name,'[|]',gf.module_id,'[|]',gf.module_name) SEPARATOR '[&]')
     FROM {local_gaia_session_course} lgsc
     INNER JOIN {local_gaia_formations} gf ON (gf.session_id = lgsc.session_id AND gf.dispositif_id = lgsc.dispositif_id AND gf.module_id = lgsc.module_id)
     WHERE lgsc.course_id = c.id
) AS course_gaia,
(
     SELECT GROUP_CONCAT(CONCAT(b.name,'|','".$CFG->wwwroot."/local/myindex/image.php/".$academy_name."/',f.id) SEPARATOR '&')
     FROM {badge_issued} bi
     INNER JOIN {badge} b ON (b.id = bi.badgeid)
     INNER JOIN {files} f ON (f.itemid = bi.badgeid)
     WHERE bi.userid = u.id AND b.courseid = c.id AND f.component = 'badges' AND f.filearea = 'userbadge' AND f.filesize > 0 AND f.contextid = (SELECT id FROM {context} cx5 WHERE cx5.contextlevel = 30 AND cx5.instanceid = u.id )
) AS badges,
( SELECT CONCAT(path,'/') FROM mdl_context WHERE contextlevel = 40 AND instanceid = (SELECT id FROM mdl_course_categories WHERE name ='Parcours de formation' AND depth = 1) ) AS cat_parcours_formation_path,
( SELECT CONCAT(path,'/') FROM mdl_context WHERE contextlevel = 40 AND instanceid = (SELECT id FROM mdl_course_categories WHERE name ='Session de formation' AND depth = 1) ) AS cat_session_formation_path,
( SELECT CONCAT(path,'/') FROM mdl_context WHERE contextlevel = 40 AND instanceid = (SELECT id FROM mdl_course_categories WHERE name ='Corbeille' AND depth = 1) ) AS cat_corbeille_path,
( SELECT CONCAT(path,'/') FROM mdl_context WHERE contextlevel = 40 AND instanceid = (SELECT id FROM mdl_course_categories WHERE name ='Archive' AND depth = 1) ) AS cat_archive_path,
li.thumbnailid AS index_picture,
crimg.type AS index_picture_type,
crimg.hashname AS index_picture_hashname,
crimg.createdate AS index_picture_createdate,
crimg.cleanname AS index_picture_cleanname,
li.videoid AS index_video,
crvid.type AS index_video_type,
crvid.hashname AS index_video_hashname,
crvid.createdate AS index_video_createdate,
crvid.cleanname AS index_video_cleanname,
li.objectif AS index_objectifs,
li.origin AS index_origin,
clio.name AS index_origin_name,
li.achievementmark AS index_achievement,
ta.short_uri AS index_origin_aca_shortname,
ta.libelle AS index_origin_aca_name,
IF(c.startdate>0,from_unixtime(c.startdate, '%d/%m/%Y'),NULL) AS index_startdate,
IF(c.enddate>0,from_unixtime(c.enddate, '%d/%m/%Y'),NULL) AS index_enddate,
IF(lw.id IS NOT NULL,1,0) AS has_wf,
IF(c.startdate < UNIX_TIMESTAMP(),1,0) AS index_hasstarted,
IF (li.tps_en_presence=0,'',CONCAT(IF (li.tps_en_presence>60,CONCAT(FLOOR(li.tps_en_presence/60),' heures '),''),IF(MOD(li.tps_en_presence,60)>0,CONCAT(MOD(li.tps_en_presence,60),' minutes'),''))) AS index_timepresence,
IF (li.tps_a_distance=0,'',CONCAT(IF (li.tps_a_distance>60,CONCAT(FLOOR(li.tps_a_distance/60),' heures '),''),IF(MOD(li.tps_a_distance,60)>0,CONCAT(MOD(li.tps_a_distance,60),' minutes'),''))) AS index_timedistance,
li.rythme_formation AS index_rythme,
(
     SELECT name FROM ".$CFG->centralized_dbname.".local_indexation_domains lid WHERE lid.id = li.domainid
) AS index_domain,
li.domainid AS index_domain_id,
(
     SELECT GROUP_CONCAT( lik.keyword SEPARATOR '[&]') FROM {local_indexation_keywords} lik WHERE lik.indexationid = li.id
) AS index_keywords,
(
    SELECT count(ntmp.id)
    FROM (
        SELECT *, SUBSTRING_INDEX(SUBSTRING_INDEX(contexturl, 'd=', -1),'#p',1) AS discussionid
        FROM mdl_notifications
        WHERE component = 'mod_forum'
        AND timeread is NULL
        AND eventtype = ''
        AND id IN (SELECT notificationid FROM {message_popup_notifications})
    ) ntmp
    INNER JOIN {forum_discussions} fd ON (fd.id = ntmp.discussionid)
    WHERE
    fd.course = c.id
    AND useridto = u.id
) AS forums_notifications
            
		FROM {course} as c
		INNER JOIN {context} as cx ON cx.instanceid = c.id
        INNER JOIN {local_favoritecourses} as fav ON fav.courseid = c.id
		INNER JOIN {user} as u ON fav.userid=u.id
		LEFT JOIN {context} as cx2 ON cx2.id = SUBSTRING_INDEX(SUBSTRING_INDEX(cx.path, '/', 3), '/', -1)
		LEFT JOIN {course_categories} as cc ON cc.id = cx2.instanceid
		LEFT JOIN {local_indexation} as li ON li.courseid = c.id
	    LEFT JOIN ".$CFG->centralized_dbname.".local_indexation_collections clic ON clic.id=li.collectionid
        LEFT JOIN ".$CFG->centralized_dbname.".local_indexation_origins clio ON clio.shortname=li.origin
		LEFT JOIN ".$CFG->centralized_dbname.".cr_resources as crimg ON crimg.resourceid = li.thumbnailid
		LEFT JOIN ".$CFG->centralized_dbname.".cr_resources as crvid ON crvid.resourceid = li.videoid
        LEFT JOIN {t_academie} as ta ON ta.id = li.academyid
        LEFT JOIN {user_lastaccess} ul ON (ul.userid = u.id AND ul.courseid = c.id)
        LEFT JOIN {local_workflow} lw ON (lw.courseid=c.id)
		WHERE cx.contextlevel = 50
		 AND cx2.contextlevel = 40
		 AND u.id = '".$localuser->id."'
         AND c.id = '".$courseid."'
         AND cx.path NOT LIKE(SELECT CONCAT('%/',id,'/%') FROM mdl_context WHERE contextlevel = 40 AND instanceid = (SELECT id FROM mdl_course_categories WHERE name ='Corbeille' AND depth = 1))
		GROUP BY c.id",array('?'));
        
        if ($courses_modal !== false)
        {
            
            foreach($courses_modal as $key => $course){
                
                $courses_modal[$key]->user_is_participant = 0;
                
                $course->is_demo = false;
                
                $course->is_demo = null;
                $course->user_role = null;
                $course->user_progression = null;
                $course->forums_notifications = null;
                
                $course->is_coming = false;
                if(strpos($course->path, $course->cat_session_formation_path) === 0){
                    $course->is_coming = (($course->visible=="0")?true:false);
                }
                
            }
            $this->rawdata = $courses_modal;
        }
        
        $this->rawdata = $courses_modal;
    }
    
    /***
     * Sort all the data of the class attribut "rawdata"
     */
    function sortall()
    {
        uasort($this->rawdata, 'self::sort_cmp');
    }
    
    /***
     * Custom sort function used by sortall()
     * @param unknown $a item to compare
     * @param unknown $b item to compare
     * @return number result of the comparison
     */
    function sort_cmp($a,$b)
    {
        // Sort fav
        // Sort startdate a venir
        // Sort no access
        // last access
        
        //sort_favtimecreated
        //sort_startdate
        //sort_timeaccess
        
        // Sort fav
        if ($a->sort_favtimecreated == $b->sort_favtimecreated)
        {
            // Sort startdate a venir
            
            if ($a->sort_startdate == $b->sort_startdate)
            {
                // Sort no access
                if ($a->sort_timeaccess == $b->sort_timeaccess)
                {
                    return 0;
                }else{
                    if ($a->sort_timeaccess < $b->sort_timeaccess)
                    {
                        return ($a->sort_timeaccess == 0)?-1:1;
                    }else{
                        return ($b->sort_timeaccess == 0)?1:-1;
                    }
                }
                
            }else{
                if ($a->sort_startdate < $b->sort_startdate)
                {
                    return ($a->sort_startdate == 0)?1:-1;
                }else{
                    return ($b->sort_startdate == 0)?-1:1;
                }
            }
            return 0;
        }
        else
        {
            return ($a->sort_favtimecreated < $b->sort_favtimecreated)?1:-1;
        }
        
    }
    
    /***
     * Generate the global JSON result
     */
    function generate_json()
    {
        global $CFG, $OUTPUT;
        $json_global = array();

        $alldata = &$this->rawdata;
        
        foreach($alldata AS $data)
        {
            $json_course = new stdClass();
            
            $json_course->course_id = $data->course_id;
            $json_course->course_fullname = $data->course_fullname;
            $json_course->course_shortname = $data->course_shortname;
            $json_course->course_description = $data->course_description;
            $json_course->course_academy = $data->course_academy;
            $json_course->course_url = $data->course_url;
            
            $category = new stdClass();
            $category->id = $data->category_id;
            $category->name = $data->category_name;
            $json_course->category = $category;
            
            $json_course->favorite = ($data->favorite?true:false);
            
            $json_course->course_formateurs = array();
            if ($data->index_collection_name != 'autoformation') {
                $formateurs = explode('&', $data->formateurs);
                foreach($formateurs AS $formateur)
                {
                    $formateur_data = explode('|', $formateur);
                    if (count($formateur_data) != 3){continue;}
                    $json_formateur = new stdClass();
                    $json_formateur->id = $formateur_data[0];
                    $json_formateur->lastname = $formateur_data[1];
                    $json_formateur->firstname = $formateur_data[2];
                    
                    $json_course->course_formateurs[] = $json_formateur;
                }
            }
            if (count($json_course->course_formateurs) == 0){$json_course->course_formateurs = null;}
            
            $json_course->course_gaia = array();
            $courses_gaia = explode('[&]', $data->course_gaia);
            foreach($courses_gaia AS $course_gaia)
            {
                $gaia = explode('[|]', $course_gaia);
                if (count($gaia) != 4){continue;}
                $json_gaia = new stdClass();
                $json_gaia->dispositif_id = $gaia[0];
                $json_gaia->dispositif_name = $gaia[1];
                $json_gaia->module_id = $gaia[2];
                $json_gaia->module_name = $gaia[3];
                $json_course->course_gaia[] = $json_gaia;
            }
            if (count($json_course->course_gaia) == 0){ $json_course->course_gaia = null; }
            
            if ($data->index_collection_name == 'espacecollab')
            {
                $json_course->user_role = null;
            }else{
                $json_course->user_role = ($data->user_role!='participant'?$data->user_role:'');
            }
            $json_course->has_picture = true;
            $json_course->indexation_url = null;
            if (strlen($data->index_picture) > 5 && strlen($data->index_picture_hashname) > 20)
            {
                $json_course->index_picture  = get_resource_centralized_secure_url(
                    '/'.$CFG->centralizedresources_media_types[$data->index_picture_type].'/'.$data->index_picture_cleanname, 
                    $data->index_picture_hashname.$data->index_picture_createdate, $CFG->secure_link_timestamp_video);
            }else{
                if($data->index_domain_id == null){
                    $json_course->has_picture = false;
                    $json_course->indexation_url = $CFG->magistere_domaine."/".$data->course_academy."/local/indexation/index.php?id=".$data->course_id;
                    $json_course->index_picture = $OUTPUT->image_url('my/'.$this->color[(int)substr($data->course_timecreated, -1)], 'theme')->out();
                }else{
                    $json_course->index_picture = $OUTPUT->image_url('offers/' . $data->index_domain_id . '_domains_2x', 'theme')->out();
                }
            }
            
            $json_course->index_video = null;
            if (strlen($data->index_video) > 5 && strlen($data->index_video_hashname) > 20)
            {
                $filterplugin = new filter_mediaplugin(null, array());
                $video_url  = get_resource_centralized_secure_url(
                    '/'.$CFG->centralizedresources_media_types[$data->index_video_type].'/'.$data->index_video_cleanname,
                    $data->index_video_hashname.$data->index_video_createdate, $CFG->secure_link_timestamp_video);
                $video_link = html_writer::link($video_url,$data->index_video_cleanname);
                $json_course->index_video = $filterplugin->filter($video_link);
            }
            
            $json_course->index_objectifs = $data->index_objectifs;
            
            $json_origin = new stdClass();
            $json_origin->name = '';
            if ($data->index_origin != 'academie')
            {
                $json_origin->name = $data->index_origin_name;
                $json_origin->picture = (strlen($data->index_origin)>2?$OUTPUT->image_url('origins/' . strtoupper($data->index_origin), 'theme')->out():'');
            }else if(array_key_exists($data->index_origin_aca_shortname, $CFG->academylist)){
                $json_origin->name = 'Académie '.(in_array(strtolower(substr($CFG->academylist[$data->index_origin_aca_shortname]['name'],0,1)),array('a','e','i','o','u','y'))?"d'":'de ').$CFG->academylist[$data->index_origin_aca_shortname]['name'];
                $json_origin->picture = (strlen($data->index_origin_aca_shortname)>2?$OUTPUT->image_url('origins/' . strtoupper($data->index_origin_aca_shortname), 'theme')->out():'');
            }
            $json_course->index_origin = $json_origin;

            $json_course->is_coming = $data->is_coming;
            $json_course->is_demo = $data->is_demo;

            $json_course->index_startdate = ($data->index_startdate > 1?$data->index_startdate:null);
            $json_course->index_enddate = ($data->index_enddate > 1?$data->index_enddate:null);
            $json_course->index_has_date = ($data->index_startdate || $data->index_enddate?true:false);
            $json_course->index_timepresence = (strlen($data->index_timepresence) > 1?$data->index_timepresence:null);
            $json_course->index_timedistance = (strlen($data->index_timedistance) > 1?$data->index_timedistance:null);
            $json_course->index_rythme = $data->index_rythme;
            $json_course->index_has_rythme = ($data->index_timepresence || $data->index_timedistance || $data->index_rythme?true:false);
            $json_course->index_has_rythme_or_date = ($json_course->index_has_date || $json_course->index_has_rythme?true:false);

            $json_course->index_domain = $data->index_domain;
            
            $json_collection = new stdClass();
            $json_collection->name = $data->index_collection_name;
            $json_collection->picture = (strlen($data->index_collection_name)>2?$OUTPUT->image_url('collections/'.$data->index_collection_name.'_32x32_gris', 'theme')->out():'');
            $json_course->index_collection = $json_collection;
            
            $json_course->index_keywords = array();
            if (strlen($data->index_keywords)>2)
            {
                $keywords = explode('[&]', $data->index_keywords);
                foreach ($keywords AS $keyword)
                {
                    $json_course->index_keywords[] = $keyword;
                }
            }
            if (count($json_course->index_keywords)==0){$json_course->index_keywords = null;}

            $json_course->forums_notifications = null;
            if ($data->course_startdate > time()) {
                $forums_notifications = new stdClass();
                $forums_notifications->count = $data->forums_notifications;
                $forums_notifications->url = $CFG->magistere_domaine."/".$data->course_academy."/mod/forum/index.php?id=".$data->course_id;
        
                $json_course->forums_notifications = $forums_notifications;
            }
            
            $json_course->badges = array();
            if (strlen($data->badges) > 1 && strpos($data->badges, '|') !== false)
            {
                $badges = explode('&', $data->badges);
                foreach ($badges AS $badge)
                {
                    $badge_ex = explode('|', $badge);
                    $json_badge = new stdClass();
                    $json_badge->name = $badge_ex[0];
                    $json_badge->url = $badge_ex[1];
                    $json_course->badges[] = $json_badge;
                }
            }
            if (count($json_course->badges)==0){$json_course->badges = null;}
            
            $json_course->user_progression = $data->user_progression;
            $json_course->user_is_participant = ($data->user_is_participant?true:false);
            
            $json_course->is_admin = has_capability('moodle/site:config', context_system::instance());
            
            $json_global[] = $json_course;
        }
        
        if ($this->mod == self::MOD_MODAL)
        {
            $this->json = json_encode($json_global[0]);
        }else{
            $json_courses = new stdClass();
            $json_courses->courses = $json_global;
            
            $this->data = $json_courses;
            $this->json = json_encode($json_courses);
        }
	}
}
