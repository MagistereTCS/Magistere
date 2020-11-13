<?php

require_once("$CFG->libdir/formslib.php");
require_once(dirname(__FILE__).'/../lib/CRQuickForm_Renderer.php');

class manageResource_form extends moodleform
{
	public function definition() {
		global $CFG;

		$mform = $this->_form;

		$this->contruct_form($mform);

		$mform->disable_form_change_checker();
	}

	function get_js_action()
	{
		global $CFG;

		return '
		<script type="text/javascript">
			$(function(){
				$("#id_creator_is_me").change(function(){
					$("#id_creator_search").prop("disabled", $(this).prop("checked"));
					$("#id_creator_search").prop("value", "");
				});

				$("#id_datepicker").datepicker({
					monthNames: ["Janvier", "F&eacute;vrier", "Mars", "Avril", "Mai", "Juin", "Juillet", "Ao&ucirc;t", "Septembre", "Octobre", "Novembre", "Décembre"],
					monthNamesShort: ["janv.", "f&eacute;vr.", "mars", "avril", "mai", "juin", "juil.", "ao&ucirc;t", "sept.", "oct.", "nov.", "d&eacute;c."],
					dayNames: ["dimanche", "lundi", "mardi", "mercredi", "jeudi", "vendredi", "samedi"],
					dayNamesShort: ["dim.", "lun.", "mar.", "mer.", "jeu.", "ven.", "sam."],
					dayNamesMin: ["D","L","M","M","J","V","S"],
					dateFormat: "dd/mm/yy",
					weekHeader: "Sem.",
					firstDay: 1,
					currentText: "Aujourd\'hui",
					closeText: "Fermer",
					prevText: "Pr&eacute;c&eacute;dent",
					nextText: "Suivant",
					isRTL: false,
					showMonthAfterYear: false
				});
				
				$("#id_cr_search").click(function(){
				    $("#search_result").jtable("load");
				});
				
				$("#id_cr_reset").click(function(){
					$("#mform1").get(0).reset();
				});
				
				window.onbeforeunload = null;
			});
		</script>
		';
	}

	public function contruct_form(MoodleQuickForm &$mform){
        $GLOBALS['_HTML_QuickForm_default_renderer'] = new CRQuickForm_Renderer();

        $mform->setDisableShortforms(true);
        
        MoodleQuickForm::registerElementType(
            'customcheckbox',
            dirname(__FILE__).'/../lib/HTML_QuickForm_customcheckbox.php',
            'HTML_QuickForm_customcheckbox'
        );
        MoodleQuickForm::registerElementType(
            'customcheckboxlist',
            dirname(__FILE__).'/../lib/HTML_QuickForm_customcheckboxlist.php',
            'HTML_QuickForm_customcheckboxlist'
        );

        $mform->updateAttributes(array(
            'class' => 'mform blocks resource-form'
        ));

		$mform->addElement('html', '<h2 id="search-form-title">' . get_string('local_cr_manage_resource_header_label', 'local_centralizedresources') . '</h2>');

		$mform->addElement('header', 'owner-ressearch', get_string('local_cr_manage_resource_creator_label', 'local_centralizedresources'));
		//$mform->setExpanded('owner-ressearch');
		$mform->addElement('text', 'creator_search', get_string('local_cr_manage_resource_search_creator_label', 'local_centralizedresources'));
		$mform->setType('creator_search', PARAM_TEXT);
        $mform->addElement('customcheckboxlist', 'creator_is_me_container', array(
            array('name' => 'creator_is_me', 'label' => get_string('local_cr_manage_resource_creator_is_me_label', 'local_centralizedresources'))
        ));

		$mform->addElement('header', 'domain-ressearch', get_string('local_cr_manage_resource_domain_restriction_header_label', 'local_centralizedresources'));
		$mform->addElement('customcheckboxlist', 'domain_restriction_container', array(
            array('name' => 'domain_restriction', 'label' => get_string('local_cr_manage_resource_domain_restriction_label', 'local_centralizedresources'))
        ));

		$mform->addElement('header', 'date-ressearch', get_string('local_cr_manage_resource_update_date_header_label', 'local_centralizedresources'));
		$mform->addElement('text', 'datepicker', get_string('local_cr_manage_resource_update_date_label', 'local_centralizedresources').' :');
		$mform->setType('datepicker', PARAM_TEXT);
		
		
		$mform->addElement('header', 'search-ressearch', get_string('local_cr_manage_resource_keyword_header_label', 'local_centralizedresources'));
		$mform->addElement('text', 'field_search', get_string('local_cr_manage_resource_keyword_search_label', 'local_centralizedresources').' :');
		$mform->setType('field_search', PARAM_TEXT);

		$mform->addElement('header', 'types-ressearch', get_string('local_cr_manage_resource_resource_type_header_label', 'local_centralizedresources'));
        $mform->addElement('customcheckboxlist', 'types', array(
            array('name' => 'video', 'label' => get_string('local_cr_manage_resource_video_resource_label', 'local_centralizedresources')),
            array('name' => 'audio', 'label' => get_string('local_cr_manage_resource_sound_resource_label', 'local_centralizedresources')),
            array('name' => 'image', 'label' => get_string('local_cr_manage_resource_picture_resource_label', 'local_centralizedresources')),
            array('name' => 'document', 'label' => get_string('local_cr_manage_resource_document_resource_label', 'local_centralizedresources')),
            array('name' => 'archive', 'label' => get_string('local_cr_manage_resource_archive_resource_label', 'local_centralizedresources')),
            array('name' => 'diaporama', 'label' => get_string('local_cr_manage_resource_multimedia_activity_resource_label', 'local_centralizedresources')),
            array('name' => 'other', 'label' => get_string('local_cr_manage_resource_other_resource_label', 'local_centralizedresources'))
        ));

        $mform->addElement('header', 'buttons-ressearch', '');
        $mform->addElement('html', $this->get_js_action());
		$mform->addElement('button', 'cr_search', get_string('local_cr_manage_resource_search_label', 'local_centralizedresources'));
		$mform->addElement('button', 'cr_reset', get_string('local_cr_manage_resource_reset_label', 'local_centralizedresources'));
		
		$mform->addElement('html', '
					<script type="text/javascript">
						$("#'.$mform->_attributes['id'].'").keypress(function(e){
							if(e.which == 13) {
								e.preventDefault();
								$("#id_cr_search").trigger( "click" );
    						}
						});
					</script>');
	}

    function get_data(){
        $data = parent::get_data();

        if(!isset($data->domainrestricted)){
            $data->domainrestricted = 0;
        }

        return $data;
    }

}