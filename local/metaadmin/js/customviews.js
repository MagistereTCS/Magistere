
/**
 * To manage the interactions between available courses list and selected courses list.
 **/

$(document).ready(function() {

    // To clone the chosen courses from available to the selected courses list.
    $("#id_addsel").click(function(e) {
        e.preventDefault();
        $("#id_acourses option:selected").each(function() {
            var val = $(this).val();
            if ($('#id_scourses option[value="' + val + '"]').length > 0) {
                return true;
            } else {
                $("#id_scourses option").remove(':contains("Aucun")');
                $("#id_scourses").append($(this).clone());
                $('#id_acourses').val("");
            }
        });
    });

    // To initialise the selected courses list when the page is charged.
    $("#id_scourses").each(function() {
       $('#id_scourses option').each(function() {
           if ($(this).val() != 0 && $(this).prop('selected') == false) {
               $(this).remove();
           }
       });
        if ($('#id_scourses option:selected').length > 1) {
            $('#id_scourses option[value=0]').remove();
        }
    });

    // To remove the chosen courses from the selected courses list.
    $("#id_removesel").click(function(e) {
        e.preventDefault();
        $("#id_scourses option:selected").remove();
        if ($("#id_scourses option").length == 0) {
            $("#id_scourses").append($("<option>", { value : 0 })
                            .text("Aucun parcours sélectionné"));
        }
    });

    // To add the "selected" HTML attribute to keep selected courses in the selected courses list.
    $("#mform1").on('submit', function(e) {
        if ($("#id_scourses option:first").val() != 0) {
            $("#id_scourses option").each(function() {
                $(this).prop("selected", 1);
            });
        }
    });

    // To add all the courses from the user's academy to the selected courses list.
    $("#id_adduseraca").click(function(e) {
        e.preventDefault();
        $.each(acaCourses, function(key, value) {
            if ($('#id_scourses option[value="' + value.id + '"]').length > 0) {
                return true;
            } else {
                $("#id_scourses option").remove(':contains("Aucun")');
                $('#id_scourses').append($("<option></option>")
                                .attr("value",value.id)
                                .text(value.label));
            }
        });
    });

    // To disable the "wrong" list of days if frequency report is weekly or monthly
    $("#id_frequency_report").each(function() {
        if ($(this).val() == "monthly") {
            $("#fitem_id_nameday_report").children().hide();
            $("#fitem_id_numday_report").children().show();
        } else {
            $("#fitem_id_nameday_report").children().show();
            $("#fitem_id_numday_report").children().hide();
        }
    });
    $("#id_frequency_report").change(function(){
        if ($(this).val() == "monthly") {
            $("#fitem_id_nameday_report").children().hide();
            $("#fitem_id_numday_report").children().show();
        } else {
            $("#fitem_id_nameday_report").children().show();
            $("#fitem_id_numday_report").children().hide();
        }
    });

    $("#id_offreff_on").click( function(){
        var off_list = $("input[name= off_list]").val().split(';');
        if( $(this).is(':checked') ){
            $("#id_acourses > option").each(function() {
                if(off_list.includes(this.value)){
                    $(this).show();
                }
            });
        }else{
            $("#id_acourses > option").each(function() {
                if(off_list.includes(this.value)){
                    $(this).hide();
                }
            });

        }
    });
    $("#id_offrefp_on").click( function(){
        var $ofp_list = $("input[name= ofp_list]").val().split(';');
        if( $(this).is(':checked') ){
            $("#id_acourses > option").each(function() {
                if($ofp_list.includes(this.value)){
                    $(this).show();
                }
            });
        }else{
            $("#id_acourses > option").each(function() {
                if($ofp_list.includes(this.value)){
                    $(this).hide();
                }
            });
        }
    });





});
