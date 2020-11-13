<?php
require_once($CFG->dirroot . '/local/magisterelib/courseList.php');
require_once($CFG->dirroot . '/local/favoritecourses/lib.php');

function build_html_seformer(){
    global $OUTPUT, $CFG;
    $courseList = new Course_list();

    $l_SeFormer_EnCours = $courseList->get_SeFormer_EnCours();
    $l_Demonstration = $courseList->get_Demonstration();
    $l_SeFormer_Archive = $courseList->get_SeFormer_Archive();

    $SeFormer_EnCours_deployed = isset($l_SeFormer_EnCours[0]->deployed) && $l_SeFormer_EnCours[0]->deployed ? ' class="hide-details"' : 'class="show-details"';
    $Demonstration_deployed = isset($l_Demonstration[0]->deployed) && $l_Demonstration[0]->deployed ? ' class="hide-details"' : 'class="show-details"';
    $SeFormer_Archive_deployed = isset($l_SeFormer_Archive[0]->deployed) && $l_SeFormer_Archive[0]->deployed ? ' class="hide-details"' : 'class="show-details"';

    $deployed_style_SeFormer_EnCours = isset($l_SeFormer_EnCours[0]->deployed) && $l_SeFormer_EnCours[0]->deployed ? '' : ' style="display:none;"';
    $deployed_style_Demonstration = isset($l_Demonstration[0]->deployed) && $l_Demonstration[0]->deployed ? '' : ' style="display:none;"';
    $deployed_style_SeFormer_Archive = isset($l_SeFormer_Archive[0]->deployed) && $l_SeFormer_Archive[0]->deployed ? '' : ' style="display:none;"';

    $html = '';

    if(!(count($l_SeFormer_EnCours) + count($l_Demonstration) + count($l_SeFormer_Archive) > 0)){
    	
    	require_once($CFG->dirroot . '/local/magisterelib/courseServices.php');
    	
    	$params = array('self_inscription'=>3);
    	$offerCourseList = new OfferCourseList($params);
    	$courses = $offerCourseList->getCourses(0,10);
    	
    	$html .=  '
<div class="element block">
    <div class="title">
        <h3>Derniers parcours de l\'offre complémentaire</h3>
        <a href="#"'.$SeFormer_EnCours_deployed.'></a>
    </div>
    <div class="details">
        <div class="mycourseoffer">';
    	
    	$i=0;
    	foreach($courses AS $course)
    	{
            if(isset($course->source)) {
                $url = new moodle_url('/course/view.php?id='.$course->id);
            }else{
                $url = $CFG->magistere_domaine . '/local/magistere_offers/index.php?v=formation#offer='. $course->ind_id;
            }
    		$i++;
    		$html .= '<div class="offerblock">';
    		
	    		$html .= '<table class="offertable" cellspacing="0" cellpadding="0" border="0">';
	    		
	    		$html .= '<tr><td class="collectionlogo">';
	    		$html .= '<img src="'.$OUTPUT->image_url('general/collection_'.$course->ind_collection.'_48x48', 'theme').'" alt="'.$course->ind_collection.'">';
	    		$html .= '</td><td class="courseformation'.(count($courses)==$i?' last':'').'">';
	    		
		    		$html .= '<p class="collection">'.$course->ind_collection.'</p>';

		    		$html .= '<p class="fullname"><a href="'.$url.'">'.$course->fullname.'</a></p>';
		    		
		    		$html .= '<p class="subscription">Inscription : <span class="maxparticipant">'.($course->maxparticipant>0?'Limitée à '.$course->maxparticipant.' places':'Permanente').'</span></p>';

	    		$html .= '</td><td class="courseformationdesc'.(count($courses)==$i?' last':'').'">';

	    			$html .= '<p class="publicationdate">Publié le : '.date('d/m/Y',$course->timepublished).'</p>';

	    			$html .= '<p class="provider">Proposée par : '.ind_origin($course,$offerCourseList).'</p>';

                if (isset($course->ind_tps_en_presence) && $course->ind_tps_en_presence != 0) {
                    $html .= '<p class="durationlocal">' . minute_to_hour($course->ind_tps_en_presence) . ' en présence</p>';
                }
                if (isset($course->ind_tps_a_distance) && $course->ind_tps_a_distance != 0) {
                    $html .= '<p class="durationremote">' . minute_to_hour($course->ind_tps_a_distance) . ' à distance</p>';
                }
		    		
		    		if ($course->hasakey) {
		    			$html .= '<p class="locked">Accès limité par mot de passe</p>';
		    		}
	    		
	    		$html .= '</td></tr></table>';
    		
    		$html .= '</div>';
    	}

        $url = $CFG->magistere_domaine . '/local/magistere_offers/index.php?v=formation';
    	
    	$html .= '<div class="button"><a href="'.$url.'"><button>Rechercher une formation</button></a></div>';
    	
    	$html .= '</div>
    </div>
</div>';
    }

    if(count($l_SeFormer_EnCours) > 0){
        $html .=  '
<div class="element block">
    <div class="title">
        <h3>Sessions en cours <span class="total"></span></h3>
        <a href="#"'.$SeFormer_EnCours_deployed.'></a>
    </div>
    <div class="details"'.$deployed_style_SeFormer_EnCours.'>
        <div class="home-course-list">';

        foreach ( $l_SeFormer_EnCours as $course ){
            $html .= build_html_course($course, 'SeFormer');
        }

        $html .= '</div>
    </div>
</div>';
    }
    
    if (count($l_Demonstration) > 0) {
        $html .= '
<div class="element block">
    <div class="title">
        <h3>Espaces en démonstration<span class="total"></span></h3>
        <a href="#"'.$Demonstration_deployed.'></a>
    </div>
    <div class="details"'.$deployed_style_Demonstration.'>
        <div class="home-course-list">';

        foreach ( $l_Demonstration as $course )
        {
            $html .= build_html_course($course, 'SeFormerCollabo');
        }
        $html .= '</div>
    </div>
</div>';
    }

    if (count($l_SeFormer_Archive) > 0) {
        $html .= '
<div class="element block">
    <div class="title">
        <h3>Sessions archivées <span class="total"></span></h3>
        <a href="#"' . $SeFormer_Archive_deployed . '></a>
    </div>
    <div class="details"' . $deployed_style_SeFormer_Archive . '>
        <div class="home-course-list">';

        foreach ($l_SeFormer_Archive as $course) {
            $html .= build_html_course($course, 'SeFormer');
        }

        $html .= '</div>
    </div>
</div>';
    }

    return $html;
}

