<?php
// fonction => affichage du bloc selon le bodyid de la page // seulement sur les pages associée à local mail
	function display_block(){
		global $PAGE;
		
		// if((strpos($PAGE->bodyid,'page-local-mail') !== false) ||  is_page_site())
		if(strpos($PAGE->bodyid,'page-local-mail') !== false)
		{return true;}else{return false;}
	}

	
//vérifie que l'on est sur la page d'acceuil
	function is_page_site()
	{
		global $PAGE;
		if ($PAGE->bodyid == "page-site-index")
		{return true;}else{return false;}
	}
	
// fonction de création de contenu pour le menu avec lien
	function menu_mail_content(){
		global $PAGE, $COURSE, $SITE;
		$menu_content = "";		
	   
       // Compose		
		    $text = get_string('compose', 'local_mail');	//modifier par l'intitulé
			$url = new moodle_url('/local/mail/compose.php');
			$url_recipients = new moodle_url('/local/mail/recipients.php');

			if ($PAGE->url->compare($url, URL_MATCH_BASE) or
				$PAGE->url->compare($url_recipients, URL_MATCH_BASE)) {
					$url->param('m', $PAGE->url->param('m'));
			} else {
				$url = new moodle_url('/local/mail/create.php');
				if ($COURSE->id != $SITE->id) {
					$url->param('c', $COURSE->id);
					$url->param('sesskey', sesskey());
				}
			}			
			$menu_content .= html_writer::link($url , $text).'<br>';

		
		// Inbox
			$text = get_string('inbox', 'local_mail');
			if (!empty($count->inbox)) {
				$text .= ' (' . $count->inbox . ')';
			}
			$url = new moodle_url('/local/mail/view.php', array('t' => 'inbox'));
			$menu_content .= html_writer::link($url , $text).'<br>';
			//$child = $node->add(s($text), $url);
			//$child->add_class('mail_inbox');


    // Starred
		$text = get_string('starredmail', 'local_mail');
		$url = new moodle_url('/local/mail/view.php', array('t' => 'starred'));
		$menu_content .= html_writer::link($url , $text).'<br>';
		//$node->add(s($text), $url);
	

    // Drafts
		$text = get_string('drafts', 'local_mail');
		if (!empty($count->drafts)) {
			$text .= ' (' . $count->drafts . ')';
		}
		$url = new moodle_url('/local/mail/view.php', array('t' => 'drafts'));
		$menu_content .= html_writer::link($url , $text).'<br>';
		// $child = $node->add(s($text), $url);
		// $child->add_class('mail_drafts');

    // Sent
		$text = get_string('sentmail', 'local_mail');
		$url = new moodle_url('/local/mail/view.php', array('t' => 'sent'));
		$menu_content .= html_writer::link($url , $text).'<br>';	
		// $node->add(s($text), $url);
	
    // Courses 
/*		$text = get_string('courses', 'local_mail');
		$nodecourses = $node->add($text, null, navigation_node::TYPE_CONTAINER);
		foreach ($courses as $course) {
			$text = $course->shortname;
			if (!empty($count->courses[$course->id])) {
				$text .= ' (' . $count->courses[$course->id] . ')';
			}
			$params = array('t' => 'course', 'c' => $course->id);
			$url = new moodle_url('/local/mail/view.php', $params);
			$child = $nodecourses->add(s($text), $url);
			$child->add_class('mail_course_'.$course->id);
		}
	

    // Labels

    $labels = local_mail_label::fetch_user($USER->id);
    if ($labels) {
        $text = get_string('labels', 'local_mail');
        $nodelabels = $node->add($text, null, navigation_node::TYPE_CONTAINER);
        foreach ($labels as $label) {
            $text = $label->name();
            if (!empty($count->labels[$label->id()])) {
                $text .= ' (' . $count->labels[$label->id()] . ')';
            }
            $params = array('t' => 'label', 'l' => $label->id());
            $url = new moodle_url('/local/mail/view.php', $params);
            $child = $nodelabels->add(s($text), $url);
            $child->add_class('mail_label_'.$label->id());
        }
    }
*/	
    // Trash
		$text = get_string('trash', 'local_mail');
		$url = new moodle_url('/local/mail/view.php', array('t' => 'trash'));
		$menu_content .= html_writer::link($url , $text).'<br>';
		
		
	return $menu_content;
	}	
	
	
	
?>