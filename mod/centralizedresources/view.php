<?php
require_once('../../config.php');
require_once($CFG->dirroot . '/mod/centralizedresources/lib.php');
require_once($CFG->dirroot . '/mod/centralizedresources/lib/cr_lib.php');
require_once($CFG->dirroot.'/mod/centralizedresources/lib/CentralizedMedia.php');
require_once($CFG->libdir.'/resourcelib.php');


$id       = optional_param('id', 0, PARAM_INT); // Course Module ID
$cr       = optional_param('r', 0, PARAM_INT);  // Centralized Resource instance ID
$redirect = optional_param('redirect', 0, PARAM_BOOL);


if ($cr) {
	if (!$centralizedresource = $DB->get_record('centralizedresources', array('id'=>$cr))) {
		resource_redirect_if_migrated($cr, 0);
		print_error('invalidaccessparameter');
	}
	$cm = get_coursemodule_from_instance('centralizedresources', $resource->id, $resource->course, false, MUST_EXIST);

} else {
	if (!$cm = get_coursemodule_from_id('centralizedresources', $id)) {
		resource_redirect_if_migrated(0, $id);
		print_error('Course Module ID was incorrect'); // NOTE this is invalid use of print_error, must be a lang string id
	}
	$centralizedresource  = $DB->get_record('centralizedresources', array('id' => $cm->instance), '*', MUST_EXIST);
}

$course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);
$where = "id = $centralizedresource->centralizedresourceid";
$cr_resource = get_cr_resource($where);

/// Print the page header

$PAGE->set_url('/mod/centralizedresources/view.php', array('id' => $cm->id));

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
$displaytype = $centralizedresource->display;
$cleanname = $cr_resource->cleanname;
$cr_type = $cr_resource->type;

if ($displaytype == RESOURCELIB_DISPLAY_OPEN || $displaytype == RESOURCELIB_DISPLAY_DOWNLOAD) {
	// For 'open' and 'download' links, we always redirect to the content - except
	// if the user just chose 'save and display' from the form then that would be
	// confusing
	if (!isset($_SERVER['HTTP_REFERER']) || strpos($_SERVER['HTTP_REFERER'], 'modedit.php') === false) {
		$redirect = true;
	}
}
$PAGE->set_heading($course->fullname);
$PAGE->set_title(format_string($centralizedresource->name));

// Output starts here
echo $OUTPUT->header();

if ($centralizedresource->name) { // Conditions to show the name can change to look for own settings or whatever
	//TCS BEGIN 2015/11/17 - NNE
	$heading = $OUTPUT->heading(format_string($centralizedresource->name), 2);
	echo $OUTPUT->add_encart_activity($heading);
	//TCS BEGIN 2015/11/17 - NNE
}

if ($centralizedresource->intro) { // Conditions to show the intro can change to look for own settings or whatever
	echo $OUTPUT->box(format_string($centralizedresource->intro));
}

$eventparams = [
    'context' => $context,
    'objectid' => $centralizedresource->centralizedresourceid
];
$event = \mod_centralizedresources\event\course_module_viewed::create($eventparams);
$event->add_record_snapshot('course_modules', $cm);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('centralizedresources', $centralizedresource);
$event->trigger();

switch ($cr_type) {
	case $CFG->centralizedresources_media_types['video']:
		$url_resource = '/'.$CFG->centralizedresources_media_types['video'].'/'.$cleanname;
		$resource_link = get_resource_centralized_secure_url($url_resource, $cr_resource->hashname .$cr_resource->createdate, $CFG->secure_link_timestamp_video);
		if ($redirect) {
			redirect($resource_link);
		}
		else{
		    try {
                $media = new CentralizedMedia($cr_resource);
                $media->initJS($PAGE);
                $content = $media->getHTML();
    		} catch(Exception $e) {
    		    $content = 'Ressource corrompue ou introuvable';
    		}
		}
		break;
	case $CFG->centralizedresources_media_types['audio']:
		$url_resource = '/'.$CFG->centralizedresources_media_types['audio'].'/'.$cleanname;
		$resource_link = get_resource_centralized_secure_url($url_resource, $cr_resource->hashname .$cr_resource->createdate, $CFG->secure_link_timestamp_audio);
		if ($redirect) {
			redirect($resource_link);
		}
		else{
		    try {
                $media = new CentralizedMedia($cr_resource);
    			$content = $media->getHTML(); // multimedia_jwplayer($resource_link, $cr_resource->hashname .$cr_resource->createdate, $cr_resource->type, $cr_resource->extension, "");
    		} catch(Exception $e) {
    		    $content = 'Ressource corrompue ou introuvable';
    		}
		}
		break;
	case $CFG->centralizedresources_media_types['diaporama']:
		$url_resource = '/';
		$resource_link  = get_resource_centralized_secure_url($url_resource, $cr_resource->hashname.$cr_resource->createdate, $CFG->secure_link_timestamp_default, true);
		if($redirect){
			redirect($resource_link);
		}
		else{
			$content = '<div class= "resource_block_scorm embed"><iframe width="100%" height="480" src="'.$resource_link.'"></iframe></div>';
		}
		break;
	case $CFG->centralizedresources_media_types['image']:
		$url_resource = '/'.$CFG->centralizedresources_media_types['image'].'/'.$cleanname;
		$resource_link  = get_resource_centralized_secure_url($url_resource, $cr_resource->hashname.$cr_resource->createdate, $CFG->secure_link_timestamp_image);
		if ($redirect) {
			redirect($resource_link);
		}
		else{
			$content = resourcelib_embed_image($resource_link, $cr_resource->cleanname);
		}
		break;
	case $CFG->centralizedresources_media_types['document']:
		$url_resource = '/'.$CFG->centralizedresources_media_types['document'].'/'.$cleanname;
		$resource_link  = get_resource_centralized_secure_url($url_resource, $cr_resource->hashname.$cr_resource->createdate, $CFG->secure_link_timestamp_default);

		if($cr_resource->extension == "pdf"){
			$content = resourcelib_embed_pdf($resource_link, $cr_resource->cleanname);
		}else{
			$content = '';
		}
		
		break;
	case $CFG->centralizedresources_media_types['archive']:
		$url_resource = '/'.$CFG->centralizedresources_media_types['archive'].'/'.$cleanname;
		$resource_link  = get_resource_centralized_secure_url($url_resource, $cr_resource->hashname.$cr_resource->createdate, $CFG->secure_link_timestamp_default);
		$content = '';
		break;
	default:
		$url_resource = '/'.$CFG->centralizedresources_media_types['file'].'/'.$cleanname;
		$resource_link  = get_resource_centralized_secure_url($url_resource, $cr_resource->hashname.$cr_resource->createdate, $CFG->secure_link_timestamp_default);
		$content = '';
		break;
}

$clicktoopen = get_string('clicktoopen2', 'centralizedresources', "<a href=\"$resource_link\">$cleanname</a>");

echo "<div id='video_magistere'>";
echo $content;
echo '<br/>';
echo $clicktoopen;

echo "</div>";

// Finish the page
echo $OUTPUT->footer();