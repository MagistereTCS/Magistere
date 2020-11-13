<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/externallib.php');
require_once($CFG->dirroot.'/local/magisterelib/indexationServices.php');
require_once($CFG->dirroot.'/blocks/course_migration/BaseConvertor.php');
require_once($CFG->dirroot.'/blocks/course_migration/lib.php');
require_once($CFG->dirroot.'/blocks/course_management/lib/duplication_lib.php');


class MagistereConversion
{
	public function runTask()
	{
		global $DB, $CFG;

		// Remove failed task that are still in status 2
		$runningtasks = $DB->get_records_sql('SELECT * FROM {block_course_migration} WHERE status=2 AND startdate < :startdate',array('startdate'=>time()-3600));
		foreach($runningtasks AS $runningtask)
		{
		    $runningtask->status = 3;
		    $DB->update_record('block_course_migration', $runningtask);
		}
		
		$i = 0;
		while(true)
		{
			if ($i > 100)
			{
				break;
			}
			
			$task = $DB->get_record_sql('SELECT 
    bcm.id,
    bcm.flexcourseid,
    bcm.stdcourseid,
    bcm.status,
    bcm.startdate,
    bcm.enddate,
    bcm.userid,
    bcm.originalformat,
    bcm.convertedformat,
    bcm.keepdata,
    bcm.validated
FROM {block_course_migration} bcm
LEFT JOIN {course} c ON c.id=bcm.flexcourseid
WHERE (bcm.status=0 OR bcm.status=4) AND c.id IS NOT NULL
ORDER BY RAND() 
LIMIT 1');
			
			if ($task === false)
			{
				break;
			}else{
				echo '####FOUND TASK : '.print_r($task,true)."####\n";
			}

			$convertorclass = 'Convert'.ucfirst($task->originalformat).'To'.ucfirst($task->convertedformat);
			if(file_exists($CFG->dirroot.'/blocks/course_migration/'.$convertorclass.'.php'))
			{
			    require_once($CFG->dirroot.'/blocks/course_migration/'.$convertorclass.'.php');
                $convertor = new $convertorclass();
                if($task->status == 4){
                    $convertor->setDisableQuizzAttempt(true);
                }

                if($task->status == 5){
                    $convertor->setUnrollOwner(true);
                }

                $convertor->process($task);
            }
			else {
			    echo $CFG->dirroot.'/course_migration/'.$convertorclass.'.php';
                echo '####CONVERT CLASS '.$convertorclass.' NOT FOUND####'."\n";
            }
			
			$i++;
		}
		
		return true;
	}
}

class MagistereAutomaticConversion {
    public function runTask()
    {
        global $DB;

        $sql = 'SELECT bcm.id,bcm.flexcourseid,bcm.stdcourseid,bcm.userid,bcm.status,bcm.startdate,bcm.enddate,bcm.originalformat,bcm.convertedformat,bcm.keepdata,bcm.validated,c1.fullname stdcoursefullname
                FROM {block_course_migration} as bcm 
                LEFT JOIN {course} c ON (c.id = bcm.flexcourseid)
                LEFT JOIN {course} c1 ON c1.id=bcm.stdcourseid
                WHERE bcm.status=? AND c.id IS NOT NULL AND (bcm.validated = ? OR (bcm.validated != ? AND bcm.enddate < ?))
                AND bcm.originalformat = "flexpage"
                ORDER by bcm.id
                LIMIT 50';

        $tasks = $DB->get_records_sql($sql, array(BaseConvertor::CONV_FINISHED, migrationValidation::PENDING, migrationValidation::OK, time()-(1*86400)));
        $mv = null;
        $notifications = [];

        foreach($tasks as $task){
            $course = $DB->get_record('course', array('id'=> $task->flexcourseid));
            $course2 = $DB->get_record('course', array('id'=> $task->stdcourseid));
            if($course){
                echo '####FOUND TASK : '.print_r($task->id,true)."####\n";

                if(!isset($notifications[$task->userid])){
                    $notifications[$task->userid] = [];
                    $notifications[$task->userid]['ok'] = [];
                    $notifications[$task->userid]['fail'] = [];
                }


                try{
                    echo '####SWAP DES ACTIVITES VIA SUR LE PARCOURS CONVERTI ';
                    unset($mv);
                    $mv = new migrationValidation();
                    $error = $mv->migrate_via($task);

                    if ($error === false)
                    {
                        echo '####TRASHING ORIGINAL COURSE : '.print_r($task->flexcourseid,true)."####\n";
                        //delete_course($course);
                        $corbeille = $DB->get_record('course_categories',array('name'=>'Corbeille','parent'=>0));
                        discard_course($task->flexcourseid, $corbeille->id,false);
                        echo "####ORIGINAL COURSE TRASHED####\n";
                    }else{
                        echo '####ERROR FOUND IN VIA MIGRATION, WE KEEP THE ORIGINAL COURSE : '.print_r($task->flexcourseid,true)."####\n";
                    }
                    
                    // Remove course_migration block from converted course
                    $course_context = context_course::instance($course2->id);
                    
                    $migration_blocks = $DB->get_records('block_instances', array('parentcontextid'=>$course_context->id,'blockname'=>'course_migration'));
                    
                    foreach($migration_blocks AS $migration_block)
                    {
                        $DB->delete_records('block_instances',array('id'=>$migration_block->id));
                        $DB->delete_records('block_positions',array('blockinstanceid'=>$migration_block->id));
                    }
                    

                    $update_course = new stdClass();
                    $update_course->id = $task->stdcourseid;
                    $update_course->visible = 1;
                    $DB->update_record('course', $update_course);
                    echo '####CONVERTED COURSE VISIBLE : '.print_r($task->flexcourseid,true)."####\n";

                    $notif = new stdClass();
                    $notif->id = $task->stdcourseid;
                    $notif->fullname = $task->stdcoursefullname;

                    $notifications[$task->userid]['ok'][] = $notif;
                } catch(Exception $e) {
                    echo '####ERROR DELETE ORIGINAL COURSE : '.print_r($e->getMessage(),true)."####\n";

                    $notif = new stdClass();
                    $notif->id = $task->stdcourseid;
                    $notif->fullname = $task->stdcoursefullname;

                    $notifications[$task->userid]['fail'][] = $notif;
                }
            }
        }

        $this->sendValidateNotification($notifications);

        return true;
    }

