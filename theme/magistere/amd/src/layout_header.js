define(["jquery","jqueryui"], function () {

	function open_reset_mainaca()
    {
        $("#reset_mainaca").dialog({
            modal: true,
            resizable: false,
            draggable: false,
            width: '500px',
            dialogClass: 'popup_frontal reset_mainaca',
            closeOnEscape: true
        });
    }

    function reset_submit(ajaxUrl)
    {
        $("#reset_mainaca_conf").html("Chargement...");
        $("#reset_mainaca_content").hide();
        $("#reset_mainaca_conf").show();
        $.get(ajaxUrl, function(data, status){
            if (data == 'success')
            {
                $("#reset_mainaca_conf").html('R&eacute;initialisation temin&eacute;e<br/><br/><input name="cancel" value="Fermer" type="button" id="bt_close">');
                $("#bt_close").click(function () {
                    $("#reset_mainaca").dialog('close');
                });
            }
            else
            {
                $("#reset_mainaca_conf").html('La r&eacute;initialisation a echou&eacute;e<br/><br/><input name="cancel" value="Fermer" type="button" id="bt_close">');
                $("#bt_close").click(function () {
                    $("#reset_mainaca").dialog('close');
                });
            }
        });
    }

	return {
		init : function(ajaxUrl) {
	        $("#bt_validate").click(function () {
	            reset_submit(ajaxUrl);
	        });
	        $("#bt_cancel").click(function () {
	            $("#reset_mainaca").dialog('close');
	        });
	        $("a[data-title='changeaca,moodle']").click(function (e) {
	            open_reset_mainaca();
	            return false;
	        });
	        $('#reset_mainaca').on('dialogclose', function(event) {
	            $("#reset_mainaca_conf").hide();
	            $("#reset_mainaca_content").show();
	        });
		}
	};

});