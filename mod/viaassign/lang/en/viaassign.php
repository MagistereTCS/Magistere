<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Strings for component 'viaassign', language 'en'
 *
 * @package   mod_viaassign
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['actionsheader'] = 'Actions';
$string['access'] = 'Access';
$string['activityoverview'] = 'You have Via delegated activities that need attention';
$string['addsubmission'] = 'Add a Via virtual classroom';
$string['addnewattempt'] = 'Add a new attempt';
$string['addnewattemptfromprevious'] = 'Add a new attempt based on previous submission';
$string['addvia'] = 'Create a new virtual classroom';
$string['allowsubmissions'] = 'Allow the user to continue making submissions for this Via delegated activity.';
$string['allowsubmissionsshort'] = 'Unlocked - Allow changes';
$string['allowsubmissionsfromdate'] = 'Allow submissions from';
$string['allowsubmissionsfromdate_help'] = 'If enabled, students will not be able to submit before this date. If disabled, students will be able to start submitting right away.';
$string['allowsubmissionsfromdatesummary'] = 'This Via delegated activity will accept submissions from <strong>{$a}</strong>';
$string['allowsubmissionsanddescriptionfromdatesummary'] = 'The Via delegated activity details and submission form will be available from <strong>{$a}</strong>';
$string['associatedusers'] = 'All users assoiated to the virtual classroom';
$string['availability'] = 'Availability';

$string['backtoviaassignment'] = 'Back to Via delegated activity';
$string['batchoperationconfirmlock'] = 'Lock all selected submissions?';
$string['batchoperationconfirmgrantextension'] = 'Grant an extension to all selected submissions?';
$string['batchoperationconfirmunlock'] = 'Unlock all selected submissions?';
$string['batchoperationlock'] = 'lock submissions';
$string['batchoperationsdescription'] = 'With selected...';
$string['batchoperationunlock'] = 'unlock submissions';

$string['changegradewarning'] = 'This Via delegated activity has graded submissions and changing the grade will not automatically re-calculate existing submission grades. You must re-grade all existing submissions, if you wish to change the grade.';
$string['choosegradingaction'] = 'Grading action';
$string['choosemarker'] = 'Choose...';
$string['chooseoperation'] = 'Choose operation';
$string['comment'] = 'Comment';
$string['commentedon'] = 'Commented on';
$string['commentedby'] = 'Commented by';
$string['completionsubmit'] = 'Student must submit to this activity to complete it';
$string['courseusers'] = 'All users associated to the cours';
$string['creator'] = 'Created by';
$string['crontask'] = 'Sends notifications';
$string['currentgrade'] = 'Current grade in gradebook';

$string['dateheader'] = 'Date/Duration';
$string['datevalidation'] = 'The date selected is outside the permitted time slot.';
$string['defaultsettings'] = 'Default  Via delegated activity settings';
$string['defaultsettings_help'] = 'These settings define the defaults for all new Via delegated activities.';
$string['deleteallsubmissions'] = 'Delete all submissions';
$string['deletesubmission'] = 'Delete';
$string['deletedsubmissionforstudent'] = 'Delete submission for student: (id={$a->id}, fullname={$a->fullname}).';
$string['deletesubmissionother'] = 'Are you sure you want to delete "{$a->vianame}" for user {$a->username} ? Once deleted the information cannot be restored.';
$string['deletesubmissionown'] = 'Are you sure you want to delete "{$a->vianame}" ? Once deleted the information cannot be restored.';
$string['description'] = 'Description';
$string['duedate'] = 'Due date';
$string['duedate_help'] = 'This is when the  Via delegated activity is due. Submissions will still be allowed after this date but any  Via delegated activities submitted after this date are marked as late.';
$string['duedateno'] = 'No due date';
$string['duedatereached'] = 'The due date for this  Via delegated activity has now passed';
$string['duedatevalidation'] = 'Due date must be after the allow submissions from date.';
$string['duration'] = 'Duration (in minutes)';
$string['durationerror'] = 'The duration you entered is greater than the maximum allowed';
$string['durationheader'] = 'Duration';

