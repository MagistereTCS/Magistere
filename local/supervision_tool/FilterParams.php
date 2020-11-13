<?php

class FilterParams {
    public $categoryid;
    public $publicationMode;
    public $startDate;
    public $endDate;
    public $type;
    public $depth;
    public $lastAccess;
    public $query;
    public $acaselect;
    public $checkcourseversion;
    public $startIndex;
    public $pageSize;
    public $sortOrder;

    const PARAM_NAME_CATEGORY= 'cid';
    const PARAM_NAME_PUBLICATION_MODE = 'pm';
    const PARAM_NAME_STARTDATE_START = 'ss';
    const PARAM_NAME_STARTDATE_END = 'se';
    const PARAM_NAME_ENDDATE_START = 'es';
    const PARAM_NAME_ENDDATE_END = 'ee';
    const PARAM_NAME_LASTACCESS_START = 'ls';
    const PARAM_NAME_LASTACCESS_END = 'le';
    const PARAM_NAME_TYPE = 't';
    const PARAM_NAME_DEPTH = 'd';
    const PARAM_NAME_QUERY = 'q';
    const PARAM_NAME_ACA_SELECT = 'as';
    const PARAM_NAME_CHECK_COURSE_VERSION = 'ccv';
    const PARAM_NAME_START_INDEX = 'si';
    const PARAM_NAME_PAGE_SIZE = 'ps';
    const PARAM_NAME_SORT_ORDER = 'so';

    const PUBLICATION_ALL = -1;
    const PUBLICATION_COURSE_OFFER = 1;
    const PUBLICATION_FORMATION_OFFER = 0;
    const PUBLICATION_FORMATION_LOCAL_OFFER = 3;
    const PUBLICATION_NONE = 2;

    const TYPE_NONE = 0;
    const TYPE_MODULAR = 1;
    const TYPE_VALIDATION = 2;
    const TYPE_FAIL = 3;
    const TYPE_FLEXPAGE = 4;
    const TYPE_TOPICS = 5;

    const DEPTH_ALL = 0;
    const DEPTH_ONE = 1;
    const DEPTH_TWO = 2;
    const DEPTH_THREE = 3;
    const DEPTH_FOUR = 4;
    const DEPTH_FIVE_OR_MORE = 5;

    const ALL_CAT = 0;
    const ALL_ACA = -1;

    public function __construct()
    {
        $this->startDate = new stdClass();
        $this->startDate->start = null;
        $this->startDate->end = null;

        $this->endDate = new stdClass();
        $this->endDate->start = null;
        $this->endDate->end = null;

        $this->lastAccess = new stdClass();
        $this->lastAccess->start = null;
        $this->lastAccess->end = null;

        $this->acaselect = null;
        $this->checkcourseversion = null;

        $this->startIndex = null;
        $this->pageSize = null;
        $this->sortOrder = null;
    }

