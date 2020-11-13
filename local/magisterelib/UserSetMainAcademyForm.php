<?php 
require_once($CFG->libdir.'/formslib.php');

//Formulaire de mise a jour du nom et prenom de l'utilisateur
class UserSetMainAcademyForm extends moodleform {

    public function definition() {
		global $CFG;
		
        $mform = $this->_form;
		
		$academyList = array();
		$academyList[''] = '';
		
		foreach($CFG->academylist as $academy => $data){
			if($academy != 'frontal'){
				$academyList[$academy] = $data['name'];
			}
		}
				
		$mform->addElement('html', '<div class="grey_background">');
		$mform->addElement('select', 'main_academy', 'Liste des instances ', $academyList);
		
		$mform->addElement('hidden', 'id', 'id');
		$mform->setType('id', PARAM_TEXT);
		
		$mform->addElement('hidden', 'validate', 'Valider et continuer');
		$mform->setType('validate', PARAM_TEXT);

		//$mform->addElement('submit', 'validate', 'Valider et continuer');

		$mform->addElement('html', '</div>');
    }

    /**
     * Validate incoming data.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
	 /*
    public function validation($data, $files) {
		
        $errors = array();
        $draftitemid = $data['files_filemanager'];
        if (file_is_draft_area_limit_reached($draftitemid, $this->_customdata['options']['areamaxbytes'])) {
            $errors['files_filemanager'] = get_string('userquotalimit', 'error');
        }

        return $errors;
    }
	*/
}