$string['editingstatus'] = 'Editing status';
$string['editaction'] = 'Actions...';
$string['enabled'] = 'Enabled';
$string['errornosubmissions'] = 'There are no submissions to download';
$string['errorquickgradingvsadvancedgrading'] = 'The grades were not saved because this Via delegated activity is currently using advanced grading';
$string['errorrecordmodified'] = 'The grades were not saved because someone has modified one or more records more recently than when you loaded the page.';
$string['eventassessablesubmitted'] = 'A submission has been submitted.';
$string['eventextensiongranted'] = 'Extension granted.';
$string['eventfeedbackupdated'] = 'Feedback updated';
$string['eventfeedbackviewed'] = 'Feedback viewed';
$string['eventgradingformviewed'] = 'Grading form viewed';
$string['eventgradingtableviewed'] = 'Grading table viewed';
$string['eventidentitiesrevealed'] = 'The identities have been revealed.';
$string['eventsubmissioncreated'] = 'Virtual classroom created.';
$string['eventsubmissiondeleted'] = 'Virtual classroom deleted.';
$string['eventsubmissionformviewed'] = 'Submission form viewed.';
$string['eventsubmissiongraded'] = 'The submission has been graded.';
$string['eventsubmissionlocked'] = 'The submissions have been locked for a user.';
$string['eventsubmissionstatusupdated'] = 'The status of the submission has been updated.';
$string['eventsubmissionstatusviewed'] = 'The details page of this activity has been viewed.';
$string['eventsubmissionunlocked'] = 'The submissions have been unlocked for a user.';
$string['eventsubmissionupdated'] = 'Virtual classroom updated.';
$string['eventsubmissionviewed'] = 'Virtual classroom viewed.';
$string['extensionduedate'] = 'Extension due date';
$string['extensionnotafterduedate'] = 'Extension date must be after the due date';
$string['extensionnotafterfromdate'] = 'Extension date must be after the allow submissions from date';

$string['feedback'] = 'Feedback';
$string['feedbackavailablehtml'] = '{$a->username} has posted some feedback on your Via delegated activity for \'<i>{$a->viaassign}</i>\'<br /><br />. You can see it appended to your <a href="{$a->url}"> Via delegated activity submission </a>.';
$string['feedbackavailablesmall'] = '{$a->username} has given feedback for  Via delegated activity {$a->viaassign}';
$string['feedbackavailabletext'] = '{$a->username} has posted some feedback on your Via delegated activity submission for \'{$a->viaassign}\' You can see it appended to your  Via delegated activity submission:     {$a->url}';
$string['feedbackplugins'] = 'Feedback plugins';
$string['feedbackpluginforgradebook'] = 'Feedback plugin that will push comments to the gradebook';
$string['feedbackpluginforgradebook_help'] = 'Only one  Via delegated activity feedback plugin can push feedback into the gradebook.';
$string['feedbackplugin'] = 'Feedback plugin';
$string['feedbacksettings'] = 'Feedback settings';
$string['feedbacktypes'] = 'Feedback types';
$string['feedbackuser'] = 'Feedback {$a}';
$string['filesubmissions'] = 'File submissions';
$string['filter'] = 'Filter';
$string['filternone'] = 'No filter';
$string['filternotsubmitted'] = 'Not submitted';
$string['filterrequiregrading'] = 'Requires grading';
$string['filtersubmitted'] = 'Submitted';

$string['gradeabovemaximum'] = 'Grade must be less than or equal to {$a}.';
$string['gradebelowzero'] = 'Grade must be greater than or equal to zero.';
$string['gradecanbechanged'] = 'Grade can be changed';
$string['gradedby'] = 'Graded by';
$string['graded'] = 'Graded';
$string['gradedon'] = 'Graded on';
$string['gradeheader'] = 'Grade and comment';
$string['gradelocked'] = 'This grade is locked or overridden in the gradebook.';
$string['gradeoutof'] = 'Grade out of {$a}';
$string['gradeoutofhelp'] = 'Grade';
$string['gradeoutofhelp_help'] = 'Enter the grade for the student\'s submission here. You may include decimals.';
$string['gradersubmissiontext'] = '{$a->username} has created a virtual classroom for Via delegated activity \'{$a->viaassign}\' at {$a->timeupdated} It is available here: {$a->url}';
$string['gradersubmissionhtml'] = '{$a->username} has created a virtual classroom for Via delegated activity <i>\'{$a->viaassign}\'  at {$a->timeupdated}</i><br /><br />
It is <a href="{$a->url}">available on the web site</a>.';
$string['gradersubmissionsmall'] = '{$a->username} has created a virtual classroom for Via delegated activity {$a->viaassign}.';
$string['gradestudent'] = 'Grade student: (id={$a->id}, fullname={$a->fullname}). ';
$string['gradeuser'] = 'Grade {$a}';
$string['grading'] = 'Grading';
$string['gradingchangessaved'] = 'The grade and comment changes were saved';
$string['gradingmethodpreview'] = 'Grading criteria';
$string['gradingoptions'] = 'Options';
$string['gradingstatus'] = 'Grading status';
$string['gradingstudent'] = 'Grading student';
$string['gradingsummary'] = 'Grading summary';
$string['grantextension'] = 'Grant extension';
$string['grantextensionforusers'] = 'Grant extension for {$a} students';
$string['groupmode_hr'] = 'The groups or groupings are activated for this activity. You may only invite users from one group.';
$string['groupmode_nogroup'] = 'The groups or groupings are activated for this activity. You are not in any group, contact your teacher if you think this is an error.';
$string['groupselect'] = 'Select a group.';
$string['groupselect_help'] = 'When the group mode is actviated you can only select users from one group in which you are member. If the "Sperate group" mode was selected you will not see any other other groups\' public recordings.  If the "Visible group" mode is selected you will be able to see their recordings even if they are from a group you are not a member of.';

