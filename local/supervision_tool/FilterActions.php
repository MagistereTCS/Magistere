<?php

require_once($CFG->dirroot.'/blocks/course_management/lib/duplication_lib.php');
require_once($CFG->dirroot.'/blocks/course_migration/lib.php');

class FilterActions {
    const MODULAR_MIGRATION = 0;
    const TOPICS_MIGRATION = 1;
    const MOVE_TO_ARCHIVE = 2;
    const MOVE_TO_TRASH = 3;
    const VALIDATION = 4;
    const MOVE_TO = 5;
    const NO_CATEGORY = -1;

    const PARAM_CATEGORY_ID = 'actioncategoryid';
    const PARAM_ACCESS_PARTICIPANT = 'actionaccess';
    const PARAM_ACTION = 'actionTodo';
    const PARAM_COURSEIDS = 'courseids';
    const PARAM_KEEP_DATA = 'actionkeepdata';

    private $actionToPerform;
    private $courseids;
    private $keepdata;

    private $msg;

    public function __construct()
    {
        $this->actionToPerform = null;
        $this->courseids = array();
        $this->keepdata = false;
        $this->msg = '';
    }

    public function loadFromForm()
    {
        $this->actionToPerform = optional_param(self::PARAM_ACTION, null, PARAM_INT);
        $this->keepdata = optional_param(self::PARAM_KEEP_DATA, 0, PARAM_INT);

        $this->courseids = optional_param(self::PARAM_COURSEIDS, null, PARAM_TEXT);
        $this->courseids = explode(',', $this->courseids);
    }

    public function perform()
    {
        if($this->actionToPerform == self::MODULAR_MIGRATION){
            $this->migrateModular();
        }

        if($this->actionToPerform == self::TOPICS_MIGRATION){
            $this->migrateTopics();
        }

        if($this->actionToPerform == self::MOVE_TO_TRASH){
            $this->moveToTrash();
        }

        if($this->actionToPerform == self::MOVE_TO_ARCHIVE){
            $this->moveToArchive();
        }

        if($this->actionToPerform == self::MOVE_TO){
            $this->moveTo();
        }

        if($this->actionToPerform == self::VALIDATION){
            $this->validate();
        }
    }

    public function getNotification()
    {
        global $OUTPUT;
        if($this->actionToPerform == self::MODULAR_MIGRATION || $this->actionToPerform == self::TOPICS_MIGRATION){
            $this->msg = get_string('notification_migration', 'local_supervision_tool');
        }

        if($this->actionToPerform == self::MOVE_TO_TRASH || $this->actionToPerform == self::MOVE_TO_ARCHIVE){
            $this->msg = get_string('notification_trash_archive', 'local_supervision_tool') . $this->msg;;
        }

        if($this->actionToPerform == self::VALIDATION){
            $this->msg =  get_string('notification_validation_archive', 'local_supervision_tool');
        }

        if($this->msg == ''){
            return false;
        }
        return $OUTPUT->notification($this->msg, 'success');
    }

    private function migrateModular()
    {
        global $CFG, $DB;

        require_once($CFG->dirroot.'/blocks/course_migration/ConvertFlexpageToModular.php');
        require_once($CFG->dirroot.'/blocks/course_migration/ConvertTopicsToModular.php');

        $flexpageToModular = new ConvertFlexpageToModular();
        $topicsToModular = new ConvertTopicsToModular();


        foreach($this->courseids as $id){
            $c = $DB->get_record('course', array('id' => $id), 'id,format');

            $coursemigrationblock = new stdClass();
            $coursemigrationblock->blockname = 'course_migration';
            $coursemigrationblock->pagetypepattern = 'course-view-*';
            $coursemigrationblock->defaultregion = 'side-pre';
            $coursemigrationblock->defaultweight = 1;
            $coursemigrationblock->showinsubcontexts = 0;
            $coursemigrationblock->timecreated = time();
            $coursemigrationblock->timemodified = time();


            if($c->format == 'topics' ){
                if($topicsToModular->has_been_converted($id)){
                    continue;
                }

                $ctx = context_course::instance($id);

                if(!$DB->get_record('block_instances',array('blockname' => 'course_migration', 'parentcontextid' => $ctx->id))){
                    $coursemigrationblock->parentcontextid = $ctx->id;
                    $DB->insert_record('block_instances', $coursemigrationblock);
                }

                $topicsToModular->add_conversion_task($id, $this->keepdata);
            }
        }
    }

