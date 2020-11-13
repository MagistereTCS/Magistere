<?php

$ADMIN->add('root', new admin_category('metaadmin', 'Meta-Administration'), 'users');

$ADMIN->add('metaadmin', new admin_category('metaadmin_stats', 'Statistiques'));

$ADMIN->add('metaadmin_stats', new admin_externalpage('metaadmin_statsparticipants', 'Académiques',
            $CFG->wwwroot.'/local/metaadmin/view_statsparticipants.php', 'local/metaadmin:statsparticipants_viewownacademy'));

$ADMIN->add('metaadmin_stats', new admin_externalpage('metaadmin_statsparticipants_nat', 'Nationales',
            $CFG->wwwroot.'/local/metaadmin/view_statsparticipants_per_academy.php', 'local/metaadmin:statsparticipants_viewownacademy' ));

$ADMIN->add('metaadmin_stats', new admin_externalpage('metaadmin_statsparticipants_1D', 'Départementales 1er degré',
            $CFG->wwwroot.'/local/metaadmin/view_statsparticipants_first_degree_per_academy.php', 'local/metaadmin:statsparticipants_viewownacademy'));

$ADMIN->add('metaadmin_stats', new admin_externalpage('metaadmin_statsparticipants_addView', 'Ajouter une nouvelle vue',
            $CFG->wwwroot.'/local/metaadmin/editcustomview.php', 'local/metaadmin:statsparticipants_manageviews'));
