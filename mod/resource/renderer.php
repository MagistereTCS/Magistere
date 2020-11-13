<?php

/**
 * Created by PhpStorm.
 * User: qgourbeault
 * Date: 02/11/2017
 * Time: 11:30
 */
class mod_resource_renderer extends plugin_renderer_base{

    private static $instance_num;

    public function aardvark_custom_access_url($mod)
    {
        global $DB, $CFG, $COURSE;
        require_once($CFG->dirroot.'/lib/resourcelib.php');
        $mod_resource = $DB->get_record('resource', array('id' => $mod->instance));

        $params = array(
            'href' => '',
        );

        $url = $mod->url;
        $display = $mod_resource->display;
        
        if ($display == RESOURCELIB_DISPLAY_OPEN) {
            $params['href'] = $mod->url;
            $params['onclick'] = 'window.open("' . $url . '"); return false;';
        } else if ($display == RESOURCELIB_DISPLAY_DOWNLOAD) {
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
}