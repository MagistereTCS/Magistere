/* jshint ignore:start */
define(['jquery', 'core/notification', 'jqueryui'], function($,notification) {
    function init() {

        $("#dialog-querystats").dialog({    
            width: 600,
            height: 'auto',
            maxHeight: 470,
            draggable: "false",
            modal: true,
            resizable: false,
            closeOnEscape: false,
            closeText: 'Fermer',
            buttons: [{
                text: "Valider",
                id: "btValidateMails",
                click: function() {
                    $( this ).dialog( "close" );
                }
            }]
        });
    }


    return {
        init: function() {
            init();
        }
    };

});