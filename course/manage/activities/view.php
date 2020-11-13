<?php

//  Display the course home page.

    require_once('../../../config.php');
	
    $id = required_param('id', PARAM_INT);
	$delete = optional_param('delete', 0, PARAM_INT);

    $course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);

    $urlparams = array('id' => $course->id);

    $PAGE->set_pagelayout("admin");
    $PAGE->set_url('/course/manage_activities/view.php', $urlparams); // Defined here to avoid notices on errors etc

    // Prevent caching of this page to stop confusion when changing page after making AJAX changes
    $PAGE->set_cacheable(false);

    context_helper::preload_course($course->id);
    $context = context_course::instance($course->id, MUST_EXIST);
    
    require_login($course);
    
    require_capability('course/manageactivities:manage', $context);

    $PAGE->navbar->add(get_string('pluginname', 'core_manageactivities'));
    
    if($delete == 1){
    	$activities = required_param('activities', PARAM_RAW);
    	
    	foreach($activities as $activityid){
    		course_delete_module($activityid);
    	}
    }
    
    $modinfo = get_fast_modinfo($id);
    
    $activities = array();
    $jsdata = array();
    foreach ($modinfo->get_instances() as $module => $instances) {
    	$modulename = get_string('modulenameplural', $module);

    	foreach ($instances as $instance) {


    		if(!isset($activities[$instance->modname])){
    			$activities[$instance->modname] = new stdClass();
    			$activities[$instance->modname]->modulenameplural = $modulename;
    			$activities[$instance->modname]->activities = array();
    		}
    		
    		$activity = new stdClass();
    		
    		$activity->id = $instance->id;
    		$activity->name = $instance->name;
    		$activity->visible = $instance->visible;
    		$activity->blocks = array();

    		$blocks = $DB->get_records('block_flexpagemod', array('cmid' => $instance->id));
    		if(empty($blocks)){
    			//Cas special pour les forums de type news
    			if($instance->modname == "forum"){
    				$blocks = array();

    				$forum = $DB->get_records('forum', array('id' => $instance->instance));
    				if($forum){
    					foreach($forum as $f){
    						if($f->type == "news"){
    							$mdlblock = $DB->get_records('block_instances', array('parentcontextid' => $context->id, 'blockname' => 'news_items'));
    							foreach($mdlblock as $b){
    								$tmp = new stdClass();
    								$tmp->id = $b->id;
    								$tmp->instanceid = $b->id;
    								$tmp->cmid = $instance->id;
    								$blocks[] = $tmp;
    							}
    							break;		
    						}
    					} 
    				}
    			}

				$instance_menu = $DB->get_records_sql('
SELECT * 
FROM {block_flexpagenav_menu} bfm 
INNER JOIN {block_flexpagenav_link} bfl ON bfl.menuid = bfm.id 
INNER JOIN {block_flexpagenav_config} bfc ON bfc.linkid = bfl.id
WHERE bfm.courseid = '. $course->id .' AND bfc.value = ' . $instance->id . ' AND bfc.name = "cmid"');
								
			}


    		
    		if(!empty($blocks)){
	    		$blocksid = array();
	    		foreach($blocks as $block){
	    			$blocksid[] = 'mbi.id = ' . $block->instanceid;
	    		};
	    		
	    		$activity->blocks = $DB->get_records_sql('SELECT 
	    						mbi.id as instanceid,
		    					mbi.subpagepattern as pageid,
		    					mffp.name as pagename,
	    						mbi.pagetypepattern as pagetypepattern
	    					FROM {block_instances} mbi
	    					LEFT JOIN {format_flexpage_page} mffp
	    					ON mffp.id = mbi.subpagepattern 
	    					WHERE  ('. implode($blocksid, ' OR ') .') AND mbi.parentcontextid = '.$context->id);	    		
    		}
    		
    		array_push($activities[$instance->modname]->activities, $activity);

    		if(isset($instance_menu)){
                $activity->nbInstance = count($activity->blocks) + count($instance_menu);
            } else {
                $activity->nbInstance = count($activity->blocks);
            }
    		
    		$jsdata[$instance->id] = new stdClass();
    		$jsdata[$instance->id]->name = $activity->name;
    		$jsdata[$instance->id]->count_blocks = $activity->nbInstance;
    	}
    }
    //HTML
    echo $OUTPUT->header();

    ?>

    
    <style>
    
    #form_manage_activities table{
    	width: 100%;
    }
    
    #form_manage_activities td{
    	vertical-align: top;
    	padding-top: 15px;
    	padding-bottom: 15px;
    }
    
    #form_manage_activities ul{
    	margin:0;
    }
    
    #validation_popup ul, #validation_popup li{
    	list-style: disc outside;
    }
    
    #form_manage_activities .mod_category td{
    	padding-bottom: 10px;
    	padding-top: 40px;
    }
    
    #form_manage_activities .activity{
    	border-top: 1px solid #ececec;
    	border-bottom: 1px solid #ececec;
    }
    
    #form_manage_activities  .bar_button{
    	padding-top: 20px;
    }
    
    .ui-dialog.validation_popup{
		background-color: #FFF;
		padding: 23px;
	}
	
	.ui-dialog.validation_popup .ui-dialog-content{
		overflow: hidden;
		border: none;
	}
	
	.ui-dialog.validation_popup .ui-dialog-buttonpane{
		background: none;
		border: none;
	}
	
	.ui-dialog.validation_popup .ui-dialog-titlebar{
		background-color: #FFF;
		border: none;
		border-bottom: solid 5px #ececec;
		color: #202a30;
		font-size: 17px;
	}
	
	.smallicon{
		margin-right: 10px;
	}
	
	.padding-checkbox{
		padding-left: 27px;
	}
    </style>
