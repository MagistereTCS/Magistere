<?php

//définition des constantes (en vue d'une modification des intitulés)
define("CAT_GAB",     "Gabarit");
define("CAT_PDF",     "Parcours de formation");
define("CAT_SDF",     "Session de formation");
define("CAT_ARC", 	  "Archive");
define("CAT_TRASH",   "Corbeille");
	
// get the main category of current 
function get_course_main_category(){
	global $PAGE;
	foreach ($PAGE->categories as $cat){			
		if($cat->name==CAT_GAB ||$cat->name==CAT_PDF ||$cat->name==CAT_SDF||$cat->name==CAT_ARC||$cat->name==CAT_TRASH){
			return $cat->name; 			
			break;
		}
	}
}

// return an array with all actions allowed for the user
function get_action_links($course_main_category, $context){
	global $USER;
	$l_action_links = null;
	if(is_pa()){
		if(has_capability('block/course_management:createblankgabarit', $context, $USER->id, TRUE)){
			$l_action_links[] = 'createblankgabarit';
		}	
	}elseif(is_course()){
		if($course_main_category==CAT_GAB){
			if (has_capability('local/centralizedresources:manage', $context, $USER->id, TRUE))
			{
				$l_action_links[] = 'managecentralizeresources';
			}
			if(has_capability('block/course_management:createparcoursfromgabarit', $context, $USER->id, TRUE)){
				$l_action_links[] = 'createparcoursfromgabarit';
			}
			if(has_capability('block/course_management:duplicate', $context, $USER->id, TRUE)){
				$l_action_links[] = 'duplicate';
			}
			if(has_capability('block/course_management:index', $context, $USER->id, TRUE)){
				$l_action_links[] = 'index';
			}
			if(has_capability('block/course_management:discard', $context, $USER->id, TRUE)){
				$l_action_links[] = 'discard';
			}
		}elseif($course_main_category==CAT_PDF){
			if (has_capability('local/centralizedresources:manage', $context, $USER->id, TRUE))
			{
				$l_action_links[] = 'managecentralizeresources';
			}
			if(has_capability('block/course_management:creategabaritfromparcours', $context, $USER->id, TRUE)){
				$l_action_links[] = 'creategabaritfromparcours';
			}
			if(has_capability('block/course_management:createsessionfromparcours', $context, $USER->id, TRUE)){
				$l_action_links[] = 'createsessionfromparcours';
			}
			if(has_capability('block/course_management:duplicate', $context, $USER->id, TRUE)){
				$l_action_links[] = 'duplicate';
			}
			if(has_capability('block/course_management:index', $context, $USER->id, TRUE)){
				$l_action_links[] = 'index';
			}
			if(has_capability('block/course_management:discard', $context, $USER->id, TRUE)){
				$l_action_links[] = 'discard';
			}
		}elseif($course_main_category==CAT_SDF){
			if (has_capability('local/centralizedresources:manage', $context, $USER->id, TRUE))
			{
				$l_action_links[] = 'managecentralizeresources';
			}
			if(has_capability('block/course_management:createparcoursfromsession', $context, $USER->id, TRUE)){
				$l_action_links[] = 'createparcoursfromsession';
			}
			if(has_capability('block/course_management:archive', $context, $USER->id, TRUE)){
				$l_action_links[] = 'archive';
			}
			if(has_capability('block/course_management:duplicate', $context, $USER->id, TRUE)){
				$l_action_links[] = 'duplicate';
			}
			if(has_capability('block/course_management:index', $context, $USER->id, TRUE)){
				$l_action_links[] = 'index';
			}
			if(has_capability('block/course_management:discard', $context, $USER->id, TRUE)){
				$l_action_links[] = 'discard';
			}
		}elseif($course_main_category==CAT_ARC){
			if (has_capability('local/centralizedresources:manage', $context, $USER->id, TRUE))
			{
				$l_action_links[] = 'managecentralizeresources';
			}
			if(has_capability('block/course_management:unarchive', $context, $USER->id, TRUE)){
				$l_action_links[] = 'unarchive';
			}
			if(has_capability('block/course_management:discard', $context, $USER->id, TRUE)){
				$l_action_links[] = 'discard';
			}
		}elseif($course_main_category==CAT_TRASH){
			if (has_capability('local/centralizedresources:manage', $context, $USER->id, TRUE))
			{
				$l_action_links[] = 'managecentralizeresources';
			}
			if(has_capability('block/course_management:restorefromtrash', $context, $USER->id, TRUE)){
				$l_action_links[] = 'restorefromtrash';
			}
		}
	}
	return $l_action_links;
}

