<?php
// This client for local_ws_course_magistere is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//

/**
 * XMLRPC client for Moodle 2 - local_ws_course_magistere
 *
 * This script does not depend of any Moodle code,
 * and it can be called from a browser.
 *
 * @authorr Jerome Mouneyrac
 */

/// MOODLE ADMINISTRATION SETUP STEPS
// 1- Install the plugin
// 2- Enable web service advance feature (Admin > Advanced features)
// 3- Enable XMLRPC protocol (Admin > Plugins > Web services > Manage protocols)
// 4- Create a token for a specific user and for the service 'My service' (Admin > Plugins > Web services > Manage tokens)
// 5- Run this script directly from your browser: you should see 'Hello, FIRSTNAME'

/// SETUP - NEED TO BE CHANGED
$token = '4ftv5xqcfmackb8xuput7pnjh3rxmkt6';

require_once('./curl.php');
require_once('../../../config.php');

$curl = new curl;

$wsurl = 'webservice/rest/server.php';
$domainname = $CFG->magistere_domaine . '/' . $CFG->academie_name . '/';

$serverurl = $domainname . $wsurl;

echo "URL : " . $serverurl . "<br/>";

$data = json_encode(array(
		'username' => '83fb27642bff551b955aeae43d4a47e881faba68d26eaef193c0917de4f6e6d2',
		'data' =>array(
			'lastname' => 'test 13456 lastname',
			'email' => "flmksdlmfhsdlmdfs@fdsmqklfjdsmqlj.cdlmskglmsdkglmkdfhslmhgd"
		)
));

$resp = $curl->post($serverurl, array(
		'fname' => 'update',
		'fparams' => $data,
		'wstoken' => $CFG->ws_user_profile_token,
		'moodlewsrestformat' => 'json',
		'wsfunction' => 'local_ws_user_profile_main'
));

print_r($resp);
echo '<br/>';

$wsurl = 'webservice/rest/server.php';
$domainname = $CFG->magistere_domaine . '/' . $CFG->academie_name . '/';

$serverurl = $domainname . $wsurl;

echo "URL : " . $serverurl . "<br/>";

$data = json_encode(array(
		'username' => '83fb27642bff551b955aeae43d4a47e881faba68d26eaef193c0917de4f6e6d2'
));

$resp = $curl->post($serverurl, array(
		'fparams' => $data,
		'fname' => 'get',
		'wstoken' => $CFG->ws_user_profile_token,
		'moodlewsrestformat' => 'json',
		'wsfunction' => 'local_ws_user_profile_main'
));

//print_r($resp);

$resp = stripslashes($resp);
$resp = substr($resp, 1, -1);

$ret = json_decode($resp, true);

print_r($ret);