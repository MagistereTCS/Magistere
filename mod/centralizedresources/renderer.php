<?php

class mod_centralizedresources_renderer extends plugin_renderer_base {
    private static $instance_num;

    public function aardvark_custom_access_url($mod){
        global $DB, $CFG, $COURSE;

        require_once($CFG->dirroot.'/lib/resourcelib.php');

        $resource = $DB->get_record('centralizedresources', array('id'=>$mod->instance));

        $options = empty($resource->displayoptions) ? array() : unserialize($resource->displayoptions);

        $params = array(
            'href' => '',
        );

        $url = $mod->url;

        $where = "id = $resource->centralizedresourceid";
        $cr_resource = get_cr_resource($where);

        $display = $resource->display;
        if($display == RESOURCELIB_DISPLAY_AUTO){
            $display = $this->get_auto_display_default_for($cr_resource);
        }

        if($cr_resource->type == 'diaporama' && $display != RESOURCELIB_DISPLAY_DOWNLOAD && $display != RESOURCELIB_DISPLAY_EMBED){
            self::$instance_num = rand(1,999999999);
            $params['class'] = 'link_log_'.self::$instance_num;
        }

        if ($display == RESOURCELIB_DISPLAY_POPUP)
        {
            $width  = empty($options['popupwidth'])  ? 620 : $options['popupwidth'];
            $height = empty($options['popupheight']) ? 450 : $options['popupheight'];
            $params['href'] = '#';

            $url->param('redirect', '0');
            $params['onclick'] ='window.open("'.$url.'", "", "width='.$width.',height='.$height.',toolbar=no,location=no,menubar=no,copyhistory=no,status=no,directories=no,scrollbars=yes,resizable=yes"); return false;';
        }else if ($display == RESOURCELIB_DISPLAY_NEW || $display == RESOURCELIB_DISPLAY_OPEN) {
            $params['href'] = $mod->url;
            $params['target'] = '_blank';
        }else if($display == RESOURCELIB_DISPLAY_DOWNLOAD) {
            add_to_progress_activity($COURSE->id, $mod->id, -1, "viewed");
            $params['href'] = $this->get_downloadlink_for($cr_resource);
        }else if($display != RESOURCELIB_DISPLAY_EMBED){
            add_to_progress_activity($COURSE->id, $mod->id, -1, "viewed");
            $params['href'] = $mod->url;
        }

        return $params;
    }

    public function aardvark_custom_section_content($mod){
        global $DB, $CFG, $COURSE;

        require_once($CFG->dirroot.'/lib/resourcelib.php');
        require_once($CFG->dirroot.'/mod/centralizedresources/lib/cr_lib.php');

        $resource = $DB->get_record('centralizedresources', array('id'=>$mod->instance));
        $where = "id = $resource->centralizedresourceid";
        $cr_resource = get_cr_resource($where);

        $context = context_module::instance($mod->id);
        $options = empty($resource->displayoptions) ? array() : unserialize($resource->displayoptions);

        $content = '';

        // show description
        $course_modules = $DB->get_record('course_modules', array('id' => $mod->id));
        if ($course_modules->showdescription && trim($resource->intro) != ''){
            $options = array('noclean'=>true, 'para'=>false, 'filter'=>true, 'context'=>$context, 'overflowdiv'=>false);
            $intro = file_rewrite_pluginfile_urls($resource->intro, 'pluginfile.php', $context->id, 'mod_centralizedresources', 'intro', null);
            $content .= html_writer::tag('p', trim(format_text($intro, $resource->introformat, $options, null)));
        }

        $display = $resource->display;

        // show type of file
        if($cr_resource->type != 'diaporama' and $display != RESOURCELIB_DISPLAY_EMBED) {
            $content .= html_writer::tag('p', 'Fichier ' . $cr_resource->extension);
        }
        // show size of the file
        if(isset($options['showsize']) && $options['showsize'] != null){
            $content .= html_writer::tag('p', get_string('sizelabel', 'mod_centralizedresources'). " " .formatBytes($cr_resource->filesize));
        }


        if($display == RESOURCELIB_DISPLAY_AUTO){
            $display = $this->get_auto_display_default_for($cr_resource);
        }

        if($display == RESOURCELIB_DISPLAY_EMBED){
            $content .= $this->display_embed_content($cr_resource);
        }

        if($display == RESOURCELIB_DISPLAY_EMBED || $display == RESOURCELIB_DISPLAY_AUTO){
            $eventparams = [
                'context' => $context,
                'objectid' => $resource->centralizedresourceid
            ];
            $event = \mod_centralizedresources\event\course_module_viewed::create($eventparams);
            $event->add_record_snapshot('course_modules', $mod);
            $event->add_record_snapshot('course', $COURSE);
            $event->add_record_snapshot('centralizedresources', $resource);
            $event->trigger();
        }

        return $content;
    }

