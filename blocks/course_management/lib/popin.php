<?php 
	$nexturl = new moodle_url("/blocks/course_management/duplication.php?");
	
	global $COURSE;
	
	$fullname = ($COURSE->fullname !== '' ? $COURSE->fullname : '');
	$shortname = ($COURSE->shortname !== '' ? $COURSE->shortname : '');
?>

<div id="dialog_createparcoursfromgabarit" style="display:none;">
	<div style="font-size: 10px; color: #515151;">Merci de renseigner les champs suivants afin de r&eacute;aliser la cr&eacute;ation du parcours<br/><br/></div>
	<form  method="POST" action="<?php echo $nexturl ?>" name="restore_form" id="createparcoursfromgabarit_form">		
		<input type="hidden"  id="blockinstanceid" name="blockinstanceid" value="<?php echo $blockinstanceid; ?>" />
		<input type="hidden"  id="link_type" name="link_type" value="createparcoursfromgabarit" />
		<input type="hidden"  id="course_id" name="course_id" value="<?php echo $PAGE->course->id; ?>" />
		<table style="font-size: 12px; color: black;">
			<tr align="left">
				<td><label>Nom : *</label></td>
				<td><input type="text" size="50%" name="new_course_name" id="new_course_name" /></td>
			</tr>
			<tr >
				<td><label>Nom abr&eacute;g&eacute; : *</label></td>
				<td><input type="text" size="50%" name="new_course_shortname" id="new_course_shortname" /></td>
			</tr>
			<tr id='tr_subcategory'>
				<td><label>Sous-cat&eacute;gorie : </label></td>
				<td>
					<select name="new_category_course" style="width:100%" id="new_category_course" >						
						<?php
							if(in_array('createparcoursfromgabarit',$l_links_type)){
								echo subcategory_select_content('createparcoursfromgabarit');
							}
						?>
					</select>
				</td>
			</tr>
		</table>
	</form>	
</div>


<div id="dialog_creategabaritfromparcours" style="display:none;">
	<div style="font-size: 10px; color: #515151;">Merci de renseigner les champs suivants afin de r&eacute;aliser la cr&eacute;ation du parcours<br/><br/></div>
	<form  method="POST" action="<?php echo $nexturl ?>" name="restore_form" id="creategabaritfromparcours_form">	
		<input type="hidden"  id="blockinstanceid" name="blockinstanceid" value="<?php echo $blockinstanceid; ?>" />
		<input type="hidden"  id="link_type" name="link_type" value="creategabaritfromparcours" />
		<input type="hidden"  id="course_id" name="course_id" value="<?php echo $PAGE->course->id; ?>" />
		<table style="font-size: 12px; color: black;">
			<tr align="left">
				<td><label>Nom : *</label></td>
				<td><input type="text" size="50%" name="new_course_name" id="new_course_name" /></td>
			</tr>
			<tr >
				<td><label>Nom abr&eacute;g&eacute; : *</label></td>
				<td><input type="text" size="50%" name="new_course_shortname" id="new_course_shortname" /></td>
			</tr>
			<tr id='tr_cat_popin_block_course_management'>
				<td><label>Sous-cat&eacute;gorie : </label></td>
				<td>
					<select name="new_category_course" style="width:100%" id="new_category_course" >
						<?php
							if(in_array('creategabaritfromparcours',$l_links_type)){
								echo subcategory_select_content('creategabaritfromparcours');
							}
						?>
					</select>
				</td>
			</tr>
		</table>
	</form>
