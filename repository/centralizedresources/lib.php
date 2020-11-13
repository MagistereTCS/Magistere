<?php


class repository_centralizedresources extends repository
{

    private $video = array('f4v', 'flv', 'mp4', 'mpg', 'avi', 'mkv', 'mov');
    private $audio = array('mp3', 'ogg');
    private $image = array('jpg', 'png', 'jpeg', 'gif');

    private $_conn;

    /**
     * @return centralized ressource DB instance
     */
    protected function getConn()
    {
    	// singleton
    	if ($this->_conn !== true) {
    		global $CFG, $CDB;

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

		    $this->_conn = true;
    	}
    }

    /**
     * fonction permettant la création de la liste à retourner pour l'affichage
     * @param string $path
     * @param string $page
     * @return array|string
     */
    public function get_listing($path = '', $page = '')
    {
    	return $this->search('');
    }

    /**
     * @return int
     */
    public function supported_returntypes()
    {
        return (FILE_EXTERNAL);
    }

    /**
     * @return array|string
     */
    function supported_filetypes()
    {
        return '*';
    }

    /**
     * affichage de l'outil de recherche
     * @return string
     */
    public function print_search()
    {
    	global $OUTPUT;
    	$html  = "";

    	$accepted_types = optional_param_array('accepted_types', array(), PARAM_FILE);
    	$colspan_attribut = array();
    	$context_type = '';
    	// Image
    	if ( in_array('.png', $accepted_types) )
    	{
    		$context_type = 'image';
    	}
    	// Video
    	else if ( in_array('.mp4', $accepted_types) )
    	{
    		$context_type = 'video';
    	}
    	// SCORM
        else if ( in_array('.scorm', $accepted_types) )
        {
            $context_type = 'scorm';
        }
        // INDEX THUMBNAIL
        else if(in_array('.thumbnail', $accepted_types) ){
    	    $context_type = 'thumbnail';
        }
    	// Link
    	else
    	{
    		$context_type = 'link';

    		$colspan_attribut = array('colspan'=>'2');
    	}


    	$html .= html_writer::start_tag('div',array('class'=>'cr_repository_form','style'=>'width:550px'));

    	// label search name

    	// $title = get_string('search_name', 'myrepo_search_name');
        $title = "Créateur :";
        $html .= html_writer::tag('p', $title, array('class' => 'title'));

        $html .= html_writer::start_div('creatorpart');
        $html .= html_writer::checkbox('created_by_me', 'true', false, 'Dont je suis le créateur', array('class'=>'checkbox_left'));

        $html .= html_writer::empty_tag('br');

        $title = "Créateur : ";
        $html .= html_writer::tag('label', $title, $param);
        // text field search name
        unset($attributes);
        $attributes['type']  = 'text';
        $attributes['name']  = 'creator';
        $attributes['value'] = '';
        $attributes['title'] = $title;
        $html .= html_writer::empty_tag('input', $attributes);

        $html .= html_writer::end_div();


    	// label search name
    	$param = array('for' => 'label_search_domaine', 'class'=>'title');
    	// $title = get_string('search_name', 'myrepo_search_name');

    	$title = "Domaine d'origine";
        $html .= html_writer::tag('p', $title, array('class' => 'title'));

        $html .= html_writer::start_div('domainpart');
    	$html .= html_writer::checkbox('this_domain', 'true', false, 'Restreindre à ce domaine', array('class'=>'checkbox_left'));
        $html .= html_writer::end_div();


    	// label search name
    	$param = array('for' => 'label_search_modification', 'class'=>'title');
    	// $title = get_string('search_name', 'myrepo_search_name');
    	$title = "Date de modification";
        $html .= html_writer::tag('p', $title, array('class' => 'title'));

        $html .= html_writer::start_div('datepart');
    	unset($param);
    	$title = "Après : ";
    	$html .= html_writer::tag('label', $title, $param);
    	// text field search name
    	unset($attributes);
    	$attributes['type']  = 'text';
    	$attributes['id']  = 'datepicker_after';
    	$attributes['class']  = 'datepicker_after';
    	$attributes['name']  = 'last_modif';
    	$attributes['title'] = $title;
    	$html .= html_writer::empty_tag('input', $attributes);

    	unset($attributes);
    	$attributes['src'] = $OUTPUT->image_url('general/calendar', 'theme');
    	$attributes['id']  = 'datepicker_after_img';
    	$attributes['class']  = 'datepicker_after_img';
    	$attributes['style']  = 'line-height:0px;';
    	$html .= html_writer::tag('img', '', $attributes);
        $html .= html_writer::end_div();


    	if ($context_type == 'link')
    	{
    	    $html .= html_writer::start_div('linkpart');
    		// label search name
    		$title = "Type de ressource";
    		$html .= html_writer::tag('label', $title, array('class'=>'title'));
    		$html .= html_writer::empty_tag('br');
    		$html .= html_writer::checkbox('resource_type_video', 'true', false, 'Vidéo');
    		$html .= html_writer::empty_tag('br');
    		$html .= html_writer::checkbox('resource_type_audio', 'true', false, 'Audio');
    		$html .= html_writer::empty_tag('br');
    		$html .= html_writer::checkbox('resource_type_image', 'true', false, 'Image');
    		$html .= html_writer::empty_tag('br');
    		$html .= html_writer::checkbox('resource_type_document', 'true', false, 'Document (pdf, word, excel, ...)');
    		$html .= html_writer::empty_tag('br');
    		$html .= html_writer::checkbox('resource_type_diapo', 'true', false, 'Diaporama');
    		$html .= html_writer::empty_tag('br');
    		$html .= html_writer::checkbox('resource_type_archive', 'true', false, 'Archive (zip, rar, 7z)');
    		$html .= html_writer::empty_tag('br');
    		$html .= html_writer::checkbox('resource_type_autre', 'true', false, 'Autre');
            $html .= html_writer::end_div();
    	}else if($context_type == 'scorm'){
            $html .= html_writer::tag('input', '', array('value' => 'true', 'name' => 'resource_type_diapo', 'type' => 'hidden'));
        } else if($context_type == 'thumbnail'){
            $html .= html_writer::tag('input', '', array('value' => 'true', 'name' => 'resource_type_thumbnail', 'type' => 'hidden'));
        }

        // label search name
        $param = array('for' => 'label_search_name', 'class'=>'title');
        // $title = get_string('search_name', 'myrepo_search_name');
        $title = "Rechercher dans le titre, la description ou le nom du fichier :";
        $html .= html_writer::tag('label', $title, $param);
        $html .= html_writer::empty_tag('br');

        // text field search name
        unset($attributes);
        $attributes['type']  = 'text';
        $attributes['name']  = 'search';
        $attributes['value'] = '';
        $attributes['title'] = $title;
        $attributes['style'] = 'width:300px';
        $html .= html_writer::empty_tag('input', $attributes);
        $html .= html_writer::empty_tag('br');

        $html .= html_writer::start_div('buttonbar');

        $title = "Submit";
        unset($attributes);
    	$attributes['type']  = 'submit';
    	$attributes['name']  = 'sub';
    	$attributes['value'] = 'Rechercher';
    	$attributes['title'] = $title;
    	$html .= html_writer::empty_tag('input', $attributes);

    	$title = "Reset";
    	unset($attributes);
    	$attributes['type']  = 'reset';
    	$attributes['name']  = 'reset';
    	$attributes['value'] = 'Effacer';
    	$attributes['title'] = $title;
    	$attributes['style'] = 'margin-left:20px';
    	$html .= html_writer::empty_tag('input', $attributes);


    	$title = "(Seuls les 100 premiers résultats sont affichés)";
    	unset($attributes);
    	$attributes['class'] = 'firsthundredmessages';
    	//$html .= html_writer::empty_tag('span', $attributes);
    	$html .= html_writer::start_span('',$attributes);
    	$html .= $title;
    	$html .= html_writer::end_span();
        $html .= html_writer::end_div();

    	$html .= html_writer::end_tag('div');

        return $html;
    }