//vérifie que l'on est sur la page d'acceuil
	function is_pa(){
		global $PAGE;
		return ($PAGE->bodyid == "page-site-index") ? true : false;
	}

//vérifie que l'on est dans un cours
	function is_course(){
		global $PAGE;
		return (strpos($PAGE->bodyid,"page-course") === 0 ) ?  true :  false;
	}


//création du contenu du bloc en fonction de l'utilisateur et de la catégorie retourné par function block_display
	function action_content($l_links_type, $course_category, $blockinstanceid){
		global $PAGE, $CFG;
		$creation_link = '<ul>';
		if(!empty($l_links_type)){
			ob_start();
			require_once ('popin.php');
			$popin = ob_get_clean();
		}else{
			return null;
		}
		
		foreach($l_links_type as $link_type){					
				
				if ($link_type == 'managecentralizeresources'){
					$url_managecentralizeresources = new moodle_url("/local/centralizedresources/view.php?", array('controller'=>'manageresource','action'=>'default','courseid'=>$PAGE->course->id));
					$creation_link .= '<li><a href="'.$url_managecentralizeresources.'" id="link_managecentralizeresources">Gestion des ressources centralisées</a></li>';
				}else if($link_type == 'createblankgabarit'){
					
					$url_crea_gab = new moodle_url("/course/edit.php?", array('category'=>'1'));
					$creation_link .= (html_writer::link( $url_crea_gab, 'Créer un gabarit'));

				}elseif($link_type == 'createparcoursfromgabarit'){
					$creation_link .= '<li><a href="#" id="link_createparcoursfromgabarit">Créer un parcours de formation</a></li>';

				}elseif($link_type == 'creategabaritfromparcours'){
					$creation_link .= '<li><a href="#" id="link_creategabaritfromparcours">Créer un gabarit</a></li>';

				}elseif($link_type == 'createsessionfromparcours'){
					$creation_link .= '<li><a href="#" id="link_createsessionfromparcours">Créer une session de formation</a></li>';

				}elseif($link_type == 'createparcoursfromsession'){
					$creation_link .= '<li><a href="#" id="link_createparcoursfromsession">Créer un parcours de formation</a></li>';

				}elseif($link_type == 'archive'){
					$creation_link .= '<li><a href="#" id="link_archive">Archiver la session</a></li>';

				}elseif($link_type == 'duplicate'){
					$creation_link .= '<li><a href="#" id="link_duplicate">Dupliquer ce parcours</a></li>';

				}elseif($link_type == 'index'){
					$creation_link .= '<li><a href="'.$CFG->wwwroot.'/local/indexation/?id='.$PAGE->course->id.'">Indexer ce parcours</a></li>';

				}elseif($link_type == 'unarchive'){
					$creation_link .= '<li><a href="#" id="link_unarchive">Ré-ouvrir la session de formation</a></li>';

				}elseif($link_type == 'discard'){
					$creation_link .= '<li><a href="#" id="link_discard">Mettre à la corbeille</a></li>';

				}elseif($link_type == 'restorefromtrash'){
					$creation_link .= '<li><a href="#" id="link_restorefromtrash">Restaurer le parcours</a></li>';
				}
		}
        $creation_link .= '</ul>';
		return $creation_link.$popin;
	}

	//function which return content of select in form popin
	function subcategory_select_content($link_type, $actual_category = ''){
		global $DB, $PAGE;
		$trash_category = '';
		if($link_type == 'createparcoursfromgabarit'){
			$main_category = $DB->get_record('course_categories' , array('name' => 'Parcours de formation'));
			$subcategory_tree = destination_subcategory_tree($main_category->id);
		}elseif($link_type == 'creategabaritfromparcours'){
			$main_category = $DB->get_record('course_categories' , array('name' => 'Gabarit'));
			$subcategory_tree = destination_subcategory_tree($main_category->id);
		}elseif($link_type == 'createsessionfromparcours'){
			$main_category = $DB->get_record('course_categories' , array('name' => 'Session de formation'));
			$subcategory_tree = destination_subcategory_tree($main_category->id);
		}elseif($link_type == 'createparcoursfromsession'){
			$main_category = $DB->get_record('course_categories' , array('name' => 'Parcours de formation'));
			$subcategory_tree = destination_subcategory_tree($main_category->id);
		}elseif($link_type == 'archive'){
			$main_category = $DB->get_record('course_categories' , array('name' => 'Archive'));
			$subcategory_tree = destination_subcategory_tree($main_category->id);
		}elseif($link_type == 'duplicate'){
			$main_category = $DB->get_record('course_categories' , array('name' => $actual_category));
			$subcategory_tree = destination_subcategory_tree($main_category->id);
		}elseif($link_type == 'unarchive'){
			$main_category = $DB->get_record('course_categories' , array('name' => 'Session de formation'));
			$subcategory_tree = destination_subcategory_tree($main_category->id);
		}elseif($link_type == 'restorefromtrash'){
			$trash_category = $DB->get_record('course_trash_category', array('course_id'=>$PAGE->course->id));
			$actual_category = $trash_category->category_id;
			$subcategory_tree = destination_subcategory_tree(0,true);
		}

		$content = '';
		if ($link_type != 'restorefromtrash')
		{
			$content .= '<option value="'.$main_category->id.'">'.get_string('nosubcategory', 'block_course_management').'</option>';
		}
		
		$content .= select_content_build($subcategory_tree, $actual_category);

		return $content;
	}

	
	//function return destination subcategories tree
	function destination_subcategory_tree($main_category_id, $is_root = false){
		global $DB;
		$subcategory_tree = '';
		
		$offset = ($is_root)?1:0;
		
		// Mantis 1370 
		//$l_subcategories = $DB->get_records('course_categories',array( 'parent' => $main_category_id));
		$l_subcategories = $DB->get_records_sql('SELECT * FROM {course_categories} WHERE parent = ? ORDER BY name',array($main_category_id));
		foreach($l_subcategories as $subcategory){
			if ($subcategory->name != CAT_ARC && $subcategory->name != CAT_TRASH)
			{
				$subcategory_tree[] = array('id' => $subcategory->id , 'name' => $subcategory->name, 'depth' => ($subcategory->depth+$offset));
				
				$children = destination_subcategory_tree($subcategory->id, $is_root);
				if(!empty($children)){
					foreach($children as $child){
						array_push($subcategory_tree,$child);
					}
				}
				$children=null;
			}
		}
		return $subcategory_tree;
	}
	
	function select_content_build($subcategory_tree, $selected_value = ''){
		$select_content = '';
		foreach($subcategory_tree as $subcategory){		
			$select_content .= '<option value="'.$subcategory['id'].'" '.($selected_value==$subcategory['id']?' selected="selected"':'').'>';
			
			for ($i = 2; $i < $subcategory['depth']; $i++) {
				$select_content .= '&nbsp&nbsp';
			}
			$select_content .= '► '.$subcategory['name'].'</option>';
		}
		return $select_content;
	}
?>