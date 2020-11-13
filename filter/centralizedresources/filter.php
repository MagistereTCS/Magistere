<?php

require_once($CFG->libdir.'/resourcelib.php');
require_once($CFG->dirroot.'/mod/centralizedresources/lib/cr_lib.php');
require_once($CFG->dirroot.'/mod/centralizedresources/lib/CentralizedMedia.php');

class filter_centralizedresources extends moodle_text_filter {
	
	//const OLD_REGEX = '/(<a href="cR_MaG_([0-9a-z-A-Z]{10})">cR_MaG_[0-9a-z-A-Z]{10}</a>)/';
	// <a href="https://magistere.lan/dgesco/cR_MaG_">cR_MaG_</a>
	//abcdefghijklmnpqrstuvwxy0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ
	
	// <a href="https://magistere-pp.reseau-canope.fr/dgesco/cR_MaG_f4v_B994lkaBbD">cR_MaG_f4v_B994lkaBbD</a>
	
	const CR_REGEX = '/(\[\[\[cr_([0-9a-f]{40})\]\]\])/';
	//const CR_REGEX = '/(cr_([0-9a-f]{40})/';
	// [[[cr_23860c7794ead9f28fe750544ed0ea16ab6546fc]]]
	
	const CR_LINK_REGEX = '/(\[\[\[cr_link_([0-9a-f]{40})\]\]\])/';
	// <a href="[[[cr_link_6d86ce7a4a610a7d2eec0fc7ac9324415712b476]]]"></a>
	
	const CR_MLINK_MEDIA_REGEX = '/(\[\[\[cr_mlink_([0-9a-f]{40})\]\]\])/';
	// [[[cr_mlink_6d86ce7a4a610a7d2eec0fc7ac9324415712b476]]]
	
	const CR_MLINK_MEDIA_A_REGEX = '~<a\s[^>]*href="(\[\[\[cr_mlink_([0-9a-f]{40})\]\]\])"[^>]*>([^>]*)</a>~is';
	// <a href="[[[cr_mlink_6d86ce7a4a610a7d2eec0fc7ac9324415712b476]]]"></a>

    public function filter($text, array $options = array()) {
	
		global $CFG;
        
		$text = preg_replace_callback(self::CR_REGEX,array($this,'callback_cr'),$text);

		$text = preg_replace_callback(self::CR_MLINK_MEDIA_A_REGEX,array($this,'callback_cr_link_media'),$text);
		
		$text = preg_replace_callback(self::CR_MLINK_MEDIA_REGEX,array($this,'callback_cr_link'),$text);
		
		$text = preg_replace_callback(self::CR_LINK_REGEX,array($this,'callback_cr_link'),$text);
		return $text;
    }
    
    private function callback_cr($matches)
    {
        $ressourceid = $matches[2];
        
        $data = $this->get_ressource_data($ressourceid);
        
        return $data['content'];
    }
    
    private function callback_cr_link_media($matches)
    {
        $ressourceid = $matches[2];
        $linkcontent = $matches[3];
        
        $data = $this->get_ressource_data($ressourceid,$linkcontent);
        
        return $data['content'];
    }
    
    private function callback_cr_link($matches)
    {
    	$ressourceid = $matches[2];
    	
    	$data = $this->get_ressource_data($ressourceid);
    	
    	return $data['resource_link'];
    }
    
