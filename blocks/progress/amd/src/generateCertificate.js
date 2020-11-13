/* jshint ignore:start */
define(['jquery', 'jqueryui'], function($) {
    function init(id, contextid, courseid, imgcalendar){
        var popup_params = {
            id: id,
            contextid: contextid,
            courseid: courseid,
            role: $('select[name="filterform_role"]').val(),
            group : $('select[name="filterform_group"]').val(),
            name: ''+$('input[name="filterform_name"]').val(),
            realized: $('input[name="filterform_realized"]:checked').val(),
            activity: $('select[name="filterform_activity"]').val(),
            neverconnected: $('input[name="filterform_neverconnected"]:checked').length == 1,
            sgaia: JSON.stringify($('input[name^="sgaia"]:checked').serializeArray()),
            sgaiaother: ($('input[name="sother"]:checked').length == 1)
        };

        $("#generate_certificate").load('popup.php', popup_params, function(){
            $("#generate_certificate").dialog({
                modal: true,
                resizable: false,
                draggable: false,
                width: '700px',
                dialogClass: 'popup_frontal generate_certificate',
                closeOnEscape: true
            });

            $("#duration_h").focus();
            $("#enddate_datepicker").datepicker({
                monthNames: ["Janvier", "F&eacute;vrier", "Mars", "Avril", "Mai", "Juin", "Juillet", "Ao&ucirc;t", "Septembre", "Octobre", "Novembre", "Décembre"],
                monthNamesShort: ["janv.", "f&eacute;vr.", "mars", "avril", "mai", "juin", "juil.", "ao&ucirc;t", "sept.", "oct.", "nov.", "d&eacute;c."],
                dayNames: ["dimanche", "lundi", "mardi", "mercredi", "jeudi", "vendredi", "samedi"],
                dayNamesShort: ["dim.", "lun.", "mar.", "mer.", "jeu.", "ven.", "sam."],
                dayNamesMin: ["D","L","M","M","J","V","S"],
                dateFormat: "dd/mm/yy",
                weekHeader: "Sem.",
                firstDay: 1,
                currentText: "Aujourd\'hui",
                closeText: "Fermer",
                prevText: "Pr&eacute;c&eacute;dent",
                nextText: "Suivant",
                isRTL: false,
                showMonthAfterYear: false,
                buttonImage: imgcalendar,
                buttonImageOnly: true,
                showOn: "button"
            });
        });
    }

    return {
        init: function(id, contextid, courseid, imgcalendar){
            $('#generate_certificate').on('click', '#bt_cancel', function () {
                $("#generate_certificate").dialog('close');
                $("#enddate_datepicker").datepicker("destroy");
            });

            $("#generate_certificate_btn").click(function () {
                init(id, contextid, courseid, imgcalendar);
                return false;
            });

            $('#generate_certificate').on('dialogclose', function() {
                $("#generate_certificate_conf").hide();
                $("#generate_certificate_content").show();
            });
        }
    };
});