    function sendValidateNotification($notifications)
    {
        global $CFG, $DB;

        foreach($notifications as $userid => $notification)
        {
            $subject = get_string('validationsubject', 'block_course_migration');
            $text = '';
            $html = '';

            if(count($notification['ok'])){
                $tlist = '';
                $hlist = '';

                foreach($notification['ok'] as $notif){
                    $tlist .= $notif->fullname . "\n";
                    $hlist .= html_writer::link(new moodle_url('/course/view.php', array('id' => $notif->id)), $notif->fullname)."<br/>";
                }

                $text = get_string('validationmessageoktxt', 'block_course_migration', $tlist);
                $html = get_string('validationmessageokhtml', 'block_course_migration', $hlist);
            }

            if(count($notification['fail'])){
                $tlist = '';
                $hlist = '';

                foreach($notification['fail'] as $notif){
                    $tlist .= $notif->fullname . "\n";
                    $hlist .= html_writer::link(new moodle_url('/course/view.php', array('id' => $notif->id)), $notif->fullname)."<br/>";
                }

                $text .= get_string('validationmessagefailtxt', 'block_course_migration', $tlist);
                $html .= get_string('validationmessagefailhtml', 'block_course_migration', $hlist);
            }

            $text = "Bonjour,\n\n".$text."\n\nVous pouvez vous rendre sur l'interface de supervision pour contrôler les validations.\n
Cordialement,\nM@gistère\n\n
Cet e-mail est envoyé automatiquement, merci de ne pas y répondre. Contactez votre administrateur en cas de problème)";
            $html = "Bonjour,<br/><br/>".$html."<br/><br/>Vous pouvez vous rendre sur l'interface de supervision pour contrôler les validations.<br/>
Cordialement,<br/>M@gistère<br/><br/>
Cet e-mail est envoyé automatiquement, merci de ne pas y répondre. Contactez votre administrateur en cas de problème";

            $fromuser = new stdClass();
            $fromuser->id = 999999999;
            $fromuser->email = str_replace('https://', 'no-reply@', $CFG->magistere_domaine);
            $fromuser->deleted = 0;
            $fromuser->auth = 'manual';
            $fromuser->suspended = 0;
            $fromuser->mailformat = 1;
            $fromuser->maildisplay = 1;

            $user = $DB->get_record('user', array('id'=>$userid));

            echo 'Envoie d\'un mail a ID='.$user->id.'&FIRSTNAME='.$user->firstname.'&LASTNAME='.$user->lastname.'&EMAIL='.$user->email."\n\nCONTENT=".$text."\n\n";
            email_to_user($user, $fromuser, $subject, $text, $html);
        }
    }
}

