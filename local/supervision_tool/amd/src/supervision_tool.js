define(["jquery", "core/str", "core/notification", "core/ajax", "local_supervision_tool/jtable","local_magisterelib/jquery.loadingModal"], function($, Str, Notification, Ajax){
    var wwwroot = M.cfg.wwwroot;

    var courseids = [];
    var formateursId = [];

    var systemselection = false;

    $('#messagesmod').dialog({
        autoOpen: false,
        resizable: false,
        height: "auto",
        width: "560px",
        dialogClass: "ui-new-message-box",
        modal: true,
        buttons: [{
            text: "Ok",
            class: 'ui-message-ok',
            click: function(){
                send_messages(formateursId);
            }
        },{
            text: "Annuler",
            class: 'ui-message-cancel',
            click: function() {
                $( this ).dialog( "close" );
                $('#messagemodtext').val('');
            }
        }]
    });
    
    $('body').on('click', 'button.formateurs', function() {
    	formateursId = $(this).attr('data-formateurs-id').split(',');
    	var label = '';
    	if (formateursId.length == 1) {
    		label = Str.get_string('sendbulkmessagesentsingle', 'core_message');
    	} else {
    		label = Str.get_string('sendbulkmessagesent', 'core_message', formateursId.length);
    	}
    	label.then(function(value) {
    		$('.ui-message-ok').text(value);
    		$('.ui-dialog-title').text(value);
    		$('#messagesmod').dialog('open');
    	});
    	$('#messagesmod').dialog('open');
    });
    
    function initJtable(columns, identifiant){

        var selectionTable = $("#filterresults").jtable({
            paging: true,
            pageSize: 10,
            pageSizes: [10, 25, 50, 100],
            selecting: true,
            multiselect: true,
            selectingCheckboxes: true,
            selectOnRowClick: false,
            sorting: true,
            defaultSorting: "id ASC",
            jqueryuiTheme: true,
            defaultDateFormat: "dd-mm-yy",
            gotoPageArea: "none",
            actions: {
                listAction: function (postData, jtParams) {
                    return $.Deferred(function ($dfd) {

                        postData = $('#mform1').serialize();

                        $.ajax({
                            url: wwwroot+"/local/supervision_tool/ajax.php?action=list&si=" + jtParams.jtStartIndex + "&ps=" + jtParams.jtPageSize + "&so=" + jtParams.jtSorting,
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
                },
            },
            fields: columns,
            recordsLoaded: function(event, data){
                $('.jtable-data-row').each(function(){
                    if(courseids.indexOf(parseInt($(this).data('record-key'))) > -1){
                        $(this).find('input[type="checkbox"]').prop('checked', true);
                        $(this).addClass('jtable-row-selected ui-state-highlight');
                    }
                });
                systemselection = false;
            },
            selectionChanged: function(event, data) {
                if(systemselection){
                    // will be reset to true when user changes pages
                    systemselection = false;
                    return;
                }

                var selectedRows = selectionTable.jtable("selectedRows");

                var records = [];
                var selectedids = [];

                $('.jtable-data-row').each(function(){
                   records.push($(this).data('record-key'));
                });

                selectedRows.each(function(){
                    // add only the local course
                    var record = $(this).data('record');
                    if(record.originidentifiant == '-' || record.originidentifiant == identifiant){
                        selectedids.push(record.localid);
                    }
                });

                for(var i = 0; i < records.length; i++){
                    var selectidx = selectedids.indexOf(''+records[i]);
                    var isselected = (selectidx > -1);

                    var courseidx = courseids.indexOf(records[i]);
                    var isinselection = (courseidx > -1);

                    if(isselected && !isinselection){
                        courseids.push(records[i]);
                    }

                    if(!isselected && isinselection){
                        courseids.splice(courseidx, 1);
                    }
                }

                $('input[name="courseids"]').val(courseids.join());

                $('input[name="actionbutton"]').prop('disabled', (courseids.length == 0));
            }
        });

        // EDIT COMMENT
        $('#filterresults').on('dblclick', '.commentarea', function(){
            var record = $(this).parent().data().record;

            // if the record is not local, do nothing
            if(record.originidentifiant != '-' && record.originidentifiant != identifiant){
                return;
            }

            var editarea = $('<textarea>').addClass('editcommentarea');
            if($(this).text() != '-'){
                editarea.val($(this).text());
            }

            $(this).text('');
            editarea.appendTo($(this));
            editarea.focus();
        });

        $('#filterresults').on('blur', '.editcommentarea', function(){

            var me = $(this);

            var postData = {
                id: me.parents('.jtable-data-row').data('record-key'),
                comment: me.val()
            }

            $.ajax({
                url: wwwroot+"/local/supervision_tool/ajax.php?action=edit",
                type: "POST",
                dataType: "json",
                data: postData,
                success: function (data) {
                    var col = me.parent();
                    col.html(data.response);
                }
            });
        });

        $('#filterresults').on('click', '.jtable-page-number', function(){
            // when the user changes page, indicate that the selection will be performed by the system
            systemselection = true;
        });

        selectionTable.jtable("load");
    }

    function initActions(actionOptions)
    {
        $("#dialog_archive").dialog({
            autoOpen: false,
            width: 600,
            title : "Archivage de la session de formation",
            draggable:"false",
            modal: true,
            appendTo: "#mform1",
            resizable:false,
            closeOnEscape: false,
            closeText: 'Fermer',
            buttons: [{
                text: "Archiver",
                id:"archivebutton",
                click: function(){
                    allowSubmit = true;
                    $('input[name="actionbutton"]').click();
                }},
                {
                    text: "Annuler",
                    id:"cancelarchivebutton",
                    click: function(){
                        $(this).dialog("close");
                    }
                }
             ]
        });


        $("#dialog_trash").dialog({
            autoOpen: false,
            width: 600,
            title : "Mise à la corbeille de la session de formation",
            draggable:"false",
            modal: true,
            appendTo: "#mform1",
            resizable:false,
            closeOnEscape: false,
            closeText: 'Fermer',
            buttons: [{
                text: "Mettre à la corbeille",
                id:"trashbutton",
                click: function(){
                    allowSubmit = true;
                    $('input[name="actionbutton"]').click();
                }},
                {
                    text: "Annuler",
                    id:"canceltrashbutton",
                    click: function(){
                        $(this).dialog("close");
                    }
                }
            ]
        });

        $("#dialog_migration").dialog({
            autoOpen: false,
            width: 600,
            title : "Conversion de la session de formation",
            draggable:"false",
            modal: true,
            appendTo: "#mform1",
            resizable:false,
            closeOnEscape: false,
            closeText: 'Fermer',
            buttons: [{
                text: "Lancer la conversion",
                id:"launchmigration",
                click: function(){
                    allowSubmit = true;
                    $('input[name="actionbutton"]').click();
                }},
                {
                    text: "Annuler",
                    id:"cancellaunchmigrationbutton",
                    click: function(){
                        $(this).dialog("close");
                    }
                }
            ]
        });

        $("#dialog_validation").dialog({
            autoOpen: false,
            width: 600,
            title : "Validation des parcours",
            draggable:"false",
            modal: true,
            appendTo: "#mform1",
            resizable:false,
            closeOnEscape: false,
            closeText: 'Fermer',
            buttons: [{
                text: "Valider les parcours",
                id:"validate",
                click: function(){
                    allowSubmit = true;
                    $('input[name="actionbutton"]').click();
                }},
                {
                    text: "Annuler",
                    id:"cancelvalidatebutton",
                    click: function(){
                        $(this).dialog("close");
                    }
                }
            ]
        });

        $("#dialog_moveTo").dialog({
            autoOpen: false,
            width: 800,
            title : "Changement de catégorie",
            draggable:"false",
            modal: true,
            appendTo: "#mform1",
            resizable:false,
            closeOnEscape: false,
            closeText: 'Fermer',
            buttons: [{
                text: "Déplacer",
                id:"moveTobutton",
                click: function(){
                    allowSubmit = true;
                    $('input[name="actionbutton"]').click();
                }},
                {
                    text: "Annuler",
                    id:"cancelmoveTobutton",
                    click: function(){
                        $(this).dialog("close");
                    }
                }
            ]
        });

        var allowSubmit = false;
        $('input[name="actionbutton"]').click(function(e){
            if(allowSubmit){
            	return true;
            }

            e.preventDefault();
            var optionSelected = $("select[name='actionTodo'] option:selected").val();
            if(optionSelected == actionOptions["archive"]){
                $('#dialog_archive').dialog('open');
            }else if(optionSelected == actionOptions["moveTo"]){
                $('#dialog_moveTo').dialog('open');
            }else if(optionSelected == actionOptions["trash"]){
                $('#dialog_trash').dialog('open');
            }else if(optionSelected == actionOptions["migrateToModular"]
                  || optionSelected == actionOptions["migrateToTopics"]){
                $('#dialog_migration').dialog('open');
            }else if(optionSelected == actionOptions["validate"]){
                $('#dialog_validation').dialog('open');
            }
        });

        $('#id_resetfilter').click(function(e){
            e.preventDefault();
            $('#id_filter select').prop('selectedIndex', 0);
            $('#id_filter input[type="text"]').val('');
            $('#id_filter input[type="checkbox"]').val('');
            $('#mform1 #id_filteraction').click();
        });

        $('input[name="csvbutton"]').click(function(e){
            var data = $('#mform1').serialize();
            data += '&action=gencsv';
            var sortOrder = $("#filterresults").jtable('instance')._lastSorting;

            if(sortOrder.length){
                data += '&so='+sortOrder[0].fieldName+' '+sortOrder[0].sortOrder;
            }else{
                data += '&so=undefined';
            }


            $("body").loadingModal({
                position: "auto",
                text: "Génération de l'export CSV en cours",
                color: "#fff",
                opacity: "0.7",
                backgroundColor: "rgb(0,0,0)",
                animation: "circle"
            });

            $.post(
                wwwroot+"/local/supervision_tool/ajax.php",
                data,
                function(response){
                    response = JSON.parse(response);

                    if(response.result == 'ok'){
                        window.location.href = response.url;
                        $("body").loadingModal("hide");
                        setTimeout(function(){$("body").loadingModal("destroy")},1000);
                    }
                });

        });
    }
    
    function send_messages(recipientsId)
    {
        var messages = [];
        var msg = $('#messagemodtext').val();

        $.each(recipientsId, function (key, value ) {
            messages.push({touserid: value, text: msg});
        });

        return Ajax.call([{
            methodname: 'core_message_send_instant_messages',
            args: {messages: messages}
        }])[0].then(function(messageIds) {
            $('#messagesmod').dialog('close');
            $('#messagemodtext').val('');
            if (messageIds.length == 1) {
                return Str.get_string('sendbulkmessagesentsingle', 'core_message');
            } else {
                return Str.get_string('sendbulkmessagesent', 'core_message', messageIds.length);
            }
        }).then(function(msg) {
            Notification.addNotification({
                message: msg,
                type: "success"
            });
            return true;
        }).catch(function(exception) {
        	$('#messagesmod').dialog('close');
        	Str.get_string('messagefailed','local_supervision_tool').then(function(msg) {
        		Notification.addNotification({
                    message: msg,
                    type: "failure"
                });
        	});
            return false;
        });
    }

    return {
        init : function(columnsname, actionOptions, masterid){
            var columns = $('input[name="'+columnsname+'"]').val();
            columns = JSON.parse(columns);

            initJtable(columns, masterid);
            initActions(actionOptions);
        }
    }

});