    /**
     * Retourne le "resource_link" et le "content" de la ressource passée en parametre
     * @param hexa $ressourceid ID unique de la ressource (sha1)
     * @return multitype:string NULL
     */
    private function get_ressource_data($ressourceid,$linkcontent=null)
    {
    	global $CFG, $PAGE;
    	
    	
    	$result = $this->getCDB()->get_record_sql(
    			"SELECT r.id, r.name, r.hashname, r.cleanname, r.createdate, r.extension, r.type,
    			rt.hashname as thumb_hashname, rt.filename as thumb_filename, rt.createdate as thumb_createdate,
    			rc.hashname as chapter_hashname, rc.filename as chapter_filename, rc.cleanname as chapter_cleanname, rc.createdate as chapter_createdate,
    			rs.hashname as subtitle_hashname, rs.filename as subtitle_filename, rs.cleanname as subtitle_cleanname, rs.createdate as subtitle_createdate,
    			rt.id as thumbnailid, rc.id as chapterid, rs.id as subtitleid, r.lowresid, r.height
    			FROM cr_resources r
    			LEFT JOIN cr_resources rt ON (r.thumbnailid = rt.id)
    			LEFT JOIN cr_resources rc ON (r.chapterid = rc.id)
    			LEFT JOIN cr_resources rs ON (r.subtitleid = rs.id)
    			WHERE r.resourceid = '".$ressourceid."' AND r.public = 1");
    	
    	$data = array();

    	if($result === false){
    		$data['content'] = "[[[".get_string('resourcedeleted', 'filter_centralizedresources')."]]]";
    		$data['link'] = "";
    		return $data;
    	}

    	switch ($result->type) {
    		case $CFG->centralizedresources_media_types['video']:
                try {
                   	$url_resource = '/'.$CFG->centralizedresources_media_types['video'].'/'.$result->cleanname;
                   	$data['resource_link']  = get_resource_centralized_secure_url($url_resource, $result->hashname. $result->createdate, $CFG->secure_link_timestamp_video);
                   	$media = new CentralizedMedia($result);
                   	$media->initJS($PAGE);
                   	$data['content'] = $media->getHTML();
                } catch(Exception $e) {
                   	$data['resource_link'] = '';
                   	$data['content'] = 'Ressource corrompue ou introuvable';
                }
    			break;
    				
    		case $CFG->centralizedresources_media_types['audio']:
                try {
                   	$url_resource = '/'.$CFG->centralizedresources_media_types['audio'].'/'.$result->cleanname;
                   	$data['resource_link']  = get_resource_centralized_secure_url($url_resource, $result->hashname.$result->createdate, $CFG->secure_link_timestamp_audio);
                   	$media = new CentralizedMedia($result);
                   	$data['content'] = $media->getHTML();
                } catch(Exception $e) {
                   	$data['resource_link'] = '';
                   	$data['content'] = 'Ressource corrompue ou introuvable';
                }
    			break;
    	
    		case $CFG->centralizedresources_media_types['image']:
    			$url_resource = '/'.$CFG->centralizedresources_media_types['image'].'/'.$result->cleanname;
    			$data['resource_link']  = get_resource_centralized_secure_url($url_resource, $result->hashname.$result->createdate, $CFG->secure_link_timestamp_image);
    			$data['content'] = resourcelib_embed_image($data['resource_link'], $result->cleanname);
    			break;
    	
    		case $CFG->centralizedresources_media_types['diaporama']:
    			$url_resource = '/';
    			$data['resource_link']  = get_resource_centralized_secure_url($url_resource, $result->hashname.$result->createdate, $CFG->secure_link_timestamp_diapo,true);
    			$data['content'] = '<a href="'.$data['resource_link'].'">'.($linkcontent!=null?$linkcontent:($result->name ? $result->name : $result->cleanname)).'</a>';
    			break;
    	
    		case $CFG->centralizedresources_media_types['document']:
    			$url_resource = '/'.$CFG->centralizedresources_media_types['document'].'/'.$result->cleanname;
    			$data['resource_link']  = get_resource_centralized_secure_url($url_resource, $result->hashname.$result->createdate, $CFG->secure_link_timestamp_default);
    			$data['content'] = '<a href="'.$data['resource_link'].'">'.($linkcontent!=null?$linkcontent:($result->name ? $result->name : $result->cleanname)).'</a>';
    			break;
    	
    		case $CFG->centralizedresources_media_types['archive']:
    			$url_resource = '/'.$CFG->centralizedresources_media_types['archive'].'/'.$result->cleanname;
    			$data['resource_link']  = get_resource_centralized_secure_url($url_resource, $result->hashname.$result->createdate, $CFG->secure_link_timestamp_default);
    			$data['content'] = '<a href="'.$data['resource_link'].'">'.($linkcontent!=null?$linkcontent:($result->name ? $result->name : $result->cleanname)).'</a>';
    			break;
    	
    		default:
    			$url_resource = '/'.$CFG->centralizedresources_media_types['file'].'/'.$result->cleanname;
    			$data['resource_link']  = get_resource_centralized_secure_url($url_resource, $result->hashname.$result->createdate, $CFG->secure_link_timestamp_default);
    			$data['content'] = '<a href="'.$data['resource_link'].'">'.($linkcontent!=null?$linkcontent:($result->name ? $result->name : $result->cleanname)).'</a>';
    			break;
    	}

    	return $data;
    }
    
    function get_attached_video_file_url($filename, $hashname, $createdate, $type){
    	global $CFG;
    	$url_resource = '/'.$CFG->centralizedresources_media_types[$type].'/'.$filename;
    	return get_resource_centralized_secure_url($url_resource, $hashname. $createdate, $CFG->secure_link_timestamp_image);
    }
    
    private function getCDB()
    {
    	global $CFG, $CDB;
    	// singleton
    	if (!isset($CDB)) {
    
    		if (!$CDB = moodle_database::get_driver_instance($CFG->dbtype, $CFG->dblibrary)) {
    			throw new dml_exception('dbdriverproblem', "Unknown driver $CFG->dblibrary/$CFG->dbtype");
    		}
    
    		try {
    			$CDB->connect($CFG->centralized_dbhost, $CFG->centralized_dbuser, $CFG->centralized_dbpass, $CFG->centralized_dbname, '', $CFG->dboptions);
    		} catch (moodle_exception $e) {
    			if (empty($CFG->noemailever) and !empty($CFG->emailconnectionerrorsto)) {
    				if (file_exists($CFG->dataroot.'/emailcount')){
    					$fp = @fopen($CFG->dataroot.'/emailcount', 'r');
    					$content = @fread($fp, 24);
    					@fclose($fp);
    					if((time() - (int)$content) > 600){
    						//email directly rather than using messaging
    						@mail($CFG->emailconnectionerrorsto,
    								'WARNING: Database connection error: '.$CFG->wwwroot,
    								'Connection error: '.$CFG->wwwroot);
    						$fp = @fopen($CFG->dataroot.'/emailcount', 'w');
    						@fwrite($fp, time());
    					}
    				} else {
    					//email directly rather than using messaging
    					@mail($CFG->emailconnectionerrorsto,
    							'WARNING: Database connection error: '.$CFG->wwwroot,
    							'Connection error: '.$CFG->wwwroot);
    					$fp = @fopen($CFG->dataroot.'/emailcount', 'w');
    					@fwrite($fp, time());
    				}
    			}
    			// rethrow the exception
    			throw $e;
    		}
    	}
    
    	return $CDB;
    }
}
?>
