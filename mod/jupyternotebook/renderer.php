<?php

require_once($CFG->dirroot.'/mod/jupyternotebook/lib.php');
require_once($CFG->dirroot.'/lib/resourcelib.php');

class mod_jupyternotebook_renderer extends plugin_renderer_base{

    public function aardvark_custom_access_url($mod)
    {
        global $DB, $CFG, $COURSE;
        require_once($CFG->dirroot.'/lib/resourcelib.php');

        $jupyter = $DB->get_record('jupyternotebook', array('id' => $mod->instance));

        $params = array(
            'href' => '',
        );

        $url = $mod->url;
        $display = $jupyter->displayoptions;

        if ($display == RESOURCELIB_DISPLAY_OPEN) {
            $params['href'] = $mod->url;
            // $params['onclick'] = 'window.open("' . $url . '"); return false;';
        } else if ($display == RESOURCELIB_DISPLAY_NEW) {
            $params['href'] = $mod->url;
            $params['onclick'] = 'window.open("' . $url . '", "", "toolbar=no,location=no,menubar=no,copyhistory=no,status=no,directories=no,scrollbars=yes,resizable=yes"); return false;';
        }

        return $params;
    }

    public function aardvark_custom_section_content($mod){
        global $DB, $USER;

        $context = context_module::instance($mod->id);
        $mod_jupyter = $DB->get_record('jupyternotebook', array('id'=>$mod->instance));

        $content = '';
        if ($mod_jupyter->showdescription && trim($mod_jupyter->intro) != ''){
            $options = array('noclean'=>true, 'para'=>false, 'filter'=>true, 'context'=>$context, 'overflowdiv'=>false);
            $intro = file_rewrite_pluginfile_urls($mod_jupyter->intro, 'pluginfile.php', $context->id, 'mod_centralizedresources', 'intro', null);
            $content .= html_writer::tag('p', trim(format_text($intro, $mod_jupyter->introformat, $options, null)));
        }

        if($mod_jupyter->displayoptions == RESOURCELIB_DISPLAY_EMBED){
            $content .= html_writer::tag('iframe', '', array('src' => jupyternotebook_get_url($mod_jupyter, $USER), 'height' => $mod_jupyter->iframeheight, 'width' => '100%'));
        }

        return $content;
    }
}