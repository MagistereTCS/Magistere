<?php
require_once($CFG->libdir.'/formslib.php');

//Formulaire de mise a jour du nom et prenom de l'utilisateur
class UserProfileUpdateForm extends moodleform {

    public function definition() {
        $mform = $this->_form;
		
		$mform->addElement('text', 'firstname', 'Pr&eacute;nom');
		$mform->setType('firstname', PARAM_TEXT);
		
		$mform->addElement('text', 'lastname', 'Nom');
		$mform->setType('lastname', PARAM_TEXT);
		
		$mform->addElement('hidden', 'id', 'id');
		$mform->setType('id', PARAM_TEXT);

        $mform->addElement('filemanager', 'imagefile', 'Photo de profil <span class="optional">(facultatif)</span>', null, array('subdirs' => 0, 'maxfiles' => 1, 'accepted_types' => array('jpeg', 'jpg', 'png')));

		$buttonarray=array();
		
		$buttonarray[] =& $mform->createElement('submit', 'validate', 'Valider et continuer');

		$mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
    }

}