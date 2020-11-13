<?php

require_once("$CFG->libdir/formslib.php");

class addResource_form extends moodleform
{
	public function definition() {
		global $CFG, $COURSE;

		$mform = $this->_form;
		
		$mform->setDisableShortforms(true);
		
		$mform->updateAttributes(array(
				'class' => 'mform blocks resource-form'
		));
		
		$this->contruct_form($mform);

		$mform->addRule('title', '', 'required', null);
		$mform->addRule('description', '', 'required', null);
		$mform->addRule('attachments', '', 'required', null);

        $mform->addElement('textarea', 'description3', get_string('local_cr_add_resource_description_label', 'local_centralizedresources'));
        $mform->setType('description3', PARAM_TEXT);


		$buttonarray=array();
		
		$buttonarray[] =& $mform->createElement('submit', 'cr_save', get_string('local_cr_add_resource_save_button_label', 'local_centralizedresources'));
		$buttonarray[] =& $mform->createElement('submit', 'cr_save_return', get_string('local_cr_add_resource_save_return_button_label', 'local_centralizedresources'));
		$buttonarray[] =& $mform->createElement('cancel', 'cr_cancel', get_string('local_cr_add_resource_cancel_button_label', 'local_centralizedresources'));

		$mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
		$mform->closeHeaderBefore('buttonar');
		
		$mform->disable_form_change_checker();
		
		$mform->addElement('hidden', 'mimetype', '');
		$mform->setType('mimetype', PARAM_TEXT);
		$mform->addElement('hidden', 'resourceid', '');
		$mform->setType('resourceid', PARAM_TEXT);

		$mform->addElement('hidden', 'course', $COURSE->id);
		$mform->setType('course', PARAM_TEXT);
	}
	
	//Custom validation should be added here
	function validation($data, $files) {		
		return array();
	}

	function get_data(){
	    $data = parent::get_data();

	    if(!isset($data->domainrestricted)){
	        $data->domainrestricted = 0;
        }

        return $data;
    }

