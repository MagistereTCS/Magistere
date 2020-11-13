<?php

$url = new moodle_url('/local/taskmonitor/');

$ADMIN->add('root', new admin_externalpage('taskmonitor', get_string('pluginname', 'local_taskmonitor'), $url, 'local/taskmonitor:access'), 'supervision_tool');
