<?php
class block_educationalbloc_edit_form extends block_edit_form {
    protected function specific_definition($mform) {
		
		// generation du contenu de la combo des types de bloc
		$conf_content = parse_ini_file('conf.ini', true);
		$types_list = array();

		foreach($conf_content['types_names'] as $key => $value)
		{
			$types_list[$key] = $value;
		}
	
        // Fields for editing educationalbloc block title and contents.
        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

        // $mform->addElement('text', 'config_title', get_string('configtitle', 'block_educationalbloc'));
        // $mform->setType('config_title', PARAM_MULTILANG);

		// selection du type de bloc pedagogique
		$mform->addElement('select', 'config_selecttype', 'Type de bloc:', $types_list);
		
        $editoroptions = array('maxfiles' => EDITOR_UNLIMITED_FILES, 'noclean'=>true, 'context'=>$this->block->context);
        $mform->addElement('editor', 'config_text', get_string('configcontent', 'block_educationalbloc'), null, $editoroptions);
        $mform->addRule('config_text', null, 'required', null, 'client');
        $mform->setType('config_text', PARAM_RAW); // XSS is prevented when printing the block contents and serving files
    }

    function set_data($defaults) {
    	$text = '';
    	
        if (!empty($this->block->config) && is_object($this->block->config)) {
            $text = $this->block->config->text;
            $draftid_editor = file_get_submitted_draft_itemid('config_text');
            if (empty($text)) {
                $currenttext = '';
            } else {
                $currenttext = $text;
            }
            $defaults->config_text['text'] = file_prepare_draft_area($draftid_editor, $this->block->context->id, 'block_educationalbloc', 'content', 0, array('subdirs'=>true), $currenttext);
            $defaults->config_text['itemid'] = $draftid_editor;
            $defaults->config_text['format'] = $this->block->config->format;
        }

        if (!$this->block->user_can_edit() && !empty($this->block->config->title)) {
            // If a title has been set but the user cannot edit it format it nicely
            $title = $this->block->config->title;
            $defaults->config_title = format_string($title, true, $this->page->context);
            // Remove the title from the config so that parent::set_data doesn't set it.
            unset($this->block->config->title);
        }

        // have to delete text here, otherwise parent::set_data will empty content
        // of editor
        unset($this->block->config->text);
        parent::set_data($defaults);
        // restore $text

        if (!isset($this->block->config) || !is_a($this->block->config, 'stdClass')) {
            $this->block->config = new stdClass();
        }
        $this->block->config->text = $text;
        if (isset($title)) {
            // Reset the preserved title
            $this->block->config->title = $title;
        }
    }
}
