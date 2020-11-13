<?php
require_once("$CFG->libdir/formslib.php");
require_once($CFG->libdir. '/coursecatlib.php');
require_once($CFG->dirroot.'/local/supervision_tool/FilterParams.php');
require_once($CFG->dirroot.'/local/supervision_tool/FilterConfig.php');
require_once($CFG->dirroot.'/local/supervision_tool/FilterActions.php');
require_once($CFG->dirroot.'/blocks/course_management/lib/block_lib.php');
require_once($CFG->dirroot.'/local/coursehub/CourseHub.php');

class supervision_form extends moodleform {

    public function definition() {
        global $USER, $PAGE, $DB;

        $PAGE->requires->js(new moodle_url('/local/supervision_tool/datepicker.js'));

        $mform = $this->_form;

        $mform->addElement('header', 'filter', get_string('filtertitle', 'local_supervision_tool'));

        $categoriesoptions = $this->get_categorie_list($USER->id);
        $categoriesoptions = array(FilterParams::ALL_CAT => get_string('all', 'core')) + $categoriesoptions;

        $mform->addElement('select', FilterParams::PARAM_NAME_CATEGORY, get_string('categorylabel', 'local_supervision_tool'), $categoriesoptions);

        $publicationtypeoptions = array(
            FilterParams::PUBLICATION_NONE => '-',
            FilterParams::PUBLICATION_ALL => get_string('all', 'core'),
            FilterParams::PUBLICATION_COURSE_OFFER => get_string('publicationcourseofferlabel', 'local_supervision_tool'),
            FilterParams::PUBLICATION_FORMATION_OFFER => get_string('publicationformationofferlabel', 'local_supervision_tool'),
            FilterParams::PUBLICATION_FORMATION_LOCAL_OFFER => get_string('publicationformationlocalofferlabel', 'local_supervision_tool')
        );
        $mform->addElement('select', FilterParams::PARAM_NAME_PUBLICATION_MODE, get_string('publicationtypelabel', 'local_supervision_tool'), $publicationtypeoptions);

        // ACA LIST
        $hub = CourseHub::instance();
        if($hub->isMaster()){
            $aca = [FilterParams::ALL_ACA => get_string('acaselectall', 'local_supervision_tool')];
            $slaves = $hub->getActiveSlaves();
            foreach($slaves as $slave){
                $aca[$slave->getIdentifiant()] = $slave->getName();
            }

            $mform->addElement('select', FilterParams::PARAM_NAME_ACA_SELECT, get_string('acaselectlabel', 'local_supervision_tool'), $aca);
            $mform->setDefault(FilterParams::PARAM_NAME_ACA_SELECT, $hub->getIdentifiant());
        }

        // COURSE VERSION
        $mform->addElement('checkbox', FilterParams::PARAM_NAME_CHECK_COURSE_VERSION, get_string('courseversionlabel', 'local_supervision_tool'));


        // STARTDATE
        $buttonarray=array();
        $datepicker = array('class' => 'datepicker');
        $buttonarray[] =& $mform->createElement('text', FilterParams::PARAM_NAME_STARTDATE_START, '', $datepicker);
        $buttonarray[] =& $mform->createElement('text', FilterParams::PARAM_NAME_STARTDATE_END, '', $datepicker);

        $mform->setType(FilterParams::PARAM_NAME_STARTDATE_START, PARAM_TEXT);
        $mform->setType(FilterParams::PARAM_NAME_STARTDATE_END, PARAM_TEXT);

        $mform->addGroup($buttonarray, 'startdategroup', get_string('startdatelabel', 'local_supervision_tool'), array(get_string('between', 'local_supervision_tool')), false);

        // ENDDATE
        $buttonarray=array();
        $buttonarray[] =& $mform->createElement('text', FilterParams::PARAM_NAME_ENDDATE_START, '', $datepicker);
        $buttonarray[] =& $mform->createElement('text', FilterParams::PARAM_NAME_ENDDATE_END, '', $datepicker);

        $mform->setType(FilterParams::PARAM_NAME_ENDDATE_START, PARAM_TEXT);
        $mform->setType(FilterParams::PARAM_NAME_ENDDATE_END, PARAM_TEXT);

        $mform->addGroup($buttonarray, 'enddategroup', get_string('enddatelabel', 'local_supervision_tool'), array(get_string('between', 'local_supervision_tool')), false);

        // TYPE
        $typeoptions = array(
            FilterParams::TYPE_NONE => get_string('all', 'core'),
            FilterParams::TYPE_FLEXPAGE => get_string('flexpagelabel', 'local_supervision_tool'),
            FilterParams::TYPE_TOPICS => get_string('topicslabel', 'local_supervision_tool'),
            FilterParams::TYPE_MODULAR => get_string('modularlabel', 'local_supervision_tool'),
            FilterParams::TYPE_VALIDATION => get_string('validationstatuslabel', 'local_supervision_tool'),
            FilterParams::TYPE_FAIL => get_string('faillabel', 'local_supervision_tool')
        );
        $mform->addElement('select', FilterParams::PARAM_NAME_TYPE, get_string('typelabel', 'local_supervision_tool'), $typeoptions);

        // DEPTH
        $depthoptions = array(
            FilterParams::DEPTH_ALL => get_string('all', 'core'),
            FilterParams::DEPTH_ONE => '1',
            FilterParams::DEPTH_TWO => '2',
            FilterParams::DEPTH_THREE => '3',
            FilterParams::DEPTH_FOUR => '4',
            FilterParams::DEPTH_FIVE_OR_MORE => '5 ou plus',

        );
        $mform->addElement('select', FilterParams::PARAM_NAME_DEPTH, get_string('depthlabel', 'local_supervision_tool'), $depthoptions);

        // LAST ACCESS
        $buttonarray=array();
        $buttonarray[] =& $mform->createElement('text', FilterParams::PARAM_NAME_LASTACCESS_START, '', $datepicker);
        $buttonarray[] =& $mform->createElement('text', FilterParams::PARAM_NAME_LASTACCESS_END, '', $datepicker);

        $mform->setType(FilterParams::PARAM_NAME_LASTACCESS_START, PARAM_TEXT);
        $mform->setType(FilterParams::PARAM_NAME_LASTACCESS_END, PARAM_TEXT);

        $mform->addGroup($buttonarray, 'lastaccessgroup', get_string('lastaccesslabel', 'local_supervision_tool'), array(get_string('between', 'local_supervision_tool')), false);

        // QUERY
        $mform->addElement('text', FilterParams::PARAM_NAME_QUERY,  get_string('querylabel', 'local_supervision_tool'));
        $mform->setType(FilterParams::PARAM_NAME_QUERY, PARAM_TEXT);

        $mform->addElement('submit', 'filteraction', get_string('filterbuttonlabel', 'local_supervision_tool'));


        // FILTER OPTIONS
        $mform->addElement('header', 'filtercfg', get_string('filterconfigtitle', 'local_supervision_tool'));

        $buttonarray=array();
        $disabled = array('disabled' => 'disabled');

        $buttonarray[] =& $mform->createElement('checkbox', FilterConfig::PARAM_NAME_IDENTIFIANT, '', get_string('cfg:idlabel','local_supervision_tool'), $disabled);
        $buttonarray[] =& $mform->createElement('checkbox', FilterConfig::PARAM_NAME_TITLE, '', get_string('cfg:coursetitlelabel','local_supervision_tool'), $disabled);
        $buttonarray[] =& $mform->createElement('checkbox', FilterConfig::PARAM_NAME_CATEGORY, '', get_string('cfg:categorylabel','local_supervision_tool'), $disabled);
        $buttonarray[] =& $mform->createElement('checkbox', FilterConfig::PARAM_NAME_TYPE, '', get_string('cfg:coursetypelabel','local_supervision_tool'), $disabled);

        $mform->addGroup($buttonarray, 'filtercfg_column1', '', array('<br/>'), false);

        $buttonarray = array();
        $buttonarray[] =& $mform->createElement('checkbox', FilterConfig::PARAM_NAME_PUBLICATION_MODE, '', get_string('cfg:publicationmodelabel','local_supervision_tool'));
        $buttonarray[] =& $mform->createElement('checkbox', FilterConfig::PARAM_NAME_PUBLICATION_DATE, '', get_string('cfg:publicationdatelabel','local_supervision_tool'));
        $buttonarray[] =& $mform->createElement('checkbox', FilterConfig::PARAM_NAME_STARTDATE, '', get_string('cfg:startdatelabel','local_supervision_tool'));
        $buttonarray[] =& $mform->createElement('checkbox', FilterConfig::PARAM_NAME_ENDDATE, '', get_string('cfg:enddatelabel','local_supervision_tool'));


        $mform->addGroup($buttonarray, 'filtercfg_column3', '', array('<br/>'), false);

        $buttonarray = array();
        // $buttonarray[] =& $mform->createElement('checkbox', FilterConfig::PARAM_NAME_MIGRATION_DATE, '', get_string('cfg:migdatelabel','local_supervision_tool'));
        $buttonarray[] =& $mform->createElement('checkbox', FilterConfig::PARAM_NAME_PAGE_COUNT, '', get_string('cfg:pagecountlabel','local_supervision_tool'));
        $buttonarray[] =& $mform->createElement('checkbox', FilterConfig::PARAM_NAME_DEPTH, '', get_string('cfg:depthlabel','local_supervision_tool'));
        $buttonarray[] =& $mform->createElement('checkbox', FilterConfig::PARAM_NAME_FORMATEUR_COUNT, '', get_string('cfg:formateurcountlabel','local_supervision_tool'));
        $buttonarray[] =& $mform->createElement('checkbox', FilterConfig::PARAM_NAME_PARTICIPANT_COUNT, '', get_string('cfg:participantcountlabel','local_supervision_tool'));
        $mform->addGroup($buttonarray, 'filtercfg_column2', '', array('<br/>'), false);

        $buttonarray = array();
        $buttonarray[] =& $mform->createElement('checkbox', FilterConfig::PARAM_NAME_LAST_ACCESS, '', get_string('cfg:lastaccesslabel','local_supervision_tool'));
        $buttonarray[] =& $mform->createElement('checkbox', FilterConfig::PARAM_NAME_COMMENT, '', get_string('cfg:commentlabel','local_supervision_tool'));
        $buttonarray[] =& $mform->createElement('checkbox', FilterConfig::PARAM_NAME_ORIGIN_ACA, '', get_string('cfg:originacalabel','local_supervision_tool'));
        $buttonarray[] =& $mform->createElement('checkbox', FilterConfig::PARAM_NAME_COURSE_VERSION, '', get_string('cfg:courseversionlabel','local_supervision_tool'));
        // $buttonarray[] =& $mform->createElement('checkbox', FilterConfig::PARAM_NAME_MIGRATION_STATUS, '', get_string('cfg:migrationstatuslabel','local_supervision_tool'));
        $mform->addGroup($buttonarray, 'filtercfg_column4', '', array('<br/>'), false);

        $buttonarray = array();
        $buttonarray[] =& $mform->createElement('checkbox', FilterConfig::PARAM_NAME_PUBLISHER, '', get_string('cfg:publisherlabel','local_supervision_tool'));
        $buttonarray[] =& $mform->createElement('checkbox', FilterConfig::PARAM_NAME_RESPONSIBLE, '', get_string('cfg:responsiblelabel','local_supervision_tool'));
        $buttonarray[] =& $mform->createElement('checkbox', FilterConfig::PARAM_NAME_ID_NUMBER, '', get_string('cfg:idnumberlabel','local_supervision_tool'));
        $buttonarray[] =& $mform->createElement('checkbox', FilterConfig::PARAM_NAME_TRAINER, '', get_string('cfg:trainerlabel','local_supervision_tool'));
        $mform->addGroup($buttonarray, 'filtercfg_column4', '', array('<br/>'), false);

        $mform->addElement('submit', 'filtersave', get_string('savefilterconfigbuttonlabel', 'local_supervision_tool'));

        // RESULT PART
        $mform->addElement('header', 'result', get_string('resulttitle', 'local_supervision_tool'));
        $mform->setExpanded('result');

        // RESET FILTER PART
        $mform->addElement('static', 'activatedfilter', get_string('activatedfilterlabel', 'local_supervision_tool'), '');

        $mform->addElement('reset', 'resetfilter', get_string('resetfilterbutton', 'local_supervision_tool'));

        // END RESET FILTER PART

        $mform->addElement('html', '<div id="filterresults"></div>');

        // ACTION PART
        $actionoptions = array(
            //FilterActions::MODULAR_MIGRATION => get_string('actiontomodularlabel', 'local_supervision_tool'),
            FilterActions::MOVE_TO => get_string('actionmovelabel', 'local_supervision_tool'),
            FilterActions::MOVE_TO_ARCHIVE => get_string('actionarchivelabel', 'local_supervision_tool'),
            //FilterActions::VALIDATION => get_string('actionvalidatelabel', 'local_supervision_tool'),
            FilterActions::MOVE_TO_TRASH => get_string('actiontrashlabel', 'local_supervision_tool'),
        );
        $buttonarray = array();
        $buttonarray[] =& $mform->createElement('select', FilterActions::PARAM_ACTION, '', $actionoptions);
        $buttonarray[] =& $mform->createElement('submit', 'actionbutton', get_string('ok', 'core'), ['disabled' => '']);

        $buttonarray[] =& $mform->createElement('button', 'csvbutton', get_string('exportcsvbutton', 'local_supervision_tool'));

        $mform->addGroup($buttonarray, 'action_group', get_string('actionlabel', 'local_supervision_tool'), array(''), false);

        // popup archive
        $cat = $DB->get_record('course_categories', array('name' => 'Archive'));
        $subcat = destination_subcategory_tree($cat->id);
        $subcat = (is_array($subcat) ? $subcat : array());

        $mform->addElement('html', '<div id="dialog_archive" style="display:none;">');
        $archiveoptions = array($cat->id => $cat->name);

        foreach($subcat as $cat){
            $archiveoptions[$cat['id']] = str_repeat('&nbsp;', ($cat['depth']-1)*2).$cat['name'];
        }

        $mform->addElement('select', FilterActions::PARAM_CATEGORY_ID, 'Catégorie', $archiveoptions);
        $mform->addElement('checkbox', FilterActions::PARAM_ACCESS_PARTICIPANT, 'Autoriser l\'accès aux participants');

        $mform->addElement('html', '</div>');
        // end popup archive

        // popup moveTo

        $displaylist = [];
        $archive = $DB->get_record('course_categories', array('name' => 'Archive'));
        $trash = $DB->get_record('course_categories', array('name' => 'Corbeille'));
        $cats = $DB->get_records('course_categories', array('parent' => 0));
        foreach ($cats as $cat) {
            if($cat->id != $archive->id && $cat->id != $trash->id) {
                $subcat = destination_subcategory_tree($cat->id);
                $subcat = (is_array($subcat) ? $subcat : array());
                $displaylist[$cat->id] = $cat->name;
                foreach ($subcat as $cat) {
                    $displaylist[$cat['id']] = str_repeat('&nbsp;', ($cat['depth']-1)*2) . $cat['name'];
                }
            }
        }
        $mform->addElement('html', '<div id="dialog_moveTo" style="display:none;">');
        $mform->addElement('select', FilterActions::PARAM_CATEGORY_ID, 'Catégorie', $displaylist);

        $mform->addElement('html', '</div>');
        // end popup moveTo

        // popup corbeille
        $mform->addElement('html', '<div id="dialog_trash" style="display:none;">');

        $mform->addElement('html', '<p>Le parcours va être déplacé dans la corbeille, celui-ci sera supprimé définitivement dans 6 mois.</p>');
        $mform->addElement('html', '<p class="published">Si ce parcours est partagé sur l’offre de parcours ou de formation, la mise à la corbeille induira la suppression de cette publication.</p>');
        $mform->addElement('html', '<p>Souhaitez-vous continuer ?</p>');

        $mform->addElement('html', '</div>');
        // fin popup corbeille

        // popup migration
        $mform->addElement('html', '<div id="dialog_migration" style="display:none;">');

        $mform->addElement('html', '<p><span class="ui-icon ui-icon-alert" style="float:left; margin:12px 12px 20px 0;"></span>La conversion du parcours peut prendre quelques minutes. Vous serez informé par mail lorsque la conversion sera terminée.</p>');

        $buttonarray = array();
        $buttonarray[] =& $mform->createElement('checkbox', FilterActions::PARAM_KEEP_DATA, '', 'Conserver les utilisateurs et leurs contributions');
        $mform->addGroup($buttonarray, 'keepdatagroup', '', '', false);

        $mform->addElement('html', '<p><br/>Le parcours sera caché aux utilisateurs tant que vous ne validez pas la migration</p>');

        $mform->addElement('html', '</div>');
        // fin popup migration

        // popup validation
        $mform->addElement('html', '<div id="dialog_validation" style="display:none;">');

        $mform->addElement('html', '<p><span class="ui-icon ui-icon-alert" style="float:left; margin:12px 12px 20px 0;"></span>Attention : La validation de ces parcours entrainera la suppression des parcours originaux !<br/>Cette action est irréversible et peut prendre plusieurs minutes !</p>');

        $mform->addElement('html', '</div>');
        // fin popup validation

        $mform->addElement('hidden', FilterActions::PARAM_COURSEIDS);
        $mform->setType(FilterActions::PARAM_COURSEIDS, PARAM_RAW_TRIMMED);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->disable_form_change_checker();
    }

