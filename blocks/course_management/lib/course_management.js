<script>
	$(function(){
	
// link_createparcoursfromgabarit
$("#dialog_createparcoursfromgabarit").dialog({
		autoOpen: false,
		width: 600,
		height: 250,
		title : "Créer un parcours de formation",
		draggable:"false",
		modal: true,
		resizable:false,
		closeOnEscape: false,
		closeText: 'Fermer',
		buttons: [{
			text: "Créer ",
			id:"btCreerCours",
			click: function(){			
				if($('#dialog_createparcoursfromgabarit #new_course_name').val() == '') {
					alert("Le nom est obligatoire");
				}
				else if($('#dialog_createparcoursfromgabarit #new_course_shortname').val() == '') {
					alert("Le nom abrégé est obligatoire");
				}
				else {
					$('#createparcoursfromgabarit_form').submit();
				}				
		}},
		  {
			text: "Annuler",
			id:"btAnnuler",
			click: function(){
				$(this).dialog("close");
	}}]});
	
		$("#link_createparcoursfromgabarit").click(function(e){
			e.preventDefault();
			resetPopinFields();
			$('#dialog_createparcoursfromgabarit').show();
			$('#dialog_createparcoursfromgabarit').dialog('open');
		});

		
//link_creategabaritfromparcours		
$("#dialog_creategabaritfromparcours").dialog({
		autoOpen: false,
		width: 600,
		height: 270,
		title : "Créer un gabarit",
		draggable:"false",
		modal: true,
		resizable:false,
		closeOnEscape: false,
		closeText: 'Fermer',
		buttons: [{
			text: "Créer ",
			id:"btCreerCours",
			click: function(){
				if($('#dialog_creategabaritfromparcours #new_course_name').val() == '') {
					alert("Le nom est obligatoire");
				}
				else if($('#dialog_creategabaritfromparcours #new_course_shortname').val() == '') {
					alert("Le nom abrégé est obligatoire");
				}
				else {
					$('#creategabaritfromparcours_form').submit();
				}	
		}},
		  {
			text: "Annuler",
			id:"btAnnuler",
			click: function(){
				$(this).dialog("close");
	}}]});
	
		$("#link_creategabaritfromparcours").click(function(e){
			e.preventDefault();
			resetPopinFields();
			$('#dialog_creategabaritfromparcours').show();
			$('#dialog_creategabaritfromparcours').dialog('open');
		});
		
		
// link_createsessionfromparcours		
$("#dialog_createsessionfromparcours").dialog({
		autoOpen: false,
		width: 600,
		height: 470,
		title : "Créer une session de formation",
		draggable:"false",
		modal: true,
		resizable:false,
		closeOnEscape: false,
		closeText: 'Fermer',
		buttons: [{
			text: "Créer ",
			id:"btCreerCours",
			click: function(){
				if($('#dialog_createsessionfromparcours #new_course_name').val() == '') {
					alert("Le nom est obligatoire");
				}
				else if($('#dialog_createsessionfromparcours #new_course_shortname').val() == '') {
					alert("Le nom abrégé est obligatoire");
				}
				else {
					$('#createsessionfromparcours_form').submit();
				}	
		}},
		  {
			text: "Annuler",
			id:"btAnnuler",
			click: function(){
				$(this).dialog("close");
	}}]});		
	
		$("#link_createsessionfromparcours").click(function(e){
			e.preventDefault();
			$('#dialog_createsessionfromparcours').show();
			$('#datepicker_session').show();
			$('#dialog_createsessionfromparcours').dialog('open');
		});
		
		
//link_createparcoursfromsession				
$("#dialog_createparcoursfromsession").dialog({
		autoOpen: false,
		width: 600,
		title : "Créer un parcours de formation",
		draggable:"false",
		modal: true,
		resizable:false,
		closeOnEscape: false,
		closeText: 'Fermer',
		buttons: [{
			text: "Créer ",
			id:"btCreerCours",
			click: function(){
				if($('#dialog_createparcoursfromsession #new_course_name').val() == '') {
					alert("Le nom est obligatoire");
				}
				else if($('#dialog_createparcoursfromsession #new_course_shortname').val() == '') {
					alert("Le nom abrégé est obligatoire");
				}
				else {
					$('#createparcoursfromsession_form').submit();
				}	
		}},
		  {
			text: "Annuler",
			id:"btAnnuler",
			click: function(){
				$(this).dialog("close");
	}}]});		
	
		$("#link_createparcoursfromsession").click(function(e){
			e.preventDefault();
			resetPopinFields();
			$('#dialog_createparcoursfromsession').show();
			$('#dialog_createparcoursfromsession').dialog('open');
		});
		
		
// link_archive	
$("#dialog_archive").dialog({
		autoOpen: false,
		width: 600,
		title : "Archivage de la session de formation",
		draggable:"false",
		modal: true,
		resizable:false,
		closeOnEscape: false,
		closeText: 'Fermer',
		buttons: [{
			text: "Archiver",
			id:"btCreerCours",
			click: function(){
					$('#archive_form').submit();
		}},
		  {
			text: "Annuler",
			id:"btAnnuler",
			click: function(){
				$(this).dialog("close");
	}}]});		
	
		$("#link_archive").click(function(e){
			e.preventDefault();
			resetPopinFields();
			$('#dialog_archive').show();
			$('#dialog_archive').dialog('open');
		});
		
		
// link_duplicate			
$("#dialog_duplicate").dialog({
		autoOpen: false,
		width: 600,
		title : "Duplication dans la même catégorie",
		draggable:"false",
		modal: true,
		resizable:false,
		closeOnEscape: false,
		closeText: 'Fermer',
		buttons: [{
			text: "Création du cours",
			id:"btCreerCours",
			click: function(){
				if($('#dialog_duplicate #new_course_name').val() == '') {
					alert("Le nom est obligatoire");
				}
				else if($('#dialog_duplicate #new_course_shortname').val() == '') {
					alert("Le nom abrégé est obligatoire");
				}
				else {
					$('#duplicate_form').submit();
				}	
		}},
		  {
			text: "Annuler",
			id:"btAnnuler",
			click: function(){
				$(this).dialog("close");
	}}]});		
	
		$("#link_duplicate").click(function(e){
			e.preventDefault();
			resetPopinFields();
			$('#dialog_duplicate').show();
			$('#dialog_duplicate').dialog('open');
		});
		
		
// link_unarchive				
$("#dialog_unarchive").dialog({
		autoOpen: false,
		width: 600,
		title : "Réouverture ",
		draggable:"false",
		modal: true,
		resizable:false,
		closeOnEscape: false,
		closeText: 'Fermer',
		buttons: [{
			text: "Rouvrir",
			id:"btCreerCours",
			click: function(){
				$('#unarchive_form').submit();
		}},
		  {
			text: "Annuler",
			id:"btAnnuler",
			click: function(){
				$(this).dialog("close");
	}}]});		
	
	
		$("#link_unarchive").click(function(e){
			e.preventDefault();
			resetPopinFields();
			$('#dialog_unarchive').show();
			$('#dialog_unarchive').dialog('open');
		});

		
	// link_discard
	$("#dialog_discard").dialog({
	autoOpen: false,
	width: 400,
	title : "Mettre à la corbeille",
	draggable:"false",
	modal: true,
	resizable:false,
	closeOnEscape: false,
	closeText: 'Fermer',
	buttons: [{
		text: "Oui",
		id:"btDiscard",
		click: function(){
				$('#discard_form').submit();
	}},
	  {
		text: "Annuler",
		id:"btCancel",
		click: function(){
			$(this).dialog("close");
	}}]});
	
	$("#link_discard").click(function(e){
		e.preventDefault();
		resetPopinFields();
		$('#dialog_discard').show();
		$('#dialog_discard').dialog('open');
	});
	
	
	// link_movetotrash
	$("#dialog_restorefromtrash").dialog({
	autoOpen: false,
	width: 600,
	title : "Restauration du parcours",
	draggable:"false",
	modal: true,
	resizable:false,
	closeOnEscape: false,
	closeText: 'Fermer',
	buttons: [{
		text: "Restaurer",
		id:"btDiscard",
		click: function(){
				$('#restorefromtrash_form').submit();
	}},
	  {
		text: "Annuler",
		id:"btCancel",
		click: function(){
			$(this).dialog("close");
	}}]});
	
	$("#link_restorefromtrash").click(function(e){
		e.preventDefault();
		resetPopinFields();
		$('#dialog_restorefromtrash').show();
		$('#dialog_restorefromtrash').dialog('open');
	});
	
	
//date Picker 
  	$("#datepicker_session").datepicker();
	$("#datepicker_session").datepicker( "option", "monthNames", ['janvier', 'f&eacute;vrier', 'mars', 'avril', 'mai', 'juin', 'juillet', 'ao&ucirc;t', 'septembre', 'octobre', 'novembre', 'd&eacute;cembre'] );
	$("#datepicker_session").datepicker( "option", "monthNamesShort", ['janv.', 'f&eacute;vr.', 'mars', 'avril', 'mai', 'juin', 'juil.', 'ao&ucirc;t', 'sept.', 'oct.', 'nov.', 'd&eacute;c.'] );
	$("#datepicker_session").datepicker( "option", "dayNames", ['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'] );
	$("#datepicker_session").datepicker( "option", "dayNamesShort", ['dim.', 'lun.', 'mar.', 'mer.', 'jeu.', 'ven.', 'sam.'] );
	$("#datepicker_session").datepicker( "option", "dayNamesMin", ['D','L','M','M','J','V','S'] );
	$("#datepicker_session").datepicker( "option", "dateFormat", 'dd/mm/yy');
	$("#datepicker_session").datepicker( "option", "weekHeader", 'Sem.');
	$("#datepicker_session").datepicker( "option", "firstDay", 1);
	$("#datepicker_session").datepicker( "option", "currentText", 'Aujourd\'hui');
	$("#datepicker_session").datepicker( "option", "closeText", 'Fermer');
	$("#datepicker_session").datepicker( "option", "prevText", 'Pr&eacute;c&eacute;dent');
	$("#datepicker_session").datepicker( "option", "nextText", 'Suivant');
	$("#datepicker_session").datepicker( "option", "isRTL", false);
	$("#datepicker_session").datepicker( "option", "showMonthAfterYear", false);
			
});

function resetPopinFields() {
	$("input").each(function() {
		if ( $( this ).is( "#new_course_name" ) ) {
		 $( this ).val('');
		}else if ( $( this ).is( "#new_course_shortname" ) ) {
		 $( this ).val('');
		}else if ( $( this ).is( "#datepicker_session" ) ) {
		 $( this ).val('');
		}
	});	
	$("#new_category_course option").first().prop('selected', true);
}

</script>