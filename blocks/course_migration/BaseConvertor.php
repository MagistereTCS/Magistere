<?php

class BaseConvertor {
    protected $oldcourseid = 0;
    protected $newcourseid = 0;
    protected $lasterror = null;
    protected $log = '';
    protected $migration = null;
    protected $newcoursecontext;

    protected $originalformat;
    protected $convertedformat;
    protected $convertionname;

    protected $disableAttemptsQuizz = false;

    protected $disableTransaction = false;

    protected $unrollCourseOwner = false;

    protected $inplaceConversion = false;

    // define by sub classes
    // from => initial course format name
    // into => new course format name
    protected $from;
    protected $into;

    const CONV_INIT = 0;
    const CONV_FINISHED = 1;
    const CONV_PROGRESS = 2;
    const CONV_FAILED = 3;
    const CONV_FAILED_QUIZ = 4;

    public function get_current_task($courseid)
    {
        global $DB;

        return $DB->get_record_sql("SELECT * 
FROM {block_course_migration} 
WHERE flexcourseid = ? 
AND bcm.from = ? AND bcm.into = ?
ORDER BY startdate DESC LIMIT 1", array($courseid, $this->from, $this->into));

    }

    public function has_been_converted($id)
    {
        global $DB;

        return $DB->get_record_sql("SELECT * 
FROM {block_course_migration} bcm
WHERE bcm.originalformat=? AND bcm.convertedformat=?
AND flexcourseid=? AND status=1", array($this->originalformat, $this->convertedformat, $id));
    }

    public function process($task)
    {
        global $DB,$CFG;
        define('IS_MIGRATING', true);
        ini_set('max_execution_time', 3600);

        $this->log = '';

        $this->migration = $task;
        $courseid = $task->flexcourseid;

        if ($this->migration !== false)
        {
            if ($this->migration->status == self::CONV_FINISHED)
            {
                $this->lasterror = 'Parcours à déjà été converti!';
                $this->l('Check prerequisite error, course already converted (courseid="'.$courseid.'")');
                return false;
            }
            else if ($this->migration->status == self::CONV_PROGRESS)
            {
                $this->lasterror = 'Le parcours est actuellement en cours de Conversion!';
                $this->l('Check prerequisite error, course Conversion in progress (courseid="'.$courseid.'")');
                return false;
            }
        }

        $this->l('Starting delegated transaction');
        if(!$this->disableTransaction){
            $transaction = $DB->start_delegated_transaction();
        }


        try {
            //$this->newcourseid =
            $this->l('Source course ID is '.$courseid);
            $this->oldcourseid = $courseid;

            $this->migration->status = self::CONV_PROGRESS;
            $DB->update_record('block_course_migration', $this->migration);

            $this->l('Checking prerequisite');
            if ($this->check_prerequisite($this->oldcourseid) === false)
            {
                $this->l('Prerequisite failed!');
                $this->l('Canceling the MySQL transaction.');
                throw new Exception('Prerequisite failed! Canceling the MySQL transaction.');
            }

            $this->l('Starting duplication of the course');
            if(!$this->inplaceConversion) {

                if ($this->duplicate_course($this->oldcourseid) === false) {
                    if ($this->unrollCourseOwner) {
                        $this->unrollCourseOwner();
                    }

                    $this->l('Duplication failed!');
                    $this->l('Canceling the MySQL transaction.');
                    throw new Exception('Duplication failed! Canceling the MySQL transaction.');
                }
            }else {
                $this->newcourseid = $this->oldcourseid;
            }

            $this->convert_to_new_format($this->newcourseid);

            $this->post_process($this->newcourseid);


            if(!$this->disableTransaction) {
                $this->l('Commiting the MySQL transaction.');
                $DB->commit_delegated_transaction($transaction);
            }


            $this->l('Send notification.');
            $this->send_notification();

        }
        catch (Exception $e)
        {
            $this->l('Exception catched : ####'.print_r($e,true).'####');
            $this->l('BACKTRACE : ####'.print_r(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,2)).'####');
            $this->l('Canceling all database updates and insert');
            //$transaction->dispose();
            //$transaction->rollback($e);

            if($this->unrollCourseOwner)
            {
                $this->unrollCourseOwner();
            }

            $DB->force_transaction_rollback();

            $this->l('Send failed notification to the user');
            $this->send_notification_failed($this->get_lasterror());
            $this->send_notification_failed_technical_team($e, $this->get_lasterror());

            $this->migration->stdcourseid = $this->newcourseid;
            $this->migration->status = 3;
            $this->migration->logs = $this->log;
            $this->migration->enddate = time();
            $DB->update_record('block_course_migration', $this->migration);

            $this->l('END CATCH');

            return false;
        }



        $this->migration->stdcourseid = $this->newcourseid;
        $this->migration->status = 1;
        $this->migration->enddate = time();
        $this->migration->logs = null;
        $DB->update_record('block_course_migration', $this->migration);

        if($this->unrollCourseOwner)
        {
            $this->unrollCourseOwner();
        }

        return $this->newcourseid;
    }

    protected function unrollCourseOwner()
    {
        global $DB;

        $instances = $DB->get_records('enrol', array('courseid'=>$this->newcourseid, 'enrol'=>'manual'), '', '*');
        foreach($instances as $instance)
        {
            $user_enrolement = enrol_get_plugin('manual');
            $user_enrolement->unenrol_user($instance, $this->migration->userid);
            $this->l('User unenroled as a formateur in new course id="'.$this->newcourseid.'" (courseid="'.$this->oldcourseid.'")');
        }
    }

    protected function post_process($newcourseid)
    {

    }

    public function add_conversion_task($courseid,$keepdata, $status = self::CONV_INIT)
    {
        global $DB, $USER;

        if ( $DB->record_exists('block_course_migration', array('flexcourseid'=>$courseid,'status'=>$status, 'originalformat' => $this->originalformat, 'convertedformat' => $this->convertedformat)) )
        {
            return false;
        }

        $task = new stdClass();
        $task->flexcourseid = $courseid;
        $task->userid = $USER->id;
        $task->status = $status;
        $task->keepdata = $keepdata;
        $task->startdate = time();
        $task->originalformat = $this->originalformat;
        $task->convertedformat = $this->convertedformat;

        return $DB->insert_record('block_course_migration', $task);
    }

    public function get_lasterror()
    {
        return $this->lasterror;
    }

    protected function l($msg)
    {
        global $DB;
        $this->log .= date('Y-m-y_H:i:s::').$msg."\n"; // .get_class($this).'::'
        echo $msg."\n";
        /*
        if ($this->migration != null && isset($this->migration->id) && $this->migration->id > 2)
        {
            $this->migration->logs = $this->log;
            $DB->update_record('block_course_migration', $this->migration);
        }*/
    }

    private function send_notification()
    {
        global $COURSE, $DB, $CFG;

        // Send notification

        $url_old = new moodle_url('/course/view.php',array('id'=>$this->oldcourseid));
        $url_new = new moodle_url('/course/view.php',array('id'=>$this->newcourseid));

        $subject = 'La conversion du parcours "'.$COURSE->fullname.'" sur M@gistère est terminé';

        $messagetext = "Bonjour,\n\nLe parcours \"".$COURSE->fullname."\" a été converti.\nLa nouvelle version \"'.$this->convertionname.'\" est maintenant disponible sur votre plateforme.\n\nParcours d'origine : ".$url_old."\nParcours converti : ".$url_new."\n\nCordialement,\nM@gistère\n\n(Cet e-mail est envoyé automatiquement, merci de ne pas y répondre. Contactez votre administrateur en cas de problème)";
        $messagehtml = "Bonjour,<br/>\n<br/>\nLe parcours \"".$COURSE->fullname."\" a été converti.<br/>\nLa nouvelle version \"".$this->convertionname."\" est maintenant disponible sur votre plateforme.<br/>\n<br/>\nParcours d'origine : <a href=\"".$url_old."\">".$url_old."</a><br/>\nParcours converti : <a href=\"".$url_new."\">".$url_new."</a><br/>\n<br/>\nCordialement,<br/>\nM@gistère<br/>\n<br/>\n(Cet e-mail est envoyé automatiquement, merci de ne pas y répondre. Contactez votre administrateur en cas de problème)";

        $fromuser = new stdClass();
        $fromuser->id = 999999999;
        $fromuser->email = str_replace('https://', 'no-reply@', $CFG->magistere_domaine);
        $fromuser->deleted = 0;
        $fromuser->auth = 'manual';
        $fromuser->suspended = 0;
        $fromuser->mailformat = 1;
        $fromuser->maildisplay = 1;

        $user = $DB->get_record('user', array('id'=>$this->migration->userid));

        $this->l('Sending success notification to user '.$this->migration->userid.'.');
        email_to_user($user, $fromuser, $subject, $messagetext, $messagehtml);
    }

    private function send_notification_failed($msg)
    {
        global $COURSE, $DB, $CFG;

        // Send notification

        $url_old = new moodle_url('/course/view.php',array('id'=>$this->oldcourseid));

        $msg = (!$msg ? '' : '('.$msg.')');

        $subject = 'La conversion du parcours "'.$COURSE->fullname.'" sur M@gistère a échouée';

        $messagetext = "Bonjour,\n\nLe parcours \"".$COURSE->fullname."\" n'a pas pu être converti.\nUne erreur s'est produite ".$msg."! Merci de réessayer plus tard ou de contacter votre administrateur.\n\nParcours d'origine : ".$url_old."\n\nCordialement,\nM@gistère\n\n(Cet e-mail est envoyé automatiquement, merci de ne pas y répondre. Contactez votre administrateur en cas de problème)";
        $messagehtml = "Bonjour,<br/>\n<br/>\nLe parcours \"".$COURSE->fullname."\" n'a pas pu être converti.<br/>\nUne erreur s'est produite ".$msg."! Merci de réessayer plus tard ou de contacter votre administrateur.<br/>\n<br/>\nParcours d'origine : <a href=\"".$url_old."\">".$url_old."</a><br/>\n<br/>\nCordialement,<br/>\nM@gistère<br/>\n<br/>\n(Cet e-mail est envoyé automatiquement, merci de ne pas y répondre. Contactez votre administrateur en cas de problème)";

        $fromuser = new stdClass();
        $fromuser->id = 999999999;
        $fromuser->email = str_replace('https://', 'no-reply@', $CFG->magistere_domaine);
        $fromuser->deleted = 0;
        $fromuser->auth = 'manual';
        $fromuser->suspended = 0;
        $fromuser->mailformat = 1;
        $fromuser->maildisplay = 1;

        $user = $DB->get_record('user', array('id'=>$this->migration->userid));

        $this->l('Sending failed notification to user '.$this->migration->userid.'.');
        email_to_user($user, $fromuser, $subject, $messagetext, $messagehtml);
    }

    private function send_notification_failed_technical_team($e, $msg)
    {
        global $COURSE, $DB, $CFG;

        // Send notification
        $url_old = new moodle_url('/course/view.php',array('id'=>$this->oldcourseid));

        $msg = (!$msg ? '' : '('.$msg.')');

        $currenturl = new moodle_url('/');
        $subject = '[M@gistère] '.$currenturl->out(false). ' : échec convertion "'.$COURSE->fullname.'" (' . $COURSE->id . ')';

        $messagetext = "LOGS : \n\n\n".$msg."\n\n\n URL ORIGINALE : ".$url_old;
        $messagetext .= "\n\n\nException catch:\n\n".print_r($e, true);

        $messagehtml = "LOGS : <br/><br/><br/>".$msg."<br/><br/><br/> URL ORIGINALE : ".$url_old;
        $messagehtml .= "<br/><br/><br/>Exception catch:<br/><br/><pre>".print_r($e, true).'</pre>';

        $fromuser = new stdClass();
        $fromuser->id = 999999999;
        $fromuser->email = str_replace('https://', 'no-reply@', $CFG->magistere_domaine);
        $fromuser->deleted = 0;
        $fromuser->auth = 'manual';
        $fromuser->suspended = 0;
        $fromuser->mailformat = 1;
        $fromuser->maildisplay = 1;

        $receivers = array("quentin.gourbeault@tcs.com", "valentin.sevaille@tcs.com","jeanbaptiste.lefevre@tcs.com", "nicolas.nenon@tcs.com");
        foreach ($receivers as $val) {
            $userto = new stdClass();
            $userto->id = -99;
            $userto->email = $val;
            $userto->deleted = 0;
            $userto->auth = 'manual';
            $userto->suspended = 0;
            $userto->mailformat = 1;
            $userto->maildisplay = 1;

            email_to_user($userto, $fromuser, $subject, $messagetext, $messagehtml);
            $this->l('Sending failed notification to user '.$val.'.');
        }
    }


    protected function check_prerequisite($courseid)
    {

    }

    /**
     * Duplicate the course given in parameters
     * @param int $courseid The ID of the course to duplicate
     * @return boolean|int Return false if failed or new course ID if succeed
     */
    private function duplicate_course($courseid)
    {
        global $DB, $CFG;
        
        $keepdata = $this->migration->keepdata==1;
        
        $this->backupdefaults = $options = array(
            array ('name' => 'activities', 'value' => 1),
            array ('name' => 'blocks', 'value' => 1),
            array ('name' => 'filters', 'value' => 1),
            array ('name' => 'users', 'value' => ($keepdata)?1:0),
            array ('name' => 'role_assignments', 'value' => 1),
            array ('name' => 'comments', 'value' => 1),
            array ('name' => 'userscompletion', 'value' => 1),
            array ('name' => 'logs', 'value' => 1),
            array ('name' => 'grade_histories', 'value' => 1)
        );

        if (!isset($courseid) || $courseid < 2)
        {
            $this->lasterror = 'Le parcours est introuvable ou invalide ('.$courseid.')!';
            $this->l('Duplication error, courseid not found or id < 2 (courseid="'.$courseid.'")');
            return false;
        }

        $course = $DB->get_record('course', array('id'=>$courseid),'*');

        if ($course === false)
        {
            $this->lasterror = 'Le parcours est introuvable ('.$courseid.')!';
            $this->l('Duplication error, course no found (courseid="'.$courseid.'")');
            return false;
        }


        // Find a unique and available shortname
        $shortname = $course->shortname;
        $oldshortname = $course->shortname;

        do {
            $shortname .= '_';

            if(strlen($shortname) > 255){
                $this->lasterror = 'Le shortname est trop long ('.$shortname.')';
                $this->l('Shortname too long ('.$shortname.')');
                return false;
            }
        }while($DB->get_record('course', array('shortname' => $shortname)) !== false);

        $course->shortname = $shortname;
        $DB->update_record('course', $course);
        $this->l('Duplication : OLD course shortname updated from "'.$oldshortname.'" to "'.$course->shortname.'" (courseid="'.$courseid.'")');

        while($DB->get_record('course', array('shortname' => $oldshortname)) !== false){
            $oldshortname .= '_';

            if(strlen($oldshortname) > 255){
                $this->lasterror = 'Le old shortname est trop long ('.$oldshortname.')';
                $this->l('Old shortname too long ('.$oldshortname.')');
                return false;
            }
        }

        $this->l('Duplication : Starting course duplication (courseid="'.$courseid.'")');

        define('MIGRATION_DISABLE_QUIZ_ATTEMPT', $this->disableAttemptsQuizz);
        $newcourse = core_course_external::duplicate_course($course->id, $course->fullname, $oldshortname, $course->category, ($keepdata?0:1), $this->backupdefaults);

        $this->newcourseid = $newcourse['id'];

        $this->l('Duplication : End of course duplication, new course id="'.$this->newcourseid.'" (courseid="'.$courseid.'")');

        $this->l('Duplication : Enrol user as a formateur in new course id="'.$this->newcourseid.'" (courseid="'.$courseid.'")');

        $formateur = $DB->get_record('role',array('shortname'=>'formateur'));
        $instances = $DB->get_records('enrol', array('courseid'=>$this->newcourseid, 'enrol'=>'manual'), '', '*');

        foreach($instances as $instance)
        {
            $user_enrolement = enrol_get_plugin('manual');
            $user_enrolement->enrol_user($instance, $this->migration->userid,  $formateur->id, 0,  0, NULL);
            $this->l('Duplication : User enroled as a formateur in new course id="'.$this->newcourseid.'" (courseid="'.$courseid.'")');
        }

        $this->l('Duplication : End of enrol user as a formateur in new course id="'.$this->newcourseid.'" (courseid="'.$courseid.'")');


        $this->l('Duplication : Restoring indexation data for new course id="'.$this->newcourseid.'" (courseid="'.$courseid.'")');

        IndexationServices::copy_indexation($courseid, $this->newcourseid);

        $this->l('Duplication : End of indexation data restoration for new course id="'.$this->newcourseid.'" (courseid="'.$courseid.'")');

        return $this->newcourseid;
    }

    private function convert_to_new_format($courseid)
    {
        global $DB;

        $this->l('Conversion : ### Start function convert_flexpage_block("'.$courseid.'") ## (oldcourseid="'.$this->oldcourseid.'",newcourseid="'.$this->newcourseid.'")');

        $this->oldcourse = $DB->get_record('course', array('id'=>$this->oldcourseid),'*');
        if ($this->oldcourse === false)
        {
            $this->lasterror = 'Parcours S introuvable!';
            $this->l('Conversion error : OLD course not found in Database (oldcourseid="'.$this->oldcourseid.'",newcourseid="'.$this->newcourseid.'")');
            return false;
        }

        $this->newcourse = $DB->get_record('course', array('id'=>$this->newcourseid),'*');
        if ($this->newcourse=== false)
        {
            $this->lasterror = 'Parcours D introuvable!';
            $this->l('Conversion error : NEW course not found in Database (oldcourseid="'.$this->oldcourseid.'",newcourseid="'.$this->newcourseid.'")');
            return false;
        }

        $this->newcoursecontext = context_course::instance($this->newcourseid);

        $this->process_course_format_options();

        $this->process_section();

        $this->process_summary_data();

        $this->process_blocks_and_activities();

        $this->update_sequence_module();

        return true;
    }

    protected function process_course_format_options()
    {
        global $DB;

        $block_positions = $DB->get_records('block_positions', array('contextid' => $this->newcoursecontext->id, 'pagetype' => 'course-view-'.$this->originalformat));

        foreach ($block_positions as $block_position) {
            $block_position->pagetype = 'course-view-' . $this->convertedformat;

            if($DB->get_record('block_positions', ['blockinstanceid' => $block_position->blockinstanceid,
                'contextid' => $block_position->contextid,
                'pagetype' => $block_position->pagetype,
                'subpage' => $block_position->subpage])){
                continue;
            }

            $DB->update_record('block_positions', $block_position);
        }
    }

    protected function process_section()
    {

    }

    protected function process_summary_data()
    {

    }

    protected function process_blocks_and_activities()
    {

    }

    protected function update_sequence_module()
    {

    }

    public function setDisableQuizzAttempt($disabled)
    {
        $this->disableAttemptsQuizz = $disabled;
    }

    public function setUnrollOwner($unroll)
    {
        $this->unrollCourseOwner = $unroll;
    }

    public function disableTransaction(){
        $this->disableTransaction = true;
    }

    public function setInplaceConversion(){
        $this->inplaceConversion = true;
    }
}