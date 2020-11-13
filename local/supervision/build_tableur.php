<?php

function build_csv($courses_user_completions, $course_list_array, $formation_hours)
{
	GLOBAL $DB, $CFG;
	$csv = '';
	$nb_courses = count($courses_user_completions);

	$premiere_ligne = $deuxieme_ligne = $troisime_ligne = $quatrieme_ligne = array();
	$premiere_ligne[0] = $deuxieme_ligne[0] = $troisieme_ligne[0] = $quatrieme_ligne[0] = '';
	$premiere_ligne[1] = $deuxieme_ligne[1] = $troisieme_ligne[1] = $quatrieme_ligne[1] = '';
	$course_iterator=2;

	// TCS BEGIN NNE - 2015/11/25
	if($formation_hours == 'where_teacher_enrolled'){
		$premiere_ligne[2] = "Nombre total d'heure de formation prévues pour la période";
		$deuxieme_ligne[2] = $troisieme_ligne[2] = $quatrieme_ligne[2] = '';
		$course_iterator=3;
	}
	// TCS END NNE - 2015/11/25
	
	//cette variable fait le lien entre un cours et sa colonne dans le csv
	$course_position = array();
	foreach($course_list_array as $course_id => $course_data)
	{
		$course = $DB->get_record('course', array('id' => $course_id));
		$indexation = $DB->get_record('local_indexation', array('courseid' => $course_id));

		$formateurs = '';
		$formateurs_list = get_formateur_from_courseid($course_id);
		foreach($formateurs_list as $current_formateur)
		{
			$formateurs .= $current_formateur;
		}
		$formateurs = ($formateurs == '') ? 'Formateurs non renseignés' : $formateurs;
		$duree = 'Durée non renseignée';
		if($indexation)
		{		
			$duree_dist = (!empty($indexation->tps_a_distance)) ? $indexation->tps_a_distance : 0;
			$duree_pres = (!empty($indexation->tps_en_presence)) ? $indexation->tps_en_presence : 0;
				
			// TCS BEGIN NNE - 2015/11/25
			$duree = 0;
			if($formation_hours == 'checked_by_tutor'){
				$duree = $duree_dist;
			}else{
				$duree = $duree_dist + $duree_pres;
			}
			// TCS END NNE - 2015/11/25
				
			$hours = ($duree != '')? floor($duree / 60) : 0;
			$minutes = ($duree != '')? $duree % 60 : 0;
			if ($duree != 0){
				$duree = $hours.':'.str_pad($minutes, 2, "0", STR_PAD_LEFT);
			}
			else{
				$duree = 'Durée non renseignée';
			}
		}
		$periode = ($course->startdate == '' || $course->startdate == 0 ) ? 'Période non renseignée' : date('d/m/Y', $course->startdate);
		$premiere_ligne[$course_iterator] = $course->fullname;
		$deuxieme_ligne[$course_iterator] = $periode;
		$troisieme_ligne[$course_iterator] = $formateurs;
		$quatrieme_ligne[$course_iterator] = $duree;
		$course_position[$course_id] = $course_iterator;
		$course_iterator++;
	}
	$result_array = array (
			$premiere_ligne,
			$deuxieme_ligne,
			$troisieme_ligne,
			$quatrieme_ligne
		 );
		 
	foreach($courses_user_completions	as $user_id => $courses)
	{
		$new_line = array();
		$user = $DB->get_record('user', array('id' => $user_id));
		$new_line[0] = $user->firstname.' '.$user->lastname;
        $sql = '
            SELECT u.username AS identifiant, uid.data AS code_rne
            FROM {user} u
            INNER JOIN {user_info_data} uid ON (uid.userid = u.id)
            WHERE u.id = :userid 
            AND uid.fieldid = (SELECT id FROM {user_info_field} WHERE shortname = "rne")';
        $user_info_data = $DB->get_record_sql($sql, ['userid' => $user_id]);
		$user_rne = $user_info_data->identifiant;
		$etablissement = $user_info_data->code_rne;
		$new_line[1] = $etablissement->appelation_officielle.' ( '.$etablissement->ville.' )';
		
		// TCS BEGIN NNE - 2015/11/25
		if($formation_hours == 'where_teacher_enrolled'){
			$new_line[2] = 0;
		}
		// TCS BEGIN NNE - 2015/11/25
		
		foreach($courses as $course_id => $progress)
		{
			$duree_dist = 0;
			$duree_pres = 0;
			$duree = 0;
			$display = null;
			$position = $course_position[$course_id];
			$is_complete = $DB->get_record('progress_complete', array('courseid' => $course_id, 'userid' => $user_id));
			$indexation = $DB->get_record('local_indexation', array('courseid' => $course_id));
			if($indexation)
			{
				$duree_dist = (!empty($indexation->tps_a_distance)) ? $indexation->tps_a_distance : 0;
				$duree_pres = (!empty($indexation->tps_en_presence)) ? $indexation->tps_en_presence : 0;
				if($formation_hours == 'where_teacher_enrolled'){
					$new_line[2] += $duree_dist + $duree_pres;
				}
			}
			if((int) $progress < 100)
			{
				if($is_complete)
					$progress = 100;
			}
			if((int) $progress < 100){ 
				if($formation_hours == 'where_teacher_enrolled'){
					$display = $duree_dist + $duree_pres;
					if($display != 0){ 
						$hours = ($display != '')? floor($display / 60) : 0;
						$minutes = ($display != '')? $display % 60 : 0;
						$display = (($hours!=00)||($minutes!=00)) ?  $hours.':'.str_pad($minutes, 2, "0", STR_PAD_LEFT) : '0';
					}
					else{
						$display = "NR";
					}
				}
				else{
					$display = 0;
				}
			}
			else{
				if($indexation)
				{
					// TCS BEGIN NNE - 2015/11/25
					$duree = 0;
					if($formation_hours == 'checked_by_tutor'){
						if($is_complete){
							$duree = $duree_dist;
						}
					}else{
						$duree = $duree_dist + $duree_pres;
					}

					// TCS END NNE - 2015/11/25

					if($duree != 0){ 
							$hours = ($duree != '')? floor($duree / 60) : 0;
							$minutes = ($duree != '')? $duree % 60 : 0;
							$duree = (($hours!=00)||($minutes!=00)) ?  $hours.':'.str_pad($minutes, 2, "0", STR_PAD_LEFT) : '0';
						}
					else{
						if($progress == 100 && $formation_hours == 'checked_by_tutor'){
							$duree = '100%';
						}
						else{
							$duree = 'NR';
						}
					}
				}
				else{
					if($progress == 100 && $formation_hours == 'checked_by_tutor'){
						$duree = '100%';
					}
					else{
						$duree = 'NR';
					}
				}
				$display = $duree;
			}

			$new_line[$position] = $display;
		}
		
		// TCS BEGIN NNE - 2015/11/25
		if($formation_hours == 'where_teacher_enrolled'){
			$duree = $new_line[2];
			$hours = floor($duree / 60);
			$minutes = $duree % 60;
			$duree = (($hours!=00)||($minutes!=00)) ?  $hours.':'.str_pad($minutes, 2, "0", STR_PAD_LEFT) : '0';

			$new_line[2] = $duree;
		}
		// TCS END NNE - 2015/11/25

		$result_array[] = $new_line;
	}

	foreach($result_array as &$line_array)
	{
		foreach($line_array as &$value)
			$value = mb_convert_encoding($value, 'UTF-16LE', 'UTF-8');
	}

	header("Content-type: text/csv");
	header("Content-Disposition: attachment; filename=file.csv");
	header("Pragma: no-cache");
	header("Expires: 0");
	$fp = fopen('php://output', 'w');
	
	foreach ($result_array as $position => $fields) {
		add_missing_field($fields);
		fputcsv($fp, $fields, ';');
		$previousPos = $position;
	}
	fclose($fp);
}