<?php 
	$parcoursUrl = new moodle_url('/course/view.php', array('id' => $id));
?>
<a id="return_btn_offer" class="btn" href="<?php echo $parcoursUrl; ?>"><?php echo get_string('gobacktocourse', 'core_manageactivities');?></a><br/><br/>

<?php
	if(count($activities) == 0){
		echo '<p style="text-align: center">' . get_string('noactivities', 'core_manageactivities') . '</p>';
	}else{
	    $action_url = new moodle_url('/course/manage/activities/view.php', array('id' => $id));
	    
	    $form = '<form id="form_manage_activities" method="post" action="'.$action_url.'">';
	    
	    $form .= '<table>';
	    
	    foreach($activities as $modulename => $module){
	    	$form .= '<tr class="mod_category"><td>';
	    	$form .=  $OUTPUT->pix_icon('icon', $module->modulenameplural, $modulename) . $module->modulenameplural;
	    	$form .= '</td><tr>';
	
	    	foreach($module->activities as $activity){
	    		$class = ($activity->nbInstance == 0 ? 'class="unused_activity"' : '');
	    		
	    		$modurl = new moodle_url('/mod/' . $modulename . '/view.php', array('id' => $activity->id));
	    		$form .= '<tr class="activity">';
    			
    			if($activity->nbInstance > 0){
	    			$form .= '<td class="padding-checkbox">';
    			}else{
    				$form .= '<td><input type="checkbox" name="activities[]" value="'.$activity->id.'"/>';
    			}
    		
    			
    			$form .= '<a href="'.$modurl.'">' . $activity->name . '</a>';
	    		$form .= '</td>';
	    		
	    		if($activity->nbInstance > 0){
	    			$form .= '<td>';
	    			$srcImg = '';
	    			if($activity->visible == 1){
	    			    $srcImg = $OUTPUT->image_url('general/action_show_pink', 'theme');
	    			}else{
	    			    $srcImg = $OUTPUT->image_url('general/action_hide_pink', 'theme');
	    			}
	    			 
	    			if(empty($srcImg) == false){
	    				$form .= '<img src="'.$srcImg.'"/>';
	    			}
	    			
	    			$form .= '</td>';
	    			
	    			$instancedOnSeveralPages = false;
	    			$msgToDisplay = '';

	    			foreach($activity->blocks as $block){
	    				if($block->pageid == null){
	    					$instancedOnSeveralPages = true;
	    					if($block->pagetypepattern == "course-view-*"){
	    						$msgToDisplay = get_string('usedoncourseview', 'core_manageactivities');
	    					}else if($block->pagetypepattern == "course-*"){
	    						$msgToDisplay = get_string('usedonpage', 'core_manageactivities');
	    					}else{
	    						$msgToDisplay = get_string('usedoncourse', 'core_manageactivities');
	    					}
	    					break;
	    				}
	    			}
	    			//if the activity is instanciated on a specific pages
	    			if($instancedOnSeveralPages == false){
	    				$form .= '<td>'.get_string('usedactivity', 'core_manageactivities', $activity->nbInstance).'</td>';
	    				$form .= '<td><ul>';
	    				
	    				foreach($activity->blocks as $block){
	    					if($block->pageid != null){
	    						$url = new moodle_url('/course/view.php', array('id' => $id, 'pageid' => $block->pageid));
	    						$form .= '<li><a href="' . $url . '">'.get_string('gotopage', 'core_manageactivities', $block->pagename).'</a></li>';
	    					}
	    				}
	    				$form .= '</ul></td>';
	    			}else{
	    				$form .= '<td>'.$msgToDisplay.'</td>';
	    				$form .= '<td></td>';
	    			}
	    		}else{
	    			$form .= '<td style="color:red">'.get_string('unusedactivity', 'core_manageactivities').'</td><td></td><td></td>';
	    		}
	    		
	    		$form .= '</tr>';
	    	}
	    }
	    
	    $form .= '</table>
		    <input type="hidden" name="delete" value="1"/>
	    	<div class="bar_button">
	    	<input id="delete_activities" type="button" value="'.get_string('deleteselection', 'core_manageactivities').'"/>
		    </div>
	    </form>';
	    
	    echo $form;
	    
	    ?>
	
	    <div id="validation_popup" style="display: none;" title="<?php echo get_string('titlevalidationpopup', 'core_manageactivities');?>">
	    	<p><?php echo get_string('deletemessage', 'core_manageactivities'); ?></p>
	    	<ul id="activities_list">
	    	
	    	</ul>
	    	
	    	<p>
	    	<?php echo get_string('confirmmessage', 'core_manageactivities'); ?>
	    	</p>
	    </div>
<?php
	}
    echo $OUTPUT->footer();