$string['hiddenuser'] = 'Participant ';
$string['hideshow'] = 'Hide/Show';
$string['hostonly'] = 'Host only';

$string['individualassignment'] = 'This is an individual assignment; you can not invite any others to join you.';
$string['invalidgradeforscale'] = 'The grade supplied was not valid for the current scale';
$string['invalidfloatforgrade'] = 'The grade provided could not be understood: {$a}';

$string['lastmodifiedsubmission'] = 'Last modified (submission)';
$string['lastmodifiedgrade'] = 'Last modified (grade)';
$string['latesubmissions'] = 'Late submissions';
$string['latesubmissionsaccepted'] = 'Only student(s) having been granted extension can still submit the Via delegated activity';
$string['locksubmissionforstudent'] = 'Prevent any more submissions for student: (id={$a->id}, fullname={$a->fullname}).';
$string['locksubmissions'] = 'Lock submissions';

$string['maxactivities'] = 'Maximum number per user';
$string['maxactivities_help'] = 'The maximum number of virtual classes users may create.';
$string['maxactivitiesreached'] = 'Maximum number of virtual classrooms reached';
$string['maxduration'] = 'Maximum time (in minutes)';
$string['maxduration_hr'] = '(Maximum time {$a} minutes)';
$string['maxduration_help'] = 'This will be applied to all activites as default as the maximum duration.';
$string['maxgrade'] = 'Maximum grade';
$string['maxgrade'] = 'Maximum Grade';
$string['maxusers'] = 'Maximum number of guests';
$string['maxusers_help'] = 'Maximum number of guests; 0 means an individual activity. Greater than 0 means that other partipants will be allowed to join the virtual classroom if invited by the creator.';
$string['messageprovider:viaassign_notification'] = 'Via delegated activity notifications';
$string['modulename'] = 'Via delegated activity';
$string['modulename_help'] = 'The Via delegated activity description to be added here!!!!';
$string['modulename_link'] = 'mod/viaassignment/view';
$string['modulenameplural'] = 'Via delegated activities';
$string['moreusers'] = '{$a} more...';
$string['mysubmission'] = 'My submission: ';

$string['newsubmissions'] = 'Via delegated activities submitted';
$string['noattempt'] = 'No attempt';
$string['nograde'] = 'No grade. ';
$string['nolatesubmissions'] = 'No late submissions accepted. ';
$string['nopotentialusers'] = 'There are no potential users to chose from.';
$string['nosavebutnext'] = 'Next';
$string['nosubmission'] = 'Nothing has been submitted for this Via delegated activity';
$string['notgraded'] = 'Not graded';
$string['notgradedyet'] = 'Not graded yet';
$string['notsubmittedyet'] = 'Not submitted yet';
$string['notifications'] = 'Notifications';
$string['nousersselected'] = 'No users selected';
$string['novccreated'] = 'No virtual classrooms were created';
$string['numberofparticipants'] = 'Participants';
$string['numberofparticipantswithvia'] = 'Number of participants having created a virtual classroom';
$string['numberofsubmittedviaassignments'] = 'Submitted';
$string['numberofsubmissionsneedgrading'] = 'Needs grading';

$string['offline'] = 'No online submissions required';
$string['open'] = 'Open';
$string['outof'] = '{$a->current} out of {$a->total}';
$string['overdue'] = '<font color="red">Overdue by: {$a}</font>';
$string['outlinegrade'] = 'Grade: {$a}';

$string['page-mod-viaassign-x'] = 'Any Via delegated activity module page';
$string['page-mod-viaassign-view'] = 'Via delegated activity module main and submission page';
$string['participant'] = 'Participant';
$string['pluginadministration'] = 'Via delegated activity - administration';
$string['pluginname'] = 'Via delegated activity';
$string['potentialusers'] = 'Potential users';
$string['potentialdates'] = 'Select a date between {$a->start} and {$a->end}';
$string['preventsubmissions'] = 'Prevent the user from creating any more Via - virtual classrooms within this Via - delegated activity.';
$string['preventsubmissionsshort'] = 'Lock - Prevent changes';
$string['previous'] = 'Previous';
$string['publicplaybacks'] = 'Public playbacks';

