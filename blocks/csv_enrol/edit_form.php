<?php

//  BRIGHTALLY CUSTOM CODE
//  Coder: Ted vd Brink
//  Contact: ted.vandenbrink@brightalley.nl
//  Date: 6 juni 2012
//
//  Description: Enrols users into a course by allowing a user to upload an csv file with only email adresses
//  Using this block allows you to use CSV files with only emailaddress
//  After running the upload you can download a txt file that contains a log of the enrolled and failed users.

//  License: GNU General Public License http://www.gnu.org/copyleft/gpl.html

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");

class block_csv_enrol_form extends moodleform {
    function definition() {
    	global $CFG;
        $mform = $this->_form;

        $data    = $this->_customdata['data'];
        $options = $this->_customdata['options'];
        
        $mform->addElement('hidden', 'id', $data->courseid);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'iid', $data->courseid);
        $mform->setType('iid', PARAM_INT);
        $mform->addElement('hidden', 'type', "");
        $mform->setType('type', PARAM_ALPHA);
        
        $mform->addElement('html','<div id="page-blocks-csv-enrol-warning"></div>');

        $mform->addElement('static', 'name', "", get_string('description', 'block_csv_enrol', $data->coursename));
        
    	$mform->addElement('filepicker', 'userfile', get_string('uploadcsv','block_csv_enrol'), null, array('accepted_types' => '*.csv'));

        $mform->addElement('filemanager', 'files_filemanager', get_string('resultfiles','block_csv_enrol'), null, $options);
        $mform->addElement('html','<style>#page-blocks-csv_enrol-edit .fp-btn-add, #page-blocks-csv_enrol-edit .fp-btn-mkdir { display: none; }</style>');
        
        // Evolution Mantis 1233
        $mform->addElement('html','<div class="panel panel-complex" style="display: none;">');
        $groups = groups_get_all_groups($data->courseid);
        $groups_for_select = array();
        if($groups){
        	foreach($groups as $group){
        		$groups_for_select[$group->id] = $group->name;
        	}
        }
        
        $roles = array(
        	'participant' => 'Participant',
        	'formateur' => 'Formateur',
        	'tuteur' => 'Tuteur'
        );
        
        $select = $mform->addElement('select', 'groups', get_string('group'), $groups_for_select);
        $select->setMultiple(true);
        
        $mform->addElement('select', 'role', get_string('role'), $roles);
        
        $mform->addElement('html','</div>');
        
        $mform->addElement('checkbox', 'delete_users', get_string('deleteusers', 'block_csv_enrol'));
        
        $this->add_action_buttons(true, get_string('savechanges'));

        $this->set_data($data);
    }
}

class block_csv_enrol_form_previsual extends moodleform {
	function definition() {
		$mform = $this->_form;
		$data = $this->_customdata['data'];
		$formdata = $this->_customdata['formdata'];
		
		$mform->addElement('hidden', 'id', $data->courseid);
		$mform->setType('id', PARAM_INT);
		$mform->addElement('hidden', 'iid', $data->courseid);
		$mform->setType('iid', PARAM_INT);
		$mform->addElement('hidden', 'content_csv', $data->content_csv);
		$mform->setType('content_csv', PARAM_RAW);
		$mform->addElement('hidden', 'delete_users', $data->delete_users);
		$mform->setType('delete_users', PARAM_INT);
		$mform->addElement('hidden', 'type', $data->type);
		$mform->setType('type', PARAM_ALPHA);	
		$mform->addElement('hidden', 'role', $data->role);
		$mform->setType('role', PARAM_ALPHA);
		$mform->addElement('hidden', 'groups', $data->groups);
		$mform->setType('groups', PARAM_RAW);
		$this->add_action_buttons(true, get_string('savechanges'));
		
		$this->set_data($data);
	
	}
}

