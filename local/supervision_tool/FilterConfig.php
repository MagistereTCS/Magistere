<?php

class FilterConfig {
    public $publicationType;
    public $publicationDate;
    public $startDate;
    public $endDate;
    public $migrationDate;

    public $pageCount;
    public $depth;

    public $formateurCount;
    public $participantCount;

    public $lastAccess;
    public $comment;

    public $migrationstatus;

    public $courseversion;

    public $originaca;
    public $publisher;
    public $idnumber;
    public $responsible;
    public $trainer;

    // The next four constant are use only for read-only checkbox
    const PARAM_NAME_IDENTIFIANT = 'cfg_id';
    const PARAM_NAME_TITLE = 'cfg_title';
    const PARAM_NAME_CATEGORY = 'cfg_category';
    const PARAM_NAME_TYPE = 'cfg_type';


    const PARAM_NAME_PUBLICATION_MODE = 'cfg_pt';
    const PARAM_NAME_PUBLICATION_DATE = 'cfg_pd';
    const PARAM_NAME_STARTDATE = 'cfg_sd';
    const PARAM_NAME_ENDDATE = 'cfg_ed';
    const PARAM_NAME_MIGRATION_DATE = 'cfg_md';
    const PARAM_NAME_PAGE_COUNT = 'cfg_pc';
    const PARAM_NAME_DEPTH = 'cfg_d';
    const PARAM_NAME_FORMATEUR_COUNT = 'cfg_fc';
    const PARAM_NAME_PARTICIPANT_COUNT = 'cfg_prc';
    const PARAM_NAME_LAST_ACCESS = 'cfg_la';
    const PARAM_NAME_COMMENT = 'cfg_cm';
    const PARAM_NAME_MIGRATION_STATUS = 'cfg_ms';
    const PARAM_NAME_COURSE_VERSION = 'cfg_cv';
    const PARAM_NAME_ORIGIN_ACA = 'cfg_oa';
    const PARAM_NAME_PUBLISHER = 'cfg_pub';
    const PARAM_NAME_ID_NUMBER = 'cfg_in';
    const PARAM_NAME_RESPONSIBLE = 'cfg_r';
    const PARAM_NAME_TRAINER = 'cfg_tr';

    function __construct()
    {
        $this->initValue();
    }

    function save($userid)
    {
        global $DB;

        $data = $DB->get_record('local_supervision_filter_cfg', array('userid' => $userid));

        if($data == null){
            $data = new stdClass();
            $data->userid = $userid;
        }

        $data->publicationtype = $this->publicationType;
        $data->publicationdate = $this->publicationDate;
        $data->startdate = $this->startDate;
        $data->enddate = $this->endDate;
        $data->migrationdate = $this->migrationDate;
        $data->pagecount = $this->pageCount;
        $data->depth = $this->depth;
        $data->formateurcount = $this->formateurCount;
        $data->participantcount = $this->participantCount;
        $data->lastaccess = $this->lastAccess;
        $data->comment = $this->comment;
        $data->migrationstatus = $this->migrationstatus;
        $data->courseversion = $this->courseversion;
        $data->originaca = $this->originaca;
        $data->publisher = $this->publisher;
        $data->idnumber = $this->idnumber;
        $data->responsible = $this->responsible;
        $data->trainer = $this->trainer;
        
        if(isset($data->id)){
            $DB->update_record('local_supervision_filter_cfg', $data);
        }else{
            $DB->insert_record('local_supervision_filter_cfg', $data);
        }

    }

    function load($userid)
    {
        global $DB;

        $data = $DB->get_record('local_supervision_filter_cfg', array('userid' => $userid));
        if(!$data){
            $this->initValue();
            return;
        }

        $this->publicationType = $data->publicationtype;
        $this->publicationDate = $data->publicationdate;
        $this->startDate = $data->startdate;
        $this->endDate = $data->enddate;
        $this->migrationDate = $data->migrationdate;
        $this->pageCount = $data->pagecount;
        $this->depth = $data->depth;
        $this->formateurCount = $data->formateurcount;
        $this->participantCount = $data->participantcount;
        $this->lastAccess = $data->lastaccess;
        $this->comment = $data->comment;
        $this->migrationstatus = $data->migrationstatus;
        $this->courseversion = $data->courseversion;
        $this->originaca = $data->originaca;
        $this->publisher = $data->publisher;
        $this->idnumber = $data->idnumber;
        $this->responsible = $data->responsible;
        $this->trainer = $data->trainer;
    }

