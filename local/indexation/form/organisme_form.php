<?php

require_once("$CFG->libdir/formslib.php");

/**
 * Class organisme_form qui permet la génération de l'onglet organisme dans le formulaire d'indexation.
 */
class organisme_form extends moodleform
{
    private $labels;
    private $titles;

    /**
     * organisme_form constructor. Il permet le chargement des différentes traductions et préciser l'url de destination.
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
            'mail_resp' => get_string('mail_resp_label', 'local_indexation'),
            'origine_parcours' => get_string('origine_label', 'local_indexation'),
            'academie' => get_string('academie_label', 'local_indexation'),
            'departement' => get_string('departement_label', 'local_indexation'),
            'espe' => get_string('espe_label', 'local_indexation'),
            'validate_by' => get_string('validate_by_label', 'local_indexation'),
            'authors' => get_string('authors_label', 'local_indexation'),
            'code' => get_string('code_label', 'local_indexation'),
            'intitule' => get_string('intitule_label', 'local_indexation'),
            'version' => get_string('version_label', 'local_indexation'),

        );

        $action = new moodle_url('/local/indexation/index.php', array('id' => $customdata['id']));
        $action .= '#organisme';

        parent::__construct($action, $customdata, $method, $target, $attributes, $editable, $ajaxformdata);
    }

    /**
     * Fonction qui définit la composition du formulaire de l'onglet organisme.
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function definition() {
        global $DB, $OUTPUT;

        $mform =& $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', $this->_customdata['id']);

        $mform->addElement('text', 'contact', $this->labels['mail_resp']);
        $mform->setType('contact', PARAM_EMAIL);

        $required_fields = $this->_customdata['notification_badges'];

        if(array_key_exists('origin', $required_fields)){
            $this->labels['origine_parcours'] .= $OUTPUT->help_icon('code_label', 'local_indexation', 1);
        }

        $DBC = get_centralized_db_connection();
        $originsDB = $DBC->get_records('local_indexation_origins');
        $originsOptions = array();
        foreach($originsDB as $id => $data){
            $originsOptions[$data->shortname] = $data->name;
        }
        $mform->addElement('select', 'origin', $this->labels['origine_parcours'], $originsOptions);

        $academieDB = $DB->get_records('t_academie');
        $academieOptions = array();
        $academieJSON = array();
        foreach($academieDB as $id => $data){
            $academieOptions[$id] = $data->libelle;
            $academieJSON[$id] = $data->short_uri;
        }

        $mform->addElement('select', 'academie', $this->labels['academie'], $academieOptions);

        $departementDB = $DB->get_records('t_departement');
        $departementOptions = array(0 => '-');
        $departementJSON = array();
        foreach($departementDB as $id => $data){
            $departementOptions[$id] = $data->libelle_long;

            // build relation list between aca and dpt for js script
            if(!isset($departementJSON[$data->code_academie])){
                $departementJSON[$data->code_academie] = array();
            }

            $departementJSON[$data->code_academie][] = $id;
        }
        $mform->addElement('select', 'departement', $this->labels['departement'], $departementOptions);

        $codeOriginDB = $DBC->get_records('local_indexation_codes');
        $codeOriginJSON = array();
        foreach($codeOriginDB as $id => $data){
            $codeOriginJSON[$data->name] = $data;
        }

        $mform->addElement('html', '<script>
                                        var departements = '.json_encode($departementJSON).';
                                        var code_origin = '.json_encode($codeOriginJSON).';
                                        var aca_uri = '.json_encode($academieJSON).';
                                    </script>');

        $espeDB = $DB->get_records('t_origine_espe');
        $espeOptions = array();
        foreach($espeDB as $id => $data) {
            $espeOptions[$id] = $data->name;
        }
        $mform->addElement('select', 'espe', $this->labels['espe'], $espeOptions);

        $mform->addElement('hidden', 'code');
        $mform->setType('code', PARAM_INT);

        $mform->addElement('text', 'validateby', $this->labels['validate_by']);
        $mform->setType('validateby', PARAM_TEXT);

        $authors_notification = '';
        if(array_key_exists('authors', $required_fields)){
            $authors_notification = $OUTPUT->help_icon('authors_label', 'local_indexation', 1);
        }
        $mform->addElement('textarea', 'authors', $this->labels['authors'].$authors_notification);

        $mform->addElement('html', '<hr/>');

        $buttonarray = array();
        $buttonarray[] = $mform->createElement('cancel', 'cancelgeneral');
        $buttonarray[] = $mform->createElement('submit', 'submitgeneral', get_string('savechanges'));
        $mform->addGroup($buttonarray, 'buttonar', '', ' ', false);
    }

    /**
     * Fonction qui modifie la valeur de certains champs avant chargement du formulaire.
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function definition_after_data()
    {
        global $CFG;

        if(!$this->_customdata['indexation']){
            return;
        }

        $mform =& $this->_form;

        $indexation = $this->_customdata['indexation'];

        $mform->setDefault('contact', $indexation->contact);
        $mform->setDefault('validateby', $indexation->validateby);
        $mform->setDefault('authors', $indexation->authors);
        $mform->setDefault('origin', $indexation->origin);
        $mform->setDefault('academie', $indexation->academyid);
        $mform->setDefault('departement', $indexation->departementid);
        $mform->setDefault('espe', $indexation->originespeid);
        $mform->setDefault('year', $indexation->year);
        $mform->setDefault('intitule', $indexation->title);
        $mform->setDefault('version', $indexation->version);
        $mform->setDefault('code', $indexation->codeorigineid);

        if(!$indexation->codeorigineid){
            list($origin, $code, $academie) = $this->get_default_code($CFG->academie_name);

            $mform->setDefault('code', $code);
            $mform->setDefault('academie', $academie);
            $mform->setDefault('origin', $origin);
        }
    }

    /**
     * Fonction qui retourne une liste de données spécifiant l'origine, le code académique et le shortname de l'académie.
     * @param $academie_name
     * @return array $origin, $code, $academie
     * @throws dml_exception
     * @throws moodle_exception
     */
    private function get_default_code($academie_name)
    {
        global $DB;

        $DBC = get_centralized_db_connection();

        $code = $DBC->get_record('local_indexation_codes', array('name' => $academie_name))->id;

        $academie = $DB->get_record('t_academie', ['short_uri' => $academie_name]);
        $academie = ($academie ? $academie->id : null);
        $origin = ($academie ? 'academie' : $academie_name);

        return [$origin, $code, $academie];
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

        if($data->origin == 'academie'){
            $data->espe = null;
        }else if($data->origin == 'espe'){
            $data->academie = null;
            $data->departement = null;
        }else{
            $data->academie = null;
            $data->departement = null;
            $data->espe = null;
        }

        $data->validateby = $this->nullify_text($data->validateby);
        $data->authors = $this->nullify_text($data->authors);
        $data->contact = $this->nullify_text($data->contact);

        $data->code = $this->nullify_int($data->code);
        $data->departement = $this->nullify_int($data->departement);

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
     * Fonction permettant de traiter un cas particulier sur des valeurs de type int vide en valeur nulle.
     * @param $int
     * @return null
     */
    private function nullify_int($int){
        return ($int == 0 ? null : $int);
    }
}