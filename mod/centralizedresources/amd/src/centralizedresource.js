define(['jquery', 'jqueryui'], function(){
    function init()
    {
        $("#search-form-title").detach().appendTo("#frontpage-tabs-1 .resource-search");
        $("#id_owner-ressearch").detach().appendTo("#frontpage-tabs-1 .resource-search");
        $("#id_domain-ressearch").detach().appendTo("#frontpage-tabs-1 .resource-search");
        $("#id_date-ressearch").detach().appendTo("#frontpage-tabs-1 .resource-search");
        $("#id_search-ressearch").detach().appendTo("#frontpage-tabs-1 .resource-search");
        $("#id_types-ressearch").detach().appendTo("#frontpage-tabs-1 .resource-search");
        $("#id_buttons-ressearch").detach().appendTo("#frontpage-tabs-1 .resource-search");
        $("#search_result").detach().appendTo("#frontpage-tabs-1");

        $("#id_add_resource").detach().appendTo("#frontpage-tabs-2");
        $("#id_audio_options").detach().appendTo("#frontpage-tabs-2");
        $("#id_video_options").detach().appendTo("#frontpage-tabs-2");
        $("#id_add_resource_type").detach().appendTo("#frontpage-tabs-2");
        $("#frontpage-tabs").tabs({
            activate: function(event, ui){
                if(ui.newPanel[0].id == "frontpage-tabs-2"){
                    $("#id_add_new_resource").val(1);
                }
                else{
                    $("#id_add_new_resource").val(0);
                }
            }
        });
        if("'.$addinstance_capability.'"==0){
            $("#frontpage-tabs").tabs({
                disabled: [1]
            });
        }


        $( "#mform1" ).submit(function( event ) {
            if($("#id_add_new_resource").val()==1){
                var title = $( "#frontpage-tabs-2").find("#id_title");
                var description = $( "#frontpage-tabs-2").find("#id_description");
                var attachment_check = $( "#frontpage-tabs-2").find(".filepicker-filename:first-child").children().prop("tagName").toLowerCase();
                var attachment = $( "#frontpage-tabs-2").find(".fp-content").children();
                var title_parent = title.parent().parent();
                var description_parent = description.parent().parent();
                var attachment_parent = $( "#frontpage-tabs-2").find("#fitem_id_attachments");

                showInputErrorMessage(event, title, title_parent);
                showInputErrorMessage(event, description, description_parent);
                showAttachmentErrorMessage(event, attachment_check, attachment_parent);
            }
        });


        /*
         * FILE ATTACHMENT PART
         */
        var fileurl = undefined;
        $("#fitem_id_attachments").on("DOMSubtreeModified",".filepicker-filename",function(event){
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
                    if(extensiondiaporama.indexOf(uri[uri.length-1]) != -1){
                        if($("#id_add_resource_type").is(":visible")){
                            $("#id_display > option[value$='.RESOURCELIB_DISPLAY_DOWNLOAD.']").show();
                        }else{
                            $("#id_display > option[value$='.RESOURCELIB_DISPLAY_DOWNLOAD.']").hide();
                            $("#id_display").val("0");
                        }

                    }else{
                        $("#id_display > option[value$='.RESOURCELIB_DISPLAY_DOWNLOAD.']").show();
                    }
                }

            }
        });


        $("#id_type_single_file").click(function(){
            $("#id_display > option[value$='.RESOURCELIB_DISPLAY_DOWNLOAD.']").show();
        });
        $("#id_type_multimedia_file").click(function(){
            $("#id_display > option[value$='.RESOURCELIB_DISPLAY_DOWNLOAD.']").hide();
        });

    }

    function showInputErrorMessage(event, element, parent){
        if(element.val() == ""){
            modifHtml(event, parent, "true");
        }
        else{
            modifHtml(event, parent, "false");
        }
    }

    function showAttachmentErrorMessage(event, element, parent){
        if(element == "a"){
            modifHtml(event, parent, "false");
        }
        else{
            modifHtml(event, parent, "true");
        }
    }

    function modifHtml(event, parent, showErrorMessage){
        var parent = parent;
        var showErrorMessage = showErrorMessage;

        if(showErrorMessage == "true"){
            if(parent.find(".error").length == 0){
                createErrorMessage(parent);
            }

            //stop the form submission
            event.preventDefault();
        }
        else{
            removeErrorMessage(parent);
        }
    }

    function createErrorMessage(parent){
        var parent = parent;
        parent.append( "<div class=\"felement ftext error\"><span class=\"error\"> * Vous devez remplir ce champ.<br></span></div>" );
    }

    function removeErrorMessage(parent){
        var parent = parent;
        parent.find(".error").remove();
    }

    return {
        init: function(){
            init();
        }
    }
})