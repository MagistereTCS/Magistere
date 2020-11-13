<?php

/**
 * indexation local plugin
 *
 * Fichier traitant l'ajout d'une nouvelle ressource à l'indexation.
 *
 * @package    local
 * @subpackage indexation
 * @author     TCS
 * @date       Aout 2019
 */

require_once('../../config.php');
require_once($CFG->dirroot.'/local/indexation/form/add_resource_form.php');
require_once($CFG->dirroot.'/local/centralizedresources/lib/cr_file_api.php');
require_once($CFG->dirroot.'/local/centralizedresources/lib/cr_insert_resource.php');

require_login();

$courseid = required_param('id', PARAM_INT);
$type = required_param('type', PARAM_TEXT);

$PAGE->set_context(context_course::instance($courseid));
$PAGE->set_pagelayout('course');
$PAGE->set_url('/local/indexation/add_resource.php');

$course = $DB->get_record('course', array('id' => $courseid));
$PAGE->set_course($course);

$site = get_site();

$pagedesc = 'Ressources';
$title = $site->shortname.': '.$pagedesc;
$fullname = $site->fullname;

$PAGE->set_title($title);
$PAGE->set_heading($fullname);


$resource_form = new add_resource_form(null, array('id' => $courseid, 'type' => $type, 'coursename' => $course->fullname));

if($data = $resource_form->get_data()){
    if($type == 'thumb'){
       $fileinfo = cr_moveFileToMediaFolder('attachments', 'indexthumb');
       
       $filename = $fileinfo['hashname'].$fileinfo['createDate'].'.'.$fileinfo['extension'];
       $filepath = $CFG->centralizedresources_media_path['indexthumb'].substr($filename,0,2).'/'.$filename;

       $datas = json_decode($data->img_datas);
       crop169resize($filepath,480,270,$datas->x*2, $datas->y*2, $datas->width, $datas->height);
       
       $fileinfo['hashname'] = sha1_file($filepath);
       $fileinfo['filesize'] = filesize($filepath);
       
       $newfilefolder = $CFG->centralizedresources_media_path['indexthumb'].substr($fileinfo['hashname'],0,2).'/';
       if (!file_exists($newfilefolder)) {
           mkdir($newfilefolder,0775,true);
       }
       $newfilepath = $newfilefolder.$fileinfo['hashname'].$fileinfo['createDate'].'.'.$fileinfo['extension'];
       rename($filepath, $newfilepath);
       
    } else {
       $fileinfo = cr_moveFileToMediaFolder('attachments', false);
    }

    $data->filename = $fileinfo['filename'];
    $data->hash = $fileinfo['hashname'];
    $data->extension = $fileinfo['extension'];
    $data->type = $fileinfo['type'];
    $data->filesize = $fileinfo['filesize'];
    $data->cleanname =$fileinfo['cleanname'];
    $data->createDate = $fileinfo['createDate'];
    $data->lastusedate = $fileinfo['createDate'];
    $data->editdate = $fileinfo['createDate'];
    $data->mimetype = $fileinfo['mimetype'];

    $data->resourceid = sha1($data->hash . $data->createDate);

    cr_insertResource($data);

    $indexation = $DB->get_record('local_indexation', array('courseid' => $courseid));
    if(!$indexation){
        throw new moodle_exception('No indexation for this course.');
    }

    if($type == 'thumb'){
        $indexation->thumbnailid = $data->resourceid;
    }else {
        $indexation->videoid = $data->resourceid;
    }

    $DB->update_record('local_indexation', $indexation);

    $msg = get_string('add_thumbnail_confirmation', 'local_indexation');
    redirect($CFG->wwwroot.'/local/indexation/index.php?id='.$courseid, "<p>".$msg."</p>");
}else if($resource_form->is_cancelled()){
    redirect($CFG->wwwroot.'/local/indexation/index.php?id='.$courseid);
}

echo $OUTPUT->header();
$resource_form->display();
echo $OUTPUT->footer();


/**
 * Fonction permettant le redimensionnement des images avant enregistrement/mise à jour des données de la ressource liée à l'indexation
 * @param $imagepath
 * @param $output_width
 * @param $output_height
 * @param $x_o
 * @param $y_o
 * @param $w_o
 * @param $h_o
 * @return bool
 */
function crop169resize($imagepath, $output_width, $output_height,$x_o, $y_o, $w_o, $h_o)
{
    list($w_i, $h_i, $type) = getimagesize($imagepath); // Return the size and image type (number)
   /*
    //calculating 16:9 ratio
    $w_o = $w_i;
    $h_o = 9 * $w_o / 16;


    //if output height is longer then width
    if ($h_i < $h_o) {
        $h_o = $h_i;
        $w_o = 16 * $h_o / 9;
    }


    $x_o = $w_i - $w_o;
    $y_o = $h_i - $h_o;



    if ($x_o + $w_o > $w_i) $w_o = $w_i - $x_o; // If width of output image is bigger then input image (considering x_o), reduce it
    if ($y_o + $h_o > $h_i) $h_o = $h_i - $y_o; // If height of output image is bigger then input image (considering y_o), reduce it
    */
    $types = array("", "gif", "jpeg", "png"); // Array with image types
    $ext = $types[$type]; // If you know image type, "code" of image type, get type name
    if ($ext) {
        $func = 'imagecreatefrom'.$ext; // Get the function name for the type, in the way to create image
        $img_i = $func($imagepath); // Creating the descriptor for input image
    } else {
        echo 'Incorrect image'; // Showing an error, if the image type is unsupported
        return false;
    }

    $img_o = imagecreatetruecolor($w_o, $h_o); // Creating descriptor for input image

    //make transparent background for png files
    $color = imagecolorallocatealpha($img_o, 0, 0, 0, 127); //fill transparent back

    imagesavealpha($img_o, true);
    imagefill($img_o, 0, 0, $color);
    imagecopy($img_o, $img_i, 0, 0, $x_o/2, $y_o/2, $w_o, $h_o); // Move part of image from input to output

    $img_o2 = imagecreatetruecolor($output_width, $output_height);

    //make transparent background for png files
    $color2 = imagecolorallocatealpha($img_o2, 0, 0, 0, 127); //fill transparent back

    imagesavealpha($img_o2, true);
    imagefill($img_o2, 0, 0, $color2);
    imagecopyresampled($img_o2, $img_o, 0, 0, 0, 0, $output_width, $output_height, $w_o, $h_o);


    $func = 'image'.$ext; // Function that allows to save the result
    return $func($img_o2, $imagepath);
}

