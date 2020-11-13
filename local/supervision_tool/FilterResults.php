<?php

require_once($CFG->dirroot.'/local/supervision_tool/FilterParams.php');
require_once($CFG->dirroot.'/local/supervision_tool/FilterConfig.php');
require_once($CFG->dirroot.'/local/coursehub/CourseHub.php');

class FilterResults {
    private $params;
    private $config;

    private $resultcount;

    private $formateurid;
    private $participantid;

    private $temptablename;

    private $userid;

    public function __construct(FilterParams $params, FilterConfig $config){
        global $DB, $USER;

        $this->params = $params;
        $this->config = $config;
        $this->resultcount = 0;

        $this->formateurid = $DB->get_record('role', array('shortname' => 'formateur'), 'id')->id;
        $this->participantid  = $DB->get_record('role', array('shortname' => 'participant'), 'id')->id;

        $this->userid = $USER->id;
        $this->temptablename = 'temp_'.$this->userid.uniqid();
    }

    public function get_courses()
    {
        global $DB;

        $params = $this->params->getParameters();

        $datatoprocess = [];

        $hub = CourseHub::instance();

        // if we are on the hub master
        // we make the list for each slaves available
        // or with the selected one

        // identifiant => slave identifiant use to restrict published course
        // dblocal => local database
        // computelocaldata => useful to compute any data from the local db (ie depth of a course)
        // dbhub => hub database
        // onlypublishedcourses => get only the published courses
        if (isset($params[FilterParams::PARAM_NAME_ACA_SELECT]) && $hub->isMaster()) {
            $identifianthub = $hub->getIdentifiant();
            $dbhub = $this->formatIdentifiantToDB($identifianthub);
            if ($params[FilterParams::PARAM_NAME_ACA_SELECT] == FilterParams::ALL_ACA) {

                $onlypublishedcourses = true;
                if($params[FilterParams::PARAM_NAME_PUBLICATION_MODE] == FilterParams::PUBLICATION_NONE){
                    $onlypublishedcourses = false;
                }

                foreach ($hub->getActiveSlaves() as $slave) {
                    $identifiant = $slave->getIdentifiant();

                    if($identifiant == $identifianthub){
                        continue;
                    }

                    $datatoprocess[] = [
                        'identifiant' => $identifiant,
                        'dblocal' => $this->formatIdentifiantToDB($identifiant),
                        'computelocaldata' => false,
                        'dbhub' => $dbhub,
                        'onlypublishedcourses' => $onlypublishedcourses,
                        'limit' => false
                    ];
                }

                // add the master to have all its courses
                $datatoprocess[] = [
                    'identifiant' => $identifianthub,
                    'dblocal' => $dbhub,
                    'computelocaldata' => true,
                    'dbhub' => $dbhub,
                    'onlypublishedcourses' => false,
                    'limit' => false
                ];
            } else {
                $aca = $hub->getSlave($params[FilterParams::PARAM_NAME_ACA_SELECT]);
                $datatoprocess[] = [
                    'identifiant' => $aca->getIdentifiant(),
                    'dblocal' => $this->formatIdentifiantToDB($aca->getIdentifiant()),
                    'computelocaldata' => true,
                    'dbhub' => $dbhub,
                    'onlypublishedcourses' => false,
                    'limit' => true
                ];
            }
        }

        // if we are a "slave", we just add ourselve
        if ($hub->isSlave()) {
            $datatoprocess[] = [
                'identifiant' => $hub->getIdentifiant(),
                'dblocal' => $this->formatIdentifiantToDB($hub->getIdentifiant()),
                'computelocaldata' => true,
                'dbhub' => $this->formatIdentifiantToDB($hub->getMasterIdentifiant()),
                'onlypublishedcourses' => false,
                'limit' => true
            ];
        }

        $canviewoncourse = (!is_siteadmin()
            && has_capability('local/supervision_tool:viewowncourses', context_system::instance())
            && !has_capability('local/supervision_tool:viewallcourses', context_system::instance())
        );

        // if we have only one data to process, use the buildRequestFor function
        // otherwise we have to concatenate all sub queries, then make the order and limit clauses
        if (count($datatoprocess) == 1) {
            $rawdata = $this->buildRequestFor($datatoprocess[0]['identifiant'],
                $datatoprocess[0]['dblocal'],
                $datatoprocess[0]['dbhub'],
                $canviewoncourse,
                $datatoprocess[0]['computelocaldata'],
                $datatoprocess[0]['onlypublishedcourses'],
                $datatoprocess[0]['limit']
            );
        } else {
            $rawdata = array();
            $sqls = [];
            $params = [];

            // little hack of myself to disable the order by in the sub queries
            $sortorder = $this->params->sortOrder;
            $this->params->sortOrder = 'undefined';

            foreach($datatoprocess as $data){
                list($s, $p) = $this->generateSqlAndParams($data['identifiant'],
                    $data['dblocal'],
                    $data['dbhub'],
                    $canviewoncourse,
                    $data['computelocaldata'],
                    $data['onlypublishedcourses'],
                    $data['limit'],
                    false
                );

                $sqls[] = $s;
                $params += $p;
            }

            // restore the actuel order
            $this->params->sortOrder = $sortorder;

            $mainsql = 'SELECT SQL_CALC_FOUND_ROWS * FROM ('.implode("\nUNION\n", $sqls).') a';
            if($this->params->sortOrder != 'undefined'){
                $mainsql .= ' ORDER BY '.$this->params->sortOrder;
            }
            if($this->params->startIndex !== null){
                $mainsql .= ' LIMIT ' . $this->params->startIndex . ',' . $this->params->pageSize;
            }

            if(count($datatoprocess) > 0){
                $rawdata = $DB->get_records_sql($mainsql, $params);
            }
        }

        $this->resultcount = $DB->get_record_sql('SELECT FOUND_ROWS() AS resultcount')->resultcount;

//        $data = $this->processRawData($rawdata);

        return $rawdata;
    }

