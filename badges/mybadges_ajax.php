<?php

/**
 * Sends request to check badges for user logged.
 *
 * @package    core
 * @subpackage badges
 * @copyright  2020 TCS
 */

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../config.php');
require_once($CFG->libdir . '/badgeslib.php');
require_once($CFG->dirroot . '/local/magisterelib/external_academic_badges.php');

require_login();
$PAGE->set_url('/badges/mybadges_ajax.php');
$PAGE->set_context(context_system::instance());

$so = required_param('so', PARAM_TEXT);
$si = required_param('si', PARAM_INT);
$ps = required_param('ps', PARAM_INT);

$badges = external_academic_badges::badges_sort_by_specific_column($USER->id, $so);

$all_badges = [];

foreach($badges as $badge){
    $data = new stdClass();
    $data->id = $badge->id;
    $badge_url = new moodle_url('/badges/badge.php', ['hash' => $badge->uniquehash]);
    if(isset($badge->aca_name)){
        $badge_url = new moodle_url($CFG->magistere_domaine.'/'.$badge->aca_name.'/badges/badge.php', [
            'hash' => $badge->uniquehash
        ]);
    }
    $badge_url = $badge_url->out(false);

    $assertion = new external_academic_badges_assertion($badge->uniquehash, $badge->aca_name);
    $badgeclass = $assertion->get_badge_class();

    $img = html_writer::empty_tag('img', [
        'src' => $badgeclass['image'],
        'alt' => $badge->name,
        'id' => 'badge-img'
    ]);
    $badge_img = html_writer::div($img, 'badge-img');

    $link = html_writer::link($badge_url, $badge->name);
    $badge_link = html_writer::div($link, 'badge-link');

    $data->name = $badge_img . $badge_link;
    $data->description = $badge->description;

    if(strlen($badge->description) > 50) {
        $data->description = html_writer::tag('p', $badge->description, ['class' => 'show-read-more']);
    }

    $data->dateobtained = $badge->dateobtained;

    if($badge->visible){
        $extraclasses = 'visible';
        $badge_state = get_string('public_state', 'badges');
    } else {
        $extraclasses = 'hide';
        $badge_state = get_string('private_state', 'badges');
    }
    $data->visibility = html_writer::tag('i', '', ['class' => 'fas fa-circle '.$extraclasses])
        . html_writer::span($badge_state);

    // Visibility action
    if ($badge->visible) {
        $url = new moodle_url('mybadges.php', [
            'hide' => $badge->issuedid,
            'sesskey' => sesskey(),
            'aca' => $badge->aca_name
        ]);
        $link = html_writer::link($url,
            icon_fontawesome('eye-slash', get_string('private_state_title', 'badges'))
        );
    } else {
        $url = new moodle_url('mybadges.php', [
            'show' => $badge->issuedid,
            'sesskey' => sesskey(),
            'aca' => $badge->aca_name
        ]);
        $link = html_writer::link($url,
            icon_fontawesome('eye', get_string('public_state_title', 'badges'))
        );

    }
    $change_visibility_link = html_writer::div($link, 'change-visibility badge-link');

    // Print action
    $url = new moodle_url('/badges/prettyview.php', ['hash' => $badge->uniquehash, 'aca' => $badge->aca_name]);
    $js = 'window.open("'.$url->out(false)
        .'","popup","menubar=no, status=no, scrollbars=no, menubar=no, width=500, height=400");';
    $print = html_writer::link('#',
        icon_fontawesome('print', get_string('print_title', 'badges')),
        ['class' => 'badge-action-link', 'onClick' => $js]);
    $print_link = html_writer::div($print, 'print badge-link');

    // Export to bagpack action
    $notexpiredbadge = (empty($badge->dateexpire) || $badge->dateexpire > time());
    $backpackexists = badges_user_has_backpack($USER->id);
    if (!empty($CFG->badges_allowexternalbackpack) && $notexpiredbadge && $backpackexists) {
        $assertion = new moodle_url($CFG->magistere_domaine.'/'.$badge->aca_name.'/badges/assertion.php',
            ['b' => $badge->uniquehash]);
        $action = new component_action('click',
            'addtobackpack',
            ['assertion' => $assertion->out(false)]);
        $addbuttonid = $OUTPUT->add_action_handler($action, 'addbutton_'.$badge->id);
        $attributes = [
            'type'  => 'button',
            'id'    => $addbuttonid,
            'value' => get_string('addtobackpack', 'badges')];

        $push = html_writer::tag('button',
            icon_fontawesome('share-square', get_string('addtobackpack', 'badges')),
            $attributes);
        $push_link = html_writer::div($push, 'push badge-link');
    }

    // URL Copy action
    $link = new moodle_url($CFG->magistere_domaine.'/'.$badge->aca_name.'/badges/assertion.php',
        ['b' => $badge->uniquehash]);
    $js = "navigator.clipboard.writeText('".$link."').then(function() { alert('Lien copié avec succès')})";
    $copy = html_writer::link('#', icon_fontawesome('copy', get_string('copylink')),
        ['class' => 'badge-action-link', 'onClick' => $js]);
    $copy_link = html_writer::div($copy, 'copy badge-link');


    // Download action
    $url = new moodle_url('mybadges.php', array('download' => $badge->id, 'hash' => $badge->uniquehash,
        'sesskey' => sesskey(), 'aca' => $badge->aca_name));
    $download = html_writer::link($url,
        icon_fontawesome('download', get_string('download_title', 'badges')));
    $download_link = html_writer::div($download, 'download badge-link');

    // Delete action
    $form = html_writer::start_tag('form', [
        'method' => 'POST',
        'action' => new moodle_url('/badges/badge.php')
    ]);
    $form .= html_writer::input_hidden_params(new moodle_url('/badges/badge.php', [
        'hash' => $badge->uniquehash,
        'delete' => true,
        'aca' => $badge->aca_name,
        'badgeid' => $badge->id
    ]));
    $form .= html_writer::tag('button',
        icon_fontawesome('trash-alt', get_string('delete_title', 'badges')), [
            'type' => 'submit',
            'class' => 'delete-badge-btn'
        ]);
    $form .= html_writer::end_tag('form');
    $delete_link = html_writer::div($form, 'delete badge-link');

    $data->actions = $change_visibility_link . $print_link . $push_link . $copy_link . $download_link . $delete_link;

    $all_badges[] = $data;
}

//Return result to jTable
$jTableResult = [];
$jTableResult['Result'] = "OK";
$jTableResult['TotalRecordCount'] = null;
$jTableResult['Records'] = $all_badges;

echo json_encode($jTableResult);

function icon_fontawesome($icon_name, $title = ''){
    $icon_link = html_writer::start_div('fa-icon-link');
    $icon_link .= html_writer::start_tag('span', ['class' => 'fa-stack']);
    $icon_link .= html_writer::tag('i', '', ['class' => 'fas fa-circle fa-stack-2x']);
    $icon_link .= html_writer::tag('i', '', ['class' => 'fas fa-'.$icon_name.' fa-stack-1x']);
    $icon_link .= html_writer::end_tag('span');
    if($title != ''){
        $icon_link .= html_writer::span($title,'span-title');
    }
    $icon_link .= html_writer::end_div();
    return $icon_link;
}
die();