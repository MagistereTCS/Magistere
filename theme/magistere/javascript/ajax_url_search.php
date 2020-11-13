<?php
require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');

$type = optional_param('type', 'error', PARAM_ALPHA);

echo get_page_url($type);


function get_page_url($type)
{
	global $CFG,$DB;
	
  $frontal_value = '';
 // selection du type de formulaire 
 if(isset($type)){
	if ($type == 'terms'){
		$value = 'terms';
		$frontal_value = $CFG->magistere_domaine.'/dgesco/mod/page/view.php?id=2232';
	}elseif ($type == 'about'){
		$value = 'about';
		$frontal_value = $CFG->magistere_domaine.'/dgesco/mod/page/view.php?id=2234';
	}elseif ($type == 'contact'){
		$value = 'contact';
		$frontal_value = $CFG->magistere_domaine.'/dgesco/mod/page/view.php?id=2235';
	}elseif ($type == 'virtualclass'){
		$value = 'virtualclass';
	}elseif ($type == 'conhelp'){
	    $frontal_value = $CFG->magistere_domaine.'/dgesco/mod/page/view.php?id=2236';
		$value = 'conhelp';
	}elseif ($type == 'instance'){
		$value = 'instance';
	}elseif ($type == 'help'){
		$value = 'help';
	}else{
		return 'error';
	}
 }

	$query = "SELECT id FROM {course_modules} WHERE module IN (SELECT id FROM {modules} WHERE name = 'page') AND course = 1 AND instance in (SELECT id FROM {page} WHERE intro LIKE '%".$value."%')";

	try {
		
		if (isfrontal())
		{
			return $frontal_value;
		}
		
		$result = $DB->get_records_sql($query);
		
		if($result)
		{
			$row = array_shift($result);

			$url_page = $CFG->wwwroot .'/mod/page/view.php?id='.$row->id;
			return $url_page;
		}
		else{
			return $CFG->wwwroot;
		}
		
	} catch(Exception $e){}
}