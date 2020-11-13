<?php
namespace mod_data\task;

use moodle_url;

class data_notification_task extends \core\task\scheduled_task
{      
    public function get_name()
    {
        // Shown in admin screens
        return "Data Notification Task";
    }
                                                                     
    public function execute() 
    {
    	global $CFG, $DB,$USER;
        $userfrom = \core_user::get_noreply_user();
        $time2check = time() - (60*30);


    	// Retrieve data for notifications
    	$notifications = $DB->get_records_sql('SELECT com.id AS comid, com.itemid AS recordid, d.name,d.id AS dataid, co.id AS courseid, co.fullname, us.id AS userid, dnl.id AS dnlid, dnl.senddate,
uscom.firstname uscomfirstname, uscom.lastname uscomlastname
                                                FROM {comments} com
                                                INNER JOIN {data_records} dr ON dr.id = com.itemid
                                                INNER JOIN {user} us ON us.id = dr.userid
                                                INNER JOIN {data} d ON d.id = dr.dataid
                                                INNER JOIN {course} co ON co.id = d.course
                                                INNER JOIN {user} uscom ON uscom.id=com.userid
                                                LEFT JOIN {data_notif_logs} dnl ON (dnl.recordid = com.itemid AND dnl.courseid=co.id AND com.id=dnl.commentid)
                                                WHERE com.component = "mod_data"
                                                AND com.commentarea = "database_entry"
                                                AND com.timecreated >= ' .$time2check.'
                                                AND (dnl.id IS NULL OR dnl.senddate IS NULL)');

        foreach($notifications as $notif){
            // Check if it is a new notification that was never sent (dnlid is null => has to be save)
            // elseif => check if the notification is already saved but not sent (error => senddate is null)
            if (is_null($notif->dnlid) || is_null($notif->senddate)) {

                $userto = $DB->get_record('user', array('id' => $notif->userid));

                $record = new \stdClass();
                $params = new \stdClass();

                $params->bdd = $notif->name;
                $params->course = $notif->fullname;

                $params->data_creator_name = $notif->uscomfirstname . " " .  $notif->uscomlastname;
                $params->userto = $userto->firstname . " " .  $userto->lastname;

                $params->data_link = (new moodle_url('/mod/data/view.php', array('d' => $notif->dataid)))->out();
                $params->record_link = (new moodle_url('/mod/data/view.php', array('d' => $notif->dataid,'rid' =>$notif->recordid)))->out();

                $messageText = get_string('notifcontenttxt', 'data', $params);
                $messageHTML = get_string('notifcontenthtml', 'data', $params);
                $today = time();
                $subject = get_string('notifsubject', 'data', $params);

                if (is_null($notif->dnlid)) {
                    $record->recordid = $notif->recordid;
                    $record->timecreated = $today;
                    $record->senddate = $today;
                    $record->touser = $notif->userid;
                    $record->courseid = $notif->courseid;
                    $record->subject = $subject;
                    $record->messagetext = $messageText;
                    $record->messagehtml = $messageHTML;
                    $record->commentid = $notif->comid;
                    $DB->insert_record('data_notif_logs', $record);

                } elseif (is_null($notif->senddate)) {
                    $record->id = $notif->dnlid;
                    $record->senddate = time();
                    $DB->update_record('data_notif_logs', $record);
                }


                $hasBeenSent = email_to_user($userto, $userfrom, $subject, $messageText, $messageHTML);

                // If not sent, senddate becomes NULL to be send again next time
                if (!$hasBeenSent) {
                    $rec = new \stdClass();
                    $rec->id = $notif->dnlid;
                    $rec->senddate = "NULL";
                    $DB->update_record('data_notif_logs', $rec);
                }
            }
        }
    }                                                                                                                               
} 