    function loadFromForm()
    {
        $this->initValue();

        if(optional_param(self::PARAM_NAME_PUBLICATION_MODE, null, PARAM_INT)){
            $this->publicationType = 1;
        }

        if(optional_param(self::PARAM_NAME_DEPTH, null, PARAM_INT)){
            $this->depth = 1;
        }

        if(optional_param(self::PARAM_NAME_COMMENT, null, PARAM_INT)){
            $this->comment = 1;
        }

        if(optional_param(self::PARAM_NAME_STARTDATE, null, PARAM_INT)){
            $this->startDate = 1;
        }

        if(optional_param(self::PARAM_NAME_ENDDATE, null, PARAM_INT)){
            $this->endDate = 1;
        }

        if(optional_param(self::PARAM_NAME_MIGRATION_DATE, null, PARAM_INT)){
            $this->migrationDate = 1;
        }

        if(optional_param(self::PARAM_NAME_PAGE_COUNT, null, PARAM_INT)){
            $this->pageCount = 1;
        }

        if(optional_param(self::PARAM_NAME_FORMATEUR_COUNT, null, PARAM_INT)){
            $this->formateurCount = 1;
        }

        if(optional_param(self::PARAM_NAME_PARTICIPANT_COUNT, null, PARAM_INT)){
            $this->participantCount = 1;
        }

        if(optional_param(self::PARAM_NAME_LAST_ACCESS, null, PARAM_INT)){
            $this->lastAccess = 1;
        }

        if(optional_param(self::PARAM_NAME_PUBLICATION_DATE, null, PARAM_INT)){
            $this->publicationDate = 1;
        }

        if(optional_param(self::PARAM_NAME_MIGRATION_STATUS, null, PARAM_INT)){
            $this->migrationstatus = 1;
        }

        if(optional_param(self::PARAM_NAME_COURSE_VERSION, null, PARAM_INT)){
            $this->courseversion = 1;
        }

        if(optional_param(self::PARAM_NAME_ORIGIN_ACA, null, PARAM_INT)){
            $this->originaca = 1;
        }

        if(optional_param(self::PARAM_NAME_PUBLISHER, null, PARAM_INT)){
            $this->publisher = 1;
        }

        if(optional_param(self::PARAM_NAME_ID_NUMBER, null, PARAM_INT)){
            $this->idnumber = 1;
        }

        if(optional_param(self::PARAM_NAME_RESPONSIBLE, null, PARAM_INT)){
            $this->responsible = 1;
        }
        
        if(optional_param(self::PARAM_NAME_TRAINER, null, PARAM_INT)){
            $this->trainer = 1;
        }
    }

    function getValueForForm($userid)
    {
        $this->load($userid);

        return array(
            self::PARAM_NAME_PUBLICATION_MODE => $this->publicationType,
            self::PARAM_NAME_DEPTH => $this->depth,
            self::PARAM_NAME_COMMENT => $this->comment,
            self::PARAM_NAME_STARTDATE => $this->startDate,
            self::PARAM_NAME_ENDDATE => $this->endDate,
            self::PARAM_NAME_MIGRATION_DATE => $this->migrationDate,
            self::PARAM_NAME_PAGE_COUNT => $this->pageCount,
            self::PARAM_NAME_FORMATEUR_COUNT => $this->formateurCount,
            self::PARAM_NAME_PARTICIPANT_COUNT => $this->participantCount,
            self::PARAM_NAME_LAST_ACCESS => $this->lastAccess,
            self::PARAM_NAME_PUBLICATION_DATE => $this->publicationDate,
            self::PARAM_NAME_MIGRATION_STATUS => $this->migrationstatus,
            self::PARAM_NAME_COURSE_VERSION => $this->courseversion,
            self::PARAM_NAME_ORIGIN_ACA => $this->originaca,
            self::PARAM_NAME_PUBLISHER => $this->publisher,
            self::PARAM_NAME_ID_NUMBER => $this->idnumber ,
            self::PARAM_NAME_RESPONSIBLE => $this->responsible,
            self::PARAM_NAME_TRAINER => $this->trainer,
        );
    }

    private function initValue()
    {
        $this->publicationType = 0;
        $this->publicationDate = 0;
        $this->startDate = 0;
        $this->endDate = 0;
        $this->migrationDate = 0;
        $this->pageCount = 0;
        $this->depth = 0;
        $this->formateurCount = 0;
        $this->participantCount = 0;
        $this->lastAccess = 0;
        $this->comment = 0;
        $this->migrationstatus = 0;
        $this->courseversion = 0;
        $this->originaca = 0;
        $this->publisher = 0;
        $this->idnumber = 0;
        $this->responsible = 0;
        $this->trainer = 0;
    }

}