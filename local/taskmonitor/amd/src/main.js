/* jshint ignore:start */
define(['jquery', 'jqueryui', 'local_taskmonitor/jtable'], function($) {
    function init() {
    	
    	
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
			    select_no_pub : $("#id_select_no_pub").is(':checked') ? 1 : 0,
                select_off : $("#id_select_off").is(':checked') ? 1 : 0,
                select_offlocales : $("#id_select_offlocales").is(':checked') ? 1 : 0,
                select_ofp : $("#id_select_ofp").is(':checked') ? 1 : 0
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
            paging: true,
			pageSize: 20,
            pageSizes: [20,50,100],
            selecting: false,
            multiselect: false,
            selectingCheckboxes: false,
			sorting: true,
			defaultSorting: "classname ASC",
            jqueryuiTheme: true,
            defaultDateFormat: "dd-mm-yy",
            gotoPageArea: "none",
            selectOnRowClick: false,
			actions: {
                listAction: function (postData, jtParams) {			
                    return $.Deferred(function ($dfd) {
                        postData = {
                        	priority: $("#id_filterPriority option:selected").val(),
                        	view: $("#id_filterView option:selected").val(),
                            tasks: $('#taskselect').val(),
                            platform: $("#id_filterPlatform option:selected").val(),
                            sorting: jtParams.jtSorting,
                            startindex: jtParams.jtStartIndex,
                            pagesize: jtParams.jtPageSize
                        };
                        
                        $.ajax({
                            url: "api.php",
                            type: "POST",
                            dataType: "json",
                            data: postData,
                            success: function (data) {
								if(data.TotalRecordCount == 0){
									$("#resultTable").parent().append('<p class="noresults"></p>');
								}else{
									if(data.showplatform){
										$("#resultTable").jtable('changeColumnVisibility','platform','visible');
									}else{
										$("#resultTable").jtable('changeColumnVisibility','platform','hidden');
									}
									$("#resultTable").show();
									$(".noresults").remove();
								}
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
				"classname": {
					title: "Tâches",
					create: false,
					edit: false,
					list: true
				},
				"component": {
					title: "Composant",
					create: false,
					edit: false,
					list: true
				},
				"server": {
					title: "Serveur",
					create: false,
					edit: false,
					list: true
				},
				"platform": {
					title: "Plateforme",
					create: false,
					edit: false,
					list: true
				},
				"type": {
					title: "Nature",
					create: false,
					edit: false,
					list: true
				},
				"disabled": {
					title: "Etat",
					create: false,
					edit: false,
					list: true,
					sorting:false
				},
				"runfrequency": {
					title: "Paramétrage",
					create: false,
					edit: false,
					list: true,
					sorting:false
				},
				"lastrun": {
					title: "Dernière execution",
					create: false,
					edit: false,
					list: true,
					sorting:false
				},
				"status": {
					title: "Statut",
					create: false,
					edit: false,
					list: true
				},
				"queries": {
					title: "Nombre de requêtes",
					create: false,
					edit: false,
					list: true,
					sorting:false
				},
				"runtime": {
					title: "Durée de l'éxecution",
					create: false,
					edit: false,
					list: true,
					sorting:false
				},
			}
		});

		//Load person list from server
		//$("#resultTable").jtable("load");
		
    	$('#filtersubmit').on('click',function(){
    		$("#resultTable").jtable("load");
    	});
    	
    	$('#tasksearch').on('keyup',function(){
    		var search = $('#tasksearch').val();
    		$('#taskselect > option').each(function(index){
    			if (this.value.search(search) > -1 || $(this).attr('data').search(search) > -1){
    				$(this).show();
    			}else{
    				$(this).hide();
    			}
    		});
    	});
    	
    	$('#id_filterView').on('change', function(){
    		var view = $("#id_filterView option:selected").val();
    		if (view == 2){
    			$("#taskmonitor #fitem_id_filterPlatform").show();
    			$("#taskmonitor #taskitem").hide();
    		}else if (view == 3){
    			$("#taskmonitor #fitem_id_filterPlatform").hide();
    			$("#taskmonitor #taskitem").show();
    		}else{
    			$("#taskmonitor #fitem_id_filterPlatform").hide();
    			$("#taskmonitor #taskitem").hide();
    		}
    	});
    	
    	if ($('#id_filterView').val() == 2){
    		$("#taskmonitor #fitem_id_filterPlatform").show();
			$("#taskmonitor #taskitem").hide();
    	}
    }

    return {
        init: function() {
            init();
        }
    };

});