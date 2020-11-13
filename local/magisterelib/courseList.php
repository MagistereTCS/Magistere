<?php

require_once($CFG->dirroot.'/local/magisterelib/databaseConnection.php');

class Course_list
{
    private $data = array();
    private $rawdata = array();
    private $accepted_role = array("participant","tuteur","formateur");

    const ESPACECOLLABCAT = "espacecollabcat";
    const ESPACECOLLABARCHIVE = "espacecollabarchive";

    function __construct()
    {
        global $USER, $CFG;

        $this->get_courses_list();

        $this->load_external_academy_data();

        $this->process_ariane();

        $this->process_logo();

        $this->sort_data();

        $this->remove_participant_not_visible_courses();

        $this->remove_espacecollab_not_visible_courses();

        $this->sort_by_coursename();

        $this->hide_deploy_section();

    }

    function load_external_academy_data()
    {
        global $CFG, $USER;

        if (!$USER->id)
        {
            return;
        }

       /* if(mmcached_get('magistere_courselist_'.$CFG->academie_name.'_'.$USER->username.'_'.sesskey()) !== false)
        {
            $this->rawdata = $this->rawdata + mmcached_get('magistere_courselist_'.$CFG->academie_name.'_'.$USER->username.'_'.sesskey());
            return;
        }*/

        $distant_site = get_magistere_academy_config();
        $rawdata = array();

        $i = 1;
        foreach ($distant_site as $academy_name => $data)
        {
            unset($acaDB);
            if($academy_name == 'frontal' || $academy_name == 'hub' || $academy_name == 'cndp'){continue;}
            if (($acaDB = databaseConnection::instance()->get($academy_name)) === false){error_log('course_list_lib.php/load_hub_data/'.$academy_name.'/Database_connection_failed'); continue;}

            $url = $CFG->magistere_domaine.$data['accessdir'];

            $courses_list_aca = $acaDB->get_records_sql(
                "SELECT CONCAT('".$i."',c.id,r.id,000000) as unid, c.id, c.fullname, c.shortname, c.startdate, r.shortname AS rolename, cx.path, clic.shortname collection, cc.name AS categorie_name, c.visible, IF(fav.id IS NOT NULL, 1,0) AS isfav
				FROM {course} as c
				INNER JOIN {context} as cx ON cx.instanceid = c.id
				INNER JOIN {role_assignments} as ra ON ra.contextid = cx.id
				INNER JOIN {role} as r ON ra.roleid = r.id
				INNER JOIN {user} as u ON ra.userid = u.id
				LEFT JOIN {context} as cx2 ON cx2.id = SUBSTRING_INDEX(SUBSTRING_INDEX(cx.path, '/', 3), '/', -1)
				LEFT JOIN {course_categories} as cc ON cc.id = cx2.instanceid
				LEFT JOIN {local_indexation} as li ON li.courseid = c.id
			    LEFT JOIN ".$CFG->centralized_dbname.".local_indexation_collections clic ON clic.id=li.collectionid
				LEFT JOIN {local_favoritecourses} as fav ON fav.courseid = c.id AND fav.userid=u.id
				WHERE cx.contextlevel = 50
				 AND cx2.contextlevel = 40
				 AND r.shortname in ('formateur','tuteur','participant')
				 AND (u.username = '".$USER->username."'
				 OR u.email = '".$USER->email."')
				GROUP BY c.id, r.id
				ORDER BY c.fullname ASC");


            foreach ($courses_list_aca as $key=>$value)
            {
                $courses_list_aca[$key]->origine = array('academie' => $academy_name, 'url'=>$url.'/course/view.php?id='.$value->id);
            }

            if ($courses_list_aca !== false)
            {
                $rawdata = $rawdata + $courses_list_aca;
            }
            $i++;
        }

        /*mmcached_add('magistere_courselist_'.$CFG->academie_name.'_'.$USER->username.'_'.sesskey(), $rawdata);*/

        $this->rawdata = $this->rawdata + $rawdata;
    }


    function get_courses_list()
    {
        global $DB, $USER, $CFG;
        // TODO : attention à la concaténation sans séparateur, cela peut induire des collisions. Par exemple : les couples (1, 12) et (11, 2) donnent les mêmes unid
        $courses_list = $DB->get_records_sql("SELECT CONCAT(c.id,r.id,000000) as unid, c.id, c.fullname, c.shortname, c.startdate, r.shortname AS rolename, cx.path, clic.shortname collection, cc.name AS categorie_name, c.visible, IF(fav.id IS NOT NULL, 1,0) AS isfav
			FROM {course} as c
			INNER JOIN {context} as cx ON cx.instanceid = c.id
			INNER JOIN {role_assignments} as ra ON ra.contextid = cx.id
			INNER JOIN {role} as r ON ra.roleid = r.id
			INNER JOIN {user} as u ON ra.userid = u.id
			LEFT JOIN {context} as cx2 ON cx2.id = SUBSTRING_INDEX(SUBSTRING_INDEX(cx.path, '/', 3), '/', -1)
			LEFT JOIN {course_categories} as cc ON cc.id = cx2.instanceid
			LEFT JOIN {local_indexation} as li ON li.courseid = c.id
			LEFT JOIN ".$CFG->centralized_dbname.".local_indexation_collections clic ON clic.id=li.collectionid
			LEFT JOIN {local_favoritecourses} as fav ON fav.courseid = c.id AND fav.userid=u.id
			WHERE cx.contextlevel = 50
			AND cx2.contextlevel = 40
			AND r.shortname in ('formateur','tuteur','participant')
			AND u.id = ?
			GROUP BY c.id, r.id
			ORDER BY c.fullname ASC", array($USER->id));
        $this->rawdata = isset($courses_list) ? $courses_list : null;
    }

    function process_ariane()
    {
        global $DB;
        foreach ( $this->rawdata as $course )
        {
            if($course->path==null){continue;}
            $path_ex = explode('/', $course->path);
            $sql_in = '';
            for ( $i = 3; $i<count($path_ex)-1; $i++ )
            {
                $sql_in .= $sql_in!=''?',':'';
                $sql_in .= $path_ex[$i];
            }

            $course->ariane_array = array();

            if ($sql_in != '' && strlen($sql_in) > 0)
            {
                $categorie_list = $DB->get_records_sql("SELECT cc.id, cc.name FROM mdl_course_categories as cc
					INNER JOIN mdl_context as cx ON cx.instanceid = cc.id
					WHERE cx.id IN (".$sql_in.")");

                $ariane = '';
                foreach ( $categorie_list as $categorie)
                {
                    $ariane .= $ariane!=''?' > ':'';
                    $ariane .= $categorie->name;
                    $course->ariane_array[$categorie->id] = $categorie->name;
                }

                $course->ariane = $ariane;
            }
            else
            {
                $course->ariane = '';
            }
        }
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

    /**
     *
     */
    function sort_data()
    {
        $this->data = array();

        $courses = array(
            'participant' => array(),
            'formateur'   => array()
        );

        $currentTime = time();

        foreach ($this->rawdata as $course) {
            $rolename = $course->rolename;

            if ($course->rolename == 'tuteur') {
                $rolename = 'formateur';
            }

            if($rolename == 'participant' && $course->startdate != 0 && $course->startdate > $currentTime && $course->collection != 'espacecollab'){
                continue;
            }

            if (array_search($course->id, $courses[$rolename]) === false)
            {
                if($course->collection == 'espacecollab')
                {
                    //if(isset($course->origine)){
                    //if(!($this->verif_course_collab($course->origine['academie'], $course->id))){
                    //continue;
                    //}
                    //}
                    if($course->categorie_name == 'Archive'){
                        $this->data[self::ESPACECOLLABARCHIVE][] = $course;
                    }else if($course->categorie_name != 'Corbeille'){
                        $this->data[self::ESPACECOLLABCAT][$course->id] = $course;
                    }

                }else{
                    $this->data[$rolename][$course->categorie_name][] = $course;
                }

                $courses[$rolename][] = $course->id;

            }
        }
    }


    // Suppression des cours non visible pour les participants
    function remove_participant_not_visible_courses()
    {
        if (isset($this->data['participant']) && is_array($this->data['participant']))
        {
            foreach($this->data['participant'] as $categorie_id => $categorie)
            {
                foreach ($categorie as $course_id => $course)
                {
                    if (isset($course->visible))
                    {
                        if ($course->visible == '0')
                        {
                            unset($this->data['participant'][$categorie_id][$course_id]);
                        }
                    }
                }

                $this->data['participant'][$categorie_id] = array_values($this->data['participant'][$categorie_id]);
            }


        }
    }

    // Suppression des cours non visible pour les participants
    function remove_espacecollab_not_visible_courses()
    {
        if (isset($this->data[self::ESPACECOLLABCAT]))
        {
            foreach ($this->data[self::ESPACECOLLABCAT] as $course_id => $course)
            {
                if (isset($course->visible))
                {
                    if ($course->rolename == 'participant' && $course->visible == 0)
                    {
                        unset($this->data[self::ESPACECOLLABCAT][$course_id]);
                    }
                }
            }

            $this->data[self::ESPACECOLLABCAT] = array_values($this->data[self::ESPACECOLLABCAT]);
        }

        if (isset($this->data[self::ESPACECOLLABARCHIVE]))
        {
            foreach ($this->data[self::ESPACECOLLABARCHIVE] as $course_id => $course)
            {
                if (isset($course->visible))
                {
                    if ($course->rolename == 'participant' && $course->visible == 0)
                    {
                        unset($this->data[self::ESPACECOLLABARCHIVE][$course_id]);
                    }
                }
            }

            $this->data[self::ESPACECOLLABARCHIVE] = array_values($this->data[self::ESPACECOLLABARCHIVE]);
        }
    }

    function sort_by_coursename_cmp($a, $b)
    {
        return strcmp($a->fullname, $b->fullname);
    }

    function sort_by_coursename()
    {
        if (is_array($this->data))
        {
            foreach($this->data as $role_id => $role)
            {
                foreach($role as $categorie_id => $categorie)
                {
                    if(is_object($categorie)){ // Fix pour virer le warning en mode debug !! TCS JBL 21/07/2017
                        $array = array();
                        array_push($array, $this->data[$role_id][$categorie_id]);
                        usort($array, array( $this, "sort_by_coursename_cmp"));
                    } else {
                        usort($this->data[$role_id][$categorie_id], array( $this, "sort_by_coursename_cmp"));
                    }
                }
            }
        }
    }

    function hide_deploy_section()
    {
        if( isset($this->data['participant']) )
        {
            if( isset( $this->data['participant']['Session de formation']) )
            {
                // SeFormer_EnCours
                if ( count($this->data['participant']['Session de formation']) > 0 )
                {
                    $this->data['participant']['Session de formation'][0]->deployed = true;
                }
            }

            if( isset( $this->data['participant']['Parcours de formation']) )
            {
                //  SeFormer_Collab_Demo
                if ( count($this->data['participant']['Parcours de formation']) > 0 )
                {
                    $this->data['participant']['Parcours de formation'][0]->deployed = true;
                }
            }

            if( isset( $this->data['participant']['Archive']) )
            {
                // SeFormer_Archive
                if ( count($this->data['participant']['Archive']) > 0 )
                {
                    $this->data['participant']['Archive'][0]->deployed = false;
                }
            }
        }


        if( isset($this->data['formateur']) )
        {
            if( isset($this->data['formateur']['Session de formation']) )
            {
                // Former_EnCours
                if ( count($this->data['formateur']['Session de formation']) > 0 )
                {
                    $this->data['formateur']['Session de formation'][0]->deployed = true;
                }
            }

            if( isset($this->data['formateur']['Archive']) )
            {
                // Former_Archive
                if ( count($this->data['formateur']['Archive']) > 0 )
                {
                    $this->data['formateur']['Archive'][0]->deployed = false;
                }
            }

            if( isset($this->data['formateur']['Parcours de formation']) )
            {
                // Concevoir
                if ( count($this->data['formateur']['Parcours de formation']) )
                {
                    $this->data['formateur']['Parcours de formation'][0]->deployed = true;
                }
            }
        }

        if( isset($this->data[self::ESPACECOLLABCAT]) )
        {
            if ( count($this->data[self::ESPACECOLLABCAT]) )
            {
                $this->data[self::ESPACECOLLABCAT][0]->deployed = true;
            }
        }

    }


    function get_SeFormer_EnCours()
    {
        if( isset($this->data['participant']) )
        {
            if( isset($this->data['participant']['Session de formation']) )
            {
                return $this->data['participant']['Session de formation'];
            }
        }
        return array();
    }

    function get_SeFormer_Archive()
    {
        if( isset($this->data['participant']) )
        {
            if( isset($this->data['participant']['Archive']) )
            {
                return $this->data['participant']['Archive'];
            }
        }
        return array();
    }

    function get_Former_EnCours()
    {
        if( isset($this->data['formateur']) )
        {
            if( isset($this->data['formateur']['Session de formation']) )
            {
                return $this->data['formateur']['Session de formation'];
            }
        }
        return array();
    }

    function get_Former_Archive()
    {
        if( isset($this->data['formateur']) )
        {
            if( isset($this->data['formateur']['Archive']))
            {
                return $this->data['formateur']['Archive'];
            }
        }
        return array();
    }

    function get_Concevoir()
    {
        if(array_key_exists ( 'formateur' , $this->data ))
        {
            if(array_key_exists ( 'Parcours de formation' , $this->data['formateur']))
            {
                return $this->data['formateur']['Parcours de formation'];
            }
        }
        return array();
    }

    function get_Demonstration()
    {
        if(array_key_exists ( 'participant' , $this->data ))
        {
            if(array_key_exists ( 'Parcours de formation' , $this->data['participant']))
            {
                return $this->data['participant']['Parcours de formation'];
            }
        }

        return array();
    }

    function get_EspaceCollaboratif()
    {
        if(array_key_exists ( self::ESPACECOLLABCAT , $this->data))
        {
            return $this->data[self::ESPACECOLLABCAT];
        }

        return array();
    }

    function get_EspaceCollaboratif_Archive()
    {
        if(array_key_exists ( self::ESPACECOLLABARCHIVE , $this->data))
        {
            return $this->data[self::ESPACECOLLABARCHIVE];
        }

        return array();
    }

    function get_user_courses($context, $role01, $role02=null)
    {
        return null;
    }

    function merge_remote_datas($data)
    {
        //ignore courses from our own academy
        global $CFG;

        $academie_urlname = $CFG->wwwroot;
        $academie_urlname = explode('/',$academie_urlname);
        $academie_urlname = '/'.$academie_urlname[count($academie_urlname)-1];

        if( isset( $data['courses_data'] ))
        {
            if(!empty($data['courses_data']))
            {
                foreach ( $data['courses_data'] as $aca => $role_user)
                {
                    foreach($role_user as $role => $courses)
                    {
                        if(in_array(strtolower($role),  $this->accepted_role))
                        {
                            foreach($courses as $id => $course)
                            {
                                if ( strpos($course['url'], $academie_urlname) === false )
                                {
                                    $course_object = new stdClass;
                                    $course_object->id = $id;
                                    $course_object->fullname = $course['fullname'];
                                    $course_object->shortname = null;
                                    $course_object->startdate = $course['startdate'];
                                    $course_object->rolename = strtolower($role);
                                    $course_object->path = null;
                                    $course_object->collection = $course['collection'];
                                    $course_object->categorie_name = $course['category'];
                                    $course_object->origine = array('academie' => $data['site_names'][$aca], 'url'=>$course['url']);

                                    $this->rawdata[$data['site_names'][$aca].$id] = $course_object;
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    function courseToHTML($course, $course_type=null)
	{
		global $OUTPUT;

		if ( isset($course->origine) )
		{
			echo '
			<div class="home-course" id="course '.$course->id.'">
				<div class="post-img collection-'.$course->collection.'"><img src="'.$OUTPUT->image_url('collections/'.$course->collection.'_48x48', 'theme').'" alt="'.$course->logo_label.'"><br>'.($course->logo_label!='empty'?$course->logo_label:'').'</div>
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
            			if($course->visible == 0){
            				echo ' - Parcours non visible pour les participants';
            			}
					}
					else if ($course_type == 'Former')
					{
            			echo $course->shortname;
			            if($course->startdate != 0){
			            	echo ' - '.date('d/m/Y',$course->startdate);
			            }
			            if($course->visible == 0){
			            	echo '- Parcours non visible pour les participants';
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

			$labelCourse = $course->shortname;

			if($course->visible == 0){
				$labelCourse .= '- Parcours non visible pour les participants';
			}

			echo '
			<div class="home-course" id="course '.$course->id.'">
				<div class="post-img collection-'.$course->collection.'"><img src="'.$OUTPUT->image_url('general/logo_'.$course->collection.'', 'theme').'" alt="'.$course->logo_label.'"><br>'.($course->logo_label!='empty'?$course->logo_label:'').'</div>
				<div class="desc">
					<p class="categorie collection-'.$course->collection.'">'.$course->ariane.'</p>
					<p class="post-title"><a href="course/view.php?id='.$course->id.'">'.$course->fullname.'</a></p>
					<p>'.$labelCourse.'</p>
				</div>
				<div class="clear"></div>
			</div>';
		}
		else if($course_type == 'SeFormer')
		{
			$dateLabel ='';

			if($course->startdate != 0){
				$dateLabel = '<p>D&eacute;but le '.date('d/m/Y',$course->startdate).'</p>';
			}

			echo '
			<div class="home-course" id="course '.$course->id.'">
				<div class="post-img collection-'.$course->collection.'"><img src="'.$OUTPUT->image_url('general/logo_'.$course->collection.'', 'theme').'" alt="'.$course->logo_label.'"><br>'.($course->logo_label!='empty'?$course->logo_label:'').'</div>
				<div class="desc">
					<p class="categorie collection-'.$course->collection.'">&nbsp;</p>
					<p class="post-title"><a href="course/view.php?id='.$course->id.'">'.$course->fullname.'</a></p>'
					.$dateLabel.
					'
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

			$label = '<p>' . $course->shortname;

			if($course->startdate != 0){
				$label .= ' - '.date('d/m/Y',$course->startdate);
			}

			if($course->visible == 0){
				$label .= ' - Parcours non visible pour les participants';
			}

			$label .= '</p>';

			echo $label;
			echo '		
				</div>
				<div class="clear"></div>
			</div>';
		}
	}

}

?>
