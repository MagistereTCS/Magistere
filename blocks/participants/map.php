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
 * Lists all the users within a given course.
 *
 * @copyright 1999 Martin Dougiamas  http://dougiamas.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package core_user
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->dirroot.'/local/interactive_map/InteractiveMap.php');
require_once($CFG->dirroot.'/lib/badgeslib.php');

$courseid = required_param('id', PARAM_INT); // This are required.
$roleid = optional_param('roleid', 0, PARAM_INT);
$groupid = optional_param('groupid', 0, PARAM_INT);
$page = optional_param('page', 0, PARAM_INT);
$ps = 24; // pagesize


if($courseid == SITEID){
    redirect(new moodle_url('/'));
}

$baseurl = new moodle_url('/blocks/participants/map.php', array(
    'roleid' => $roleid,
    'id' => $courseid,
    'groupid' => $groupid
));

$course = $DB->get_record('course', array('id' => $courseid));
$PAGE->set_url($baseurl);

require_login($course);

$context = context_course::instance($courseid);

//$PAGE->set_pagelayout('preonly');

// set a nice white background to the page
$PAGE->set_pagelayout('incourse');

// remove the side post region
$PAGE->add_body_class('empty-region-side-post');


require_capability('moodle/course:viewparticipants', $context);

$rolenames = role_fix_names(get_profile_roles($context), $context, ROLENAME_ALIAS, true);
$rolenames[0] = get_string('allparticipants');


// Make sure other roles may not be selected by any means.
if (empty($rolenames[$roleid])) {
    print_error('noparticipants');
}

$sql =
'SELECT 
DISTINCT u.id,
u.*, tuai.appelation_officielle, tuai.ville, tuai.coordonnee_lat, tuai.coordonnee_long, av.hashname AS picturehash,
(
    SELECT GROUP_CONCAT(CONCAT(ti.contextid,"||",t.name) SEPARATOR "&&") FROM {tag_instance} ti
    INNER JOIN {tag} t ON (t.id = ti.tagid)
    WHERE ti.itemid = u.id AND ti.itemtype = "user"
) AS interests,
(
    SELECT COUNT(bi.id)
    FROM {badge} b 
    INNER JOIN {badge_issued} bi ON bi.badgeid=b.id
    WHERE bi.userid=u.id AND b.courseid=e.courseid AND (b.status=:active OR b.status=:activelocked)
) AS badges
FROM {enrol} e 
INNER JOIN {user_enrolments} ue ON ue.enrolid=e.id
INNER JOIN {user} u ON u.id=ue.userid
INNER JOIN {user_info_data} uid ON (uid.userid = u.id AND uid.fieldid = (SELECT id FROM {user_info_field} WHERE shortname = "rne" ))
INNER JOIN {t_uai} tuai ON (uid.data = tuai.code_rne)
LEFT JOIN '.$CFG->centralized_dbname.'.cr_avatars av ON (av.id = u.picture)
WHERE e.courseid= :courseid';

$params = array();
if ($roleid > 0)
{
	list($relatedctxsql, $relatedctxparams) = $DB->get_in_or_equal($context->get_parent_context_ids(true), SQL_PARAMS_NAMED, 'relatedctx');
	
	$sql .= " AND u.id IN (SELECT userid FROM {role_assignments} WHERE roleid = :roleid AND contextid $relatedctxsql)";
	$params = array_merge($params, array('roleid' => $roleid), $relatedctxparams);
}

if ($groupid > 0)
{
	$sql .= " AND u.id IN (SELECT userid FROM {groups_members} WHERE groupid = :groupid)";
	$params = array_merge($params, array('groupid' => $groupid));
}

$params['courseid'] = $courseid;
$params['active'] = BADGE_STATUS_ACTIVE;
$params['activelocked'] = BADGE_STATUS_ACTIVE_LOCKED;

$participantslist = $DB->get_records_sql($sql, $params);



$PAGE->set_title("$course->shortname: ".get_string('participants'));
$PAGE->set_heading($course->fullname);
$PAGE->set_pagetype('course-view-' . $course->format);
$PAGE->add_body_class('path-user');                     // So we can style it independently.
$PAGE->set_other_editing_capability('moodle/course:manageactivities');

