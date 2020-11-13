<?php

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot.'/blocks/configurable_reports/locallib.php');

class custom_reports_form extends moodleform {

    const CATEGORYID_FIELD = 'categoryid';
    const FROM_DATE = 'fromdate';
    const TO_DATE = 'todate';
    const EXPORT_TYPE = "exporttype";
    const ACTION = "action";

    // export types
    const EXPORT_CSV = 1;
    const EXPORT_XLS = 2;
    const EXPORT_ODS = 3;
    const EXPORT_TYPES = array(self::EXPORT_CSV => 'CSV', self::EXPORT_XLS => 'XLS', self::EXPORT_ODS => 'ODS');

    function definition() {
        global $COURSE, $DB, $CFG;

        $mform =& $this->_form;

        $mform->addElement('header', 'general', get_string('query_stats_2020', 'local_custom_reports'));

        $mform->addElement('hidden', self::ACTION, 'validate');
        $mform->setType(self::ACTION, PARAM_ALPHANUMEXT);


        $subcategorieslist = array_keys($DB->get_records('course_categories'));

        $courseoptions = array();
        $mform->addElement('html', '<div>'.get_string('allowed_cat', 'local_custom_reports').'</div>');


		$courseoptions[0] = get_string('filter_all', 'block_configurable_reports');
		if(!empty($subcategorieslist)){
			list($usql, $params) = $DB->get_in_or_equal($subcategorieslist);
			$subcategories = $DB->get_records_select('course_categories',"id $usql",$params,'sortorder ASC');
            $subcategories = $this->remove_unwanted_cat($subcategories);
            
			$courseoptions = $courseoptions + $this->build_categorie_tree($subcategories);
		}
		
		$mform->addElement('select', self::CATEGORYID_FIELD, get_string('subcategories', 'block_configurable_reports'), $courseoptions);
        $mform->setType(self::CATEGORYID_FIELD, PARAM_INT);

        $mform->addElement('date_selector', self::FROM_DATE, get_string('from'));
        $year = date("Y");
        $currentMonth = date("m");
        if ($currentMonth < 9) {
            $year = $year - 1;
        }

        $startDefaultDate = date('U', strtotime("$year-09-01"));
        $mform->setDefault(self::FROM_DATE, $startDefaultDate);

        $mform->addElement('date_selector', self::TO_DATE, get_string('to'));

        $year += 1;
        $endDefaultDate = date('U', strtotime("$year-08-31"));
        $mform->setDefault(self::TO_DATE, $endDefaultDate);


        // file format
        $mform->addElement('select', self::EXPORT_TYPE, get_string('format'), self::EXPORT_TYPES);
        $mform->setType(self::EXPORT_TYPE, PARAM_INT);

        $buttonarray = array();
        $buttonarray[] = $mform->createElement('cancel');
        $buttonarray[] = $mform->createElement('submit', 'submitbutton', get_string('confirm'));
        $mform->addGroup($buttonarray, 'buttonar', '', ' ', false);

        $mform->closeHeaderBefore('buttonar');

    }

    protected function build_categorie_tree($subcategory_data, $selected_value = '') {
		$courseoptions = array();
	
		foreach($subcategory_data as $subcategory){
            $str = '';
			for ($i = 1; $i < $subcategory->depth; $i++) {
				$str .= '&nbsp&nbsp';
			}
			$courseoptions[$subcategory->id] = $str.'► '.format_string($subcategory->name);
		}
		return $courseoptions;
	}

    protected function remove_unwanted_cat($categories) {
        global $DB;
        $allowed_categories_name = ['Gabarit', 'Parcours de formation', 'Session de formation', 'Archive'];

        list($usql, $params) = $DB->get_in_or_equal($allowed_categories_name);
        $allowed_parent_categories = $DB->get_records_select('course_categories',"name $usql",$params,'sortorder ASC');
        $allowed_parent_categories_id = array();
        foreach($allowed_parent_categories as $allowed_parent_cat) {
            $allowed_parent_categories_id[] = $allowed_parent_cat->id;
        }

        $allowed_cats = array();
        foreach($categories as $cat) {
            
            $ancestors = explode('/',$cat->path);
            if (count($ancestors) > 1 && in_array($ancestors[1],$allowed_parent_categories_id)  ) {
                $allowed_cats[] = $cat;
            }
        }

        return $allowed_cats;
    }
}

