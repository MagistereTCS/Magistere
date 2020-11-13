<?php

require_once($CFG->libdir.'/formslib.php');

/**
 * Class detail_form qui permet la génération de l'onglet detail dans le formulaire d'indexation.
 */
class detail_form extends moodleform
{
    private $labels;

    /**
     * detail_form constructor. Il permet le chargement des différentes traductions et préciser l'url de destination.
     * @param null $action
     * @param null $customdata
     * @param string $method
     * @param string $target
     * @param null $attributes
     * @param bool $editable
     * @param array|null $ajaxformdata
     * @throws coding_exception
     * @throws moodle_exception
     */
    public function __construct($action = null, $customdata = null, $method = 'post', $target = '', $attributes = null, $editable = true, array $ajaxformdata = null)
    {
        $this->labels = array(
            'temps_a_distance' => get_string('temps_a_distance_label', 'local_indexation'),
            'temps_en_presence' => get_string('temps_en_presence_label', 'local_indexation'),
            'public_cible' => get_string('public_cible_label', 'local_indexation'),
            'accompagnement' => get_string('accompagnement_label', 'local_indexation'),
            'rythme_formation' => get_string('rythme_formation_label', 'local_indexation'),
            'startdate' => get_string('startdate_label', 'local_indexation'),
            'enddate' => get_string('enddate_label', 'local_indexation'),
        );

        $action = new moodle_url('/local/indexation/index.php', array('id' => $customdata['id']));
        $action .= '#detail';

        parent::__construct($action, $customdata, $method, $target, $attributes, $editable, $ajaxformdata);
    }

    /**
     * Fonction qui définit la composition du formulaire de l'onglet detail.
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function definition() {
        global $OUTPUT;
        $mform =& $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', $this->_customdata['id']);

        $required_fields = $this->_customdata['notification_badges'];

        $tempspresence_notification = '';
        if(array_key_exists('tps_en_presence', $required_fields)){
            $tempspresence_notification = $OUTPUT->help_icon('temps_en_presence_label', 'local_indexation', 1);
        }
        $tempspresence = array();
        $tempspresence[] =& $mform->createElement('text', 'tempspresence_h');
        $tempspresence[] =& $mform->createElement('text', 'tempspresence_min');
        $mform->addGroup($tempspresence, 'tempspresence_group', $this->labels['temps_en_presence'].$tempspresence_notification, array(' h '), false);
        $mform->setType('tempspresence_h', PARAM_ALPHANUM);
        $mform->setType('tempspresence_min', PARAM_ALPHANUM);

        $tempsdist_notification = '';
        if(array_key_exists('tps_a_distance', $required_fields)){
            $tempsdist_notification = $OUTPUT->help_icon('temps_a_distance_label', 'local_indexation', 1);
        }
        $tempsdist = array();
        $tempsdist[] = $mform->createElement('text', 'tempsdistance_h');
        $tempsdist[] = $mform->createElement('text', 'tempsdistance_min');
        $mform->addGroup($tempsdist, 'tempspresence_group', $this->labels['temps_a_distance'].$tempsdist_notification, array(' h '), false);
        $mform->setType('tempsdistance_h', PARAM_ALPHANUM);
        $mform->setType('tempsdistance_min', PARAM_ALPHANUM);

        $DBC = get_centralized_db_connection();

        $publics = $DBC->get_records('local_indexation_publics',null,'name ASC');
        $checkbox = array();
        foreach($publics as $id => $data){
            $checkbox[] =& $mform->createElement('checkbox', 'publics['.$id.']', '', $data->name);
        }

        $publics_notification = '';
        if(array_key_exists('public_cibles', $required_fields)){
            $publics_notification = $OUTPUT->help_icon('public_cible_label', 'local_indexation', 1);
        }
        $mform->addGroup($checkbox, 'public_group', $this->labels['public_cible'].$publics_notification, array("<br/>"), false);

        $mform->addElement('textarea', 'accompagnement', $this->labels['accompagnement']);
        $mform->addRule('accompagnement', get_string('error_accompagnement', 'local_indexation'), 'rangelength', array(0, 254));

        $mform->addElement('text', 'rythme_formation', $this->labels['rythme_formation']);
        $mform->setType('rythme_formation', PARAM_TEXT);

        $startdate_notification = '';
        if(array_key_exists('startdate', $required_fields)){
            $startdate_notification = $OUTPUT->help_icon('startdate_label', 'local_indexation', 1);
        }
        $mform->addElement('text', 'startdate', $this->labels['startdate'].$startdate_notification);
        $mform->setType('startdate', PARAM_TEXT);
        $mform->addElement('html', '<i class="i-calendar icon-calendar"></i>');

        $enddate_notification = '';
        if(array_key_exists('enddate', $required_fields)){
            $enddate_notification = $OUTPUT->help_icon('enddate_label', 'local_indexation', 1);
        }
        $mform->addElement('text', 'enddate', $this->labels['enddate'].$enddate_notification);
        $mform->setType('enddate', PARAM_TEXT);
        $mform->addElement('html', '<i class="i-calendar icon-calendar"></i>');

        $mform->addElement('html', '<hr/>');

        $buttonarray=array();
        $buttonarray[] = $mform->createElement('cancel', 'cancelgeneral');
        $buttonarray[] = $mform->createElement('submit', 'submitgeneral', get_string('savechanges'));
        $mform->addGroup($buttonarray, 'buttonar', '', ' ', false);
    }

    /**
     * Fonction qui modifie la valeur de certains champs avant chargement du formulaire.
     * @throws dml_exception
     */
    public function definition_after_data()
    {
        global $DB;
        
        if(!$this->_customdata['indexation']){
            return;
        }

        $mform =& $this->_form;

        $indexation = $this->_customdata['indexation'];
        $course = $this->_customdata['course'];

        if($indexation->tps_a_distance || $indexation->tps_a_distance == "0"){
            $h = intval($indexation->tps_a_distance / 60);
            $min = $indexation->tps_a_distance % 60;
            $mform->setDefault('tempsdistance_h', $h);
            $mform->setDefault('tempsdistance_min', $min);
        } else {
            $mform->setDefault('tempsdistance_h', "");
            $mform->setDefault('tempsdistance_min', "");
        }

        if($indexation->tps_en_presence || $indexation->tps_en_presence == "0"){
            $h = intval($indexation->tps_en_presence / 60);
            $min = $indexation->tps_en_presence % 60;
            $mform->setDefault('tempspresence_h', $h);
            $mform->setDefault('tempspresence_min', $min);
        } else {
            $mform->setDefault('tempspresence_h', "");
            $mform->setDefault('tempspresence_min', "");
        }


        $mform->setDefault('accompagnement', $indexation->accompagnement);
        $mform->setDefault('rythme_formation', $indexation->rythme_formation);

        if($course->startdate){
            $mform->setDefault('startdate', date('d/m/Y', $course->startdate));
        }

        if($course->enddate){
            $mform->setDefault('enddate', date('d/m/Y', $course->enddate));
        }

        $publics = $DB->get_records('local_indexation_public', array('indexationid' => $indexation->id));
        foreach($publics as $id => $data){
            $mform->setDefault('publics['.$data->publicid.']', 1);
        }
    }

