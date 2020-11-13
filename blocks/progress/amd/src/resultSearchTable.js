/* jshint ignore:start */
define(['jquery', 'core/str', 'core/notification', 'core/ajax', 'local_magisterelib/jtable'], function($, Str, Notification, Ajax) {
    function init(ajax_url, ajax_redirect, id, contextid, courseid, jtheader_firstname, jtheader_lastname, jtheader_lastlogin,
                  jtheader_suivi, jtheader_progression, jtheader_finished, jttitle){
        var selectedRowsObject = [];
        $('#message_form_div').hide();

        $('#ProgressBarOverviewTable').on('mouseout', '.progressBarCell', function() {
            $(".progressEventInfo").empty();
        });

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
                    send_messages(selectedRowsObject);
                }
            },{
                text: "Annuler",
                class: 'ui-message-cancel',
                click: function() {
                    $( this ).dialog( "close" );
                }
            }]
        });

        $('#select_submit').on('change', function (e) {
            var valueSelected = this.value;

            if (valueSelected == 'formation_finished') {
                if (selectedRowsObject == "") {
                    alert("Vous n'avez pas sélectionné d'utilisateur.");
                }
                else {
                    var data_value = '';
                    $.each(selectedRowsObject, function (key, value ) {
                        if (value.timeaccess == ''){
                            $("#page-blocks-progress-warning").show();
                            window.scrollTo(0, 0);
                            return true;
                        }
                        if (data_value){ data_value += ','; }
                        data_value += value.id;
                    });
                    if (data_value != '') {
                        $.ajax({
                            type: "POST",
                            url : ajax_redirect,
                            data: { action: 'submit_termine', users: data_value, id: id, courseid: courseid }
                        }).done(
                            function (msg) {
                                reloadtable();
                            }
                        );
                    }
                }

                $("#select_submit option").filter(function() {
                    return $(this).val() == 'none';
                }).prop('selected', true);
            }
            else if (valueSelected == 'send_message') {
                if (selectedRowsObject == "") {
                    alert("Vous n'avez pas sélectionné d'utilisateur.");
                }
                else {
                    var label = '';
                    if (selectedRowsObject.length == 1) {
                        label = Str.get_string('sendbulkmessagesentsingle', 'core_message');
                    } else {
                        label = Str.get_string('sendbulkmessagesent', 'core_message', selectedRowsObject.length);
                    }

                    label.then(function(value){
                        $('.ui-message-ok').text(value);
                        $('.ui-dialog-title').text(value);
                        $('#messagesmod').dialog('open');
                    });
                }
            }
        });


        $('#refresh_cache').on("click","", function (e) {
            if (window.is_cachebutton_disable == false) {
                $.ajax({
                    type: "POST",
                    url : ajax_url,
                    data: { action: 'refresh' }
                }).done(
                    function (msg) {
                        reloadtable();

                        disable_cachebutton();
                    }
                );
            }
        });

        $('#clean_selected_users').click(function () {
            selectedRowsObject = [];
            $('#ProgressBarOverviewTable').find(".jtable-selecting-column > input:checked").click();
        });

        $('#filterform_submit').click(function () {
            reloadtable();
        });

        $("#filterform_name").keyup(function (e) {
            if (e.keyCode == 13) {
                reloadtable();
            }
        });

        //Prepare jTable
        $('#ProgressBarOverviewTable').jtable({
            title: jttitle,
            paging: true,
            pageSize: 20,
            pageSizes: [20, 30, 40, 60, 80, 100],
            selecting: true,
            multiselect: true,
            selectingCheckboxes: true,
            sorting: true,
            defaultSorting: 'firstname ASC',
            jqueryuiTheme: true,
            defaultDateFormat: 'dd-mm-yy',
            gotoPageArea: 'none',
            selectOnRowClick: false,
            actions: {
                listAction: function (postData, jtParams) {
                    return $.Deferred(function ($dfd) {

                        var frole = $('#filterform_role').val();
                        var fgroup = $('#filterform_group').val();
                        var frealized = $('input[name=filterform_realized]:checked', '#filterform_form').val();
                        var factivity = $('#filterform_activity').val();
                        var fname = $('#filterform_name').val();
                        var fneverconnected = '';
                        if ( $('#filterform_neverconnected').is(":checked") ) {
                            fneverconnected = $('#filterform_neverconnected').val();
                        }

                        var sgaia = JSON.stringify($('input[name^="sgaia"]:checked').serializeArray());
                        var sgaiaother = ($('input[name="sother"]:checked').length == 1);

                        postData = { id: id,
                            courseid: courseid,
                            contextid: contextid,
                            role: frole,
                            group: fgroup,
                            realized: frealized,
                            activity: factivity,
                            name: fname,
                            neverconnected: fneverconnected,
                            sgaia: sgaia,
                            sgaiaother: sgaiaother };

                        $.ajax({
                            url: ajax_url+'?action=list&si=' + jtParams.jtStartIndex
                                + '&ps=' + jtParams.jtPageSize
                                + '&so=' + jtParams.jtSorting,
                            type: 'POST',
                            dataType: 'json',
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
            //Register to selectionChanged event to handle events
            selectionChanged: function () {

                //Get all selected rows
                var $selectedRows = $('#ProgressBarOverviewTable').jtable('selectedRows');

                // DEL - add all non-visible rows to colnew then swap
                var $colnew = [];
                for (var i = 0, len = selectedRowsObject.length; i < len; i++) {
                    $row = $('#ProgressBarOverviewTable').jtable('getRowByKey', selectedRowsObject[i].id);
                    if (!$row)
                        $colnew.push(selectedRowsObject[i]);
                }
                selectedRowsObject = $colnew;

                // ADD - make sure currently selected rows are selected
                if ($selectedRows.length > 0) {
                    $selectedRows.each(function () {
                        var record = $(this).data('record');
                        if (!objectIsInArray(selectedRowsObject, record.id))
                            selectedRowsObject.push(record);
                    });
                }
                $('#selected_users span').text(selectedRowsObject.length);
            },
            rowInserted: function (event, data) {
                if(objectIsInArray(selectedRowsObject, data.record.id)){
                    $('#ProgressBarOverviewTable').jtable('selectRows', data.row);
                }
            },

            fields: {
                id: {
                    title: 'id',
                    key: true,
                    create: false,
                    edit: false,
                    list: false
                },
                firstname: {
                    title: jtheader_firstname,
                    width: '13%',
                    create: false,
                    edit: false,
                    list: true
                },
                lastname: {
                    title: jtheader_lastname,
                    width: '19%',
                    create: false,
                    edit: false,
                    list: true
                },
                timeaccess: {
                    title: jtheader_lastlogin,
                    width: '21%',
                    type: 'date',
                    columnResizable: false,
                    listClass : "jt_lastlogin",
                    create: false,
                    edit: false,
                    list: true
                },
                suivi: {
                    title: jtheader_suivi,
                    width: '23%',
                    listClass : "jt_suivi",
                    create: false,
                    edit: false,
                    list: true,
                    sorting: false
                },
                progression: {
                    title: jtheader_progression,
                    width: '5%',
                    create: false,
                    edit: false,
                    list: true,
                    sorting: false
                },
                finished: {
                    title: jtheader_finished,
                    width: '10%',
                    create: false,
                    edit: false,
                    list: true
                }
            }
        });

        //Load person list from server
        $('#ProgressBarOverviewTable').jtable('load');
    }

    function reloadtable() {
        $('#ProgressBarOverviewTable').jtable('load');
    }

    window.is_cachebutton_disable = false;

    function enable_cachebutton() {
        $('#refresh_cache').css("filter", "");
        $('#refresh_cache').css("-webkit-filter", "");
        $('#refresh_cache').css("-moz-filter", "");

        $('#refresh_cache').css("cursor", "pointer");

        window.is_cachebutton_disable = false;
    }

    function disable_cachebutton() {
        window.is_cachebutton_disable = true;

        $('#refresh_cache').css("filter", "grayscale(100%)");
        $('#refresh_cache').css("-webkit-filter", "grayscale(100%)");
        $('#refresh_cache').css("-moz-filter", "grayscale(100%)");

        $('#refresh_cache').css("filter", "opacity(30%)");
        $('#refresh_cache').css("-webkit-filter", "opacity(30%)");
        $('#refresh_cache').css("-moz-filter", "opacity(30%)");

        $('#refresh_cache').css("cursor", "auto");

        setTimeout('enable_cachebutton()', 60000);
    }
    
    function objectIsInArray(objects, id){
        for (var i = 0, len = objects.length; i < len; i++) {
            if (objects[i].id == id) {
                return true;
            }
        }
        return false;
    }

    function send_messages(selectedRowsObject)
    {
        var messages = [];
        var msg = $('#messagemodtext').val();

        $.each(selectedRowsObject, function (key, value ) {
            messages.push({touserid: value.id, text: msg});
        });

        return Ajax.call([{
            methodname: 'core_message_send_instant_messages',
            args: {messages: messages}
        }])[0].then(function(messageIds) {
            $('#messagesmod').dialog('close');

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
        }).catch(Notification.exception);
    }

    return {
        init: function(ajax_url, ajax_redirect, id, contextid, courseid, jtheader_firstname, jtheader_lastname, jtheader_lastlogin,
                       jtheader_suivi, jtheader_progression, jtheader_finished, jttitle){
            init(ajax_url, ajax_redirect, id, contextid, courseid, jtheader_firstname, jtheader_lastname, jtheader_lastlogin,
                jtheader_suivi, jtheader_progression, jtheader_finished, jttitle);
        }
    };
});