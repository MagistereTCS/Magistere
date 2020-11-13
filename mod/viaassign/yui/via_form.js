$(document).ready(function () {
    var maxusers = $("input[name=maxusers]").val();

    $("#id_participants_add_btn,#id_participants_remove_btn,#id_animators_add_btn,#id_animators_remove_btn").click(function () {
        var totalusers = 0;

        $("#id_participants option").each(function () {
            totalusers++;
        });

        $("#id_animators option").each(function () {
            totalusers++;
        });

        if (totalusers > maxusers) {
            $("#fgroup_id_add_users .felement.fgroup").addClass("error");
            $("#usererror").removeClass("hide");
        } else {
            if ($("#fgroup_id_add_users .felement.fgroup").hasClass("error")) {
                $("#fgroup_id_add_users .felement.fgroup").removeClass("error");
            }
            if (!$("#usererror").hasClass("hide")) {
                $("#usererror").addClass("hide");
            }
            if ($('#fgroup_id_add_users .felement.fgroup SPAN.error').length) {
                $(this).addClass("hide");
            }
        }
    });

    $('#id_groupid').on('change', function () {
        var groupid = this.value;
        console.log(this);
        // If users had been selected, we need to remove them, only users from one group can be added.

        $("#id_participants option").each(function () {
            var select = $(".viauserlists:not(.hide):first").attr("id");
            $(this).remove().appendTo("#" + select);
        });
        $("#id_animators option").each(function () {
            var select = $(".viauserlists:not(.hide):first").attr("id");
            $(this).remove().appendTo("#" + select);
        });

        var allgroups = $("[id ^= 'id_groupusers']");

        $(allgroups).each(function (i, val) {
            // If selected group, remove hide; otherwise hide.
            if ($(val).attr('id') == "id_groupusers" + groupid) {
                if ($("#" + $(val).attr('id')).hasClass("hide")) {
                    $("#" + $(val).attr('id')).removeClass("hide");
                }
            } else {
                if (!$("#" + $(val).attr('id')).hasClass("hide")) {
                    $("#" + $(val).attr('id')).addClass("hide");
                }
            }
        });
    });

    $('.felement').each(function () {
        if ($(this).hasClass("error")) {
            $("#id_submitbutton").prop("disabled", true);
        }
    });

    $('.error select').change(function () {

        var day = $('#id_datebegin_day').val();
        var month = $('#id_datebegin_month').val();
        var year = $('#id_datebegin_year').val();

        var selected = new Date(year, parseInt(month, 10) - 1, day);
        selected = Date.parse(selected) / 1000;

        var enddate = $('input[name=duedate]').val();
        var startdate = $('input[name=allowsubmissionsfromdate]').val();

        if (selected >= startdate && selected <= enddate) {
            $(this).closest('.felement').find('span.error').css("display", "none");
            $(this).closest('.felement').removeClass("error");
            $("#id_submitbutton").prop("disabled", false);
        } else {
            // Only add if they don't already have it!
            if (!$(this).closest('.felement').hasClass("error")) {
                $(this).closest('.felement').find('span.error').css("display", "inline-block");
                $(this).closest('.felement').addClass("error");
                $("#id_submitbutton").prop("disabled", true);
            }
        }

    });

    $('.error input').change(function () {

        var newduration = $('#id_duration').val();
        var maxduration = $("input[name=maxduration]").val();

        if (newduration >= maxduration) {
            $(this).closest('.felement').find('span.error').css("display", "none");
            $(this).closest('.felement').removeClass("error");
            $("#id_submitbutton").prop("disabled", false);
        } else {
            // Only add if they don't already have it!
            if (!$(this).closest('.felement').hasClass("error")) {
                $(this).closest('.felement').find('span.error').css("display", "inline-block");
                $(this).closest('.felement').addClass("error");
                $("#id_submitbutton").prop("disabled", true);
            }
        }

    });

});