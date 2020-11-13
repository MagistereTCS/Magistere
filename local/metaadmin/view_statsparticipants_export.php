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

    $parcoursidentifiant_year = required_param('parcoursidentifiant_year', PARAM_INT);
    $gaia_origine = required_param('gaia_origine', PARAM_INT);
    $parcoursidentifiant_name = required_param('parcoursidentifiant_name', PARAM_TEXT);
    
    $lastconnmin = $lastconnmin/1000;
    $lastconnmax = $lastconnmax/1000;
    
    //require_login($course, false);
    
    $PAGE->set_context(context_system::instance());
    
    $academie = $CFG->academie_name;
    $result = array();
    
    if (has_capability('local/metaadmin:statsparticipants_viewallacademies', context_system::instance()))
    {
    	$academies = get_magistere_academy_config();
    	
    	$special_aca = array('reseau-canope','dgesco','efe','ih2ef','dne-foad');
    	
    	$result2 = array();
    	foreach($academies as $academy=>$daca)
    	{
    		if (substr($academy,0,3) != 'ac-')
    		{
    			continue;
    		}
    		
    		if (in_array($academy,$special_aca))
    		{
    			/*
    			$res = get_aca_stats($academy, $userrole, $parcoursidentifiant_year, $gaia_origine, $parcoursidentifiant_name, $lastconnmin, $lastconnmax);
    			
    			foreach($res as $key=>$value)
    			{
    				$result3->{$key} += $value;
    			}
    			
    			$result3->academy = 'Ministère (000)';
    			*/
    			continue;
    		}
    	
            $r = get_aca_stats($academy, $userrole, $parcoursidentifiant_year, $gaia_origine, $parcoursidentifiant_name, $lastconnmin, $lastconnmax,false,$select_off,$select_offlocales,$select_ofp,$select_no_pub);

            if ($r !== false)
    		{
    			$result2[] = $r;
    		}
    	}
    	$result = $result2;
    	
    	usort($result,'sort_results');
    }
    else if (has_capability('local/metaadmin:statsparticipants_viewownacademy', context_system::instance()))
    {
    	$r = get_aca_stats($academie, $userrole, $parcoursidentifiant_year, $gaia_origine, $parcoursidentifiant_name, $lastconnmin, $lastconnmax,false,$select_no_pub,$select_off,$select_offlocales,$select_ofp);
    	
    	if($r){
    		$result = array(get_aca_stats($academie, $userrole, $parcoursidentifiant_year, $gaia_origine, $parcoursidentifiant_name, $lastconnmin, $lastconnmax,false,$select_no_pub,$select_off,$select_offlocales,$select_ofp));
    	}
    }
    
    if ($format == 'html' || $format == 'htmltsv')
    {

	    echo '<style type="text/css">#htmlTableExport th,#htmlTableExport td{border:1px solid #000;}</style><table border="1" colspan="1" rowspan="1" id="htmlTableExport"><tr><th>'.get_string('jtheader_academy', 'local_metaadmin').'</td></th><th>'.get_string('jtheader_public1D', 'local_metaadmin').'</th><th>'.get_string('jtheader_prive1D', 'local_metaadmin').'</th><th>'.get_string('jtheader_total1D', 'local_metaadmin').'</th><th>'.get_string('jtheader_public2D', 'local_metaadmin').'</th><th>'.get_string('jtheader_prive2D', 'local_metaadmin').'</th><th>'.get_string('jtheader_total2D', 'local_metaadmin').'</th><th>'.get_string('jtheader_other', 'local_metaadmin').'</th><th>'.get_string('jtheader_total', 'local_metaadmin').'</th></tr>';
	    
	    // print table
	    foreach($result as $row)
	    {
	    	echo '<tr><td>'.$row->academy.'</td><td>'.$row->public1D.'</td><td>'.$row->prive1D.'</td><td>'.$row->total1D.'</td><td>'.$row->public2D.'</td><td>'.$row->prive2D.'</td><td>'.$row->total2D.'</td><td>'.$row->other.'</td><td>'.$row->total.'</td></tr>';
	    }
	    
	    echo '</table>';
    }
    
    if ($format == 'htmltsv')
    {
    	echo '######';
    }
    
    if ($format == 'tsv' || $format == 'htmltsv'){
	    echo get_string('jtheader_academy', 'local_metaadmin')."\t".get_string('jtheader_public1D', 'local_metaadmin')."\t".get_string('jtheader_prive1D', 'local_metaadmin')."\t".get_string('jtheader_total1D', 'local_metaadmin')."\t".get_string('jtheader_public2D', 'local_metaadmin')."\t".get_string('jtheader_prive2D', 'local_metaadmin')."\t".get_string('jtheader_total2D', 'local_metaadmin')."\t".get_string('jtheader_other', 'local_metaadmin')."\t".get_string('jtheader_total', 'local_metaadmin')."\r\n";
	    
	    // print table
	    foreach($result as $row)
	    {
	    	echo $row->academy."\t".$row->public1D."\t".$row->prive1D."\t".$row->total1D."\t".$row->public2D."\t".$row->prive2D."\t".$row->total2D."\t".$row->other."\t".$row->total."\r\n";
	    }
    }
    
    if ($format == 'csv')
    {
    	header('Content-Type: application/octet-stream');
    	header("Content-Transfer-Encoding: Binary");
    	header('Content-disposition: attachment; filename="file.csv"');
    	
    	echo get_string('jtheader_academy', 'local_metaadmin').','.get_string('jtheader_public1D', 'local_metaadmin').','.get_string('jtheader_prive1D', 'local_metaadmin').','.get_string('jtheader_total1D', 'local_metaadmin').','.get_string('jtheader_public2D', 'local_metaadmin').','.get_string('jtheader_prive2D', 'local_metaadmin').','.get_string('jtheader_total2D', 'local_metaadmin').','.get_string('jtheader_other', 'local_metaadmin').','.get_string('jtheader_total', 'local_metaadmin')."\r\n";
    	 
    	// print table
    	foreach($result as $row)
    	{
    		echo $row->academy.','.$row->public1D.','.$row->prive1D.','.$row->total1D.','.$row->public2D.','.$row->prive2D.','.$row->total2D.','.$row->other.','.$row->total."\r\n";
    	}
    }
}


function sort_results($a, $b)
{
	global $so;
	
	$orders = explode(' ',$so);
	$order_field = $orders[0];
	$order_asc = ($orders[1]=='DESC'?false:true);
	
	if ($a->{$order_field} == $b->{$order_field})
	{
		return 0;
	}
	
	if ($order_asc)
	{
		return ($a->{$order_field} < $b->{$order_field}? -1:1);
	}
	else{
		return ($a->{$order_field} > $b->{$order_field}? -1:1);
	}
}
