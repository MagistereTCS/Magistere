<?php
require_once('../../config.php');
require($CFG->dirroot.'/local/mr/bootstrap.php');

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout("admin");
$PAGE->requires->jquery();
$PAGE->requires->jquery_plugin('ui');
$PAGE->requires->jquery_plugin('jtable');
$PAGE->requires->jquery_plugin('jtable-fr');
$PAGE->requires->jquery_plugin('jtable-css');

require_login();             								  //make sure user is logged in

mr_controller::render('local/centralizedresources', 'crframework', 'local_centralizedresources');