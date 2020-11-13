/* jshint ignore:start */
define(['jquery', 'jqueryui'], function($) {
    function init(){
        //date Picker
        $("#datepicker_session").datepicker();
        $("#datepicker_session").datepicker("option", "monthNames", ['janvier', 'f&eacute;vrier', 'mars', 'avril', 'mai', 'juin', 'juillet', 'ao&ucirc;t', 'septembre', 'octobre', 'novembre', 'd&eacute;cembre']);
        $("#datepicker_session").datepicker("option", "monthNamesShort", ['janv.', 'f&eacute;vr.', 'mars', 'avril', 'mai', 'juin', 'juil.', 'ao&ucirc;t', 'sept.', 'oct.', 'nov.', 'd&eacute;c.']);
        $("#datepicker_session").datepicker("option", "dayNames", ['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi']);
        $("#datepicker_session").datepicker("option", "dayNamesShort", ['dim.', 'lun.', 'mar.', 'mer.', 'jeu.', 'ven.', 'sam.']);
        $("#datepicker_session").datepicker("option", "dayNamesMin", ['D', 'L', 'M', 'M', 'J', 'V', 'S']);
        $("#datepicker_session").datepicker("option", "dateFormat", 'dd/mm/yy');
        $("#datepicker_session").datepicker("option", "weekHeader", 'Sem.');
        $("#datepicker_session").datepicker("option", "firstDay", 1);
        $("#datepicker_session").datepicker("option", "currentText", 'Aujourd\'hui');
        $("#datepicker_session").datepicker("option", "closeText", 'Fermer');
        $("#datepicker_session").datepicker("option", "prevText", 'Pr&eacute;c&eacute;dent');
        $("#datepicker_session").datepicker("option", "nextText", 'Suivant');
        $("#datepicker_session").datepicker("option", "isRTL", false);
        $("#datepicker_session").datepicker("option", "showMonthAfterYear", false);
    }

    return {
        init: function(){
            init();
        }
    };
});