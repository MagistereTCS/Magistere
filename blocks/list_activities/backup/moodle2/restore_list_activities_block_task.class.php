<?php

class restore_list_activities_block_task extends restore_block_task {

    /**
     * Translates the backed up configuration data for the target course modules
     *
     * @global type $DB
     */
    public function after_restore() {

        global $DB;

        $list_activities_xml = file_exists ( $this->taskbasepath.'/list_activities.xml');

        if($list_activities_xml){
            $list_activities = $this->list_activites_xml_to_array();
        }

        //Restored course id
        $courseid = $this->get_courseid();


        if($DB->get_record('block_list_activities', array('courseid' => $courseid))){
            // le bloc existe deja, on ne fait rien
            // cas de la restoration avec fusion
            return;
        }


        $weight = unserialize_array(base64_decode($list_activities["weight"]));
        $notdisplay = unserialize_array(base64_decode($list_activities["notdisplay"]));

        $new_weight = [];
        foreach ($weight as $id) {
            list($course, $cm) = get_course_and_cm_from_cmid($id);
            $result = restore_dbops::get_backup_ids_record($this->get_restoreid(),
                'course_module',strval($cm->id));

            $new_weight[] = $result->newitemid;
        }

        $new_notdisplay = [];
        foreach ($notdisplay as $id) {
            list($course, $cm) = get_course_and_cm_from_cmid($id);
            $result = restore_dbops::get_backup_ids_record($this->get_restoreid(),
                'course_module',strval($cm->id));

            $new_notdisplay[] = $result->newitemid;
        }

        $la = new stdClass();
        $la->courseid = $courseid;
        $la->notdisplay = base64_encode(serialize($new_notdisplay));
        $la->weight =base64_encode(serialize($new_weight));

        $DB->insert_record('block_list_activities', $la);

    }

    //transforme le contenu xml en un tableau
    public function list_activites_xml_to_array(){

        $list_activities_a = array();
        //récupération du contenu xml et boucle sur les éléments
        $doc_summary= new DOMDocument();
        $doc_summary->load($this->taskbasepath."/list_activities.xml");
        $list_activities = $doc_summary->getElementsByTagName( "list_activities" );

        foreach($list_activities as $la) {
            $elements = $la->getElementsByTagName( "element" );
            foreach($elements as $element) {
                $courseid_ua = $element->getElementsByTagName("courseid");
                $courseid = $courseid_ua->item(0)->nodeValue;

                $weight_ua = $element->getElementsByTagName("weight");
                $weight = $weight_ua->item(0)->nodeValue;

                $notdisplay_ua = $element->getElementsByTagName("notdisplay");
                $notdisplay = $notdisplay_ua->item(0)->nodeValue;

                $list_activity = array('courseid' => $courseid, 'weight' => $weight , 'notdisplay' => $notdisplay);
                //on quitte a partir du moment ou on trouve un paramètre (il peut y avoir suelement un paramètre du block pour un cours)
                return $list_activity;

            }
        }
    }

    /**
     * There are no unusual settings for this restore
     */
    protected function define_my_settings() {
    }

    /**
     * There are no unusual steps for this restore
     */
    protected function define_my_steps() {
    }

    /**
     * There are no files associated with this block
     *
     * @return array An empty array
     */
    public function get_fileareas() {
        return array();
    }

    /**
     * There are no specially encoded attributes
     *
     * @return array An empty array
     */
    public function get_configdata_encoded_attributes() {
        return array();
    }

    /**
     * There is no coded content in the backup
     *
     * @return array An empty array
     */
    static public function define_decode_contents() {
        return array();
    }

    /**
     * There are no coded links in the backup
     *
     * @return array An empty array
     */
    static public function define_decode_rules() {
        return array();
    }
}
