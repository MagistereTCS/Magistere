<?php

class backup_list_activities_block_task extends backup_block_task {
    protected $list_activities_path;
    /**
     * override récupération locale de $basepath
     * Block tasks have their own directory to write files
     */
    public function get_taskbasepath() {

        $basepath = $this->get_basepath();

        // Module blocks are under module dir
        if (!empty($this->moduleid)) {
            $basepath .= '/activities/' . $this->modulename . '_' . $this->moduleid .
                '/blocks/' . $this->blockname . '_' . $this->blockid;

            // Course blocks are under course dir
        } else {
            $basepath .= '/course/blocks/' . $this->blockname . '_' . $this->blockid;
        }
        $this->list_activities_path= $basepath;
        return $basepath;

    }

    protected function define_my_settings() {
    }

    protected function define_my_steps() {
    }

    public function get_fileareas() {

        if($this->list_activities_path!=''){
            $this->list_activities_file();
        }
        return array();

    }

    public function get_configdata_encoded_attributes() {
        return array();
    }

    static public function encode_content_links($content) {
        return $content;
    }

    /*
     *	création du contenu xml correspondant aux contenu monitorés
     */
    protected function list_activities_content(){

        global $DB;

        $courseid = $this->get_courseid();

        $list_activities = $DB->get_record('block_list_activities',["courseid" => $courseid]);

        //création du contenu XML
        $xml = new DOMDocument('1.0', 'utf-8');
        $element = $xml->createElement('list_activities');
        $xml->appendChild($element);

        if($list_activities){
            //création de l'élément monitoré
            $uniqueelement = $xml->createElement('element');
            $uniqueelement->setAttribute('id',0);

            // courseid
            $xcourseid= $xml->createElement('courseid', $list_activities->courseid);
            $uniqueelement->appendChild($xcourseid);

            // sectionid
            $xsectionid= $xml->createElement('weight', $list_activities->weight);
            $uniqueelement->appendChild($xsectionid);

            // parentid
            $xparentid= $xml->createElement('notdisplay', $list_activities->notdisplay);
            $uniqueelement->appendChild($xparentid);

            $element->appendChild($uniqueelement);
        }


        return $xml->saveXML();

    }

    /*
     *	création du fichier xml monitor.xml
     */
    protected function list_activities_file(){
        $file_content = $this->list_activities_content();
        $f_list_activities= fopen($this->list_activities_path."/list_activities.xml","w");
        if($f_list_activities!=false){
            $test_write = fwrite( $f_list_activities , $file_content);
            fclose($f_list_activities);
        }

    }
}
