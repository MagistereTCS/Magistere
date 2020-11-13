<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This page prints a particular instance of etherpadlite
 *
 * @package    mod_etherpadlite
 *
 * @author     Timo Welde <tjwelde@gmail.com>
 * @copyright  2012 Humboldt-Universität zu Berlin <moodle-support@cms.hu-berlin.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');

$id = optional_param('id', 0, PARAM_INT); // The course_module id, or
$a = optional_param('a', 0, PARAM_INT);  // etherpadlite instance id.
$g  = optional_param('g', 0, PARAM_INT);  // user group of etherpadlite instance ID
if ($id) {
    $cm = get_coursemodule_from_id('etherpadlite', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
    $etherpadlite = $DB->get_record('etherpadlite', ['id' => $cm->instance], '*', MUST_EXIST);
} else if ($a) {
    $etherpadlite = $DB->get_record('etherpadlite', ['id' => $a], '*', MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $etherpadlite->course], '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('etherpadlite', $etherpadlite->id, $course->id, false, MUST_EXIST);
} else {
    error('You must specify a course_module ID or an instance ID');
}

// This must be here, so that require login doesn't throw a warning.
$PAGE->set_url('/mod/etherpadlite/view.php', ['id' => $cm->id]);
require_login($course, true, $cm);
$cm_context = context_module::instance($cm->id);
$capa_viewallgroups = $can_viewallgroups = has_capability('mod/etherpadlite:viewallgroups', $cm_context);
$config = get_config('etherpadlite');

if ($config->ssl) {
    // The https_required doesn't work, if $CFG->loginhttps doesn't work.
    $CFG->httpswwwroot = str_replace('http:', 'https:', $CFG->wwwroot);
    if (!isset($_SERVER['HTTPS'])) {
        $url = $CFG->httpswwwroot.'/mod/etherpadlite/view.php?id='.$id;

        redirect($url);
    }
}

// Mark as viewed.
$completion = new completion_info($course);
$completion->set_module_viewed($cm);

// START of Initialise the session for the Author.
// Set vars.
$domain = $config->url;
$padid = $etherpadlite->uri;
$fullurl = 'domain.tld';

// Make a new intance from the etherpadlite client.
$instance = new \mod_etherpadlite\client($config->apikey, $domain.'api');

//groupmode (0=No group, 1=separated groups, 2=visible groups)
//groupingid
//groupmembersonly

//echo 'mode='.$cm->groupmode.'&groupingid='.$cm->groupingid;
// Get all groups of this course or grouping if defined
$course_groups = groups_get_all_groups($course->id,0,$cm->groupingid);


// TCS - #2450 - 05/09/2018
if ($course->groupmodeforce == 1) {
    $cm->groupmode = $course->groupmode;
}

$iscurrentpadmember = false;

if ($cm->groupmode > 0)
{
  if ( $cm->groupmode == 2 ) {$can_viewallgroups=true;}
  // ##### Group management
  $user_group = false;
  $user_groups = groups_get_user_groups($course->id);
  
  // The user is member of at least one group of the grouping. We select the first
  if (count($user_groups[$cm->groupingid]) > 0)
  {
  	$user_group = array_values($user_groups[$cm->groupingid])[0];
  }else if ($can_viewallgroups) // The user is not a member of a group but can see all groups of the grouping. We select the first
  {
  	$user_group = array_values($course_groups)[0]->id;
  }
  
  // We check if a specific groupid have been given and if it is valid
  $is_course_group=false;
  foreach($course_groups as $group)
  {
  	if ($group->id == $g)
  	{
  		$is_course_group=true;
  		break;
  	}
  }
  // If the given groupid is valid, we override the default groupid
  if ($is_course_group)
  {
  	
  	// Is member of the group or can see all groups
  	if (in_array($g, $user_groups[$cm->groupingid]) || $can_viewallgroups)
  	{
  		$user_group = $g;
  	}
  } 

  // If no groups have been found.
  // The use has nothing to do here
  if ($user_group == false)
  {
  	throw new Exception("Vous n'êtes membre d'aucun groupe. L'activité n'est donc pas accessible");
  }
  
  // ##### Pad management
  
  // Check if a pad exist for this group
  $etherpadlite_group = $DB->get_record('etherpadlite_groups', array('etherpadid'=>$etherpadlite->id,'groupid'=>$user_group));
  
  // If it does not exist, we create it and insert it in DB
  if ($etherpadlite_group === false)
  {
    try {
      try {
          $groupID = $instance->create_group();
      } catch (Exception $e) {
        // the group already exists or something else went wrong
          //echo "\n\ncreateGroup Failed with message ". $e->getMessage();
          throw $e;
      }
      
      try {
          $padid = $instance->create_group_pad($groupID, $config->padname);
          //echo "Created new pad with padID: $padid\n\n";
      } catch (Exception $e) {
          // the pad already exists or something else went wrong
          //echo "\n\ncreateGroupPad Failed with message ". $e->getMessage();
          throw $e;
      }
      
      // Insert the new pad in the etherpad group table
      $etherpadlite_groups = new StdClass();
      $etherpadlite_groups->etherpadid = $etherpadlite->id;
      $etherpadlite_groups->groupid = $user_group;
      $etherpadlite_groups->uri = $padid;
      $etherpadlite_groups->timecreated = time();
      $etherpadlite_groups->timemodified = time();

      $DB->insert_record('etherpadlite_groups', $etherpadlite_groups);
    } catch (Exception $e) {}
  }
  else // If the pad already exist, we take its uri
  {
      $padid = $etherpadlite_group->uri;
  }
  
  //generate pad selection list
  
  $available_groups_m = array();
  foreach($course_groups as $key=>$group)
  {
  	if ( in_array($group->id,$user_groups[$cm->groupingid]))
  	{
  		$group->member = true;
  		$available_groups_m[] = $group;
  	}
  	else{
  		$group->member = false;
  		$available_groups_nm[] = $group;
  	}
  	
  }

  // Sort the list by membership and name
  function cmp($a, $b)
  {
  	return strcmp($a->name, $b->name);
  }
  
  usort($available_groups_m, "cmp");
  $available_groups = $available_groups_m;
  usort($available_groups_nm, "cmp");
  if ($can_viewallgroups && count($available_groups_nm) > 0)
  {
  	if (count($available_groups_m) > 0)
  	{
  		$available_groups = array_merge($available_groups_m,$available_groups_nm);
  	}else{
  		$available_groups = $available_groups_nm;
  	}
  }
  
  $groups_select = '<select id="sgroups">';
  foreach($available_groups as $group)
  {
  	$groups_select .= '<option value="'.$group->id.'"'.($group->id==$user_group?' selected="selected"':'').'>'.$group->name.($group->member==true?' (membre)':'').'</option>';
  	if ($group->id==$user_group && $group->member==true) {
  	    $iscurrentpadmember = true;
  	}
  }
  $groups_select .= '</select>';
}

//echo '&etherpad_uri='.$padid;

// Transmit the new uri to the rest of the code
//$padId = $padid;

// Fullurl generation.
if ((isguestuser() && !etherpadlite_guestsallowed($etherpadlite)) 
    || ($cm->groupmode == 2 && !$iscurrentpadmember && !$capa_viewallgroups)
    ) {
    try {
        $readonlyid = $instance->get_readonly_id($padid);
        $fullurl = $domain.'ro/'.$readonlyid;
    } catch (Exception $e) {
        throw $e;
    }
}else{
    $fullurl = $domain.'p/'.$padid;
}

// Get the groupID.
$groupid = explode('$', $padid);
$groupid = $groupid[0];

// Create author if not exists for logged in user (with full name as it is obtained from Moodle core library).
try {
    if (isguestuser() && etherpadlite_guestsallowed($etherpadlite)) {
        $authorid = $instance->create_author('Guest-'.etherpadlite_gen_random_string());
    } else {
        $authorid = $instance->create_author_if_not_exists_for($USER->id, fullname($USER));
    }
} catch (Exception $e) {
    // The pad already exists or something else went wrong.
    throw $e;
}

$validuntil = time() + $config->cookietime;
try {
    $sessionid = $instance->create_session($groupid, $authorid, $validuntil);
} catch (Exception $e) {
    throw $e;
}

// If we reach the etherpadlite server over https, then the cookie should only be delivered over ssl.
$ssl = (stripos($config->url, 'https://') === 0) ? true : false;

setcookie('sessionID', $sessionid, $validuntil, '/', $config->cookiedomain, $ssl); // Set a cookie.

// END of Etherpad Lite init.

$context = context_module::instance($cm->id);

// Display the etherpadlite and possibly results.
$eventparams = [
    'context' => $context,
    'objectid' => $etherpadlite->id
];
$event = \mod_etherpadlite\event\course_module_viewed::create($eventparams);
$event->add_record_snapshot('course_modules', $cm);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('etherpadlite', $etherpadlite);
$event->trigger();

$PAGE->set_title(get_string('modulename', 'mod_etherpadlite').': '.format_string($etherpadlite->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

$renderer = $PAGE->get_renderer('mod_etherpadlite');

// Print the page header.
echo $renderer->header();

//TCS BEGIN 2015/11/17 - NNE
$heading = $OUTPUT->heading(format_string($etherpadlite->name), 2);
echo $OUTPUT->add_encart_activity($heading);
//TCS BEGIN 2015/11/17 - NNE


if (isset($available_groups) && count($available_groups) > 1) {
    echo get_string('select_group','etherpadlite');
    echo $groups_select;
    echo '<script type="text/javascript">
            $(function() {
              $("#sgroups").on("change", function() {
                window.location = "'.$PAGE->url.'" + "&g=" + $(this).val()
              });
            });
        </script>';
}

// Print the etherpad content.
echo $renderer->render_etherpad($etherpadlite, $cm, $fullurl);

$ajaxurl = new moodle_url('/mod/etherpadlite/ajax.php', ['a' => 'vp', 'cmid' => $cm->id, 'c' => $course->id]);
$ajaxurl = $ajaxurl->out(false);

echo '<script type="text/javascript">
YUI().use(\'resize\', function(Y) {
    var resize = new Y.Resize({
        //Selector of the node to resize
        node: \'#etherpadiframe\',
        handles: \'br\'
    });
    resize.plug(Y.Plugin.ResizeConstrained, {
        minWidth: 380,
        minHeight: 140,
        maxWidth: 1080,
        maxHeight: 1080
    });
    
});

// custom code to send the participation event
$(function(){
    
    var sendParticipation = function()
    {
        $.ajax({
            url: "'.$ajaxurl.'"
        });    
    }
    
    var overiFrame = -1;
    $("iframe").hover( function() {
        overiFrame = $(this);
    }, function() {
        overiFrame = -1
    });
    
    $(window).blur( function() {
        if( overiFrame != -1 ){
            sendParticipation();
        }
    });
    
})
</script>
';

// Close the page.
echo $renderer->footer();