    /**
     * fonction de recherche, retourne une liste du même type que la fonction get_listing.
     * @param string $search_text
     * @param int $page
     * @return array|mixed|string
     */
    public function search($search_text, $page = 0)
    {
    	global $CFG, $USER, $CDB;

    	$creator = optional_param('creator', '', PARAM_TEXT);
    	$last_modif = optional_param('last_modif', '', PARAM_TEXT);
    	$search = optional_param('search', '', PARAM_TEXT);


    	$created_by_me = optional_param('created_by_me', 'false', PARAM_ALPHA);
    	$onthis_domain = optional_param('this_domain', 'false', PARAM_ALPHA);


    	$resource_type_video = optional_param('resource_type_video', 'false', PARAM_ALPHA);
    	$resource_type_audio = optional_param('resource_type_audio', 'false', PARAM_ALPHA);
    	$resource_type_image = optional_param('resource_type_image', 'false', PARAM_ALPHA);
    	$resource_type_document = optional_param('resource_type_document', 'false', PARAM_ALPHA);
    	$resource_type_diapo = optional_param('resource_type_diapo', 'false', PARAM_ALPHA);
    	$resource_type_archive = optional_param('resource_type_archive', 'false', PARAM_ALPHA);
    	$resource_type_autre = optional_param('resource_type_autre', 'false', PARAM_ALPHA);
    	$resource_type_thumbnail = optional_param('resource_type_thumbnail', 'false', PARAM_ALPHA);

    	$accepted_types = optional_param_array('accepted_types', array(), PARAM_FILE);

    	if ($creator == '' && $created_by_me == 'false' && $last_modif == '' && $search == '' && $onthis_domain == 'false')
    	{
    		return $this->formatResults( array(), true );
    	}

    	$context_type = '';
    	// Image
    	if ( in_array('.png', $accepted_types) )
    	{
    		$context_type = 'image';
    	}
    	// Video
    	else if ( in_array('.mp4', $accepted_types) )
    	{
    		$context_type = 'video';
    	}
    	// Link
    	else
    	{
    		$context_type = 'link';
    	}



    	$creatorwhere = '';
    	if ($created_by_me == 'true')
    	{
    		$creatorwhere = " AND c.email = '".$USER->email."'";
    	}else{
	    	// creator
	    	$creator = trim(str_replace("\\",'',str_replace("'",' ',str_replace('%','',trim($creator)))));
	    	if ($creator != '')
	    	{
	    		$words = explode(' ', $creator);

	    		foreach($words as $word)
	    		{
	    			$creatorwhere .= " AND (c.firstname LIKE '%".$word."%' OR c.firstname LIKE '%".$word."%' OR c.email LIKE '%".$word."%')";
	    		}
	    	}
    	}

    	// search
    	$searchwhere = '';
    	$search = trim(str_replace("\\",'',str_replace("'",'',str_replace('%','',trim($search)))));
    	if ($search != '')
    	{
    		$words = explode(' ', $search);

    		foreach($words as $word)
    		{
    			$searchwhere .= " AND (r.name LIKE '%".$word."%' OR r.description LIKE '%".$word."%' OR r.filename LIKE '%".$word."%')";
    		}
    	}


    	// last_modif
    	$last_modifwhere = '';
    	if ( !empty($last_modif) )
    	{
    		$found = preg_match("#([0-9]|[1-3][0-9])/(0[0-9]|1[0-2])/([1-2][0-9][0-9][0-9])#", $last_modif, $match);

    		if ($found)
    		{
    			$day  = $match[1];
    			$month  = $match[2];
    			$year = $match[3];

    			$last_modif_time = mktime(23,59,59,$month,$day,$year);

    			$last_modifwhere = ' AND r.editdate > '.$last_modif_time;
    		}
    	}


    	// this_domain
    	$domain_where = '';
    	if ($onthis_domain == 'true')
    	{
    		$domain_where = ' AND c.academie = "'.$CFG->academie_name.'"';
    	}


    	$ressource_type_where = '';
    	if ( $context_type == 'video' )
    	{
    		$ressource_type_where = " AND (r.type = '".$CFG->centralizedresources_media_types['video']."' OR r.type = '".$CFG->centralizedresources_media_types['audio']."')";
    	}
    	else if ( $context_type == 'image' )
    	{
    		$ressource_type_where = " AND r.type = '".$CFG->centralizedresources_media_types['image']."'";
    	}
    	else
    	{

    		if ($resource_type_video == 'true')
    		{
    			$ressource_type_where .= ' r.type = "'.$CFG->centralizedresources_media_types['video'].'"';
    		}
    		if ($resource_type_audio == 'true')
    		{
    			if ( !empty($ressource_type_where) )
    			{
    				$ressource_type_where .= ' OR ';
    			}
    			$ressource_type_where .= ' r.type = "'.$CFG->centralizedresources_media_types['audio'].'"';
    		}
    		if ($resource_type_image == 'true')
    		{
    			if ( !empty($ressource_type_where) )
    			{
    				$ressource_type_where .= ' OR ';
    			}
    			$ressource_type_where .= ' r.type = "'.$CFG->centralizedresources_media_types['image'].'"';
    		}
    		if ($resource_type_document == 'true')
    		{
    			if ( !empty($ressource_type_where) )
    			{
    				$ressource_type_where .= ' OR ';
    			}
    			$ressource_type_where .= ' r.type = "'.$CFG->centralizedresources_media_types['document'].'"';
    		}
    		if ($resource_type_diapo == 'true')
    		{
    			if ( !empty($ressource_type_where) )
    			{
    				$ressource_type_where .= ' OR ';
    			}
    			$ressource_type_where .= ' r.type = "'.$CFG->centralizedresources_media_types['diaporama'].'"';
    		}
    		if ($resource_type_archive == 'true')
    		{
    			if ( !empty($ressource_type_where) )
    			{
    				$ressource_type_where .= ' OR ';
    			}
    			$ressource_type_where .= ' r.type = "'.$CFG->centralizedresources_media_types['archive'].'"';
    		}
    		if ($resource_type_autre == 'true')
    		{
    			if ( !empty($ressource_type_where) )
    			{
    				$ressource_type_where .= ' OR ';
    			}
    			$ressource_type_where .= ' r.type = "'.$CFG->centralizedresources_media_types['file'].'"';
    		}
    		if($resource_type_thumbnail == 'true')
    		{
                if ( !empty($ressource_type_where) )
                {
                    $ressource_type_where .= ' OR ';
                }

                $ressource_type_where .= ' r.type = "'.$CFG->centralizedresources_media_types['indexthumb'].'"';
            }

    		if ( !empty($ressource_type_where) )
    		{
    			$ressource_type_where = ' AND ('.$ressource_type_where.')';
    		}

    	}




    	$sql = "SELECT r.id, r.resourceid, r.name, r.description, r.hashname, r.type, r.extension, r.createdate, r.editdate, r.thumbnailid, r.filename, r.filesize, r.editdate, r.public, r.cleanname,
    			c.firstname, c.lastname, c.email, c.academie,
    			rt.hashname as thumb_hashname, rt.extension as thumb_extension, rt.createdate as thumb_createdate
    			FROM cr_resources r
    			INNER JOIN cr_contributor c ON (r.contributorid = c.id)
    			LEFT JOIN cr_resources rt ON (r.thumbnailid = rt.id)
    			WHERE r.public = 1
    			      ".$ressource_type_where."
    			      ".$creatorwhere."
    				  ".$searchwhere."
    				  ".$last_modifwhere."
    				  ".$domain_where." 
    			AND (r.domainrestricted = 0 OR (r.domainrestricted=1 AND c.academie = '".$CFG->academie_name."'))  
    			LIMIT 100";

    	//echo '####'.$ressource_type_where.'####';
    	//echo '####'.$sql.'####';

    	$this->getConn();
		$result = $CDB->get_records_sql($sql);


        return $this->formatResults( $result );
    }


