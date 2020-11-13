<?php

defined('MOODLE_INTERNAL') || die;
require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot.'/local/magisterelib/databaseConnection.php');

class customview_statsparticipants_form extends moodleform {
    protected $course;
    protected $context;

    /**
     * Form definition.
     */
    function definition() {
        global $CFG, $DB;
        $mform = $this->_form;
        $viewId = $this->_customdata['id'];
        $view = get_custom_views_by_id($viewId);
        $emails = str_replace(",", ", ", $view->emails);
        $courses = get_available_courses();
        $acourses = $courses['ref'];
        $scourses = array();
		
        foreach ($view->scourses as $courseuid) {
            if (isset($acourses[$courseuid])) {
                $scourses[$courseuid] = str_replace('*+%','_',$courseuid);
            }
        }
		asort($scourses);

        ////// "global" variables
        $dispOpt = array("classical"    => get_string('view_disp_classical', 'local_metaadmin'),
                        "bycourse"      => get_string('view_disp_bycourse', 'local_metaadmin'),
                        "bycoursebyaca" => get_string('view_disp_bycoursebyaca', 'local_metaadmin'));

        $yesno = array(0 => get_string('view_no', 'local_metaadmin'),
                        1 => get_string('view_yes', 'local_metaadmin'));

        $freqOpt = array("weekly" => get_string('view_weekly', 'local_metaadmin'),
                        "monthly" => get_string('view_monthly', 'local_metaadmin'));

        $labelDaysOpt = array(1 => get_string('view_monday', 'local_metaadmin'),
                            2 => get_string('view_tuesday', 'local_metaadmin'),
                            3 => get_string('view_wednesday', 'local_metaadmin'),
                            4 => get_string('view_thursday', 'local_metaadmin'),
                            5 => get_string('view_friday', 'local_metaadmin'),
                            6 => get_string('view_saturday', 'local_metaadmin'),
                            7 => get_string('view_sunday', 'local_metaadmin'));

        ////// 1st part: view's resume
        $mform->addElement('header','generalr', get_string('view_resume', 'local_metaadmin'));

        $mform->addElement('hidden', 'name', $view->view_name);
        $mform->setType('name', PARAM_TEXT);

        $mform->addElement('static', 'display_type', get_string('customviewdisplay'), $dispOpt[$view->display_type]);
        $mform->addElement('static', 'trainee_calc', get_string('customviewcalculation'), $yesno[$view->trainee_calc]);

        $attributes = array('disabled' => 'disabled', 'size' => 10);
        $mform->addElement('select', 'scourses', get_string('view_scourses', 'local_metaadmin'), $scourses, $attributes);
        $mform->addElement('static', 'send_report', get_string('customviewreport'), $yesno[$view->send_report]);

        if ($view->send_report) {
            $mform->addElement('static', 'frequency_report', get_string('customviewfrequency'), $freqOpt[$view->frequency_report]);

            if ($view->frequency_report == "weekly") {
                $mform->addElement('static', 'day_report', get_string('customviewday'), $labelDaysOpt[$view->day_report]);
            } else {
                $mform->addElement('static', 'day_report', get_string('customviewday'), $view->day_report);
            }
            $mform->addElement('static', 'emails', get_string('customviewemails'), $emails);
        }

        $objs = array();
        $objs[] =& $mform->createElement('submit', 'modview', get_string('view_modify', 'local_metaadmin'));
        $objs[] =& $mform->createElement('submit', 'delview', get_string('view_delete', 'local_metaadmin'));
        $grp =& $mform->addElement('group', 'viewbuttongrp', get_string('view_buttongrp', 'local_metaadmin'), $objs, array(' ', '<br />'), false);

        ////// 2nd part: form to make stats

        $mform->addElement('hidden', 'id', $viewId);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('header','general', get_string('view_createparcourssession', 'local_metaadmin'));
        $mform->addElement('hidden','lastconnmin','',array('id'=>'lastconnmin'));
        $mform->setType('lastconnmin', PARAM_INT);

        $attributes = array(
            'startyear' => 2000,
            'optional'  => false
        );
        $mform->addElement('date_selector', 'lastconnmin_dt', get_string('lastconnmin', 'local_metaadmin'), $attributes);
        $mform->addHelpButton('lastconnmin_dt', 'lastconnmin', 'local_metaadmin');
        $mform->setType('lastconnmin_dt', PARAM_INT);

        $starttime = mktime(0,0,0,9,1,date('Y'));
        if (time() < $starttime) {
            $starttime = mktime(0,0,0,9,1,date('Y')-1);
        }

        $mform->setDefault('lastconnmin_dt', $starttime);
        $mform->addElement('hidden','lastconnmax','', array('id'=>'lastconnmax'));
        $mform->setType('lastconnmax', PARAM_INT);

        $mform->addElement('date_selector', 'lastconnmax_dt', get_string('lastconnmax', 'local_metaadmin'), $attributes);
        $mform->addHelpButton('lastconnmax_dt', 'lastconnmax', 'local_metaadmin');
        $mform->setType('lastconnmax_dt', PARAM_INT);
        $mform->setDefault('lastconnmax_dt', time());

        $mform->addElement('html', '<div id="warning_archive_message" style="display: none;">'.get_string('warning_archive_message', 'local_metaadmin').'</div>');
        $origines_gaia = $DB->get_records('origine_gaia');
        $gaia_select = '';
        foreach($origines_gaia as $origine_gaia) {
            $gaia_select .= '<option value="'.$origine_gaia->id.'">'.$origine_gaia->code.'</option>';
        }

        $mform->addElement('static', 'parcoursidentifiant', get_string('parcoursidentifiant', 'local_metaadmin'),
            '<input size="2" name="parcoursidentifiant_year" id="parcoursidentifiant_year" maxlength="2" /> _ 
            <select name="gaia_origine" id="gaia_origine">'.$gaia_select.'</select> _ 
            <input size="40" name="parcoursidentifiant_name" id="parcoursidentifiant_name"/>');
        $mform->addHelpButton('parcoursidentifiant', 'parcoursidentifiant', 'local_metaadmin');

        $roles_shortname = array('participant','formateur','tuteur');
        $roles_query = $DB->get_records_sql("SELECT id, name, shortname FROM {role} WHERE shortname IN ('".implode("','",$roles_shortname)."')");
        $roles = array();
        foreach($roles_query as $role) {
            $roles[$role->shortname] = $role->name;
        }

        $mform->addElement('select', 'userrole', get_string('userrole', 'local_metaadmin'), $roles);

        $availablefromgroup=array();
        $availablefromgroup[] =& $mform->createElement('checkbox', 'select_ofp', '', 'Offre de parcours');
        $availablefromgroup[] =& $mform->createElement('checkbox', 'select_off', '', 'Offre de formation (hors parcours locaux)');
        $availablefromgroup[] =& $mform->createElement('checkbox', 'select_offlocales', '', 'Offre de formation locale');
        $mform->setDefault('select_off', 1);
        $mform->setDefault('select_ofp', 1);
        $mform->setDefault('select_offlocales', 1);

        $mform->addGroup($availablefromgroup, 'fitleroffer', '', array(' '), false);

        $mform->addHelpButton('userrole', 'userrole', 'local_metaadmin');
        $mform->setType('userrole', PARAM_INT);
        $mform->setDefault('userrole', time());

        $classarray = array('class' => 'form-submit');
        $buttonarray[] = &$mform->createElement('submit', 'saveanddisplay', get_string('showresults', 'local_metaadmin'), $classarray);
        $buttonarray[] = &$mform->createElement('button', 'b_export', get_string('html_export', 'local_metaadmin'));
        $buttonarray[] = &$mform->createElement('button', 'b_clipboard', get_string('copy_table', 'local_metaadmin'),array('class'=>'clipbutton','data-clipboard-text'=>''));
        $buttonarray[] = &$mform->createElement('button', 'b_downloadcsv', get_string('downloadcsv', 'local_metaadmin'));

        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');

        $mform->addElement('html', html_writer::script(false, new moodle_url('/local/metaadmin/js/clipboard.min.js')));

        $mform->addElement('html', '<br/><br/><style type="text/css">
        #region-main fieldset .fitemtitle .fgrouplabel {
            display: inline-block;
            width:210px !important;
            color: #000000 !important;
        }
        
        #region-main fieldset .fitemtitle label {
            display: inline-block;
            width: 180px !important;
        }
        		
        #region-main fieldset .fitemtitle{
            width: 210px !important;
    		display: inline-block !important;
        }
        
