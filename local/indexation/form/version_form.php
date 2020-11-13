<?php

require_once("$CFG->libdir/formslib.php");

/**
 * Class version_form qui permet la génération de l'onglet version dans le formulaire d'indexation.
 */
class version_form extends moodleform
{
    private $labels;

    /**
     * version_form constructor. Il permet le chargement des différentes traductions et préciser l'url de destination.
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
            'year' => get_string('year_label', 'local_indexation'),
            'version' => get_string('version_label', 'local_indexation'),
            'matricule' => get_string('matricule_label', 'local_indexation'),
            'currentnote' => get_string('currentnote_label', 'local_indexation')
        );

        $action = new moodle_url('/local/indexation/index.php', array('id' => $customdata['id']));
        $action .= '#version';

        parent::__construct($action, $customdata, $method, $target, $attributes, $editable, $ajaxformdata);
    }

    /**
     * Fonction qui définit la composition du formulaire de l'onglet version.
     * @throws coding_exception
     */
    public function definition() {
        global $OUTPUT;
        $mform =& $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', $this->_customdata['id']);

        $required_fields = $this->_customdata['notification_badges'];

        // check the required fields and mark all the missing one
        if(array_key_exists('year', $required_fields)){
            $this->labels['year'] .= $OUTPUT->help_icon('year_label', 'local_indexation', 1);
        }
        
        if(array_key_exists('version', $required_fields)){
            $this->labels['version'] .= $OUTPUT->help_icon('version_label', 'local_indexation', 1);
        }

        // element to force the creation of the hidden fieldset
        $mform->addElement('text', 'fieldset', '', array('style' => 'display: none; height: 0;'));
        $mform->setType('fieldset', PARAM_BOOL);

        $mform->addElement('html', '<div class="code_group">');
        $mform->addElement('html', '<div class="col">');
        $mform->addElement('text', 'year', $this->labels['year']);
        $mform->setType('year', PARAM_INT);
        $mform->addRule('year', get_string('error'), 'rangelength', array(2, 2));

        $mform->addElement('html', '<span class="subtitle">'.get_string('label_year', 'local_indexation').'</span>');
        $mform->addElement('html', '</div>');

        $mform->addElement('html', '<div class="col">');
        $currentversion = 0;
        if(isset($this->_customdata['indexation']->version)){
            $currentversion = $this->_customdata['indexation']->version;
        }

        $versions = $this->get_list_version($currentversion);
        $mform->addElement('select', 'version', $this->labels['version'], $versions);

        $mform->addElement('html', '<span></span>');
        $mform->addElement('html', '</div>');

        $mform->addElement('html', '<div class="col">');

        $mform->addElement('text', 'matricule', $this->labels['matricule'], array('disabled' => 'disabled'));
        $mform->setType('matricule', PARAM_TEXT);

        $mform->addElement('html', '<span></span>');

        $mform->addElement('html', '</div></div>');

        $mform->addElement('textarea', 'currentnote', $this->labels['currentnote']);

        $mform->addElement('hidden', 'currentversion');
        $mform->setType('currentversion', PARAM_TEXT);

        $mform->addElement('hidden', 'previousnote');
        $mform->setType('previousnote', PARAM_TEXT);
    }

    /**
     * Fonction qui modifie la valeur de certains champs avant chargement du formulaire.
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function definition_after_data()
    {
        global $DB, $CFG;

        if(!$this->_customdata['indexation']){
            return;
        }

        $mform =& $this->_form;

        $indexation = $this->_customdata['indexation'];

        $notes = $DB->get_records_sql('SELECT * 
FROM {local_indexation_notes} lin
WHERE lin.version <> ? AND lin.indexationid = ? ORDER BY lin.timecreated DESC', [$indexation->version, $indexation->id]);

        foreach($notes as $id => $data){

            if(empty($data->note)){
                continue;
            }

            $d = new stdClass();
            $d->version = $data->version;
            $d->date = date('d/m/Y', $data->timemodified);
            $label = get_string('oldnote_label', 'local_indexation', $d);
            $data->note = nl2br($data->note);

            $mform->addElement('html', '<label class="oldnotelabel">'.$label.'</label>');
            $mform->addElement('html', '<div class="oldnote">'.$data->note.'</div>');
        }

        if(isset($indexation->version)){
            $mform->setDefault('currentversion', $indexation->version);

            $currentnote = $DB->get_record('local_indexation_notes', ['indexationid' => $indexation->id, 'version' => $indexation->version]);

            if($currentnote){
                $mform->setDefault('currentnote', $currentnote->note);
            }
        }

        $year = (isset($indexation->year) ? $indexation->year : intval(date('y')));
        $mform->setDefault('year', $year);

        // set the course identification
        $intitule = ($indexation->title ? $indexation->title : $indexation->courseid);
        $version = ($indexation->version ? $indexation->version : '0.1');

        // choose the selected origin code, otherwise use the code of the current academie
        $DBC = get_centralized_db_connection();
        $conditions = ['name' => $CFG->academie_name];
        if($indexation->origin){
            if($indexation->origin == 'academie'){
                $name = $DB->get_record('t_academie', ['id' => $indexation->academyid])->short_uri;
                $conditions['name'] = $name;
            } else {
                $conditions['name'] = $indexation->origin;
            }
        }

        $code = $DBC->get_record('local_indexation_codes', $conditions)->code;

        $mform->setDefault('matricule', $year.'_'.$code.'_'.$intitule.'_'.$version);

        $buttonarray=array();
        $buttonarray[] = $mform->createElement('cancel', 'cancelgeneral');
        $buttonarray[] = $mform->createElement('submit', 'submitgeneral', get_string('savechanges'));
        $mform->addGroup($buttonarray, 'buttonar', '', ' ', false);
    }

    /**
     * Fonction qui modifie la valeur de certaines propriétés de l'objet data après validation du formulaire.
     * @return object
     */
    public function get_data()
    {
        $data = parent::get_data();

        return $data;
    }

    /**
     * Compute the next available versions for a given version.
     *
     * @param $version
     * @return array associative array that can be use with moodle select
     */
    private function get_list_version($version){
        //special cases for 0 (no version) and the max available version
        if($version == 0){
            return ['0.1' => '0.1', '1.0' => '1.0'];
        }

        if($version == '9.9'){
            return ['9.9' => '9.9'];
        }

        // extract major and minor version
        $numv = explode('.', $version);
        $major = $numv[0];
        $minor = $numv[1];

        // if minor equals to nine return only the next major version
        if($minor == 9){
            $next = ($major+1).'.0';
            return [$version => $version, $next => $next];
        }

        // otherwise return the next minor version and the next major version
        $next1 = $major.'.'.($minor+1);
        $next2 = ($major+1).'.0';

        return [$version => $version, $next1 => $next1, $next2 => $next2];
    }
}