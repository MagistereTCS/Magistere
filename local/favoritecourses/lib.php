<?php

require_once($CFG->dirroot.'/local/magisterelib/databaseConnection.php');

class favoriteCourses
{
    private $rawdata = array();
    private $data = array();

    const CATPARCOURSFORMATION = "Parcours de formation";

    function __construct()
    {
        $this->get_courses_list();
        $this->load_external_academy_data();
        $this->process_logo();
        $this->prepare_data();
        $this->get_favorite_courses_list_by_timecreated();
    }

    function get_courses_list()
    {
        global $DB, $USER, $CFG;

        $courses_list = $DB->get_records_sql("SELECT CONCAT(c.id,000000,IF(r.id IS NULL,0,r.id)) as unid, c.id, c.fullname, c.shortname, c.startdate, GROUP_CONCAT(r.shortname) AS rolename, cx.path, lic.shortname AS collection, cc.name AS categorie_name, c.visible, fc.timecreated 
FROM {user} as u 
INNER JOIN {local_favoritecourses} as fc ON fc.userid=u.id			
INNER JOIN {course} as c ON c.id = fc.courseid
INNER JOIN {context} as cx ON cx.instanceid = c.id
LEFT JOIN {role_assignments} as ra ON ra.contextid = cx.id AND ra.userid = u.id
LEFT JOIN {role} as r ON ra.roleid = r.id
LEFT JOIN {context} as cx2 ON cx2.id = SUBSTRING_INDEX(SUBSTRING_INDEX(cx.path, '/', 3), '/', -1)
LEFT JOIN {course_categories} as cc ON cc.id = cx2.instanceid
LEFT JOIN {local_indexation} as im ON im.courseid = c.id
LEFT JOIN ".$CFG->centralized_dbname.".local_indexation_collections lic ON lic.id=im.collectionid
WHERE cx.contextlevel = 50
AND cx2.contextlevel = 40
AND u.id = ?
GROUP BY fc.id", array($USER->id));

        $this->rawdata = isset($courses_list) ? $courses_list : null;
    }

    function load_external_academy_data()
    {
        global $CFG, $USER;

        if (!$USER->id)
        {
            return;
        }

        $distant_site = get_magistere_academy_config();
        $rawdata = array();

        $i = 1;
        foreach ($distant_site as $academy_name => $data)
        {
            unset($acaDB);
            if($academy_name == 'frontal' || $academy_name == 'hub' || $academy_name == 'cndp' || $academy_name == $CFG->academie_name){continue;}
            if (($acaDB = databaseConnection::instance()->get($academy_name)) === false){error_log('course_list_lib.php/load_hub_data/'.$academy_name.'/Database_connection_failed'); continue;}

            $url = $CFG->magistere_domaine.$data['accessdir'];

            $courses_list_aca = $acaDB->get_records_sql(
                "SELECT CONCAT('".$i."',c.id, fc.id,000000) as unid, c.id, c.fullname, c.shortname, c.startdate, GROUP_CONCAT(r.shortname) AS rolename, cx.path, lic.shortname AS collection, cc.name AS categorie_name, c.visible, fc.timecreated
                FROM {user} as u 
				INNER JOIN mdl_local_favoritecourses as fc ON fc.userid=u.id
				INNER JOIN mdl_course as c ON c.id = fc.courseid
				INNER JOIN mdl_context as cx ON cx.instanceid = c.id
				LEFT JOIN mdl_role_assignments as ra ON ra.contextid = cx.id
				LEFT JOIN mdl_role as r ON ra.roleid = r.id
				LEFT JOIN mdl_context as cx2 ON cx2.id = SUBSTRING_INDEX(SUBSTRING_INDEX(cx.path, '/', 3), '/', -1)
				LEFT JOIN mdl_course_categories as cc ON cc.id = cx2.instanceid
				LEFT JOIN mdl_local_indexation as im ON im.courseid = c.id
				LEFT JOIN ".$CFG->centralized_dbname.".local_indexation_collections lic ON lic.id=im.collectionid
				WHERE cx.contextlevel = 50
				 AND cx2.contextlevel = 40
				 AND (fc.username = '".$USER->username."'
				 OR fc.email = '".$USER->email."')
				GROUP BY c.id");

            foreach ($courses_list_aca as $key=>$value)
            {
                $courses_list_aca[$key]->origine = array('academie' => $academy_name, 'url'=>$url.'/course/view.php?id='.$value->id);
            }

            if ($courses_list_aca !== false)
            {
                $rawdata = $rawdata + $courses_list_aca;
            }
        }

        $this->rawdata = $this->rawdata + $rawdata;
    }

    function process_logo()
    {
        foreach ( $this->rawdata as $course )
        {
            $label = '';
            $course->collection = strtolower($course->collection);
            switch ($course->collection) {
                case "action":
                    $label = "Action";
                    break;
                case "analyse":
                    $label = "Analyse";
                    break;
                case "autoformation":
                    $label = "Autoformation";
                    break;
                case 'decouverte':
                    $label = "Découverte";
                    break;
                case "reseau":
                    $label = "Réseau";
                    break;
                case "simulation":
                    $label = "Simulation";
                    break;
                case "qualification":
                    $label = "Qualification";
                    break;
                case "volet_distant":
                    $label = "Volet Distant";
                    break;
                case "espacecollab":
                    $label = "Espace Collaboratif";
                    break;
                default:
                    $course->collection = 'empty';
                    $label = "empty";
            }
            $course->logo_label = $label;
        }
    }

    function prepare_data()
    {
        if (is_array($this->rawdata))
        {
            $this->data = array();

            foreach($this->rawdata as $course)
            {
                $rolename = explode(',', $course->rolename);

                $course->rolename = array();

                if(in_array('formateur', $rolename)){
                    $course->rolename[] = 'concepteur';
                }else if(in_array('tuteur', $rolename)){
                    $course->rolename[] = 'concepteur';
                }else if(in_array('participant', $rolename)){
                    $course->rolename[] = 'participant';
                }

                $this->data[$course->unid] = $course;
            }
        }
    }

    ///********* Favorite functions ***********///

    function sort_timecreated_desc($a, $b){
        return $a->timecreated < $b->timecreated;
    }

    function get_favorite_courses_list_by_timecreated()
    {
        if( isset($this->data) )
        {
            usort($this->data, array($this, 'sort_timecreated_desc'));
            return $this->data;
        }
        return array();
    }
}

function add_favorite_course($courseid, $user, $aca = ''){
    global $DB;

    if($courseid != null && $user != null){
        $favorite = new stdClass();
        $favorite->courseid = $courseid;
        $favorite->userid = $user->id;
        $favorite->email = $user->email;
        $favorite->username = $user->username;
        $favorite->timecreated = time();

        if(empty($aca)){
            // local aca
            return $DB->insert_record('local_favoritecourses', $favorite) > 0;
        }else{
            if (($acaDB = databaseConnection::instance()->get($aca)) === false){
                error_log('course_list_lib.php/load_hub_data/'.$aca.'/Database_connection_failed');
                return false;
            }

			if(($acaUser = $acaDB->get_record('user', array('username' => $user->username))) === false){
                error_log('no username found for user ' . $user->username . ' on ' . $aca);
                return false;
            }

            $favorite->userid = $acaUser->id;

            return $acaDB->insert_record('local_favoritecourses', $favorite) > 0;
        }


    }
    return false;
}

function delete_favorite_course($id, $aca=''){
    global $DB;
    if($id != null){
        if(empty($aca)){
            // local aca
            $DB->delete_records('local_favoritecourses', array('id' => $id));
        }else{
            if (($acaDB = databaseConnection::instance()->get($aca)) === false){
                error_log('course_list_lib.php/load_hub_data/'.$aca.'/Database_connection_failed');
                return false;
            }

            $acaDB->delete_records('local_favoritecourses', array('id' => $id));
        }

        return true;
    }
    return false;
}
