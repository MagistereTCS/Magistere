<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.


class block_my_lastcoursesoffer extends block_base {
    function init() {
        $this->title = "Derniers parcours de l'offre";
    }

    function has_config() {
        return false;
    }

    function applicable_formats() {
        return array('my-index' => true);
    }

    function is_empty() {
    	global $CFG, $SCRIPT;
    	require_once($CFG->dirroot . '/local/magisterelib/courseList.php');
    	
    	$courseList = new Course_list();
    	
    	switch($SCRIPT){
    		case '/my/seformer.php':
    			//return count($courseList->get_SeFormer_EnCours()) + count($courseList->get_Demonstration()) + count($courseList->get_SeFormer_Archive()) > 0;
                return false;
    			break;
    		case '/my/former.php':
    			return !(count($courseList->get_Former_EnCours()) + count($courseList->get_Former_Archive()));
    			break;
    		case '/my/concevoir.php':
    			return !count($courseList->get_Concevoir());
    			break;
    		default:
    	}
    	return false;
   	}
    
    function get_content() {
        global $CFG, $PAGE, $OUTPUT;

        $PAGE->requires->jquery();
        $this->content = new stdClass();
        $this->content->text = '';
        
        
        require_once($CFG->dirroot . '/local/magisterelib/courseServices.php');
        
        $params = array('self_inscription'=>0);
        $offerCourseList = new OfferCourseList($params);
        $courses = $offerCourseList->getCourses(0,5);
        $html = '<div class="mycourseoffer">';
        $i = 0;
        foreach($courses AS $course)
        {
        	$i++;
        	$html .= '<div>';
        	
        	$html .= '<div class="collectionlogo">';
        	$html .= '<img src="'.$OUTPUT->image_url('general/collection_'.$course->ind_collection.'_48x48', 'theme').'" alt="'.$course->ind_collection.'">';
        	$html .= '</div>';
        	
        	$html .= '<div class="courseformation">';
        	$html .= '<p class="collection">'.$course->ind_collection.'</p>';

            if(isset($course->source)) {
                $url = new moodle_url('/course/view.php?id='.$course->id);
        	}else{
                $url = $CFG->magistere_domaine . '/local/magistere_offers/index.php?v=course#offer='. $course->ind_id;
            }

        	$html .= '<p class="fullname"><a href="'.$url.'">'.$course->fullname.'</a></p>';
        	
        	
        	$html .= '<p class="provider">Origine : '.$this->ind_origin($course,$offerCourseList).'</p>';
        	
        	$html .= '<p class="subscription">Inscription : <span class="maxparticipant">'.($course->maxparticipant>0?'Limitée à '.$course->maxparticipant.' places':'Permanente').'</span></p>';
        	
        	if ($course->ind_tps_a_distance > 0 || $course->ind_tps_en_presence > 0)
        	{
        		$html .= '<p class="duration">';
        		if ($course->ind_tps_a_distance > 0)
        		{
        			$html .= $this->minute_to_hour($course->ind_tps_a_distance).' à distance';
        			if ($course->ind_tps_en_presence > 0)
        			{
        				$html .= ' - ';
        			}
        		}
        		if ($course->ind_tps_en_presence > 0)
        		{
					$html .= $this->minute_to_hour($course->ind_tps_en_presence).' en présence';
        		}
				$html .= '</p>';
        		
        	}
        	
        	if ($course->hasakey)
        	{
        		$html .= '<p class="locked">Accès limité par mot de passe</p>';
        	}
        	
        	$html .= '</div>';
        	
        	$html .= '</div>';
        }

        $url = $CFG->magistere_domaine . '/local/magistere_offers/index.php?v=course';
        
        $html .= '<div style="text-align:center"><a href="'.$url.'"><button>Rechercher un parcours</button></a></div>';
        
        $html .= '</div>';
        
        $this->content->text = $html;

        return $this->content;
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
}


