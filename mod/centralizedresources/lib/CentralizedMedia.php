<?php

require_once($CFG->dirroot.'/mod/centralizedresources/lib/cr_lib.php');

class CentralizedMedia {

    private $thumbnail;

    private $subtitle;

    private $sources;

    private $chapter;

    private $htmlId;

    private $type;

    public function __construct($resource, $preview = false)
    {
        global $CFG;

        if(is_int($resource)){
            $resource = get_cr_resource('id = '.$resource);
        }

        if($resource->type != 'video' && $resource->type != 'audio'){
            throw new Exception('Resource must be a video or an audio');
        }

        $this->type = $resource->type;

        $timeout = $CFG->{'secure_link_timestamp_'.$resource->type};
        $urlresource = '/'.$CFG->centralizedresources_media_types[$resource->type].'/'.$resource->cleanname;;

        if($preview){
            $this->sources['hd'] = get_resource_centralized_secure_preview_url($urlresource, $resource->hashname. $resource->createdate, $timeout);
        }else{
            $this->sources['hd'] = get_resource_centralized_secure_url($urlresource, $resource->hashname. $resource->createdate, $timeout);
        }


        if($this->type == 'video' )
        {
            if(strlen($resource->lowresid) > 0 && intval($resource->lowresid) > 0)
            {
                $where2 = 'id = '.$resource->lowresid;
                $resource_lowres = get_cr_resource($where2);

                $resource->cleanname = substr($resource->cleanname,0,strlen($resource->cleanname)-4).'-'.$resource->height.'.'.$resource->extension;
                $resource_lowres->cleanname = substr($resource_lowres->cleanname,0,strlen($resource_lowres->cleanname)-4).'-'.$resource_lowres->height.'.'.$resource_lowres->extension;

                $resource_lowres_url = '/'.$CFG->centralizedresources_media_types['video'].'/'.$resource_lowres->cleanname;

                if($preview){
                    $this->sources['sd'] = get_resource_centralized_secure_preview_url($resource_lowres_url, $resource_lowres->hashname. $resource_lowres->createdate, $CFG->secure_link_timestamp_video);
                }else{
                    $this->sources['sd'] = get_resource_centralized_secure_url($resource_lowres_url, $resource_lowres->hashname. $resource_lowres->createdate, $CFG->secure_link_timestamp_video);
                }

            }


            $this->thumbnail = get_cr_attached_video_file_link($resource,'thumbnail');
            $this->subtitle  = get_cr_attached_video_file_link($resource,'subtitle');
            $this->chapter  = get_cr_attached_video_file_link($resource,'chapter');
        }

        $instance_num = rand(1,4);
        $instance_num = rand(1,999999999);

        $this->htmlId = 'canoplayer_'.$instance_num;
    }

    public function getResourceInfo()
    {
        $data = array();

        if(count($this->sources) == 1){
            $data['source'] = reset($this->sources);
        }

        if(count($this->sources) > 1){
            $data['sources'] = array();

            foreach($this->sources as $label => $source)
            {
                $data['sources'][$label] = $source;
            }
        }

        if($this->subtitle){
            $data['subtitle'] = $this->subtitle;
        }

        if($this->thumbnail){
            $data['thumbnail'] = $this->thumbnail;
        }

        if($this->chapter){
            $data['chapter'] = $this->chapter;
        }

        $data['type'] = $this->type;

        return $data;
    }

    public function getHTML()
    {
        if($this->type == 'audio'){
            return '<audio style="width: 100%" controls src="'.$this->sources['hd'].'"></audio>';
        }

        $videoattribute = array('id' => $this->htmlId, 'class' => 'video-js', 'controls' => true);

        if($this->thumbnail)
        {
            $videoattribute['poster'] = $this->thumbnail;
        }

        $html = html_writer::start_tag('video', $videoattribute);

        if($this->subtitle)
        {
            $html .= html_writer::tag('track', null, array(
                'src' => $this->subtitle,
                'kind' => 'subtitles',
                'srclang' => 'fr',
                'label' => 'Français',
                'default' => 'default'
            ));
        }

        if($this->chapter)
        {
            $html .= html_writer::tag('track', null, array(
                'src' => $this->chapter,
                'kind' => 'chapters',
                'srclang' => 'fr',
                'label' => 'Français'
            ));
        }

        $html .= html_writer::end_tag('video');

        return $html;
    }

    public function initJS(moodle_page $page)
    {
        $page->requires->js_call_amd('mod_centralizedresources/centralizedmedia', 'clearSrc');
        
        foreach($this->sources as $label => $source)
        {
            $res = ($label == 'HD' ? 720 : 480);
            $page->requires->js_call_amd('mod_centralizedresources/centralizedmedia', 'addSrc', array($source, 'video/mp4', $label, $res));
        }

        $page->requires->js_call_amd('mod_centralizedresources/centralizedmedia', 'updateSrc', array($this->htmlId));
    }

    public function get_raw_js()
    {
        $js = '';
        $js .= $this->generate_require_amd('mod_centralizedresources/centralizedmedia', 'clearSrc');

        foreach($this->sources as $label => $source)
        {
            $res = ($label == 'HD' ? 720 : 480);
            $js .= $this->generate_require_amd('mod_centralizedresources/centralizedmedia', 'addSrc', array($source, 'video/mp4', $label, $res));
        }

        $js .= $this->generate_require_amd('mod_centralizedresources/centralizedmedia', 'updateSrc', array($this->htmlId));

        return $js;
    }

    private function generate_require_amd($module, $function, $params = array())
    {
        $strparams = [];
        foreach($params as $param){
            $strparams[] = json_encode($param);
        }

        return 'require(["'.$module.'"], function(amd) { amd.'.$function.'('.implode(',', $strparams).'); });';
    }
}