        #warning_archive_message {
            background-color: #fcf8e3;
            padding: 5px;
            text-align: center;
            border: 2px #fff2ba solid;
            margin: 15px 0;
        }

        </style>');
		
		if ($view->display_type == 'bycourse') {
			$html = '<div id="resultTable" style="width:100%"></div><div id="exportTable" style="display:none"></div>
			<script type="text/javascript">
$(function(){
        require(["local_magisterelib/jtable"],function(){

        	var downloading = false;
        	var sortorder = "academy ASC";
        	var clipboard = new Clipboard(".clipbutton");
        	
        	// TODO: empêche mes boutons modifier/supprimer la vue de fonctionner => ajouter condition sur $("#id_saveanddisplay") ?
        	//$("#mform1").submit(function(event) {
        	$("#id_saveanddisplay").on("click", function(event) {
                if (downloading===false) {
                    event.preventDefault();
                    $("#resultTable").jtable("load");
                }
			});
        	
        	function submit_csvform() {
        		downloading = true;
        		$("#mform1").attr("action","customview_statsparticipants_export.php?action=export&view_id='.$viewId.'&format=csv&so="+sortorder);
        		$("#lastconnmin").val((new Date($("#id_lastconnmin_dt_year").val(), $("#id_lastconnmin_dt_month").val()-1, $("#id_lastconnmin_dt_day").val())).getTime());
				$("#lastconnmax").val((new Date($("#id_lastconnmax_dt_year").val(), $("#id_lastconnmax_dt_month").val()-1, $("#id_lastconnmax_dt_day").val())).getTime());
        		$("#mform1").submit();
        		downloading = false;
        	}
        		
        	$("#id_b_downloadcsv").on("click",function(){submit_csvform();});
        	$("#id_b_export").on("click",function(){openExport();});
        	
			function openExport() {
        		$("#exportTable").dialog({modal:false,closeOnEscape: true,width: "600px"}).show();
			}
        		
        	function showExport() {
        		$("#exportTable").dialog({modal:false,closeOnEscape: true,width: "600px"}).show();
        	}
        		
        	function loadExport(execafter) {
        		var lastconnmin_val = (new Date($("#id_lastconnmin_dt_year").val(), $("#id_lastconnmin_dt_month").val()-1, $("#id_lastconnmin_dt_day").val())).getTime();
				var lastconnmax_val = (new Date($("#id_lastconnmax_dt_year").val(), $("#id_lastconnmax_dt_month").val()-1, $("#id_lastconnmax_dt_day").val())).getTime();
				var parcoursidentifiant_val = $("#id_parcoursidentifiant").val();
				postData = { 
				    lastconnmax: lastconnmax_val, 
				    lastconnmin: lastconnmin_val, 
				    parcoursidentifiant_year: $("#parcoursidentifiant_year").val(), 
				    gaia_origine: $("#gaia_origine").val(), 
				    parcoursidentifiant_name: $("#parcoursidentifiant_name").val(), 
				    userrole: $("#id_userrole").val(), 
				    view_id: '.$viewId.'  ,
				    select_no_pub : $("#id_select_no_pub").is(\':checked\') ? 1 : 0,
                    select_off : $("#id_select_off").is(\':checked\') ? 1 : 0,
                    select_offlocales : $("#id_select_offlocales").is(\':checked\') ? 1 : 0,
                    select_ofp : $("#id_select_ofp").is(\':checked\') ? 1 : 0  
                };
				$.ajax({
					url: "customview_statsparticipants_export.php?action=export&format=htmltsv&so=" + sortorder,
					type: "POST",
					data: postData,
					success: function (data) {
        				var res = data.split("######");
        				$("#exportTable").html(res[0]);
						$("#id_b_clipboard").attr("data-clipboard-text",res[1]);
        				if(execafter != undefined) {
        					execafter();
        		 		}
					}
				});
        	}
        	
        	$("#resultTable").jtable({
				title: "'.get_string('jttitle', 'local_metaadmin').'",
                paging: false,
				pageSize: 50,
                pageSizes: [50],
                selecting: false,
                multiselect: false,
                selectingCheckboxes: false,
				sorting: '.(has_capability('local/metaadmin:statsparticipants_viewallacademies', context_system::instance())?'true':'false').',
				defaultSorting: "academy ASC",
                jqueryuiTheme: true,
                defaultDateFormat: "dd-mm-yy",
                gotoPageArea: "none",
                selectOnRowClick: false,
				actions: {
                    listAction: function (postData, jtParams) {
                        return $.Deferred(function ($dfd) {
                            var lastconnmin_val = (new Date($("#id_lastconnmin_dt_year").val(), $("#id_lastconnmin_dt_month").val()-1, $("#id_lastconnmin_dt_day").val())).getTime();
        					var lastconnmax_val = (new Date($("#id_lastconnmax_dt_year").val(), $("#id_lastconnmax_dt_month").val()-1, $("#id_lastconnmax_dt_day").val())).getTime();
                            var parcoursidentifiant_val = $("#id_parcoursidentifiant").val();
        					sortorder = jtParams.jtSorting;
                            postData = { 
                                lastconnmax: lastconnmax_val, 
                                lastconnmin: lastconnmin_val, 
                                parcoursidentifiant_year: $("#parcoursidentifiant_year").val(), 
                                gaia_origine: $("#gaia_origine").val(), 
                                parcoursidentifiant_name: $("#parcoursidentifiant_name").val(),
                                userrole: $("#id_userrole").val(), 
                                view_id: '.$viewId.',
				                select_no_pub : $("#id_select_no_pub").is(\':checked\') ? 1 : 0,
                                select_off : $("#id_select_off").is(\':checked\') ? 1 : 0,
                                select_offlocales : $("#id_select_offlocales").is(\':checked\') ? 1 : 0,
                                select_ofp : $("#id_select_ofp").is(\':checked\') ? 1 : 0
                             };
                            
                            $.ajax({
                                url: " customview_statsparticipants_ajax.php?action=list&so=" + jtParams.jtSorting,
                                type: "POST",
                                dataType: "json",
                                data: postData,
                                success: function (data) {
        							loadExport();
                                    $dfd.resolve(data);
                                },
                                error: function () {
                                    $dfd.reject();
                                }
                            });
                        });
                    }
				},
				fields: {';
				if ($view->trainee_calc == 1) {
					$html .= '
					courseuid: {
						title: "'.get_string('jtheader_courseuid', 'local_metaadmin').'",
						width: "10%",
						create: false,
						edit: false,
						list: true
					},
					distanthours: {
						title: "'.get_string('jtheader_distanthours', 'local_metaadmin').'",
						width: "5%",
						create: false,
						edit: false,
						list: true
					},
					localhours: {
						title: "'.get_string('jtheader_localhours', 'local_metaadmin').'",
						width: "5%",
						create: false,
						edit: false,
						list: true
					},
					public1D: {
						title: "'.get_string('jtheader_public1D', 'local_metaadmin').'",
						width: "5%",
						create: false,
						edit: false,
						list: true
					},
					prive1D: {
						title: "'.get_string('jtheader_prive1D', 'local_metaadmin').'",
						width: "5%",
						create: false,
						edit: false,
						list: true
					},
					total1D: {
						title: "'.get_string('jtheader_total1D', 'local_metaadmin').'",
						width: "5%",
						create: false,
						edit: false,
						list: true
					},
					total1Dh: {
						title: "'.get_string('jtheader_total1Dh', 'local_metaadmin').'",
						width: "5%",
						create: false,
						edit: false,
						list: true
					},
					total1Dj: {
						title: "'.get_string('jtheader_total1Dj', 'local_metaadmin').'",
						width: "5%",
						create: false,
						edit: false,
						list: true
					},
					public2D: {
						title: "'.get_string('jtheader_public2D', 'local_metaadmin').'",
						width: "5%",
						create: false,
						edit: false,
						list: true
					},
					prive2D: {
						title: "'.get_string('jtheader_prive2D', 'local_metaadmin').'",
						width: "5%",
						create: false,
						edit: false,
						list: true
					},
					total2D: {
						title: "'.get_string('jtheader_total2D', 'local_metaadmin').'",
						width: "5%",
						create: false,
						edit: false,
						list: true
					},
					total2Dh: {
						title: "'.get_string('jtheader_total2Dh', 'local_metaadmin').'",
						width: "5%",
						create: false,
						edit: false,
						list: true
					},
					total2Dj: {
						title: "'.get_string('jtheader_total2Dj', 'local_metaadmin').'",
						width: "5%",
						create: false,
						edit: false,
						list: true
					},
					other: {
						title: "'.get_string('jtheader_other', 'local_metaadmin').'",
						width: "5%",
						create: false,
						edit: false,
						list: true
					},
					otherh: {
						title: "'.get_string('jtheader_otherh', 'local_metaadmin').'",
						width: "5%",
						create: false,
						edit: false,
						list: true
					},
					otherj: {
						title: "'.get_string('jtheader_otherj', 'local_metaadmin').'",
						width: "5%",
						create: false,
						edit: false,
						list: true
					},
					total: {
						title: "'.get_string('jtheader_total', 'local_metaadmin').'",
						width: "5%",
						create: false,
						edit: false,
						list: true
					},
					totalh: {
						title: "'.get_string('jtheader_totalh', 'local_metaadmin').'",
						width: "5%",
						create: false,
						edit: false,
						list: true
					},
					totalj: {
						title: "'.get_string('jtheader_totalj', 'local_metaadmin').'",
						width: "5%",
						create: false,
						edit: false,
						list: true
					}
					';
				} else {
					$html .= '
					courseuid: {
						title: "'.get_string('jtheader_courseuid', 'local_metaadmin').'",
						width: "20%",
						create: false,
						edit: false,
						list: true
					},
					public1D: {
						title: "'.get_string('jtheader_public1D', 'local_metaadmin').'",
						width: "10%",
						create: false,
						edit: false,
						list: true
					},
					prive1D: {
						title: "'.get_string('jtheader_prive1D', 'local_metaadmin').'",
						width: "10%",
						create: false,
						edit: false,
						list: true
					},
					total1D: {
						title: "'.get_string('jtheader_total1D', 'local_metaadmin').'",
						width: "10%",
						create: false,
						edit: false,
						list: true
					},
					public2D: {
						title: "'.get_string('jtheader_public2D', 'local_metaadmin').'",
						width: "10%",
						create: false,
						edit: false,
						list: true
					},
					prive2D: {
						title: "'.get_string('jtheader_prive2D', 'local_metaadmin').'",
						width: "10%",
						create: false,
						edit: false,
						list: true
					},
					total2D: {
						title: "'.get_string('jtheader_total2D', 'local_metaadmin').'",
						width: "10%",
						create: false,
						edit: false,
						list: true
					},
					other: {
						title: "'.get_string('jtheader_other', 'local_metaadmin').'",
						width: "10%",
						create: false,
						edit: false,
						list: true
					},
					total: {
						title: "'.get_string('jtheader_total', 'local_metaadmin').'",
						width: "10%",
						create: false,
						edit: false,
						list: true
					}';
				}
					$html .= '
				}
			});

			//Load person list from server
			$("#resultTable").jtable("load");
        	
        	$("#id_lastconnmin_dt_year, #id_lastconnmin_dt_month, #id_lastconnmax_dt_year, #id_lastconnmax_dt_month").change(function(){
        	    var year = parseInt($("#id_lastconnmin_dt_year").val()),
        	        month = parseInt($("#id_lastconnmin_dt_month").val()-1),
        	        today = new Date(),
        	        currentYear = today.getFullYear();
        	        
        	    if(today.getMonth() < 9){
        	        currentYear--;
        	    }
        	    
        	    if(month < 9){
        	        year--;
        	    }
        	    
        	    if(currentYear <= year){
        	        $("#warning_archive_message").hide();
        	    }else if(year < currentYear){
        	        $("#warning_archive_message").show();
        	    }
        	});
});
});
	        </script>';
			
			$mform->addElement('html', $html);
		} else if ($view->display_type == 'bycoursebyaca') {
			$html = '<div id="exportTable" style="display:none"></div><script type="text/javascript">
$(function(){
        require(["local_magisterelib/jtable"],function(){

        	var downloading = false;
        	var sortorder = "academy ASC";
        	var clipboard = new Clipboard(".clipbutton");
        	
        	function submit_csvform() {
        		downloading = true;
        		$("#mform1").attr("action","customview_statsparticipants_export.php?action=export&view_id='.$viewId.'&format=csv&so="+sortorder);
        		$("#lastconnmin").val((new Date($("#id_lastconnmin_dt_year").val(), $("#id_lastconnmin_dt_month").val()-1, $("#id_lastconnmin_dt_day").val())).getTime());
				$("#lastconnmax").val((new Date($("#id_lastconnmax_dt_year").val(), $("#id_lastconnmax_dt_month").val()-1, $("#id_lastconnmax_dt_day").val())).getTime());
        		$("#mform1").submit();
        		downloading = false;
        	}
        		
        	$("#id_b_downloadcsv").on("click",function(){submit_csvform();});
        	$("#id_b_export").on("click",function(){openExport();});
        	
			function openExport() {
        		$("#exportTable").dialog({modal:false,closeOnEscape: true,width: "875px"}).show();
			}
        		
        	function showExport() {
        		$("#exportTable").dialog({modal:false,closeOnEscape: true,width: "875px"}).show();
        	}
        		
        	function loadExport(execafter) {
        		var lastconnmin_val = (new Date($("#id_lastconnmin_dt_year").val(), $("#id_lastconnmin_dt_month").val()-1, $("#id_lastconnmin_dt_day").val())).getTime();
				var lastconnmax_val = (new Date($("#id_lastconnmax_dt_year").val(), $("#id_lastconnmax_dt_month").val()-1, $("#id_lastconnmax_dt_day").val())).getTime();
				var parcoursidentifiant_val = $("#id_parcoursidentifiant").val();
				postData = { 
				    lastconnmax: lastconnmax_val, 
				    lastconnmin: lastconnmin_val, 
				    parcoursidentifiant_year: $("#parcoursidentifiant_year").val(), 
				    gaia_origine: $("#gaia_origine").val(), 
				    parcoursidentifiant_name: $("#parcoursidentifiant_name").val(), 
				    userrole: $("#id_userrole").val(), 
				    view_id: '.$viewId.',
				    select_no_pub : $("#id_select_no_pub").is(\':checked\') ? 1 : 0,
                    select_off : $("#id_select_off").is(\':checked\') ? 1 : 0,
                    select_offlocales : $("#id_select_offlocales").is(\':checked\') ? 1 : 0,
                    select_ofp : $("#id_select_ofp").is(\':checked\') ? 1 : 0  
                };
                
				$.ajax({
					url: "customview_statsparticipants_export.php?action=export&format=htmltsv&so=" + sortorder,
					type: "POST",
					data: postData,
					success: function (data) {
        				var res = data.split("######");
        				$("#exportTable").html(res[0]);
						$("#id_b_clipboard").attr("data-clipboard-text",res[1]);
        				if(execafter != undefined) {
        					execafter();
        		 		}
					}
				});
        	}
			';
			
			$academies = get_magistere_academy_config();
			$saveanddisplay_button = '';
			$jtable_divs = '';
			$special_aca = array('reseau-canope','dgesco','efe','ih2ef','dne-foad');
			
			foreach($academies AS $academy_name=>$academy)
			{
				if (substr($academy_name,0,3) != 'ac-') {
					continue;
				}
				if (in_array($academy_name, $special_aca)) {
					continue;
				}
				
				$jtable_divs .= '<div id="resultTable_'.$academy_name.'" style="width:100%"></div>';
				$saveanddisplay_button .= '$("#resultTable_'.$academy_name.'").jtable("load");';
				
				$html .= '$("#resultTable_'.$academy_name.'").jtable({
				title: "'.get_string('jttitle', 'local_metaadmin').' pour '.$academy_name.'",
                paging: false,
				pageSize: 50,
                pageSizes: [50],
                selecting: false,
                multiselect: false,
                selectingCheckboxes: false,
				sorting: '.(has_capability('local/metaadmin:statsparticipants_viewallacademies', context_system::instance())?'true':'false').',
				defaultSorting: "academy ASC",
                jqueryuiTheme: true,
                defaultDateFormat: "dd-mm-yy",
                gotoPageArea: "none",
                selectOnRowClick: false,
				actions: {
                    listAction: function (postData, jtParams) {
                        return $.Deferred(function ($dfd) {
                            var lastconnmin_val = (new Date($("#id_lastconnmin_dt_year").val(), $("#id_lastconnmin_dt_month").val()-1, $("#id_lastconnmin_dt_day").val())).getTime();
        					var lastconnmax_val = (new Date($("#id_lastconnmax_dt_year").val(), $("#id_lastconnmax_dt_month").val()-1, $("#id_lastconnmax_dt_day").val())).getTime();
                            var parcoursidentifiant_val = $("#id_parcoursidentifiant").val();
        					sortorder = jtParams.jtSorting;
                            postData = { 
                                lastconnmax: lastconnmax_val, 
                                lastconnmin: lastconnmin_val, 
                                parcoursidentifiant_year: $("#parcoursidentifiant_year").val(), 
                                gaia_origine: $("#gaia_origine").val(), 
                                parcoursidentifiant_name: $("#parcoursidentifiant_name").val(), 
                                userrole: $("#id_userrole").val(), 
                                view_id: '.$viewId.' ,
				                select_no_pub : $("#id_select_no_pub").is(\':checked\') ? 1 : 0,
                                select_off : $("#id_select_off").is(\':checked\') ? 1 : 0,
                                select_offlocales : $("#id_select_offlocales").is(\':checked\') ? 1 : 0,
                                select_ofp : $("#id_select_ofp").is(\':checked\') ? 1 : 0  
                            };
                            
                            $.ajax({
                                url: " customview_statsparticipants_ajax.php?action=list&aca='.$academy_name.'&so=" + jtParams.jtSorting,
                                type: "POST",
                                dataType: "json",
                                data: postData,
                                success: function (data) {
                                    $dfd.resolve(data);
                                },
                                error: function () {
                                    $dfd.reject();
                                }
                            });
                        });
                    }
				},
				fields: {';
					if ($view->trainee_calc == 1) {
					$html .= '
					courseuid: {
						title: "'.get_string('jtheader_courseuid', 'local_metaadmin').'",
						width: "20%",
						create: false,
						edit: false,
						list: true
					},
					distanthours: {
						title: "'.get_string('jtheader_distanthours', 'local_metaadmin').'",
						width: "10%",
						create: false,
						edit: false,
						list: true
					},
					localhours: {
						title: "'.get_string('jtheader_localhours', 'local_metaadmin').'",
						width: "10%",
						create: false,
						edit: false,
						list: true
					},
					public1D: {
						title: "'.get_string('jtheader_public1D', 'local_metaadmin').'",
						width: "10%",
						create: false,
						edit: false,
						list: true
					},
					prive1D: {
						title: "'.get_string('jtheader_prive1D', 'local_metaadmin').'",
						width: "10%",
						create: false,
						edit: false,
						list: true
					},
					total1D: {
						title: "'.get_string('jtheader_total1D', 'local_metaadmin').'",
						width: "10%",
						create: false,
						edit: false,
						list: true
					},
					total1Dh: {
						title: "'.get_string('jtheader_total1Dh', 'local_metaadmin').'",
						width: "10%",
						create: false,
						edit: false,
						list: true
					},
					total1Dj: {
						title: "'.get_string('jtheader_total1Dj', 'local_metaadmin').'",
						width: "10%",
						create: false,
						edit: false,
						list: true
					},
					public2D: {
						title: "'.get_string('jtheader_public2D', 'local_metaadmin').'",
						width: "10%",
						create: false,
						edit: false,
						list: true
					},
					prive2D: {
						title: "'.get_string('jtheader_prive2D', 'local_metaadmin').'",
						width: "10%",
						create: false,
						edit: false,
						list: true
					},
					total2D: {
						title: "'.get_string('jtheader_total2D', 'local_metaadmin').'",
						width: "10%",
						create: false,
						edit: false,
						list: true
					},
					total2Dh: {
						title: "'.get_string('jtheader_total2Dh', 'local_metaadmin').'",
						width: "10%",
						create: false,
						edit: false,
						list: true
					},
					total2Dj: {
						title: "'.get_string('jtheader_total2Dj', 'local_metaadmin').'",
						width: "10%",
						create: false,
						edit: false,
						list: true
					},
					other: {
						title: "'.get_string('jtheader_other', 'local_metaadmin').'",
						width: "10%",
						create: false,
						edit: false,
						list: true
					},
					otherh: {
						title: "'.get_string('jtheader_otherh', 'local_metaadmin').'",
						width: "10%",
						create: false,
						edit: false,
						list: true
					},
					otherj: {
						title: "'.get_string('jtheader_otherj', 'local_metaadmin').'",
						width: "10%",
						create: false,
						edit: false,
						list: true
					},
					total: {
						title: "'.get_string('jtheader_total', 'local_metaadmin').'",
						width: "10%",
						create: false,
						edit: false,
						list: true
					},
					totalh: {
						title: "'.get_string('jtheader_totalh', 'local_metaadmin').'",
						width: "10%",
						create: false,
						edit: false,
						list: true
					},
					totalj: {
						title: "'.get_string('jtheader_totalj', 'local_metaadmin').'",
						width: "10%",
						create: false,
						edit: false,
						list: true
					}
					';
				} else {
					$html .= 'courseuid: {
						title: "'.get_string('jtheader_courseuid', 'local_metaadmin').'",
						width: "20%",
						create: false,
						edit: false,
						list: true
					},
					public1D: {
						title: "'.get_string('jtheader_public1D', 'local_metaadmin').'",
						width: "10%",
						create: false,
						edit: false,
						list: true
					},
					prive1D: {
						title: "'.get_string('jtheader_prive1D', 'local_metaadmin').'",
						width: "10%",
						create: false,
						edit: false,
						list: true
					},
					total1D: {
						title: "'.get_string('jtheader_total1D', 'local_metaadmin').'",
						width: "10%",
						create: false,
						edit: false,
						list: true
					},
					public2D: {
						title: "'.get_string('jtheader_public2D', 'local_metaadmin').'",
						width: "10%",
						create: false,
						edit: false,
						list: true
					},
					prive2D: {
						title: "'.get_string('jtheader_prive2D', 'local_metaadmin').'",
						width: "10%",
						create: false,
						edit: false,
						list: true
					},
					total2D: {
						title: "'.get_string('jtheader_total2D', 'local_metaadmin').'",
						width: "10%",
						create: false,
						edit: false,
						list: true
					},
					other: {
						title: "'.get_string('jtheader_other', 'local_metaadmin').'",
						width: "10%",
						create: false,
						edit: false,
						list: true
					},
					total: {
						title: "'.get_string('jtheader_total', 'local_metaadmin').'",
						width: "10%",
						create: false,
						edit: false,
						list: true
					}';
				}
				$html .= '
				}
			});
			
			';
			}
			
        	$html .= '$("#id_saveanddisplay").on("click", function(event) {
                if (downloading===false) {
                    event.preventDefault();
                    '.$saveanddisplay_button.'
					loadExport();
                }
			});
			
        	$("#id_lastconnmin_dt_year, #id_lastconnmin_dt_month, #id_lastconnmax_dt_year, #id_lastconnmax_dt_month").change(function(){
        	    var year = parseInt($("#id_lastconnmin_dt_year").val()),
        	        month = parseInt($("#id_lastconnmin_dt_month").val()-1),
        	        today = new Date(),
        	        currentYear = today.getFullYear();
        	        
        	    if(today.getMonth() < 9){
        	        currentYear--;
        	    }
        	    
        	    if(month < 9){
        	        year--;
        	    }
        	    
        	    if(currentYear <= year){
        	        $("#warning_archive_message").hide();
        	    }else if(year < currentYear){
        	        $("#warning_archive_message").show();
        	    }
        	});
			
			'.$saveanddisplay_button.'
			loadExport();
});
});
	        </script>';
			
			$mform->addElement('html', $jtable_divs);
			$mform->addElement('html', $html);
		} else {
			$html = '<div id="resultTable" style="width:100%"></div><div id="exportTable" style="display:none"></div>
			<script type="text/javascript">
$(function(){
        require(["local_magisterelib/jtable"],function(){
        	var downloading = false;
        	var sortorder = "academy ASC";
        	var clipboard = new Clipboard(".clipbutton");
        	
        	$("#id_saveanddisplay").on("click", function(event) {
                if (downloading===false) {
                    event.preventDefault();
                    $("#resultTable").jtable("load");
                }
			});
        	
        	function submit_csvform() {
        		downloading = true;
        		$("#mform1").attr("action","customview_statsparticipants_export.php?action=export&view_id='.$viewId.'&format=csv&so="+sortorder);
        		$("#lastconnmin").val((new Date($("#id_lastconnmin_dt_year").val(), $("#id_lastconnmin_dt_month").val()-1, $("#id_lastconnmin_dt_day").val())).getTime());
				$("#lastconnmax").val((new Date($("#id_lastconnmax_dt_year").val(), $("#id_lastconnmax_dt_month").val()-1, $("#id_lastconnmax_dt_day").val())).getTime());
        		$("#mform1").submit();
        		downloading = false;
        	}
        		
        	$("#id_b_downloadcsv").on("click",function(){submit_csvform();});
        	$("#id_b_export").on("click",function(){openExport();});
        	
			function openExport() {
        		$("#exportTable").dialog({modal:false,closeOnEscape: true,width: "600px"}).show();
			}
        		
        	function showExport() {
        		$("#exportTable").dialog({modal:false,closeOnEscape: true,width: "600px"}).show();
        	}
        		
        	function loadExport(execafter) {
        		var lastconnmin_val = (new Date($("#id_lastconnmin_dt_year").val(), $("#id_lastconnmin_dt_month").val()-1, $("#id_lastconnmin_dt_day").val())).getTime();
				var lastconnmax_val = (new Date($("#id_lastconnmax_dt_year").val(), $("#id_lastconnmax_dt_month").val()-1, $("#id_lastconnmax_dt_day").val())).getTime();
				var parcoursidentifiant_val = $("#id_parcoursidentifiant").val();
				postData = { 
				    lastconnmax: lastconnmax_val, 
				    lastconnmin: lastconnmin_val, 
				    parcoursidentifiant_year: $("#parcoursidentifiant_year").val(), 
				    gaia_origine: $("#gaia_origine").val(),
				    parcoursidentifiant_name: $("#parcoursidentifiant_name").val(), 
				    userrole: $("#id_userrole").val(), 
				    view_id: '.$viewId.',
				    select_no_pub : $("#id_select_no_pub").is(\':checked\') ? 1 : 0,
                    select_off : $("#id_select_off").is(\':checked\') ? 1 : 0,
                    select_offlocales : $("#id_select_offlocales").is(\':checked\') ? 1 : 0,
                    select_ofp : $("#id_select_ofp").is(\':checked\') ? 1 : 0  
                };
				$.ajax({
					url: "customview_statsparticipants_export.php?action=export&format=htmltsv&so=" + sortorder,
					type: "POST",
					data: postData,
					success: function (data) {
        				var res = data.split("######");
        				$("#exportTable").html(res[0]);
						$("#id_b_clipboard").attr("data-clipboard-text",res[1]);
        				if(execafter != undefined) {
        					execafter();
        		 		}
					}
				});
        	}
        	
        	$("#resultTable").jtable({
				title: "'.get_string('jttitle', 'local_metaadmin').'",
                paging: false,
				pageSize: 50,
                pageSizes: [50],
                selecting: false,
                multiselect: false,
                selectingCheckboxes: false,
				sorting: '.(has_capability('local/metaadmin:statsparticipants_viewallacademies', context_system::instance())?'true':'false').',
				defaultSorting: "academy ASC",
                jqueryuiTheme: true,
                defaultDateFormat: "dd-mm-yy",
                gotoPageArea: "none",
                selectOnRowClick: false,
				actions: {
                    listAction: function (postData, jtParams) {
                        return $.Deferred(function ($dfd) {
                            var lastconnmin_val = (new Date($("#id_lastconnmin_dt_year").val(), $("#id_lastconnmin_dt_month").val()-1, $("#id_lastconnmin_dt_day").val())).getTime();
        					var lastconnmax_val = (new Date($("#id_lastconnmax_dt_year").val(), $("#id_lastconnmax_dt_month").val()-1, $("#id_lastconnmax_dt_day").val())).getTime();
                            var parcoursidentifiant_val = $("#id_parcoursidentifiant").val();
        					sortorder = jtParams.jtSorting;
                            postData = { 
                                lastconnmax: lastconnmax_val, 
                                lastconnmin: lastconnmin_val, 
                                parcoursidentifiant_year: $("#parcoursidentifiant_year").val(), 
                                gaia_origine: $("#gaia_origine").val(), 
                                parcoursidentifiant_name: $("#parcoursidentifiant_name").val(), 
                                userrole: $("#id_userrole").val(), 
                                view_id: '.$viewId.',
				                select_no_pub : $("#id_select_no_pub").is(\':checked\') ? 1 : 0,
                                select_off : $("#id_select_off").is(\':checked\') ? 1 : 0,
                                select_offlocales : $("#id_select_offlocales").is(\':checked\') ? 1 : 0,
                                select_ofp : $("#id_select_ofp").is(\':checked\') ? 1 : 0  
                            };
                            
                            $.ajax({
                                url: " customview_statsparticipants_ajax.php?action=list&so=" + jtParams.jtSorting,
                                type: "POST",
                                dataType: "json",
                                data: postData,
                                success: function (data) {
        							loadExport();
                                    $dfd.resolve(data);
                                },
                                error: function () {
                                    $dfd.reject();
                                }
                            });
                        });
                    }
				},
				fields: {
					';
				if ($view->trainee_calc == 1) {
					$html .= 'academy: {
						title: "'.get_string('jtheader_academy', 'local_metaadmin').'",
						width: "20%",
						create: false,
						edit: false,
						list: true
					},
					distanthours: {
						title: "'.get_string('jtheader_distanthours', 'local_metaadmin').'",
						width: "10%",
						create: false,
						edit: false,
						list: true
					},
					localhours: {
						title: "'.get_string('jtheader_localhours', 'local_metaadmin').'",
						width: "10%",
						create: false,
						edit: false,
						list: true
					},
					public1D: {
						title: "'.get_string('jtheader_public1D', 'local_metaadmin').'",
						width: "10%",
						create: false,
						edit: false,
						list: true
					},
					prive1D: {
						title: "'.get_string('jtheader_prive1D', 'local_metaadmin').'",
						width: "10%",
						create: false,
						edit: false,
						list: true
					},
					total1D: {
						title: "'.get_string('jtheader_total1D', 'local_metaadmin').'",
						width: "10%",
						create: false,
						edit: false,
						list: true
					},
					total1Dh: {
						title: "'.get_string('jtheader_total1Dh', 'local_metaadmin').'",
						width: "10%",
						create: false,
						edit: false,
						list: true
					},
					total1Dj: {
						title: "'.get_string('jtheader_total1Dj', 'local_metaadmin').'",
						width: "10%",
						create: false,
						edit: false,
						list: true
					},
					public2D: {
						title: "'.get_string('jtheader_public2D', 'local_metaadmin').'",
						width: "10%",
						create: false,
						edit: false,
						list: true
					},
					prive2D: {
						title: "'.get_string('jtheader_prive2D', 'local_metaadmin').'",
						width: "10%",
						create: false,
						edit: false,
						list: true
					},
					total2D: {
						title: "'.get_string('jtheader_total2D', 'local_metaadmin').'",
						width: "10%",
						create: false,
						edit: false,
						list: true
					},
					total2Dh: {
						title: "'.get_string('jtheader_total2Dh', 'local_metaadmin').'",
						width: "10%",
						create: false,
						edit: false,
						list: true
					},
					total2Dj: {
						title: "'.get_string('jtheader_total2Dj', 'local_metaadmin').'",
						width: "10%",
						create: false,
						edit: false,
						list: true
					},
					other: {
						title: "'.get_string('jtheader_other', 'local_metaadmin').'",
						width: "10%",
						create: false,
						edit: false,
						list: true
					},
					otherh: {
						title: "'.get_string('jtheader_otherh', 'local_metaadmin').'",
						width: "10%",
						create: false,
						edit: false,
						list: true
					},
					otherj: {
						title: "'.get_string('jtheader_otherj', 'local_metaadmin').'",
						width: "10%",
						create: false,
						edit: false,
						list: true
					},
					total: {
						title: "'.get_string('jtheader_total', 'local_metaadmin').'",
						width: "10%",
						create: false,
						edit: false,
						list: true
					},
					totalh: {
						title: "'.get_string('jtheader_totalh', 'local_metaadmin').'",
						width: "10%",
						create: false,
						edit: false,
						list: true
					},
					totalj: {
						title: "'.get_string('jtheader_totalj', 'local_metaadmin').'",
						width: "10%",
						create: false,
						edit: false,
						list: true
					}
					';
				} else {
					$html .= 'academy: {
						title: "'.get_string('jtheader_academy', 'local_metaadmin').'",
						width: "20%",
						create: false,
						edit: false,
						list: true
					},
					public1D: {
						title: "'.get_string('jtheader_public1D', 'local_metaadmin').'",
						width: "10%",
						create: false,
						edit: false,
						list: true
					},
					prive1D: {
						title: "'.get_string('jtheader_prive1D', 'local_metaadmin').'",
						width: "10%",
						create: false,
						edit: false,
						list: true
					},
					total1D: {
						title: "'.get_string('jtheader_total1D', 'local_metaadmin').'",
						width: "10%",
						create: false,
						edit: false,
						list: true
					},
					public2D: {
						title: "'.get_string('jtheader_public2D', 'local_metaadmin').'",
						width: "10%",
						create: false,
						edit: false,
						list: true
					},
					prive2D: {
						title: "'.get_string('jtheader_prive2D', 'local_metaadmin').'",
						width: "10%",
						create: false,
						edit: false,
						list: true
					},
					total2D: {
						title: "'.get_string('jtheader_total2D', 'local_metaadmin').'",
						width: "10%",
						create: false,
						edit: false,
						list: true
					},
					other: {
						title: "'.get_string('jtheader_other', 'local_metaadmin').'",
						width: "10%",
						create: false,
						edit: false,
						list: true
					},
					total: {
						title: "'.get_string('jtheader_total', 'local_metaadmin').'",
						width: "10%",
						create: false,
						edit: false,
						list: true
					}';
				}
				$html .= '
				}
			});

			//Load person list from server
			$("#resultTable").jtable("load");
        	
        	$("#id_lastconnmin_dt_year, #id_lastconnmin_dt_month, #id_lastconnmax_dt_year, #id_lastconnmax_dt_month").change(function(){
        	    var year = parseInt($("#id_lastconnmin_dt_year").val()),
        	        month = parseInt($("#id_lastconnmin_dt_month").val()-1),
        	        today = new Date(),
        	        currentYear = today.getFullYear();
        	        
        	    if(today.getMonth() < 9){
        	        currentYear--;
        	    }
        	    
        	    if(month < 9){
        	        year--;
        	    }
        	    
        	    if(currentYear <= year){
        	        $("#warning_archive_message").hide();
        	    }else if(year < currentYear){
        	        $("#warning_archive_message").show();
        	    }
        	});
});
});
	        </script>';
			
			$mform->addElement('html', $html);
		}
    }

    function getAllID() {
        global $CFG;
        return array('test_id_01'=>'test_id_01','test_id_02'=>'test_id_02','test_id_03'=>'test_id_03','test_id_04'=>'test_id_04');
        $academies = get_magistere_academy_config();
        $special_aca = array('reseau-canope','dgesco','efe','ih2ef','hub','dne-foad');
        $pids = array();
        foreach ($academies as $academy_name=>$aca_data) {
            if (substr($academy_name,0,3) != 'ac-' && !in_array($academy_name,$special_aca)) {
                continue;
            }

            unset($acaDB);
            if (($acaDB = databaseConnection::instance()->get($academy_name)) === false){
                error_log('customview_statsparticipants.php/getAllID()/'.$academy_name.'/Database_connection_failed');
                continue;
            }
            $aca_query = "SELECT DISTINCT CONCAT(im.year, '_', lic.code, '_', im.title, '_', li.version) as course_identification 
FROM {local_indexation} li 
INNER JOIN ".$CFG->centralized_dbname.".local_indexation_codes lic ON lic.id=li.codeorigineid
WHERE li.year IS NOT NULL AND li.year != ''
AND lic.code IS NOT NULL
AND li.title IS NOT NULL AND li.title != ''
AND li.version IS NOT NULL AND li.version != ''";
            $aca_result = $acaDB->get_records_sql($aca_query);

            foreach($aca_result as $value) {
                $pids[$value->course_identification] = $value->course_identification;
            }
        }
        return $pids;
    }

    /**
     * Fill in the current page data for this course.
     */
    function definition_after_data() {
    }

    /**
     * Validation.
     *
     * @param array $data
     * @param array $files
     * @return array the errors that were found
     */
    function validation($data, $files) {
        global $DB;
        $errors = parent::validation($data, $files);
        return $errors;
    }
}

