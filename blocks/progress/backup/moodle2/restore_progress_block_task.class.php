<?php
class restore_progress_block_task extends restore_block_task {

    /**
     * Translates the backed up configuration data for the target course modules
     *
     * @global type $DB
     */
    public function after_restore() {
        global $DB;
		//vérifier la présence d'un fichier monitor.xml
		
		$monitor_xml = file_exists ( $this->taskbasepath.'/monitor.xml');
		
		if($monitor_xml){		
			$monitor_a = $this->monitoring_xml_to_array();
		}			
			// Get the blockid
			$id = $this->get_blockid();

			//Restored course id
			$courseid = $this->get_courseid();

			if ($configdata = $DB->get_field('block_instances', 'configdata', array('id' => $id))) {			
			
				$config = (array)unserialize(base64_decode($configdata));

				// Translate the old config information to the target course values
				foreach ($config as $key => $value) {
					$matches = array();
					preg_match('/monitor_(\D+)(\d+)/', $key, $matches);
					if ($value == 1 && !empty($matches)) {
						$module = $matches[1];
						$instance = $matches[2];
						
						if($monitor_xml){
							//vérifier que module si module et instance sont retrouvé dans le fichier array_monitor
							foreach($monitor_a as $key => $value){
								if(($value['name']==$module)&&($value['instance']==$instance)){
									// récupération de la nouvelle instance
									$newinstance = $this->new_module_instance($courseid, $module, $value['created']);
									
									if ($newinstance !== false && !empty($newinstance) && $newinstance != '' && $newinstance != 0)
									{
											// Set new config
										$config["monitor_$module$newinstance"] =
											$config["monitor_$module$instance"];
										if(isset($config["locked_$module$instance"])){
										$config["locked_$module$newinstance"] =
											$config["locked_$module$instance"];
										}
										$config["date_time_$module$newinstance"] =
											$config["date_time_$module$instance"];
										$config["action_$module$newinstance"] =
											$config["action_$module$instance"];
										$config["position_$module$newinstance"] =
											$config["position_$module$instance"];
									}

									// Unset old config
									unset($config["monitor_$module$instance"]);
									unset($config["locked_$module$instance"]);
									unset($config["date_time_$module$instance"]);
									unset($config["action_$module$instance"]);
									unset($config["position_$module$instance"]);

								}
								
							}
							
						}else{
						
							// Find a matching module in the target course
							if ($cm = get_coursemodule_from_instance($module, $instance)) {

								// Get new cm and instance
								$newitem = restore_dbops::get_backup_ids_record(
									$this->get_restoreid(), "course_module", $cm->id);
								
								if ($newitem !== false)
								{
									$newcm = get_coursemodule_from_id($module, $newitem->newitemid);
									$newinstance = $newcm->instance;
									
									if ($newinstance !== false && !empty($newinstance) && $newinstance != '' && $newinstance != 0)
									{
										// Set new config
										$config["monitor_$module$newinstance"] =
											$config["monitor_$module$instance"];
										$config["locked_$module$newinstance"] =
											$config["locked_$module$instance"];
										$config["date_time_$module$newinstance"] =
											$config["date_time_$module$instance"];
										$config["action_$module$newinstance"] =
											$config["action_$module$instance"];
									}
								}

								// Unset old config
								unset($config["monitor_$module$instance"]);
								unset($config["locked_$module$instance"]);
								unset($config["date_time_$module$instance"]);
								unset($config["action_$module$instance"]);
							}
						}	
					}
				}
				
				
				// VSE - Clean old activities
				// Constants
				$activities = array('workshop', 'data', 'chat', 'assign', 'forum', 'glossary', 'scorm', 'questionnaire', 'choice', 'quiz', 'via', 'wiki', 'centralizedresources', 'viaassign', 'choicegroup', 'etherpadlite');
				$new_actions = array('data'=>'add', 'glossary'=>'add_entry', 'wiki'=>'add_page', 'questionnaire'=>'iscompleted');
				
				
				foreach($config as $key=>$value)
				{
					$found = preg_match("/(monitor|date_time|action|position|locked)_([a-zA-Z_]+?)([0-9]{1,9})/i",$key,$match);
					if ($found)
					{
						if (!in_array($match[2], $activities))
						{
							unset($config[$key]);
						}
				
						else if (array_key_exists($match[2], $new_actions) && $match[1] == 'action')
						{
							$config[$key] = $new_actions[$match[2]];
						}
					}
				}
				
				// VSE
				
				
				
				// Save everything back to DB
				$configdata = base64_encode(serialize((object)$config));
				$DB->set_field('block_instances', 'configdata', $configdata, array('id' => $id));
			}
		
    }
	
	public function new_module_instance($ci, $mo, $cr){
		global $DB;
		//besoin de l'id du module
		$module = $DB->get_record('modules', array('name' => $mo));			
		
		$cm = $DB->get_record('course_modules', array('course' => $ci, 'module' => $module->id, 'added' => $cr));
		if ($cm === false)
		{
			return false;
		}			
		return $cm->instance;
	}
	
	//transforme le contenu xml en un tableau 
	public function monitoring_xml_to_array(){
		$monitor_a = array();
		//récupération du contenu xml et boucle sur les éléments
		$doc_monitor = new DOMDocument();  
		$doc_monitor->load($this->taskbasepath."/monitor.xml"); 
		$elements = $doc_monitor->getElementsByTagName( "elements" );  
		
		foreach($elements as $ua) {  
			$element = $ua->getElementsByTagName("element");  
		
			foreach($element as $ua) {  
				$name_ua = $ua->getElementsByTagName("name");  
				$name = $name_ua->item(0)->nodeValue;						
				
				$instance_ua = $ua->getElementsByTagName("instance");  
				$instance = $instance_ua->item(0)->nodeValue;
				
				$created_ua = $ua->getElementsByTagName("created");  
				$created = $created_ua->item(0)->nodeValue;
				
				$monitor_l = array('name' =>  $name ,'instance' => $instance ,'created' => $created);
				array_push($monitor_a, $monitor_l);
			}	
		}  
		return $monitor_a;
	}

    /**
     * There are no unusual settings for this restore
     */
    protected function define_my_settings() {
    }

    /**
     * There are no unusual steps for this restore
     */
    protected function define_my_steps() {
    }

    /**
     * There are no files associated with this block
     *
     * @return array An empty array
     */
    public function get_fileareas() {
        return array();
    }

    /**
     * There are no specially encoded attributes
     *
     * @return array An empty array
     */
    public function get_configdata_encoded_attributes() {
        return array();
    }

    /**
     * There is no coded content in the backup
     *
     * @return array An empty array
     */
    static public function define_decode_contents() {
        return array();
    }

    /**
     * There are no coded links in the backup
     *
     * @return array An empty array
     */
    static public function define_decode_rules() {
        return array();
    }
}
