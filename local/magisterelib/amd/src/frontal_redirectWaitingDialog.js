/* jshint ignore:start */
define(["jquery", "jqueryui"], function(){

	return {
		init : function() {
			$("#redirect_waiting_popup").dialog({
				modal: true,
				resizable: false,
				draggable: false,
				width: "600px",
				dialogClass: "popup_frontal",
				closeOnEscape: false,
				open: function(event, ui) { $(".ui-dialog-titlebar-close", ui.dialog | ui).hide(); }
		  	});
		}
	}
    
});