function build_html_former(){
    $courseList = new Course_list();

    $l_Former_EnCours = $courseList->get_Former_EnCours();
    $l_Former_Archive = $courseList->get_Former_Archive();

    $html = '';

    if (count($l_Former_EnCours) > 0) {
        $Former_EnCours_deployed = $l_Former_EnCours[0]->deployed ? ' class="hide-details"' : 'class="show-details"';
        $deployed_style_Former_EnCours = $l_Former_EnCours[0]->deployed ? '' : ' style="display:block; display:initial;"';

        $html .= '
<div class="element">
    <div class="title" id="Formateur">
        <h3>Mes sessions en cours <span class="total"></span></h3>
        <a href="#"'.$Former_EnCours_deployed.'></a>
    </div>
    <div class="details"'.$deployed_style_Former_EnCours.'>
        <div class="home-course-list">';

        foreach ( $l_Former_EnCours as $course )
        {
            $html .= build_html_course($course, 'Former');
        }

        $html .= '</div>
    </div>
</div>';
    }

    if (count($l_Former_Archive) > 0) {
        $Former_Archive_deployed = $l_Former_Archive[0]->deployed ? ' class="hide-details"' : 'class="show-details"';
        $deployed_style_Former_Archive = $l_Former_Archive[0]->deployed ? '' : ' style="display:block; display:initial;"';

        $html .= '
<div class="element">
    <div class="title" id="Formateur">
        <h3>Mes sessions terminées <span class="total"></span></h3>
        <a href="#"'.$Former_Archive_deployed.'></a>
    </div>
    <div class="details"'.$deployed_style_Former_Archive.'>
        <div class="home-course-list">';

        foreach ( $l_Former_Archive as $course )
        {
            $html .= build_html_course($course, 'Former');
        }

        $html .= '</div>
    </div>
</div>';
    }

    return $html;
}