// TCS BEGIN NNE - 2015/11/26
function add_missing_field(&$array){
	ksort($array);
	foreach($array as $k => $v) {
		while($i < $k) {
			// if $i < $k we're missing some keys.
			$array[$i] = '';
			$i ++;
		}
		$i++;
	}
	ksort($array);
}
// TCS END NNE - 2015/11/26

function build_excel($courses_user_completions, $course_list_array, $formation_hours)
{
	GLOBAL $DB, $CFG;
	require_once dirname(__FILE__) . '/excel/Classes/PHPExcel.php';
	$objPHPExcel = new PHPExcel();

	// Set document properties
	$objPHPExcel->getProperties()->setCreator("magistere")
								 ->setLastModifiedBy("magistere")
								 ->setTitle("Supervision des stagiaires")
								 ->setSubject("Supervision")
								 ->setDescription("Suivi des stagiaires")
								 ->setKeywords("")
								 ->setCategory("magistere");
	
	$course_iterator = 2;
	// TCS BEGIN NNE - 2015/11/25
	if($formation_hours == 'where_teacher_enrolled'){
		$objPHPExcel->setActiveSheetIndex(0)
				->setCellValue(getNameFromNumber(2).'1', "Nombre total d'heure de formation prévues pour la période");
		$course_iterator=3;
	}
	// TCS END NNE - 2015/11/25
	
	foreach($course_list_array as $course_id => $course_data)
	{
		$course = $DB->get_record('course', array('id' => $course_id));
		$indexation = $DB->get_record('local_indexation', array('courseid' => $course_id));

		$formateurs = '';
		$formateurs_list = get_formateur_from_courseid($course_id);
		foreach($formateurs_list as $current_formateur)
		{
			$formateurs .= $current_formateur;
		}
		$formateurs = ($formateurs == '') ? 'Formateurs non renseignés' : $formateurs;
		$duree = 'Durée non renseignée';
		if($indexation)
		{
			
			$duree_dist = (!empty($indexation->tps_a_distance)) ? $indexation->tps_a_distance : 0;
			$duree_pres = (!empty($indexation->tps_en_presence)) ? $indexation->tps_en_presence : 0;
			
			// TCS BEGIN NNE - 2015/11/25
			$duree = 0;
			if($formation_hours == 'checked_by_tutor'){
				$duree = $duree_dist;
			}else{
				$duree = $duree_dist + $duree_pres;
			}
			// TCS END NNE - 2015/11/25
			
			$hours = floor($duree / 60);
			$minutes = $duree % 60;
			
			if ($duree != 0){
				$duree = $hours.':'.str_pad($minutes, 2, "0", STR_PAD_LEFT);
			}
			else{
				$duree = 'Durée non renseignée';
			}
		}
		$periode = ($course->startdate == ''|| $course->startdate == 0 ) ? 'Période non renseignée' : date('d/m/Y', $course->startdate);
		$objPHPExcel->setActiveSheetIndex(0)
				->setCellValue(getNameFromNumber($course_iterator).'1', $course->fullname)
				->setCellValue(getNameFromNumber($course_iterator).'2', $periode)
				->setCellValue(getNameFromNumber($course_iterator).'3', $formateurs)
				->setCellValue(getNameFromNumber($course_iterator).'4', $duree);
		$objPHPExcel->getActiveSheet()->getCell(getNameFromNumber($course_iterator).'1')->getHyperlink()->setUrl($CFG->wwwroot.'/course/view.php?id='.$course->id);
		$course_position[$course_id] = $course_iterator;
		$course_iterator++;
	}
	$current_line = 5;
	foreach($courses_user_completions	as $user_id => $courses)
	{
		$new_line = array();
		$user = $DB->get_record('user', array('id' => $user_id));
        $sql = '
            SELECT u.username AS identifiant, uid.data AS code_rne
            FROM {user} u
            INNER JOIN {user_info_data} uid ON (uid.userid = u.id)
            WHERE u.id = :userid 
            AND uid.fieldid = (SELECT id FROM {user_info_field} WHERE shortname = "rne")';
        $user_info_data = $DB->get_record_sql($sql, ['userid' => $user_id]);
        $user_rne = $user_info_data->identifiant;
        $etablissement = $user_info_data->code_rne;


		$objPHPExcel->setActiveSheetIndex(0)
				->setCellValue('A'.$current_line, $user->firstname.' '.$user->lastname)
				->setCellValue('B'.$current_line, $etablissement->appelation_officielle.' ( '.$etablissement->ville.' )');

		$totalWhere_teacher_enrolled = 0;
		foreach($courses as $course_id => $progress)
		{
			$duree_dist = 0;
			$duree_pres = 0;
			$duree = 0;
			$display_progress = null;
			$is_complete = $DB->get_record('progress_complete', array('courseid' => $course_id, 'userid' => $user_id));
			$indexation = $DB->get_record('local_indexation', array('courseid' => $course_id));
			if($indexation)
			{
				$duree_dist = (!empty($indexation->tps_a_distance)) ? $indexation->tps_a_distance : 0;
				$duree_pres = (!empty($indexation->tps_en_presence)) ? $indexation->tps_en_presence : 0;
				if($formation_hours == 'where_teacher_enrolled'){
					$totalWhere_teacher_enrolled += $duree_dist + $duree_pres;
				}
			}
			if($progress < 100)
			{
				if($is_complete)
					$progress = 100;
			}
			if((int) $progress < 100) 
			{
				if($formation_hours == 'where_teacher_enrolled'){
					$display_progress = $duree_dist + $duree_pres;
					if($display_progress != 0){
						$hours = ($display_progress != '')? floor($display_progress / 60) : 0;
						$minutes = ($display_progress != '')? $display_progress % 60 : 0;
						$display_progress = (($hours!=00)||($minutes!=00)) ?  $hours.':'.str_pad($minutes, 2, "0", STR_PAD_LEFT) : '0';
					}
					else{
						$display_progress = "NR";
					}
				}
				else{
					$display_progress = 0;
				}
			}
			else{
				if($indexation)
				{

					// TCS BEGIN NNE - 2015/11/25
					$duree = 0;
					if($formation_hours == 'checked_by_tutor'){
						if($is_complete){
							$duree = $duree_dist;
						}
					}else {
						$duree = $duree_dist + $duree_pres;
					}
						
					// TCS END NNE - 2015/11/25
					if($duree != 0){
						$hours = floor($duree / 60);
						$minutes = $duree % 60;
						$duree = $hours.':'.str_pad($minutes, 2, "0", STR_PAD_LEFT);
					}
					else{
						if($progress == 100 && $formation_hours == 'checked_by_tutor'){
							$duree = '100%';
						}
						else{
							$duree = 'NR';
						}
					}					
				}
				else{
					if($progress == 100 && $formation_hours == 'checked_by_tutor'){
						$duree = '100%';
					}
					else{
						$duree = 'NR';
					}
				}
				$display_progress = $duree;
			}
			$position = $course_position[$course_id];
			$new_line[$position] = $progress.'%';
			$objPHPExcel->setActiveSheetIndex(0)
				->setCellValue(getNameFromNumber($position).$current_line, $display_progress);

		}
		// TCS BEGIN NNE - 2015/11/24
		if($formation_hours == 'where_teacher_enrolled'){
			$hours = floor($totalWhere_teacher_enrolled / 60);
			$minutes = $totalWhere_teacher_enrolled % 60;
			$duree = $hours.':'.str_pad($minutes, 2, "0", STR_PAD_LEFT);

			$objPHPExcel->setActiveSheetIndex(0)
				->setCellValue(getNameFromNumber(2).$current_line, $duree);
		}
		// TCS END NNE - 2015/11/24	
			
		$result_array[] = $new_line;

		$current_line++;
	}




	$objPHPExcel->setActiveSheetIndex(0);

	header('Content-Type: application/vnd.ms-excel');
	header('Content-Disposition: attachment;filename="supervision.xls"');
	header('Cache-Control: max-age=0');
	// If you're serving to IE 9, then the following may be needed
	header('Cache-Control: max-age=1');

	// If you're serving to IE over SSL, then the following may be needed
	header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
	header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
	header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
	header ('Pragma: public'); // HTTP/1.0

	$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
	$objWriter->save('php://output');
	exit;
}


