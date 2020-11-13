<?php

require_once($CFG->dirroot.'/local/custom_reports/custom_reports_form.php');

function getBaseExportFilepath($timestamp, $userid) {
    global $CFG;
    make_temp_directory('custom_reports');
    $basepath = $CFG->tempdir.'/custom_reports/'.$timestamp.'_custom_reports_'.$userid;
    return $basepath;
}

function export_csv($records, $filepath) {
    global $CFG;
    require_once($CFG->libdir . '/csvlib.class.php');

    $matrix = createDataMatrix($records);

    $fp = fopen($filepath, 'w+');
    fputs($fp, $bom =( chr(0xEF) . chr(0xBB) . chr(0xBF) ));
    $delimiter = ',';
    $enclosure = '"';
    foreach($matrix as $ri => $col){
        fputcsv($fp, $col, $delimiter, $enclosure);
    }
    fclose($fp);
}

function export_ods($records, $filepath) {
    $matrix = createDataMatrix($records);

    $writer = Box\Spout\Writer\WriterFactory::create(Box\Spout\Common\Type::ODS);
    $writer->openToFile($filepath);
    foreach($matrix as $ri => $row){
        $writer->addRow($row);
    }
    $writer->close();
}

function export_xls($records, $filepath) {
    $matrix = createDataMatrix($records);

    $writer = Box\Spout\Writer\WriterFactory::create(Box\Spout\Common\Type::XLSX);
    $writer->openToFile($filepath);
    foreach($matrix as $ri => $row){
        $writer->addRow($row);
    }
    $writer->close();
}

function createDataMatrix($records) {
    $header_written = false;
    $matrix = array();

    $i = 1;
    foreach($records AS $record){
        // write header first
        if (!$header_written) {
            foreach(array_keys(get_object_vars($record)) AS $headerName){
                $matrix[0][$headerName] = str_replace("\n",' ',htmlspecialchars_decode(strip_tags(nl2br($headerName))));
            }
            $header_written= true;
        }

        
        foreach($record AS $headerName=>$value){
            $matrix[$i][$headerName] = str_replace("\n",' ',htmlspecialchars_decode(strip_tags(nl2br($value))));
        }
        $i++;
    }
    return $matrix;
}


function get_stats_filepath($basepath, $exportType) {
    switch($exportType) {
        case custom_reports_form::EXPORT_CSV :
            return $basepath.'.csv';
        case custom_reports_form::EXPORT_XLS :
            return $basepath.'.xlsx';
        case custom_reports_form::EXPORT_ODS :
            return $basepath.'.ods';
        default :
            throw new Exception('The following export type is not a valid one : ' .$exportType);
    }
}

/**
 * Create the file in the right format with the results
 *
 * @param   array    $records array of row containning the results to write
 * @param   int      $exportType file format
 * @param   string   $filepath path of the file to create
 */
function export_stats_to_file($records, $exportType, $filepath) {
    switch($exportType) {
        case custom_reports_form::EXPORT_CSV :
            export_csv($records, $filepath);
            break;
        case custom_reports_form::EXPORT_XLS :
            export_xls($records, $filepath);
            break;
        case custom_reports_form::EXPORT_ODS :
            export_ods($records, $filepath);
            break;
        default :
            throw new Exception('The following export type is not a valid one : ' .$exportType);
    }
}

/**
 * Send an email with the attached file  
 *
 * @param   int     $userid The user ID of the asking user.
 * @param   string  $filepath path of the generated file
 */