function build_html_concevoir(){
    $html = '';

    $courseList = new Course_list();

    $l_Concevoir = $courseList->get_Concevoir();

    if (count($l_Concevoir) > 0) {
        $Concevoir_deployed = $l_Concevoir[0]->deployed ? ' class="hide-details"' : ' class="show-details"';
        $deployed_style_Concevoir = $l_Concevoir[0]->deployed ? '' : ' style="display:block; display:initial;"';

        $html .= '
<div class="element">
    <div class="title" id="Formateur">
        <h3>Mes parcours en conception<span class="total"></span></h3>
        <a href="#"'.$Concevoir_deployed.'></a>
    </div>
    <div class="details"'.$deployed_style_Concevoir.'>
        <div class="home-course-list">';

        foreach ( $l_Concevoir as $course )
        {
            $html .= build_html_course($course, 'conception');
        }

        $html .= '</div>
    </div>
</div>';
	}

    return $html;
}

function build_html_espacecollaboratifs(){
    $html = '';

    $courseList = new Course_list();

    $l_EspaceCollaboratif = $courseList->get_EspaceCollaboratif();
    $l_EspaceCollaboratif_Archive = $courseList->get_EspaceCollaboratif_Archive();

    if(count($l_EspaceCollaboratif) > 0) {
        $EspaceCollaboratif_deployed = $l_EspaceCollaboratif[0]->deployed ? ' class="hide-details"' : ' class="show-details"';
        $deployed_style_EspaceCollaboratif = $l_EspaceCollaboratif[0]->deployed ? '' : ' style="display:block; display:initial;"';

        $html .= '
<div class="element">
    <div class="title" id="Formateur">
        <h3>Mes Espaces Collaboratifs<span class="total"></span></h3>
        <a href="#"'.$EspaceCollaboratif_deployed.'></a>
    </div>
    <div class="details"'.$deployed_style_EspaceCollaboratif.'>
        <div class="home-course-list">';

        foreach ( $l_EspaceCollaboratif as $course )
        {
            $html .= build_html_course($course, 'SeFormerCollabo');
        }

        $html .= '</div>
    </div>
</div>';
    }


    if(count($l_EspaceCollaboratif_Archive) > 0){
        $EspaceCollaboratif_Archive_deployed = $l_EspaceCollaboratif[0]->deployed ? ' class="hide-details"' : ' class="show-details"';
        $deployed_style_EspaceCollaboratif_Archive = $l_EspaceCollaboratif[0]->deployed ? '' : ' style="display:block; display:initial;"';

        $html .= '
<div class="element">
    <div class="title" id="Formateur">
        <h3>Mes espaces archivés<span class="total"></span></h3>
        <a href="#"'.$EspaceCollaboratif_Archive_deployed.'></a>
    </div>
    <div class="details"'.$deployed_style_EspaceCollaboratif_Archive.'>
        <div class="home-course-list">';

        foreach ( $l_EspaceCollaboratif_Archive as $course )
        {
            $html .= build_html_course($course, 'SeFormerCollabo');
        }

        $html .= '</div>
    </div>
</div>';
     }

    return $html;
}

function build_html_favorite(){
    $html = '';

    $favoriteCourses = new favoriteCourses();

    $favoriteList = $favoriteCourses->get_favorite_courses_list_by_timecreated();

    $html = '<div class="element">
    <div class="title" id="Formateur">
        <h3>'.get_string('favoritecoursestitle', 'block_mycourselist').'</h3>
    </div>';

    if(count($favoriteList) > 0) {
        $html .= '<div class="details">
        <div class="home-course-list">';

        foreach ( $favoriteList as $course )
        {
            $html .= build_html_course($course, 'Favoris');
        }

        $html .= '</div>
    </div>';
    }else{
        $nofav = new moodle_url('/blocks/mycourselist/img/nofav.png');
        $html .= '<img style="display: block; margin:auto; margin-bottom: 35px;" src="'.$nofav.'">
<p style="font-size:20px; text-align:center; font-weight: bold;">'.get_string('nofav_title', 'block_mycourselist').'</p>
<p style="text-align:center;">'.get_string('nofav_text', 'block_mycourselist').'</p>';
    }

    $html .= '</div>';

    return $html;
}

