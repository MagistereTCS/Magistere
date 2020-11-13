<?php
require_once('../../config.php');

require_login();

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_title('Supervision');																	
$PAGE->set_heading('Supervision');
$PAGE->set_pagelayout('standard');
$PAGE->set_url('/local/supervision/');

echo $OUTPUT->header();


//$PAGE->set_url('/local/supervision/index.php');
//$PAGE->set_context(context_system::instance());
//$PAGE->set_pagelayout('standard');
//$context = context_system::instance();

if (!has_capability('moodle/supervision:consult', $context)){
    echo '<div id="access_error">Vous ne possédez pas les droits associés à l\'outil de supervision.</div>'; 
	echo $OUTPUT->footer();
	exit;
}	
$ajax_url = $CFG->wwwroot.'/local/supervision/ajax.php';

//global $OUTPUT;
$icon_progress = $OUTPUT->pix_icon('i/loading_small', 'test').'';
// echo '<div class="filemanager-loading mdl-align">'.$icon_progress.'</div>';

//echo '<pre>'; print_r($USER);

//$USER->profile['FrEduFonctAdm'] = 'IEN1D';
//$USER->profile['rne'] = '0160053W';

//$USER->profile['FrEduFonctAdm'] = '';
//$USER->profile['rne'] = '';

$circonscription = false;
if ( isset($USER->profile['FrEduFonctAdm']) && $USER->profile['FrEduFonctAdm'] = 'IEN1D' )
{
	if ( isset($USER->profile['rne']) && strlen($USER->profile['rne']) > 0 )
	{
		$circonscription = $DB->get_record('t_circonscription', array('code'=>$USER->profile['rne']));
		
	}
}



?>
<div id="supervision_content">
	<table class="form_header">
		 <tr>
		 <td>
			<h1>Outil de supervision</h1>
		</td><td id="spin_loading">
			<?php //echo $icon_progress; ?>
		 </td>
		 </tr>
	</table>
<form id="supervision_intern_search" action="<?php echo $CFG->wwwroot.'/local/supervision/search.php';?>" method="POST" name="supervision_search">
 <table>
	 <tr>
		<td class="label_form" id="public" colspan="2" >
		<h2 style="font-weight:bold;">Public concerné *</h2>
		</td>

	</tr>
	<tr>
		<td class="label_supervision">
			<label for="school_group">Choisir le groupe</label>
		</td>
		<td>
		<?php
			if ($circonscription)
			{
				echo '<input type="hidden" id="group_school" name="school_group[]" value="'.$circonscription->code.'"/> '.$circonscription->libelle_long;
			}else{
				echo '<select class="select_ajax_launcher" id="group_school" name="school_group[]" multiple="multiple"></select>';
			}
		?>
		</td>
	</tr>
	<tr>
		<td class="label_supervision">
			<label for="school_name">Choisir l'établissement</label>
		</td>
		<td>
			<select class="select_ajax_launcher" id="school_name" name="school_name[]" multiple="multiple"></select>
		</td>
	</tr>
	<tr>
		<td class="label_supervision">
			<label for="stagiaire">Choisir le stagiaire</label>
		</td>
		<td>
			<select id="stagiaire_select" name="stagiaire[]" multiple="multiple"></select>
		</td>
	</tr>
	<tr>
		<td class="label_form" colspan="2">
			<h2 style="font-weight:bold;">Période concernée</h2>
		</td>

	</tr>
	<tr>
		<td class="label_supervision">
			<label for="date_debut">Du</label>
		</td>
		<td>
			<input id="date_debut" type="text" name="date_debut">
		</td>
	</tr>
	<tr>
		<td class="label_supervision">
			<label for="date_fin">Au</label>
		</td>
		<td>
			<input id="date_fin" type="text" name="date_fin">
		</td>
	</tr>
	<tr>
		<td class="label_form" colspan="2">
			<h2 style="font-weight:bold;">Nombre d'heures de formation</h2>
		</td>

	</tr>
	<tr>
		<td class="label_supervision">
			où l'enseignant est inscrit
		</td>
		<td>
			<input class="sortie_checkbox" type="radio" name="formation_hours" value="where_teacher_enrolled" checked>
		</td>
	</tr>
	<tr>
		<td class="label_supervision">
			dont la participation est attestée par le formateur
		</td>
		<td>
			<input class="sortie_checkbox" type="radio" name="formation_hours" value="checked_by_tutor">
		</td>
	</tr>
	<tr>
		<td class="label_form" colspan="2">
			<h2 style="font-weight:bold;">Type de sortie *</h2>
		</td>

	</tr>
	<tr>
		<td class="label_supervision">
			Fichier CSV
		</td>
		<td>
			<input class="sortie_checkbox" type="radio" name="sortie" value="sortie_csv">
		</td>
	</tr>
	<tr>
		<td class="label_supervision">
			Fichier xls
		</td>
		<td>
			<input class="sortie_checkbox" type="radio" name="sortie" value="sortie_xls" checked>
		</td>
	</tr>
	<tr>
		<td class="label_supervision">
			A l'écran
		</td>
		<td>
			<input id="test_sort_html" class="sortie_checkbox" type="radio" name="sortie" value="sortie_html">
		</td>
	</tr>
</table>
<div id="submit_btn">
	<input id="submit_btn" type="submit" value="Valider">
</div>
</div>	
</form>
<div id="html_result">

</div>