function send_export_by_mail($userid, $timecreated, $filepath = null) {
    global $DB;
    $time = date('d/m/Y à H:i:s', $timecreated);
    $subject = html_to_text(get_string('notification_subject',
        'local_custom_reports', $time));
    if($filepath) {
        $body_text = html_to_text(get_string('notification_message',
            'local_custom_reports', $time));
    } else {
        $body_text = html_to_text(get_string('notification_message_no_records',
            'local_custom_reports', $time));
    }
    $body_html = report_message_to_html(get_string('notification_message',
        'local_custom_reports', $time));

    if($filepath) {
        $path_parts = pathinfo($filepath);
        $attachname = $path_parts['basename'];
    }

    $user = \core_user::get_user($userid);

    $message = new \core\message\message();
    $message->component = 'local_custom_reports';
    $message->name = 'send_notification';
    $message->userfrom = \core_user::get_noreply_user();
    $message->userto = $user;
    $message->subject = $subject;
    $message->fullmessage = $body_text;
    $message->fullmessageformat = FORMAT_HTML;
    $message->fullmessagehtml = $body_html;
    $message->notification = 1;

    if($filepath) {
        $usercontext = context_user::instance($user->id);
        $file = new stdClass;
        $file->contextid = $usercontext->id;
        $file->component = 'user';
        $file->filearea = 'private';
        $file->itemid = 0;
        $file->filepath = '/';
        $file->filename = $attachname;

        $fs = get_file_storage();
        $file = $fs->create_file_from_pathname($file, $filepath);

        $message->attachname = $attachname;
        $message->attachment = $file;
    }

    $newmessageid = message_send($message);

    if($filepath) {
        if (file_exists($filepath)) {
            unlink($filepath);
        }
    }

    // Fix pour faire apparaitre la notification comme non lue dans l'interface utilisateur.
    $notification = $DB->get_record('notifications', ['id' => $newmessageid]);
    $updatenotification = new stdClass();
    $updatenotification->id = $notification->id;
    $updatenotification->timeread = null;
    $DB->update_record('notifications', $updatenotification);
}


/**
 * Fonction qui determine la structure principale d'une notification en HTML.
 * @param $message
 * @return string
 */
function report_message_to_html($message){
    $posthtml = '<head>';
    $posthtml .= '</head>';
    $posthtml .= '<body>';
    $posthtml .= $message;
    $posthtml .= '</body>';
    return $posthtml;
}