function build_html_course($course, $course_type=null){
    global $OUTPUT;

    $otherattributes = array();
    $origine = isset($course->origine) ? string_format_origine($course->origine['academie']) : '';

    if ($course_type == 'SeFormer')
    {
        if($course->startdate != 0){
            $otherattributes[] = 'Débute le '.date('d/m/Y',$course->startdate);
        }
    }
    else if ($course_type == 'conception')
    {
        $otherattributes[] = $course->shortname;
        if($course->visible == 0){
            $otherattributes[]  = 'Parcours non visible pour les participants';
        }
    }
    else if ($course_type == 'Former')
    {
        $otherattributes[]  = $course->shortname;
        if($course->startdate != 0){
            $otherattributes[] = date('d/m/Y',$course->startdate);
        }
        if($course->visible == 0){
            $otherattributes[]  = 'Parcours non visible pour les participants';
        }
    }

    $html = ' <div class="home-course" id="course '.$course->id.'">
    <div class="post-img collection-'.$course->collection.'">
        <img src="'.$OUTPUT->image_url('general/collection_'.$course->collection.'_48x48', 'theme').'" alt="'.$course->logo_label.'">
    </div>
    <div class="desc">
        <p class="collection-title">'.($course->logo_label!='empty'?$course->logo_label:'').'</p>';

    $url = !empty($origine) ? $course->origine['url'] : new moodle_url('/course/view.php', array('id' => $course->id));


    $html .= '<p class="post-title">';
    $html .= '<a href="'.$url.'">'.$course->fullname.'</a>';

    $dataac = isset($course->origine['academie']) ? ' data-ac="'.$course->origine['academie'].'"' : '';
    if((isset($course->isfav) && $course->isfav) || $course_type == 'Favoris'){
        $html .= '<a class="fav" href="#" data-id="'.$course->id.'"'.$dataac.'><i class="fa fa-star" aria-hidden="true"></i></a>';
    }else{
        $html .= '<a class="unfav" href="#" data-id="'.$course->id.'"'.$dataac.'><i class="far fa-star" aria-hidden="true"></i></a>';
    }

    $html .= '</p>';

    if(count($otherattributes) > 0){
        $html .= '<p class="mycourselist_detail">'.implode($otherattributes, ' - ').'</p>';
    }

    if(!empty($origine)){
        $html .= '<div class="origine"><img src="'.$OUTPUT->image_url('general/logo_btn_acad', 'theme').'"><a class="aca_name">'.$origine.'</a></div>';
    }

    $html .= '</div>';

    if(isset($course->rolename) && count($course->rolename) > 0 && is_array($course->rolename)) {
        $html  .= '<div class="tags">';
        foreach($course->rolename as $rolename) {
            $html .= '<span class="'.$rolename.'"><i class="fa fa-user" aria-hidden="true"></i>'.$rolename.'</span><br/>';
        }
        $html .= '</div>';
    }

    $html .= '<div class="clear"></div>
</div>';

    return $html;
}

function string_format_origine($academie){
    if(strpos($academie, 'ac-') === 0){
        $academiename = str_replace('ac-', '', $academie);
        $academiename = ucfirst($academiename);

        if( in_array($academiename[0], array('A', 'I', 'O', 'U', 'E'))){
            // si commence par une voyelle
            $academiename = 'd\''.$academiename;
        }else {
            $academiename = 'de '.$academiename;
        }
        $academiename = get_string('originacademielabel', 'block_mycourselist', $academiename);
    }else{
        if($academie == 'reseau-canope'){ /* Mantis #1950 25/08/2017 JBL */
            $academiename = 'Réseau Canopé';
        } else {
            $academiename = strtoupper($academie);
        }
    }

    return $academiename;
}

function minute_to_hour($minutes)
{
	if ($minutes < 60)
	{
		return $minutes.'min';
	}
	else if ($minutes > 60 && ($minutes%60) > 0)
	{
		return floor($minutes/60).'h'.($minutes%60).'min';
	}else{
		return floor($minutes/60).'h';
	}
	
}



function ind_origin($course, $offerCourseList)
{
	switch ($course->ind_origine) {
		case 'academie':
			if (isset($offerCourseList->getFields()['academy'][$course->ind_academy])) {
				$output = $offerCourseList->getFields()['academy'][$course->ind_academy];
				
				if ($course->ind_department && isset($offerCourseList->getFields()['department'][$course->ind_department])) {
					$output .= ' (' . $offerCourseList->getFields()['department'][$course->ind_department] . ')';
				}
				
				return $output;
			}
			return 'Académie';
			
		case 'espe':
			return 'Espé (' . $offerCourseList->getFields()['origin_espe'][$course->ind_origin_espe] . ')';
			
		default:
			return $offerCourseList->getFields()['origine'][$course->ind_origine];
	}
}