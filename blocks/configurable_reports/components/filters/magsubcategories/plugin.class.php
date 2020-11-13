<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/** Configurable Reports
  * A Moodle block for creating customizable reports
  * @package blocks
  * @author: Juan leyva <http://www.twitter.com/jleyvadelgado>
  * @date: 2009
  */

require_once($CFG->dirroot.'/blocks/configurable_reports/plugin.class.php');

class plugin_magsubcategories extends plugin_base{

	function init(){
		$this->form = false;
		$this->unique = true;
		$this->fullname = get_string('filtermagsubcategories','block_configurable_reports');
		$this->reporttypes = array('categories','sql');
	}

	function summary($data){
		return get_string('filtermagsubcategories_summary','block_configurable_reports');
	}

	function execute($finalelements, $data){

		$filter_subcategories = optional_param('filter_magsubcategories',0,PARAM_INT);
		if(!$filter_subcategories)
			return $finalelements;

		if ($this->report->type != 'sql') {
            return array($filter_subcategories);
		} else {
			if (preg_match("/%%FILTER_MAG_SUBCATEGORIES:([^%]+):([^%]+)%%/i",$finalelements, $output)) {
				$replace = ' AND ('.$output[2].' LIKE CONCAT( \'%/\', '.$filter_subcategories.', \'/%\') OR '.$output[1].' = '.$filter_subcategories.') ';
				// %%FILTER_MAG_SUBCATEGORIES:mdl_course_category.id:mdl_course_category.path%%
				$query = str_replace('%%FILTER_MAG_SUBCATEGORIES:'.$output[1].':'.$output[2].'%%', $replace, $finalelements);				
				return $query;
			}
		}
		
		return $finalelements;
	}

	function print_filter(&$mform){
		global $remotedb, $CFG;

		$filter_subcategories = optional_param('filter_magsubcategories',0,PARAM_INT);

		$reportclassname = 'report_'.$this->report->type;
		$reportclass = new $reportclassname($this->report);

		if($this->report->type != 'sql'){
			$components = cr_unserialize($this->report->components);
			$conditions = $components['conditions'];

			$subcategorieslist = $reportclass->elements_by_conditions($conditions);
		}
		else{
			$subcategorieslist = array_keys($remotedb->get_records('course_categories'));
		}
		
		$courseoptions = array();
		
		$courseoptions[0] = get_string('filter_all', 'block_configurable_reports');

		if(!empty($subcategorieslist)){
			list($usql, $params) = $remotedb->get_in_or_equal($subcategorieslist);
			$subcategories = $remotedb->get_records_select('course_categories',"id $usql",$params,'sortorder ASC');
			
			$courseoptions = $courseoptions + $this->build_categorie_tree($subcategories);
		}
		
		$mform->addElement('select', 'filter_magsubcategories', get_string('category'), $courseoptions);
		$mform->setType('filter_magsubcategories', PARAM_INT);

	}
	
	function build_categorie_tree($subcategory_data, $selected_value = '') {
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

}