    function definition_after_data()
    {
        global $DB;

        parent::definition_after_data(); // TODO: Change the autogenerated stub

        $mform = $this->_form;

        // THE FIRST FOUR COLUMN ARE MANDATORY
        $elm = $mform->getElement('filtercfg_column1')->getElements();

        $elm[0]->setChecked(true);
        $elm[1]->setChecked(true);
        $elm[2]->setChecked(true);
        $elm[3]->setChecked(true);


        $o = optional_param(FilterParams::PARAM_NAME_TYPE, null, PARAM_INT);
        if($o){
            $mform->setDefault(FilterParams::PARAM_NAME_TYPE, $o);
        }

        // SHOW ALL ACTIVATED FILTERS
        $enabledFilter = array();

        $category = $mform->getElementValue(FilterParams::PARAM_NAME_CATEGORY);
        if($category && ($catid = $category[0]) > 0){
            $cat = $DB->get_record('course_categories', array('id' => $catid));
            $enabledFilter[] = get_string('categorylabel', 'local_supervision_tool') . ' ' . $cat->name;
        }

        $publicationMode = $mform->getElementValue(FilterParams::PARAM_NAME_PUBLICATION_MODE);
        if($publicationMode){
            if($publicationMode == FilterParams::PUBLICATION_FORMATION_OFFER){
                $enabledFilter[] = get_string('publicationtypelabel', 'local_supervision_tool') . ' ' .  get_string('publicationformationofferlabel', 'local_supervision_tool');
            }

            if($publicationMode == FilterParams::PUBLICATION_FORMATION_LOCAL_OFFER){
                $enabledFilter[] = get_string('publicationtypelabel', 'local_supervision_tool') . ' ' .  get_string('publicationformationlocalofferlabel', 'local_supervision_tool');
            }

            if($publicationMode == FilterParams::PUBLICATION_COURSE_OFFER){
                $enabledFilter[] = get_string('publicationtypelabel', 'local_supervision_tool') . ' ' .  get_string('publicationcourseofferlabel', 'local_supervision_tool');
            }
        }

        $hub = CourseHub::instance();

        if($hub->isMaster()){
            $acaselect = $mform->getElementValue(FilterParams::PARAM_NAME_ACA_SELECT);

            if($acaselect[0] !== null && $acaselect[0] != '-1'){
                $elm = $mform->getElement(FilterParams::PARAM_NAME_ACA_SELECT);
                $label = '';
                foreach($elm->_options as $option){
                    if($option['attr']['value'] == $acaselect[0]){
                        $label = $option['text'];
                        break;
                    }
                }

                $enabledFilter[] = get_string('originacafilterlabel', 'local_supervision_tool', $label);
            }
        }



        $checkvourseversion = $mform->getElementValue(FilterParams::PARAM_NAME_CHECK_COURSE_VERSION);
        if($checkvourseversion){
            $enabledFilter[] = get_string('courseversionlabel', 'local_supervision_tool');
        }

        $startdategroup = $mform->getElement('startdategroup');
        if($startdategroup){
            $startdate = $startdategroup->getElements();

            $s = '';
            if($startdate[0]->getValue()){
                $s .= get_string('startdatelabel', 'local_supervision_tool') . ' ' . $startdate[0]->getValue();
            }

            if($startdate[1]->getValue()){
                $s .= get_string('between', 'local_supervision_tool') . $startdate[1]->getValue();
            }

            if($s){
                $enabledFilter[] = $s;
            }

        }

        $enddategroup = $mform->getElement('enddategroup');
        if($enddategroup){
            $enddate = $enddategroup->getElements();

            $s = '';
            if($enddate[0]->getValue()){
                $s .= get_string('enddatelabel', 'local_supervision_tool') . ' ' . $enddate[0]->getValue();
            }

            if($enddate[1]->getValue()){
                $s .= get_string('between', 'local_supervision_tool') . $enddate[1]->getValue();
            }

            if($s){
                $enabledFilter[] = $s;
            }

        }

        $type = $mform->getElementValue(FilterParams::PARAM_NAME_TYPE);

        if($type){
            $t = intval($type[0]);

            $label = '';
            switch($t){
                case FilterParams::TYPE_FAIL:
                    $label = get_string('faillabel', 'local_supervision_tool');
                    break;
                case FilterParams::TYPE_VALIDATION:
                    $label = get_string('validationstatuslabel', 'local_supervision_tool');
                    break;
                case FilterParams::TYPE_MODULAR:
                    $label = get_string('modularlabel', 'local_supervision_tool');
                    break;
                case FilterParams::TYPE_FLEXPAGE:
                    $label = get_string('flexpagelabel', 'local_supervision_tool');
                    break;
                case FilterParams::TYPE_TOPICS:
                    $label = get_string('topicslabel', 'local_supervision_tool');
                    break;
            }

            if($label){
                $enabledFilter[] = get_string('typelabel', 'local_supervision_tool') . ' ' . $label;
            }
        }

        $depth = $mform->getElementValue(FilterParams::PARAM_NAME_DEPTH);
        if($depth){
            $d = intval($depth[0]);

            $label = '';
            switch($d){
                case FilterParams::DEPTH_ONE:
                    $label = 1;
                    break;
                case FilterParams::DEPTH_TWO:
                    $label = 2;
                    break;
                case FilterParams::DEPTH_THREE:
                    $label = 3;
                    break;
                case FilterParams::DEPTH_FOUR:
                    $label = 4;
                    break;
                case FilterParams::DEPTH_FIVE_OR_MORE:
                    $label = '5 ou plus';
                    break;
            }

            if($label){
                $enabledFilter[] = get_string('depthlabel', 'local_supervision_tool') . ' ' . $label;
            }
        }

        $lastaccessgroup = $mform->getElement('lastaccessgroup');
        if($lastaccessgroup){
            $lastaccess = $lastaccessgroup->getElements();

            $s = '';
            if($lastaccess[0]->getValue()){
                $s .= get_string('lastaccesslabel', 'local_supervision_tool') . ' ' . $lastaccess[0]->getValue();
            }

            if($lastaccess[1]->getValue()){
                $s .= get_string('between', 'local_supervision_tool') . $lastaccess[1]->getValue();
            }

            if($s){
                $enabledFilter[] = $s;
            }

        }

        $query = $mform->getElement(FilterParams::PARAM_NAME_QUERY);
        if($query){
            $q = $query->getValue();

            if($q){
                $enabledFilter[] = get_string('querylabel', 'local_supervision_tool') . ' "' . $q . '"';
            }

        }

        if($enabledFilter){
            $mform->getElement('activatedfilter')->setText(implode('<br/>', $enabledFilter));
        }else{
            $mform->removeElement('activatedfilter');
            $mform->removeElement('resetfilter');
        }

    }