	function get_js_action()
	{
		global $CFG;
		
		$extensionaudio = $CFG->centralizedresources_allow_filetype['audio'];
		$extensionvideo = $CFG->centralizedresources_allow_filetype['video'];
		$extensionoptionvideo = array_merge($CFG->centralizedresources_allow_filetype['subtitle'],$CFG->centralizedresources_allow_filetype['chapter']);
		
		return '
		<script type="text/javascript">
			$(function(){
			    display_option("attachments");
			    display_option("subtitle");
				display_option("chapter");
				
			});
			function display_option(filepicker_id){		
				var fileurl = undefined;
				var filepicker_filename = $("#fitem_id_" + filepicker_id).find(".filepicker-filename");
                $("#fitem_id_" + filepicker_id).on("DOMSubtreeModified",".filepicker-filename",function(event){
                    var newurl = $(this).find("a").attr("href");
                    if(!newurl){
                        return;
                    }
                    if(fileurl !== newurl){
                        fileurl = $(this).find("a").attr("href");
                        var extensiondiaporama = ["' . implode('","', $CFG->centralizedresources_allow_filetype['diaporama']) . '"];                                
                        var posfilename = fileurl.lastIndexOf("/");                                
                        var filename = null;
                        
                        if(posfilename !== false){
                            filename = fileurl.substr(posfilename+1);
                        }
                        
                        var uri = filename.split(".");
                        
                        if(uri.length > 1){											
                            var extension = uri[uri.length-1];
                            var extensionaudio = ["'. implode('","', $extensionaudio) . '"];
                            var extensionvideo = ["'. implode('","', $extensionvideo) . '"];
                            
                            if(extensionaudio.indexOf(extension) != -1){
                                $("#id_audio_options").show();
                            }else{
                                $("#id_audio_options").hide();
                                
                            }
                            if(extensionvideo.indexOf(extension) != -1 
                            || filepicker_id == "subtitle" 
                            || filepicker_id == "chapter"){
                                $("#id_video_options").show();
                            }else{
                                $("#id_video_options").hide();
                                
                            }
                            if(extensiondiaporama.indexOf(extension) != -1){
                                $("#id_add_resource_type").show();
                            }else{
                                $("#id_add_resource_type").hide();
                            }
                        }else{
                            if($(\'input[type="hidden"][name="mimetype"]\').val() != "video"){
                                $("#id_video_options").hide();
                                
                            }
                            
                            $("#id_type_single_file").prop("checked", true);
                            $("#id_add_resource_type").hide();
                        }
                        
                    }					        
                });
			}
			if($(\'input[type="hidden"][name="mimetype"]\').val() == ""){
				$("#id_warning_upload").hide();
			}
			
			if($(\'input[type="hidden"][name="mimetype"]\').val() != "video"){
				$("#id_video_options").hide();	
			}
            if($(\'input[type="hidden"][name="mimetype"]\').val() != "audio"){
				$("#id_audio_options").hide();	
			}
			$("#id_add_resource_type").hide();
		</script>
		';
	}
	
	public function contruct_form(&$mform){
		global $OUTPUT;

		$mform->addElement('header', 'add_resource', get_string('local_cr_add_resource_header_label', 'local_centralizedresources'));
		
		$mform->addElement('text', 'title', get_string('local_cr_add_resource_title_label', 'local_centralizedresources'));
		$mform->setType('title', PARAM_TEXT);
				
		$mform->addElement('textarea', 'description', get_string('local_cr_add_resource_description_label', 'local_centralizedresources'));
		$mform->setType('description', PARAM_TEXT);

		$warningicon = '<img src="' . $OUTPUT->image_url('general/warning', 'theme') . '" alt=""/>';
		
		$mform->addElement('html', '<br/><p id="id_warning_upload">' . $warningicon .  get_string('local_cr_add_resource_update_explain_text', 'local_centralizedresources') . '<p>');
		
		$mform->addElement('filepicker', 'attachments', get_string('local_cr_add_resource_update_label', 'local_centralizedresources'), null, array('subdirs' => 0, 'maxfiles' => 1));

        $buttonarr = array();
        $buttonarr[] =& $mform->createElement('checkbox', 'domainrestricted',  '', get_string('local_cr_domainrestricted_label', 'local_centralizedresources'));

        $mform->addGroup($buttonarr, 'domainrestrictedgroup', '', null, false);
        $mform->addHelpButton('domainrestrictedgroup', 'local_cr_domainrestricted_label', 'local_centralizedresources');

        $mform->addElement('header', 'audio_options', get_string('local_cr_add_resource_audio_options_label', 'local_centralizedresources'));
        $mform->addElement('html',  '<p style="color:red;font-size:14px">' . get_string('local_cr_add_resource_audio_warning_text', 'local_centralizedresources') . '</p>');
        
		$mform->addElement('header', 'video_options', get_string('local_cr_add_resource_video_options_label', 'local_centralizedresources'));
		$mform->addElement('html',  '<p style="color:red;font-size:14px">' . get_string('local_cr_add_resource_video_warning_text', 'local_centralizedresources') . '</p>');
		
		$secondesValue = array();
		
		for($i = 0; $i < 31; $i++){
			$secondesValue["$i"] = "$i";
		}
		
		$select = $mform->addElement('select', 'tsecondes', get_string('local_cr_add_resource_select_pos_thumbnail_label', 'local_centralizedresources'), $secondesValue);
		$select->setSelected('5');

		$mform->addElement('filepicker', 'subtitle', get_string('local_cr_add_resource_subtitle_file', 'local_centralizedresources'), null, array('subdirs' => 0, 'maxfiles' => 1));
        $mform->addHelpButton('subtitle', 'local_cr_add_resource_subtitle_file', 'local_centralizedresources');

		$mform->addElement('filepicker', 'chapter', get_string('local_cr_add_resource_chapter_file', 'local_centralizedresources'), null, array('subdirs' => 0, 'maxfiles' => 1));
        $mform->addHelpButton('chapter', 'local_cr_add_resource_chapter_file', 'local_centralizedresources');

		$mform->addElement('header', 'add_resource_type', get_string('local_cr_add_resource_type_header', 'local_centralizedresources'));
		
		$mform->addElement('html',  "<p>" . get_string('local_cr_add_resource_explain_text', 'local_centralizedresources') . "</p>");
		
		$mform->addElement('radio', 'type', '', get_string('local_cr_add_resource_type_resource_label', 'local_centralizedresources'), 'single_file');
		$mform->addElement('radio', 'type', '', get_string('local_cr_add_resource_type_multimedia_file_label', 'local_centralizedresources'), 'multimedia_file');
        
		$mform->setDefault('type', 'single_file');
		
		$mform->addElement('html', $this->get_js_action());
		$mform->addElement('hidden', 'mimetype', '');

		$mform->setType('mimetype', PARAM_TEXT);
	}
}
?>