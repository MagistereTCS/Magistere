<?php

$tasks = array(
                array(
                    'classname' => 'local_metaadmin\task\metaadmin_update_statsparticipants_table_task',
                    'blocking' => 0,
                    'minute' => '00',
                    'hour' => '03',
                    'day' => '1',
                    'dayofweek' => '*',
                    'month' => '1',
                    'disabled' => 1
                ),
                array(
                    'classname' => 'local_metaadmin\task\metaadmin_send_statsparticipants_report_task',
                    'blocking' => 0,
                    'minute' => '00',
                    'hour' => '00',
                    'day' => '*',
                    'dayofweek' => '*',
                    'month' => '*',
                    'disabled' => 1
                )
);