</div>

				
<div id="dialog_createsessionfromparcours" style="display:none;">
	<div style="font-size: 10px; color: #515151;"><?php echo get_string('createsessiondesc', 'block_course_management'); ?><br/><br/></div>
	<form  method="POST" action="<?php echo $nexturl ?>" name="restore_form" id="createsessionfromparcours_form">
		<input type="hidden"  id="blockinstanceid" name="blockinstanceid" value="<?php echo $blockinstanceid; ?>" />
		<input type="hidden"  id="link_type" name="link_type" value="createsessionfromparcours" />
		<input type="hidden"  id="course_id" name="course_id" value="<?php echo $PAGE->course->id; ?>" />
		<table style="font-size: 12px; color: black;">
		<tr align="left">
				<td><label><?php echo get_string('createsession', 'block_course_management'); ?></label></td>
				<td><label for="move_type_copy"><input type="radio" name="move_type" value="duplication" id="move_type_copy" checked/>En copiant le parcours</label><br/>
				<label for="move_type_move"><input type="radio" name="move_type" value="move" id="move_type_move"/>En d&eacute;pla&ccedil;ant le parcours</label></td>
			</tr>
			<tr align="left">
				<td><label><?php echo get_string('coursename', 'block_course_management'); ?></label></td>
				<td><input type="text" size="50%" name="new_course_name" id="new_course_name" value="<?php echo $fullname; ?>"/></td>
			</tr>
			<tr >
				<td><label><?php echo get_string('courseshortname', 'block_course_management'); ?></label></td>
				<td><input type="text" size="50%" name="new_course_shortname" id="new_course_shortname" value="<?php echo $shortname; ?>"/><br/>
				<em><?php echo get_string('courseshortnamehelp', 'block_course_management'); ?></em></td>
			</tr>
			<tr id='tr_cat_popin_block_course_management'>
				<td><label><?php echo get_string('subcategory', 'block_course_management'); ?></label></td>
				<td>
					<select name="new_category_course" style="width:100%" id="new_category_course" >
						<?php
							if(in_array('createsessionfromparcours',$l_links_type)){
								echo subcategory_select_content('createsessionfromparcours');
							}
						?>
					</select>
				</td>
			</tr>
			<tr id="tr_datepicker_session">
				<td><label><?php echo get_string('startdatecourse', 'block_course_management'); ?></label></td>
				<td><input size="50%" name="datepicker_session" id="datepicker_session" /></td>
			</tr>
		</table>
	</form>
</div>

				
<div id="dialog_createparcoursfromsession" style="display:none;">
	<div style="font-size: 10px; color: #515151;">Merci de renseigner les champs suivants afin de r&eacute;aliser la cr&eacute;ation du parcours<br/><br/></div>
	<form  method="POST" action="<?php echo $nexturl ?>" name="restore_form" id="createparcoursfromsession_form">
		<input type="hidden"  id="blockinstanceid" name="blockinstanceid" value="<?php echo $blockinstanceid; ?>" />
		<input type="hidden"  id="link_type" name="link_type" value="createparcoursfromsession" />
		<input type="hidden"  id="course_id" name="course_id" value="<?php echo $PAGE->course->id; ?>" />
		<table style="font-size: 12px; color: black;">
			<tr align="left">
				<td><label>Nom : *</label></td>
				<td><input type="text" size="50%" name="new_course_name" id="new_course_name" /></td>
			</tr>
			<tr >
				<td><label>Nom abr&eacute;g&eacute; : *</label></td>
				<td><input type="text" size="50%" name="new_course_shortname" id="new_course_shortname" /></td>
			</tr>
			<tr id='tr_cat_popin_block_course_management'>
				<td><label>Sous-cat&eacute;gorie : </label></td>
				<td>
					<select name="new_category_course" style="width:100%" id="new_category_course" >
						<?php
							if(in_array('createparcoursfromsession',$l_links_type)){
								echo subcategory_select_content('createparcoursfromsession');
							}
						?>
					</select>
				</td>
			</tr>
		</table>
	</form>
</div>

				
<div id="dialog_archive" style="display:none;">
	<form  method="POST" action="<?php echo $nexturl ?>" name="restore_form" id="archive_form">
		<input type="hidden"  id="blockinstanceid" name="blockinstanceid" value="<?php echo $blockinstanceid; ?>" />
		<input type="hidden"  id="link_type" name="link_type" value="archive" />
		<input type="hidden"  id="course_id" name="course_id" value="<?php echo $PAGE->course->id; ?>" />
		<table style="font-size: 12px; color: black;">
			<tr id='tr_cat_popin_block_course_management'>
				<td><label>Sous-cat&eacute;gorie : </label></td>
				<td>
					<select name="new_category_course" style="width:100%" id="new_category_course" >
						<?php
							if(in_array('archive',$l_links_type)){
								echo subcategory_select_content('archive');
							}
						?>
					</select>
				</td>
			</tr>
			<tr>
				<td>
					<input type="radio" name="access" value="visible" checked>
					Avec accès des participants
				</td>
			</tr>
			<tr>				
				<td>
					<input type="radio" name="access" value="hidden">
					Sans accès des participants
				</td>
			</tr>
		</table>
	</form>