    private function formatIdentifiantToDB($identifiant)
    {
        global $CFG;

        return $CFG->db_prefix.str_replace(['ac-', '-'], ['', '_'], $identifiant);
    }

    /**
     * This function exectue a request on a known hub database and a known local database.
     * Apply each filters the user may have selected.
     *
     * @param $identifiant identifiant of the local (slave)
     * @param $dblocal name of the local database
     * @param $dbhub name of the hub database
     * @param $viewowncourse is the user can only see his own courses
     * @param $computelocaldata compute local data (ie depth, lastaccess...)
     * @param $onlypublishedcourses return only the published course
     * @param $limit if true use the startindex and pagesize otherwise return all the rows
     * @return array
     * @throws coding_exception
     * @throws ddl_exception
     * @throws ddl_table_missing_exception
     * @throws dml_exception
     */
    public function buildRequestFor($identifiant, $dblocal, $dbhub, $viewowncourse, $computelocaldata, $onlypublishedcourses, $limit)
    {
        global $CFG, $DB;

        list($sql, $params) = $this->generateSqlAndParams($identifiant, $dblocal, $dbhub, $viewowncourse, $computelocaldata, $onlypublishedcourses, $limit);

        $rawdata = $DB->get_records_sql($sql, $params);

        return $rawdata;
    }

    public function generateSqlAndParams($identifiant, $dblocal, $dbhub, $viewowncourse, $computelocaldata, $onlypublishedcourses, $limit, $sqlcalc = true)
    {
        global $CFG;

        // used to make each sql params unique
        static $reqidx = 0;

        $dblocal .= '.';
        $dbhub .= '.';

        $selectclauses = [
            ($sqlcalc ? 'SQL_CALC_FOUND_ROWS ':'').'CONCAT(c.id, "-", IFNULL(hc.id, 0)) id',
            'c.id localid',
            'IFNULL(hc.id, 0) hubid',
            'c.fullname',
            'c.category',
            'cc.name AS categoryname',
            'c.format',
            'hc.publish publicationtype',
            'hc.isalocalsession',
            'hc.timemodified publicationdate',
            'hc.timecreated publicationdateorigine',
            'c.startdate startdate',
            'c.enddate enddate',
            'bcm.flexcourseid',
            'bcm.startdate migrationdate',
            'bcm.originalformat migrationoriginalformat',
            'bcm.status migrationstatus',
            'bcm.validated',
            'bcm2.stdcourseid stdcourseid',
            'bcm2.convertedformat convertedformat',
            'bcm2.enddate frommigrationdate',
            'bcm2.status frommigrationstatus',
            'bcm2.validated frommigrationvalidated',
            'com.comment `comment`',
            'hc.timecoursemodified timemodifiedcoursehub',
            'c.timemodified timemodifiedcourse',
            'CONCAT(hc.firstname, " ", hc.lastname) publisher',
            'hc.email publisheremail',
            'localuser.id publisherid',
            'hc.url originurl',
            'hc.name originaca',
            'hc.identifiant originidentifiant',
            'i.updatedate timemodifiedlocalindexation',
            'hci.updatedate timemodifiedhubindexation',
            'hci.contact responsible',
            'CONCAT(hci.year, "_", clic.code, "_", hci.title,"_", hci.version) idnumber',
            'CONCAT("' . $identifiant . '") aca_name'
        ];

        $fromclauses = [
            $dblocal . '{course} c',
            'INNER JOIN ' . $dblocal . '{course_categories} cc ON (cc.id=c.category)',
            'INNER JOIN ' . $dblocal . '{context} co ON (co.contextlevel = 50 AND co.instanceid=c.id)',
            'LEFT JOIN ' . $dblocal . '{local_indexation} i ON i.courseid=c.id',
        ];

        if ($onlypublishedcourses) {
            $fromclauses[] = 'INNER JOIN ' ;
        } else {
            $fromclauses[] = 'LEFT JOIN ' ;
        }

        $fromclauses[] = '(
            SELECT
            hc.id,
            hc.courseid,
            hc.slaveid,
            hc.publish,
            hc.isalocalsession,
            hc.timemodified,
            hc.timecreated,
            hc.timecoursemodified,
            hc.firstname,
            hc.lastname,
            hc.email,
            hc.deleted,
            hslave.url,
            hslave.name,
            hslave.identifiant
            FROM ' . $dbhub . '{local_coursehub_course} hc
            LEFT JOIN ' . $dbhub . '{local_coursehub_slave} hslave ON hslave.id=hc.slaveid
            WHERE hslave.identifiant=:aca'.$reqidx.'
            ) hc ON hc.courseid = c.id';