class migrationValidation
{
    private $logs = '';
    private $task = null;

    const NONE = 0;
    const OK = 1;
    const PENDING = 2;

    public function __construct()
    {
        
    }
    
    private function l($msg)
    {
        global $DB;
        
        $m = date('Y-m-y_H:i:s::').$msg."\n";
        $this->task->validationlogs .= $m;
        
        if (!defined('MIG_VAL_NO_DEBUG'))
        {
            echo $m;
        }
    }
    
    public function migrate_via($task)
    {
        global $DB;
        
        $this->task = $task;
        $this->logs = '';
        
        $error1 = $this->process_via_activities($task);
        $error2 = $this->process_viaassign_activities($task);
        
        $error = $error1 !== false || $error2 !== false;
        
        $this->task->validated = ($error)?self::NONE:self::OK;
        
        $DB->update_record('block_course_migration', $this->task);
        
        return $error;
    }
    
    private function process_via_activities($task){
        global $DB;
        
        $this->task = $task;
        
        $error = false;
        
        $this->l('VIAswap==Starting task : '.print_r($task,true));
        $via_activities = $DB->get_records('via', array('course' => $task->stdcourseid));
        
        $this->l('VIAswap==Found '.count($via_activities).' via activities');
        
        foreach ($via_activities as $via_activity){
            $this->l('VIAswap==Processing activity : '.print_r($via_activity,true));
            try{
                
                $assoc_viaids = $DB->get_record('block_course_migration_via', array('new_viaid' => $via_activity->id));
                
                if ($assoc_viaids === false)
                {
                    $this->l('VIAswap==WARNING: No assoc found, skip this activity (was '.$via_activity->id.')');
                    continue;
                }
                
                $this->l('VIAswap==Assoc found : '.print_r($assoc_viaids,true));
                
                $old_via = $DB->get_record('via', array('id' => $assoc_viaids->old_viaid));
                if ($old_via === false)
                {
                    $this->l('VIAswap==WARNING: Old VIA activity not found, skip this activity (was '.$assoc_viaids->old_viaid.')');
                    continue;
                }
                $this->l('VIAswap==Get old via activity : '.print_r($old_via,true));
                
                $viamod = $DB->get_record('modules', array('name'=>'via'));
                $this->l('VIAswap==Get via module : '.print_r($viamod,true));
                
                $new_cm = $DB->get_record('course_modules', array('module' => $viamod->id, 'course' => $via_activity->course, 'instance' => $via_activity->id));
                if ($new_cm === false)
                {
                    $this->l('VIAswap==ERROR: New CM not found, skip this activity (was mod '.$viamod->id.' activity '.$via_activity->id.')');
                    $error = true;
                    continue;
                }
                $this->l('VIAswap==Get the new via course module : '.print_r($new_cm,true));
                
                $old_cm = $DB->get_record('course_modules', array('module' => $viamod->id, 'course' => $old_via->course, 'instance' => $old_via->id));
                if ($old_cm === false)
                {
                    $this->l('VIAswap==ERROR: Old CM not found, skip this activity(was mod '.$viamod->id.' activity '.$old_via->id.')');
                    $error = true;
                    continue;
                }
                $this->l('VIAswap==Get the old via course module : '.print_r($old_cm,true));
                
                
                
                
                // Swap VIA activities
                $this->l('VIAswap==Swap old/new VIA activities');
                $updatedNewViaActivity = clone $via_activity;
                $updatedNewViaActivity->course = $old_via->course;
                $this->l('VIAswap==Updating new via record : '.print_r($updatedNewViaActivity,true));
                $DB->update_record('via', $updatedNewViaActivity);
                
                $updatedOldViaActivity = clone $old_via;
                $updatedOldViaActivity->course = $via_activity->course;
                $this->l('VIAswap==Updating old via record : '.print_r($updatedOldViaActivity,true));
                $DB->update_record('via', $updatedOldViaActivity);
                
                
                // Swap VIA course modules
                
                $updatedNewViaCM = clone $new_cm;
                $updatedNewViaCM->instance = $old_cm->instance;
                $this->l('VIAswap==SwapVIACM==Replace '.print_r($new_cm,true).' ==>> '.print_r($updatedNewViaCM,true));
                $DB->update_record('course_modules', $updatedNewViaCM);
                
                $updatedOldViaCM = clone $old_cm;
                $updatedOldViaCM->instance = $new_cm->instance;
                $this->l('VIAswap==SwapVIACM==Replace '.print_r($old_cm,true).' ==>> '.print_r($updatedOldViaCM,true));
                $DB->update_record('course_modules', $updatedOldViaCM);
                
                /*
                // Update course section
                $this->l('VIAswap==UpdatingSection');
                $new_section = $DB->get_record('course_sections', array('id'=>$updatedNewViaCM->section));
                $this->l('VIAswap==UpdatingSection==New section is '.print_r($new_section,true));
                $old_section = $DB->get_record('course_sections', array('id'=>$updatedOldViaCM->section));
                $this->l('VIAswap==UpdatingSection==Old section is '.print_r($old_section,true));
                
                $new_section->sequence = str_replace($updatedOldViaCM->id,$updatedNewViaCM->id,$new_section->sequence);
                $this->l('VIAswap==UpdatingSection==Updating new section with '.print_r($new_section,true));
                
                $DB->update_record('course_sections', $new_section);
                $this->l('VIAswap==UpdatingSection==New section updated');
                
                $old_section->sequence = str_replace($updatedNewViaCM->id,$updatedOldViaCM->id,$old_section->sequence);
                $this->l('VIAswap==UpdatingSection==Updating old section with '.print_r($old_section,true));
                
                $DB->update_record('course_sections', $old_section);
                $this->l('VIAswap==UpdatingSection==Old section updated');
                */
                
                /*
                // Update the course module context
                $this->l('VIAswap==UpdatingContext');
                $new_context = $DB->get_record('context', array('contextlevel'=> CONTEXT_MODULE, 'instanceid' => $updatedNewViaCM->id));
                $this->l('VIAswap==UpdatingContext==New cm context is '.print_r($new_context,true));
                $newcourse_context = context_course::instance($updatedNewViaCM->course);
                $this->l('VIAswap==UpdatingContext==New course context is '.print_r($newcourse_context,true));
                
                $old_context = $DB->get_record('context', array('contextlevel'=> CONTEXT_MODULE, 'instanceid' => $updatedOldViaCM->id));
                $this->l('VIAswap==UpdatingContext==Old cm context is '.print_r($old_context,true));
                $oldcourse_context = context_course::instance($updatedOldViaCM->course);
                $this->l('VIAswap==UpdatingContext==Old course context is '.print_r($oldcourse_context,true));
                
                
                $new_context_path = explode('/',$new_context->path);
                $this->l('VIAswap==UpdatingContext==Splitting new context path '.print_r($new_context_path,true));
                $new_context_path[count($new_context_path)-1] = $old_context->id;
                $this->l('VIAswap==UpdatingContext==Replacing new module context '.print_r($new_context_path,true));
                $new_context->path = implode('/', $new_context_path);
                $this->l('VIAswap==UpdatingContext==Updating the new cm context '.print_r($new_context,true));
                
                $DB->update_record('context', $new_context);
                $this->l('VIAswap==UpdatingContext==Context updated');
                
                
                $old_context_path = explode('/',$old_context->path);
                $this->l('VIAswap==UpdatingContext==Splitting new context path '.print_r($old_context_path,true));
                $old_context_path[count($old_context_path)-1] = $new_context->id;
                $this->l('VIAswap==UpdatingContext==Replacing new module context '.print_r($old_context_path,true));
                $old_context->path = implode('/', $old_context_path);
                $this->l('VIAswap==UpdatingContext==Updating the new cm context '.print_r($old_context,true));
                
                $DB->update_record('context', $old_context);
                $this->l('VIAswap==UpdatingContext==Context updated');
                */
                
                $this->l('VIAswap==Rebuilding course cache for "'.$updatedNewViaCM->course.'"');
                rebuild_course_cache($updatedNewViaCM->course, true);
                
                $this->l('VIAswap==Rebuilding course cache for "'.$updatedOldViaCM->course.'"');
                rebuild_course_cache($updatedOldViaCM->course, true);
                
            } catch (Exception $e){
                $this->l(print_r($e->getMessage(),true));
            }
         }
         
         return $error;
    }
        
