<?php

//echo '{"Result":"OK","TotalRecordCount":50,"Records":[{"Id":12,"Name":"Laurence Wayne","Age":15,"RecordDate":"\/Date(1446674400000)\/"},{"Id":13,"Name":"Laurence Wayne","Age":15,"RecordDate":"\/Date(1446674400000)\/"},{"Id":14,"Name":"Laurence Wayne","Age":15,"RecordDate":"\/Date(1446674400000)\/"},{"Id":15,"Name":"Laurence Wayne","Age":15,"RecordDate":"\/Date(1446674400000)\/"},{"Id":16,"Name":"Laurence Wayne","Age":15,"RecordDate":"\/Date(1446674400000)\/"},{"Id":17,"Name":"Laurence Wayne","Age":15,"RecordDate":"\/Date(1446674400000)\/"},{"Id":18,"Name":"Laurence Wayne","Age":15,"RecordDate":"\/Date(1446674400000)\/"},{"Id":19,"Name":"Laurence Wayne","Age":15,"RecordDate":"\/Date(1446674400000)\/"},{"Id":20,"Name":"Laurence Wayne","Age":15,"RecordDate":"\/Date(1446674400000)\/"},{"Id":21,"Name":"Laurence Wayne","Age":15,"RecordDate":"\/Date(1446674400000)\/"},{"Id":22,"Name":"Laurence Wayne","Age":15,"RecordDate":"\/Date(1446674400000)\/"}]}';

ini_set("mysql.trace_mode", "0");

require_once('../../config.php');
global $DB, $PAGE, $OUTPUT, $SESSION, $CFG;

error_reporting(E_ALL);
ini_set("display_errors", 1);

require_once($CFG->dirroot.'/local/magisterelib/databaseConnection.php');

require_once($CFG->dirroot.'/local/metaadmin/lib.php');

$action = required_param('action', PARAM_ALPHA);

if($action == "export")
{
	$format = optional_param('format', 'html', PARAM_ALPHA);
	
	$so = required_param('so', PARAM_TEXT);
	
    $lastconnmin = required_param('lastconnmin', PARAM_INT);
    $lastconnmax = required_param('lastconnmax', PARAM_INT);
    $userrole = required_param('userrole', PARAM_TEXT);

    $select_no_pub = optional_param('select_no_pub', 0, PARAM_INT);
    $select_off = optional_param('select_off', 0, PARAM_INT);
    $select_offlocales = optional_param('select_offlocales', 0, PARAM_INT);
    $select_ofp = optional_param('select_ofp',0, PARAM_INT);
    
    $lastconnmin = $lastconnmin/1000;
    $lastconnmax = $lastconnmax/1000;

	$parcoursidentifiant_year = required_param('parcoursidentifiant_year', PARAM_INT);
    $gaia_origine = required_param('gaia_origine', PARAM_INT);
    $parcoursidentifiant_name = required_param('parcoursidentifiant_name', PARAM_TEXT);
	
    $PAGE->set_context(context_system::instance());
    
    $academie = $CFG->academie_name;

	$result = get_aca_stats_per_academy($userrole, $parcoursidentifiant_year, $gaia_origine, $parcoursidentifiant_name, $lastconnmin, $lastconnmax,$select_no_pub,$select_off,$select_offlocales,$select_ofp);
/*
	print_r(($result === false ? 'false' : 'true'));
	die;
	*/
	$sortaca = $CFG->academylist;
	unset($sortaca['frontal']);
	
	$header = array();
	foreach($sortaca as $aca => $data){
		$header[] = $data['name'];
	}
	
	$content = array();
	if($result != false){
		foreach($result as $row)
		{
			$r = array($row->name);
			
			foreach($sortaca as $key => $data){
				$r[] = (isset($row->{$key}) ? $row->{$key} : '');
			}
			
			$content[] = $r;
		}
	}
	
	
    if ($format == 'html' || $format == 'htmltsv')
    {

	    echo '<style type="text/css">#htmlTableExport th,#htmlTableExport td{border:1px solid #000; white-space: nowrap;}</style>';
		
		echo '<table border="1" colspan="1" rowspan="1" id="htmlTableExport">';
	    
		echo '<tr><th></th><th>'.join('</th><th>', $header).'</th></tr>';
		
		foreach($content as $row){
			echo '<tr><td>'.join('</td><td>', $row).'</td></tr>';
		}
	    
	    echo '</table>';
    }
    
    if ($format == 'htmltsv')
    {
    	echo '######';
    }
    
    if ($format == 'tsv' || $format == 'htmltsv'){
		echo "\t".join("\t", $header) . "\n";
		foreach($content as $row){
			echo join("\t", $row) . "\n";
		}
    }
    
    if ($format == 'csv')
    {
    	header('Content-Type: application/octet-stream');
    	header("Content-Transfer-Encoding: Binary");
    	header('Content-disposition: attachment; filename="file.csv"');
    	
		echo ','.join(",", $header) . "\n";
		
		foreach($content as $row){
			echo join(",", $row) . "\n";
		}
    }
}
