<?php
if (!defined('MOODLE_INTERNAL')) {
	die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot.'/mod/centralizedresources/lib.php');
require_once($CFG->dirroot.'/mod/resource/locallib.php');
require_once($CFG->dirroot.'/local/centralizedresources/form/manageResource_form.php');
require_once($CFG->dirroot.'/local/centralizedresources/form/addResource_form.php');

class mod_centralizedresources_mod_form extends moodleform_mod {

	function definition() {
		$this->_features->showdescription = true;
		global $CFG, $DB, $OUTPUT, $COURSE, $PAGE;

		$PAGE->requires->jquery();
        $PAGE->requires->jquery_plugin('ui');
        $PAGE->requires->jquery_plugin('ui-css');
        $PAGE->requires->jquery_plugin('jtable');
        $PAGE->requires->jquery_plugin('jtable-css');
        $PAGE->requires->jquery_plugin('jtable-fr');

		$mform =& $this->_form;
		
		$config = get_config('resource');
		$return = optional_param("return", "0", PARAM_TEXT);
		$forbidden_force_download = optional_param("forbiddenfd", "0", PARAM_TEXT);
		
		/*
		$update = optional_param("update", "0", PARAM_TEXT);
		$course_module = $DB->get_record('course_modules', array('id' => $update));
		$where = '';
		if ($course_module !== false)
		{
			$centralizedresource = $DB->get_record('centralizedresources', array('id' => $course_module->instance));
			if ($centralizedresource !== false) 
			{
				$where = "id = '" . $centralizedresource->centralizedresourceid . "'";
			}
		}
		$cr_resource = get_cr_resource($where);
		*/
		
		$id = $COURSE->id; // Course Module ID
		$context = context_course::instance($id);
		$addinstance_capability = has_capability('local/centralizedresources:addresource', $context);
				
		$mform->addElement('text', 'name', get_string('cr_name', 'centralizedresources'), array('size'=>'64'));
		$mform->setType('name', PARAM_TEXT);
		$mform->addRule('name', null, 'required', null, 'client');
		
		// Adding the standard "intro" and "introformat" fields
        $this->standard_intro_elements(get_string('intro', 'data'));
        
        $manageResourceForm = new manageResource_form();
        $addResourceForm = new addResource_form();
        $jtable_title = get_string('local_cr_jtable_title', 'local_centralizedresources');

        $PAGE->requires->js_call_amd('local_centralizedresources/resultSearchTable', 'init', array(
            $CFG->wwwroot . '/local/centralizedresources/view.php?controller=manageresource&action=search',
            $CFG->wwwroot . '/local/centralizedresources/view.php?controller=manageresource&action=getpagerecord',
            true,
            false,
            "false",
            "false",
            '',
            '',
            $jtable_title,
            '',
            '',
            RESOURCELIB_DISPLAY_DOWNLOAD,
            $context->id
        ));

        $PAGE->requires->js_call_amd('mod_centralizedresources/centralizedresource', 'init');

        $manageResourceForm->contruct_form($mform);
        $addResourceForm->contruct_form($mform);
        
        $mform->addElement('header', 'tabs', get_string('resource_definition_header', 'centralizedresources'));
        if(intval($return == 1)){
        	$mform->addElement('html', '<p>* Veuillez sélectionner ou créer une ressource centralisée.</p>');
        }
        $mform->addElement('html', '
        		<div id="frontpage-tabs"> 
					<ul id="course_list-header">
						<li><a href="#frontpage-tabs-1">Sélectionner</a></li>
						<li><a href="#frontpage-tabs-2">Créer</a></li>
					</ul>
        			<div id="frontpage-tabs-1" class="tablebody">
        			    <div class="resource-search"></div>
					</div>
		        	<div id="frontpage-tabs-2" class="tablebody">
					</div>
        		</div>');

        $mform->addElement('html', '<div id="search_result" style="width:100%"></div>');

        $mform->addElement('hidden', 'centralizedresourceid', 0, array('id'=>'id_centralizedresourceid'));
        $mform->setType('centralizedresourceid', PARAM_TEXT);
        $mform->addRule('centralizedresourceid', null, 'required', null, 'client');
        $mform->addElement('hidden', 'add_new_resource', 0, array('id'=>'id_add_new_resource'));
        $mform->setType('add_new_resource', PARAM_TEXT);
        
        $mform->addElement('header', 'optionssection', get_string('optionsheader', 'resource'));

        if ($this->current->instance) {
        	$options = resourcelib_get_displayoptions(explode(',', $config->displayoptions), $this->current->display);
        } else {
        	$options = resourcelib_get_displayoptions(explode(',', $config->displayoptions));
        }

        if (count($options) == 1) {
        	$mform->addElement('hidden', 'display');
        	$mform->setType('display', PARAM_INT);
        	reset($options);
        	$mform->setDefault('display', key($options));
        } else {
        	$mform->addElement('select', 'display', get_string('displayselect', 'resource'), $options);
        	$mform->setDefault('display', $config->display);
        	$mform->addHelpButton('display', 'displayselect', 'resource');
        }
        
        if(intval($forbidden_force_download == 1)){
        	$mform->addElement('html', '<p>* Impossible de sélectionner "forcer le téléchargement"pour une ressource centralisée de type diaporama.</p>');
        }

        $mform->addElement('checkbox', 'showsize', get_string('showsize', 'resource'));
        $mform->setDefault('showsize', $config->showsize);
        $mform->addHelpButton('showsize', 'showsize', 'resource');
        
        if (array_key_exists(RESOURCELIB_DISPLAY_POPUP, $options)) {
        	$mform->addElement('text', 'popupwidth', get_string('popupwidth', 'resource'), array('size'=>3));
        	if (count($options) > 1) {
        		$mform->disabledIf('popupwidth', 'display', 'noteq', RESOURCELIB_DISPLAY_POPUP);
        	}
        	$mform->setType('popupwidth', PARAM_INT);
        	$mform->setDefault('popupwidth', $config->popupwidth);
        
        	$mform->addElement('text', 'popupheight', get_string('popupheight', 'resource'), array('size'=>3));
        	if (count($options) > 1) {
        		$mform->disabledIf('popupheight', 'display', 'noteq', RESOURCELIB_DISPLAY_POPUP);
        	}
        	$mform->setType('popupheight', PARAM_INT);
        	$mform->setDefault('popupheight', $config->popupheight);
        }

		$this->standard_coursemodule_elements();

		$this->add_action_buttons();
	}
	
	function data_preprocessing(&$default_values) {

		if (!empty($default_values['displayoptions'])) {
			$displayoptions = unserialize($default_values['displayoptions']);

			if (!empty($displayoptions['popupwidth'])) {
				$default_values['popupwidth'] = $displayoptions['popupwidth'];
			}
			if (!empty($displayoptions['popupheight'])) {
				$default_values['popupheight'] = $displayoptions['popupheight'];
			}
			if (!empty($displayoptions['showsize'])) {
				$default_values['showsize'] = $displayoptions['showsize'];
			} else {
				// Must set explicitly to 0 here otherwise it will use system
				// default which may be 1.
				$default_values['showsize'] = 0;
			}

		}
	}
	
}