        $fromclauses[] = 'LEFT JOIN ' . $dbhub . '{local_coursehub_index} hci ON hci.publishid=hc.id';
        $fromclauses[] = 'LEFT JOIN ' . $dblocal . '{local_supervision_tool_comm} com ON com.courseid=c.id';
        $fromclauses[] = 'LEFT JOIN ' . $dblocal . '{block_course_migration} bcm ON bcm.stdcourseid=c.id';
        $fromclauses[] = 'LEFT JOIN ' . $dblocal . '{block_course_migration} bcm2 ON bcm2.flexcourseid=c.id';
        $fromclauses[] = 'LEFT JOIN ' . $dblocal . '{course} flexpagecourse2 ON flexpagecourse2.id=bcm2.flexcourseid';
        $fromclauses[] = 'LEFT JOIN ' . $dblocal . '{course} flexpagecourse ON flexpagecourse.id=bcm.flexcourseid';
        $fromclauses[] = 'LEFT JOIN ' . $dblocal . '{user} localuser ON localuser.email=hc.email';
        $fromclauses[] = 'LEFT JOIN ' . $CFG->centralized_dbname . '.local_indexation_codes clic ON clic.id=hci.codeorigineid';

        // formateurs
        $selectclauses[] = 'GROUP_CONCAT(formateurlist.userid) as formateurs_id, GROUP_CONCAT(formateurlist.name) AS formateurs';
        $fromclauses[] = '
LEFT JOIN (
        SELECT co.instanceid as courseid, ra.userid as userid, CONCAT(u.firstname, " ", u.lastname) as name
        FROM ' . $dblocal . '{role_assignments} ra
        INNER JOIN ' . $dblocal . '{context} co ON co.id=ra.contextid
        INNER JOIN '. $dblocal . '{user} u ON ra.userid = u.id
        WHERE co.contextlevel=50 AND (ra.roleid=' . $this->formateurid .') 
) formateurlist ON formateurlist.courseid=c.id
';
        
        
        if ($computelocaldata) {
            $selectclauses[] = 'sectioncount.pagecount pagecount';
            $selectclauses[] = 'IFNULL(stats.formateurcount, 0) formateurcount';
            $selectclauses[] = 'IFNULL(stats.participantcount, 0) participantcount';
            $selectclauses[] = 'lastaccess.lastaccess lastaccess';
            $selectclauses[] = 'depths.depth';

            $fromclauses[] = 'LEFT JOIN ' . $dblocal . '{' . $this->temptablename . '} depths ON depths.courseid=c.id';

            $fromclauses[] = 'LEFT JOIN (
  SELECT course courseid, COUNT(*) pagecount
  FROM ' . $dblocal . '{course_sections}
  GROUP BY course
) sectioncount ON sectioncount.courseid=c.id';

