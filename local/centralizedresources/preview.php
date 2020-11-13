<?php
require_once('../../config.php');
require_once($CFG->dirroot . '/mod/centralizedresources/lib.php');
require_once($CFG->dirroot . '/mod/centralizedresources/lib/cr_lib.php');
require_once($CFG->dirroot.'/mod/centralizedresources/lib/CentralizedMedia.php');
require_once($CFG->libdir.'/resourcelib.php');


$id       = required_param('id', PARAM_INT); // Course ID
$cr       = required_param('r', PARAM_INT);  // Centralized Resource instance ID


$course = $DB->get_record('course', array('id'=>$id), '*', MUST_EXIST);
$where = "id = $cr";
$cr_resource = get_cr_resource($where);

/// Print the page header

$PAGE->set_url('/local/centralizedresource/preview.php', array('id' => $id, 'r' => $cr));

require_course_login($course, true);
$cleanname = $cr_resource->cleanname;
$cr_type = $cr_resource->type;

$PAGE->set_course($course);
$PAGE->set_heading($course->fullname);
$PAGE->set_title(format_string($cleanname));

switch ($cr_type) {
    case $CFG->centralizedresources_media_types['video']:
        $url_resource = '/'.$CFG->centralizedresources_media_types['video'].'/'.$cleanname;
        $resource_link = get_resource_centralized_secure_preview_url($url_resource, $cr_resource->hashname .$cr_resource->createdate, $CFG->secure_link_timestamp_video);

        try {
            $media = new CentralizedMedia($cr_resource, true);
            $media->initJS($PAGE);
            $content = $media->getHTML();
        } catch(Exception $e) {
            $content = 'Ressource corrompue ou introuvable';
        }
        break;
    case $CFG->centralizedresources_media_types['audio']:
        $url_resource = '/'.$CFG->centralizedresources_media_types['audio'].'/'.$cleanname;
        $resource_link = get_resource_centralized_secure_preview_url($url_resource, $cr_resource->hashname .$cr_resource->createdate, $CFG->secure_link_timestamp_audio);

        try {
            $media = new CentralizedMedia($cr_resource, true);
            $content = $media->getHTML();
        } catch(Exception $e) {
            $content = 'Ressource corrompue ou introuvable';
        }

        break;
    case $CFG->centralizedresources_media_types['diaporama']:
        $url_resource = '/';
        $resource_link  = get_resource_centralized_secure_preview_url($url_resource, $cr_resource->hashname.$cr_resource->createdate, $CFG->secure_link_timestamp_default, true);
        $content = '<div class= "resource_block_scorm embed"><iframe width="100%" height="480" src="'.$resource_link.'"></iframe></div>';
        break;
    case $CFG->centralizedresources_media_types['image']:
        $url_resource = '/'.$CFG->centralizedresources_media_types['image'].'/'.$cleanname;
        $resource_link  = get_resource_centralized_secure_preview_url($url_resource, $cr_resource->hashname.$cr_resource->createdate, $CFG->secure_link_timestamp_image);
        $content = resourcelib_embed_image($resource_link, $cr_resource->cleanname);
        break;
    case $CFG->centralizedresources_media_types['document']:
        $url_resource = '/'.$CFG->centralizedresources_media_types['document'].'/'.$cleanname;
        $resource_link  = get_resource_centralized_secure_preview_url($url_resource, $cr_resource->hashname.$cr_resource->createdate, $CFG->secure_link_timestamp_default);
        if($cr_resource->extension == "pdf") {
            $content = resourcelib_embed_pdf($resource_link, $cr_resource->cleanname, '');
        }else{
            $content = html_writer::link($resource_link, get_string('downloadlink', 'local_centralizedresources'));
        }
        break;
    case $CFG->centralizedresources_media_types['archive']:
        $url_resource = '/'.$CFG->centralizedresources_media_types['archive'].'/'.$cleanname;
        $resource_link  = get_resource_centralized_secure_preview_url($url_resource, $cr_resource->hashname.$cr_resource->createdate, $CFG->secure_link_timestamp_default);
        $content = html_writer::link($resource_link, get_string('downloadlink', 'local_centralizedresources'));
        break;
    default:
        $url_resource = '/'.$CFG->centralizedresources_media_types['file'].'/'.$cleanname;
        $resource_link  = get_resource_centralized_secure_preview_url($url_resource, $cr_resource->hashname.$cr_resource->createdate, $CFG->secure_link_timestamp_default);
        $content = html_writer::link($resource_link, get_string('downloadlink', 'local_centralizedresources'));
        break;
}

// Output starts here
echo $OUTPUT->header();
echo html_writer::tag('h3', get_string('previewtitle', 'local_centralizedresources', format_string($cleanname)));
echo $content;
// Finish the page
echo $OUTPUT->footer();