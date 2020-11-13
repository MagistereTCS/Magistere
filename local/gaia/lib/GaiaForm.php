<?php
require_once($CFG->dirroot . '/local/gaia/GaiaApi.php');

class GaiaForm {

    private $form;
    private $courseid;
//    private $context;
    private $type;

    public function __construct($courseid, $type) {
        $this->courseid = $courseid;
//        $this->context = context_course::instance($courseid);
        $this->type = $type;
        $this->form = $this->loadForm();
    }

    public function isSubmittedForm() {
        if($this->form != null){
            if($this->form->get_data() != null) {
                return true;
            }
        }
        return false;
    }

    private function loadForm() {
        global $CFG;
        require_once($CFG->dirroot.'/local/gaia/form/gaia_course_form.php');


        $formData = array(
            'id' => $this->courseid,
            'type' => $this->type,
//            'contextid' => context_course::instance($this->courseid)->id,
        );

        return new gaiaCourseForm(null, $formData);
    }

    public function getForm() {
        if ($this->type == GaiaApi::TYPE_COURSE){
            $source = html_writer::start_div('form gaia-course');
        } else {
            $source = html_writer::start_div('form gaia-cv');
        }

        $source .= $this->form->render();
        $source .= html_writer::end_div();
        return $source;
    }

}