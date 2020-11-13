/* jshint ignore:start */
define(["jquery", "jqueryui"], function(){
	
	return {
		init : function() {
			$("#fill_firstname_lastname").dialog({
				modal: true,
				resizable: false,
				draggable: false,
				dialogClass: "popup_frontal fill_firstname_lastname",
				width: "600px",
				closeOnEscape: false,
				open: function(event, ui) { $(".ui-dialog-titlebar-close", ui.dialog | ui).hide(); }
			});
		}
	}

});
