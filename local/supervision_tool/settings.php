<?php

$url = new moodle_url('/local/supervision_tool/');

$ADMIN->add('root', new admin_externalpage('supervision_tool', get_string('pluginname', 'local_supervision_tool'), $url, 'local/supervision_tool:view'), 'metaadmin');