    public function display_embed_content($cr_resource){
        global $CFG, $PAGE;

        $content = '';
        $url_resource = '/'.$CFG->centralizedresources_media_types[$cr_resource->type].'/'.$cr_resource->cleanname;
        $timeout = $this->get_timeout_for($cr_resource->type);
        $resource_link  = get_resource_centralized_secure_url($url_resource, $cr_resource->hashname. $cr_resource->createdate, $timeout);
        $cleanname = $cr_resource->cleanname;

        switch($cr_resource->type){
            case 'video':
                $media = new CentralizedMedia($cr_resource);
                $media->initJS($PAGE);
                $content .= $media->getHTML();
                break;
            case 'audio':
                $media = new CentralizedMedia($cr_resource);
                $content .= $media->getHTML();
                break;
            case 'image':
                $content .= resourcelib_embed_image($resource_link, $cleanname);
                break;
            case 'diaporama':
                $resource_link  = get_resource_centralized_secure_url('/', $cr_resource->hashname. $cr_resource->createdate, $timeout, true);
                $content .= '<div class= "resource_block_scorm embed"><iframe width="100%" autoplay="false" src="'.$resource_link.'"></iframe></div>';
                break;
            case 'document':
                $clicktoopen = get_string('clicktoopen2', 'centralizedresources', "<a href=\"$resource_link\">$cleanname</a>");
                $content .= centralizedresources_embed_pdf($resource_link, $cleanname, $clicktoopen);
        }

        return $content;

    }
    public function get_auto_display_default_for($cr){
        $res = RESOURCELIB_DISPLAY_AUTO;
        switch($cr->type){
            case 'video':
            case 'audio':
            case 'image':
            case 'diaporama':
                $res = RESOURCELIB_DISPLAY_EMBED;
                break;
            case 'document':
                if($cr->extension == 'pdf'){ $res = RESOURCELIB_DISPLAY_EMBED; }
                break;
        }

        return $res;
    }

    public function get_timeout_for($type){
        global $CFG;

        $timestamp = $CFG->secure_link_timestamp_default;

        switch($type){
            case 'video':
                $timestamp = $CFG->secure_link_timestamp_video;
            case 'audio':
                $timestamp = $CFG->secure_link_timestamp_audio;
            case 'image':
                $timestamp = $CFG->secure_link_timestamp_image;
            default:
                $timestamp = $CFG->secure_link_timestamp_default;
        }

        return $timestamp;
    }

    public function get_downloadlink_for($cr){
        global $CFG;

        $isdiapo = ($cr->type == 'diaporama');
        $url_resource = ($isdiapo ? '/' : '/'.$CFG->centralizedresources_media_types[$cr->type].'/'.$cr->cleanname);
        $timeout = $this->get_timeout_for($cr->type);
        $resource_link  = get_resource_centralized_secure_url($url_resource, $cr->hashname.$cr->createdate, $timeout);

        $forcedownload = true;

        return get_resource_centralized_secure_url(
            $url_resource,
            $cr->hashname. $cr->createdate,
            $timeout,
            $isdiapo, $forcedownload);
    }
}


