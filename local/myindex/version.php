<?php

$plugin->version  = 2020033100;     // The (date) version of this module
                                    // This version number is displayed into /admin/forms.php
                                    // TODO: if ever this plugin get branched, the old branch number
                                    // will not be updated to the current date but just incremented. We will
                                    // need then a $plugin->release human friendly date. For the moment, we use
                                    // display this version number with userdate (dev friendly)
$plugin->requires = 2016052306;     // Requires this Moodle version - at least 2.0
$plugin->cron     = 0;
$plugin->release = '1.0';
$plugin->maturity = MATURITY_STABLE;
$plugin->component = 'local_myindex';
