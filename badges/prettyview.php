<?php
require_once(__DIR__ . '/../config.php');
require_once(__DIR__ . '/../badges/renderer.php');
require_once($CFG->dirroot . '/local/magisterelib/external_academic_badges.php');

$badgeid = required_param('hash', PARAM_ALPHANUM); // Badge ID
$aca_name = optional_param('aca', '', PARAM_TEXT); // Academic name

if($aca_name){
    $ibadge = new external_academic_issued_badge($badgeid, $aca_name);
    if (!$badge = new external_academic_badge($ibadge->badgeid, $aca_name)) {
        print_error('Aucun badge n\'a été trouvé.');
    }
} else {
    $ibadge = new issued_badge($badgeid);
    if (!$badge = new badge($ibadge->badgeid)) {
        print_error('Aucun badge n\'a été trouvé.');
    }
}

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/badges/prettyview.php', ['hash' => $badgeid, 'aca' => $aca_name]);
$PAGE->set_pagelayout('embedded');
$PAGE->requires->jquery();

echo $OUTPUT->header();

// Print dialog link.
$printtext = get_string('print', 'glossary');
$printlinkatt = ['onclick' => 'window.print();return false;', 'class' => 'printicon'];
$printiconlink = html_writer::link('#', $printtext, $printlinkatt);
echo html_writer::tag('div', $printiconlink, ['class' => 'displayprinticon']);
$html = html_writer::tag('h1', format_string($badge->name), ['id' => 'badge_printable_title']);

$issued = $ibadge->issued;
$userinfo = $ibadge->recipient;
$badgeclass = $ibadge->badgeclass;
$now = time();
$expiration = isset($issued['expires']) ? $issued['expires'] : $now + 86400;

$output = '';
$output .= html_writer::start_tag('div', ['id' => 'badge']);
$output .= html_writer::start_tag('div', ['id' => 'badge-image']);
$output .= html_writer::empty_tag('img', ['src' => $badgeclass['image'], 'alt' => $badge->name]);
if ($expiration < $now) {
    $output .= $OUTPUT->pix_icon('i/expired',
        get_string('expireddate', 'badges', userdate($issued['expires'])),
        'moodle',
        ['class' => 'expireimage']);
}
$output .= html_writer::end_tag('div');

$output .= html_writer::start_tag('div', ['id' => 'badge-details']);

// Recipient information.
$output .= $OUTPUT->heading(get_string('recipientdetails', 'badges'), 3);
$dl = [];
if ($userinfo->deleted) {
    $strdata = new stdClass();
    $strdata->user = fullname($userinfo);
    $strdata->site = format_string($SITE->fullname, true, ['context' => context_system::instance()]);

    $dl[get_string('name')] = get_string('error:userdeleted', 'badges', $strdata);
} else {
    $dl[get_string('name')] = fullname($userinfo);
}
$output .= definition_list($dl);

$output .= $OUTPUT->heading(get_string('issuerdetails', 'badges'), 3);
$dl = [];
$dl[get_string('issuername', 'badges')] = $badge->issuername;
if (isset($badge->issuercontact) && !empty($badge->issuercontact)) {
    $dl[get_string('contact', 'badges')] = obfuscate_mailto($badge->issuercontact);
}
$output .= definition_list($dl);

$output .= $OUTPUT->heading(get_string('badgedetails', 'badges'), 3);
$dl = [];
$dl[get_string('name')] = $badge->name;
$dl[get_string('description', 'badges')] = $badge->description;

if ($badge->type == BADGE_TYPE_COURSE && isset($badge->courseid)) {
    $coursename = $DB->get_field('course', 'fullname', ['id' => $badge->courseid]);
    $dl[get_string('course')] = $coursename;
}
$output .= definition_list($dl);

$output .= $OUTPUT->heading(get_string('issuancedetails', 'badges'), 3);
$dl = [];
$dl[get_string('dateawarded', 'badges')] = userdate($issued['issuedOn']);
if (isset($issued['expires'])) {
    if ($issued['expires'] < $now) {
        $dl[get_string('expirydate', 'badges')] = userdate($issued['expires'])
            . get_string('warnexpired', 'badges');

    } else {
        $dl[get_string('expirydate', 'badges')] = userdate($issued['expires']);
    }
}

// Print evidence.
$agg = $badge->get_aggregation_methods();
$evidence = $badge->get_criteria_completions($userinfo->id);
$eids = array_map(function($o) {
    return $o->critid;
}, $evidence);
unset($badge->criteria[BADGE_CRITERIA_TYPE_OVERALL]);

$items = [];
foreach ($badge->criteria as $type => $c) {
    if (in_array($c->id, $eids)) {
        if (count($c->params) == 1) {
            $items[] = get_string('criteria_descr_single_' . $type , 'badges') . $c->get_details();
        } else {
            $items[] = get_string('criteria_descr_' . $type , 'badges',
                    core_text::strtoupper($agg[$badge->get_aggregation_method($type)])) . $c->get_details();
        }
    }
}

$dl[get_string('evidence', 'badges')] = get_string('completioninfo', 'badges')
    . html_writer::alist($items, [], 'ul');
$output .= definition_list($dl);
$output .= html_writer::end_tag('div');

$html .= $output;

echo '<div id="badge_printable_content">';
echo format_text($html, FORMAT_HTML);
echo '</div>';

echo $OUTPUT->footer();


function definition_list(array $items, array $attributes = []) {
    $output = html_writer::start_tag('dl', $attributes);
    foreach ($items as $label => $value) {
        $output .= html_writer::tag('dt', $label);
        $output .= html_writer::tag('dd', $value);
    }
    $output .= html_writer::end_tag('dl');
    return $output;
}