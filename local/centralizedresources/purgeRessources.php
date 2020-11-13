<?php

require_once('../../config.php');


class PurgeResources
{
	//private $default_delay = 47433600; // delay in second, 47433600s = one year and a half
	private $default_delay = 433600; 
	
	function __construct()
	{
		
	}
	
	function execute()
	{
		global $CFG;
		
		$cdb = get_centralized_db_connection();
		
		$resource_to_delete_query = 'SELECT * FROM cr_resources WHERE lastusedate<"' . (time()-$this->default_delay) . '" AND id > 5 AND type != "thumbnail"';
		
		$resources = $cdb->get_records_sql($resource_to_delete_query);
		
		if($resources !== false){
			
			foreach($resources as $resource)
			{
				
				$filehash = $resource->hashname;
				$filepath = $CFG->centralizedresources_media_path[$resource->type].substr($filehash,0,2).'/'.$filehash.$resource->createdate.'.'.$resource->extension;
				
				if ($resource->thumbnailid)
				{
					$thumb = $cdb->get_record_sql('SELECT * FROM cr_resources WHERE id = ' . $resource->thumbnailid);
					$resource->thumbnail = $thumb;
					
					$filehash2 = $thumb->hashname;
					$filepath2 = $CFG->centralizedresources_media_path[$thumb->type].substr($filehash2,0,2).'/'.$filehash2.$thumb->createdate.'.'.$thumb->extension;
				}
				
				
				cr_log($resource->resourceid,$filepath,'delete',json_encode($resource),'0','purge_cron');
				
				if (file_exists($filepath))
				{
					if (is_dir($filepath))
					{
						//rrmdir($filepath);
					}else{
					    //unlink($filepath);
					}
				}
				
				//$cdb->execute('DELETE FROM cr_resources WHERE id = '.$resource->thumbnailid);
				//$cdb->execute('DELETE FROM cr_resources WHERE id = '.$resource->id);
				
			}
		}
	}
}


function cr_log($ressourceid,$filepath, $action, $data = '', $user_id = null, $academy = null)
{
	global $CFG, $USER;
	
	if (strlen($ressourceid) != 40)
	{
		error_log('cr_log/Error:ressourceid is too short or too long : "'.$ressourceid.'"');
		return false;
	}
	
	if (substr($filepath,0,strlen($CFG->centralized_path)-1) == $CFG->centralized_path)
	{
		error_log('cr_log/Error:filepath doest not start with the common directory ("'.$CFG->centralized_path.'") : "'.$filepath.'"');
		return false;
	}

	if ($academy == null)
	{
		$academy = $CFG->academie_name;
	}
	
	if ($user_id == null)
	{
		$user_id = $USER->id;
	}
	
	$CFG->prefix = '';
	
	$cdb = get_centralized_db_connection();
	
	$log = new StdClass();
	$log->academy = $academy;
	$log->userid = $user_id;
	$log->date = time();
	$log->ressourceid = $ressourceid;
	$log->filepath = $filepath;
	$log->action = $action;
	$log->data = $data;
	
	$cdb->insert_record('cr_log', $log);
	
	
	//$sql = 'INSERT INTO `cr_log`(`academy`,`userid`,`date`,`ressourceid`,`filepath`,`action`,`data`) VALUES("'.$academy.'","'.$user_id.'","'.time().'","'.$ressourceid.'","'.$filepath.'","'.$action.'","'.mysql_escape_string($data).'")';
	//$sql = "INSERT INTO `cr_log`(`academy`,`userid`,`date`,`ressourceid`,`filepath`,`action`,`data`) VALUES('".$academy."','".$user_id."','".time()."','".$ressourceid."','".$filepath."','".$action."','".mysql_escape_string($data)."')";
	//echo '###'.$sql.'###';
	//$cdb->execute($sql);
	
	return true;
}



//CREATE TABLE `moodle_centralized`.`cr_log` ( `id` INT(11) NOT NULL AUTO_INCREMENT , `academy` VARCHAR(30) NOT NULL , `userid` INT(11) NOT NULL , `date` INT(11) NOT NULL , `ressourceid` VARCHAR(40) NOT NULL , `filepath` VARCHAR(255) NOT NULL , `action` VARCHAR(15) NOT NULL , `data` MEDIUMTEXT NOT NULL , PRIMARY KEY (`id`) ) ENGINE = InnoDB;


$purgeResources = new PurgeResources();

$purgeResources->execute();