    function get_data() {
        $data = parent::get_data();

        if(!$data){
            return $data;
        }

        $dataToProcess = array(
            FilterParams::PARAM_NAME_STARTDATE_START,
            FilterParams::PARAM_NAME_STARTDATE_END,
            FilterParams::PARAM_NAME_ENDDATE_START,
            FilterParams::PARAM_NAME_ENDDATE_END,
            FilterParams::PARAM_NAME_LASTACCESS_START,
            FilterParams::PARAM_NAME_LASTACCESS_END
        );

        foreach($dataToProcess as $d){
            if(isset($data->{$d})){
                $data->{$d} = trim($data->{$d});

                if(strlen($data->{$d}) == 0){
                    $data->{$d} = 0;
                    continue;
                }

                $date = explode('/', $data->{$d});
                $data->{$d} = mktime(0, 0, 0, $date[1], $date[0], $date[2]);
            }
        }

        return $data;
    }

    private function get_categorie_list($userid)
    {
        global $DB;

        if(has_capability('local/supervision_tool:viewallcourses', context_system::instance())){
            return coursecat::make_categories_list();
        }

        // else check to find any 'gestionnaire' or 'formateur' roles
        $categories = $DB->get_records_sql('
SELECT DISTINCT categories.id, categories.name, categories.parent
FROM (
	SELECT cc.id, cc.name, cc.parent, cc.sortorder
	FROM {role_assignments} ra
	INNER JOIN {context} co ON co.id=ra.contextid
	INNER JOIN {course_categories} cc ON cc.id=co.instanceid
	WHERE ra.userid=?
	AND ra.roleid IN (SELECT id FROM {role} WHERE shortname = "formateur" OR shortname = "gestionnaire")
	AND co.contextlevel = 40
UNION
	SELECT cc.id, cc.name, cc.parent, cc.sortorder
	FROM {role_assignments} ra
	INNER JOIN {context} co ON co.id=ra.contextid
	INNER JOIN {course} c ON c.id=co.instanceid
	INNER JOIN {course_categories} cc ON cc.id=c.category
	WHERE ra.userid=? 
	AND ra.roleid IN (SELECT id FROM {role} WHERE shortname = "formateur" OR shortname = "gestionnaire")
	AND co.contextlevel = 50
) categories
ORDER BY categories.sortorder ASC', array($userid, $userid));

        if(count($categories) == 0){
            return '';
        }

        $parents = $DB->get_records('course_categories', array(), 'id,name,parent');
        $cat = array();

        foreach($categories as $category)
        {
            if($category->parent == 0){
                $cat[$category->id] = $category->name;
                continue;
            }

            $pid = $category->parent;
            while($pid > 0){
                $category->name = $parents[$pid]->name . '&nbsp;/&nbsp;' . $category->name;
                $pid = $parents[$pid]->parent;
            }

            $cat[$category->id] = $category->name;
        }

        return $cat;
    }
}
