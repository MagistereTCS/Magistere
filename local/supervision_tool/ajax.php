<?php

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->dirroot.'/local/supervision_tool/FilterParams.php');
require_once($CFG->dirroot.'/local/supervision_tool/FilterConfig.php');
require_once($CFG->dirroot.'/local/supervision_tool/FilterResults.php');
require_once($CFG->dirroot.'/local/supervision_tool/FilterDisplay.php');
require_once($CFG->dirroot.'/local/supervision_tool/ExportCSV.php');

require_login();

$action = required_param('action', PARAM_TEXT);

if($action == 'list'){
    $filterParams = new FilterParams();
    $filterParams->loadFromUrl();

    $filterConfig = new FilterConfig();
    $filterConfig->load($USER->id);

    $result = new FilterResults($filterParams, $filterConfig);
    $data = $result->get_courses();

    $filterDisplay = new FilterDisplay($filterConfig);
    $filterDisplay->formatData($data);

    $jTableResult = array();
    $jTableResult['Result'] = "OK";
    $jTableResult['TotalRecordCount'] = $result->getResultCount();
    $jTableResult['Records'] = array_values($data);

    echo json_encode($jTableResult);
}

if($action == 'edit'){
    global $DB;

    $id = required_param('id', PARAM_INT);
    $comment = required_param('comment', PARAM_TEXT);

    $comment = trim($comment);

    $record = $DB->get_record('local_supervision_tool_comm', array('courseid' => $id));
    if($record === false){
        $record = new stdClass();
        $record->courseid = $id;
        $record->comment = $comment;

        $DB->insert_record('local_supervision_tool_comm', $record);
    }else{
        $record->comment = $comment;
        $DB->update_record('local_supervision_tool_comm', $record);
    }

    if(empty($comment)){
        $comment = '-';
    }

    echo json_encode(array(
        'result' => 'ok',
        'response' => nl2br($comment, false)
    ));
}

if($action == 'gencsv'){
    $filterConfig = new FilterConfig();
    $filterConfig->load($USER->id);

    $filterParams = new FilterParams();
    $filterParams->loadFromUrl();

    $exporter = new ExportCSV();
    $writeFile = true;
    $fileid = $exporter->export($filterConfig, $filterParams, $writeFile);

    $url = new moodle_url('/local/supervision_tool/ajax.php', ['action' => 'getcsv', 'id' => $fileid]);

    echo json_encode([
        'result' => 'ok',
        'url' => $url->out(false)
    ]);
}

if($action == 'getcsv'){
    $fileid = optional_param('id', null, PARAM_TEXT);

    if(!$fileid){
        return;
    }

    $filename = "export_".$CFG->academie_shortname.'_' . date('Y-m-d').'.csv';
    $filepath = $CFG->tempdir.'/csv_'.$fileid.'.csv';
    if (file_exists($filepath)){
        header('Content-Disposition: attachment; filename=' . urlencode($filename));
        header('Content-Type: application/force-download');
        header('Content-Type: application/octet-stream');
        header('Content-Type: application/download');
        header('Content-Description: File Transfer');
        header('Content-Length: ' . filesize($filepath));

        readfile($filepath);
        unlink($filepath);
    }
}