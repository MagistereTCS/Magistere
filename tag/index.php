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
 * @package    core_tag
 * @category   tag
 * @copyright  2007 Luiz Cruz <luiz.laydner@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../config.php');
require_once($CFG->dirroot . '/lib/weblib.php');
require_once($CFG->dirroot . '/blog/lib.php');
require_once($CFG->dirroot.'/local/interactive_map/InteractiveMap.php');

require_login();

if (empty($CFG->usetags)) {
    print_error('tagsaredisabled', 'tag');
}

$tagid       = optional_param('id', 0, PARAM_INT); // tag id
$tagname     = optional_param('tag', '', PARAM_TAG); // tag
$tagareaid   = optional_param('ta', 0, PARAM_INT); // Tag area id.
$exclusivemode = optional_param('excl', 0, PARAM_BOOL); // Exclusive mode (show entities in one tag area only).
$page        = optional_param('page', 0, PARAM_INT); // Page to display.
//TCS BEGIN 3863 - THE
$fromctx     = optional_param('from', context_system::instance()->id, PARAM_INT);
//TCS END 3863 - THE
$ctx         = optional_param('ctx', null, PARAM_INT);
$rec         = optional_param('rec', 1, PARAM_INT);

$edit        = optional_param('edit', -1, PARAM_BOOL);

$systemcontext   = context_system::instance();

if ($tagname) {
    $tagcollid = optional_param('tc', 0, PARAM_INT);
    if (!$tagcollid) {
        // Tag name specified but tag collection was not. Try to guess it.
        $tags = core_tag_tag::guess_by_name($tagname, '*');
        if (count($tags) > 1) {
            // This tag was found in more than one collection, redirect to search.
            redirect(new moodle_url('/tag/search.php', array('query' => $tagname)));
        } else if (count($tags) == 1) {
            $tag = reset($tags);
        }
    } else {
        if (!$tag = core_tag_tag::get_by_name($tagcollid, $tagname, '*')) {
            redirect(new moodle_url('/tag/search.php', array('tc' => $tagcollid, 'query' => $tagname)));
        }
    }
} else if ($tagid) {
    $tag = core_tag_tag::get($tagid, '*');
}
unset($tagid);
if (empty($tag)) {
    redirect(new moodle_url('/tag/search.php'));
}

//////=> TCS - 20/02/2018 - CLEO - evol #2325 - 2.0.9

$cssUrl = new moodle_url('/tag/style.css');
$jsUrl = new moodle_url('/tag/tag.js');
$PAGE->requires->css($cssUrl);
$PAGE->requires->js($jsUrl, true);

//////<= TCS - 20/02/2018 - CLEO - evol #2325 - 2.0.9

if ($fromctx && ($context = context::instance_by_id($fromctx, IGNORE_MISSING)) && $context->contextlevel >= CONTEXT_COURSE) {
    list($context, $course, $cm) = get_context_info_array($context->id);
    require_login($course, false, $cm, false, true);
} else {
    $PAGE->set_context($systemcontext);
}

$tagcollid = $tag->tagcollid;

$PAGE->set_url($tag->get_view_url($exclusivemode, $fromctx, $ctx, $rec));
$PAGE->set_subpage($tag->id);
$tagnode = $PAGE->navigation->find('tags', null);
$tagnode->make_active();

$PAGE->set_blocks_editing_capability('moodle/tag:editblocks');

// set a nice white background to the page
$PAGE->set_pagelayout('incourse');

// remove the side post region
$PAGE->add_body_class('empty-region-side-post');
$buttons = '';
if (has_capability('moodle/tag:manage', context_system::instance())) {
    $buttons .= $OUTPUT->single_button(new moodle_url('/tag/manage.php'),
            get_string('managetags', 'tag'), 'GET');
}
if ($PAGE->user_allowed_editing()) {
    if ($edit != -1) {
        $USER->editing = $edit;
    }
    $buttons .= $OUTPUT->edit_button(clone($PAGE->url));
}

$PAGE->navbar->add($tagname);
$PAGE->set_title(get_string('tag', 'tag') .' - '. $tag->get_display_name());
$PAGE->set_heading($COURSE->fullname);
$PAGE->set_button($buttons);

// Find all areas in this collection and their items tagged with this tag.
$tagareas = core_tag_collection::get_areas($tagcollid);
if ($tagareaid) {
    $tagareas = array_intersect_key($tagareas, array($tagareaid => 1));
}
if (!$tagareaid && count($tagareas) == 1) {
    // Automatically set "exclusive" mode for tag collection with one tag area only.
    $exclusivemode = 1;
}
$entities = array();
foreach ($tagareas as $ta) {
    $entities[] = $tag->get_tag_index($ta, $exclusivemode, $fromctx, $ctx, $rec, $page);
}

$tagrenderer = $PAGE->get_renderer('core', 'tag');
$pagecontents = $tagrenderer->tag_index_page($tag, array_filter($entities), $tagareaid,
        $exclusivemode, $fromctx, $ctx, $rec, $page);

echo $OUTPUT->header();
echo $pagecontents;
echo $OUTPUT->footer();