</div>

				
<div id="dialog_duplicate" style="display:none;">
	<div style="font-size: 10px; color: #515151;">Merci de renseigner les champs suivants afin de r&eacute;aliser la cr&eacute;ation du parcours<br/><br/></div>
	<form  method="POST" action="<?php echo $nexturl ?>" name="restore_form" id="duplicate_form">
		<input type="hidden"  id="blockinstanceid" name="blockinstanceid" value="<?php echo $blockinstanceid; ?>" />
		<input type="hidden"  id="link_type" name="link_type" value="duplicate" />
		<input type="hidden"  id="course_id" name="course_id" value="<?php echo $PAGE->course->id; ?>" />
		<table style="font-size: 12px; color: black;">
			<tr align="left">
				<td><label>Nom : *</label></td>
				<td><input type="text" size="50%" name="new_course_name" id="new_course_name" /></td>
			</tr>
			<tr >
				<td><label>Nom abr&eacute;g&eacute; : *</label></td>
				<td><input type="text" size="50%" name="new_course_shortname" id="new_course_shortname" /></td>
			</tr>
			<tr id='tr_cat_popin_block_course_management'>
				<td><label>Sous-cat&eacute;gorie : </label></td>
				<td>
					<select name="new_category_course" style="width:100%" id="new_category_course" >
						<?php
							if(in_array('duplicate',$l_links_type)){
								echo subcategory_select_content('duplicate', $course_category);
							}
						?>
					</select>
				</td>
			</tr>
		</table>
	</form>
</div>
				
<div id="dialog_unarchive" style="display:none;">
	<div style="font-size: 10px; color: #515151;">Merci de renseigner la sous-catégorie de session de formation afin de rouvrir ce parcours.<br/><br/></div>
	<form  method="POST" action="<?php echo $nexturl ?>" name="restore_form" id="unarchive_form">
		<input type="hidden"  id="blockinstanceid" name="blockinstanceid" value="<?php echo $blockinstanceid; ?>" />
		<input type="hidden"  id="link_type" name="link_type" value="unarchive" />
		<input type="hidden"  id="course_id" name="course_id" value="<?php echo $PAGE->course->id; ?>" />
		<table style="font-size: 12px; color: black;">
			<tr id='tr_cat_popin_block_course_management'>
				<td><label>Sous-cat&eacute;gorie : </label></td>
				<td>
					<select name="new_category_course" style="width:100%" id="new_category_course" >
						<?php
							if(in_array('unarchive',$l_links_type)){
								echo subcategory_select_content('unarchive');
							}
						?>
					</select>
				</td>
			</tr>
		</table>
	</form>
</div>

<div id="dialog_discard" style="display:none;">
	<form  method="POST" action="<?php echo $nexturl ?>" name="discard_form" id="discard_form">
		<input type="hidden"  id="blockinstanceid" name="blockinstanceid" value="<?php echo $blockinstanceid; ?>" />
		<input type="hidden"  id="link_type" name="link_type" value="discard" />
		<input type="hidden"  id="course_id" name="course_id" value="<?php echo $PAGE->course->id; ?>" />
		<table style="font-size: 12px; color: black;">
			<?php
			
			echo '<tr><td>Le parcours va être déplacé dans la corbeille, celui-ci sera supprimé définitivement dans 6 mois.</td></tr>';
			global $DB;
			$published = $DB->record_exists('course_published', array('courseid'=>$PAGE->course->id));
			if ($published)
			{
				echo '<tr><td>Ce parcours est partagé sur l’offre de parcours / publier pour inscription, la mise à la corbeille induira la suppression de cette publication</td></tr>';
			}
			
			echo '<tr><td>Souhaitez-vous continuer ?</td></tr>';
			
			?>
		</table>
	</form>
</div>

<div id="dialog_restorefromtrash" style="display:none;">
	<div style="font-size: 10px; color: #515151;">Merci de renseigner les champs suivants afin de r&eacute;aliser la cr&eacute;ation du parcours<br/><br/></div>
	<form  method="POST" action="<?php echo $nexturl ?>" name="restorefromtrash_form" id="restorefromtrash_form">
		<input type="hidden"  id="blockinstanceid" name="blockinstanceid" value="<?php echo $blockinstanceid; ?>" />
		<input type="hidden"  id="link_type" name="link_type" value="restorefromtrash" />
		<input type="hidden"  id="course_id" name="course_id" value="<?php echo $PAGE->course->id; ?>" />
		<table style="font-size: 12px; color: black;">
			<tr id='tr_cat_popin_block_course_management'>
				<td><label>Sous-cat&eacute;gorie : </label></td>
				<td>
					<select name="new_category_course" style="width:100%" id="new_category_course" >
						<?php
							if(in_array('restorefromtrash',$l_links_type)){
								
								echo subcategory_select_content('restorefromtrash');
							}
						?>
					</select>
				</td>
			</tr>
		</table>
	</form>
</div>