<script>
$(function(){

	//gestion des calendriers

	$('#date_debut, #date_fin').datepicker();
	$("#date_debut, #date_fin").datepicker( "option", "monthNames", ['janvier', 'f&eacute;vrier', 'mars', 'avril', 'mai', 'juin', 'juillet', 'ao&ucirc;t', 'septembre', 'octobre', 'novembre', 'd&eacute;cembre'] );
	$("#date_debut, #date_fin").datepicker( "option", "monthNamesShort", ['janv.', 'f&eacute;vr.', 'mars', 'avril', 'mai', 'juin', 'juil.', 'ao&ucirc;t', 'sept.', 'oct.', 'nov.', 'd&eacute;c.'] );
	$("#date_debut, #date_fin").datepicker( "option", "dayNames", ['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'] );
	$("#date_debut, #date_fin").datepicker( "option", "dayNamesShort", ['dim.', 'lun.', 'mar.', 'mer.', 'jeu.', 'ven.', 'sam.'] );
	$("#date_debut, #date_fin").datepicker( "option", "dayNamesMin", ['D','L','M','M','J','V','S'] );
	$("#date_debut, #date_fin").datepicker( "option", "dateFormat", 'dd/mm/yy');
	$("#date_debut, #date_fin").datepicker( "option", "weekHeader", 'Sem.');
	$("#date_debut, #date_fin").datepicker( "option", "firstDay", 1);
	$("#date_debut, #date_fin").datepicker( "option", "currentText", 'Aujourd\'hui');
	$("#date_debut, #date_fin").datepicker( "option", "closeText", 'Fermer');
	$("#date_debut, #date_fin").datepicker( "option", "prevText", 'Pr&eacute;c&eacute;dent');
	$("#date_debut, #date_fin").datepicker( "option", "nextText", 'Suivant');
	$("#date_debut, #date_fin").datepicker( "option", "isRTL", false);
	$("#date_debut, #date_fin").datepicker( "option", "showMonthAfterYear", false);

	//au clic sur le bouton "valider" on vide la div html_result
	$(document).on("click", 'input[id="submit_btn"]', function(){
		$('#html_result').empty();
	});
	//au clic sur select_all, on selectionne toutes les options, et seulement après on lance l'appel ajax
	$(document).on("click", 'option[value="select_all"]', function(){
		var select_id = $(this).parent().attr('id');
		$('#'+select_id+' option').prop('selected', 'selected');
		if(select_id != 'stagiaire_select')
		{
			get_select_content(select_id, $('#'+select_id).val());
		}
	});


	

	<?php 
	if ($circonscription)
	{
		echo 'get_select_content("group_school", ["circ_"+$("#group_school").val()]);';
	}else{
		?>
		
		//fonction à executer au chargement de la page (charge la liste des groupes pour le premier select)
	function load_list_group()
	{
		$.ajax({
			type: "POST",
			url: "<?php echo $ajax_url;?>",
			data: { method: 'init_group' }
			}).done(function( msg ) {
				var select_html_content = '';				
				if(msg == ''){
					$('#supervision_content').html('<div id="access_error">Erreur. Aucunes circonscriptions trouvées. Vous ne pouvez pas utiliser l\'outil de supervision.</div>');
				}else{		
					var json_data = $.parseJSON(msg);
					$(json_data).each(function(i,val){
						$.each(val,function(k,v){
							select_html_content += '<option value="'+k+'">'+v+'</option>';
						});
					});
					$('#group_school').html(select_html_content).trigger('change');
				}	
			});	
	}
		
		
		load_list_group();

		<?php 
	}
	?>

	//méthode ajax qui remplit les select
	function get_select_content(select_id, select_value)
	{		
		$('#spin_loading').html('<?php echo $icon_progress;?>');
		//on envoi pas la valeur select_all, toutes les options sont selectionnées et l'appel se fait sur le onclick au dessus
		if(select_value=='select_all')
		{
			return;
		}
		$.ajax({
			type: "POST",
			url: "<?php echo $ajax_url;?>",
			data: { select_id: select_id, select_value: select_value }
			}).done(function( msg ) {
				build_selects(select_id, msg);
				 $('#spin_loading').html('');	
			});
		
		
	}

	//combobox settings
	function build_selects(select_source_id, msg)
	{
		//on défini le select cible (celui à traiter)
		if(select_source_id == 'group_school')
		{
			target_select_id = '#school_name';
		}
		else if(select_source_id == 'school_name')
		{
			target_select_id = '#stagiaire_select';
		}
		//on vide le select cible
		$(target_select_id).html('');
		var select_html_content = '';
		var json_data = $.parseJSON(msg);
		$(json_data).each(function(i,val){
			$.each(val,function(k,v){
				select_html_content += '<option value="'+k+'">'+v+'</option>';
			});
		});
		$(target_select_id).html(select_html_content);
		$(target_select_id).trigger('change');
	}

	//au changement de valeur sur le premier ou deuxieme select, on charge les autres select en conséquence
	$('.select_ajax_launcher').change(function(){
		
			get_select_content($(this).attr('id'), $(this).val());
	});
	
	
	$('#supervision_intern_search').submit(function(){
		if($('input[name=sortie]:checked').attr('value') == undefined)
		{
			alert('Vous devez sélectionner un format de sortie');
			return false;
		}
		else if($('input[name=sortie]:checked').attr('value') == 'sortie_html')
		{
			$('#spin_loading').html('<?php echo $icon_progress;?>');
			$.ajax({
			type: "POST",
			dataType: "html",
			url: "<?php echo $CFG->wwwroot.'/local/supervision/search.php';?>",
			data: { stagiaire: $('#stagiaire_select').val(), sortie:'sortie_html', date_debut:$('#date_debut').val(), date_fin:$('#date_fin').val(), formation_hours: $('input[name=formation_hours]:checked').val() }
			}).done(function( msg ) {
				$('#html_result').html(msg);
				$('#spin_loading').html('');
			});
			

			return false;
		}
		return true;
	});

});

</script>

<?php


echo $OUTPUT->footer();

?>