    /**
     * Fonction qui modifie la valeur de certaines propriétés de l'objet data après validation du formulaire.
     * @return object
     */
    public function get_data()
    {
        $data = parent::get_data();

        if(!$data){
            return $data;
        }

        $data->accompagnement = $this->nullify_text($data->accompagnement);
        $data->rythme_formation = $this->nullify_text($data->rythme_formation);
        $data->startdate = $this->zerofy_text($data->startdate);
        $data->enddate = $this->zerofy_text($data->enddate);

        if($data->startdate){
            $arg = explode('/', $data->startdate);
            $data->startdate = mktime(0, 0, 0, $arg[1], $arg[0], $arg[2]);
        }

        if($data->enddate){
            $arg = explode('/', $data->enddate);
            $data->enddate = mktime(23, 59, 59, $arg[1], $arg[0], $arg[2]);
        }

        $tempdistance = 0;
        $data->tps_a_distance = null;
        if($data->tempsdistance_h != null){
            $tempdistance += $data->tempsdistance_h*60;
            $data->tps_a_distance = $tempdistance;
        }
        if($data->tempsdistance_min != null){
            $tempdistance += $data->tempsdistance_min;
            $data->tps_a_distance = $tempdistance;
        }

        $tempspresence = 0;
        $data->tps_en_presence = null;
        if($data->tempspresence_h != null){
            $tempspresence += $data->tempspresence_h*60;
            $data->tps_en_presence = $tempspresence;
        }
        if($data->tempspresence_min != null){
            $tempspresence += $data->tempspresence_min;
            $data->tps_en_presence = $tempspresence;
        }

        if(!isset($data->publics)){
            $data->publics = array();
        }
        return $data;
    }

    /**
     * Fonction permettant de traiter un cas particulier sur des valeurs de type text vide en valeur nulle.
     * @param $text
     * @return null|string
     */
    private function nullify_text($text){
        $text = trim($text);
        return (empty($text) ? null : $text);
    }

    /**
     * Fonction permettant de traiter un cas particulier sur des valeurs de type text vide en valeur à zéro.
     * @param $text
     * @return int|string
     */
    private function zerofy_text($text){
        $text = trim($text);
        return (empty($text) ? 0 : $text);
    }

}