    private function migrateTopics()
    {
        global $CFG, $DB;

        require_once($CFG->dirroot.'/local/course_migration/ConvertFlexpageToTopics.php');

        $coursemigrationblock = new stdClass();
        $coursemigrationblock->blockname = 'course_migration';
        $coursemigrationblock->pagetypepattern = 'course-view-*';
        $coursemigrationblock->defaultregion = 'side-pre';
        $coursemigrationblock->defaultweight = 1;
        $coursemigrationblock->showinsubcontexts = 0;

        $flexpageToTopics = new ConvertFlexpageToTopics();

        foreach($this->courseids as $id){
            $c = $DB->get_record('course', array('id' => $id), 'id,format');

            if($c->format == 'flexpage'){
                if($flexpageToTopics->has_been_converted($id)){
                    continue;
                }

                $ctx = context_course::instance($id);

                if(!$DB->get_record('block_instances',array('blockname' => 'course_migration', 'parentcontextid' => $ctx->id))){
                    $coursemigrationblock->parentcontextid = $ctx->id;
                    $DB->insert_record('block_instances', $coursemigrationblock);
                }

                $flexpageToTopics->add_conversion_task($id, $this->keepdata);
            }
        }
    }

    private function moveTo()
    {
        global $DB;

        $context = context_system::instance();

        if (!has_capability('local/supervision_tool:moveto', $context))
        {
            return;
        }

        $selectedCategory = required_param(FilterActions::PARAM_CATEGORY_ID, PARAM_INT);
        $cat = $DB->get_record_sql('SELECT cc.id, co.path 
FROM {course_categories} cc
INNER JOIN {context} co ON co.instanceid=cc.id
WHERE co.contextlevel=40 AND cc.id=?', array($selectedCategory));

        $warningmsg = [];
        if($cat) {
            foreach ($this->courseids as $id) {
                $c = $DB->get_record('course', array('id' => $id));
                $c->category = $cat->id;
                update_course($c);

                if (!$c) {
                    $warningmsg[] = get_string('coursenotmovemessage', 'local_supervision_tool', $id);
                    continue;
                }


            }
        }else{
            $warningmsg[] = '';// TODO add warning message here
        }

        if(count($warningmsg)){
            $this->msg = '<br/><br/>'.implode('<br/>', $warningmsg);
        }
    }

    private function moveToTrash()
    {
        global $DB;

        $corbeille = $DB->get_record('course_categories',array('name'=>'Corbeille'));
        if(!$corbeille){
            return;
        }

        foreach($this->courseids as $id) {
            discard_course($id, $corbeille->id, false);
        }

    }

    private function moveToArchive()
    {
        global $DB;

        $selectedCategory = required_param(FilterActions::PARAM_CATEGORY_ID, PARAM_INT);
        $accessParticipant = optional_param(FilterActions::PARAM_ACCESS_PARTICIPANT, 0, PARAM_INT);

        $cat = $DB->get_record_sql('SELECT cc.id, co.path 
FROM {course_categories} cc
INNER JOIN {context} co ON co.instanceid=cc.id
WHERE co.contextlevel=40 AND cc.name=?', array('Session de formation'));

        $warningmsg = [];

        foreach($this->courseids as $id){
            $c = $DB->get_record_sql('SELECT *
FROM {course} c 
INNER JOIN {context} co ON co.instanceid=c.id
WHERE c.id=? AND co.contextlevel=50 AND co.path LIKE "'.$cat->path.'%"', array($id));

            if(!$c){
                $warningmsg[] = get_string('coursenotarchivemessage', 'local_supervision_tool', $id);
                continue;
            }

            archive_course($id, $selectedCategory, ($accessParticipant == 0 ? 'hidden' : 1), false);
        }

        if(count($warningmsg)){
            $this->msg = '<br/><br/>'.implode('<br/>', $warningmsg);
        }
    }

    private function validate()
    {
        global $DB;

        $context = context_system::instance();

        if (!has_capability('block/course_migration:removeflexpagecourse', $context))
        {
            return;
        }

        foreach($this->courseids as $id){
            $course = $DB->get_record('course', array('id'=>$id));

            if($course->format == 'flexpage'){
                continue;
            }

            if ($course->visible != 1)
            {
                $course->visible = 1;
                $DB->update_record('course', $course);
            }

            $task = $DB->get_record_sql('SELECT id,
flexcourseid,
stdcourseid,
userid,
status,
startdate,
enddate,
originalformat,
convertedformat,
keepdata,
validated 
FROM {block_course_migration} 
WHERE stdcourseid=? AND status = 1 AND validated=?', array($course->id, migrationValidation::NONE));

            if($task === false){
                continue;
            }

            $task->validated = migrationValidation::PENDING;

            $DB->update_record('block_course_migration', $task);
        }
    }



}