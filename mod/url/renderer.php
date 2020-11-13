<?php

/**
 * Created by PhpStorm.
 * User: qgourbeault
 * Date: 23/10/2017
 * Time: 17:21
 */
class mod_url_renderer extends plugin_renderer_base{

        private static $instance_num;

        public function aardvark_custom_access_url($mod)
        {
            global $DB, $CFG, $COURSE;
            require_once($CFG->dirroot.'/lib/resourcelib.php');

            $mod_url = $DB->get_record('url', array('id' => $mod->instance));

            $params = array(
                'href' => '',
            );

        $url = $mod->url;
        $display = $mod_url->display;

        if ($display == RESOURCELIB_DISPLAY_OPEN) {
            $params['href'] = $mod->url;
            $params['onclick'] = 'window.open("' . $url . '"); return false;';
        } else if ($display == RESOURCELIB_DISPLAY_NEW) {
            $params['href'] = $mod->url;
            $params['onclick'] = 'window.open("' . $url . '", "", "toolbar=no,location=no,menubar=no,copyhistory=no,status=no,directories=no,scrollbars=yes,resizable=yes"); return false;';
        } else if ($display == RESOURCELIB_DISPLAY_AUTO) {
            $params['href'] = $mod->url;
            $params['onclick'] = 'window.open("' . $url . '"); return false;';
        } else if ($display == RESOURCELIB_DISPLAY_POPUP) {
            $params['href'] = $mod->onclick;
            $params['onclick'] = $mod->onclick;
        } else if ($display == RESOURCELIB_DISPLAY_EMBED) {
            $params['href'] = $mod->url;
            $params['onclick'] = 'window.open("' . $url . '", "", "toolbar=no,location=no,menubar=no,copyhistory=no,status=no,directories=no,scrollbars=yes,resizable=yes"); return false;';
        }
        return $params;
    }


    public function aardvark_custom_section_content($mod){
        global $DB, $CFG, $COURSE;
        require_once($CFG->dirroot.'/lib/resourcelib.php');
        require_once($CFG->dirroot.'/mod/centralizedresources/lib/cr_lib.php');

        $mod_url = $DB->get_record('url', array('id'=>$mod->instance));




    }
}