function build_html_table($courses_user_completions, $course_list_array, $formation_hours)
{
		GLOBAL $DB, $CFG;
		
		// définition des lignes du header correspondantes au parcours
		// $t_result = array();
		$p_id[0] = $p_name[0] = $p_date[0] = $p_trainer[0] = $p_duration[0] =  $p_id[1] = $p_name[1] = $p_date[1] = $p_trainer[1] = $p_duration[1] = '';  
		
		// TCS BEGIN NNE - 2015/11/24
		if($formation_hours == 'where_teacher_enrolled'){			
			$p_id[2] = $p_date[2] = $p_trainer[2] = $p_duration[2] = '';
			$p_name[2] = 'Nombre total d\'heure de formation prévues pour la période';
		}
		// TCS END NNE - 2015/11/24
		
		
		//on remplit le header
		foreach($course_list_array as $course_id => $course_data)
		{
			$p_id[ ] = $course_id;
			
			//récupération des info relative au parcours
			$course = $DB->get_record('course', array('id' => $course_id));
			
			//lien vers le parcours
			$p_name[ ] = '<a href="'.$CFG->wwwroot.'/course/view.php?id='.$course->id.'">'.$course->fullname.'</a>';
			
			//date de début du parcours			
			$p_date[ ] =  ($course->startdate == ''|| $course->startdate == 0 ) ? 'Période non renseignée' : date('d/m/Y', $course->startdate);
			
			//récupération des formateurs 			
			$formateurs_list = get_formateur_from_courseid($course_id);
            $formateurs = "";
			foreach($formateurs_list as $current_formateur)
			{
				$formateurs .= $current_formateur;
			}
			$p_trainer[ ] = ($formateurs == '') ? 'Formateurs non renseignés' : $formateurs;
			$formateurs = '';
			
			// course duration
			$indexation = $DB->get_record('local_indexation', array('courseid' => $course_id));
			if($indexation){

				$duree_dist = (!empty($indexation->tps_a_distance)) ? $indexation->tps_a_distance : 0;
				$duree_pres = (!empty($indexation->tps_en_presence)) ? $indexation->tps_en_presence : 0;
				
				// TCS BEGIN NNE - 2015/11/24
				$duree = 0;
				if($formation_hours == 'checked_by_tutor'){
					$duree = $duree_dist;
				}else{
					$duree = $duree_dist + $duree_pres;
				}
				// TCS END NNE - 2015/11/24
				
				$hours = ($duree != '')? floor($duree / 60) : 0;
				$minutes = ($duree != '')? $duree % 60 : 0;
					
				if ($duree != 0){
					$duree = $hours.':'.str_pad($minutes, 2, "0", STR_PAD_LEFT);
				}
				
			}else{
				$duree = '00:00';
			}
			$p_duration[ ] = ($duree != '00:00') ? $duree : 'Durée non renseignée';
			
		}
		
		$result_array = array($p_id, $p_name, $p_date, $p_trainer, $p_duration);

		//users step 
		foreach($courses_user_completions	as $user_id => $courses){
			$new_line = array();
			$user = $DB->get_record('user', array('id' => $user_id));
			$new_line[0] = $user->firstname.' '.$user->lastname;
            $sql = '
            SELECT u.username AS identifiant, uai.code_rne, uai.appelation_officielle, uai.ville
            FROM {user} u
            INNER JOIN {user_info_data} uid ON (uid.userid = u.id)
            INNER JOIN {t_uai} uai ON (uai.code_rne = uid.data) 
            WHERE u.id = :userid 
            AND uid.fieldid = (SELECT id FROM {user_info_field} WHERE shortname = "rne")';
            $user_info_data = $DB->get_record_sql($sql, ['userid' => $user_id]);
            $new_line[1] = "";
            if($user_info_data != null){
                $new_line[1] = $user_info_data->appelation_officielle.' <br> ( '.$user_info_data->ville.' )';
            }
			
			// TCS BEGIN NNE - 2015/11/24
			if($formation_hours == 'where_teacher_enrolled'){
				$new_line[2] = 0;
			}
			// TCS END NNE - 2015/11/24
			
			//for each courses
			foreach($p_id as $position => $selected_courseid){
				// TCS BEGIN NNE - 2015/11/24
				if($formation_hours == 'where_teacher_enrolled' && $position == 2){
					//skip the 3rd column when in 'where_teacher_enrolled' mode
					continue;
				}
				// TCS END NNE - 2015/11/24
				
				if($position == 0 || $position == 1){ continue;}
				$course_result = '-';
				foreach($courses as $user_courseid => $progression ){
					$duree_dist = 0;
					$duree_pres = 0;
					$duree = 0;
					$display = null;
					if($selected_courseid == $user_courseid){
						//processing on progression value 
						$is_complete = $DB->get_record('progress_complete', array('courseid' => $user_courseid, 'userid' => $user_id));
						$indexation = $DB->get_record('local_indexation', array('courseid' => $selected_courseid));
						if($indexation)
						{
							$duree_dist = (!empty($indexation->tps_a_distance)) ? $indexation->tps_a_distance : 0;
							$duree_pres = (!empty($indexation->tps_en_presence)) ? $indexation->tps_en_presence : 0;
							if($formation_hours == 'where_teacher_enrolled'){
								$new_line[2] += $duree_dist + $duree_pres;
							}
						}
						if($progression < 100){
							if($is_complete)
								$progression = 100;
						}						
						if((int) $progression < 100){ 
							if($formation_hours == 'where_teacher_enrolled'){
								$display = $duree_dist + $duree_pres;
								if($display != 0){ 
									$hours = ($display != '')? floor($display / 60) : 0;
									$minutes = ($display != '')? $display % 60 : 0;
									$display = (($hours!=00)||($minutes!=00)) ?  $hours.':'.str_pad($minutes, 2, "0", STR_PAD_LEFT) : '0';
								}
								else{
									$display = "NR";
								}
							}
							else{
								$display = 0;
							}
							
						}else{
							if($indexation)
							{
								// TCS BEGIN NNE - 2015/11/24
								$duree = 0;
								if($formation_hours == 'checked_by_tutor'){
									if($is_complete){
										$duree = $duree_dist;
									}
								} else{
									$duree = $duree_dist + $duree_pres;
								}
								// TCS END NNE - 2015/11/24
								if($duree != 0){ 
									$hours = ($duree != '')? floor($duree / 60) : 0;
									$minutes = ($duree != '')? $duree % 60 : 0;
									$duree = (($hours!=00)||($minutes!=00)) ?  $hours.':'.str_pad($minutes, 2, "0", STR_PAD_LEFT) : '0';
								}
								else{
									if($progression == 100 && $formation_hours == 'checked_by_tutor'){
										$duree = '100%';
									}
									else{
										$duree = 'NR';
									}
								}
							}
							else{
								if($progression == 100 && $formation_hours == 'checked_by_tutor'){
									$duree = '100%';
								}
								else{
									$duree = 'NR';
								}
							}
							$display = $duree;
						}						
						$course_result = $display;
						break;
					} 
				}
				$new_line[] = $course_result;
			}

			// TCS BEGIN NNE - 2015/11/24
			if($formation_hours == 'where_teacher_enrolled'){
				$duree = $new_line[2];
				$hours = floor($duree / 60);
				$minutes = $duree % 60;
				$duree = (($hours!=00)||($minutes!=00)) ?  $hours.':'.str_pad($minutes, 2, "0", STR_PAD_LEFT) : '0';

				$new_line[2] = $duree;
			}
			// TCS END NNE - 2015/11/24			
			$result_array[] = $new_line;
		}
		
		//display setting
		if(!empty($courses_user_completions)) {

			$html_result = '<table id="tableau_supervision_stagiaires">';
			
			// Results Header 	
			// delete of the ID line
			unset($result_array[0]);
			$html_result .='
			<THEAD>
				<tr class="label_form">			
						<h2 style="font-weight:bold;">Résultat</h2>
				</tr>
			</THEAD>';

			$line_counter = 0;
			foreach($result_array as $position => $data) {
				$html_result .= '<tr >';
				$count_line_element = 0;
				foreach($data as $current_data)	{
					if($line_counter <= 3){
						// TCS BEGIN NNE - 2015/11/26
						//cas special pour l'entete du nombre d'heure totale
						if($count_line_element == 2 && $formation_hours == 'where_teacher_enrolled'){
							if($line_counter == 0){
								$html_result .= '<th class="td_line" rowspan=4>'.$current_data.'</th>';
							}
						}else{								
							$class = ($current_data == "")?"cell_vide":"";
							$html_result .= '<th class="td_line '.$class.'">'.$current_data.'</th>';
						}
					}
					else{
						if($count_line_element < 2) {
							$html_result .= '<th class="td_col">'.$current_data.'</th>';
						} else {

							$class = ($current_data === "")?"":"cell_vide";
							$html_result .= '<td class="'.$class.'" >'.$current_data.'</td>';
						}
					}
					$count_line_element ++;
				}
				$html_result .= '</tr>';
				$line_counter ++;
			}
			
			$html_result .= '</table>';
		} else {
			//no results		
			$html_result = '<table id="tableau_supervision_stagiaires">';
			// Results Header 	
			$html_result .='
			<THEAD>
				<tr class="label_form">			
						<h2 style="font-weight:bold;">Résultat</h2>
				</tr>
			</THEAD>';		
			$html_result .= '<tr><td class="label_alert">Aucun résultat ne correspond à vos critères</td></tr>';
			$html_result .= '</table>';
		}
		
		//display
		echo $html_result;		
}

//fonction pour calculer l'intitulé des colonnes du tableur (ab, ac, ad, etc) à partir de leur numéro (a=0, b=1, ab=27, etc)
function getNameFromNumber($num) {
    $numeric = $num % 26;
    $letter = chr(65 + $numeric);
    $num2 = intval($num / 26);
    if ($num2 > 0) {
        return getNameFromNumber($num2 - 1) . $letter;
    } else {
        return $letter;
    }
}

function encodeCSV($value, $key){
    $value = iconv('UTF-8', 'Windows-1251', $value);
}

function get_formateur_from_courseid($courseid)
{

	global $DB;
	$liste_formateurs = array();

	$role_formateur = $DB->get_record('role', array('shortname' => 'formateur'));

	$context = $DB->get_record('context', array('contextlevel' => 50, 'instanceid' => $courseid));
	
	$role_assignments = $DB->get_records('role_assignments', array('contextid' => $context->id, 'roleid' => $role_formateur->id));

	foreach($role_assignments as $current_enrolment)
	{
		$user = $DB->get_record('user', array('id' => $current_enrolment->userid));
		$liste_formateurs[] = $user->firstname.' '.$user->lastname.'<br>';
	}

	return $liste_formateurs;
}