            $fromclauses[] = 'LEFT JOIN (
	SELECT co.instanceid courseid, COUNT(IF(ra.roleid=' . $this->formateurid . ', 1, NULL)) formateurcount, COUNT(IF(ra.roleid=' . $this->participantid . ', 1, NULL)) participantcount
	FROM ' . $dblocal . '{role_assignments} ra
	INNER JOIN ' . $dblocal . '{context} co ON co.id=ra.contextid
	WHERE co.contextlevel=50 AND (ra.roleid=' . $this->formateurid . ' OR ra.roleid=' . $this->participantid . ')
	GROUP BY co.instanceid
) stats ON stats.courseid=c.id';

            $fromclauses[] = 'LEFT JOIN (
                SELECT courseid, MAX(timeaccess) lastaccess 
	FROM ' . $dblocal . '{user_lastaccess} 
	WHERE userid > 2 GROUP BY courseid
) lastaccess ON lastaccess.courseid=c.id';

        } else {
            $selectclauses[] = 'NULL pagecount';
            $selectclauses[] = 'NULL formateurcount';
            $selectclauses[] = 'NULL participantcount';
            $selectclauses[] = 'NULL lastaccess';
            $selectclauses[] = 'NULL depth';
        }

        if ($viewowncourse) {
            $fromclauses[] = 'INNER JOIN (
                SELECT cc.id, cc.name, cc.parent, cc.sortorder
	FROM ' . $dblocal . '{role_assignments} ra
	INNER JOIN ' . $dblocal . '{context} co ON co.id=ra.contextid
	INNER JOIN ' . $dblocal . '{course_categories} cc ON cc.id=co.instanceid
	WHERE ra.userid=' . $this->userid . '
            AND ra.roleid IN (SELECT id FROM ' . $dblocal . '{role} WHERE shortname = "formateur" OR shortname = "gestionnaire")
	AND co.contextlevel = 40
UNION
	SELECT cc.id, cc.name, cc.parent, cc.sortorder
	FROM ' . $dblocal . '{role_assignments} ra
	INNER JOIN ' . $dblocal . '{context} co ON co.id=ra.contextid
	INNER JOIN ' . $dblocal . '{course} c ON c.id=co.instanceid
	INNER JOIN ' . $dblocal . '{course_categories} cc ON cc.id=c.category
	WHERE ra.userid=' . $this->userid . '
            AND ra.roleid IN (SELECT id FROM ' . $dblocal . '{role} WHERE shortname = "formateur" OR shortname = "gestionnaire")
	AND co.contextlevel = 50
            ) categories ON categories.id=flexpagecourse.category';
        }

        $where = array();

        $params = $this->params->getParameters();
        $params['aca'] = $identifiant;

        // STARTDATE
        $hasstartdatestart = isset($params[FilterParams::PARAM_NAME_STARTDATE_START]);
        $hasstartdateend = isset($params[FilterParams::PARAM_NAME_STARTDATE_END]);

        if ($hasstartdatestart) {
            $where[] = 'c.startdate >= :' . FilterParams::PARAM_NAME_STARTDATE_START.$reqidx;
        }

        if ($hasstartdateend) {
            $where[] = 'c.startdate <= :' . FilterParams::PARAM_NAME_STARTDATE_END.$reqidx;
        }

        // ENDDATE
        $hasenddatestart = isset($params[FilterParams::PARAM_NAME_ENDDATE_START]);
        $hasenddateend = isset($params[FilterParams::PARAM_NAME_ENDDATE_END]);

        if ($hasenddatestart) {
            $where[] = 'c.enddate >= :' . FilterParams::PARAM_NAME_ENDDATE_START.$reqidx;
        }

        if ($hasenddateend) {
            $where[] = 'c.enddate <= :' . FilterParams::PARAM_NAME_ENDDATE_END.$reqidx;
        }


        if ($computelocaldata) {
            // LAST ACCESS
            $haslastaccessstart = isset($params[FilterParams::PARAM_NAME_LASTACCESS_START]);
            $haslastaccessend = isset($params[FilterParams::PARAM_NAME_LASTACCESS_END]);
            if ($haslastaccessstart) {
                $where[] = 'lastaccess.lastaccess >= :' . FilterParams::PARAM_NAME_LASTACCESS_START.$reqidx;
            }

            if ($haslastaccessend) {
                $where[] = 'lastaccess.lastaccess <= :' . FilterParams::PARAM_NAME_LASTACCESS_END.$reqidx;
            }

            // DEPTH
            if (isset($params[FilterParams::PARAM_NAME_DEPTH])) {
                $d = $params[FilterParams::PARAM_NAME_DEPTH];
                if ($d == FilterParams::DEPTH_FIVE_OR_MORE) {
                    $where[] = 'depths.depth >= 5';
                } else if ($d != FilterParams::DEPTH_ALL) {
                    $where[] = 'depths.depth = :' . FilterParams::PARAM_NAME_DEPTH.$reqidx;
                }
            }
        }

        // PUBLICATION MODE
        $pb = FilterParams::PUBLICATION_NONE;
        if (isset($params[FilterParams::PARAM_NAME_PUBLICATION_MODE])) {
            $pb = $params[FilterParams::PARAM_NAME_PUBLICATION_MODE];
        }

        $params['identifiant'] = $identifiant;
        if ($pb == FilterParams::PUBLICATION_ALL) {
            $where[] = 'hc.identifiant=:identifiant'.$reqidx.' AND hc.deleted=0';
        } else if($pb == FilterParams::PUBLICATION_NONE){
            // Was previously designed as NONE to prevent showing any already published courses, it not anymore
            // Now it will show published and not published alike
            // $where[] = '(hc.id IS NULL OR hc.identifiant!=:identifiant'.$reqidx.')';
        } else {
            $enrollable = null;
            if ($pb == FilterParams::PUBLICATION_COURSE_OFFER) {
                $enrollable = FilterParams::PUBLICATION_COURSE_OFFER;
            } else if ($pb == FilterParams::PUBLICATION_FORMATION_OFFER) {
                $enrollable = FilterParams::PUBLICATION_FORMATION_OFFER.' AND hc.isalocalsession = 0';
            } else if ($pb == FilterParams::PUBLICATION_FORMATION_LOCAL_OFFER) {
                $enrollable = FilterParams::PUBLICATION_FORMATION_OFFER.' AND hc.isalocalsession = 1';
            }

            $where[] = 'hc.identifiant=:identifiant'.$reqidx.' AND hc.deleted=0 AND hc.publish=' . $enrollable;
        }

        // TYPE
        if (isset($params[FilterParams::PARAM_NAME_TYPE])) {
            $type = $params[FilterParams::PARAM_NAME_TYPE];
            if ($type == FilterParams::TYPE_FLEXPAGE) {
                $where[] = 'c.format="flexpage"';
            }

            if ($type == FilterParams::TYPE_MODULAR) {
                $where[] = 'c.format="modular"';
            }

            if ($type == FilterParams::TYPE_TOPICS) {
                $where[] = 'c.format="topics"';
            }

            if ($type == FilterParams::TYPE_FAIL) {
                $where[] = 'bcm2.status=' . BaseConvertor::CONV_FAILED;
            }

            if ($type == FilterParams::TYPE_VALIDATION) {
                $where[] = 'flexpagecourse.id IS NOT NULL';
                $where[] = 'bcm.status=' . BaseConvertor::CONV_FINISHED;
                $where[] = 'c.category != (SELECT id FROM {course_categories} WHERE name = "Corbeille" AND parent = 0)';
            }
        }

        // QUERY
        if (isset($params[FilterParams::PARAM_NAME_QUERY])) {
            $query = $params[FilterParams::PARAM_NAME_QUERY];
            $query = explode(' ', $query);

            $w = array();
            $com = array();
            $i = 0;
            foreach ($query as $q) {
                $w[] = 'c.fullname LIKE :q' . $i . $reqidx;
                $params['q' . $i] = '%' . $q . '%';

                $com[] = 'com.comment LIKE :q' . ($i + 1) . $reqidx;
                $params['q' . ($i + 1)] = '%' . $q . '%';
                $i += 2;
            }

            $where[] = '(' . implode(' AND ', $w) . ' OR (com.comment IS NOT NULL AND ' . implode(' AND ', $com) . '))';

            unset($params[FilterParams::PARAM_NAME_QUERY]);
        }

        // CATEGORY
        if (isset($params[FilterParams::PARAM_NAME_CATEGORY]) && $params[FilterParams::PARAM_NAME_CATEGORY] != FilterParams::ALL_CAT) {
            $where[] = 'co.path LIKE CONCAT((SELECT path FROM {context} WHERE contextlevel=40 AND instanceid=:' . FilterParams::PARAM_NAME_CATEGORY . $reqidx . '), "/%")';
        }

        if ($computelocaldata) {
            // the table use to compute the depth must be on the local database
            $this->createTemporaryDepthTable($dblocal);
            $depths = $this->compute_depth();
            if (count($depths) > 0) {
                $this->insertIntoTemporaryDethTable($dblocal, $depths);
            }
        }

        // TIMEMODIFIED
        if (isset($params[FilterParams::PARAM_NAME_CHECK_COURSE_VERSION])) {
            $where[] = '(
                (hc.timecoursemodified IS NOT NULL AND hc.timecoursemodified <> c.timemodified)
                OR
                (hci.updatedate IS NOT NULL AND hci.updatedate <> i.updatedate)
            )';
        }

        $where[] = 'c.id > 1';

        $sql = 'SELECT ' . implode(",\n", $selectclauses) . "\n FROM " . implode("\n", $fromclauses);
        $sql .= ' WHERE ' . implode(' AND ', $where) . ' GROUP BY c.id ';


        if ($this->params->sortOrder != 'undefined') {
            $this->parseSortOrder($this->params->sortOrder);
            $sql .= ' ORDER BY ' . $this->params->sortOrder;
        }

        if ($limit && $this->params->startIndex !== null && $this->params->pageSize !== null) {
            $sql .= ' LIMIT ' . $this->params->startIndex . ',' . $this->params->pageSize;
        }

        $paramswithidx = [];
        foreach($params as $key => $value){
            $paramswithidx[$key.$reqidx] = $value;
        }

        $reqidx++;

        return [$sql, $paramswithidx];
    }

    public function parseSortOrder($sortorder)
    {

    }

    public function createTemporaryDepthTable($dbname)
    {
        global $DB;

        $sql = 'CREATE TEMPORARY TABLE '.$dbname.'{'.$this->temptablename.'}(
    courseid INT PRIMARY KEY NOT NULL,
    depth INT NOT NULL
)';
        $DB->execute($sql);
    }

    public function insertIntoTemporaryDethTable($dbname, $data)
    {
        global $DB;

        $sql = 'INSERT INTO '.$dbname.'{'.$this->temptablename.'}(courseid, depth) VALUES ';

        $values = [];
        $params = [];
        foreach($data as $d){
            $values[] = '(?, ?)';
            $params[] = $d->courseid;
            $params[] = $d->depth;
        }

        $sql .= implode(',', $values);

        $DB->execute($sql, $params);
    }

    public function processRawData($rawdata)
    {
        $data = [];

        foreach($rawdata as $id => $data)
        {

        }

        return $rawdata;
    }

    public function getResultCount(){
        return $this->resultcount;
    }

    function compute_depth()
    {
        global $DB;

        $flexpages = array();
        $root = array();

        $records = $DB->get_recordset_sql(
'SELECT ffp.id, ffp.courseid, ffp.parentid
FROM {course} c 
INNER JOIN {format_flexpage_page} ffp ON ffp.courseid=c.id
WHERE c.format="flexpage"');

        // first retrieve all data
        foreach($records as $record){
            if(!isset($flexpages[$record->courseid])){
                $flexpages[$record->courseid] = array();
            }

            if(isset($flexpages[$record->courseid][$record->id])){
                continue;
            }

            $ffp = new stdclass();
            $ffp->id = $record->id;
            $ffp->parentid = $record->parentid;
            $ffp->children = array();

            $flexpages[$record->courseid][$record->id] = $ffp;

            if($ffp->parentid == 0){
                if(!isset($root[$record->courseid])){
                    $root[$record->courseid] = array();
                }

                $root[$record->courseid][] = $record->id;
            }
        }
        $records->close();

        // then build the flexpage tree
        foreach($flexpages as $courseid => $pages)
        {
            foreach($pages as $page)
            {
                if($page->parentid){
                    $flexpages[$courseid][$page->parentid]->children[] = $flexpages[$courseid][$page->id];
                }
            }
        }

        // and finally compute the max depth for each courses
        $depths = array();
        foreach($root as $courseid => $pages)
        {
            $depth = 0;
            foreach($pages as $page){
                $d = $this->depth($flexpages[$courseid][$page]);
                $flexpages[$courseid][$page]->depth = $d;

                $depth = max($d, $depth);
            }

            // format like an objet to insert with insert_records
            $data = new stdClass();
            $data->courseid = $courseid;
            $data->depth = $depth;

            $depths[$courseid] = $data;
        }

        unset($flexpages);
        return $depths;
    }

    private function depth($page)
    {
        if(empty($page->children)){
            return 1;
        }


        $depth = 0;
        foreach($page->children as $child){
            $depth = max($this->depth($child), $depth);
        }

        return $depth+1;
    }
}


































