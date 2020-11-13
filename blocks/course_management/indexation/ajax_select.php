<?php
	require_once('../../../config.php');
	global $DB;
	
	
	if(isset($_POST['value'])){
		
		switch ($_POST['value'])
		{
			case "dgesco":
			case "ih2ef":
			case "reseau-canope":
			case "ife":
			case "espe":
			case "irem":
            case "dne-foad":
			case "autre":
				$code_gaia = $DB->get_record('origine_gaia', array('name'=> $_POST['value']));
				break;
			case $_POST['value'] > 0:
				$index_academy = $DB->get_record('t_academie', array('id'=> $_POST['value']));
				if(isset($index_academy)){
					$code_gaia = $DB->get_record('origine_gaia', array('name'=> $index_academy->short_uri));
				}
				break;
		}
		echo json_encode($code_gaia);
	}
	
	