    protected function ucname($string) {
    	$string =ucwords(strtolower($string));

    	foreach (array('-', '\'') as $delimiter) {
    		if (strpos($string, $delimiter)!==false) {
    			$string =implode($delimiter, array_map('ucfirst', explode($delimiter, $string)));
    		}
    	}
    	return $string;
    }

    public function get_link($url) {
        global $CFG, $CDB;

        $thumbnail_url = $url;
        if(strpos($url, '[[[') !== false && strpos($url, ']]]') !== false) {
            // Here it's not a real URL but a [[[cr_xxxx]]] value, and needs to be transformed
            $resourceid = $url;
            $resourceid = str_replace(']]]', '', $resourceid);
            $resourceid = str_replace('[[[cr_link_', '', $resourceid);
            $resourceid = str_replace('[[[cr_', '', $resourceid);

            $this->getConn();
            $resource = $CDB->get_record_sql("SELECT r.id, r.resourceid, r.name, r.description, r.hashname, r.type, r.extension, r.createdate, r.editdate, r.thumbnailid, r.filename, r.filesize, r.editdate, r.public, r.cleanname,
    			rt.hashname as thumb_hashname, rt.extension as thumb_extension, rt.createdate as thumb_createdate
    			FROM cr_resources r
    			LEFT JOIN cr_resources rt ON (r.thumbnailid = rt.id)
    			WHERE r.public = 1 AND r.resourceid = ?", [$resourceid]);

            if ($resource) {
                $thumbnail_url = $this->get_image_secure_url_from_resource($resource, true);
            }
        }
        return $thumbnail_url;
    }

    private function get_image_secure_url_from_resource($resource, $can_return_icon = false){
        global $CFG, $OUTPUT;
        if($can_return_icon){
            $url = $OUTPUT->image_url(file_extension_icon($resource->filename, 24))->out(false);
        }else{
            $url = null;
        }
        switch ($resource->type) {
            case 'video':
            case 'videolr':
                // Check if thumbnail registered in DB for thhis video still exists
                $thumbnail_exists = file_exists( $CFG->centralizedresources_media_path['thumbnail'].substr($resource->thumb_hashname,0,2).'/'.$resource->thumb_hashname.$resource->thumb_createdate.'.'.$resource->thumb_extension);
                // Works for the videos
                if ($resource->thumbnailid != null && $thumbnail_exists)
                {
                    $url = get_resource_centralized_secure_url('/'. $CFG->centralizedresources_media_types['thumbnail'] .'/thumb.'. $resource->thumb_extension, $resource->thumb_hashname.$resource->thumb_createdate, $CFG->secure_link_timestamp_image);
                }
                break;
            case 'image':
            case 'thumbnail':
            case 'indexthumb':
            $url = get_resource_centralized_secure_url('/'.$resource->type.'/'.$resource->cleanname, $resource->hashname.$resource->createdate, $CFG->secure_link_timestamp_video);
                break;
        }
        return $url;
    }

    /**
     * @param $data
     * @return array
     */
    protected function formatResults($data,$iscentralized=false)
    {
        global $CFG, $OUTPUT;

        $accepted_types = optional_param_array('accepted_types', array(), PARAM_FILE);

        $context_type = '';
        // Image
        if ( in_array('.png', $accepted_types) )
        {
        	$context_type = 'image';
        }
        // Video
        else if ( in_array('.mp4', $accepted_types) )
        {
        	$context_type = 'video';
        }
        else if( in_array('.thumbnail', $accepted_types)){
            $context_type = 'thumbnail';
        }
        // Link
        else
        {
        	$context_type = 'link';
        }

        $output = array();

        foreach ($data as $row) {

            $resourceId = '[[[cr_' . $row->resourceid.']]]';
            if ($context_type == 'link')
            {
            	$resourceId = '[[[cr_link_' . $row->resourceid.']]]';
            }

            $academie = $this->ucname(str_replace('ac-','',$row->academie));

            if ($row->type == $CFG->centralizedresources_media_types['diaporama'])
            {
            	list($ext_description,$ext_icon) = get_extension_info('diapo');
            }else{
            	list($ext_description,$ext_icon) = get_extension_info($row->extension);
            }

            $file = array(
                'title'    => $row->filename,
                'shorttitle' => '<span>'.$row->name.' </span><img src="'.$OUTPUT->image_url('general/info15', 'theme').'" alt="" id="info_'.$row->id.'" title="'.$row->description.'" style="float:right; margin:0px 2px 0px 0px" />',
                'source'   => $resourceId,
                'url'      => '',
                'type' => '<img src="'.$OUTPUT->image_url('fileicon/'.$ext_icon, 'theme').'" alt="'.$ext_description.'" style="float:left"/> <span style="float:left;margin-top:5px">'.$ext_description.'</span>',
            	//'mimetype' => $row->mimetype
            	'size'     => $row->filesize,
            	'domain'   => $academie,
            	'creator'  => $row->firstname.' '.$row->lastname,
            	'lasteditdate' => date('d.m.Y', $row->editdate),
            	'date'     => $row->createdate,
            	'datemodified' => $row->editdate,
            	'datecreated' => $row->createdate,
            	'author'   => $row->firstname.' '.$row->lastname,
            	'license'  => ($row->public == 1?'publique':'privée'),
            	'iscentralized' => 'true',
                'icon' => null,
                'image_height' => 100,
                'image_width'  => 100,
                'thumbnail_title' => $row->name,
                'thumbnail' => $this->get_image_secure_url_from_resource($row),
                'thumbnail_width' => 100,
                'thumbnail_height' => 100
            );
            $output[] = $file;
        }

        return array(
            'list' => $output,
        	'iscentralized' => $iscentralized,
        	'nologin' => true,
        	'norefresh' => true,
        	'nosearch' => false,
        	'noreload' => true
        );
    }
}