$interactiveMap = new InteractiveMap('participantmap');
$interactiveMap->load_css($PAGE);
$interactiveMap->load_js($PAGE);
$interactiveMap->set_view(46.5, 2, 5);
$interactiveMap->set_mapheight('500px');

$group = array();
foreach($participantslist AS $participant)
{
	$profilelink = new moodle_url('/user/view.php', array('id'=>$participant->id, 'course'=>$courseid));
	$messagelink = new moodle_url('/message/index.php', array('id'=>$participant->id));
	
	$taglist = '<div class="tag_list hideoverlimit"><ul class="inline-list">';
	
	if (strlen($participant->interests) > 5)
	{
	    $i=0;
	    $interests = explode('&&',$participant->interests);
	    foreach ($interests AS $interesta)
	    {
	        $interest = explode('||', $interesta);
	        if (count($interest) != 2) {continue;}
	        
	        $i++;
	        if ($i > 5)
	        {
	            $taglist .= '<li><a href="'.$profilelink.'" target="_blank" class="label label-info">...</a></li>';
	            break;
	        }
	        $tagurl = new moodle_url("/tag/index.php",array('tc'=>1,'tag'=>$interest[1],'from'=>$interest[0]));
	        $taglist .= '<li><a href="'.$tagurl.'" target="_blank" class="label label-info">'.$interest[1].'</a></li>';
	        
	    }
	}
	$taglist .= '</ul></div>';
	
	$picture_url = secure_url('/avatar/img.png',$participant->picturehash,$CFG->secure_link_timestamp_default);
	
	$popup = '<table border="0" cellspacing="0" cellpadding="0" style ="font-family: \'open sans\';"><tr><td style="width: 50px; padding: 0 10px 0 0;vertical-align: initial"><a href="'.$profilelink.'" target="_blank"><img height="50" src="'.$picture_url.'"/></a></td>'
	.'<td><span class="leaflet-name">'
	.$participant->firstname.' '.$participant->lastname.'</span><br/>'
	.'<span class="leaflet-institute">'.ucwords($participant->appelation_officielle).' - '.ucwords($participant->ville).'</span><br/>'
	.'<a href="'.$profilelink.'" target="_blank" class="leaflet-viewprofil"><i class="fa fa-user" aria-hidden="true"></i> Voir le profil</a> |'
    .'<a href="'.$messagelink.'" target="_blank" class="leaflet-contact"><i class="fa fa-envelope-o" aria-hidden="true"></i> Contacter</a>|'
    .'<a href="" style="margin-left: 5px"><i class="fa fa-shield-alt"></i> '.get_string('badgepopuplabel', 'local_interactive_map', $participant->badges).'</a></td></tr>'
	.'<tr><td colspan="2"><hr></td></tr>'
	.'<tr><td colspan="2">'.$taglist.'</td></tr>'
	.'</table>';
	
	$popup = str_replace('"', '\\"', $popup);
	
	//$interactiveMap->add_marker($participant->coordonnee_lat,$participant->coordonnee_long,$participant->firstname.' '.$participant->lastname.'<br/>'.$participant->email);
	$group[] = $interactiveMap->create_marker($participant->coordonnee_lat,$participant->coordonnee_long,$popup);
	
	
}

$interactiveMap->add_marker_group($group);

$interactiveMap->generate_js();
$PAGE->requires->js_call_amd("block_participants/changerole", "init");

echo $OUTPUT->header();

echo encart_block(get_string('participantsmap','block_participants'), $courseid);
echo $interactiveMap->getMap();


$actionurl = new moodle_url('/blocks/participants/map.php',array('id'=>$courseid));

echo '<form action="'.$actionurl.'" method="post">';

if(count($rolenames) || count($groups)){
	echo '<table cellpadding="0" cellspacing="0" style="margin-top:25px;width:auto;margin-left:10px"><tr><td colspan="2" style="font-weight:bold;padding-bottom:10px">Paramètres d\'affichage des participants</td></tr>';
}

