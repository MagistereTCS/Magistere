<?php

class backup_progress_block_task extends backup_block_task {
	
	protected $monitor_path;
	 /**
     * override récupération locale de $basepath
     * Block tasks have their own directory to write files
     */
    public function get_taskbasepath() {
        $basepath = $this->get_basepath();

        // Module blocks are under module dir
        if (!empty($this->moduleid)) {
            $basepath .= '/activities/' . $this->modulename . '_' . $this->moduleid .
                         '/blocks/' . $this->blockname . '_' . $this->blockid;

        // Course blocks are under course dir
        } else {
            $basepath .= '/course/blocks/' . $this->blockname . '_' . $this->blockid;
        }
		$this->monitor_path = $basepath;
        return $basepath;
    }

    protected function define_my_settings() {
    }

    protected function define_my_steps() {
    }

    public function get_fileareas() {
		if($this->monitor_path!=''){			
			$this->monitor_file();
		}
        return array();
    }

    public function get_configdata_encoded_attributes() {
        return array();
    }

    static public function encode_content_links($content) {
        return $content;
    }
	
	/*
	*	creation du contenu xml correspondant aux contenu monitores 
	*/
	protected function monitor_file_content(){
		global $DB;
		
		//lecture de la config de progressbar
		$block = $DB->get_record('block_instances', array('id' => $this->blockid));
		$config = unserialize(base64_decode($block->configdata));
		
		//création du contenu XML
		$xml = new DOMDocument('1.0', 'utf-8');
		$elements = $xml->createElement('elements');
		$xml->appendChild($elements);
		
		  foreach ($config as $key => $value) {
		  
			$matches = array();
                preg_match('/monitor_(\D+)(\d+)/', $key, $matches);
                if ($value == 1 && !empty($matches)) {
                    $module_name = $matches[1];
					$instance = $matches[2];
					
					//mdl_modules => rechercher l'id du module en fonction de son nom
					$module = $DB->get_record('modules', array('name' => $module_name));					
					
					//mdl_course_modules => récupérer le added
					$module_instance = $DB->get_record('course_modules', array('module' => $module->id, 'instance'=> $instance ));					
					
					if ($module_instance !== false)
					{
						//création de l'élément monitoré
						$element = $xml->createElement('element');
						$elements->appendChild($element);
						
						//nom de l'élément
						$name = $xml->createElement('name', $module_name);
						$element->appendChild($name);
						
						//id de l'instance de l'élément
						$instance = $xml->createElement('instance', $module_instance->instance);
						$element->appendChild($instance);
											
						//date de création de l'élément
						$created = $xml->createElement('created', $module_instance->added);
						$element->appendChild($created);
					}
				}				
		  }		  
		return $xml->saveXML();
	}
	
	/*
	*	création du fichier xml monitor.xml
	*/
	protected function monitor_file(){
		$file_content = $this->monitor_file_content();
		
		$f_monitor = fopen($this->monitor_path."/monitor.xml","w"); 
		if($f_monitor!=false){			
			$test_write = fwrite( $f_monitor , $file_content);			
			fclose($f_monitor);
		}
	}
}
