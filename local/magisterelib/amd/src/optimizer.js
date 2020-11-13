/* jshint ignore:start */
define(['jquery', 'jqueryui', 'local_magisterelib/jquery.loadingModal'], function($) {
	var apiurl = '';
	var courseid = '';
	var session = '';
	var ignorestep1 = false;
	var ignorestep2 = false;
	var callbackfunction = undefined;
	
	function closeandcallback(){
		$('#wf_dialog_optimize').hide();
        $('#wf_dialog_optimize').dialog('close');
		if (callbackfunction && callbackfunction != null && {}.toString.call(callbackfunction) === '[object Function]'){
			setTimeout(callbackfunction,10);
		}
	}
	
	function makeid(length) {
	   var result           = '';
	   var characters       = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
	   var charactersLength = characters.length;
	   for ( var i = 0; i < length; i++ ) {
	      result += characters.charAt(Math.floor(Math.random() * charactersLength));
	   }
	   return result;
	}
	
	function load_step1(callback){
		callbackfunction = callback;
		session = makeid(25);
		
		$("body").loadingModal({
            position: "auto",
            text: "Calcul de l'optimisation en cours...",
            color: "#fff",
            opacity: "0.7",
            backgroundColor: "rgb(0,0,0)",
            animation: "circle"
        });
		
		$.post(
		    apiurl,
            '{"module":"getunusedfiles","courseid":'+courseid+',"session":"'+session+'"}',
            function(response) {
                if (response.error==false)
                {
                	if (response.files.length > 0) {
                    	$("#wf_dialog_optimize_content ul").html('');
                    	$.each(response.files,function(){
                    		$("#wf_dialog_optimize_content ul").append('<li><input type="checkbox" id="filecb'+this.id+'" name="files" value="'+this.id+'" checked/><label for="filecb'+this.id+'"> '+this.name+'</label></li>');
                    	});
                    	ignorestep1 = false;
                    	show_step1();
                	}else{
                		ignorestep1 = true;
                		load_step2();
                	}
                }
                $("body").loadingModal("destroy");
            },
            "json"
        );
	}

	function load_step2(){
		$("body").loadingModal({
            position: "auto",
            text: "Calcul de l'optimisation en cours...",
            color: "#fff",
            opacity: "0.7",
            backgroundColor: "rgb(0,0,0)",
            animation: "circle"
        });
		
		$.post(
		    apiurl,
            '{"module":"getusedfilestoconvert","courseid":'+courseid+',"session":"'+session+'"}',
            function(response) {
                if (response.error==false)
                {
                	if (response.files.length > 0) {
                    	$("#wf_dialog_optimize_step2_content ul").html('');
                    	$.each(response.files,function(){
                    		$("#wf_dialog_optimize_step2_content ul").append('<li><input type="checkbox" id="filecb'+this.id+'" name="files" value="'+this.id+'" checked/><label for="filecb'+this.id+'"> '+this.name+'</label></li>');
                    	});
                    	
                    	show_step2();
                        $("body").loadingModal("destroy");
                	}else if (ignorestep1 == false) {
                        $("body").loadingModal("destroy");
            			load_step3();
            		}else{
            			$("body").loadingModal("destroy");
            			if((!callbackfunction || callbackfunction == undefined || callbackfunction == null)){
            				show_step4();
            			}else{
            				$("body").loadingModal("destroy");
            				closeandcallback();
            			}
            		}
                }else{
                    $("body").loadingModal("destroy");
                }
                
            },
            "json"
        );
	}

	function load_step3(){
		$("body").loadingModal({
            position: "auto",
            text: "Chargement du résumé des opérations effectuées...",
            color: "#fff",
            opacity: "0.7",
            backgroundColor: "rgb(0,0,0)",
            animation: "circle"
        });
		
		$.post(
		    apiurl,
            '{"module":"getresults","courseid":'+courseid+',"session":"'+session+'"}',
            function(response) {
                if (response.error==false)
                {
                	if(response.file_deleted.length > 0) {
                    	$("#wf_dialog_optimize_step3_deleted_content ul").html('');
                    	$.each(response.file_deleted,function(){
                    		$("#wf_dialog_optimize_step3_deleted_content ul").append('<li>'+this.name+'</li>');
                    	});
                    	$("#wf_dialog_optimize_step3_deleted_desc").show();
                    	$("#wf_dialog_optimize_step3_deleted_content").show();
                	}else{
                		$("#wf_dialog_optimize_step3_deleted_desc").hide();
                    	$("#wf_dialog_optimize_step3_deleted_content").hide();
                	}
                	
                	if(response.file_converted.length > 0) {
                    	$("#wf_dialog_optimize_step3_converted_content ul").html('');
                    	$.each(response.file_converted,function(){
                    		$("#wf_dialog_optimize_step3_converted_content ul").append('<li>'+this.name+'</li>');
                    	});
                    	$("#wf_dialog_optimize_step3_converted_desc").show();
                    	$("#wf_dialog_optimize_step3_converted_content").show();
                	}else{
                		$("#wf_dialog_optimize_step3_converted_desc").hide();
                    	$("#wf_dialog_optimize_step3_converted_content").hide();
                	}

                	if(response.file_failed.length > 0) {
                    	$("#wf_dialog_optimize_step3_failed_content ul").html('');
                    	$.each(response.file_failed,function(){
                    		$("#wf_dialog_optimize_step3_failed_content ul").append('<li>'+this.name+'</li>');
                    	});
                    	$("#wf_dialog_optimize_step3_failed_desc").show();
                    	$("#wf_dialog_optimize_step3_failed_content").show();
                	}else{
                		$("#wf_dialog_optimize_step3_failed_desc").hide();
                    	$("#wf_dialog_optimize_step3_failed_content").hide();
                	}
                	
                	show_step3();
                }
                $("body").loadingModal("destroy");
            },
            "json"
        );
	}
	
	function show_step1(){
		$('div[aria-describedby=wf_dialog_optimize] .ui-dialog-titlebar-close').hide();
    	$("#wf_dialog_optimize_step1_button_submit").prop('disabled', false);
		$("#wf_dialog_optimize_step1_button_ignore").prop('disabled', false);
		$('#wf_dialog_optimize_step2').hide();
		$('#wf_dialog_optimize_step3').hide();
		$('#wf_dialog_optimize_step1').show();
		$('#wf_dialog_optimize_step4').hide();
		$("#wf_dialog_optimize").dialog({title:$('#wf_dialog_optimize_step1').attr('data')});
		$("#wf_dialog_optimize").dialog({height:350});
		$('#wf_dialog_optimize').show();
        $('#wf_dialog_optimize').dialog('open');
	}
	function show_step2(){
		$("#wf_dialog_optimize_step2_button_submit").prop('disabled', false);
		$("#wf_dialog_optimize_step2_button_ignore").prop('disabled', false);
		$('#wf_dialog_optimize_step1').hide();
		$('#wf_dialog_optimize_step3').hide();
		$('#wf_dialog_optimize_step2').show();
		$('#wf_dialog_optimize_step4').hide();
		$("#wf_dialog_optimize").dialog({title:$('#wf_dialog_optimize_step2').attr('data')});
		$("#wf_dialog_optimize").dialog({height:350});
		$('#wf_dialog_optimize').show();
        $('#wf_dialog_optimize').dialog('open');
	}
	function show_step3(){
		$('#wf_dialog_optimize_step1').hide();
		$('#wf_dialog_optimize_step2').hide();
		$('#wf_dialog_optimize_step3').show();
		$('#wf_dialog_optimize_step4').hide();
		$("#wf_dialog_optimize").dialog({title:$('#wf_dialog_optimize_step3').attr('data')});
		$("#wf_dialog_optimize").dialog({height:350});
		$('#wf_dialog_optimize').show();
        $('#wf_dialog_optimize').dialog('open');
	}
	function show_step4(){
		$('#wf_dialog_optimize_step1').hide();
		$('#wf_dialog_optimize_step2').hide();
		$('#wf_dialog_optimize_step3').hide();
		$('#wf_dialog_optimize_step4').show();
		$("#wf_dialog_optimize").dialog({title:$('#wf_dialog_optimize_step4').attr('data')});
		$("#wf_dialog_optimize").dialog({height:150});
		$('#wf_dialog_optimize').show();
        $('#wf_dialog_optimize').dialog('open');
	}
	
    function init(gapiurl,gcourseid) {
    	
		apiurl = gapiurl;
		courseid = gcourseid;
    	
    	$("#wf_link_optimize").on("click", function(e){
    		e.preventDefault();
    		load_step1();
    	});
    	
    	$("#wf_dialog_optimize_step1_button_submit").on("click", function(e){
    		$("#wf_dialog_optimize_step1_button_submit").prop('disabled', true);
    		$("#wf_dialog_optimize_step1_button_ignore").prop('disabled', true);
    		
    		$("body").loadingModal({
                position: "auto",
                text: "Suppression des fichiers inutilisés",
                color: "#fff",
                opacity: "0.7",
                backgroundColor: "rgb(0,0,0)",
                animation: "circle"
            });
    		
    		var files = [];
        	$('#wf_dialog_optimize_content input[type="checkbox"]').each(function() {
                if ($(this).is(":checked")) {
                	files.push(this.value);
                }
            });
        	
        	$.post(
    		    apiurl,
                '{"module":"deleteunusedfiles","courseid":'+courseid+',"session":"'+session+'","files":'+JSON.stringify(files)+'}',
                function(response) {
                    if (response.error==false){
                        $("body").loadingModal("destroy");
                    	load_step2();
                    }
                },
                "json"
            );
    	});
    	
    	$("#wf_dialog_optimize_step1_button_ignore").on("click", function(e){
    		$("#wf_dialog_optimize_step1_button_submit").prop('disabled', true);
    		$("#wf_dialog_optimize_step1_button_ignore").prop('disabled', true);
    		
    		ignorestep1 = true;
    		load_step2();
    	});
    	$("#wf_dialog_optimize_step2_button_submit").on("click", function(e){
    		$("#wf_dialog_optimize_step2_button_submit").prop('disabled', true);
    		$("#wf_dialog_optimize_step2_button_ignore").prop('disabled', true);
    		
    		$("body").loadingModal({
                position: "auto",
                text: "Centralisation des fichiers en cours",
                color: "#fff",
                opacity: "0.7",
                backgroundColor: "rgb(0,0,0)",
                animation: "circle"
            });
    		
    		var files = [];
        	$('#wf_dialog_optimize_step2_content input[type="checkbox"]').each(function() {
                if ($(this).is(":checked")) {
                	files.push(this.value);
                }
            });
        	
        	$.post(
    		    apiurl,
                '{"module":"convertunusedfilestocr","courseid":'+courseid+',"session":"'+session+'","files":'+JSON.stringify(files)+'}',
                function(response) {
    		    	$("body").loadingModal("destroy");
                    if (response.error==false){
                    	load_step3();
                    }
                    
                },
                "json"
            );
    	});
    	$("#wf_dialog_optimize_step2_button_ignore").on("click", function(e){
    		$("#wf_dialog_optimize_step2_button_submit").prop('disabled', true);
    		$("#wf_dialog_optimize_step2_button_ignore").prop('disabled', true);
    		
    		ignorestep2 = true;
    		if (ignorestep1 == false) {
    			load_step3();
    		}else{
    			closeandcallback();
    		}
    	});
    	$("#wf_dialog_optimize_step3_button_close").on("click", function(e){
            closeandcallback();
    	});
    	
    	$("#wf_dialog_optimize_step4_button_close").on("click", function(e){
            closeandcallback();
    	});
    	
    	$("#wf_dialog_optimize").dialog({
	        autoOpen: false,
	        width: 700,
	        height: 350,
	        title: "Optimiser le parcours",
	        draggable: false,
	        modal: true,
	        resizable: false,
	        closeOnEscape: false,
	        buttons: []
	    });
    	
    	$('#wf_link_optimize').prop('disabled', false);;
    }
	
    return {
    	init: function(apiurl,courseid) {
            init(apiurl,courseid);
        },
        load_step1: load_step1
    };

});