if(count($rolenames)){
    echo '<tr><td><label for="roleid">'.get_string('roles').'</label></td>';
    echo '<td><select id="roleid" name="roleid" style="width:100%">';
    foreach($rolenames as $id => $name){
        $selected = ($id == $roleid ? ' selected' : '');
        echo '<option value="'.$id.'"'.$selected.'>'.$name.'</option>';
    }
    echo '</select></td></tr>';
}

$groups = array();
if($courseid != SITEID){
    $groups = $DB->get_records('groups', array('courseid' => $courseid));
}

if(count($groups)){
    $first = new stdClass();
    $first->id = 0;
    $first->name = get_string('allgroups');
    array_unshift($groups, $first);

    echo '<tr><td><label for="groupid">'.get_string('group').'</label></td>';
    echo '<td><select id="groupid" name="groupid" style="width:100%">';
    foreach($groups as $g){
        $selected = ($g->id == $groupid ? ' selected' : '');
        echo '<option value="'.$g->id.'"'.$selected.'>'.$g->name.'</option>';
    }
    echo '</select></td></tr>';
}

//echo '<tr><td></td><td><input type="submit" value="'.get_string('refreshmap', 'block_participants').'" style="width:100%;margin:0"/></td></tr>';

if(count($rolenames) || count($groups)){
	echo '</table>';
}

echo '</form>';
$htmlparticipantslist = '';


foreach(array_slice($participantslist, $page*$ps, $ps) as $participant){
    $picture_url = secure_url('/avatar/img.png',$participant->picturehash,$CFG->secure_link_timestamp_default);
    $profilelink = new moodle_url('/user/view.php', array('id'=>$participant->id, 'course'=>$courseid));

    $username = $participant->firstname.' '.$participant->lastname;
    $img = html_writer::img($picture_url, $username, ['title' => $username]);
    $htmlparticipantslist .= html_writer::start_div('userlistuser');
    $text = $img.'<br>';
    $text .= html_writer::tag('span', $username);
    $htmlparticipantslist .= html_writer::link($profilelink, $text, ['target' => '_blank']);
    $htmlparticipantslist .= html_writer::end_div();
}

if($htmlparticipantslist){
    echo html_writer::tag('h3', "Liste des participants",array("style" => "font-size: 15px;margin-left: 10px;font-family: 'open sans',sans-serif;font-weight: bold;"));
    echo html_writer::div($htmlparticipantslist, 'userslist');

    $nbpart = count($participantslist);
    if($nbpart > $ps){
        echo html_writer::start_div('link_navigation');

        $cpage = ceil($nbpart/$ps);
        list($prev, $next) = get_nav_links($page, $cpage, $roleid, $courseid);

        if($prev){
            echo html_writer::link($prev, "Précédent", ['class' => 'prev']);
        }

        if($next){
            echo html_writer::link($next, "Suivant", ['class' => 'next']);
        }

        echo html_writer::end_div();
    }

}
echo $OUTPUT->footer();


function get_nav_links($page, $cpage, $roleid, $courseid)
{
    $result = [null, null];

    if($page < $cpage-1){
        $result[1] = new moodle_url('/blocks/participants/map.php', [
            'page' => $page+1,
            'roleselect' => $roleid,
            'id' => $courseid
        ]);
    }

    if($page > 0){
        $result[0] = new moodle_url('/blocks/participants/map.php', [
            'page' => $page-1,
            'roleselect' => $roleid,
            'id' => $courseid
        ]);
    }

    return $result;
}

function encart_block($name, $courseid)
{
    global $OUTPUT;

    // css classes are reused from the meth add_encart_activity from $OUTPUT

    $courseurl = new moodle_url('/course/view.php', ['id' => $courseid]);
    $title = "Retour au parcours de formation";
    $html = html_writer::link($courseurl, $title, ['class' => 'activity-encart-backButton']); //html_writer::start_div('encar-block-course-badges');
    $area =  html_writer::tag('h2', "Liste des participants");
    $html .= html_writer::div($area);


    return html_writer::div($html, 'activity-encart topics');
}