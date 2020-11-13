<?php

require_once("$CFG->libdir/formslib.php");

/**
 * Class add_resource_form qui permet la génération du formulaire d'ajout de ressource greffé au formulaire d'indexation.
 */
class add_resource_form extends moodleform
{
    /**
     * Fonction qui définit la composition du formulaire d'ajout de ressource.
     * @throws coding_exception
     * @throws moodle_exception
     */
    public function definition() {
        global $PAGE;

        $PAGE->requires->js_call_amd("local_indexation/resize_image", "init");
        $PAGE->requires->css(new moodle_url("/local/magisterelib/styles/cropperjs.min.css"));
        $PAGE->requires->css(new moodle_url('/local/indexation/style.css'));

        $mform =& $this->_form;

        $mform->setDisableShortforms(true);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', $this->_customdata['id']);

        $mform->addElement('hidden', 'type');
        $mform->setDefault('type', $this->_customdata['type']);
        $mform->setType('type', PARAM_TEXT);

        $mform->addElement('header', 'add_resource', get_string('local_cr_add_resource_header_label', 'local_centralizedresources'));

        $mform->addElement('text', 'title', get_string('local_cr_add_resource_title_label', 'local_centralizedresources'));
        $mform->setType('title', PARAM_TEXT);

        $mform->setDefault('title', $this->_customdata['coursename']);



        $mform->addElement('textarea', 'description', get_string('local_cr_add_resource_description_label', 'local_centralizedresources'));
        $mform->setType('description', PARAM_TEXT);
        if($this->_customdata['type'] == 'video') {
            $mform->setDefault('description',get_string('default_description_video', 'local_indexation',$this->_customdata['coursename']));
        }else{
            $mform->setDefault('description',get_string('default_description', 'local_indexation',$this->_customdata['coursename']));
        }

        $mform->addElement('filepicker', 'attachments', get_string('local_cr_add_resource_update_label', 'local_centralizedresources'), null, array('subdirs' => 0, 'maxfiles' => 1));
        $mform->addRule('title', get_string('required'), 'required');

        $mform->addElement('html',"
            <div class ='img-container'>
                <img id='image' 
                    src='https://upload.wikimedia.org/wikipedia/commons/9/9a/Gull_portrait_ca_usa.jpg' 
                    alt='Picture' 
                    class='cropper-hidden' 
                    width='500px'>
            </div>");

        $mform->addRule('description', get_string('required'), 'required');
        $mform->addRule('attachments', get_string('required'), 'required');

        $mform->addElement('hidden', 'img_datas');
        $mform->setType('img_datas', PARAM_TEXT);

        $mform->addElement('hidden', 'img_old_datas');
        $mform->setType('img_old_datas', PARAM_TEXT);

        if($this->_customdata['type'] == 'video'){
            $mform->addElement('hidden', 'tsecondes');
            $mform->setType('tsecondes', PARAM_INT);
            $mform->setDefault('tsecondes', 5);
        }

        $buttonarray=array();

        $buttonarray[] =& $mform->createElement('submit', 'add', get_string('add'));
        $buttonarray[] =& $mform->createElement('cancel', 'cancel', get_string('cancel'));

        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');

    }

    /**
     * Fonction qui vérifie l'intégrité des données saisies dans le formulaire.
     * @return array|bool
     * @throws coding_exception
     */
    public function validate_draft_files()
    {
        global $CFG;
        $ret =  parent::validate_draft_files();

        if($ret !== true){
            return $ret;
        }

        $draftitemid = file_get_submitted_draft_itemid('attachments');
        $draftareaFiles = file_get_drafarea_files($draftitemid, false);

        $fileinfo = $draftareaFiles->list[0];
        $split = explode('.', $fileinfo->filename);
        $extension = strtolower(end($split));
        if($this->_customdata['type'] == 'thumb'){
            if(!in_array($extension, $CFG->centralizedresources_allow_filetype['image'])){
                return array('attachments' => get_string('thumbnail_bad_extension', 'local_indexation'));
            }
        }

        if($this->_customdata['type'] == 'video' && !in_array($extension, $CFG->centralizedresources_allow_filetype['video'])){
            return array('attachments' => get_string('video_bad_extension', 'local_indexation'));
        }

        return $ret;
    }

    /**
     * Fonction qui modifie la valeur de certains champs avant chargement du formulaire.
     */
    public function definition_after_data()
    {
        $mform =& $this->_form;
        $img_old_datas =& $mform->getElement('img_old_datas');
        $img_old_datas->setValue($mform->getElementValue('img_datas'));
    }
}
