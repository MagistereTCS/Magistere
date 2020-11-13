<?php
global $PAGE, $CFG, $OUTPUT;

require_once('../../config.php');

// affichage du header
echo $OUTPUT->header();

?>

<form>
	<input type="button" value="Ajouter une ressource" id="addRessource" />
</form>

<!-- JAVASCRIPT -->

<script>
	$(function(){
		$("#addRessource").click(function(){
			window.location.href = "addResource.php";
		});
	});
</script>


<?php
// affichage du footer
echo $OUTPUT->footer();
?>
