<?php

define('CLI_SCRIPT',true);

require_once('../../config.php');

global $CFG;
require_once($CFG->dirroot.'/local/metaadmin/lib.php');
update_tmp_table2();
