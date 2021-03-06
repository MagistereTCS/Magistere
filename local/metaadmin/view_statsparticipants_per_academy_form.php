<?php

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir.'/formslib.php');

require_once($CFG->dirroot.'/local/magisterelib/databaseConnection.php');

class view_statsparticipants_per_academy_form extends moodleform {
    protected $course;
    protected $context;

    /**
     * Form definition.
     */
    function definition() {
        global $CFG, $PAGE, $DB;

        $mform = $this->_form;

        //$courseid = $this->_customdata['courseid'];
        

        //$this->context = $context;

        // Form definition with new course defaults.
        $mform->addElement('header','general', get_string('createparcourssession', 'local_metaadmin'));
        
        $attributes = array(
        		'startyear' => 2000,
        		'optional'  => false
        );
        
        //$mform->addElement('static', 'sessionstartdate', '<label>'.get_string('sessionstartdate', 'block_course_management').'</label>', '<span id="sessionstartdate_value">'.date('d/m/Y').'</span>');
        //$mform->addHelpButton('sessionstartdate', 'sessionstartdate_parcours', 'block_course_management');
        
        $mform->addElement('hidden','lastconnmin','',array('id'=>'lastconnmin'));
        $mform->setType('lastconnmin', PARAM_INT);
        $mform->addElement('date_selector', 'lastconnmin_dt', get_string('lastconnmin', 'local_metaadmin'), $attributes);
        $mform->addHelpButton('lastconnmin_dt', 'lastconnmin', 'local_metaadmin');
        $mform->setType('lastconnmin_dt', PARAM_INT);
        
        $starttime = mktime(0,0,0,9,1,date('Y'));
        if (time() < $starttime)
        {
        	$starttime = mktime(0,0,0,9,1,date('Y')-1);
        }
        
        $mform->setDefault('lastconnmin_dt', $starttime);
        
        $mform->addElement('hidden','lastconnmax','',array('id'=>'lastconnmax'));
        $mform->setType('lastconnmax', PARAM_INT);
        $mform->addElement('date_selector', 'lastconnmax_dt', get_string('lastconnmax', 'local_metaadmin'), $attributes);
        $mform->addHelpButton('lastconnmax_dt', 'lastconnmax', 'local_metaadmin');
        $mform->setType('lastconnmax_dt', PARAM_INT);
        $mform->setDefault('lastconnmax_dt', time());

        /*
        $data = $this->getAllID();
        
        $mform->addElement('select', 'parcoursidentifiant', get_string('parcoursidentifiant', 'local_metaadmin'), $data, $attributes);
        $mform->addHelpButton('parcoursidentifiant', 'parcoursidentifiant', 'local_metaadmin');
        $mform->addRule('parcoursidentifiant', '', 'required', null, 'server');
        $mform->setType('parcoursidentifiant', PARAM_INT);
        $mform->setDefault('parcoursidentifiant', time());
        */
		$mform->addElement('html', '<div id="warning_archive_message" style="display: none;">'.get_string('warning_archive_message', 'local_metaadmin').'</div>');

        $origines_gaia = $DB->get_records('origine_gaia');
        $gaia_select = '';
        foreach($origines_gaia as $origine_gaia)
        {
        	$gaia_select .= '<option value="'.$origine_gaia->id.'">'.$origine_gaia->code.'</option>';
        }
        
        $mform->addElement('static', 'parcoursidentifiant', get_string('parcoursidentifiant', 'local_metaadmin'), '<input size="2" name="parcoursidentifiant_year" id="parcoursidentifiant_year" maxlength="2" /> _ <select name="gaia_origine" id="gaia_origine">'.$gaia_select.'</select> _ <input size="40" name="parcoursidentifiant_name" id="parcoursidentifiant_name"/>');
        $mform->addHelpButton('parcoursidentifiant', 'parcoursidentifiant', 'local_metaadmin');
		
        $roles_shortname = array('participant','formateur','tuteur');
        
        $roles_query = $DB->get_records_sql("SELECT id, name, shortname FROM {role} WHERE shortname IN ('".implode("','",$roles_shortname)."')");
        
        $roles = array();
        foreach($roles_query as $role)
        {
        	$roles[$role->shortname] = $role->name;
        }
        
        $mform->addElement('select', 'userrole', get_string('userrole', 'local_metaadmin'), $roles);
        $mform->addHelpButton('userrole', 'userrole', 'local_metaadmin');
        $mform->setType('userrole', PARAM_INT);
        $mform->setDefault('userrole', time());

        $availablefromgroup=array();
        $availablefromgroup[] =& $mform->createElement('checkbox', 'select_no_pub', '', 'Parcours non publiés');
        $availablefromgroup[] =& $mform->createElement('checkbox', 'select_ofp', '', 'Offre de parcours');
        $availablefromgroup[] =& $mform->createElement('checkbox', 'select_off', '', 'Offre de formation (hors parcours locaux)');
        $availablefromgroup[] =& $mform->createElement('checkbox', 'select_offlocales', '', 'Offre de formation locale');
        $mform->setDefault('select_off', 1);
        $mform->setDefault('select_ofp', 1);
        $mform->setDefault('select_no_pub', 1);
        $mform->setDefault('select_offlocales', 1);

        $mform->addGroup($availablefromgroup, 'fitleroffer', '', array(' '), false);

        // When two elements we need a group.
        //$buttonarray = array();
        $classarray = array('class' => 'form-submit');
       
        $buttonarray[] = &$mform->createElement('submit', 'saveanddisplay', get_string('showresults', 'local_metaadmin'), $classarray);
        $buttonarray[] = &$mform->createElement('button', 'b_export', get_string('html_export', 'local_metaadmin'));
        
        $buttonarray[] = &$mform->createElement('button', 'b_clipboard', get_string('copy_table', 'local_metaadmin'),array('class'=>'clipbutton','data-clipboard-text'=>''));
        
        $buttonarray[] = &$mform->createElement('button', 'b_downloadcsv', get_string('downloadcsv', 'local_metaadmin'));
        
        //$buttonarray[] = &$mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
        
        $mform->addElement('html', html_writer::script(false, new moodle_url('/local/metaadmin/js/clipboard.min.js')));
        
        
        $mform->addElement('html', '<br/><br/>
		<div class="result-header">'.get_string('jttitle', 'local_metaadmin').'</div>
		<style type="text/css">
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
         #resultTable tbody tr td:first-of-type {
            background-color: #EEEEEE  !important;
                border: 1px solid #d3d3d3 !important;
         }
         
         #resultTable tbody tr:nth-child(even) {
            background-color: #f9f9f9  !important;
   
         }
        </style>
<div id="resultTable" style="width:100%"></div><div id="exportTable" style="display:none"></div>');
        $cpp=0;
        $jtableheader = '"name": {title: "Académie",create: false, edit: false, list: true},';
        foreach($CFG->academylist as $aca => $data){
			if($aca != 'frontal'){
				$jtableheader .= '"'.$aca.'": {
					title: "'.$data['name'].'",
					create: false,
					edit: false,
					list: true
					
				},';
				$cpp ++;
			}
        }

        $mform->addElement('html', '<script type="text/javascript">
$(function(){
        require(["local_magisterelib/jtable"],function(){
        	var downloading = false;
        	var sortorder = "academy ASC";
        	
        	var clipboard = new Clipboard(".clipbutton");
        	
        	$("#mform1").submit(function(event) {
        		if (downloading===false)
        		{
        			event.preventDefault();
        			$("#resultTable").jtable("load");
        		}
			});
        	
        	function submit_csvform()
        	{
        		downloading = true;
        		$("#mform1").attr("action","view_statsparticipants_per_academy_export.php?action=export&format=csv&so="+sortorder);
        		
        		$("#lastconnmin").val((new Date($("#id_lastconnmin_dt_year").val(), $("#id_lastconnmin_dt_month").val()-1, $("#id_lastconnmin_dt_day").val())).getTime());
				$("#lastconnmax").val((new Date($("#id_lastconnmax_dt_year").val(), $("#id_lastconnmax_dt_month").val()-1, $("#id_lastconnmax_dt_day").val())).getTime());
        		
        		$("#mform1").submit();
        		downloading = false;
        	}
        		
        	$("#id_b_downloadcsv").on("click",function(){submit_csvform();});
        		
        	$("#id_b_export").on("click",function(){openExport();});
        	
			function openExport(){
				/*loadExport(showExport);*/
        		$("#exportTable").dialog({modal:false,closeOnEscape: true,width: "600px"}).show();
			}
        		
        	function showExport()
        	{
        		$("#exportTable").dialog({modal:false,closeOnEscape: true,width: "600px"}).show();
        	}
        		
        	function loadExport(execafter)
        	{
        		var lastconnmin_val = (new Date($("#id_lastconnmin_dt_year").val(), $("#id_lastconnmin_dt_month").val()-1, $("#id_lastconnmin_dt_day").val())).getTime();
				var lastconnmax_val = (new Date($("#id_lastconnmax_dt_year").val(), $("#id_lastconnmax_dt_month").val()-1, $("#id_lastconnmax_dt_day").val())).getTime();
        		// lastconnmax: lastconnmax_val, 

				postData = { 
				    lastconnmax: lastconnmax_val, 
				    lastconnmin: lastconnmin_val, 
				    parcoursidentifiant_year: $("#parcoursidentifiant_year").val(), 
				    gaia_origine: $("#gaia_origine").val(), 
				    parcoursidentifiant_name: $("#parcoursidentifiant_name").val(), 
				    userrole: $("#id_userrole").val(),
				    select_no_pub : $("#id_select_no_pub").is(\':checked\') ? 1 : 0,
                    select_off : $("#id_select_off").is(\':checked\') ? 1 : 0,
                    select_offlocales : $("#id_select_offlocales").is(\':checked\') ? 1 : 0,
                    select_ofp : $("#id_select_ofp").is(\':checked\') ? 1 : 0
				};
				$.ajax({
					url: "view_statsparticipants_per_academy_export.php?action=export&format=htmltsv&so=" + sortorder,
					type: "POST",
					data: postData,
					success: function (data) {
        				var res = data.split("######");
        				$("#exportTable").html(res[0]);
						$("#id_b_clipboard").attr("data-clipboard-text",res[1]);
        				if(execafter != undefined)
        				{
        					execafter();
        		 		}
        				
					}
				});
        	}
        	
        	$("#resultTable").jtable({
				title: "",
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
        					/* lastconnmax: lastconnmax_val, */
                            postData = { 
                                lastconnmax: lastconnmax_val, 
                                lastconnmin: lastconnmin_val, 
                                parcoursidentifiant_year: $("#parcoursidentifiant_year").val(), 
                                gaia_origine: $("#gaia_origine").val(), 
                                parcoursidentifiant_name: $("#parcoursidentifiant_name").val(), 
                                userrole: $("#id_userrole").val() ,
                                select_no_pub : $("#id_select_no_pub").is(\':checked\') ? 1 : 0,
                                select_off : $("#id_select_off").is(\':checked\') ? 1 : 0,
                                select_offlocales : $("#id_select_offlocales").is(\':checked\') ? 1 : 0,
                                select_ofp : $("#id_select_ofp").is(\':checked\') ? 1 : 0
                            };
                            
                            $.ajax({
                                url: " view_statsparticipants_per_academy_ajax.php?action=list&so=" + jtParams.jtSorting,
                                type: "POST",
                                dataType: "json",
                                data: postData,
                                success: function (data) {
									if(data.TotalRecordCount == 0){
										$("#resultTable").hide();
										if($(".noresults").length == 0){
											$("#resultTable").parent().append(\'<p class="noresults">'.get_string('noresults', 'local_metaadmin').'</p>\');
										}
										
									}else{
										$("#resultTable").show();
										$(".noresults").remove();
									}
									
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
					'.$jtableheader.'
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
	        </script>');
        
    }
    
    function getAllID()
    {
    	global $CFG;
    	
    	return array('test_id_01'=>'test_id_01','test_id_02'=>'test_id_02','test_id_03'=>'test_id_03','test_id_04'=>'test_id_04');
    	
    	$academies = get_magistere_academy_config();
    	
    	$special_aca = array('reseau-canope','dgesco','efe','ih2ef','hub','dne_foad');
    	
    	$pids = array();
    	foreach ($academies as $academy_name=>$aca_data)
    	{
    		if (substr($academy_name,0,3) != 'ac-' && !in_array($academy_name,$special_aca))
    		{
    			continue;
    		}
    		
    		unset($acaDB);
    		if (($acaDB = databaseConnection::instance()->get($academy_name)) === false){error_log('view_statsparticipants.php/getAllID()/'.$academy_name.'/Database_connection_failed'); continue;}
    			
    		$aca_query = "SELECT DISTINCT CONCAT(im.year, '_', lic.code, '_', im.title, '_', li.version) as course_identification 
FROM {local_indexation} li 
INNER JOIN ".$CFG->centralized_dbname.".local_indexation_codes lic ON lic.id=li.codeorigineid
WHERE li.year IS NOT NULL AND li.year != ''
AND lic.code IS NOT NULL
AND li.title IS NOT NULL AND li.title != ''
AND li.version IS NOT NULL AND li.version != ''";
    		
    		$aca_result = $acaDB->get_records_sql($aca_query);
    	
    		foreach($aca_result as $value)
    		{
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
        
//        if ($data['sessionstartdate'] > $data['sessionenddate'])
//        {
//        	$errors['subscriptionenddate'] = get_string('sessionenddate_isbefore_sessionstartdate', 'block_course_management');
//        }

        
        //$errors = array_merge($errors, enrol_course_edit_validation($data, $this->context));


        return $errors;
    }
}

