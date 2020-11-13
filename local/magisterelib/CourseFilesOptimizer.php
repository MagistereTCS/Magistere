<?php

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');

//require_once($CFG->dirroot.'/local/magisterelib/databaseConnection.php');

class CourseFilesOptimizer
{
    private $courseid;
    private $courseFiles = null;
    private $courseFullname = null;
    
    private $logfile = '';
    
    const FILE_USED = 1;
    const FILE_NOT_USED = 2;
    const FILE_NOT_TESTED = 3;
    
    const RC_TAG_BEGIN = '[[[cr_mlink_';
    const RC_TAG_END = ']]]';
    
    const DEFAULT_CENTRALIZE_MIN_SIZE = 5242880; // 5 Mo
    
    public function __construct($courseid)
	{
        $this->courseid = $courseid;

        $logfilepath = $GLOBALS['CFG']->dataroot.'/logs/optimizer';
        if (!file_exists($logfilepath)){
            mkdir($logfilepath,0770,true);
        }
        $this->logfile = $logfilepath.'/optimizer_'.$courseid.'_'.date('Y-m-d_H-i-s').'.log';
        //echo '##LOGFILE=##'.$this->logfile.'##';
        //$this->l('test','truc');
        
        if ($courseid) {
            $course = get_course($this->courseid);
            $this->courseFullname = $course->fullname;
        }
    }
    
    private function l($msg,$source=''){
        //echo date('Y-m-d_H:i:s::').$source.'__'.$msg."\n";
        file_put_contents($this->logfile, date('Y-m-d_H:i:s::').$source.'__'.$msg."\n", FILE_APPEND);
    }
	