    public function loadFromUrl()
    {
        $this->categoryid = optional_param(self::PARAM_NAME_CATEGORY, null, PARAM_INT);

        $this->publicationMode = optional_param(self::PARAM_NAME_PUBLICATION_MODE, null, PARAM_INT);
        if($this->publicationMode !== null &&
            !in_array($this->publicationMode, array(self::PUBLICATION_ALL, self::PUBLICATION_COURSE_OFFER, self::PUBLICATION_FORMATION_OFFER, self::PUBLICATION_FORMATION_LOCAL_OFFER, self::PUBLICATION_NONE))){
            $this->publicationMode = null;
        }

        $this->startDate->start = optional_param(self::PARAM_NAME_STARTDATE_START, null, PARAM_TEXT);
        $this->startDate->end = optional_param(self::PARAM_NAME_STARTDATE_END, null, PARAM_TEXT);

        if($this->startDate->start){
            $date = explode('/', $this->startDate->start);
            $this->startDate->start = mktime(0, 0, 0, $date[1], $date[0], $date[2]);
        }

        if($this->startDate->end){
            $date = explode('/', $this->startDate->end);
            $this->startDate->end = mktime(0, 0, 0, $date[1], $date[0], $date[2]);
        }

        $this->endDate->start = optional_param(self::PARAM_NAME_ENDDATE_START, null, PARAM_TEXT);
        $this->endDate->end = optional_param(self::PARAM_NAME_ENDDATE_END, null, PARAM_TEXT);

        if($this->endDate->start){
            $date = explode('/', $this->endDate->start);
            $this->startDate->start = mktime(0, 0, 0, $date[1], $date[0], $date[2]);
        }

        if($this->endDate->end){
            $date = explode('/', $this->endDate->end);
            $this->endDate->end = mktime(0, 0, 0, $date[1], $date[0], $date[2]);
        }

        $this->lastAccess->start = optional_param(self::PARAM_NAME_LASTACCESS_START, null, PARAM_TEXT);
        $this->lastAccess->end = optional_param(self::PARAM_NAME_LASTACCESS_END, null, PARAM_TEXT);

        if($this->lastAccess->start){
            $date = explode('/', $this->lastAccess->start);
            $this->lastAccess->start = mktime(0, 0, 0, $date[1], $date[0], $date[2]);
        }

        if($this->lastAccess->end){
            $date = explode('/', $this->lastAccess->end);
            $this->lastAccess->end = mktime(0, 0, 0, $date[1], $date[0], $date[2]);
        }

        $this->startDate->start = $this->startDate->start ? $this->startDate->start : null;
        $this->startDate->end = $this->startDate->end ? $this->startDate->end : null;

        $this->endDate->start = $this->endDate->start ? $this->endDate->start : null;
        $this->endDate->end = $this->endDate->end ? $this->endDate->end : null;

        $this->lastAccess->start = $this->lastAccess->start ? $this->lastAccess->start : null;
        $this->lastAccess->end = $this->lastAccess->end ? $this->lastAccess->end : null;

        $this->type = optional_param(self::PARAM_NAME_TYPE, null, PARAM_INT);
        if($this->type !== null &&
            !in_array($this->type, array(self::TYPE_NONE, self::TYPE_MODULAR, self::TYPE_VALIDATION, self::TYPE_FAIL, self::TYPE_FLEXPAGE, self::TYPE_TOPICS))){
            $this->type = null;
        }

        $this->depth = optional_param(self::PARAM_NAME_DEPTH, null, PARAM_INT);

        $this->query = optional_param(self::PARAM_NAME_QUERY, null, PARAM_TEXT);
        if($this->query !== null){
            $this->query = trim($this->query);
            if(empty($this->query)){
                $this->query = null;
            }
        }

        $this->acaselect = optional_param(self::PARAM_NAME_ACA_SELECT, null, PARAM_TEXT);
        $this->checkcourseversion = optional_param(self::PARAM_NAME_CHECK_COURSE_VERSION, null, PARAM_INT);

        $this->startIndex = optional_param(self::PARAM_NAME_START_INDEX, null, PARAM_INT);
        $this->pageSize = optional_param(self::PARAM_NAME_PAGE_SIZE, null, PARAM_INT);
        $this->sortOrder = optional_param(self::PARAM_NAME_SORT_ORDER, null, PARAM_TEXT);
    }

    public function serialize()
    {
        $result = array();
        foreach($this->getParameters() as $name => $value){
            $result[] = $name.'='.$value;
        }

        return implode('&', $result);
    }

    public function getParameters()
    {
        $param = array();

        if($this->categoryid !== null){
            $param[self::PARAM_NAME_CATEGORY] = $this->categoryid;
        }

        if($this->publicationMode !== null){
            $param[self::PARAM_NAME_PUBLICATION_MODE] = $this->publicationMode;
        }

        if($this->startDate->start !== null){
            $param[self::PARAM_NAME_STARTDATE_START] = $this->startDate->start;
        }

        if($this->startDate->end !== null){
            $param[self::PARAM_NAME_STARTDATE_END] = $this->startDate->end;
        }

        if($this->endDate->start !== null){
            $param[self::PARAM_NAME_ENDDATE_START] = $this->endDate->start;
        }

        if($this->endDate->end !== null){
            $param[self::PARAM_NAME_ENDDATE_END] = $this->endDate->end;
        }

        if($this->lastAccess->start !== null){
            $param[self::PARAM_NAME_LASTACCESS_START] = $this->lastAccess->start;
        }

        if($this->lastAccess->end !== null){
            $param[self::PARAM_NAME_LASTACCESS_END] = $this->lastAccess->end;
        }

        if($this->type !== null){
            $param[self::PARAM_NAME_TYPE] = $this->type;
        }

        if($this->depth !== null){
            $param[self::PARAM_NAME_DEPTH] = $this->depth;
        }

        if($this->query !== null){
            $param[self::PARAM_NAME_QUERY] = $this->query;
        }

        if($this->checkcourseversion !== null){
            $param[self::PARAM_NAME_CHECK_COURSE_VERSION] = $this->checkcourseversion;
        }

        if($this->acaselect !== null){
            $param[self::PARAM_NAME_ACA_SELECT] = $this->acaselect;
        }

        if($this->startIndex !== null){
            $param[self::PARAM_NAME_START_INDEX] = $this->startIndex;
        }

        if($this->pageSize !== null){
            $param[self::PARAM_NAME_PAGE_SIZE] = $this->pageSize;
        }

        if($this->sortOrder !== null){
            $param[self::PARAM_NAME_SORT_ORDER] = $this->sortOrder;
        }

        return $param;
    }
}