<?php

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/local/myindex/MyIndex.php');

$userid = $USER->id;  // Owner of the page
$context = context_user::instance($USER->id);

// Prevent caching of this page to stop confusion when changing page after making AJAX changes
$PAGE->set_cacheable(false);
$PAGE->requires->jquery();
$PAGE->requires->jquery_plugin('ui');
$PAGE->requires->jquery_plugin('ui-css');
$PAGE->requires->js_call_amd("local_myindex/myindex", "init", array($CFG->wwwroot.'/local/myindex/api.php', $CFG->magistere_domaine.'/local/magistere_offers/index.php'));
$PAGE->requires->js_call_amd("local_myindex/myindex", "validateform", array($CFG->wwwroot.'/local/myindex/api.php', $CFG->magistere_domaine.'/local/magistere_offers/index.php'));

$PAGE->set_context($context);
$PAGE->set_url('/local/myindex/index.php');
$PAGE->set_pagetype('site-index');
$PAGE->set_pagelayout('myindex');

$PAGE->set_title($SITE->fullname);
$PAGE->set_heading($SITE->fullname);

$buttons = $OUTPUT->edit_button($PAGE->url);
$PAGE->set_button($buttons);

echo $OUTPUT->header();
$myindex = new MyIndex();
echo $myindex->showMyIndex();

echo $OUTPUT->footer();
