<?php
require_once('../../config.php');
require_once("$CFG->libdir/formslib.php");
require_once('form/addResource_form.php');

// affichage du header
echo $OUTPUT->header();

$addResource_form = new addResource_form();

$addResource_form->display();
 ?>
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
