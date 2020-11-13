<?php

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');

require_once('MyIndexApi.php');
$PAGE->set_context(context_system::instance());
require_login();


$s = optional_param('s', '', PARAM_TEXT);

echo '<pre>';


$api = new MyIndexApi(MyIndexApi::MOD_MAIN, $s, MyIndexApi::FILTER_ALLCOURSE);

print_r($api->get_courses_groups_json());
echo "\n\n";
echo '####'.count($api->get_data()->courses).'####';
$api = new MyIndexApi(MyIndexApi::MOD_MAIN, $s, MyIndexApi::FILTER_PARCOURSDEMO);
echo '####'.count($api->get_data()->courses).'####';
$api = new MyIndexApi(MyIndexApi::MOD_MAIN, $s, MyIndexApi::FILTER_ESPACECOLLABO);
echo '####'.count($api->get_data()->courses).'####';
//print_r($api->get_data()->courses);
$api = new MyIndexApi(MyIndexApi::MOD_MAIN, $s, MyIndexApi::FILTER_SEFORMER);
echo '####'.count($api->get_data()->courses).'####';
$api = new MyIndexApi(MyIndexApi::MOD_MAIN, $s, MyIndexApi::FILTER_FORMER);
echo '####'.count($api->get_data()->courses).'####';
$api = new MyIndexApi(MyIndexApi::MOD_MAIN, $s, MyIndexApi::FILTER_CONCEVOIR);
echo '####'.count($api->get_data()->courses).'####';
$api = new MyIndexApi(MyIndexApi::MOD_MAIN, $s, MyIndexApi::FILTER_FAVORIS);
echo '####'.count($api->get_data()->courses).'####';
$api = new MyIndexApi(MyIndexApi::MOD_MAIN, $s, MyIndexApi::FILTER_FAVORIS,true);
echo '####'.count($api->get_data()->courses).'####';
$api = new MyIndexApi(MyIndexApi::MOD_MAIN, $s, MyIndexApi::FILTER_ARCHIVE);
echo '####'.count($api->get_data()->courses).'####';

print_r($api->get_data());


//print_r($api->query());
//print_r($api->get_json());



//print_r($api->get_data());

//echo $api->get_data();

//print_r($api->get_courses_groups());

