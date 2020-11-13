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
require_once($CFG->dirroot.'/local/magistere_offers/lib.php');
require_once($CFG->dirroot.'/local/magistere_offers/form/publics_form.php');
class block_mycourseoffer extends block_base {
    function init() {
        // aucun titre
        $this->title = 'Dernières formations en inscription libre';

    }

    function has_config() {
        return false;
    }
    
    function is_empty() {
    	global $CFG, $SCRIPT;
    	require_once($CFG->dirroot . '/local/magisterelib/courseList.php');

    	$courseList = new Course_list();
    	
    	switch($SCRIPT){
    		case '/my/seformer.php':
    			return !(count($courseList->get_SeFormer_EnCours()) + count($courseList->get_Demonstration()) + count($courseList->get_SeFormer_Archive()) > 0);
    			break;
    		case '/my/former.php':
    			//return !(count($courseList->get_Former_EnCours()) + count($courseList->get_Former_Archive()));
    			return false;
    			break;
    		case '/my/concevoir.php':
    			//return !count($courseList->get_Concevoir());
    			return false;
    			break;
    		default:
    	}
    	return false;
   	}

    function get_content() {
        global $CFG, $PAGE, $OUTPUT, $USER;

        $PAGE->requires->jquery();

        $this->content = new stdClass();

        $this->content->text = '';

        require_once($CFG->dirroot . '/local/magisterelib/courseServices.php');

        $publicsByfonction = new PublicsByFunctionUser($USER->id, 'formation');
        $form_publics = new publics_form(null);

        if($form_publics->get_data()){
            $data = $form_publics->get_data();
            $publicsByfonction->set_formation_first_connection(true);
            if(isset($data->publics_fav)){
                $publicsByfonction->set_favorite_formation_publics(array_keys($data->publics_fav));
            } else {
                $publicsByfonction->set_favorite_formation_publics("");
            }
            if(isset($data->get_notif)){
                $publicsByfonction->set_formation_notification($data->get_notif);
            } else {
                $publicsByfonction->set_formation_notification(0);
            }

            redirect($CFG->wwwroot.'/my/');
        }

        $filter = new stdClass();
        $filter->limit_for_block = true;

        $publics_formation_fav = $publicsByfonction->get_favorite_formation_publics();
        if($publics_formation_fav){
            $filter->publics = $publicsByfonction->prepare_publics_for_checkboxes_form();
        }

        $offerCourse = new OfferCourse($filter, 'formation', $USER->id);
        $offers = $offerCourse->get_all_course_offers();

        $url = $CFG->magistere_domaine . '/local/magistere_offers/index.php?v=formation';

        $input_search = html_writer::tag('input',null, array(
            'type'=>'text',
            'id' => 'search-block-course-offer',
            'placeholder' => get_string('search', 'block_mycourseoffer'),
            'name'=>"search_name"
        ));

        $input = '<button type="submit" class="search_submit">
                    <i class="fa fa-search"></i>
                </button>';
        $form_course_search = html_writer::tag('form',$input_search.$input,array("action"=>$url,"method"=>"post"));


        $html = html_writer::div(
            $form_course_search
            .html_writer::tag('button',
                html_writer::tag('i','',array('class' => 'fas fa-pencil-alt icon','style'=>'font-size: 20px;')),
                array('id' => 'edit', 'data-toggle'=>"modal",'data-target'=>"#publics-modal")),
            'search-course');

        $html .= '<ul class="mycourseoffer">';
        $i = 0;
        foreach($offers as $offer)
        {
            $i++;
            $html .= '<li>';

            if(isset($offer->collectionid) && $offer->collectionid != null){
                $html .= '<div class="collectionlogo">';
                $html .= '<img src="'.$OUTPUT->image_url('general/collection_'.$offer->col_shortname.'_48x48', 'theme').'" alt="'.$offer->col_shortname.'">';
                $html .= '</div>';

                $html .= '<div class="courseformation">';
                $html .= '<p class="collection">'.$offer->col_name.'</p>';
            }

            if(isset($offer->source) && $offer->source == 'local') {
                $url = new moodle_url('/course/view.php?id='.$offer->courseid);
            }else{
                $url = $CFG->magistere_domaine . '/f'. $offer->fakeid;
            }
            $html .= '<p class="fullname"><a href="'.$url.'">'.$offer->fullname.'</a></p>';

            if(isset($offer->origin_shortname) && $offer->origin_shortname != null) {
                if ($offer->origin_shortname == "academie") {
                    $origin_name = OfferCourse::string_format_origine_offers($offer->aca_uri);
                } else {
                    $origin_name = OfferCourse::string_format_origine_offers($offer->origin_shortname);
                }
                $html .= '<p class="provider">Origine : '.$origin_name.'</p>';
            }

            $html .= '<p class="subscription">Inscription : <span class="maxparticipant">'.($offer->maxparticipant>0?'Limitée à '.$offer->maxparticipant.' places':'Permanente').'</span></p>';

            if ($offer->tps_a_distance > 0 || $offer->tps_en_presence > 0)
            {
                $html .= '<p class="duration">';
                if ($offer->tps_a_distance > 0)
                {
                    $html .= $this->minute_to_hour($offer->tps_a_distance).' à distance';
                    if ($offer->tps_en_presence > 0)
                    {
                        $html .= ' - ';
                    }
                }
                if ($offer->tps_en_presence > 0)
                {
                    $html .= $this->minute_to_hour($offer->tps_en_presence).' en présence';
                }
                $html .= '</p>';

            }

            if ($offer->hasakey)
            {
                $html .= '<p class="locked">Accès limité par mot de passe</p>';
            }

            $html .= '</div>';

            $html .= '</li>';
        }

        if($i == 0){
            $html.="<p>Aucun parcours à afficher.</p>";
        }
        $html .= '</ul>';

        $html.= $publicsByfonction->create_modal("publics-modal");

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