    private function getCourseFiles(){
	    
	    return $GLOBALS['DB']->get_records_sql('SELECT course.uid, course.contextlevel, course.oid, course.sectionnumber, course.otype, course.fid, course.filename, course.userid, course.contenthash, course.contextid, course.component, course.filearea, course.itemid, course.filepath, course.filesize, course.timecreated, course.timemodified
FROM (

SELECT f.id as uid, cx.contextlevel, cx.instanceid AS oid, null as sectionnumber, "course" AS otype, f.id AS fid, f.userid, f.contenthash, f.contextid, f.component, f.filearea, f.itemid, f.filepath, f.filename, f.filesize, f.timecreated, f.timemodified
FROM mdl_files f
INNER JOIN mdl_context cx ON (cx.id=f.contextid)
WHERE f.filename <> "." AND cx.instanceid = ? AND cx.contextlevel = 50 AND referencefileid IS NULL

UNION

SELECT f.id as uid, cx.contextlevel, cm.id AS oid, cs.section as sectionnumber, m.name AS otype, f.id AS fid, f.userid, f.contenthash, f.contextid, f.component, f.filearea, f.itemid, f.filepath, f.filename, f.filesize, f.timecreated, f.timemodified
FROM mdl_files f
INNER JOIN mdl_context cx ON (f.contextid = cx.id)
INNER JOIN mdl_course_modules cm ON (cx.instanceid = cm.id)
INNER JOIN mdl_course_sections cs ON (cm.section = cs.id)
INNER JOIN mdl_modules m ON (m.id = cm.module)
WHERE filename <> "." AND cm.course = ? AND referencefileid IS NULL

UNION

SELECT f.id as uid, cx.contextlevel, bi.id AS oid, null as sectionnumber, bi.blockname AS otype, f.id AS fid, f.userid, f.contenthash, f.contextid, f.component, f.filearea, f.itemid, f.filepath, f.filename, f.filesize, f.timecreated, f.timemodified
FROM mdl_block_instances bi
INNER JOIN mdl_context cx on (cx.contextlevel=80 and bi.id = cx.instanceid)
INNER JOIN mdl_files f on (cx.id = f.contextid)
INNER JOIN mdl_context pcx on (bi.parentcontextid = pcx.id)
INNER JOIN mdl_course c on (pcx.instanceid = c.id)
WHERE filename <> "." AND c.id = ? AND referencefileid IS NULL

) AS course

LEFT JOIN 
(
  SELECT TRUE AS rid,
  REPLACE(SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(b.data,\'contextid";\',-1),\';\',1),\':\',-1),\'"\',\'\') AS contextid,
  REPLACE(SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(b.data,\'component";\',-1),\';\',1),\':\',-1),\'"\',\'\') AS component,
  REPLACE(SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(b.data,\'filearea";\',-1),\';\',1),\':\',-1),\'"\',\'\') AS filearea,
  REPLACE(SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(b.data,\'itemid";\',-1),\';\',1),\':\',-1),\'"\',\'\') AS itemid,
  REPLACE(SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(b.data,\'filepath";\',-1),\';\',1),\':\',-1),\'"\',\'\') AS filepath,
  REPLACE(SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(b.data,\'filename";\',-1),\';\',1),\':\',-1),\'"\',\'\') AS filename
  FROM (SELECT FROM_BASE64(reference) AS data FROM mdl_files_reference ) b
) ref
ON (ref.contextid = course.contextid AND 
ref.component = course.component AND 
ref.filearea = course.filearea AND 
ref.itemid = course.itemid AND 
ref.filepath = course.filepath AND 
ref.filename = course.filename)
WHERE ref.rid IS NULL

ORDER BY course.filesize DESC',array($this->courseid,$this->courseid,$this->courseid));
	    
	}
	
	private function getCourseFilesWithUse($disablecache=false){
	    if ($this->courseFiles != null && !$disablecache){
	        return $this->courseFiles;
	    }
	    $coursefiles = $this->getCourseFiles();
	    
	    foreach($coursefiles AS $coursefile){
	        switch ($coursefile->contextlevel) {
	            case 30:
	                $coursefile->used = self::FILE_NOT_TESTED;
	                
	                break;
	            case 50:
	                $coursefile->used = self::FILE_NOT_TESTED;
	                    
	                break;
	            case 70:
	                if($coursefile->otype == 'hvp' || $coursefile->otype == 'resource' || $coursefile->otype == 'folder'){$coursefile->used = self::FILE_NOT_TESTED; continue 2;}
	                $inIntro = false;
	                if ($coursefile->filearea == 'intro'){
	                   $inIntro = $this->isFileUsedInModIntro($coursefile->otype,$coursefile->oid,rawurlencode($coursefile->filename));
	                }
	                if ($inIntro) {
	                    $coursefile->used = ($inIntro?(self::FILE_USED):(self::FILE_NOT_USED));
	                }else if (method_exists($this,'isFileUsedInMod_'.$coursefile->otype)) {
	                    $coursefile->used = (($this->{'isFileUsedInMod_'.$coursefile->otype}($coursefile->oid,$coursefile->fid,rawurlencode($coursefile->filename)))?(self::FILE_USED):(self::FILE_NOT_USED));
	                }else if (!$inIntro){
	                    $coursefile->used = self::FILE_NOT_USED;
	                }
	                
	                break;
	            case 80:
                    if (method_exists($this,'isFileUsedInBlock_'.$coursefile->otype)) {
                        $coursefile->used = (($this->{'isFileUsedInBlock_'.$coursefile->otype}($coursefile->oid,rawurlencode($coursefile->filename)))?(self::FILE_USED):(self::FILE_NOT_USED));
	                }else{
	                    $coursefile->used = self::FILE_NOT_TESTED;
	                }
	                
	                break;
	            default:
	                $coursefile->used = self::FILE_NOT_TESTED;
	        }
	    }
	    
	    $this->courseFiles = $coursefiles;
	    return $this->courseFiles;
	}
	
	
	public function get_unused_files(){
	    $courses = $this->getCourseFilesWithUse();
	    $unusedcourses = array();
	    foreach($courses AS $key=>$course) {
	        if ($course->used == self::FILE_NOT_USED) {
	            $unusedcourses[$key] = $course;
	        }
	    }
	    return $unusedcourses;
	}
	
	public function get_used_files(){
	    $courses = $this->getCourseFilesWithUse();
	    $usedcourses = array();
	    foreach($courses AS $key=>$course) {
	        if ($course->used == self::FILE_USED) {
	            $usedcourses[$key] = $course;
	        }
	    }
	    return $usedcourses;
	}
	
	public function getConfigCentralizeMinSize(){
	    $conf = get_config('local_magisterelib','centralizeMinSize');
	    if ($conf === false) {
	        $this->setConfigCentralizeMinSize(self::DEFAULT_CENTRALIZE_MIN_SIZE);
	        return self::DEFAULT_CENTRALIZE_MIN_SIZE;
	    }else{
	        return $conf;
	    }
	}
	
	public function setConfigCentralizeMinSize($value){
	    return set_config('centralizeMinSize',$value,'local_magisterelib');
	}
	
	/***
	 * Return all the files used in the course which are bigger than the size given
	 * @param int $size Minimum size of the file in octet
	 * @return object[] An array of object containing informations about the file and the container(course,user,mod,block)
	 */
	public function get_used_files_bigger_than($size=null){
	    if ($size==null){$size=$this->getConfigCentralizeMinSize();}
	    $courses = $this->getCourseFilesWithUse();
	    
	    $usedcourses = array();
	    foreach($courses AS $key=>$course) {
	        if ($course->used == self::FILE_USED && $course->filesize > $size) {
	            $usedcourses[$key] = $course;
	        }
	    }
	    return $usedcourses;
	}
	
	
	public function remove_unused_file($fileid){
	    global $DB;
	    $courses = $this->get_unused_files();
	    
	    if (!isset($courses[$fileid])){
	        return false;
	    }
	    if ($DB->delete_records('files',array('id'=>$fileid))) {
	        unset($this->courseFiles[$fileid]);
	        return true;
	    }
	    return false;
	}
	
	public function sort_files_by_section($filesid) {
	    global $CFG;
	    
	    $files = $this->get_used_files_bigger_than();
	    
	    $filesSelected = array_filter(
	        $files,
	        function ($key) use ($filesid) {
	            return in_array($key, $filesid);
	        },
	        ARRAY_FILTER_USE_KEY
	    );
	    
	    uasort(
	        $filesSelected, 
	        function($file1, $file2) {
	            if (isset($file1->sectionnumber) && isset($file2->sectionnumber)) {
	                return $file1->sectionnumber > $file2->sectionnumber;
	            }
	            return 0;
	        }
	    );
	    
	    $sortedfilesid = array_keys($filesSelected); 
	    return $sortedfilesid;
	}
	
	public function centralize_used_file_and_replace($fileid){
	    global $DB, $CFG;
	    
	    $ls = 'centralize_used_file_and_replace('.$fileid.')';
	    $bigfiles = $this->get_used_files_bigger_than();
	    $this->l('###############',$ls);
	    $this->l('BEGIN centralizing file '.$fileid.')',$ls);
	    
	    if (!isset($bigfiles[$fileid])){
	        $this->l('File not found in the bigfile list ('.$fileid.')',$ls);
	        return false;
	    }
	    
	    $file = $bigfiles[$fileid];
	    $localfilepath = $CFG->dataroot.'/filedir/'.substr($file->contenthash,0,2).'/'.substr($file->contenthash,2,2).'/'.$file->contenthash;
	    
	    $this->l('The moodle file path is '.$localfilepath,$ls);
	    
	    $CDB = get_centralized_db_connection();
	    
	    if (!file_exists($localfilepath)) {
	        $this->l('The file do not exist on the disk, we skip the migration of this file',$ls);
	        return 'Local File not found';
	    }
	    
	    $this->l('The file found on the disk',$ls);
	    
	    // Check if the file already exist in the CR
	    $crfile = $CDB->get_record('cr_resources', array('hashname'=>$file->contenthash));
	    if ($crfile === false) {
	        
	        $this->l('The file do not exist in the centralized ressources, we add it (filehash='.$file->contenthash.')',$ls);
	        
	        // We create the new CR
	        $contributor = null;
	        
	        $owner = $DB->get_record('user', array('id'=>$file->userid));
	        
	        // If the file owner do not exist, we take the admin of the academie as the new owner
	        if ($owner === false){
	            $this->l('The file owner do not exist on moodle, we swap it with the admin account',$ls);
	            $owner = $DB->get_record('user', array('id'=>2));
	        }
	        $this->l('We found the owner : '.print_r($owner,true),$ls);
	        
	        if ($owner->auth == 'shibboleth') {
	            $this->l('The owner is a shibboleth account, we search for the contributor with the same numen ('.$CFG->academie_name.'/'.$owner->username.')',$ls);
	            $contributor = $CDB->get_record('cr_contributor',array('academie'=>$CFG->academie_name,'numen'=>$owner->username));
	        }else{
	            $this->l('The owner is a manual account, we search for the contributor with the same mail ('.$CFG->academie_name.'/'.$owner->email.')',$ls);
	            $contributor = $CDB->get_record('cr_contributor',array('academie'=>$CFG->academie_name,'email'=>$owner->email));
	        }
	        
	        // If we don't found the contributor, we have to create it
	        $contribid = 0;
	        if ($contributor === false) {
	            $this->l('The owner could not be found in the contributor table, we need to create it',$ls);
	            $contributor = new stdClass();
	            $contributor->firstname = $owner->firstname;
	            $contributor->lastname = $owner->lastname;
	            $contributor->email = $owner->email;
	            if ($owner->auth == 'shibboleth'){
	                $contributor->numen = $owner->username;
	            }
	            $contributor->academie = $CFG->academie_name;
	            
	            $this->l('We create the new contributor : '.print_r($contributor,true),$ls);
	            $contribid = $CDB->insert_record('cr_contributor', $contributor);
	        }else{
	            $contribid = $contributor->id;
	        }
	        
	        
	        $newCR = new stdClass();
	        $newCR->name = $file->filename;
	        $newCR->hashname = $file->contenthash;
	        $newCR->description = 'Parcours '.$this->courseFullname.(isset($file->sectionnumber) ? ' - Section '.$file->sectionnumber : '');
	        $newCR->filename = $file->filename;
	        $newCR->cleanname = preg_replace("/[^a-zA-Z0-9\.-;]/", '_', iconv("UTF8", 'ASCII//TRANSLIT//IGNORE', $newCR->filename));
	        $newCR->filesize = $file->filesize;
	        $newCR->extension = pathinfo($file->filename,PATHINFO_EXTENSION);
	        $newCR->createdate = $file->timecreated;
	        $newCR->editdate = $file->timemodified;
	        $newCR->lastusedate = time();
	        $newCR->views = 0;
	        $newCR->contributorid = $contribid;
	        $newCR->public = 1;
	        $newCR->domainrestricted = 1;
	        
	        $newCR->resourceid = sha1($newCR->hashname.$newCR->createdate);
	        
	        $newCR->type = null;
	        
	        // force zip to archive (or else it can be mistaken with the diaporama type)
	        if ($newCR->extension === 'zip') {
	            $newCR->type = 'archive';
	        } else {
	            foreach($CFG->centralizedresources_allow_filetype AS $type=>$exts) {
	                foreach($exts AS $ext) {
	                    if($ext == $newCR->extension){
	                        $this->l('The ressource type is : '.$type,$ls);
	                        $newCR->type = $type;
	                        break 2;
	                    }
	                }
	            }
	        }
	        
	        if ($newCR->type == null) {
	            $this->l('We do not found the ressource type, we use the default type "file"',$ls);
	            $newCR->type = 'file';
	        }
	        
	        $newcrpath = $CFG->centralizedresources_media_path[$newCR->type].substr($newCR->hashname,0,2).'/';
	        $newcrfilename = $newCR->hashname.$newCR->createdate.'.'.$newCR->extension;
	        
	        $this->l('The new ressource path will be : '.$newcrpath,$ls);
	        $this->l('The new ressource filename will be : '.$newcrfilename,$ls);
	        
	        if (!file_exists($newcrpath)){
	            mkdir($newcrpath,0775,true);
	        }
	        
	        if ( copy($localfilepath, $newcrpath.$newcrfilename) ){
	            $this->l('The copy of the moodle file ('.$localfilepath.') to the new path ('.$newcrpath.$newcrfilename.') SUCCEED',$ls);
	        }else{
	            $this->l('The copy of the moodle file ('.$localfilepath.') to the new path ('.$newcrpath.$newcrfilename.') FAILED',$ls);
	            $this->l('Ressource migration aborted',$ls);
	           
	            return 'CR_COPY_FAILED';
	        }
	        
	        if (file_exists($newcrpath.$newcrfilename)) {
	           $newCR->id = $CDB->insert_record('cr_resources', $newCR);
	        }
	        $crfile = $CDB->get_record('cr_resources', array('hashname'=>$file->contenthash));
	        if ($crfile === false) {
	            return 'CR_INSERT_FAILED';
	        }
	    }else{
	        $this->l('The file already exists in the CR, we use the existing one',$ls);
	    }
	    
	    $this->l('We start to replace all file reference of every files',$ls);
	    
	    // we replace the file reference in the context
	    switch ($file->contextlevel) {
	        case 30:
	            
	            break;
	        case 50:
	            
	            break;
	        case 70:
	            $this->replaceFileByRCInModIntro($file->otype,$file->oid,rawurlencode($file->filename),$crfile->resourceid);
	            if (method_exists($this,'replaceFileByRCInMod_'.$file->otype)) {
	                $this->l('Starting ressource replace in isFileUsedInMod_'.$file->otype,$ls);
	                $this->{'replaceFileByRCInMod_'.$file->otype}($file->oid,$file->fid,rawurlencode($file->filename),$crfile->resourceid);
	            }
	            break;
	        case 80:
	            if (method_exists($this,'replaceFileByRCInBlock_'.$file->otype)) {
	                $this->l('Starting ressource replace in replaceFileByRCInBlock_'.$file->otype,$ls);
	                $this->{'replaceFileByRCInBlock_'.$file->otype}($file->oid,rawurlencode($file->filename),$crfile->resourceid);
	            }
	            break;
	    }
	    
	    $this->l('All replacement finished',$ls);
	    
	    // we delete the local file
	    $samefiles = $DB->get_records('files',array('contenthash'=>$file->contenthash));
	    
	    $this->l('We found the file '.count($samefiles).' time in the files table',$ls);
	    
	    // If the file have only one reference, we delete it
	    if (count($samefiles) < 2){
	        $this->l('The file have only one reference so we remove it ('.$localfilepath.')',$ls);
	        unlink($localfilepath);
	    }else{
	        $this->l('The file have multiple references so we keep it ('.$localfilepath.')',$ls);
	    }
	    
	    $this->l('We delete the file from the file table (ID='.$file->fid.')',$ls);
	    $DB->delete_records('files',array('id'=>$file->fid));
	    
	    $this->l('Centralization finished',$ls);
	    
	    return true;
	}
	
	public static function getDialogHtml(){
	    $threshold = display_size((new CourseFilesOptimizer(0))->getConfigCentralizeMinSize());
	    
	    return '
<div id="wf_dialog_optimize" style="display:none;">
    <div id="wf_dialog_optimize_step1" style="display:none" data="Fichiers inutilisés">
	    <div id="wf_dialog_optimize_desc">Les fichiers suivants ont été déposés dans le parcours mais ne sont pas utilisés dans les ressources et les activités : <br/></div>
	    <div id="wf_dialog_optimize_content">
	    <ul></ul>
	    </div>
	    <div id="wf_dialog_optimize_step1_button">
	    <input type="button" id="wf_dialog_optimize_step1_button_submit" value="Effacer les fichiers selectionnés">
	    <input type="button" id="wf_dialog_optimize_step1_button_ignore" value="ignorer cette étape">
	    </div>
	    </div>
	    <div id="wf_dialog_optimize_step2" style="display:none" data="Fichiers utilisés de plus de '.$threshold.'">
	    <div id="wf_dialog_optimize_step2_desc">Les fichiers suivants ont été déposés dans le parcours, il est possible de les externaliser dans les ressources centralisées pour alléger le parcours : <br/></div>
	    <div id="wf_dialog_optimize_step2_content">
	    <ul></ul>
	    </div>
	    <div id="wf_dialog_optimize_step2_info">Il peut exister d\'autres fichiers lourds dans le parcours dans des activités Fichier ou Dossier. Ces cas ne sont pas traités automatiquement dans cette optimisation.</div>
        <div id="wf_dialog_optimize_step2_button">
            <input type="button" id="wf_dialog_optimize_step2_button_submit" value="Centraliser les fichiers selectionnés">
            <input type="button" id="wf_dialog_optimize_step2_button_ignore" value="ignorer cette étape">
        </div>
    </div>
    <div id="wf_dialog_optimize_step3" style="display:none" data="Fin de l\'optimisation">
        <div id="wf_dialog_optimize_step3_content">
            <div id="wf_dialog_optimize_step3_deleted_desc">Les fichiers suivants ont été supprimés : <br/></div>
            <div id="wf_dialog_optimize_step3_deleted_content">
                <ul></ul>
            </div>
            <div id="wf_dialog_optimize_step3_converted_desc">Les fichiers suivants ont été externalisés dans les ressources centralisées : <br/></div>
            <div id="wf_dialog_optimize_step3_converted_content">
                <ul></ul>
            </div>
            <div id="wf_dialog_optimize_step3_failed_desc">Les fichiers suivants n\'ont pas pu être traités : <br/></div>
            <div id="wf_dialog_optimize_step3_failed_content">
                <ul></ul>
            </div>
        </div>
        <div id="wf_dialog_optimize_step3_button">
            <input type="button" id="wf_dialog_optimize_step3_button_close" value="Optimisation terminée">
        </div>
    </div>
    <div id="wf_dialog_optimize_step4" style="display:none" data="Optimisation">
        <div id="wf_dialog_optimize_step4_content">
            <div id="wf_dialog_optimize_step4_noopt_desc">Aucune optimisation n\'est possible pour ce parcours.<br/></div>
        </div>
        <div id="wf_dialog_optimize_step4_button">
            <input type="button" id="wf_dialog_optimize_step4_button_close" value="Fermer">
        </div>
    </div>
</div>';
	}
	
	
	public function get_test(){
	    
	    $courses = $this->getCourseFilesWithUse();
	    $count = array();
	    $count['used'] = 0;
	    $count['notused'] = 0;
	    $count['nottested'] = 0;
	    foreach($courses AS $course) {
	        if ($course->used == self::FILE_NOT_TESTED) {
	            $count['nottested'] += 1;
	            $course->used = 'NOT TESTED';
	        }else if ($course->used == self::FILE_USED) {
	            $count['used'] += 1;
	            $course->used = 'USED';
	        }else{
	            $count['notused'] += 1;
	            $course->used = 'NOT USED';
	        }
	        
	        $course->filename2 = rawurlencode($course->filename);
	    }
	    
	    print_r($count);
	    return $courses;
	}
	
	
	
	
	
	
	private function isFileUsedInBlock_educationalbloc($blockid,$filename) {
	    return (strpos(base64_decode(block_instance_by_id($blockid)->instance->configdata), '@@PLUGINFILE@@/'.$filename)===false?false:true);
	}
	
	private function isFileUsedInBlock_html($blockid,$filename) {
	    return (strpos(base64_decode(block_instance_by_id($blockid)->instance->configdata), '@@PLUGINFILE@@/'.$filename)===false?false:true);
	}
	
	private function isFileUsedInBlock_rolespecifichtml($blockid,$filename) {
	    return (strpos(base64_decode(block_instance_by_id($blockid)->instance->configdata), '@@PLUGINFILE@@/'.$filename)===false?false:true);
	}
	
	
	private function isFileUsedInModIntro($modname,$cmid,$filename) {
	    global $DB;
	    
	    $mod = $DB->get_record_sql('SELECT intro FROM {'.$modname.'} WHERE id = (SELECT instance FROM {course_modules} WHERE id = ?)', array($cmid));
	    if ($mod === false){return false;}
	    
	    // Mod intro
	    if (strpos($mod->intro, '@@PLUGINFILE@@/'.$filename)!==false) {
	        return true;
	    }
	    return false;
	}
	
	private function isFileUsedInMod_book($cmid,$fileid,$filename) {
	    global $DB;
	    
	    $book = $DB->get_record_sql('SELECT id FROM {book} WHERE id = (SELECT instance FROM {course_modules} WHERE id = ?)', array($cmid));
	    
	    // Book chapters
	    $chapters = $DB->get_records('book_chapters',array('bookid'=>$book->id));
	    foreach ($chapters AS $chapter) {
	        if (strpos($chapter->content, '@@PLUGINFILE@@/'.$filename)!==false) {
	            return true;
	        }
	    }
	    return false;
	}
	
	private function isFileUsedInMod_feedback($cmid,$fileid,$filename) {
	    global $DB;
	    
	    $feedback = $DB->get_record_sql('SELECT id FROM {feedback} WHERE id = (SELECT instance FROM {course_modules} WHERE id = ?)', array($cmid));
	    
	    // Feedback items
	    $feedback_items = $DB->get_records('feedback_item',array('feedback'=>$feedback->id));
	    foreach ($feedback_items AS $feedback_item) {
	        if (strpos($feedback_item->presentation, '@@PLUGINFILE@@/'.$filename)!==false) {
	            return true;
	        }
	    }
	    // TODO : feedback_value?  feedback_valuetmp?
	    return false;
	}
	
	private function isFileUsedInMod_forum($cmid,$fileid,$filename) {
	    global $DB;
	    
	    $forum = $DB->get_record_sql('SELECT id FROM {forum} WHERE id = (SELECT instance FROM {course_modules} WHERE id = ?)', array($cmid));
	    
	    // Forum posts
	    $forum_posts = $DB->get_records_sql('SELECT message FROM {forum_posts} WHERE discussion IN (SELECT id FROM {forum_discussions} WHERE forum = ?)',array($forum->id));
	    foreach ($forum_posts AS $forum_post) {
	        if (strpos($forum_post->message, '@@PLUGINFILE@@/'.$filename)!==false) {
	            return true;
	        }
	    }
	    return false;
	}
	
	private function isFileUsedInMod_glossary($cmid,$fileid,$filename) {
	    global $DB;
	    
	    $glossary = $DB->get_record_sql('SELECT id FROM {glossary} WHERE id = (SELECT instance FROM {course_modules} WHERE id = ?)', array($cmid));
	    
	    // Glossary entries
	    $glossary_entries = $DB->get_records('glossary_entries',array('glossaryid'=>$glossary->id));
	    foreach ($glossary_entries AS $glossary_entrie) {
	        if (strpos($glossary_entrie->definition, '@@PLUGINFILE@@/'.$filename)!==false) {
	            return true;
	        }
	    }
	    return false;
	}
	
	private function isFileUsedInMod_lesson($cmid,$fileid,$filename) {
	    global $DB;
	    
	    $lesson = $DB->get_record_sql('SELECT id FROM {lesson} WHERE id = (SELECT instance FROM {course_modules} WHERE id = ?)', array($cmid));
	    
	    // Lesson pages
	    $lesson_pages = $DB->get_records('lesson_pages',array('lessonid'=>$lesson->id));
	    foreach ($lesson_pages AS $lesson_page) {
	        if (strpos($lesson_page->contents, '@@PLUGINFILE@@/'.$filename)!==false) {
	            return true;
	        }
	    }
	    
	    // Lesson answers
	    $lesson_answers = $DB->get_records('lesson_answers',array('lessonid'=>$lesson->id));
	    foreach ($lesson_answers AS $lesson_answer) {
	        if (strpos($lesson_answer->answer, '@@PLUGINFILE@@/'.$filename)!==false) {
	            return true;
	        }
	        if (strpos($lesson_answer->response, '@@PLUGINFILE@@/'.$filename)!==false) {
	            return true;
	        }
	    }
	    
	    // Lesson attempts
	    $lesson_attempts = $DB->get_records('lesson_attempts',array('lessonid'=>$lesson->id));
	    foreach ($lesson_attempts AS $lesson_attempt) {
	        if (strpos($lesson_attempt->useranswer, '@@PLUGINFILE@@/'.$filename)!==false) {
	            return true;
	        }
	    }
	    return false;
	}
	
	private function isFileUsedInMod_page($cmid,$fileid,$filename) {
	    global $DB;
	    
	    $page = $DB->get_record_sql('SELECT id, content FROM {page} WHERE id = (SELECT instance FROM {course_modules} WHERE id = ?)', array($cmid));
	    
	    if (strpos($page->content, '@@PLUGINFILE@@/'.$filename)!==false) {
	        return true;
	    }
	    return false;
	}
	
	private function isFileUsedInMod_publication($cmid,$fileid,$filename) {
        global $DB;
        
        $publication = $DB->get_record_sql('SELECT id FROM {publication} WHERE id = (SELECT instance FROM {course_modules} WHERE id = ?)', array($cmid));
        
        // Publication files
        $publication_files = $DB->get_records('publication_file',array('publication'=>$publication->id));
        foreach ($publication_files AS $publication_file) {
            if ($publication_file->fileid == $fileid) {
                return true;
            }
        }
        return false;
    }
    
    private function isFileUsedInMod_questionnaire($cmid,$fileid,$filename) {
        global $DB;
        
        $questionnaire_surveys = $DB->get_records('questionnaire_survey',array('courseid'=>$this->courseid));
        foreach ($questionnaire_surveys AS $questionnaire_survey) {
            if (strpos($questionnaire_survey->info, '@@PLUGINFILE@@/'.$filename)!==false) {
                return true;
            }
            if (strpos($questionnaire_survey->thank_body, '@@PLUGINFILE@@/'.$filename)!==false) {
                return true;
            }
            if (strpos($questionnaire_survey->feedbacknotes, '@@PLUGINFILE@@/'.$filename)!==false) {
                return true;
            }
            
            // Questionnaire question
            $questionnaire_questions = $DB->get_records('questionnaire_question',array('surveyid'=>$questionnaire_survey->id));
            foreach ($questionnaire_questions AS $questionnaire_question) {
                if (strpos($questionnaire_question->content, '@@PLUGINFILE@@/'.$filename)!==false) {
                    return true;
                }
                
                // Questionnaire response text
                $questionnaire_response_texts = $DB->get_records('questionnaire_response_text',array('question_id'=>$questionnaire_question->id));
                foreach ($questionnaire_response_texts AS $questionnaire_response_text) {
                    if (strpos($questionnaire_response_text->response, '@@PLUGINFILE@@/'.$filename)!==false) {
                        return true;
                    }
                }
                
                // Questionnaire response other
                $questionnaire_response_others = $DB->get_records('questionnaire_response_other',array('question_id'=>$questionnaire_question->id));
                foreach ($questionnaire_response_others AS $questionnaire_response_other) {
                    if (strpos($questionnaire_response_other->response, '@@PLUGINFILE@@/'.$filename)!==false) {
                        return true;
                    }
                }
                
            }
            
            // Questionnaire feedback sections
            $questionnaire_fb_sections = $DB->get_records('questionnaire_fb_sections',array('surveyid'=>$questionnaire_survey->id));
            foreach ($questionnaire_fb_sections AS $questionnaire_fb_section) {
                if (strpos($questionnaire_fb_section->definition, '@@PLUGINFILE@@/'.$filename)!==false) {
                    return true;
                }
                
                // Questionnaire feedback
                $questionnaire_feedbacks = $DB->get_records('questionnaire_feedback',array('sectionid'=>$questionnaire_fb_sections->id));
                foreach ($questionnaire_feedbacks AS $questionnaire_feedback) {
                    if (strpos($questionnaire_feedback->response, '@@PLUGINFILE@@/'.$filename)!==false) {
                        return true;
                    }
                }
            }
        }
        return false;
    }
    
    private function isFileUsedInMod_quiz($cmid,$fileid,$filename) {
        global $DB;
        
        $quiz = $DB->get_record_sql('SELECT id FROM {quiz} WHERE id = (SELECT instance FROM {course_modules} WHERE id = ?)', array($cmid));
        
        // Quiz Feedback
        $quiz_feedbacks = $DB->get_records('quiz_feedback',array('quizid'=>$quiz->id));
        foreach ($quiz_feedbacks AS $quiz_feedback) {
            if (strpos($quiz_feedback->feedbacktext, '@@PLUGINFILE@@/'.$filename)!==false) {
                return true;
            }
        }
        
        // Quiz Question
        $questions = $DB->get_records_sql('SELECT * FROM {question} WHERE id IN (SELECT questionid FROM {quiz_slots} WHERE quizid = ?)',array($quiz->id));
        foreach ($questions AS $question) {
            if (strpos($question->questiontext, '@@PLUGINFILE@@/'.$filename)!==false) {
                return true;
            }
            
            if (strpos($question->generalfeedback, '@@PLUGINFILE@@/'.$filename)!==false) {
                return true;
            }
            
            switch($question->qtype) {
                case 'essay':
                    $essays = $DB->get_records('qtype_essay_options',array('questionid'=>$question->id));
                    
                    foreach ($essays AS $essay) {
                        
                        if (strpos($essay->graderinfo, '@@PLUGINFILE@@/'.$filename)!==false) {
                            return true;
                        }
                        if (strpos($essay->responsetemplate, '@@PLUGINFILE@@/'.$filename)!==false) {
                            return true;
                        }
                    }
                    break;
                case 'ddimageortext':
                    $ddimageortexts = $DB->get_records('qtype_ddimageortext',array('questionid'=>$question->id));
                    foreach ($ddimageortexts AS $ddimageortext) {
                        if (strpos($ddimageortext->correctfeedback, '@@PLUGINFILE@@/'.$filename)!==false) {
                            return true;
                        }
                        if (strpos($ddimageortext->partiallycorrectfeedback, '@@PLUGINFILE@@/'.$filename)!==false) {
                            return true;
                        }
                        if (strpos($ddimageortext->incorrectfeedback, '@@PLUGINFILE@@/'.$filename)!==false) {
                            return true;
                        }
                    }
                    // Drag? Drop?
                    break;
                case 'ddmarker':
                    $ddmarkers = $DB->get_records('qtype_ddmarker',array('questionid'=>$question->id));
                    foreach ($ddmarkers AS $ddmarker) {
                        if (strpos($ddmarker->correctfeedback, '@@PLUGINFILE@@/'.$filename)!==false) {
                            return true;
                        }
                        if (strpos($ddmarker->partiallycorrectfeedback, '@@PLUGINFILE@@/'.$filename)!==false) {
                            return true;
                        }
                        if (strpos($ddmarker->incorrectfeedback, '@@PLUGINFILE@@/'.$filename)!==false) {
                            return true;
                        }
                    }
                    // Drag? Drop?
                    break;
                case 'match':
                    $matchs = $DB->get_records('qtype_match_options',array('questionid'=>$question->id));
                    foreach ($matchs AS $match) {
                        if (strpos($match->correctfeedback, '@@PLUGINFILE@@/'.$filename)!==false) {
                            return true;
                        }
                        if (strpos($match->partiallycorrectfeedback, '@@PLUGINFILE@@/'.$filename)!==false) {
                            return true;
                        }
                        if (strpos($match->incorrectfeedback, '@@PLUGINFILE@@/'.$filename)!==false) {
                            return true;
                        }
                    }
                    $match_subquestions = $DB->get_records('qtype_match_subquestions',array('questionid'=>$question->id));
                    foreach ($match_subquestions AS $match_subquestion) {
                        if (strpos($match_subquestion->questiontext, '@@PLUGINFILE@@/'.$filename)!==false) {
                            return true;
                        }
                    }
                    break;
                case 'multichoice':
                    $multichoices = $DB->get_records('qtype_multichoice_options',array('questionid'=>$question->id));
                    foreach ($multichoices AS $multichoice) {
                        if (strpos($multichoice->correctfeedback, '@@PLUGINFILE@@/'.$filename)!==false) {
                            return true;
                        }
                        if (strpos($multichoice->partiallycorrectfeedback, '@@PLUGINFILE@@/'.$filename)!==false) {
                            return true;
                        }
                        if (strpos($multichoice->incorrectfeedback, '@@PLUGINFILE@@/'.$filename)!==false) {
                            return true;
                        }
                    }
                    break;
                case 'poodllrecording':
                    $poodllrecordings = $DB->get_records('qtype_poodllrecording_opts',array('questionid'=>$question->id));
                    foreach ($poodllrecordings AS $poodllrecording) {
                        if (strpos($poodllrecording->graderinfo, '@@PLUGINFILE@@/'.$filename)!==false) {
                            return true;
                        }
                        if (strpos($poodllrecording->backimage, '@@PLUGINFILE@@/'.$filename)!==false) {
                            return true;
                        }
                        if (strpos($poodllrecording->boardsize, '@@PLUGINFILE@@/'.$filename)!==false) {
                            return true;
                        }
                    }
                    break;
                case 'randomsamatch':
                    $randomsamatchs = $DB->get_records('qtype_randomsamatch_options',array('questionid'=>$question->id));
                    foreach ($randomsamatchs AS $randomsamatch) {
                        if (strpos($randomsamatch->correctfeedback, '@@PLUGINFILE@@/'.$filename)!==false) {
                            return true;
                        }
                        if (strpos($randomsamatch->partiallycorrectfeedback, '@@PLUGINFILE@@/'.$filename)!==false) {
                            return true;
                        }
                        if (strpos($randomsamatch->incorrectfeedback, '@@PLUGINFILE@@/'.$filename)!==false) {
                            return true;
                        }
                    }
                  break;
                case 'calculated':
                    $calculateds = $DB->get_records('question_calculated_options',array('question'=>$question->id));
                    foreach ($calculateds AS $calculated) {
                        if (strpos($calculated->correctfeedback, '@@PLUGINFILE@@/'.$filename)!==false) {
                            return true;
                        }
                        if (strpos($calculated->partiallycorrectfeedback, '@@PLUGINFILE@@/'.$filename)!==false) {
                            return true;
                        }
                        if (strpos($calculated->incorrectfeedback, '@@PLUGINFILE@@/'.$filename)!==false) {
                            return true;
                        }
                    }
                  break;
                case 'ddwtos':
                    $ddwtos = $DB->get_records('question_ddwtos',array('questionid'=>$question->id));
                    foreach ($ddwtos AS $ddwto) {
                        if (strpos($ddwto->correctfeedback, '@@PLUGINFILE@@/'.$filename)!==false) {
                            return true;
                        }
                        if (strpos($ddwto->partiallycorrectfeedback, '@@PLUGINFILE@@/'.$filename)!==false) {
                            return true;
                        }
                        if (strpos($ddwto->incorrectfeedback, '@@PLUGINFILE@@/'.$filename)!==false) {
                            return true;
                        }
                    }
                  break;
                case 'gapselect':
                    $gapselects = $DB->get_records('question_gapselect',array('questionid'=>$question->id));
                    foreach ($gapselects AS $gapselect) {
                        if (strpos($gapselect->correctfeedback, '@@PLUGINFILE@@/'.$filename)!==false) {
                            return true;
                        }
                        if (strpos($gapselect->partiallycorrectfeedback, '@@PLUGINFILE@@/'.$filename)!==false) {
                            return true;
                        }
                        if (strpos($gapselect->incorrectfeedback, '@@PLUGINFILE@@/'.$filename)!==false) {
                            return true;
                        }
                    }
                  break;
            }
            
            // Quiz question answers
            $answers = $DB->get_records('question_answers',array('question'=>$question->id));
            foreach ($answers AS $answer) {
                if (strpos($answer->answer, '@@PLUGINFILE@@/'.$filename)!==false) {
                    return true;
                }
                
                if (strpos($answer->feedback, '@@PLUGINFILE@@/'.$filename)!==false) {
                    return true;
                }
            }
            
            // Quiz question attemps
            $attempts = $DB->get_records('question_attempts',array('questionid'=>$question->id));
            foreach ($attempts AS $attempt) {
                if (strpos($attempt->questionsummary, '@@PLUGINFILE@@/'.$filename)!==false) {
                    return true;
                }
                if (strpos($attempt->rightanswer, '@@PLUGINFILE@@/'.$filename)!==false) {
                    return true;
                }
                if (strpos($attempt->responsesummary, '@@PLUGINFILE@@/'.$filename)!==false) {
                    return true;
                }
            }
            
            // Quiz question hints
            $answers = $DB->get_records('question_hints',array('questionid'=>$question->id));
            foreach ($answers AS $answer) {
                if (strpos($answer->hint, '@@PLUGINFILE@@/'.$filename)!==false) {
                    return true;
                }
            }
            
            // Quiz question response analysis
            $analysis = $DB->get_records('question_response_analysis',array('questionid'=>$question->id));
            foreach ($analysis AS $analysi) {
                if (strpos($analysi->response, '@@PLUGINFILE@@/'.$filename)!==false) {
                    return true;
                }
            }
            
            // Quiz question categories
            $categories = $DB->get_records('question_categories',array('id'=>$question->category));
            foreach ($categories AS $categorie) {
                if (strpos($categorie->info, '@@PLUGINFILE@@/'.$filename)!==false) {
                    return true;
                }
            }
        }
        return false;
    }
    
    /*
    private function isFileUsedInMod_resource($cmid,$fileid,$filename) {
        global $DB;
        
        $resource = $DB->get_record_sql('SELECT id FROM {resource} WHERE id = (SELECT instance FROM {course_modules} WHERE id = ?)', array($cmid));
        
        // Glossary entries
        $glossary_entries = $DB->get_records('glossary_entries',array('glossaryid'=>$glossary->id));
        foreach ($glossary_entries AS $glossary_entrie) {
            if (strpos($glossary_entrie->definition, '@@PLUGINFILE@@/'.$filename)!==false) {
                return true;
            }
        }
        return false;
    }*/
    
    private function isFileUsedInMod_scorm($cmid,$fileid,$filename) {
        global $DB;
        
        $cm_context = context_module::instance($cmid);
        
        $scorm = $DB->get_record_sql('SELECT id FROM {files} WHERE id = ? AND contextid = ?', array($fileid,$cm_context->id));
        
        return $scorm !== false;
    }
    
    private function isFileUsedInMod_tracker($cmid,$fileid,$filename) {
        global $DB;
        
        $glossary = $DB->get_record_sql('SELECT id FROM {tracker} WHERE id = (SELECT instance FROM {course_modules} WHERE id = ?)', array($cmid));
        
        // Tracker issues
        $tracker_issues = $DB->get_records('tracker_issue',array('trackerid'=>$glossary->id));
        foreach ($tracker_issues AS $tracker_issue) {
            if (strpos($tracker_issue->description, '@@PLUGINFILE@@/'.$filename)!==false) {
                return true;
            }
            
            if (strpos($tracker_issue->resolution, '@@PLUGINFILE@@/'.$filename)!==false) {
                return true;
            }
        }
        
        // Tracker issues comment
        $tracker_issuecomments = $DB->get_records('tracker_issuecomment',array('trackerid'=>$glossary->id));
        foreach ($tracker_issuecomments AS $tracker_issuecomment) {
            if (strpos($tracker_issuecomment->comment, '@@PLUGINFILE@@/'.$filename)!==false) {
                return true;
            }
        }
        
        // Tracker issues dependancy
        $tracker_issuedependancys = $DB->get_records('tracker_issuedependancy',array('trackerid'=>$glossary->id));
        foreach ($tracker_issuedependancys AS $tracker_issuedependancy) {
            if (strpos($tracker_issuedependancy->comment, '@@PLUGINFILE@@/'.$filename)!==false) {
                return true;
            }
        }
        return false;
    }
    
    private function isFileUsedInMod_viaassign($cmid,$fileid,$filename) {
        global $DB;
        
        $viaassign = $DB->get_record_sql('SELECT id FROM {viaassign} WHERE id = (SELECT instance FROM {course_modules} WHERE id = ?)', array($cmid));
        
        // Viaassign Feedback comments
        $viaassignfeedback_comments = $DB->get_records('viaassignfeedback_comments',array('viaassign'=>$viaassign->id));
        foreach ($viaassignfeedback_comments AS $viaassignfeedback_comment) {
            if (strpos($viaassignfeedback_comment->commenttext, '@@PLUGINFILE@@/'.$filename)!==false) {
                return true;
            }
        }
        return false;
    }
    
    private function isFileUsedInMod_wiki($cmid,$fileid,$filename) {
        global $DB;
        
        $wiki = $DB->get_record_sql('SELECT id FROM {wiki} WHERE id = (SELECT instance FROM {course_modules} WHERE id = ?)', array($cmid));
        
        // Wiki page and version
        $wiki_subwikis = $DB->get_records('wiki_subwikis',array('wikiid'=>$wiki->id));
        foreach ($wiki_subwikis AS $wiki_subwiki) {
            $wiki_pages = $DB->get_records('wiki_pages',array('subwikiid'=>$wiki_subwiki->id));
            foreach ($wiki_pages AS $wiki_page) {
                if (strpos($wiki_page->cachedcontent, '@@PLUGINFILE@@/'.$filename)!==false) {
                    return true;
                }
                
                $wiki_versions = $DB->get_records('wiki_versions',array('pageid'=>$wiki->id));
                foreach ($wiki_versions AS $wiki_version) {
                    if (strpos($wiki_version->content, '@@PLUGINFILE@@/'.$filename)!==false) {
                        return true;
                    }
                }
                
            }
        }
        return false;
    }
    
    private function isFileUsedInMod_workshop($cmid,$fileid,$filename) {
        global $DB;
        
        $workshop = $DB->get_record_sql('SELECT * FROM {workshop} WHERE id = (SELECT instance FROM {course_modules} WHERE id = ?)', array($cmid));
        
        if (strpos($workshop->instructauthors, '@@PLUGINFILE@@/'.$filename)!==false) {
            return true;
        }
        if (strpos($workshop->instructreviewers, '@@PLUGINFILE@@/'.$filename)!==false) {
            return true;
        }
        
        if (strpos($workshop->conclusion, '@@PLUGINFILE@@/'.$filename)!==false) {
            return true;
        }
        
        
        
        // Workshop form accumulative
        $workshopform_accumulatives = $DB->get_records('workshopform_accumulative',array('workshopid'=>$workshop->id));
        foreach ($workshopform_accumulatives AS $workshopform_accumulative) {
            if (strpos($workshopform_accumulative->description, '@@PLUGINFILE@@/'.$filename)!==false) {
                return true;
            }
        }
        
        // Workshop form comments
        $workshopform_comments = $DB->get_records('workshopform_comments',array('workshopid'=>$workshop->id));
        foreach ($workshopform_comments AS $workshopform_comment) {
            if (strpos($workshopform_comment->description, '@@PLUGINFILE@@/'.$filename)!==false) {
                return true;
            }
        }
        
        // Workshop form numerrors
        $workshopform_numerrors = $DB->get_records('workshopform_numerrors',array('workshopid'=>$workshop->id));
        foreach ($workshopform_numerrors AS $workshopform_numerror) {
            if (strpos($workshopform_numerror->description, '@@PLUGINFILE@@/'.$filename)!==false) {
                return true;
            }
        }
        
        // Workshop form rubric
        $workshopform_rubrics = $DB->get_records('workshopform_rubric',array('workshopid'=>$workshop->id));
        foreach ($workshopform_rubrics AS $workshopform_rubric) {
            if (strpos($workshopform_rubric->description, '@@PLUGINFILE@@/'.$filename)!==false) {
                return true;
            }
        }
        
        // Workshop submissions
        $workshop_submissions = $DB->get_records('workshop_submissions',array('workshopid'=>$workshop->id));
        foreach ($workshop_submissions AS $workshop_submission) {
            if (strpos($workshop_submission->content, '@@PLUGINFILE@@/'.$filename)!==false) {
                return true;
            }
            if (strpos($workshop_submission->feedbackauthor, '@@PLUGINFILE@@/'.$filename)!==false) {
                return true;
            }
            
            // Workshop assessments
            $workshop_assessments = $DB->get_records('workshop_assessments',array('submissionid'=>$workshop_submission->id));
            foreach ($workshop_assessments AS $workshop_assessment) {
                if (strpos($workshop_assessment->feedbackauthor, '@@PLUGINFILE@@/'.$filename)!==false) {
                    return true;
                }
                if (strpos($workshop_assessment->feedbackreviewer, '@@PLUGINFILE@@/'.$filename)!==false) {
                    return true;
                }
                
                // Workshop grades
                $workshop_grades = $DB->get_records('workshop_grades',array('assessmentid'=>$workshop_assessment->id));
                foreach ($workshop_grades AS $workshop_grade) {
                    if (strpos($workshop_grade->peercomment, '@@PLUGINFILE@@/'.$filename)!==false) {
                        return true;
                    }
                }
            }
        }
        
        
        return false;
    }
	
	
    
    
    
    private function replaceFileByRCInBlock_educationalbloc($blockid,$filename,$ressourceid) {
        global $DB;
        $block = $DB->get_record('block_instances', array('id'=>$blockid));
        $configdata64 = base64_decode($block->configdata);
        if(strpos($configdata64, '@@PLUGINFILE@@/'.$filename)!==false) {
            $configdata = unserialize($configdata64);
            foreach($configdata AS $key => $data) {
                if(strpos($data, '@@PLUGINFILE@@/'.$filename)!==false) {
                    $configdata->{$key} = str_replace('@@PLUGINFILE@@/'.$filename,self::RC_TAG_BEGIN.$ressourceid.self::RC_TAG_END,$data);
                    $this->l('Old file reference replaced (blockid='.$blockid.'/filename='.$filename.'/ressourceid='.$ressourceid.')',__FUNCTION__);
                }
            }
            $block->configdata = base64_encode(serialize($configdata));
            $DB->update_record('block_instances', $block);
        }
    }
    
    private function replaceFileByRCInBlock_html($blockid,$filename,$ressourceid) {
        global $DB;
        $block = $DB->get_record('block_instances', array('id'=>$blockid));
        $configdata64 = base64_decode($block->configdata);
        if(strpos($configdata64, '@@PLUGINFILE@@/'.$filename)!==false) {
            $configdata = unserialize($configdata64);
            foreach($configdata AS $key => $data) {
                if(strpos($data, '@@PLUGINFILE@@/'.$filename)!==false) {
                    $configdata->{$key} = str_replace('@@PLUGINFILE@@/'.$filename,self::RC_TAG_BEGIN.$ressourceid.self::RC_TAG_END,$data);
                    $this->l('Old file reference replaced (blockid='.$blockid.'/filename='.$filename.'/ressourceid='.$ressourceid.')',__FUNCTION__);
                }
            }
            $block->configdata = base64_encode(serialize($configdata));
            $DB->update_record('block_instances', $block);
        }
    }
    
    private function replaceFileByRCInBlock_rolespecifichtml($blockid,$filename,$ressourceid) {
        global $DB;
        $block = $DB->get_record('block_instances', array('id'=>$blockid));
        $configdata64 = base64_decode($block->configdata);
        if(strpos($configdata64, '@@PLUGINFILE@@/'.$filename)!==false) {
            $configdata = unserialize($configdata64);
            foreach($configdata AS $key => $data) {
                if(strpos($data, '@@PLUGINFILE@@/'.$filename)!==false) {
                    $configdata->{$key} = str_replace('@@PLUGINFILE@@/'.$filename,self::RC_TAG_BEGIN.$ressourceid.self::RC_TAG_END,$data);
                    $this->l('Old file reference replaced (blockid='.$blockid.'/filename='.$filename.'/ressourceid='.$ressourceid.')',__FUNCTION__);
                }
            }
            $block->configdata = base64_encode(serialize($configdata));
            $DB->update_record('block_instances', $block);
        }
    }
    
    
    private function replaceFileByRCInModIntro($modname,$cmid,$filename,$ressourceid) {
        global $DB;
        
        $mod = $DB->get_record_sql('SELECT id,intro FROM {'.$modname.'} WHERE id = (SELECT instance FROM {course_modules} WHERE id = ?)', array($cmid));
        if ($mod === false){return false;}
        
        // Mod intro
        if (strpos($mod->intro, '@@PLUGINFILE@@/'.$filename)!==false) {
            $mod->intro = str_replace('@@PLUGINFILE@@/'.$filename,self::RC_TAG_BEGIN.$ressourceid.self::RC_TAG_END,$mod->intro);
            $DB->update_record($modname, $mod);
            $this->l('Old file reference replaced (modname='.$modname.'/cmid='.$cmid.'/filename='.$filename.'/ressourceid='.$ressourceid.')',__FUNCTION__);
        }
        return false;
    }
    
    private function replaceFileByRCInMod_book($cmid,$fileid,$filename,$ressourceid) {
        global $DB;
        
        $book = $DB->get_record_sql('SELECT id FROM {book} WHERE id = (SELECT instance FROM {course_modules} WHERE id = ?)', array($cmid));
        
        // Book chapters
        $chapters = $DB->get_records('book_chapters',array('bookid'=>$book->id));
        foreach ($chapters AS $chapter) {
            if (strpos($chapter->content, '@@PLUGINFILE@@/'.$filename)!==false) {
                $chapter->content = str_replace('@@PLUGINFILE@@/'.$filename,self::RC_TAG_BEGIN.$ressourceid.self::RC_TAG_END,$chapter->content);
                $DB->update_record('book_chapters', $chapter);
                $this->l('Old file reference replaced (cmid='.$cmid.'/fileid='.$fileid.'/filename='.$filename.'/ressourceid='.$ressourceid.')',__FUNCTION__);
            }
        }
        return false;
    }
    
    private function replaceFileByRCInMod_feedback($cmid,$fileid,$filename,$ressourceid) {
        global $DB;
        
        $feedback = $DB->get_record_sql('SELECT id FROM {feedback} WHERE id = (SELECT instance FROM {course_modules} WHERE id = ?)', array($cmid));
        
        // Feedback items
        $feedback_items = $DB->get_records('feedback_item',array('feedback'=>$feedback->id));
        foreach ($feedback_items AS $feedback_item) {
            if (strpos($feedback_item->presentation, '@@PLUGINFILE@@/'.$filename)!==false) {
                $feedback_item->presentation = str_replace('@@PLUGINFILE@@/'.$filename,self::RC_TAG_BEGIN.$ressourceid.self::RC_TAG_END,$feedback_item->presentation);
                $DB->update_record('feedback_item', $feedback_item);
                $this->l('Old file reference replaced (cmid='.$cmid.'/fileid='.$fileid.'/filename='.$filename.'/ressourceid='.$ressourceid.')',__FUNCTION__);
            }
        }
        // TODO : feedback_value?  feedback_valuetmp?
        return false;
    }
    
    private function replaceFileByRCInMod_forum($cmid,$fileid,$filename,$ressourceid) {
        global $DB;
        
        $forum = $DB->get_record_sql('SELECT id FROM {forum} WHERE id = (SELECT instance FROM {course_modules} WHERE id = ?)', array($cmid));
        
        // Forum posts
        $forum_posts = $DB->get_records_sql('SELECT id,message FROM {forum_posts} WHERE discussion IN (SELECT id FROM {forum_discussions} WHERE forum = ?)',array($forum->id));
        foreach ($forum_posts AS $forum_post) {
            if (strpos($forum_post->message, '@@PLUGINFILE@@/'.$filename)!==false) {
                $forum_post->message = str_replace('@@PLUGINFILE@@/'.$filename,self::RC_TAG_BEGIN.$ressourceid.self::RC_TAG_END,$forum_post->message);
                $DB->update_record('forum_posts', $forum_post);
                $this->l('Old file reference replaced (cmid='.$cmid.'/fileid='.$fileid.'/filename='.$filename.'/ressourceid='.$ressourceid.')',__FUNCTION__);
            }
        }
        return false;
    }
    
    private function replaceFileByRCInMod_glossary($cmid,$fileid,$filename,$ressourceid) {
        global $DB;
        
        $glossary = $DB->get_record_sql('SELECT id FROM {glossary} WHERE id = (SELECT instance FROM {course_modules} WHERE id = ?)', array($cmid));
        
        // Glossary entries
        $glossary_entries = $DB->get_records('glossary_entries',array('glossaryid'=>$glossary->id));
        foreach ($glossary_entries AS $glossary_entrie) {
            if (strpos($glossary_entrie->definition, '@@PLUGINFILE@@/'.$filename)!==false) {
                $glossary_entrie->definition = str_replace('@@PLUGINFILE@@/'.$filename,self::RC_TAG_BEGIN.$ressourceid.self::RC_TAG_END,$glossary_entrie->definition);
                $DB->update_record('glossary_entries', $glossary_entrie);
                $this->l('Old file reference replaced (cmid='.$cmid.'/fileid='.$fileid.'/filename='.$filename.'/ressourceid='.$ressourceid.')',__FUNCTION__);
            }
        }
        return false;
    }
    
    private function replaceFileByRCInMod_lesson($cmid,$fileid,$filename,$ressourceid) {
        global $DB;
        
        $lesson = $DB->get_record_sql('SELECT id FROM {lesson} WHERE id = (SELECT instance FROM {course_modules} WHERE id = ?)', array($cmid));
        
        // Lesson pages
        $lesson_pages = $DB->get_records('lesson_pages',array('lessonid'=>$lesson->id));
        foreach ($lesson_pages AS $lesson_page) {
            if (strpos($lesson_page->contents, '@@PLUGINFILE@@/'.$filename)!==false) {
                $lesson_page->contents = str_replace('@@PLUGINFILE@@/'.$filename,self::RC_TAG_BEGIN.$ressourceid.self::RC_TAG_END,$lesson_page->contents);
                $DB->update_record('lesson_pages', $lesson_page);
                $this->l('Old file reference replaced (cmid='.$cmid.'/fileid='.$fileid.'/filename='.$filename.'/ressourceid='.$ressourceid.')',__FUNCTION__);
            }
        }
        
        // Lesson answers
        $lesson_answers = $DB->get_records('lesson_answers',array('lessonid'=>$lesson->id));
        foreach ($lesson_answers AS $lesson_answer) {
            if (strpos($lesson_answer->answer, '@@PLUGINFILE@@/'.$filename)!==false) {
                $lesson_answer->answer = str_replace('@@PLUGINFILE@@/'.$filename,self::RC_TAG_BEGIN.$ressourceid.self::RC_TAG_END,$lesson_answer->answer);
                $DB->update_record('lesson_answers', $lesson_answer);
                $this->l('Old file reference replaced (cmid='.$cmid.'/fileid='.$fileid.'/filename='.$filename.'/ressourceid='.$ressourceid.')',__FUNCTION__);
            }
            if (strpos($lesson_answer->response, '@@PLUGINFILE@@/'.$filename)!==false) {
                $lesson_answer->response = str_replace('@@PLUGINFILE@@/'.$filename,self::RC_TAG_BEGIN.$ressourceid.self::RC_TAG_END,$lesson_answer->response);
                $DB->update_record('lesson_answers', $lesson_answer);
                $this->l('Old file reference replaced (cmid='.$cmid.'/fileid='.$fileid.'/filename='.$filename.'/ressourceid='.$ressourceid.')',__FUNCTION__);
            }
        }
        
        // Lesson attempts
        $lesson_attempts = $DB->get_records('lesson_attempts',array('lessonid'=>$lesson->id));
        foreach ($lesson_attempts AS $lesson_attempt) {
            if (strpos($lesson_attempt->useranswer, '@@PLUGINFILE@@/'.$filename)!==false) {
                $lesson_attempt->useranswer = str_replace('@@PLUGINFILE@@/'.$filename,self::RC_TAG_BEGIN.$ressourceid.self::RC_TAG_END,$lesson_attempt->useranswer);
                $DB->update_record('lesson_attempts', $lesson_attempt);
                $this->l('Old file reference replaced (cmid='.$cmid.'/fileid='.$fileid.'/filename='.$filename.'/ressourceid='.$ressourceid.')',__FUNCTION__);
            }
        }
        return false;
    }
    
    private function replaceFileByRCInMod_page($cmid,$fileid,$filename,$ressourceid) {
        global $DB;
        
        $page = $DB->get_record_sql('SELECT id, content FROM {page} WHERE id = (SELECT instance FROM {course_modules} WHERE id = ?)', array($cmid));
        
        if (strpos($page->content, '@@PLUGINFILE@@/'.$filename)!==false) {
            $page->content = str_replace('@@PLUGINFILE@@/'.$filename,self::RC_TAG_BEGIN.$ressourceid.self::RC_TAG_END,$page->content);
            $DB->update_record('page', $page);
            $this->l('Old file reference replaced (cmid='.$cmid.'/fileid='.$fileid.'/filename='.$filename.'/ressourceid='.$ressourceid.')',__FUNCTION__);
        }
        return false;
    }
    
    private function replaceFileByRCInMod_questionnaire($cmid,$fileid,$filename,$ressourceid) {
        global $DB;
        
        $questionnaire_surveys = $DB->get_records('questionnaire_survey',array('courseid'=>$this->courseid));
        foreach ($questionnaire_surveys AS $questionnaire_survey) {
            if (strpos($questionnaire_survey->info, '@@PLUGINFILE@@/'.$filename)!==false) {
                $questionnaire_survey->info = str_replace('@@PLUGINFILE@@/'.$filename,self::RC_TAG_BEGIN.$ressourceid.self::RC_TAG_END,$questionnaire_survey->info);
                $DB->update_record('questionnaire_survey', $questionnaire_survey);
                $this->l('Old file reference replaced (cmid='.$cmid.'/fileid='.$fileid.'/filename='.$filename.'/ressourceid='.$ressourceid.')',__FUNCTION__);
            }
            if (strpos($questionnaire_survey->thank_body, '@@PLUGINFILE@@/'.$filename)!==false) {
                $questionnaire_survey->thank_body = str_replace('@@PLUGINFILE@@/'.$filename,self::RC_TAG_BEGIN.$ressourceid.self::RC_TAG_END,$questionnaire_survey->thank_body);
                $DB->update_record('questionnaire_survey', $questionnaire_survey);
                $this->l('Old file reference replaced (cmid='.$cmid.'/fileid='.$fileid.'/filename='.$filename.'/ressourceid='.$ressourceid.')',__FUNCTION__);
            }
            if (strpos($questionnaire_survey->feedbacknotes, '@@PLUGINFILE@@/'.$filename)!==false) {
                $questionnaire_survey->feedbacknotes = str_replace('@@PLUGINFILE@@/'.$filename,self::RC_TAG_BEGIN.$ressourceid.self::RC_TAG_END,$questionnaire_survey->feedbacknotes);
                $DB->update_record('questionnaire_survey', $questionnaire_survey);
                $this->l('Old file reference replaced (cmid='.$cmid.'/fileid='.$fileid.'/filename='.$filename.'/ressourceid='.$ressourceid.')',__FUNCTION__);
            }
            
            // Questionnaire question
            $questionnaire_questions = $DB->get_records('questionnaire_question',array('surveyid'=>$questionnaire_survey->id));
            foreach ($questionnaire_questions AS $questionnaire_question) {
                if (strpos($questionnaire_question->content, '@@PLUGINFILE@@/'.$filename)!==false) {
                    $questionnaire_question->content = str_replace('@@PLUGINFILE@@/'.$filename,self::RC_TAG_BEGIN.$ressourceid.self::RC_TAG_END,$questionnaire_question->content);
                    $DB->update_record('questionnaire_question', $questionnaire_question);
                    $this->l('Old file reference replaced (cmid='.$cmid.'/fileid='.$fileid.'/filename='.$filename.'/ressourceid='.$ressourceid.')',__FUNCTION__);
                }
                
                // Questionnaire response text
                $questionnaire_response_texts = $DB->get_records('questionnaire_response_text',array('question_id'=>$questionnaire_question->id));
                foreach ($questionnaire_response_texts AS $questionnaire_response_text) {
                    if (strpos($questionnaire_response_text->response, '@@PLUGINFILE@@/'.$filename)!==false) {
                        $questionnaire_response_text->response = str_replace('@@PLUGINFILE@@/'.$filename,self::RC_TAG_BEGIN.$ressourceid.self::RC_TAG_END,$questionnaire_response_text->response);
                        $DB->update_record('questionnaire_response_text', $questionnaire_response_text);
                        $this->l('Old file reference replaced (cmid='.$cmid.'/fileid='.$fileid.'/filename='.$filename.'/ressourceid='.$ressourceid.')',__FUNCTION__);
                    }
                }
                
                // Questionnaire response other
                $questionnaire_response_others = $DB->get_records('questionnaire_response_other',array('question_id'=>$questionnaire_question->id));
                foreach ($questionnaire_response_others AS $questionnaire_response_other) {
                    if (strpos($questionnaire_response_other->response, '@@PLUGINFILE@@/'.$filename)!==false) {
                        $questionnaire_response_other->response = str_replace('@@PLUGINFILE@@/'.$filename,self::RC_TAG_BEGIN.$ressourceid.self::RC_TAG_END,$questionnaire_response_other->response);
                        $DB->update_record('questionnaire_response_other', $questionnaire_response_other);
                        $this->l('Old file reference replaced (cmid='.$cmid.'/fileid='.$fileid.'/filename='.$filename.'/ressourceid='.$ressourceid.')',__FUNCTION__);
                    }
                }
                
            }
            
            // Questionnaire feedback sections
            $questionnaire_fb_sections = $DB->get_records('questionnaire_fb_sections',array('surveyid'=>$questionnaire_survey->id));
            foreach ($questionnaire_fb_sections AS $questionnaire_fb_section) {
                if (strpos($questionnaire_fb_section->definition, '@@PLUGINFILE@@/'.$filename)!==false) {
                    $questionnaire_fb_section->definition = str_replace('@@PLUGINFILE@@/'.$filename,self::RC_TAG_BEGIN.$ressourceid.self::RC_TAG_END,$questionnaire_fb_section->definition);
                    $DB->update_record('questionnaire_fb_sections', $questionnaire_fb_section);
                    $this->l('Old file reference replaced (cmid='.$cmid.'/fileid='.$fileid.'/filename='.$filename.'/ressourceid='.$ressourceid.')',__FUNCTION__);
                }
                
                // Questionnaire feedback
                $questionnaire_feedbacks = $DB->get_records('questionnaire_feedback',array('sectionid'=>$questionnaire_fb_sections->id));
                foreach ($questionnaire_feedbacks AS $questionnaire_feedback) {
                    if (strpos($questionnaire_feedback->response, '@@PLUGINFILE@@/'.$filename)!==false) {
                        $questionnaire_feedback->response = str_replace('@@PLUGINFILE@@/'.$filename,self::RC_TAG_BEGIN.$ressourceid.self::RC_TAG_END,$questionnaire_feedback->response);
                        $DB->update_record('questionnaire_feedback', $questionnaire_feedback);
                        $this->l('Old file reference replaced (cmid='.$cmid.'/fileid='.$fileid.'/filename='.$filename.'/ressourceid='.$ressourceid.')',__FUNCTION__);
                    }
                }
            }
        }
        return false;
    }
    
    private function replaceFileByRCInMod_quiz($cmid,$fileid,$filename,$ressourceid) {
        global $DB;
        
        $quiz = $DB->get_record_sql('SELECT id FROM {quiz} WHERE id = (SELECT instance FROM {course_modules} WHERE id = ?)', array($cmid));
        
        // Quiz Feedback
        $quiz_feedbacks = $DB->get_records('quiz_feedback',array('quizid'=>$quiz->id));
        foreach ($quiz_feedbacks AS $quiz_feedback) {
            if (strpos($quiz_feedback->feedbacktext, '@@PLUGINFILE@@/'.$filename)!==false) {
                $quiz_feedback->feedbacktext = str_replace('@@PLUGINFILE@@/'.$filename,self::RC_TAG_BEGIN.$ressourceid.self::RC_TAG_END,$quiz_feedback->feedbacktext);
                $DB->update_record('quiz_feedback', $quiz_feedback);
                $this->l('Old file reference replaced (cmid='.$cmid.'/fileid='.$fileid.'/filename='.$filename.'/ressourceid='.$ressourceid.')',__FUNCTION__);
            }
        }
        
        // Quiz Question
        $questions = $DB->get_records_sql('SELECT * FROM {question} WHERE id IN (SELECT questionid FROM {quiz_slots} WHERE quizid = ?)',array($quiz->id));
        foreach ($questions AS $question) {
            if (strpos($question->questiontext, '@@PLUGINFILE@@/'.$filename)!==false) {
                $question->questiontext = str_replace('@@PLUGINFILE@@/'.$filename,self::RC_TAG_BEGIN.$ressourceid.self::RC_TAG_END,$question->questiontext);
                $DB->update_record('question', $question);
                $this->l('Old file reference replaced (cmid='.$cmid.'/fileid='.$fileid.'/filename='.$filename.'/ressourceid='.$ressourceid.')',__FUNCTION__);
            }
            
            if (strpos($question->generalfeedback, '@@PLUGINFILE@@/'.$filename)!==false) {
                $question->generalfeedback = str_replace('@@PLUGINFILE@@/'.$filename,self::RC_TAG_BEGIN.$ressourceid.self::RC_TAG_END,$question->generalfeedback);
                $DB->update_record('question', $question);
                $this->l('Old file reference replaced (cmid='.$cmid.'/fileid='.$fileid.'/filename='.$filename.'/ressourceid='.$ressourceid.')',__FUNCTION__);
            }
            
            switch($question->qtype) {
                case 'essay':
                    $essays = $DB->get_records('qtype_essay_options',array('questionid'=>$question->id));
                    
                    foreach ($essays AS $essay) {
                        if (strpos($essay->graderinfo, '@@PLUGINFILE@@/'.$filename)!==false) {
                            $essay->graderinfo = str_replace('@@PLUGINFILE@@/'.$filename,self::RC_TAG_BEGIN.$ressourceid.self::RC_TAG_END,$essay->graderinfo);
                            $DB->update_record('qtype_essay_options', $essay);
                            $this->l('Old file reference replaced (cmid='.$cmid.'/fileid='.$fileid.'/filename='.$filename.'/ressourceid='.$ressourceid.')',__FUNCTION__);
                        }
                        if (strpos($essay->responsetemplate, '@@PLUGINFILE@@/'.$filename)!==false) {
                            $essay->responsetemplate = str_replace('@@PLUGINFILE@@/'.$filename,self::RC_TAG_BEGIN.$ressourceid.self::RC_TAG_END,$essay->responsetemplate);
                            $DB->update_record('qtype_essay_options', $essay);
                            $this->l('Old file reference replaced (cmid='.$cmid.'/fileid='.$fileid.'/filename='.$filename.'/ressourceid='.$ressourceid.')',__FUNCTION__);
                        }
                    }
                    break;
                case 'ddimageortext':
                    $ddimageortexts = $DB->get_records('qtype_ddimageortext',array('questionid'=>$question->id));
                    foreach ($ddimageortexts AS $ddimageortext) {
                        if (strpos($ddimageortext->correctfeedback, '@@PLUGINFILE@@/'.$filename)!==false) {
                            $ddimageortext->correctfeedback = str_replace('@@PLUGINFILE@@/'.$filename,self::RC_TAG_BEGIN.$ressourceid.self::RC_TAG_END,$ddimageortext->correctfeedback);
                            $DB->update_record('qtype_ddimageortext', $ddimageortext);
                            $this->l('Old file reference replaced (cmid='.$cmid.'/fileid='.$fileid.'/filename='.$filename.'/ressourceid='.$ressourceid.')',__FUNCTION__);
                        }
                        if (strpos($ddimageortext->partiallycorrectfeedback, '@@PLUGINFILE@@/'.$filename)!==false) {
                            $ddimageortext->partiallycorrectfeedback = str_replace('@@PLUGINFILE@@/'.$filename,self::RC_TAG_BEGIN.$ressourceid.self::RC_TAG_END,$ddimageortext->partiallycorrectfeedback);
                            $DB->update_record('qtype_ddimageortext', $ddimageortext);
                            $this->l('Old file reference replaced (cmid='.$cmid.'/fileid='.$fileid.'/filename='.$filename.'/ressourceid='.$ressourceid.')',__FUNCTION__);
                        }
                        if (strpos($ddimageortext->incorrectfeedback, '@@PLUGINFILE@@/'.$filename)!==false) {
                            $ddimageortext->incorrectfeedback = str_replace('@@PLUGINFILE@@/'.$filename,self::RC_TAG_BEGIN.$ressourceid.self::RC_TAG_END,$ddimageortext->incorrectfeedback);
                            $DB->update_record('qtype_ddimageortext', $ddimageortext);
                            $this->l('Old file reference replaced (cmid='.$cmid.'/fileid='.$fileid.'/filename='.$filename.'/ressourceid='.$ressourceid.')',__FUNCTION__);
                        }
                    }
                    // Drag? Drop?
                    break;
                case 'ddmarker':
                    $ddmarkers = $DB->get_records('qtype_ddmarker',array('questionid'=>$question->id));
                    foreach ($ddmarkers AS $ddmarker) {
                        if (strpos($ddmarker->correctfeedback, '@@PLUGINFILE@@/'.$filename)!==false) {
                            $ddmarker->correctfeedback = str_replace('@@PLUGINFILE@@/'.$filename,self::RC_TAG_BEGIN.$ressourceid.self::RC_TAG_END,$ddmarker->correctfeedback);
                            $DB->update_record('qtype_ddmarker', $ddmarker);
                            $this->l('Old file reference replaced (cmid='.$cmid.'/fileid='.$fileid.'/filename='.$filename.'/ressourceid='.$ressourceid.')',__FUNCTION__);
                        }
                        if (strpos($ddmarker->partiallycorrectfeedback, '@@PLUGINFILE@@/'.$filename)!==false) {
                            $ddmarker->partiallycorrectfeedback = str_replace('@@PLUGINFILE@@/'.$filename,self::RC_TAG_BEGIN.$ressourceid.self::RC_TAG_END,$ddmarker->partiallycorrectfeedback);
                            $DB->update_record('qtype_ddmarker', $ddmarker);
                            $this->l('Old file reference replaced (cmid='.$cmid.'/fileid='.$fileid.'/filename='.$filename.'/ressourceid='.$ressourceid.')',__FUNCTION__);
                        }
                        if (strpos($ddmarker->incorrectfeedback, '@@PLUGINFILE@@/'.$filename)!==false) {
                            $ddmarker->incorrectfeedback = str_replace('@@PLUGINFILE@@/'.$filename,self::RC_TAG_BEGIN.$ressourceid.self::RC_TAG_END,$ddmarker->incorrectfeedback);
                            $DB->update_record('qtype_ddmarker', $ddmarker);
                            $this->l('Old file reference replaced (cmid='.$cmid.'/fileid='.$fileid.'/filename='.$filename.'/ressourceid='.$ressourceid.')',__FUNCTION__);
                        }
                    }
                    // Drag? Drop?
                    break;
                case 'match':
                    $matchs = $DB->get_records('qtype_match_options',array('questionid'=>$question->id));
                    foreach ($matchs AS $match) {
                        if (strpos($match->correctfeedback, '@@PLUGINFILE@@/'.$filename)!==false) {
                            $match->correctfeedback = str_replace('@@PLUGINFILE@@/'.$filename,self::RC_TAG_BEGIN.$ressourceid.self::RC_TAG_END,$match->correctfeedback);
                            $DB->update_record('qtype_match_options', $match);
                            $this->l('Old file reference replaced (cmid='.$cmid.'/fileid='.$fileid.'/filename='.$filename.'/ressourceid='.$ressourceid.')',__FUNCTION__);
                        }
                        if (strpos($match->partiallycorrectfeedback, '@@PLUGINFILE@@/'.$filename)!==false) {
                            $match->partiallycorrectfeedback = str_replace('@@PLUGINFILE@@/'.$filename,self::RC_TAG_BEGIN.$ressourceid.self::RC_TAG_END,$match->partiallycorrectfeedback);
                            $DB->update_record('qtype_match_options', $match);
                            $this->l('Old file reference replaced (cmid='.$cmid.'/fileid='.$fileid.'/filename='.$filename.'/ressourceid='.$ressourceid.')',__FUNCTION__);
                        }
                        if (strpos($match->incorrectfeedback, '@@PLUGINFILE@@/'.$filename)!==false) {
                            $match->incorrectfeedback = str_replace('@@PLUGINFILE@@/'.$filename,self::RC_TAG_BEGIN.$ressourceid.self::RC_TAG_END,$match->incorrectfeedback);
                            $DB->update_record('qtype_match_options', $match);
                            $this->l('Old file reference replaced (cmid='.$cmid.'/fileid='.$fileid.'/filename='.$filename.'/ressourceid='.$ressourceid.')',__FUNCTION__);
                        }
                    }
                    $match_subquestions = $DB->get_records('qtype_match_subquestions',array('questionid'=>$question->id));
                    foreach ($match_subquestions AS $match_subquestion) {
                        if (strpos($match_subquestion->questiontext, '@@PLUGINFILE@@/'.$filename)!==false) {
                            $match_subquestion->questiontext = str_replace('@@PLUGINFILE@@/'.$filename,self::RC_TAG_BEGIN.$ressourceid.self::RC_TAG_END,$match_subquestion->questiontext);
                            $DB->update_record('qtype_match_subquestions', $match_subquestion);
                            $this->l('Old file reference replaced (cmid='.$cmid.'/fileid='.$fileid.'/filename='.$filename.'/ressourceid='.$ressourceid.')',__FUNCTION__);
                        }
                    }
                    break;
                case 'multichoice':
                    $multichoices = $DB->get_records('qtype_multichoice_options',array('questionid'=>$question->id));
                    foreach ($multichoices AS $multichoice) {
                        if (strpos($multichoice->correctfeedback, '@@PLUGINFILE@@/'.$filename)!==false) {
                            $multichoice->correctfeedback = str_replace('@@PLUGINFILE@@/'.$filename,self::RC_TAG_BEGIN.$ressourceid.self::RC_TAG_END,$multichoice->correctfeedback);
                            $DB->update_record('qtype_multichoice_options', $multichoice);
                            $this->l('Old file reference replaced (cmid='.$cmid.'/fileid='.$fileid.'/filename='.$filename.'/ressourceid='.$ressourceid.')',__FUNCTION__);
                        }
                        if (strpos($multichoice->partiallycorrectfeedback, '@@PLUGINFILE@@/'.$filename)!==false) {
                            $multichoice->partiallycorrectfeedback = str_replace('@@PLUGINFILE@@/'.$filename,self::RC_TAG_BEGIN.$ressourceid.self::RC_TAG_END,$multichoice->partiallycorrectfeedback);
                            $DB->update_record('qtype_multichoice_options', $multichoice);
                            $this->l('Old file reference replaced (cmid='.$cmid.'/fileid='.$fileid.'/filename='.$filename.'/ressourceid='.$ressourceid.')',__FUNCTION__);
                        }
                        if (strpos($multichoice->incorrectfeedback, '@@PLUGINFILE@@/'.$filename)!==false) {
                            $multichoice->incorrectfeedback = str_replace('@@PLUGINFILE@@/'.$filename,self::RC_TAG_BEGIN.$ressourceid.self::RC_TAG_END,$multichoice->incorrectfeedback);
                            $DB->update_record('qtype_multichoice_options', $multichoice);
                            $this->l('Old file reference replaced (cmid='.$cmid.'/fileid='.$fileid.'/filename='.$filename.'/ressourceid='.$ressourceid.')',__FUNCTION__);
                        }
                    }
                    break;
                case 'poodllrecording':
                    $poodllrecordings = $DB->get_records('qtype_poodllrecording_opts',array('questionid'=>$question->id));
                    foreach ($poodllrecordings AS $poodllrecording) {
                        if (strpos($poodllrecording->graderinfo, '@@PLUGINFILE@@/'.$filename)!==false) {
                            $poodllrecording->graderinfo = str_replace('@@PLUGINFILE@@/'.$filename,self::RC_TAG_BEGIN.$ressourceid.self::RC_TAG_END,$poodllrecording->graderinfo);
                            $DB->update_record('qtype_poodllrecording_opts', $poodllrecording);
                            $this->l('Old file reference replaced (cmid='.$cmid.'/fileid='.$fileid.'/filename='.$filename.'/ressourceid='.$ressourceid.')',__FUNCTION__);
                        }
                        if (strpos($poodllrecording->backimage, '@@PLUGINFILE@@/'.$filename)!==false) {
                            $poodllrecording->backimage = str_replace('@@PLUGINFILE@@/'.$filename,self::RC_TAG_BEGIN.$ressourceid.self::RC_TAG_END,$poodllrecording->backimage);
                            $DB->update_record('qtype_poodllrecording_opts', $poodllrecording);
                            $this->l('Old file reference replaced (cmid='.$cmid.'/fileid='.$fileid.'/filename='.$filename.'/ressourceid='.$ressourceid.')',__FUNCTION__);
                        }
                        if (strpos($poodllrecording->boardsize, '@@PLUGINFILE@@/'.$filename)!==false) {
                            $poodllrecording->boardsize = str_replace('@@PLUGINFILE@@/'.$filename,self::RC_TAG_BEGIN.$ressourceid.self::RC_TAG_END,$poodllrecording->boardsize);
                            $DB->update_record('qtype_poodllrecording_opts', $poodllrecording);
                            $this->l('Old file reference replaced (cmid='.$cmid.'/fileid='.$fileid.'/filename='.$filename.'/ressourceid='.$ressourceid.')',__FUNCTION__);
                        }
                    }
                    break;
                case 'randomsamatch':
                    $randomsamatchs = $DB->get_records('qtype_randomsamatch_options',array('questionid'=>$question->id));
                    foreach ($randomsamatchs AS $randomsamatch) {
                        if (strpos($randomsamatch->correctfeedback, '@@PLUGINFILE@@/'.$filename)!==false) {
                            $randomsamatch->correctfeedback = str_replace('@@PLUGINFILE@@/'.$filename,self::RC_TAG_BEGIN.$ressourceid.self::RC_TAG_END,$randomsamatch->correctfeedback);
                            $DB->update_record('qtype_randomsamatch_options', $randomsamatch);
                            $this->l('Old file reference replaced (cmid='.$cmid.'/fileid='.$fileid.'/filename='.$filename.'/ressourceid='.$ressourceid.')',__FUNCTION__);
                        }
                        if (strpos($randomsamatch->partiallycorrectfeedback, '@@PLUGINFILE@@/'.$filename)!==false) {
                            $randomsamatch->partiallycorrectfeedback = str_replace('@@PLUGINFILE@@/'.$filename,self::RC_TAG_BEGIN.$ressourceid.self::RC_TAG_END,$randomsamatch->partiallycorrectfeedback);
                            $DB->update_record('qtype_randomsamatch_options', $randomsamatch);
                            $this->l('Old file reference replaced (cmid='.$cmid.'/fileid='.$fileid.'/filename='.$filename.'/ressourceid='.$ressourceid.')',__FUNCTION__);
                        }
                        if (strpos($randomsamatch->incorrectfeedback, '@@PLUGINFILE@@/'.$filename)!==false) {
                            $randomsamatch->incorrectfeedback = str_replace('@@PLUGINFILE@@/'.$filename,self::RC_TAG_BEGIN.$ressourceid.self::RC_TAG_END,$randomsamatch->incorrectfeedback);
                            $DB->update_record('qtype_randomsamatch_options', $randomsamatch);
                            $this->l('Old file reference replaced (cmid='.$cmid.'/fileid='.$fileid.'/filename='.$filename.'/ressourceid='.$ressourceid.')',__FUNCTION__);
                        }
                    }
                    break;
                case 'calculated':
                    $calculateds = $DB->get_records('question_calculated_options',array('question'=>$question->id));
                    foreach ($calculateds AS $calculated) {
                        if (strpos($calculated->correctfeedback, '@@PLUGINFILE@@/'.$filename)!==false) {
                            $calculated->correctfeedback = str_replace('@@PLUGINFILE@@/'.$filename,self::RC_TAG_BEGIN.$ressourceid.self::RC_TAG_END,$calculated->correctfeedback);
                            $DB->update_record('question_calculated_options', $calculated);
                            $this->l('Old file reference replaced (cmid='.$cmid.'/fileid='.$fileid.'/filename='.$filename.'/ressourceid='.$ressourceid.')',__FUNCTION__);
                        }
                        if (strpos($calculated->partiallycorrectfeedback, '@@PLUGINFILE@@/'.$filename)!==false) {
                            $calculated->partiallycorrectfeedback = str_replace('@@PLUGINFILE@@/'.$filename,self::RC_TAG_BEGIN.$ressourceid.self::RC_TAG_END,$calculated->partiallycorrectfeedback);
                            $DB->update_record('question_calculated_options', $calculated);
                            $this->l('Old file reference replaced (cmid='.$cmid.'/fileid='.$fileid.'/filename='.$filename.'/ressourceid='.$ressourceid.')',__FUNCTION__);
                        }
                        if (strpos($calculated->incorrectfeedback, '@@PLUGINFILE@@/'.$filename)!==false) {
                            $calculated->incorrectfeedback = str_replace('@@PLUGINFILE@@/'.$filename,self::RC_TAG_BEGIN.$ressourceid.self::RC_TAG_END,$calculated->incorrectfeedback);
                            $DB->update_record('question_calculated_options', $calculated);
                            $this->l('Old file reference replaced (cmid='.$cmid.'/fileid='.$fileid.'/filename='.$filename.'/ressourceid='.$ressourceid.')',__FUNCTION__);
                        }
                    }
                    break;
                case 'ddwtos':
                    $ddwtos = $DB->get_records('question_ddwtos',array('question'=>$question->id));
                    foreach ($ddwtos AS $ddwto) {
                        if (strpos($ddwto->correctfeedback, '@@PLUGINFILE@@/'.$filename)!==false) {
                            $ddwto->correctfeedback = str_replace('@@PLUGINFILE@@/'.$filename,self::RC_TAG_BEGIN.$ressourceid.self::RC_TAG_END,$ddwto->correctfeedback);
                            $DB->update_record('question_ddwtos', $ddwto);
                            $this->l('Old file reference replaced (cmid='.$cmid.'/fileid='.$fileid.'/filename='.$filename.'/ressourceid='.$ressourceid.')',__FUNCTION__);
                        }
                        if (strpos($ddwto->partiallycorrectfeedback, '@@PLUGINFILE@@/'.$filename)!==false) {
                            $ddwto->partiallycorrectfeedback = str_replace('@@PLUGINFILE@@/'.$filename,self::RC_TAG_BEGIN.$ressourceid.self::RC_TAG_END,$ddwto->partiallycorrectfeedback);
                            $DB->update_record('question_ddwtos', $ddwto);
                            $this->l('Old file reference replaced (cmid='.$cmid.'/fileid='.$fileid.'/filename='.$filename.'/ressourceid='.$ressourceid.')',__FUNCTION__);
                        }
                        if (strpos($ddwto->incorrectfeedback, '@@PLUGINFILE@@/'.$filename)!==false) {
                            $ddwto->incorrectfeedback = str_replace('@@PLUGINFILE@@/'.$filename,self::RC_TAG_BEGIN.$ressourceid.self::RC_TAG_END,$ddwto->incorrectfeedback);
                            $DB->update_record('question_ddwtos', $ddwto);
                            $this->l('Old file reference replaced (cmid='.$cmid.'/fileid='.$fileid.'/filename='.$filename.'/ressourceid='.$ressourceid.')',__FUNCTION__);
                        }
                    }
                    break;
                case 'gapselect':
                    $gapselects = $DB->get_records('question_gapselect',array('question'=>$question->id));
                    foreach ($gapselects AS $gapselect) {
                        if (strpos($gapselect->correctfeedback, '@@PLUGINFILE@@/'.$filename)!==false) {
                            $gapselect->correctfeedback = str_replace('@@PLUGINFILE@@/'.$filename,self::RC_TAG_BEGIN.$ressourceid.self::RC_TAG_END,$gapselect->correctfeedback);
                            $DB->update_record('question_gapselect', $gapselect);
                            $this->l('Old file reference replaced (cmid='.$cmid.'/fileid='.$fileid.'/filename='.$filename.'/ressourceid='.$ressourceid.')',__FUNCTION__);
                        }
                        if (strpos($gapselect->partiallycorrectfeedback, '@@PLUGINFILE@@/'.$filename)!==false) {
                            $gapselect->partiallycorrectfeedback = str_replace('@@PLUGINFILE@@/'.$filename,self::RC_TAG_BEGIN.$ressourceid.self::RC_TAG_END,$gapselect->partiallycorrectfeedback);
                            $DB->update_record('question_gapselect', $gapselect);
                            $this->l('Old file reference replaced (cmid='.$cmid.'/fileid='.$fileid.'/filename='.$filename.'/ressourceid='.$ressourceid.')',__FUNCTION__);
                        }
                        if (strpos($gapselect->incorrectfeedback, '@@PLUGINFILE@@/'.$filename)!==false) {
                            $gapselect->incorrectfeedback = str_replace('@@PLUGINFILE@@/'.$filename,self::RC_TAG_BEGIN.$ressourceid.self::RC_TAG_END,$gapselect->incorrectfeedback);
                            $DB->update_record('question_gapselect', $gapselect);
                            $this->l('Old file reference replaced (cmid='.$cmid.'/fileid='.$fileid.'/filename='.$filename.'/ressourceid='.$ressourceid.')',__FUNCTION__);
                        }
                    }
                    break;
            }
            
            // Quiz question answers
            $answers = $DB->get_records('question_answers',array('question'=>$question->id));
            foreach ($answers AS $answer) {
                if (strpos($answer->answer, '@@PLUGINFILE@@/'.$filename)!==false) {
                    $answer->answer = str_replace('@@PLUGINFILE@@/'.$filename,self::RC_TAG_BEGIN.$ressourceid.self::RC_TAG_END,$answer->answer);
                    $DB->update_record('question_answers', $answer);
                    $this->l('Old file reference replaced (cmid='.$cmid.'/fileid='.$fileid.'/filename='.$filename.'/ressourceid='.$ressourceid.')',__FUNCTION__);
                }
                
                if (strpos($answer->feedback, '@@PLUGINFILE@@/'.$filename)!==false) {
                    $answer->feedback = str_replace('@@PLUGINFILE@@/'.$filename,self::RC_TAG_BEGIN.$ressourceid.self::RC_TAG_END,$answer->feedback);
                    $DB->update_record('question_answers', $answer);
                    $this->l('Old file reference replaced (cmid='.$cmid.'/fileid='.$fileid.'/filename='.$filename.'/ressourceid='.$ressourceid.')',__FUNCTION__);
                }
            }
            
            // Quiz question attemps
            $attempts = $DB->get_records('question_attempts',array('questionid'=>$question->id));
            foreach ($attempts AS $attempt) {
                if (strpos($attempt->questionsummary, '@@PLUGINFILE@@/'.$filename)!==false) {
                    $attempt->questionsummary = str_replace('@@PLUGINFILE@@/'.$filename,self::RC_TAG_BEGIN.$ressourceid.self::RC_TAG_END,$attempt->questionsummary);
                    $DB->update_record('question_attempts', $attempt);
                    $this->l('Old file reference replaced (cmid='.$cmid.'/fileid='.$fileid.'/filename='.$filename.'/ressourceid='.$ressourceid.')',__FUNCTION__);
                }
                if (strpos($attempt->rightanswer, '@@PLUGINFILE@@/'.$filename)!==false) {
                    $attempt->rightanswer = str_replace('@@PLUGINFILE@@/'.$filename,self::RC_TAG_BEGIN.$ressourceid.self::RC_TAG_END,$attempt->rightanswer);
                    $DB->update_record('question_attempts', $attempt);
                    $this->l('Old file reference replaced (cmid='.$cmid.'/fileid='.$fileid.'/filename='.$filename.'/ressourceid='.$ressourceid.')',__FUNCTION__);
                }
                if (strpos($attempt->responsesummary, '@@PLUGINFILE@@/'.$filename)!==false) {
                    $attempt->responsesummary = str_replace('@@PLUGINFILE@@/'.$filename,self::RC_TAG_BEGIN.$ressourceid.self::RC_TAG_END,$attempt->responsesummary);
                    $DB->update_record('question_attempts', $attempt);
                    $this->l('Old file reference replaced (cmid='.$cmid.'/fileid='.$fileid.'/filename='.$filename.'/ressourceid='.$ressourceid.')',__FUNCTION__);
                }
            }
            
            // Quiz question hints
            $answers = $DB->get_records('question_hints',array('questionid'=>$question->id));
            foreach ($answers AS $answer) {
                if (strpos($answer->hint, '@@PLUGINFILE@@/'.$filename)!==false) {
                    $answer->hint = str_replace('@@PLUGINFILE@@/'.$filename,self::RC_TAG_BEGIN.$ressourceid.self::RC_TAG_END,$answer->hint);
                    $DB->update_record('question_hints', $answer);
                    $this->l('Old file reference replaced (cmid='.$cmid.'/fileid='.$fileid.'/filename='.$filename.'/ressourceid='.$ressourceid.')',__FUNCTION__);
                }
            }
            
            // Quiz question response analysis
            $analysis = $DB->get_records('question_response_analysis',array('questionid'=>$question->id));
            foreach ($analysis AS $analysi) {
                if (strpos($analysi->response, '@@PLUGINFILE@@/'.$filename)!==false) {
                    $analysi->response = str_replace('@@PLUGINFILE@@/'.$filename,self::RC_TAG_BEGIN.$ressourceid.self::RC_TAG_END,$analysi->response);
                    $DB->update_record('question_response_analysis', $analysi);
                    $this->l('Old file reference replaced (cmid='.$cmid.'/fileid='.$fileid.'/filename='.$filename.'/ressourceid='.$ressourceid.')',__FUNCTION__);
                }
            }
            
            // Quiz question categories
            $categories = $DB->get_records('question_categories',array('questionid'=>$question->id));
            foreach ($categories AS $categorie) {
                if (strpos($categorie->info, '@@PLUGINFILE@@/'.$filename)!==false) {
                    $categorie->info = str_replace('@@PLUGINFILE@@/'.$filename,self::RC_TAG_BEGIN.$ressourceid.self::RC_TAG_END,$categorie->info);
                    $DB->update_record('question_categories', $categorie);
                    $this->l('Old file reference replaced (cmid='.$cmid.'/fileid='.$fileid.'/filename='.$filename.'/ressourceid='.$ressourceid.')',__FUNCTION__);
                }
            }
        }
        return false;
    }
    
    private function replaceFileByRCInMod_tracker($cmid,$fileid,$filename,$ressourceid) {
        global $DB;
        
        $glossary = $DB->get_record_sql('SELECT id FROM {tracker} WHERE id = (SELECT instance FROM {course_modules} WHERE id = ?)', array($cmid));
        
        // Tracker issues
        $tracker_issues = $DB->get_records('tracker_issue',array('trackerid'=>$glossary->id));
        foreach ($tracker_issues AS $tracker_issue) {
            if (strpos($tracker_issue->description, '@@PLUGINFILE@@/'.$filename)!==false) {
                $tracker_issue->description = str_replace('@@PLUGINFILE@@/'.$filename,self::RC_TAG_BEGIN.$ressourceid.self::RC_TAG_END,$tracker_issue->description);
                $DB->update_record('tracker_issue', $tracker_issue);
                $this->l('Old file reference replaced (cmid='.$cmid.'/fileid='.$fileid.'/filename='.$filename.'/ressourceid='.$ressourceid.')',__FUNCTION__);
            }
            
            if (strpos($tracker_issue->resolution, '@@PLUGINFILE@@/'.$filename)!==false) {
                $tracker_issue->resolution = str_replace('@@PLUGINFILE@@/'.$filename,self::RC_TAG_BEGIN.$ressourceid.self::RC_TAG_END,$tracker_issue->resolution);
                $DB->update_record('tracker_issue', $tracker_issue);
                $this->l('Old file reference replaced (cmid='.$cmid.'/fileid='.$fileid.'/filename='.$filename.'/ressourceid='.$ressourceid.')',__FUNCTION__);
            }
        }
        
        // Tracker issues comment
        $tracker_issuecomments = $DB->get_records('tracker_issuecomment',array('trackerid'=>$glossary->id));
        foreach ($tracker_issuecomments AS $tracker_issuecomment) {
            if (strpos($tracker_issuecomment->comment, '@@PLUGINFILE@@/'.$filename)!==false) {
                $tracker_issuecomment->comment = str_replace('@@PLUGINFILE@@/'.$filename,self::RC_TAG_BEGIN.$ressourceid.self::RC_TAG_END,$tracker_issuecomment->comment);
                $DB->update_record('tracker_issuecomment', $tracker_issuecomment);
                $this->l('Old file reference replaced (cmid='.$cmid.'/fileid='.$fileid.'/filename='.$filename.'/ressourceid='.$ressourceid.')',__FUNCTION__);
            }
        }
        
        // Tracker issues dependancy
        $tracker_issuedependancys = $DB->get_records('tracker_issuedependancy',array('trackerid'=>$glossary->id));
        foreach ($tracker_issuedependancys AS $tracker_issuedependancy) {
            if (strpos($tracker_issuedependancy->comment, '@@PLUGINFILE@@/'.$filename)!==false) {
                $tracker_issuedependancy->comment = str_replace('@@PLUGINFILE@@/'.$filename,self::RC_TAG_BEGIN.$ressourceid.self::RC_TAG_END,$tracker_issuedependancy->comment);
                $DB->update_record('tracker_issuedependancy', $tracker_issuedependancy);
                $this->l('Old file reference replaced (cmid='.$cmid.'/fileid='.$fileid.'/filename='.$filename.'/ressourceid='.$ressourceid.')',__FUNCTION__);
            }
        }
        return false;
    }
    
    private function replaceFileByRCInMod_viaassign($cmid,$fileid,$filename,$ressourceid) {
        global $DB;
        
        $viaassign = $DB->get_record_sql('SELECT id FROM {viaassign} WHERE id = (SELECT instance FROM {course_modules} WHERE id = ?)', array($cmid));
        
        // Viaassign Feedback comments
        $viaassignfeedback_comments = $DB->get_records('viaassignfeedback_comments',array('viaassign'=>$viaassign->id));
        foreach ($viaassignfeedback_comments AS $viaassignfeedback_comment) {
            if (strpos($viaassignfeedback_comment->commenttext, '@@PLUGINFILE@@/'.$filename)!==false) {
                $viaassignfeedback_comment->commenttext = str_replace('@@PLUGINFILE@@/'.$filename,self::RC_TAG_BEGIN.$ressourceid.self::RC_TAG_END,$viaassignfeedback_comment->commenttext);
                $DB->update_record('viaassignfeedback_comments', $viaassignfeedback_comment);
                $this->l('Old file reference replaced (cmid='.$cmid.'/fileid='.$fileid.'/filename='.$filename.'/ressourceid='.$ressourceid.')',__FUNCTION__);
            }
        }
        return false;
    }
    
    private function replaceFileByRCInMod_wiki($cmid,$fileid,$filename,$ressourceid) {
        global $DB;
        
        $wiki = $DB->get_record_sql('SELECT id FROM {wiki} WHERE id = (SELECT instance FROM {course_modules} WHERE id = ?)', array($cmid));
        
        // Wiki page and version
        $wiki_subwikis = $DB->get_records('wiki_subwikis',array('wikiid'=>$wiki->id));
        foreach ($wiki_subwikis AS $wiki_subwiki) {
            $wiki_pages = $DB->get_records('wiki_pages',array('subwikiid'=>$wiki_subwiki->id));
            foreach ($wiki_pages AS $wiki_page) {
                if (strpos($wiki_page->cachedcontent, '@@PLUGINFILE@@/'.$filename)!==false) {
                    $wiki_page->cachedcontent = str_replace('@@PLUGINFILE@@/'.$filename,self::RC_TAG_BEGIN.$ressourceid.self::RC_TAG_END,$wiki_page->cachedcontent);
                    $DB->update_record('wiki_pages', $wiki_page);
                    $this->l('Old file reference replaced (cmid='.$cmid.'/fileid='.$fileid.'/filename='.$filename.'/ressourceid='.$ressourceid.')',__FUNCTION__);
                }
                
                $wiki_versions = $DB->get_records('wiki_versions',array('pageid'=>$wiki->id));
                foreach ($wiki_versions AS $wiki_version) {
                    if (strpos($wiki_version->content, '@@PLUGINFILE@@/'.$filename)!==false) {
                        $wiki_version->content = str_replace('@@PLUGINFILE@@/'.$filename,self::RC_TAG_BEGIN.$ressourceid.self::RC_TAG_END,$wiki_version->content);
                        $DB->update_record('wiki_versions', $wiki_version);
                        $this->l('Old file reference replaced (cmid='.$cmid.'/fileid='.$fileid.'/filename='.$filename.'/ressourceid='.$ressourceid.')',__FUNCTION__);
                    }
                }
                
            }
        }
        return false;
    }
    
    private function replaceFileByRCInMod_workshop($cmid,$fileid,$filename,$ressourceid) {
        global $DB;
        
        $workshop = $DB->get_record_sql('SELECT * FROM {workshop} WHERE id = (SELECT instance FROM {course_modules} WHERE id = ?)', array($cmid));
        
        if (strpos($workshop->instructauthors, '@@PLUGINFILE@@/'.$filename)!==false) {
            $workshop->instructauthors = str_replace('@@PLUGINFILE@@/'.$filename,self::RC_TAG_BEGIN.$ressourceid.self::RC_TAG_END,$workshop->instructauthors);
            $DB->update_record('workshop', $workshop);
            $this->l('Old file reference replaced (cmid='.$cmid.'/fileid='.$fileid.'/filename='.$filename.'/ressourceid='.$ressourceid.')',__FUNCTION__);
        }
        if (strpos($workshop->instructreviewers, '@@PLUGINFILE@@/'.$filename)!==false) {
            $workshop->instructreviewers = str_replace('@@PLUGINFILE@@/'.$filename,self::RC_TAG_BEGIN.$ressourceid.self::RC_TAG_END,$workshop->instructreviewers);
            $DB->update_record('workshop', $workshop);
            $this->l('Old file reference replaced (cmid='.$cmid.'/fileid='.$fileid.'/filename='.$filename.'/ressourceid='.$ressourceid.')',__FUNCTION__);
        }
        
        if (strpos($workshop->conclusion, '@@PLUGINFILE@@/'.$filename)!==false) {
            $workshop->conclusion = str_replace('@@PLUGINFILE@@/'.$filename,self::RC_TAG_BEGIN.$ressourceid.self::RC_TAG_END,$workshop->conclusion);
            $DB->update_record('workshop', $workshop);
            $this->l('Old file reference replaced (cmid='.$cmid.'/fileid='.$fileid.'/filename='.$filename.'/ressourceid='.$ressourceid.')',__FUNCTION__);
        }
        
        // Workshop form accumulative
        $workshopform_accumulatives = $DB->get_records('workshopform_accumulative',array('workshopid'=>$workshop->id));
        foreach ($workshopform_accumulatives AS $workshopform_accumulative) {
            if (strpos($workshopform_accumulative->description, '@@PLUGINFILE@@/'.$filename)!==false) {
                $workshopform_accumulative->description = str_replace('@@PLUGINFILE@@/'.$filename,self::RC_TAG_BEGIN.$ressourceid.self::RC_TAG_END,$workshopform_accumulative->description);
                $DB->update_record('workshopform_accumulative', $workshopform_accumulative);
                $this->l('Old file reference replaced (cmid='.$cmid.'/fileid='.$fileid.'/filename='.$filename.'/ressourceid='.$ressourceid.')',__FUNCTION__);
            }
        }
        
        // Workshop form comments
        $workshopform_comments = $DB->get_records('workshopform_comments',array('workshopid'=>$workshop->id));
        foreach ($workshopform_comments AS $workshopform_comment) {
            if (strpos($workshopform_comment->description, '@@PLUGINFILE@@/'.$filename)!==false) {
                $workshopform_comment->description = str_replace('@@PLUGINFILE@@/'.$filename,self::RC_TAG_BEGIN.$ressourceid.self::RC_TAG_END,$workshopform_comment->description);
                $DB->update_record('workshopform_comments', $workshopform_comment);
                $this->l('Old file reference replaced (cmid='.$cmid.'/fileid='.$fileid.'/filename='.$filename.'/ressourceid='.$ressourceid.')',__FUNCTION__);
            }
        }
        
        // Workshop form numerrors
        $workshopform_numerrors = $DB->get_records('workshopform_numerrors',array('workshopid'=>$workshop->id));
        foreach ($workshopform_numerrors AS $workshopform_numerror) {
            if (strpos($workshopform_numerror->description, '@@PLUGINFILE@@/'.$filename)!==false) {
                $workshopform_numerror->description = str_replace('@@PLUGINFILE@@/'.$filename,self::RC_TAG_BEGIN.$ressourceid.self::RC_TAG_END,$workshopform_numerror->description);
                $DB->update_record('workshopform_numerrors', $workshopform_numerror);
                $this->l('Old file reference replaced (cmid='.$cmid.'/fileid='.$fileid.'/filename='.$filename.'/ressourceid='.$ressourceid.')',__FUNCTION__);
            }
        }
        
        // Workshop form rubric
        $workshopform_rubrics = $DB->get_records('workshopform_rubric',array('workshopid'=>$workshop->id));
        foreach ($workshopform_rubrics AS $workshopform_rubric) {
            if (strpos($workshopform_rubric->description, '@@PLUGINFILE@@/'.$filename)!==false) {
                $workshopform_rubric->description = str_replace('@@PLUGINFILE@@/'.$filename,self::RC_TAG_BEGIN.$ressourceid.self::RC_TAG_END,$workshopform_rubric->description);
                $DB->update_record('workshopform_rubric', $workshopform_rubric);
                $this->l('Old file reference replaced (cmid='.$cmid.'/fileid='.$fileid.'/filename='.$filename.'/ressourceid='.$ressourceid.')',__FUNCTION__);
            }
        }
        
        // Workshop submissions
        $workshop_submissions = $DB->get_records('workshop_submissions',array('workshopid'=>$workshop->id));
        foreach ($workshop_submissions AS $workshop_submission) {
            if (strpos($workshop_submission->content, '@@PLUGINFILE@@/'.$filename)!==false) {
                $workshop_submission->content = str_replace('@@PLUGINFILE@@/'.$filename,self::RC_TAG_BEGIN.$ressourceid.self::RC_TAG_END,$workshop_submission->content);
                $DB->update_record('workshop_submissions', $workshop_submission);
                $this->l('Old file reference replaced (cmid='.$cmid.'/fileid='.$fileid.'/filename='.$filename.'/ressourceid='.$ressourceid.')',__FUNCTION__);
            }
            if (strpos($workshop_submission->feedbackauthor, '@@PLUGINFILE@@/'.$filename)!==false) {
                $workshop_submission->feedbackauthor = str_replace('@@PLUGINFILE@@/'.$filename,self::RC_TAG_BEGIN.$ressourceid.self::RC_TAG_END,$workshop_submission->feedbackauthor);
                $DB->update_record('workshop_submissions', $workshop_submission);
                $this->l('Old file reference replaced (cmid='.$cmid.'/fileid='.$fileid.'/filename='.$filename.'/ressourceid='.$ressourceid.')',__FUNCTION__);
            }
            
            // Workshop assessments
            $workshop_assessments = $DB->get_records('workshop_assessments',array('submissionid'=>$workshop_submission->id));
            foreach ($workshop_assessments AS $workshop_assessment) {
                if (strpos($workshop_assessment->feedbackauthor, '@@PLUGINFILE@@/'.$filename)!==false) {
                    $workshop_assessment->feedbackauthor = str_replace('@@PLUGINFILE@@/'.$filename,self::RC_TAG_BEGIN.$ressourceid.self::RC_TAG_END,$workshop_assessment->feedbackauthor);
                    $DB->update_record('workshop_assessments', $workshop_assessment);
                    $this->l('Old file reference replaced (cmid='.$cmid.'/fileid='.$fileid.'/filename='.$filename.'/ressourceid='.$ressourceid.')',__FUNCTION__);
                }
                if (strpos($workshop_assessment->feedbackreviewer, '@@PLUGINFILE@@/'.$filename)!==false) {
                    $workshop_assessment->feedbackreviewer = str_replace('@@PLUGINFILE@@/'.$filename,self::RC_TAG_BEGIN.$ressourceid.self::RC_TAG_END,$workshop_assessment->feedbackreviewer);
                    $DB->update_record('workshop_assessments', $workshop_assessment);
                    $this->l('Old file reference replaced (cmid='.$cmid.'/fileid='.$fileid.'/filename='.$filename.'/ressourceid='.$ressourceid.')',__FUNCTION__);
                }
                
                // Workshop grades
                $workshop_grades = $DB->get_records('workshop_grades',array('assessmentid'=>$workshop_assessment->id));
                foreach ($workshop_grades AS $workshop_grade) {
                    if (strpos($workshop_grade->peercomment, '@@PLUGINFILE@@/'.$filename)!==false) {
                        $workshop_grade->peercomment = str_replace('@@PLUGINFILE@@/'.$filename,self::RC_TAG_BEGIN.$ressourceid.self::RC_TAG_END,$workshop_grade->peercomment);
                        $DB->update_record('workshop_grades', $workshop_grade);
                        $this->l('Old file reference replaced (cmid='.$cmid.'/fileid='.$fileid.'/filename='.$filename.'/ressourceid='.$ressourceid.')',__FUNCTION__);
                    }
                }
            }
        }
        
        return false;
    }
    
    
    
    
    
	
	
}

/*
echo '<pre>';

//$optimiser = new CourseFilesOptimizer(1022);
//$optimiser = new CourseFilesOptimizer(1636);
//$optimiser = new CourseFilesOptimizer(1334);
$optimiser = new CourseFilesOptimizer(1950);

print_r($optimiser->get_used_files());

print_r($optimiser->get_unused_files());

print_r($optimiser->get_test());
*/
//print_r($optimiser->get_used_files_bigger_than(3000000));

//$optimiser->setConfigCentralizeMinSize(512000);

//$optimiser->centralize_used_file_and_replace(1264695);
//$optimiser->get_used_files();
//print_r($optimiser->get_used_files());

//var_dump($optimiser->remove_unused_file(54475858));
/*
print_r($optimiser->get_unused_files());
*/
//$res = $optimiser->get_test();
//echo 'FOUND '.count($res)." rows\n\n";
//print_r($res);