<?php

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot.'/repository/lib.php');
require_once($CFG->dirroot.'/repository/centralizedresources/lib.php');

/**
 * Class general_form qui permet la génération de l'onglet general dans le formulaire d'indexation.
 */
class general_form extends moodleform
{
    private $labels;
    private $titles;

    /**
     * general_form constructor. Il permet le chargement des différentes traductions et préciser l'url de destination.
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
            'intitule' => get_string('intitule_label', 'local_indexation'),
            'description' => get_string('description_label', 'local_indexation'),
            'objectif' => get_string('objectif_label', 'local_indexation'),
            'entree_metier' => get_string('entree_metier_label', 'local_indexation'),
            'certificat' => get_string('certificat_label', 'local_indexation'),
            'keyword' => get_string('keyword_label', 'local_indexation'),
            'thumbnail' => get_string('thumbnail_label', 'local_indexation'),
            'video' => get_string('video_label', 'local_indexation'),
            'domaine' => get_string('domaine_label', 'local_indexation'),
            'collection' => get_string('collection_label', 'local_indexation'),
            'choose_thumbnail' => get_string('choose_thumbnail_label', 'local_indexation'),
            'choose_video' => get_string('choose_video_label', 'local_indexation'),
            'choose_certificat' => get_string('choose_certificat_label', 'local_indexation'),
            'choose_collection' => get_string('choose_collection_label', 'local_indexation'),
            'choose_domain' => get_string('choose_domain_label', 'local_indexation'),
            'upload_video' => get_string('upload_video_label', 'local_indexation'),
            'upload_thumbnail' => get_string('upload_thumbnail_label', 'local_indexation'),
            'thumbnailgroup' => get_string('thumbnail_group_label', 'local_indexation'),
            'videogroup' => get_string('video_group_label', 'local_indexation'),
            'achievementmark' => get_string('achievement_mark', 'local_indexation'),
            'coursefullname' => get_string('coursefullname_label', 'local_indexation')
        );

        $this->titles = array(
            'offer' => get_string('offer_title', 'local_indexation'),
            'domain' => get_string('domain_title', 'local_indexation'),
        );

        $action = new moodle_url('/local/indexation/index.php', array('id' => $customdata['id']));
        $action .= '#general';

        parent::__construct($action, $customdata, $method, $target, $attributes, $editable, $ajaxformdata);
    }

    /**
     * Fonction qui définit la composition du formulaire de l'onglet general.
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function definition() {
        global $DB, $PAGE, $OUTPUT,$CFG;

        $mform =& $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', $this->_customdata['id']); // course id

        $required_fields = $this->_customdata['notification_badges'];

        $coursefullname = $this->_customdata['coursefullname'] ;
        $coursedesc = $this->_customdata['coursedescription'];

        $mform->addElement('text', 'coursefullname', $this->labels['coursefullname']);
        $mform->setDefault('coursefullname', $coursefullname);
        $mform->setType('coursefullname', PARAM_TEXT);
        $mform->addRule('coursefullname', get_string('error'), 'required');

        if(array_key_exists('title', $required_fields)){
            $this->labels['intitule'] .= $OUTPUT->help_icon('intitule_label', 'local_indexation', 1);
        }

        $mform->addElement('text', 'intitule', $this->labels['intitule']);
        $mform->setType('intitule', PARAM_TEXT);
        $mform->addRule('intitule', get_string('error'), 'maxlength', 15);

        $mform->addElement('html', '<span class="subtitle">'.get_string('label_intitule', 'local_indexation').'</span></div><div class="col">');

        $element = $mform->addElement('editor', 'description', $this->labels['description']);
        $mform->setType('description', PARAM_RAW);
        $element->setValue(array('text' => $coursedesc));

        $mform->addElement('editor', 'objectif', $this->labels['objectif']);
        $mform->setType('objectif', PARAM_RAW);

        $mform->addElement('checkbox', 'achievementmark', $this->labels['achievementmark']);

        $radioarray=array();
        $radioarray[] = $mform->createElement('radio', 'entree_metier', '', get_string('yes'), 1);
        $radioarray[] = $mform->createElement('radio', 'entree_metier', '', get_string('no'), 0);
        $mform->addGroup($radioarray, 'entree_metier_group', $this->labels['entree_metier'], array(' '), false);

        $DBC = get_centralized_db_connection();

        $certificats = $DBC->get_records('local_indexation_certificats');
        $certificationOptions = array($this->labels['choose_certificat']);
        foreach($certificats as $id => $data){
            $certificationOptions[$id] = $data->name;
        }

        $mform->addElement('select', 'certificat', $this->labels['certificat'], $certificationOptions);

        $mform->addElement('text', 'keywords', $this->labels['keyword']);
        $mform->setType('keywords', PARAM_TEXT);

        if(isset($this->_customdata["indexation"]) && is_object($this->_customdata["indexation"]) && $this->_customdata["indexation"]->thumbnailid){
            $cr_resource_thumbnail = $DBC->get_record('cr_resources',array("resourceid"=>$this->_customdata["indexation"]->thumbnailid));
            $thumbnail_url  = get_resource_centralized_secure_url(
                '/'.$cr_resource_thumbnail->type.'/'.$cr_resource_thumbnail->cleanname,
                $cr_resource_thumbnail->hashname.$cr_resource_thumbnail->createdate, $CFG->secure_link_timestamp_video);
        }else{
            $domainid = (isset($this->_customdata["indexation"]->domainid)?isset($this->_customdata["indexation"]->domainid):'');
            $img = 'offers/' . $domainid . '_domains_2x';
            $thumbnail_url = $OUTPUT->image_url($img, 'theme');
        }

        $thumbnailgroup = array();
        $thumbnailgroup[] =& $mform->createElement('submit', 'upload_thumbnail', $this->labels['upload_thumbnail']);
        $thumbnailgroup[] =& $mform->createElement('button', 'choose_thumbnail', $this->labels['choose_thumbnail']);
        $mform->addGroup($thumbnailgroup, 'thumbnailgroup', $this->labels['thumbnailgroup'], ' ', false);
        $mform->addElement('html', '<div id="thumbnail_img"><img  src="'.$thumbnail_url.'" /></div><div style="clear: both;"></div>');

        if(isset($this->_customdata["indexation"]) && is_object($this->_customdata["indexation"]) && $this->_customdata["indexation"]->videoid){
            $cr_resource_video = $DBC->get_record('cr_resources',array("resourceid"=>$this->_customdata["indexation"]->videoid));
            $cr_resource_thumbnail_video = $DBC->get_record('cr_resources',array("id"=>$cr_resource_video->thumbnailid));

            $video_name = $cr_resource_video->name;
            $video_icon = $OUTPUT->image_url(file_extension_icon($cr_resource_video->filename, 24))->out(false);
            if($cr_resource_thumbnail_video) {
                $thumbnail_exists = file_exists($CFG->centralizedresources_media_path['thumbnail'] . substr($cr_resource_thumbnail_video->hashname, 0, 2) . '/' . $cr_resource_thumbnail_video->hashname . $cr_resource_thumbnail_video->createdate . '.' . $cr_resource_thumbnail_video->extension);

                if ($thumbnail_exists) {
                    $video_url = get_resource_centralized_secure_url(
                        '/' . $cr_resource_thumbnail_video->type . '/' . $cr_resource_thumbnail_video->cleanname,
                        $cr_resource_thumbnail_video->hashname . $cr_resource_thumbnail_video->createdate, $CFG->secure_link_timestamp_video);
                }
            }
        }else{
            $img = 'offers/novideo';
            $video_url = $OUTPUT->image_url($img, 'theme');
        }

        $videogroup = array();
        $videogroup[] =& $mform->createElement('submit', 'upload_video', $this->labels['upload_video']);
        $videogroup[] =& $mform->createElement('button', 'choose_video', $this->labels['choose_video']);
        $mform->addGroup($videogroup, 'videogroup', $this->labels['videogroup'], ' ', false);
        if(isset($video_url)){
            $mform->addElement('html', '<div id="video_img" ><img class="video" src="'.$video_url.'" /></div><div style="clear: both;"></div>');
        }else{
            $mform->addElement('html', '<div id="video_img" ><img class="icon" src="'.$video_icon.'"title="'.$video_name.'" alt="'.$video_name.'" /></div><div style="clear: both;"></div>');
        }
        $mform->addElement('hidden', 'thumbnailid');
        $mform->setType('thumbnailid', PARAM_TEXT);


        $mform->addElement('hidden', 'videoid');
        $mform->setType('videoid', PARAM_TEXT);

        $centralizedrepo = $DB->get_record_sql('SELECT ri.id 
FROM {repository} r
INNER JOIN {repository_instances} ri ON ri.typeid=r.id
WHERE r.type="centralizedresources"');

        $repo = new repository_centralizedresources($centralizedrepo->id);
        $repo->options['sortorder'] = 1;

        $repodata = $repo->get_meta();

        $mform->addElement('html', '<script>
$(function(){
   var repositories = [];

    repositories['.$repodata->id.'] = '.json_encode($repodata).';
    var options = { 
        userprefs: {
            recentrepository: "'.$centralizedrepo->id.'"
        },
        repositories: repositories,
        crexternallink: true,
        returntype: 1
    };
    
    $("#id_choose_video").on("click", function(){
        options.accepted_types = [".mp4"];
        options.formcallback = function(data){
            $("input[name=\'videoid\']").val(data.url);
            if(data.link.includes("theme")){
                 $("#video_img > img").removeClass().addClass(\'icon\');
            }else{
                $("#video_img > img").removeClass().addClass(\'video\');
            }
            $("#video_img > img").attr("src",data.link);
        };
        
        M.core_filepicker.show(Y, options);
    }); 
    
    $("#id_choose_thumbnail").on("click", function(){
        options.accepted_types = [".thumbnail"];
        options.formcallback = function(data){
            $("input[name=\'thumbnailid\']").val(data.url);
            $("#thumbnail_img > img").attr("src",data.link);
        };
        M.core_filepicker.show(Y, options);
    });
});
</script>');


        $mform->addElement('html', '<hr/>');

        $mform->addElement('html', '<h2>'.$this->titles['domain'].'</h2>');

        $domaines = $DBC->get_records('local_indexation_domains');
        $domainOptions = array($this->labels['choose_domain']);
        foreach($domaines as $id => $data){
            $domainOptions[$id] = $data->name;
        }

        $domain_notification = '';
        if(array_key_exists('domainid', $required_fields)){
            $domain_notification = $OUTPUT->help_icon('domaine_label', 'local_indexation', 1);
        }
        $collection_notification = '';
        if(array_key_exists('collectionid', $required_fields)){
            $collection_notification = $OUTPUT->help_icon('collection_label', 'local_indexation', 1);
        }

        $mform->addElement('select', 'domain', $this->labels['domaine'].$domain_notification, $domainOptions);

        $collections = $DBC->get_records('local_indexation_collections');
        $collectionsOptions = array($this->labels['choose_collection']);
        foreach($collections as $id => $data){
            $collectionsOptions[$id] = $data->name;
        }

        $mform->addElement('select', 'collection', $this->labels['collection'].$collection_notification, $collectionsOptions);

        $mform->addElement('html', '<hr/>');

        $buttonarray=array();
        $buttonarray[] = $mform->createElement('cancel', 'cancelgeneral');
        $buttonarray[] = $mform->createElement('submit', 'submitgeneral', get_string('savechanges'));
        $mform->addGroup($buttonarray, 'buttonar', '', ' ', false);

        // dirty hack : load filepicker js
        $mform->addElement('html', '<div style="display: none;">');
        $mform->addElement('filepicker', 'dummyfilepicker');
        $mform->addElement('html', '</div>');
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

        $indexation->objectif = array("text" => $indexation->objectif, "format" => "1");
        $mform->setDefault('objectif', $indexation->objectif);
        $mform->setDefault('entree_metier', $indexation->entree_metier);
        $mform->setDefault('certificat', $indexation->certificatid);
        $mform->setDefault('domain', $indexation->domainid);
        $mform->setDefault('collection', $indexation->collectionid);
        $mform->setDefault('videoid', $indexation->videoid);
        $mform->setDefault('thumbnailid', $indexation->thumbnailid);
        $mform->setDefault('achievementmark', $indexation->achievementmark);

        // if the indexation title has not been set, use the course id instead
        $intitule = (isset($indexation->title) ? $indexation->title : $this->_customdata['id']);
        $mform->setDefault('intitule', $intitule);

        $keywords = $DB->get_records('local_indexation_keywords', array('indexationid' => $indexation->id));
        $keywordlist = array();
        foreach($keywords as $k){
            $keywordlist[] = $k->keyword;
        }

        $mform->setDefault('keywords', implode(', ', $keywordlist));


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

        $data->intitule = $this->nullify_text($data->intitule);

        $data->objectif = $data->objectif['text'];

        $data->certificat = ($data->certificat > 0 ? $data->certificat : null);
        $data->domain = ($data->domain > 0 ? $data->domain : null);
        $data->collection = ($data->collection > 0 ? $data->collection : null);

        $data->entree_metier = ($data->entree_metier == null ? 0 : $data->entree_metier);

        $data->thumbnailid = str_replace(array('[[[cr_', ']]]'), '', $data->thumbnailid);
        $data->thumbnailid = $this->nullify_text($data->thumbnailid);

        $data->videoid = str_replace(array('[[[cr_', ']]]'), '', $data->videoid);
        $data->videoid = $this->nullify_text($data->videoid);

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
}