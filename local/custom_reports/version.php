<?php

$plugin->version   = 2019121301;
$plugin->requires  = 2018051705;
$plugin->component = 'local_custom_reports';
$plugin->release   = '0.1.0';
$plugin->maturity  = MATURITY_STABLE;
$plugin->cron      = 0;
$plugin->dependencies = array(
    'block_configurable_reports' => 2011040115, // The block_configurable_reports plugin version 2011040115 or higher must be present.
);
