$(function(){

    $(".datepicker").datepicker({
        monthNames: ["Janvier", "F&eacute;vrier", "Mars", "Avril", "Mai", "Juin", "Juillet", "Ao&ucirc;t", "Septembre", "Octobre", "Novembre", "Décembre"],
        monthNamesShort: ["janv.", "f&eacute;vr.", "mars", "avril", "mai", "juin", "juil.", "ao&ucirc;t", "sept.", "oct.", "nov.", "d&eacute;c."],
        dayNames: ["dimanche", "lundi", "mardi", "mercredi", "jeudi", "vendredi", "samedi"],
        dayNamesShort: ["dim.", "lun.", "mar.", "mer.", "jeu.", "ven.", "sam."],
        dayNamesMin: ["D","L","M","M","J","V","S"],
        dateFormat: "dd/mm/yy",
        weekHeader: "Sem.",
        firstDay: 1,
        currentText: "Aujourd'hui",
        closeText: "Fermer",
        prevText: "Pr&eacute;c&eacute;dent",
        nextText: "Suivant",
        isRTL: false,
        showMonthAfterYear: false
    });
});