    public function process_viaassign_activities($task){
        global $DB;
        
        $this->task = $task;
        
        $error = false;
        
        $this->l('VIAASSIGNreplace==Starting task : '.print_r($task,true));
        $viaassign_activities = $DB->get_records('viaassign', array('course' => $task->stdcourseid));
        
        $this->l('VIAASSIGNreplace==Found '.count($viaassign_activities).' viaassign activities');
        
        foreach ($viaassign_activities as $viaassign_activity){
            
            try {
                $assoc_viaassignids = $DB->get_record('block_course_migration_via2', array('new_viaassignid' => $viaassign_activity->id));
                
                if ($assoc_viaassignids === false)
                {
                    $this->l('VIAASSIGNreplace==WARNING: No assoc found, skip this activity (was '.$viaassign_activity->id.')');
                    continue;
                }
            
            
                $this->l('VIAASSIGN==VIAmove==list submited via activities : '.print_r($viaassign_activity,true));
                $via_activities = $DB->get_records_sql('SELECT v.* FROM {viaassign_submission} vs INNER JOIN {via} v ON (vs.viaid = v.id) WHERE vs.viaassignid = :viaassignid AND v.id IS NOT NULL', array('viaassignid' => $assoc_viaassignids->old_viaassignid));
            
                $this->l('VIAASSIGN==VIAmove==Found '.count($via_activities).' linked via activities');
            
                foreach ($via_activities as $via_activity) {
                $this->l('VIAASSIGN==VIAmove==Processing activity : '.print_r($via_activity,true));
                    
                    // Move old via activity
                    $via_activity->course = $task->stdcourseid;
                    $this->l('VIAASSIGN==VIAmove==Moving activity to course '.$task->stdcourseid);
                    $DB->update_record('via', $via_activity);
                    $this->l('VIAASSIGN==VIAmove==Activity moved');
                }
            
                $this->l('VIAASSIGNmove==Processing activity : '.print_r($viaassign_activity,true));
                
                $submissions = $DB->get_records('viaassign_submission', array('viaassignid'=>$assoc_viaassignids->old_viaassignid));
                $this->l('Found '.count($submissions).' submissions for this activity ('.$assoc_viaassignids->old_viaassignid.')');
                
                foreach($submissions AS $submission)
                {
                    $this->l('VIAASSIGNmove==Processing submission : '.print_r($submission,true));
                    
                    $this->l('VIAASSIGNmove==Replace old VIAASSIGN activity id with new one in the submission');
                    $submission->viaassignid = $assoc_viaassignids->new_viaassignid;
                    $this->l('VIAASSIGNmove==Updating new viaassign submission record : '.print_r($submission,true));
                    $DB->update_record('viaassign_submission', $submission);
                }
                
                
                $this->l('VIAASSIGNmove==Rebuilding course cache for "'.$task->stdcourseid.'"');
                rebuild_course_cache($task->stdcourseid, true);
                
            } catch (Exception $e){
                $this->l('EXCEPTION: '.print_r($e->getMessage(),true));
            }
        }
        
        return $error;
    }

}


// $DB->get_record('block_course_migration', array('id'=>56));