$string['quickgrading'] = 'Quick grading';
$string['quickgrading_help'] = 'Quick grading allows you to viaassign grades (and outcomes) directly in the submissions table. Quick grading is not compatible with advanced grading and is not recommended when there are multiple markers.';
$string['quickgradingchangesnotsaved'] = 'Nothing was saved as there were no modifications';
$string['quickgradingchangessaved'] = 'The grade and comment changes were saved';
$string['quickgradingresult'] = 'Quick grading';

$string['recordid'] = 'Identifier';
$string['recording'] = 'Recording';
$string['recordings'] = 'Recordings';
$string['reviewactivity'] = 'Default access rights for recordings';
$string['reviewactivity_help'] = 'Select who can see the recordings.';
$string['reviewed'] = 'Reviewed';
$string['roomtype'] = 'Room type';

$string['saveallquickgradingchanges'] = 'Save all quick grading changes';
$string['savechanges'] = 'Save changes';
$string['savegradingresult'] = 'Grade';
$string['savenext'] = 'Save and show next';
$string['scale'] = 'Scale';
$string['selectedusers'] = 'Selected users';
$string['selectlink'] = 'Select...';
$string['selectuser'] = 'Select {$a}';
$string['seminar'] = 'Seminar';
$string['sendnotifications'] = 'Notify graders about submissions';
$string['sendnotifications_help'] = 'If enabled, graders (usually teachers) receive a message whenever a student submits an Via delegated activity, early, on time and late. Message methods are configurable.';
$string['sendstudentnotifications'] = 'Notify students';
$string['sendstudentnotifications_help'] = 'If enabled, students receive a message about the updated grade or feedback.';
$string['sendstudentnotificationsdefault'] = 'Default setting for "Notify students"';
$string['sendstudentnotificationsdefault_help'] = 'Set the default value for the "Notify students" checkbox on the grading form.';
$string['settings'] = 'Via delegated activity settings';
$string['showrecentsubmissions'] = 'Show recent submissions';
$string['standard'] = 'Virtuel classroom (standard)';
$string['status'] = 'Status';

$string['submissionreceipttext'] = 'You have submitted an
Via delegated activity submission for \'{$a->viaassign}\'

You can see the status of your Via delegated activity submission:

    {$a->url}';

$string['submissionreceiptsmall'] = 'You have submitted your Via delegated activity submission for {$a->viaassign}';
$string['submissionslocked'] = 'This v is not accepting submissions';
$string['submissionslockedshort'] = 'Locked - changes are not allowed';
$string['submissions'] = 'Submissions';
$string['submissionsnotgraded'] = 'Submissions not graded: {$a}';
$string['submissionsclosed'] = 'Submissions closed';
$string['submissionsettings'] = 'Submission settings';
$string['submissionstatus_marked'] = 'Graded';
$string['submissionstatus_new'] = 'New submission';
$string['submissionstatus_reopened'] = 'Reopened';
$string['submissionstatus_'] = 'To do';
$string['submissionstatus'] = 'Status';
$string['submissionstatus_created'] = 'Created';
$string['submissionstatus_future'] = 'Upcoming';
$string['submissionstatus_done'] = 'Finished';
$string['submissionstatus_now'] = 'Under way';
$string['submissionsummary'] = '{$a->status}. Last modified on {$a->timemodified}';
$string['submissionteam'] = 'Group';
$string['submissiontypes'] = 'Submission types';
$string['submission'] = 'Submission';
$string['submitaction'] = 'Submit';
$string['submitviaassignment_help'] = 'Once this Via delegated activity is submitted you will not be able to make any more changes.';
$string['submitviaassignment'] = 'Submit Via delegated activity';
$string['submittedearly'] = 'Via delegated activity was submitted {$a} early';
$string['submittedlate'] = 'Via delegated activity was submitted {$a} late';
$string['submittedlateshort'] = '{$a} late';
$string['submitted'] = 'Submitted';
$string['subplugintype_viaassignfeedback'] = 'Feedback plugin';
$string['subplugintype_viaassignfeedback_plural'] = 'Feedback plugins';

