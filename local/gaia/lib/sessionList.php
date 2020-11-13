<?php

function sort_session($s1, $s2)
{
	if($s1->startdate <= $s2->enddate){
		return -1;
	}else if($s1->startdate > $s2->enddate){
		return 1;
	}
	
	return 0;
}

class sessionList
{ 
	
	static public function get_list_gaia_from_dipositif($dispositif_id, $restrictResultEmail = '')
	{
		global $DB;
	
		$sql = '';
		
		if($restrictResultEmail == ''){
			$data = $DB->get_records('local_gaia_formations', array('dispositif_id' => $dispositif_id));
		}else{
			$sql = '
SELECT gf.*
FROM {local_gaia_formations} gf
LEFT JOIN {local_gaia_intervenants} gi ON gf.module_id = gi.module_id AND gi.table_name=gf.table_name
WHERE gf.dispositif_id="' . $dispositif_id . '" AND gi.email = "'.$restrictResultEmail . '"';
			
			$data = $DB->get_records_sql($sql);
		}
		
		if($data === false){
			return null;
		}
	
		$res = new stdClass();
		$res->modules = array();
		
		foreach($data as $session){
			if(isset($res->modules[$session->module_id]) === false){
				$res->modules[$session->module_id] = new stdClass();
				$res->modules[$session->module_id]->id = $session->module_id;
				$res->modules[$session->module_id]->name = $session->module_name;
				$res->modules[$session->module_id]->sessions = array();
			}
	
			$s = new stdClass();
			$s->id = $session->id;
			$s->session_id = $session->session_id;
			$s->startdate = $session->startdate;
			$s->enddate = $session->enddate;
			$s->place_type = $session->place_type;
			$s->formation_place = $session->formation_place;
			$s->group_number = $session->group_number;
	
			$res->modules[$session->module_id]->sessions[$session->session_id] = $s;
		}
				
		krsort($res->modules);
		
		foreach($res->modules as $id => $module){
			usort($res->modules[$id]->sessions, "sort_session");	
		}
	
		return $res;
	}
	
	static public function get_list_gaia_from_date($startdate, $enddate, $restrictResultEmail = '')
	{
		global $DB;
	
		$data = false;
		
		$sql = '';
		if($restrictResultEmail != ''){
				$sql = '
SELECT gf.*
FROM {local_gaia_formations} gf
JOIN {local_gaia_intervenants} gi ON gi.module_id = gf.module_id  AND gf.table_name = gi.table_name
WHERE gf.startdate >= '.$startdate.' AND gf.enddate < ' . $enddate . ' AND gi.email="'.$restrictResultEmail . '"';
		}else{
			$sql = '
SELECT *
FROM {local_gaia_formations} gf
WHERE gf.startdate >= '.$startdate.' AND gf.enddate < ' . $enddate;
		}
		
		$data = $DB->get_records_sql($sql);
	
		if($data === false){
			return null;
		}
	
		$res = array();
	
		foreach($data as $session){
			if(isset($res[$session->dispositif_id]) === false){
				$res[$session->dispositif_id] = new stdClass();
				$res[$session->dispositif_id]->name = $session->dispositif_name;
				$res[$session->dispositif_id]->id = $session->dispositif_id;
				$res[$session->dispositif_id]->table_name = $session->table_name;
				$res[$session->dispositif_id]->modules = array();
			}
	
			if(isset($res[$session->dispositif_id]->modules[$session->module_id]) === false){
				$m = new stdClass();
				$m->id = $session->module_id;
				$m->name = $session->module_name;
				$m->sessions = array();
					
				$res[$session->dispositif_id]->modules[$m->id] = $m;
			}
	
			$s = new stdClass();
			$s->id = $session->id;
			$s->session_id = $session->session_id;
			$s->startdate = intval($session->startdate);
			$s->enddate = intval($session->enddate);
			$s->place_type = $session->place_type;
			$s->formation_place = $session->formation_place;
			$s->group_number = $session->group_number;
	
			$res[$session->dispositif_id]->modules[$session->module_id]->sessions[$session->session_id] = $s;
		}
		
		foreach($res as $id => $dispositif){
			krsort($res[$id]->modules);
			
			foreach($res[$id]->modules as $mid => $module){
				usort($res[$id]->modules[$mid]->sessions, "sort_session");
			}
		}
		
		return $res;
	}
}