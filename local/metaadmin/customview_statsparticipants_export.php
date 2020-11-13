<?php

ini_set("mysql.trace_mode", "0");

require_once('../../config.php');

error_reporting(E_ALL);
ini_set("display_errors", 1);

require_once($CFG->dirroot.'/local/magisterelib/databaseConnection.php');
require_once($CFG->dirroot.'/local/metaadmin/lib.php');

$action = required_param('action', PARAM_ALPHA);

if($action == "export") {
	$format = optional_param('format', 'html', PARAM_ALPHA);
	$so = required_param('so', PARAM_TEXT);
    $lastconnmin = required_param('lastconnmin', PARAM_INT);
    $lastconnmax = required_param('lastconnmax', PARAM_INT);
    $userrole = required_param('userrole', PARAM_TEXT);
    $parcoursidentifiant_year = required_param('parcoursidentifiant_year', PARAM_INT);
    $gaia_origine = required_param('gaia_origine', PARAM_INT);
    $parcoursidentifiant_name = required_param('parcoursidentifiant_name', PARAM_TEXT);

    $select_off = optional_param('select_off', 0, PARAM_INT);
    $select_ofp = optional_param('select_ofp',0, PARAM_INT);
    $select_offlocales = optional_param('select_offlocales', 0, PARAM_INT);

	$key = optional_param('key', false, PARAM_TEXT);
	$view_id = required_param('view_id', PARAM_INT);
	$view = get_custom_views_by_id($view_id);
    
    $lastconnmin = $lastconnmin/1000;
    $lastconnmax = $lastconnmax/1000;
    
    $PAGE->set_context(context_system::instance());
    
    $academie = $CFG->academie_name;
    $dataTypeTitle = "";
	$dataTypeValue = "";
	$result = array();
	
	if ($view->display_type == 'bycourse') {
		$dataTypeTitle = get_string('jtheader_courseuid', 'local_metaadmin');
	} else {
		$dataTypeTitle = get_string('jtheader_academy', 'local_metaadmin');
	}
	
    if (has_capability('local/metaadmin:statsparticipants_viewallacademies', context_system::instance()) || $key == $CFG->metaadmin_customview_reports_export_allaca_key) {
		$academies = get_magistere_academy_config();
    	$special_aca = array('reseau-canope','dgesco','efe','ih2ef','dne-foad');
    	if ($view->display_type == 'bycourse') {
			$result = get_view_courses_stats($parcoursidentifiant_year, $gaia_origine, $parcoursidentifiant_name, $userrole, $lastconnmin, $lastconnmax, $view_id,$select_off,$select_offlocales,$select_ofp);
		} else if ($view->display_type == 'bycoursebyaca') {
			foreach($academies as $academy => $daca) {
				if (substr($academy,0,3) != 'ac-') {
					continue;
				}
				if (in_array($academy, $special_aca)) {
					continue;
				}
				$r = get_aca_courses_stats($academy, $parcoursidentifiant_year, $gaia_origine, $parcoursidentifiant_name, $userrole, $lastconnmin, $lastconnmax, $view_id,$select_off,$select_offlocales,$select_ofp);
				if ($r !== false) {
					$jt = array();
					$jt['Result'] = "OK";
					$jt['TotalRecordCount'] = count($r);
					$jt['Records'] = $r;
					$result[$academy] = $jt;
				}
			}
		} else {
			foreach($academies as $academy => $daca) {
				if (substr($academy,0,3) != 'ac-') {
					continue;
				}
				if (in_array($academy, $special_aca)) {
					continue;
				}
				$r = get_aca_stats($academy, $userrole, $parcoursidentifiant_year, $gaia_origine, $parcoursidentifiant_name, $lastconnmin, $lastconnmax, $view_id,$select_off,$select_offlocales,$select_ofp);
				if ($r !== false) {
					$result[] = $r;
				}
			}
			usort($result,'sort_results');
		}
    }
    else if (has_capability('local/metaadmin:statsparticipants_viewownacademy', context_system::instance()) || $key == $CFG->metaadmin_customview_reports_export_ownaca_key) {
        $result = array(get_aca_stats($academie, $userrole, $parcoursidentifiant_year, $gaia_origine, $parcoursidentifiant_name, $lastconnmin, $lastconnmax, $view_id,$select_off,$select_offlocales,$select_ofp));
    } else {
        $result = array();
    }
    
    if ($format == 'html' || $format == 'htmltsv') {
		if ($view->display_type == 'bycoursebyaca') {
			if ($view->trainee_calc == 1) {
				echo '<style type="text/css">#htmlTableExport th,#htmlTableExport td{border:1px solid #000;}</style><table border="1" colspan="1" rowspan="1" id="htmlTableExport"><tr><th>'.get_string('jtheader_academy', 'local_metaadmin').'</th><th>'.get_string('jtheader_courseuid', 'local_metaadmin').'</th><th>'.get_string('jtheader_localhours', 'local_metaadmin').'</th><th>'.get_string('jtheader_distanthours', 'local_metaadmin').'</th><th>'.get_string('jtheader_public1D', 'local_metaadmin').'</th><th>'.get_string('jtheader_prive1D', 'local_metaadmin').'</th><th>'.get_string('jtheader_total1D', 'local_metaadmin').'</th><th>'.get_string('jtheader_total1Dh', 'local_metaadmin').'</th><th>'.get_string('jtheader_total1Dj', 'local_metaadmin').'</th><th>'.get_string('jtheader_public2D', 'local_metaadmin').'</th><th>'.get_string('jtheader_prive2D', 'local_metaadmin').'</th><th>'.get_string('jtheader_total2D', 'local_metaadmin').'</th><th>'.get_string('jtheader_total2Dh', 'local_metaadmin').'</th><th>'.get_string('jtheader_total2Dj', 'local_metaadmin').'</th><th>'.get_string('jtheader_other', 'local_metaadmin').'</th><th>'.get_string('jtheader_otherh', 'local_metaadmin').'</th><th>'.get_string('jtheader_otherj', 'local_metaadmin').'</th><th>'.get_string('jtheader_total', 'local_metaadmin').'</th><th>'.get_string('jtheader_totalh', 'local_metaadmin').'</th><th>'.get_string('jtheader_totalj', 'local_metaadmin').'</th></tr>';
				foreach ($result as $aca=>$acadata) {
					foreach ($acadata['Records'] as $row) {
						echo '<tr><td>'.$aca.'</td><td>'.$row->courseuid.'</td><td>'.$row->localhours.'</td><td>'.$row->distanthours.'</td><td>'.$row->public1D.'</td><td>'.$row->prive1D.'</td><td>'.$row->total1D.'</td><td>'.$row->total1Dh.'</td><td>'.$row->total1Dj.'</td><td>'.$row->public2D.'</td><td>'.$row->prive2D.'</td><td>'.$row->total2D.'</td><td>'.$row->total2Dh.'</td><td>'.$row->total2Dj.'</td><td>'.$row->other.'</td><td>'.$row->otherh.'</td><td>'.$row->otherj.'</td><td>'.$row->total.'</td><td>'.$row->totalh.'</td><td>'.$row->totalj.'</td></tr>';
					}
				}
				echo '</table>';
			} else {
				echo '<style type="text/css">#htmlTableExport th,#htmlTableExport td{border:1px solid #000;}</style><table border="1" colspan="1" rowspan="1" id="htmlTableExport"><tr><th>'.get_string('jtheader_academy', 'local_metaadmin').'</th><th>'.get_string('jtheader_courseuid', 'local_metaadmin').'</th><th>'.get_string('jtheader_public1D', 'local_metaadmin').'</th><th>'.get_string('jtheader_prive1D', 'local_metaadmin').'</th><th>'.get_string('jtheader_total1D', 'local_metaadmin').'</th><th>'.get_string('jtheader_public2D', 'local_metaadmin').'</th><th>'.get_string('jtheader_prive2D', 'local_metaadmin').'</th><th>'.get_string('jtheader_total2D', 'local_metaadmin').'</th><th>'.get_string('jtheader_other', 'local_metaadmin').'</th><th>'.get_string('jtheader_total', 'local_metaadmin').'</th></tr>';
				foreach ($result as $aca=>$acadata) {
					foreach ($acadata['Records'] as $row) {
						echo '<tr><td>'.$aca.'</td><td>'.$row->courseuid.'</td><td>'.$row->public1D.'</td><td>'.$row->prive1D.'</td><td>'.$row->total1D.'</td><td>'.$row->public2D.'</td><td>'.$row->prive2D.'</td><td>'.$row->total2D.'</td><td>'.$row->other.'</td><td>'.$row->total.'</td></tr>';
					}
				}
				echo '</table>';
			}
		} else {
			if ($view->trainee_calc == 1) {
				echo '<style type="text/css">#htmlTableExport th,#htmlTableExport td{border:1px solid #000;}</style><table border="1" colspan="1" rowspan="1" id="htmlTableExport"><tr><th>'.$dataTypeTitle.'</th><th>'.get_string('jtheader_localhours', 'local_metaadmin').'</th><th>'.get_string('jtheader_distanthours', 'local_metaadmin').'</th><th>'.get_string('jtheader_public1D', 'local_metaadmin').'</th><th>'.get_string('jtheader_prive1D', 'local_metaadmin').'</th><th>'.get_string('jtheader_total1D', 'local_metaadmin').'</th><th>'.get_string('jtheader_total1Dh', 'local_metaadmin').'</th><th>'.get_string('jtheader_total1Dj', 'local_metaadmin').'</th><th>'.get_string('jtheader_public2D', 'local_metaadmin').'</th><th>'.get_string('jtheader_prive2D', 'local_metaadmin').'</th><th>'.get_string('jtheader_total2D', 'local_metaadmin').'</th><th>'.get_string('jtheader_total2Dh', 'local_metaadmin').'</th><th>'.get_string('jtheader_total2Dj', 'local_metaadmin').'</th><th>'.get_string('jtheader_other', 'local_metaadmin').'</th><th>'.get_string('jtheader_otherh', 'local_metaadmin').'</th><th>'.get_string('jtheader_otherj', 'local_metaadmin').'</th><th>'.get_string('jtheader_total', 'local_metaadmin').'</th><th>'.get_string('jtheader_totalh', 'local_metaadmin').'</th><th>'.get_string('jtheader_totalj', 'local_metaadmin').'</th></tr>';
					
				foreach ($result as $row) {
					if ($view->display_type == 'bycourse') {
						$dataTypeValue = $row->courseuid;
					} else {
						$dataTypeValue = $row->academy;
					}
					echo '<tr><td>'.$dataTypeValue.'</td><td>'.$row->localhours.'</td><td>'.$row->distanthours.'</td><td>'.$row->public1D.'</td><td>'.$row->prive1D.'</td><td>'.$row->total1D.'</td><td>'.$row->total1Dh.'</td><td>'.$row->total1Dj.'</td><td>'.$row->public2D.'</td><td>'.$row->prive2D.'</td><td>'.$row->total2D.'</td><td>'.$row->total2Dh.'</td><td>'.$row->total2Dj.'</td><td>'.$row->other.'</td><td>'.$row->otherh.'</td><td>'.$row->otherj.'</td><td>'.$row->total.'</td><td>'.$row->totalh.'</td><td>'.$row->totalj.'</td></tr>';
				}
				echo '</table>';
			} else {
				echo '<style type="text/css">#htmlTableExport th,#htmlTableExport td{border:1px solid #000;}</style><table border="1" colspan="1" rowspan="1" id="htmlTableExport"><tr><th>'.$dataTypeTitle.'</th><th>'.get_string('jtheader_public1D', 'local_metaadmin').'</th><th>'.get_string('jtheader_prive1D', 'local_metaadmin').'</th><th>'.get_string('jtheader_total1D', 'local_metaadmin').'</th><th>'.get_string('jtheader_public2D', 'local_metaadmin').'</th><th>'.get_string('jtheader_prive2D', 'local_metaadmin').'</th><th>'.get_string('jtheader_total2D', 'local_metaadmin').'</th><th>'.get_string('jtheader_other', 'local_metaadmin').'</th><th>'.get_string('jtheader_total', 'local_metaadmin').'</th></tr>';
				
				foreach ($result as $row) {
					if ($view->display_type == 'bycourse') {
						$dataTypeValue = $row->courseuid;
					} else {
						$dataTypeValue = $row->academy;
					}
					echo '<tr><td>'.$dataTypeValue.'</td><td>'.$row->public1D.'</td><td>'.$row->prive1D.'</td><td>'.$row->total1D.'</td><td>'.$row->public2D.'</td><td>'.$row->prive2D.'</td><td>'.$row->total2D.'</td><td>'.$row->other.'</td><td>'.$row->total.'</td></tr>';
				}
				echo '</table>';
			}
		}
    }
    
    if ($format == 'htmltsv') {
    	echo '######';
    }
    
    if ($format == 'tsv' || $format == 'htmltsv') {
		if ($view->display_type == 'bycoursebyaca') {
			if ($view->trainee_calc == 1) {
				echo get_string('jtheader_academy', 'local_metaadmin')."\t".get_string('jtheader_courseuid', 'local_metaadmin')."\t".get_string('jtheader_localhours', 'local_metaadmin')."\t".get_string('jtheader_distanthours', 'local_metaadmin')."\t".get_string('jtheader_public1D', 'local_metaadmin')."\t".get_string('jtheader_prive1D', 'local_metaadmin')."\t".get_string('jtheader_total1D', 'local_metaadmin')."\t".get_string('jtheader_total1Dh', 'local_metaadmin')."\t".get_string('jtheader_total1Dj', 'local_metaadmin')."\t".get_string('jtheader_public2D', 'local_metaadmin')."\t".get_string('jtheader_prive2D', 'local_metaadmin')."\t".get_string('jtheader_total2D', 'local_metaadmin')."\t".get_string('jtheader_total2Dh', 'local_metaadmin')."\t".get_string('jtheader_total2Dj', 'local_metaadmin')."\t".get_string('jtheader_other', 'local_metaadmin')."\t".get_string('jtheader_otherh', 'local_metaadmin')."\t".get_string('jtheader_otherj', 'local_metaadmin')."\t".get_string('jtheader_total', 'local_metaadmin')."\t".get_string('jtheader_totalh', 'local_metaadmin')."\t".get_string('jtheader_totalj', 'local_metaadmin')."\r\n";
			
				foreach ($result as $aca=>$acadata) {
					foreach ($acadata['Records'] as $row) {
						echo $aca."\t".$row->courseuid."\t".$row->localhours."\t".$row->distanthours."\t".$row->public1D."\t".$row->prive1D."\t".$row->total1D."\t".$row->total1Dh."\t".$row->total1Dj."\t".$row->public2D."\t".$row->prive2D."\t".$row->total2D."\t".$row->total2Dh."\t".$row->total2Dj."\t".$row->other."\t".$row->otherh."\t".$row->otherj."\t".$row->total."\t".$row->totalh."\t".$row->totalj."\n";
					}
				}
			} else {
				echo get_string('jtheader_academy', 'local_metaadmin')."\t".get_string('jtheader_courseuid', 'local_metaadmin')."\t".get_string('jtheader_public1D', 'local_metaadmin')."\t".get_string('jtheader_prive1D', 'local_metaadmin')."\t".get_string('jtheader_total1D', 'local_metaadmin')."\t".get_string('jtheader_public2D', 'local_metaadmin')."\t".get_string('jtheader_prive2D', 'local_metaadmin')."\t".get_string('jtheader_total2D', 'local_metaadmin')."\t".get_string('jtheader_other', 'local_metaadmin')."\t".get_string('jtheader_total', 'local_metaadmin')."\r\n";
				
				foreach ($result as $aca=>$acadata) {
					foreach ($acadata['Records'] as $row) {
						echo $aca."\t".$row->courseuid."\t".$row->public1D."\t".$row->prive1D."\t".$row->total1D."\t".$row->public2D."\t".$row->prive2D."\t".$row->total2D."\t".$row->other."\t".$row->total."\n";
					}
				}
			}
		} else {
			if ($view->trainee_calc == 1) {
				echo $dataTypeTitle."\t".get_string('jtheader_localhours', 'local_metaadmin')."\t".get_string('jtheader_distanthours', 'local_metaadmin')."\t".get_string('jtheader_public1D', 'local_metaadmin')."\t".get_string('jtheader_prive1D', 'local_metaadmin')."\t".get_string('jtheader_total1D', 'local_metaadmin')."\t".get_string('jtheader_total1Dh', 'local_metaadmin')."\t".get_string('jtheader_total1Dj', 'local_metaadmin')."\t".get_string('jtheader_public2D', 'local_metaadmin')."\t".get_string('jtheader_prive2D', 'local_metaadmin')."\t".get_string('jtheader_total2D', 'local_metaadmin')."\t".get_string('jtheader_total2Dh', 'local_metaadmin')."\t".get_string('jtheader_total2Dj', 'local_metaadmin')."\t".get_string('jtheader_other', 'local_metaadmin')."\t".get_string('jtheader_otherh', 'local_metaadmin')."\t".get_string('jtheader_otherj', 'local_metaadmin')."\t".get_string('jtheader_total', 'local_metaadmin')."\t".get_string('jtheader_totalh', 'local_metaadmin')."\t".get_string('jtheader_totalj', 'local_metaadmin')."\r\n";
			
				foreach($result as $row) {
					if ($view->display_type == 'bycourse') {
						$dataTypeValue = $row->courseuid;
					} else {
						$dataTypeValue = $row->academy;
					}
					echo $dataTypeValue."\t".$row->localhours."\t".$row->distanthours."\t".$row->public1D."\t".$row->prive1D."\t".$row->total1D."\t".$row->total1Dh."\t".$row->total1Dj."\t".$row->public2D."\t".$row->prive2D."\t".$row->total2D."\t".$row->total2Dh."\t".$row->total2Dj."\t".$row->other."\t".$row->otherh."\t".$row->otherj."\t".$row->total."\t".$row->totalh."\t".$row->totalj."\n";
				}
			} else {
				echo $dataTypeTitle."\t".get_string('jtheader_public1D', 'local_metaadmin')."\t".get_string('jtheader_prive1D', 'local_metaadmin')."\t".get_string('jtheader_total1D', 'local_metaadmin')."\t".get_string('jtheader_public2D', 'local_metaadmin')."\t".get_string('jtheader_prive2D', 'local_metaadmin')."\t".get_string('jtheader_total2D', 'local_metaadmin')."\t".get_string('jtheader_other', 'local_metaadmin')."\t".get_string('jtheader_total', 'local_metaadmin')."\r\n";
			
				foreach($result as $row) {
					if (!is_object($row)) {
						continue;
					}
					if ($view->display_type == 'bycourse') {
						$dataTypeValue = $row->courseuid;
					} else {
						$dataTypeValue = $row->academy;
					}
					echo $dataTypeValue."\t".$row->public1D."\t".$row->prive1D."\t".$row->total1D."\t".$row->public2D."\t".$row->prive2D."\t".$row->total2D."\t".$row->other."\t".$row->total."\n";
				}
			}
		}
    }
    
    if ($format == 'csv') {

		header('Content-Type: application/octet-stream');
		header("Content-Transfer-Encoding: Binary");
		header('Content-disposition: attachment; filename="file.csv"');
		if ($view->display_type == 'bycoursebyaca') {
			if ($view->trainee_calc == 1) {
				echo get_string('jtheader_academy', 'local_metaadmin').",".get_string('jtheader_courseuid', 'local_metaadmin').",".get_string('jtheader_localhours', 'local_metaadmin').','.get_string('jtheader_distanthours', 'local_metaadmin').','.get_string('jtheader_public1D', 'local_metaadmin').','.get_string('jtheader_prive1D', 'local_metaadmin').','.get_string('jtheader_total1D', 'local_metaadmin').','.get_string('jtheader_total1Dh', 'local_metaadmin').','.get_string('jtheader_total1Dj', 'local_metaadmin').','.get_string('jtheader_public2D', 'local_metaadmin').','.get_string('jtheader_prive2D', 'local_metaadmin').','.get_string('jtheader_total2D', 'local_metaadmin').','.get_string('jtheader_total2Dh', 'local_metaadmin').','.get_string('jtheader_total2Dj', 'local_metaadmin').','.get_string('jtheader_other', 'local_metaadmin').','.get_string('jtheader_otherh', 'local_metaadmin').','.get_string('jtheader_otherj', 'local_metaadmin').','.get_string('jtheader_total', 'local_metaadmin').','.get_string('jtheader_totalh', 'local_metaadmin').','.get_string('jtheader_totalj', 'local_metaadmin')."\r\n";
			
				foreach ($result as $aca=>$acadata) {
					foreach ($acadata['Records'] as $row) {
						echo $aca.','.$row->courseuid.','.$row->localhours.','.$row->distanthours.','.$row->public1D.','.$row->prive1D.','.$row->total1D.','.$row->total1Dh.','.$row->total1Dj.','.$row->public2D.','.$row->prive2D.','.$row->total2D.','.$row->total2Dh.','.$row->total2Dj.','.$row->other.','.$row->otherh.','.$row->otherj.','.$row->total.','.$row->totalh.','.$row->totalj."\n";
					}
				}
			} else {
				echo get_string('jtheader_academy', 'local_metaadmin').",".get_string('jtheader_courseuid', 'local_metaadmin').",".get_string('jtheader_public1D', 'local_metaadmin').','.get_string('jtheader_prive1D', 'local_metaadmin').','.get_string('jtheader_total1D', 'local_metaadmin').','.get_string('jtheader_public2D', 'local_metaadmin').','.get_string('jtheader_prive2D', 'local_metaadmin').','.get_string('jtheader_total2D', 'local_metaadmin').','.get_string('jtheader_other', 'local_metaadmin').','.get_string('jtheader_total', 'local_metaadmin')."\r\n";
			
				foreach ($result as $aca=>$acadata) {
					foreach ($acadata['Records'] as $row) {
						echo $aca.','.$row->courseuid.','.$row->public1D.','.$row->prive1D.','.$row->total1D.','.$row->public2D.','.$row->prive2D.','.$row->total2D.','.$row->other.','.$row->total."\n";
					}
				}
			}
		} else {
			if ($view->trainee_calc == 1) {
				echo $dataTypeTitle.','.get_string('jtheader_localhours', 'local_metaadmin').','.get_string('jtheader_distanthours', 'local_metaadmin').','.get_string('jtheader_public1D', 'local_metaadmin').','.get_string('jtheader_prive1D', 'local_metaadmin').','.get_string('jtheader_total1D', 'local_metaadmin').','.get_string('jtheader_total1Dh', 'local_metaadmin').','.get_string('jtheader_total1Dj', 'local_metaadmin').','.get_string('jtheader_public2D', 'local_metaadmin').','.get_string('jtheader_prive2D', 'local_metaadmin').','.get_string('jtheader_total2D', 'local_metaadmin').','.get_string('jtheader_total2Dh', 'local_metaadmin').','.get_string('jtheader_total2Dj', 'local_metaadmin').','.get_string('jtheader_other', 'local_metaadmin').','.get_string('jtheader_otherh', 'local_metaadmin').','.get_string('jtheader_otherj', 'local_metaadmin').','.get_string('jtheader_total', 'local_metaadmin').','.get_string('jtheader_totalh', 'local_metaadmin').','.get_string('jtheader_totalj', 'local_metaadmin')."\r\n";
				
				foreach($result as $row) {
					if (!is_object($row))
					{
						continue;
					}
					if ($view->display_type == 'bycourse') {
						$dataTypeValue = $row->courseuid;
					} else {
						$dataTypeValue = $row->academy;
					}
					echo $dataTypeValue.','.$row->localhours.','.$row->distanthours.','.$row->public1D.','.$row->prive1D.','.$row->total1D.','.$row->total1Dh.','.$row->total1Dj.','.$row->public2D.','.$row->prive2D.','.$row->total2D.','.$row->total2Dh.','.$row->total2Dj.','.$row->other.','.$row->otherh.','.$row->otherj.','.$row->total.','.$row->totalh.','.$row->totalj."\n";
				}
			} else {
				echo $dataTypeTitle.','.get_string('jtheader_public1D', 'local_metaadmin').','.get_string('jtheader_prive1D', 'local_metaadmin').','.get_string('jtheader_total1D', 'local_metaadmin').','.get_string('jtheader_public2D', 'local_metaadmin').','.get_string('jtheader_prive2D', 'local_metaadmin').','.get_string('jtheader_total2D', 'local_metaadmin').','.get_string('jtheader_other', 'local_metaadmin').','.get_string('jtheader_total', 'local_metaadmin')."\r\n";
				foreach($result as $row) {
					if ($view->display_type == 'bycourse') {
						$dataTypeValue = $row->courseuid;
					} else {
						$dataTypeValue = $row->academy;
					}
					echo $dataTypeValue.','.$row->public1D.','.$row->prive1D.','.$row->total1D.','.$row->public2D.','.$row->prive2D.','.$row->total2D.','.$row->other.','.$row->total."\r\n";
				}
			}
		}
    }
}

/**
 * @param $a
 * @param $b
 * @return int
 */
function sort_results($a, $b) {
	global $so;
	$orders = explode(' ', $so);
	$order_field = $orders[0];
	$order_asc = ($orders[1]=='DESC'?false:true);
	
	if ($a->{$order_field} == $b->{$order_field}) {
		return 0;
	}
	
	if ($order_asc) {
		return ($a->{$order_field} < $b->{$order_field}? -1:1);
	} else {
		return ($a->{$order_field} > $b->{$order_field}? -1:1);
	}
}
