<?php

//echo '{"Result":"OK","TotalRecordCount":50,"Records":[{"Id":12,"Name":"Laurence Wayne","Age":15,"RecordDate":"\/Date(1446674400000)\/"},{"Id":13,"Name":"Laurence Wayne","Age":15,"RecordDate":"\/Date(1446674400000)\/"},{"Id":14,"Name":"Laurence Wayne","Age":15,"RecordDate":"\/Date(1446674400000)\/"},{"Id":15,"Name":"Laurence Wayne","Age":15,"RecordDate":"\/Date(1446674400000)\/"},{"Id":16,"Name":"Laurence Wayne","Age":15,"RecordDate":"\/Date(1446674400000)\/"},{"Id":17,"Name":"Laurence Wayne","Age":15,"RecordDate":"\/Date(1446674400000)\/"},{"Id":18,"Name":"Laurence Wayne","Age":15,"RecordDate":"\/Date(1446674400000)\/"},{"Id":19,"Name":"Laurence Wayne","Age":15,"RecordDate":"\/Date(1446674400000)\/"},{"Id":20,"Name":"Laurence Wayne","Age":15,"RecordDate":"\/Date(1446674400000)\/"},{"Id":21,"Name":"Laurence Wayne","Age":15,"RecordDate":"\/Date(1446674400000)\/"},{"Id":22,"Name":"Laurence Wayne","Age":15,"RecordDate":"\/Date(1446674400000)\/"}]}';

ini_set("mysql.trace_mode", "0");

require_once('../../config.php');
global $DB, $PAGE, $OUTPUT, $SESSION, $CFG;

require_once($CFG->dirroot.'/local/magisterelib/databaseConnection.php');

require_once($CFG->dirroot.'/local/metaadmin/lib.php');

$action = required_param('action', PARAM_ALPHA);


if($action == "list")
{
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
    	$result = array(get_aca_stats($academie, $userrole, $parcoursidentifiant_year, $gaia_origine, $parcoursidentifiant_name, $lastconnmin, $lastconnmax,false,$select_no_pub,$select_off,$select_offlocales,$select_ofp)); //, $lastconnmax));
    }else{
    	$result = array();
    }

    
    //Return result to jTable
    $jTableResult = array();
    $jTableResult['Result'] = "OK";
    $jTableResult['TotalRecordCount'] = count($result);
    $jTableResult['Records'] = $result;

    print json_encode($jTableResult);
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