$string['takepresence'] = 'Presence report';
$string['takepresence_help'] = 'HELP : Presence report';
$string['textinstructions'] = 'Via delegated activity instructions';
$string['timemodified'] = 'Last modified';
$string['timeremaining'] = 'Time remaining';
$string['time'] = 'Scheduled date and time';
$string['title'] = 'Title';
$string['toomanyusers'] = 'You have associated too many users to this activity, please refer to the "Maximum number of guests".';

$string['unlocksubmissionforstudent'] = 'Allow submissions for student: (id={$a->id}, fullname={$a->fullname}).';
$string['unlocksubmissions'] = 'Unlock submissions';
$string['updategrade'] = 'Update grade';
$string['updatetable'] = 'Save and update table';
$string['upgradenotimplemented'] = 'Upgrade not implemented in plugin ({$a->type} {$a->subtype})';
$string['userextensiondate'] = 'Extension granted until: {$a}';
$string['userrole'] = 'Role required for creation';
$string['userrole_help'] = 'Role required to create Via virtual classrooms, you may select more than one.';
$string['userrole_none'] = 'No user roles are permitted to create Via virtual classrooms using this activiy type, please contact your sites administrator. The administrator will need to modify permissions at site level.';
$string['userrole_none_short'] = 'No user roles!';
$string['userrolevalidation'] = 'You must select at least one role that will be allowed to create activities. If none are available please contact your administrator. The administrator will need to modify permissions at site level.';
$string['usergrade'] = 'User grade';

$string['viaassign:addinstance'] = 'Add a new Via delegated activity';
$string['viaassign:deleteown'] = 'Delete own virtual classrooms';
$string['viaassign:deleteothers'] = 'Delete others virtual classrooms';
$string['viaassign:grade'] = 'Grade Via delegated activity';
$string['viaassign:grantextension'] = 'Grant extension';
$string['viaassign:managegrades'] = 'Review and release grades';
$string['viaassign:viewgrades'] = 'View grades';
$string['viaassign:submit'] = 'Submit Via delegated activity';
$string['viaassign:view'] = 'View Via delegated activity';
$string['viaassignfeedback'] = 'Via Feedback plugin';
$string['viaassignfeedbackpluginname'] = 'Via Feedback plugin';
$string['viaassignhistory'] = 'Virtual classrooms I created';
$string['viaassignmentmail'] = '{$a->grader} has posted some feedback on your viaassignment submission for \'{$a->viaassign}\' You can see it appended to your Via delegated activity submission:  {$a->url}';
$string['viaassignmentmailhtml'] = '<p>{$a->grader} has posted some feedback on your viaassignment submission for \'<i>{$a->viaassign}</i>\'.</p> <p>You can see it appended to your <a href="{$a->url}">Via delegated activity submission</a>.</p>';
$string['viaassignmentmailsmall'] = '{$a->grader} has posted some feedback on your viaassignment submission for \'{$a->viaassign}\' You can see it appended to your submission';
$string['viaassignmentname'] = 'Title';
$string['viaassignmentplugins'] = 'Via delegated activity plugins';
$string['viaassignmentsperpage'] = 'Via delegated activities per page';
$string['viaassignparticipation'] = 'Virtual classrooms in which I will participate';
$string['viaassignparticipation_none'] = 'You have not been invited to participate in any virtual classrooms so far.';
$string['viasettings'] = 'Settings';
$string['titleinfo1'] = 'The title is a link to the details of your virtual classroom where you can also import content, replay/export your recordings, view the list of your guests (if allowed) and make changes if necessary.';
$string['titleinfo2'] = 'Direct access to the virtual classroom. When the class is over, this button is a link to the recordings, if there are any. You can always access the details of your virtual classroom by clicking the title.';
$string['titleinfo3'] = 'The title is a link to the details of the virtual classroom where you can download imported content, replay recordings, etc.';
$string['titleinfo4'] = 'Direct access to the virtual classroom. When the class is over, this button is a link to the recordings, if there are any. You can always access the details of the virtual classroom by clicking the title.';
$string['viewfeedback'] = 'View feedback';
$string['viewfeedbackforuser'] = 'View feedback for user: {$a}';
$string['viewfullgradingpage'] = 'Open the full grading page to provide feedback';
$string['viewgradebook'] = 'View gradebook';
$string['viewgradingformforstudent'] = 'View grading page for student: (id={$a->id}, fullname={$a->fullname}).';
$string['viewgrading'] = 'View/grade all submissions';
$string['viewsubmissionforuser'] = 'View submission for user: {$a}';
$string['viewsubmission'] = 'View submission';
$string['viewfull'] = 'View full';
$string['viewsummary'] = 'View summary';
$string['viewsubmissiongradingtable'] = 'View submission grading table.';