function launch_stat_query($data) {
    global $CFG, $DB;

    $fromDateTimestamp = mktime(0,0,0,$data->from_date->month, $data->from_date->day, $data->from_date->year);
    $toDateTimestamp = mktime(0,0,0,$data->to_date->month, $data->to_date->day, $data->to_date->year);

    $params = array(
        'startdate'=>$fromDateTimestamp, 
        'enddate' =>$toDateTimestamp,
        'startdate2'=>$fromDateTimestamp, 
        'enddate2' =>$toDateTimestamp,
        'startdate3'=>$fromDateTimestamp, 
        'enddate3' =>$toDateTimestamp,
    );
    $categorieCondition = '';
    if ($data->category_id == 0) {
        $categorieCondition = "id IN (SELECT id from allowed_categories)";
    } else {
        $categorieCondition = "id = :categorieid";
        $params['categorieid'] = $data->category_id;
    }
    
    $queryStr = "
    -- temp table with course_categorie_path in text
    WITH RECURSIVE 
    allowed_categories AS (
        SELECT id
        from {course_categories} cc
        WHERE cc.name IN ('Gabarit', 'Parcours de formation', 'Session de formation', 'Archive')
    ),
    cc_path AS (
        SELECT id, name 
        FROM {course_categories}
        WHERE parent = 0 && ".$categorieCondition."
        UNION ALL
        SELECT cc.id, concat(ccp.name, '/',cc.name)
        FROM {course_categories} cc 
        INNER JOIN cc_path ccp ON ccp.id = cc.parent
    )
    
    SELECT 
        c.id AS id, 
        cc_path.name AS 'Categorie', 
        c.fullname AS 'Titre',
        DATE_FORMAT(FROM_UNIXTIME(c.startdate),'%d/%m/%Y') AS 'Date de debut',
        DATE_FORMAT(FROM_UNIXTIME(c.enddate),'%d/%m/%Y') AS 'Date de fin',
        DATE_FORMAT(FROM_UNIXTIME(c.timecreated),'%d/%m/%Y') AS 'Date de creation',
    
        DATE_FORMAT(FROM_UNIXTIME((
            SELECT lsls.timecreated
            FROM {logstore_standard_log} lsls
            WHERE lsls.timecreated BETWEEN :startdate AND :enddate
            AND lsls.eventname LIKE '_core_event_course_restored'
            AND lsls.objectid = c.id
            ORDER BY lsls.timecreated DESC
            LIMIT 1
        )),'%d/%m/%Y')
        AS 'Date de restauration-duplication',
    
        -- tuteur formateur
        (
            SELECT COUNT(DISTINCT(ra.userid))
            FROM {role_assignments} ra
            INNER JOIN {context} cx ON ra.contextid = cx.id AND cx.contextlevel = 50
            WHERE ra.roleid IN (SELECT id FROM {role} r WHERE r.shortname IN ('tuteur', 'formateur'))
            AND cx.instanceid = c.id 
        ) AS 'Nombre de formateurs/tuteurs',
        (
            SELECT COUNT(DISTINCT(ra.userid))
            FROM {role_assignments} ra
            INNER JOIN {context} cx ON ra.contextid = cx.id AND cx.contextlevel = 50
            INNER JOIN {user_lastaccess} ul ON (ul.userid=ra.userid AND ul.courseid = cx.instanceid)
            WHERE ra.roleid IN (SELECT id FROM {role} r WHERE r.shortname IN ('tuteur', 'formateur'))
            AND cx.instanceid = c.id 
        ) AS 'Nombre de formateurs/tuteurs connectes',
    
        -- participant
        (
            SELECT COUNT(*)
            FROM {role_assignments} ra
            INNER JOIN {context} cx ON ra.contextid = cx.id AND cx.contextlevel = 50
            WHERE ra.roleid IN (SELECT id FROM {role} r WHERE r.shortname = 'participant')
            AND cx.instanceid = c.id 
        ) AS 'Nombre de participants',
        -- participant
        (
            SELECT COUNT(*)
            FROM {role_assignments} ra
            INNER JOIN {context} cx ON ra.contextid = cx.id AND cx.contextlevel = 50
            INNER JOIN {user_lastaccess} ul ON (ul.userid=ra.userid AND ul.courseid = cx.instanceid)
            WHERE ra.roleid IN (SELECT id FROM {role} r WHERE r.shortname = 'participant')
            AND cx.instanceid = c.id 
        ) AS 'Nombre de participants connectes' ,
        -- completion_progress
        (
            SELECT COUNT(*)
            FROM {user_enrolments} ue
            INNER JOIN {enrol} e ON (e.id = ue.enrolid)
            INNER JOIN {user_lastaccess} ul ON (ul.userid=ue.userid AND ul.courseid = e.courseid)
            INNER JOIN {progress_complete} pc ON (pc.userid = ue.userid and pc.courseid = e.courseid)
            WHERE e.roleid = (SELECT id FROM {role} r WHERE r.shortname = 'participant') AND pc.is_complete = 1
            AND e.courseid = c.id
        ) AS 'Nombre de participants ayant terminé', 
        (
            SELECT COUNT(*) FROM {badge} b WHERE b.courseid = c.id
        )
        AS 'Badges proposes',
        (
            SELECT COUNT(bi.id)
            FROM {badge_issued} bi
            INNER JOIN {badge} b ON(b.id = bi.badgeid)
            WHERE b.courseid = c.id
        )
        AS 'Badges delivres',
    
        (
            SELECT IF(lcp.publish=1,'OFP',IF(lcp.publish=0,'OFF',''))
            FROM {local_coursehub_published} lcp
            WHERE lcp.courseid = c.id
        )
        AS 'Status publication',
    
        (
            SELECT li.tps_en_presence
            FROM {local_indexation} li
            WHERE li.courseid = c.id
        )
        AS 'Duree en presentiel',
        
        (
            SELECT li.tps_a_distance
            FROM {local_indexation} li
            WHERE li.courseid = c.id
        )
        AS 'Duree a distance',
        
        -- domaine and collection subquery
        (
            SELECT lic.name
            FROM {local_indexation} li
            INNER JOIN ".$CFG->centralized_dbname.".local_indexation_collections lic ON (lic.id = li.collectionid)
            WHERE li.courseid = c.id
        ) as 'Collection',
        (
            SELECT lid.name 
            FROM {local_indexation} li
            INNER JOIN ".$CFG->centralized_dbname.".local_indexation_domains lid ON (lid.id = li.domainid)
            WHERE li.courseid = c.id
        ) as 'Domaine',
        (
            SELECT
            (
                SELECT IF(cc.name='Session de formation' AND lw.id IS NOT NULL, 'Session en cours',cc.name)
                FROM {context} cx2
                INNER JOIN {course_categories} cc ON (cc.id = cx2.instanceid)
    
                LEFT JOIN {local_workflow} lw ON (lw.courseid=cx2.instanceid)
                WHERE cx2.id = SUBSTRING_INDEX(SUBSTRING_INDEX(cx.path, '/', 3), '/', -1)
            )
            FROM {context} cx
            WHERE cx.instanceid = c.id AND cx.contextlevel = 50
        )
        AS 'Etat du cycle de vie',
        -- nombre d'acti
    
        -- forum
        (
            SELECT COUNT(*)
            FROM {modules} m
            INNER JOIN {course_modules} cm ON cm.module = m.id
            WHERE m.name = 'forum' AND cm.course = c.id
        )
        AS 'Nombre d\'activites forum',
        ((
            SELECT COUNT(*) 
            FROM (
                SELECT DISTINCT fd.userid AS userid, fd.course
                FROM {forum_discussions} fd
                UNION
                SELECT DISTINCT fp.userid AS userid, fd.course
                FROM {forum_discussions} fd
                INNER JOIN {forum_posts} fp ON fd.id = fp.discussion
            ) AS nb_participant
            WHERE nb_participant.course = c.id
        ) / IFNULL(participants_count.nb, 0))
        AS 'Taux de participation forum',
            
            
        -- via/via déléguée
        (
            SELECT COUNT(*)
            FROM {modules} m
            INNER JOIN {course_modules} cm ON cm.module = m.id
            WHERE (m.name = 'via' OR m.name = 'viaassign') AND cm.course = c.id
        ) 
        AS 'Nombre d\'activites via',
        ((
            SELECT COUNT(*)
            FROM (
                SELECT DISTINCT vp.userid AS userid, v.course as course
                FROM {via} as v 
                INNER JOIN {via_participants} vp ON vp.activityid = v.id
                UNION
                SELECT DISTINCT vas.userid AS userid, va.course as course
                FROM {viaassign} as va
                INNER JOIN {viaassign_submission} vas ON vas.viaassignid = va.id
            ) as nb_participant
            WHERE nb_participant.course = c.id
        ) / IFNULL(participants_count.nb, 0))
        AS 'Taux de participation via',
    
        -- questionnaire
        (
            SELECT COUNT(*)
            FROM {modules} m
            INNER JOIN {course_modules} cm ON cm.module = m.id
            WHERE m.name = 'questionnaire' AND cm.course = c.id
        ) 
        AS 'Nombre d\'activites questionnaire',
        ((
            SELECT COUNT(DISTINCT qr.userid)
            FROM {questionnaire} q
            INNER JOIN {questionnaire_response} qr ON (q.id = qr.questionnaireid AND qr.complete = 'y')
            WHERE q.course = c.id
        ) / IFNULL(participants_count.nb, 0))
        AS 'Taux de participation questionnaire',
        -- test
        (
            SELECT COUNT(*)
            FROM {modules} m
            INNER JOIN {course_modules} cm ON cm.module = m.id
            WHERE m.name = 'quiz'  AND cm.course = c.id
        ) 
        AS 'Nombre d\'activites test',
    
        ((
            SELECT COUNT(DISTINCT qa.userid)
            FROM {quiz} q
            INNER JOIN {quiz_attempts} qa ON (q.id = qa.quiz)
            WHERE q.course = c.id
        ) / IFNULL(participants_count.nb, 0))
        AS 'Taux de participation test',
        -- choix de groupe
        (
            SELECT COUNT(*)
            FROM {modules} m
            INNER JOIN {course_modules} cm ON cm.module = m.id
            WHERE m.name = 'choicegroup' AND cm.course = c.id
        ) 
        AS 'Nombre d\'activites choix de groupe',
        ((
            SELECT COUNT(*)
            FROM {modules} m
            INNER JOIN {course_modules} cm ON cm.module = m.id
            WHERE m.name = 'choicegroup' AND cm.course = c.id
        ) / IFNULL(participants_count.nb, 0))
        AS 'Taux de participation choix de groupe',
        -- sondage
        (
            SELECT COUNT(DISTINCT sa.userid)
            FROM {survey} s
            INNER JOIN {survey_answers} sa ON (s.id = sa.survey)
            WHERE s.course = c.id
        )
        AS 'Nombre de participants sondage',
        ((
            SELECT COUNT(*)
            FROM {modules} m
            INNER JOIN {course_modules} cm ON cm.module = m.id
            WHERE m.name = 'survey' AND cm.course = c.id
        ) / IFNULL(participants_count.nb, 0))
        AS 'Taux de participation sondage',
        -- devoir
        (
            SELECT COUNT(*)
            FROM {modules} m
            INNER JOIN {course_modules} cm ON cm.module = m.id
            WHERE m.name = 'assign' AND cm.course = c.id
        ) 
        AS 'Nombre d\'activites devoir',
        ((
            SELECT COUNT(DISTINCT asu.userid)
            FROM {assign} a
            INNER JOIN {assign_submission} asu ON (a.id = asu.assignment)
            WHERE a.course = c.id
        ) / IFNULL(participants_count.nb, 0))
        AS 'Taux de participation devoir',
        -- choix de badge
        (
            SELECT COUNT(*)
            FROM {modules} m
            INNER JOIN {course_modules} cm ON cm.module = m.id
            WHERE m.name = 'coursebadges' AND cm.course = c.id
        ) 
        AS 'Nombre d\'activites choix de badge',
        ((
            SELECT COUNT(DISTINCT cbusb.userid)
            FROM {coursebadges} cb
            INNER JOIN {coursebadges_available_bdg} cbab ON (cb.id = cbab.coursebadgeid)
            INNER JOIN {coursebadges_usr_select_bdg} cbusb ON (cbab.id = cbusb.selectionbadgeid)
            WHERE cb.course = c.id
        ) / IFNULL(participants_count.nb, 0))
        AS 'Taux de participation choix de badge',
        -- base de données
        (
            SELECT COUNT(*)
            FROM {modules} m
            INNER JOIN {course_modules} cm ON cm.module = m.id
            WHERE m.name = 'data' AND cm.course = c.id
        ) 
        AS 'Nombre d\'activites base de données',
        ((
            SELECT COUNT(DISTINCT dr.userid)
            FROM {data} d
            INNER JOIN {data_records} dr ON (d.id = dr.dataid)
            WHERE d.course = c.id
        ) / IFNULL(participants_count.nb, 0))
        AS 'Taux de participation base de données'
    
    FROM {course} c
    INNER join cc_path ON c.category = cc_path.id
    LEFT JOIN (
        SELECT cx.instanceid AS instanceid, COUNT(*) AS nb
        FROM {role_assignments} ra
        INNER JOIN {context} cx ON ra.contextid = cx.id AND cx.contextlevel = 50
        INNER JOIN {user_lastaccess} ul ON (ul.userid=ra.userid AND ul.courseid = cx.instanceid)
        WHERE ra.roleid IN (SELECT id FROM {role} r WHERE r.shortname = 'participant')
        GROUP BY cx.instanceid
    ) participants_count ON c.id = participants_count.instanceid 
    WHERE c.timecreated BETWEEN :startdate2 AND :enddate2
    OR c.id IN(
       SELECT lsl.objectid FROM mdl_logstore_standard_log lsl WHERE lsl.timecreated BETWEEN :startdate3 AND :enddate3 AND lsl.eventname LIKE '_core_event_course_restored'
    )
    ";
    $records = $DB->get_records_sql($queryStr, $params);

    return $records;
}