<?php

ignore_user_abort(true);

global $PAGE, $CFG, $OUTPUT;

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->dirroot.'/lib/coursecatlib.php');
require_once($CFG->dirroot.'/local/supervision_tool/supervision_form.php');
require_once($CFG->dirroot.'/local/supervision_tool/FilterParams.php');
require_once($CFG->dirroot.'/local/supervision_tool/FilterConfig.php');
require_once($CFG->dirroot.'/local/supervision_tool/FilterResults.php');
require_once($CFG->dirroot.'/local/supervision_tool/FilterDisplay.php');
require_once($CFG->dirroot.'/local/supervision_tool/FilterActions.php');

require_login();
require_capability('local/supervision_tool:view', context_system::instance());

$url = new moodle_url('/local/supervision_tool/index.php');

$PAGE->set_url($url);
$PAGE->set_context(context_system::instance());

$PAGE->requires->jquery();
$PAGE->requires->jquery_plugin('ui');
$PAGE->requires->jquery_plugin('ui-css');
$PAGE->requires->jquery_plugin('jtable-css');
$PAGE->requires->css("/local/supervision_tool/js/jquery.loadingModal.min.css");
$PAGE->requires->css(new moodle_url('/local/supervision_tool/styles.css'));

// affichage du header
echo $OUTPUT->header();

$form = new supervision_form();

$filterConfig = new FilterConfig();

if(($data = $form->get_data())){
    // save config user
    if(isset($data->filtersave)){
        $filterConfig->loadFromForm();
        $filterConfig->save($USER->id);
    }else{
        $filterConfig->load($USER->id);
    }

    if(isset($data->actionbutton)){
        $filterAction = new Filteractions();
        $filterAction->loadFromForm();
        $filterAction->perform();
        echo $filterAction->getNotification();
    }
}else{
    $data = $filterConfig->getValueForForm($USER->id);

    $form->set_data($data);
}

$hub = CourseHub::instance();
$ismaster = $hub->isMaster();

$filterdisplay = new FilterDisplay($filterConfig);
$PAGE->requires->js_call_amd('local_supervision_tool/supervision_tool', 'init', array(
    'jtablecolumns',
    array(
        'moveTo' => FilterActions::MOVE_TO,
        'archive' => FilterActions::MOVE_TO_ARCHIVE,
        'trash' => FilterActions::MOVE_TO_TRASH,
        'migrateToTopics' => FilterActions::TOPICS_MIGRATION,
        //'migrateToModular' => FilterActions::MODULAR_MIGRATION,
        //'validate' => FilterActions::VALIDATION,
    ),
    $hub->getIdentifiant()
));

$form->display();

echo html_input_data('jtablecolumns', json_encode($filterdisplay->getJtableColumn()));

print_message_modal();

// affichage du footer
echo $OUTPUT->footer();

function html_input_data($name, $data){
    return html_writer::tag('input', '', ['type' => 'hidden', 'name' => $name, 'value' => $data]);
}

function print_message_modal(){
    echo html_writer::start_div('', array('id' => "messagesmod"));
    echo html_writer::tag('textarea', '', array('id' => 'messagemodtext'));
    echo html_writer::end_div();
}
