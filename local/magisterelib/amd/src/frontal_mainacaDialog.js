/* jshint ignore:start */
define(["jquery", "jqueryui", "local_magisterelib/tooltipster", "local_magisterelib/vmap"], function($){
	
	return {
		init : function() {
			$("#fill_main_aca").dialog({
				modal: true,
				resizable: false,
				draggable: false,
				width: "900px",
				dialogClass: "popup_frontal fill_main_aca no-close-dialog",
				closeOnEscape: false,
				autoOpen: true
			});
	
			$("#francemap").vectorMap({
				map: "france_fr",
				hoverOpacity: 0,
		        hoverColor: "#007fd0",
		        backgroundColor: "#ffffff",
		        color: "#99cbeb",
		        borderColor: "#FFF",
		        selectedColor: "#99cbeb",
				enableZoom: false,
				showTooltip: true,
				onRegionClick: function(element, code, region)
				{
					$("select[name=main_academy]").val(code).change();
				}
			});
	
	        $("#id_main_academy").change(function() {
	            $("#id_main_academy").closest("form").submit();
	        });
			$("#platforme_instit a, .link_dommap").click(function(){
				var code = $(this).attr("href").replace("#", "");
				
				$("select[name=main_academy]").val(code).change();
			});
			
			$(".link_dommap img").hover(function(){
				var src = $(this).attr("src");
				src += "_hover";
				$(this).attr("src", src);
			}, function(){
				var src = $(this).attr("src");
				src = src.replace("_hover", "");
				$(this).attr("src", src);
			});
			
			$(document).tooltip({track: true});
		}
	}

});