?>

<script>

var data = <?php echo json_encode($jsdata); ?>

console.log(data);

$('#form_manage_activities input[type=checkbox]').change(function(){
	$('#activities_list').html('');
	
	$('input[type=checkbox]:checked').each(function(){
		if(data[$(this).attr('value')]){
			var text = '<li><b>'+data[$(this).attr('value')].name+'</b>';
			
			if(data[$(this).attr('value')].count_blocks > 0){
				text += ' <?php echo get_string('usedactivity', 'core_manageactivities');?>'.replace('{$a}', data[$(this).attr('value')].count_blocks); 
			}else{
				text += ' <?php echo get_string('unusedactivity', 'core_manageactivities');?>'
			}

			text += '</li>';
			
			$('#activities_list').append(text);
		}
	});
});

$('#delete_activities').click(function(){

	var nbChecked = 0;
	$('input[type=checkbox]:checked').each(function(){
		if($(this).attr('id') !== 'select_all'){
			nbChecked++;
		}
	});
		
	if(nbChecked > 0){
		$( "#validation_popup" ).dialog({
		  position: {my: "center", at: "top", of: window},
	      resizable: false,
	      draggable: false,
	      modal: true,
	      width: '700px',
	      dialogClass: 'validation_popup',
	      buttons: {
	    	"<?php echo get_string('popupclose', 'core_manageactivities');?>": function() {
	              $( this ).dialog( "close" );
	            },
	        "<?php echo get_string('popupok', 'core_manageactivities');?>": function() {
	          $( this ).dialog( "close" );
	          $('#form_manage_activities').submit();
	        }
	        
	      }
	    });
	}
});
</script>
