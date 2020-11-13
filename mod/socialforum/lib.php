<?php

///////////////
// CONSTANTS //
///////////////

define('SF_CHOOSESUBSCRIBE', 0);
define('SF_FORCESUBSCRIBE', 1);
define('SF_INITIALSUBSCRIBE', 2);
define('SF_DISALLOWSUBSCRIBE',3);

define('SF_SUBSCRIBESUBJECT', 1);
define('SF_UNSUBSCRIBESUBJECT',0);

define('SF_SUBJECT_VIEW', 1);
define('SF_CONTRIBUTION_VIEW', 2);

defined('MOODLE_INTERNAL') || die();

/** Include required files */
require_once($CFG->libdir.'/filelib.php');
require_once($CFG->libdir.'/eventslib.php');
require_once($CFG->dirroot.'/user/selector/lib.php');

////////////////////////
// STANDARD FUNCTIONS //
////////////////////////

function socialforum_supports($feature) {
    switch($feature) {
        case FEATURE_MOD_ARCHETYPE:
            return false;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return false;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        default:
            return null;
    }
}

/**
 * Saves a new instance of the socialforum into the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param object $socialforum An object from the form in mod_form.php
 * @param mod_socialforum_mod_form $mform
 * @return int The id of the newly inserted socialforum record
 */
function socialforum_add_instance(stdClass $socialforum, mod_socialforum_mod_form $mform = null) {
    global $DB, $USER;
    $socialforum->userid = $USER->id;
    $socialforum->timecreated = time();

    $id = $DB->insert_record('socialforum', $socialforum);
    manage_subscriptions($id, $socialforum->modesubscribe);
    return $id;
}

/**
 * Updates an instance of the socialforum in the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param object $socialforum An object from the form in mod_form.php
 * @param mod_socialforum_mod_form $mform
 * @return boolean Success/Fail
 */
function socialforum_update_instance(stdClass $socialforum, mod_socialforum_mod_form $mform = null) {
    global $DB;

    $socialforum->timemodified = time();
    $socialforum->id = $socialforum->instance;
    $socialforum->timemodified = time();

    $bool = $DB->update_record('socialforum', $socialforum);
    manage_subscriptions($socialforum->id, $socialforum->modesubscribe);
    return $bool;
}

/**
 * Removes an instance of the socialforum from the database
 *
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 */
function socialforum_delete_instance($id) {
    global $DB;

    if (! $socialforum = $DB->get_record('socialforum', array('id' => $id))) {
        return false;
    }

    # Delete any dependent records here #
    $sf = new SocialForum($socialforum->id);
    $sf->delete_contributions();

    $DB->delete_records('socialforum', array('id' => $socialforum->id));

    return true;
}

///////////////////
// GENERAL CLASS //
///////////////////
class SocialForum {

    private $id = 0;
    private $forum = null;
    private $allcontributions = array();
    private $allcontributionids = "";
    private $allcontributionsnotmailed = array();
    private $allpopularities = array();
    private $allfavorites = array();
    private $contributions = array();
    private $subjects = array();


    function __construct($id)
    {
        $this->id = $id;
        $this->load_forum();
        $this->load_all_contributions();
        $this->load_contributions_and_subjects();
        $this->load_all_popularities();
        $this->load_all_favorites();
    }

    protected function load_forum()
    {
        global $DB;
        $this->forum = $DB->get_record('socialforum', array('id' => $this->id));
    }

    protected function load_all_contributions()
    {
        global $DB;
        $this->allcontributions = $DB->get_records('sf_contributions', array('socialforum' => $this->id));
        $contributionids = "";
        foreach($this->allcontributions as $contribution){
            $contributionids .= $contribution->id.",";
        }
        $this->allcontributionids = substr($contributionids, 0, -1);
    }

    protected function load_all_popularities()
    {
        global $DB;
        if($this->allcontributionids){
            $this->allpopularities = $DB->get_records_sql('SELECT * FROM {sf_popularities} WHERE contribution IN('.$this->allcontributionids.')');
        }
    }

    protected function load_all_favorites()
    {
        global $DB;
        if($this->allcontributionids){
            $this->allfavorites = $DB->get_records_sql('SELECT * FROM {sf_favorites} WHERE contribution IN('.$this->allcontributionids.')');
        }
    }

    protected function load_contributions_and_subjects()
    {
        $contributions = array();
        $subjects = array();
        foreach($this->allcontributions as $contribution){
            if($contribution->issubject == 0){
                array_push($contributions, $contribution);
            } else {
                array_push($subjects, $contribution);
            }
        }
        $this->contributions = $contributions;
        $this->subjects = $subjects;
    }

    protected function load_contributions_not_mailed()
    {
        $contributions = array();
        foreach($this->allcontributions as $contribution){
            if($contribution->issubject == 0 && $contribution->mailed == 0){
                array_push($contributions, $contribution);
            }
        }
        $this->allcontributionsnotmailed = $contributions;
    }

    ///********* Contribution functions ***********///
    function delete_contribution($id){
        global $DB;
        foreach($this->contributions as $contribution) {
            if ($contribution->id == $id) {
                // Suppression des popularities
                foreach($this->allpopularities as $popularity){
                    if($popularity->contribution == $id){
                        $DB->delete_records('sf_popularities', array('contribution'=>$popularity->contribution));
                    }
                }

                // Suppression des favorites
                foreach($this->allfavorites as $favorite){
                    if($favorite->contribution == $id){
                        $DB->delete_records('sf_favorites', array('contribution'=>$favorite->contribution));
                    }
                }

                // Suppression de la contribution
                $DB->delete_records('sf_contributions', array('id'=>$contribution->id));
            }
        }
    }
    function delete_contributions(){
        global $DB;
        foreach($this->allcontributions as $contribution) {
            // Suppression des popularities
            foreach($this->allpopularities as $popularity){
                if($popularity->contribution == $contribution->id){
                    $DB->delete_records('sf_popularities', array('contribution'=>$popularity->contribution));
                }
            }

            // Suppression des favorites
            foreach($this->allfavorites as $favorite){
                if($favorite->contribution == $contribution->id){
                    $DB->delete_records('sf_favorites', array('contribution'=>$favorite->contribution));
                }
            }

            // Suppression de la contribution
            $DB->delete_records('sf_contributions', array('id'=>$contribution->id));
        }
    }

    function delete_cascade_subject($subject_id){
        global $DB;
        if($subject_id){
            // Suppression des contributions
            foreach($this->get_contributions_by_subject_id($subject_id) as $contribution){
                $this->delete_contribution($contribution->id);
            }
            // Suppression des subscriptions
            $this->delete_subscriptions_by_subject_id($subject_id);
            // Suppression du sujet
            $DB->delete_records('sf_contributions', array('id'=>$subject_id));
            return true;
        }
        return false;
    }

    ///********* Subscription functions ***********///
    function delete_subscriptions_by_subject_id($subject_id){
        global $DB;
        if($subject_id){
            $DB->delete_records('sf_subscriptions', array('subject'=>$subject_id));
            return true;
        }
        return false;
    }

    ///********* Popularity functions ***********///

    function add_popularity($id,$userid){
        global $DB;
        foreach($this->allcontributions as $contribution){
            if($contribution->id == $id){
                $popularity = new stdClass();
                $popularity->contribution = $id;
                $popularity->userid = $userid;
                $popularity->timecreated = time();
                return $DB->insert_record('sf_popularities', $popularity);
            }
        }
        return false;
    }

    function delete_popularity($id,$userid){
        global $DB;
        foreach($this->allpopularities as $popularity){
            if($popularity->contribution == $id && $popularity->userid == $userid){
                $DB->delete_records('sf_popularities', array('contribution'=>$popularity->contribution, 'userid'=>$popularity->userid));
                return true;
            }
        }
        return false;
    }

    function get_popularity_by_user_and_contribution($id, $userid){
        foreach($this->allpopularities as $popularity){
            if($popularity->contribution == $id && $popularity->userid == $userid){
                return $popularity;
            }
        }
        return false;
    }

    function get_popularities_by_contribution_id($id)
    {
        $datas = array();
        foreach($this->allpopularities as $popularity){
            if($popularity->contribution == $id){
                array_push($datas, $popularity);
            }
        }
        return $datas;
    }

    function get_all_popularities_by_subject_id($subject_id)
    {
        global $DB;
        $contributionids = "";
        foreach($this->contributions as $contribution){
            if($contribution->subject == $subject_id && $contribution->timepublished <= time()) {
                $contributionids .= $contribution->id . ",";
            }
        }
        $contributionids = substr($contributionids, 0, -1);
        $datas = $DB->get_records('sf_popularities', array('contribution'=>$subject_id));
        if($contributionids){
            foreach($DB->get_records_sql('SELECT * FROM {sf_popularities} WHERE contribution IN('.$contributionids.')') as $popularity){
                array_push($datas, $popularity);
            }
        }
        return $datas;
    }

    ///********* Favorite functions ***********///

    function add_favorite($id,$userid){
        global $DB;
        foreach($this->allcontributions as $contribution){
            if($contribution->id == $id){
                $favorite = new stdClass();
                $favorite->contribution = $id;
                $favorite->userid = $userid;
                $favorite->timecreated = time();
                return $DB->insert_record('sf_favorites', $favorite);
            }
        }
        return false;
    }

    function delete_favorite($id,$userid){
        global $DB;
        foreach($this->allfavorites as $favorite){
            if($favorite->contribution == $id && $favorite->userid == $userid){
                $DB->delete_records('sf_favorites', array('contribution'=>$favorite->contribution, 'userid'=>$favorite->userid));
                return true;
            }
        }
        return false;
    }

    function get_favorite_by_user_and_contribution($id, $userid){
        foreach($this->allfavorites as $favorite){
            if($favorite->contribution == $id && $favorite->userid == $userid){
                return $favorite;
            }
        }
        return false;
    }

    function get_all_favorites_by_subject_id($subject_id)
    {
        global $DB;
        $contributionids = "";
        foreach($this->contributions as $contribution){
            if($contribution->subject == $subject_id && $contribution->timepublished <= time()) {
                $contributionids .= $contribution->id . ",";
            }
        }
        $contributionids = substr($contributionids, 0, -1);
        $datas = $DB->get_records('sf_favorites', array('contribution'=>$subject_id));
        if($contributionids){
            foreach($DB->get_records_sql('SELECT * FROM {sf_favorites} WHERE contribution IN('.$contributionids.')') as $favorite){
                array_push($datas, $favorite);
            }
        }
        return $datas;
    }


    ///********* Contribution functions ***********///

    function get_instance()
    {
        return $this->forum;
    }

    function get_contributions_by_subject_id($subject_id)
    {
        $datas = array();
        foreach($this->contributions as $contribution){
            if($contribution->subject == $subject_id && $contribution->timepublished <= time()){
                array_push($datas, $contribution);
            }
        }
        return $datas;
    }

    function get_deferred_contributions_by_subject_id($subject_id)
    {
        $datas = array();
        foreach($this->contributions as $contribution){
            if($contribution->subject == $subject_id && $contribution->timepublished > time()){
                array_push($datas, $contribution);
            }
        }
        return $datas;
    }

    function get_subject_by_id($subject_id)
    {
        foreach($this->subjects as $subject){
            if($subject->id == $subject_id){
                return $subject;
            }
        }
        return false;
    }

    function get_all_subjects()
    {
        return $this->subjects;
    }

    function get_count_subjects()
    {
        return count($this->subjects);
    }

    function get_best_popularity_contribution($subject_id)
    {
        global $DB;
        $contributionids = "";
        foreach($this->contributions as $contribution){
            if($contribution->subject == $subject_id && $contribution->timepublished <= time()) {
                $contributionids .= $contribution->id . ",";
            }
        }
        $contributionids = substr($contributionids, 0, -1);
        $bestlikecontribution = false;
        if($contributionids){
            $bestlikecontribution = $DB->get_record_sql('   SELECT * FROM {sf_contributions} 
                                                            WHERE id =(    SELECT contribution
                                                                            FROM {sf_popularities} 
                                                                            WHERE contribution IN('.$contributionids.')
                                                                            GROUP BY contribution
                                                                            ORDER BY COUNT(*) ASC, timecreated ASC
                                                                            LIMIT 1)
                                                            LIMIT 1');
        }
        return $bestlikecontribution;
    }

    ///******** Sorting functions *********///

    function sort_all_contributions_by_date($subject_id, $sort='ASC')
    {
        global $DB;
        $contributionids = "";
        foreach($this->contributions as $contribution){
            if($contribution->subject == $subject_id && $contribution->timepublished <= time()) {
                $contributionids .= $contribution->id . ",";
            }
        }
        $contributionids = substr($contributionids, 0, -1);
        $datas = array();
        if($contributionids){
            foreach($DB->get_records_sql('SELECT * FROM {sf_contributions} WHERE id IN('.$contributionids.') ORDER BY timecreated '.$sort.'') as $contribution){
                array_push($datas, $contribution);
            }
        }
        return $datas;
    }

    function sort_all_contributions_by_popularity($subject_id, $sorting)
    {
        global $DB;
        $contributionids = "";
        foreach($this->contributions as $contribution){
            if($contribution->subject == $subject_id && $contribution->timepublished <= time()) {
                $contributionids .= $contribution->id . ",";
            }
        }
        $contributionids = substr($contributionids, 0, -1);
        if($contributionids){
            $datas = array();
            $bestlikecontribution = $DB->get_record_sql('   SELECT * FROM {sf_contributions} 
                                                            WHERE id =(    SELECT contribution
                                                                            FROM {sf_popularities} 
                                                                            WHERE contribution IN('.$contributionids.')
                                                                            GROUP BY contribution
                                                                            ORDER BY COUNT(*) ASC, timecreated ASC
                                                                            LIMIT 1)
                                                            LIMIT 1');
            array_push($datas, $bestlikecontribution);
            foreach($DB->get_records_sql('SELECT * FROM {sf_contributions} WHERE id IN('.$contributionids.') ORDER BY timecreated '.$sorting.'') as $contribution){
                if($contribution->id != $bestlikecontribution->id){
                    array_push($datas, $contribution);
                }
            }
        }
        return $datas;
    }

    function sort_all_contributions_by_favorite($subject_id, $sorting)
    {
        global $DB, $USER;
        $contributionids = "";
        foreach($this->contributions as $contribution){
            if($contribution->subject == $subject_id && $contribution->timepublished <= time()) {
                $contributionids .= $contribution->id . ",";
            }
        }
        $contributionids = substr($contributionids, 0, -1);
        if($contributionids){
            $datas = array();
            $favoritedcontributions = $DB->get_records_sql(' SELECT * FROM {sf_contributions} 
                                                            WHERE id IN(    SELECT contribution
                                                                            FROM {sf_favorites} 
                                                                            WHERE contribution IN('.$contributionids.')
                                                                            AND userid = '.$USER->id.')
                                                                  ORDER BY timecreated '.$sorting.'');
            if(count($favoritedcontributions)!= 0) {
                $datas = $favoritedcontributions;
            }
            foreach($DB->get_records_sql('SELECT * FROM {sf_contributions} WHERE id IN('.$contributionids.') ORDER BY timecreated '.$sorting.'') as $contribution){
                if(!array_key_exists($contribution->id, $favoritedcontributions)){
                    array_push($datas, $contribution);
                }
            }
        }
        return $datas;
    }
}

//////////////////////
// CUSTOM FUNCTIONS //
//////////////////////

function socialforum_add_contribution($id, $subject_id=null){
    global $CFG;
    $content = '';
    $content .= '<div class="singlebutton forumaddnew">';
    $content .= "<form id=\"newcontributionform\" method=\"get\" action=\"$CFG->wwwroot/mod/socialforum/post.php\">";
    $content .= '<div>';
    $content .= "<input type=\"hidden\" name=\"socialforum\" value=\"$id\" />";
    if(!$subject_id){
        $content .= "<input type=\"hidden\" name=\"issubject\" value= 1 />";
        $content .= '<input type="submit" value="'.get_string('addanewsubject', 'socialforum').'" />';
    } else {
        $content .= "<input type=\"hidden\" name=\"ispost\" value= 1 />";
        $content .= "<input type=\"hidden\" name=\"subject\" value= ".$subject_id." />";
        $content .= '<input type="submit" value="'.get_string('addanewcontribution', 'socialforum').'" />';
    }
    $content .= '</div>';
    $content .= '</form>';
    $content .= "</div>\n";
    return $content;
}

function manage_subscriptions($id, $type_subscription=SF_CHOOSESUBSCRIBE){
    global $DB;
    return $DB->set_field("socialforum", "modesubscribe", $type_subscription, array("id" => $id));
}

/**
 * @global object
 * @param int $userid
 * @param object $socialforum
 * @return bool
 */
function socialforum_subject_is_subscribed($userid, $subjectid) {
    global $DB;

    return $DB->record_exists("sf_subscriptions", array("userid" => $userid, "subject" => $subjectid));
}

/**
 * Adds user to the subscriber list
 *
 * @global object
 * @param int $userid
 * @param int $sujectid
 */
function socialforum_subject_subscribe($userid, $sujectid) {
    global $DB;

    if ($DB->record_exists("sf_subscriptions", array("userid"=>$userid, "subject"=>$sujectid))) {
        return true;
    }

    $sub = new stdClass();
    $sub->userid  = $userid;
    $sub->subject = $sujectid;
    $sub->timecreated = time();

    return $DB->insert_record("sf_subscriptions", $sub);
}

/**
 * Removes user from the subscriber list
 *
 * @global object
 * @param int $userid
 * @param int $sujectid
 */
function socialforum_subject_unsubscribe($userid, $sujectid) {
    global $DB;
    return $DB->delete_records("sf_subscriptions", array("userid"=>$userid, "subject"=>$sujectid));
}

function socialforum_get_subscribemode($socialforum) {
    global $DB;
    if (isset($socialforum->modesubscribe)) {    // then we use that
        return $socialforum->modesubscribe;
    } else {   // Check the database
        return $DB->get_field('socialforum', 'modesubscribe', array('id' => $socialforum));
    }
}

/**
 * Adds module specific settings to the settings block
 *
 * @param settings_navigation $settings The settings navigation object
 * @param navigation_node $forumnode The node to add module settings to
 */
function socialforum_extend_settings_navigation(settings_navigation $settingsnav, navigation_node $socialforumnode){
    global $USER, $PAGE, $CFG, $DB, $OUTPUT;

    $socialforum = $DB->get_record("socialforum", array("id" => $PAGE->cm->instance));
    if (empty($PAGE->cm->context)) {
        $PAGE->cm->context = context_module::instance($PAGE->cm->instance);
    }

    $explode = explode('/',$PAGE->url->get_path());
    $file = end($explode);
    $showlink = false;

    $subjectid= null;
    if($file == 'view.php' && $PAGE->url->get_param('issubject')){ // Vue d'un sujet
        $subjectid = $PAGE->url->get_param('ctid');
        if($subjectid){
            $showlink = true;
        }
    }

    if($file == 'post.php' && $PAGE->url->get_param('subject')){ // Formulaire des contributions
        $subjectid = $PAGE->url->get_param('subject');
        if($subjectid){
            $showlink = true;
        }
    }

// for some actions you need to be enrolled, beiing admin is not enough sometimes here
    $enrolled = is_enrolled($PAGE->cm->context, $USER, '', false);
    $activeenrolled = is_enrolled($PAGE->cm->context, $USER, '', true);

    $canmanage  = has_capability('mod/socialforum:managesubscriptions', $PAGE->cm->context, $USER->id);
    $subscriptionmode = socialforum_get_subscribemode($socialforum);
    //$cansubscribe = ($activeenrolled && $subscriptionmode != FORUM_FORCESUBSCRIBE && ($subscriptionmode != FORUM_DISALLOWSUBSCRIBE || $canmanage));

    if ($canmanage) {
        $mode = $socialforumnode->add(get_string('subscriptionmode', 'forum'), null, navigation_node::TYPE_CONTAINER);

        $allowchoice = $mode->add(get_string('subscriptionoptional', 'forum'), new moodle_url('/mod/socialforum/subscribe.php', array('id'=>$socialforum->id, 'mode'=>SF_CHOOSESUBSCRIBE, 'sesskey'=>sesskey())), navigation_node::TYPE_SETTING);
        $forceforever = $mode->add(get_string("subscriptionforced", "forum"), new moodle_url('/mod/socialforum/subscribe.php', array('id'=>$socialforum->id, 'mode'=>SF_FORCESUBSCRIBE, 'sesskey'=>sesskey())), navigation_node::TYPE_SETTING);
        $forceinitially = $mode->add(get_string("subscriptionauto", "forum"), new moodle_url('/mod/socialforum/subscribe.php', array('id'=>$socialforum->id, 'mode'=>SF_INITIALSUBSCRIBE, 'sesskey'=>sesskey())), navigation_node::TYPE_SETTING);
        $disallowchoice = $mode->add(get_string('subscriptiondisabled', 'forum'), new moodle_url('/mod/socialforum/subscribe.php', array('id'=>$socialforum->id, 'mode'=>SF_DISALLOWSUBSCRIBE, 'sesskey'=>sesskey())), navigation_node::TYPE_SETTING);

        switch ($subscriptionmode) {
            case SF_CHOOSESUBSCRIBE : // 0
                $allowchoice->action = null;
                $allowchoice->add_class('activesetting');
                break;
            case SF_FORCESUBSCRIBE : // 1
                $forceforever->action = null;
                $forceforever->add_class('activesetting');
                break;
            case SF_INITIALSUBSCRIBE : // 2
                $forceinitially->action = null;
                $forceinitially->add_class('activesetting');
                break;
            case SF_DISALLOWSUBSCRIBE : // 3
                $disallowchoice->action = null;
                $disallowchoice->add_class('activesetting');
                break;
        }
    }
//    } else if ($activeenrolled) {
//
//        switch ($subscriptionmode) {
//            case FORUM_CHOOSESUBSCRIBE : // 0
//                $notenode = $socialforumnode->add(get_string('subscriptionoptional', 'forum'));
//                break;
//            case FORUM_FORCESUBSCRIBE : // 1
//                $notenode = $socialforumnode->add(get_string('subscriptionforced', 'forum'));
//                break;
//            case FORUM_INITIALSUBSCRIBE : // 2
//                $notenode = $socialforumnode->add(get_string('subscriptionauto', 'forum'));
//                break;
//            case FORUM_DISALLOWSUBSCRIBE : // 3
//                $notenode = $socialforumnode->add(get_string('subscriptiondisabled', 'forum'));
//                break;
//        }
//    }
    if($subjectid){
        $subscriptionmode = socialforum_get_subscribemode($socialforum);
        if($showlink && $subscriptionmode == SF_CHOOSESUBSCRIBE || $subscriptionmode == SF_INITIALSUBSCRIBE){
//    if ($cansubscribe) {
            if (socialforum_subject_is_subscribed($USER->id, $subjectid)) {
                $linktext = get_string('unsubscribe', 'socialforum');
            } else {
                $linktext = get_string('subscribe', 'socialforum');
            }
            $url = new moodle_url('/mod/socialforum/subscribe.php', array('subjectid'=>$subjectid, 'sesskey'=>sesskey()));
            $socialforumnode->add($linktext, $url, navigation_node::TYPE_SETTING);
//    }
        }
    }
}

///////////////////////
// DISPLAY FUNCTIONS //
///////////////////////

function display_socialforum($sf, $cm){
    global $OUTPUT, $DB, $CFG;

    $socialforum = $sf->get_instance();

    $heading = $OUTPUT->heading(format_string($socialforum->name), 2);
    echo $OUTPUT->add_encart_activity($heading);

    if (!empty($socialforum->intro)) {
        echo $OUTPUT->box(format_module_intro('socialforum', $socialforum, $cm->id), 'generalbox', 'intro');
    }

    echo socialforum_add_contribution($socialforum->id);
    echo '&nbsp;'; // this should fix the floating in FF

    $table = new html_table();
    $table->id = "social_forum_subjects_".$socialforum->id;
    $table->attributes['class'] = 'aard_section_forumheaderlist social-forum-subjects';
    $table->head = array(
        "Sujets",
        "Réponses",
        "Vote",
        "Dernier Message",
        "Favoris");
    $table->align = array('left','left','center','center','left','center');
    $table->headspan = array(2,1,1,1,1);
    foreach($sf->get_all_subjects() as $subject){
        $last_contribution = $sf->sort_all_contributions_by_date($subject->id,'DESC');
        $user = $DB->get_record('user', array('id'=>$subject->userid));
        if($last_contribution){
            $user_last_contribution = $DB->get_record('user', array('id'=>$last_contribution[0]->userid));
            $last_contribution = html_writer::link($CFG->wwwroot.'/user/view.php?id='.$user_last_contribution->id, html_writer::span($user_last_contribution->firstname." ".$user_last_contribution->lastname,'user'), array("class" => "author-field"))
            ."<br/>". html_writer::link(new moodle_url('view.php', array('ctid'=>$subject->id,'issubject'=>1)), userdate($last_contribution[0]->timecreated, "%a %d %b %Y, %H:%M"));
        } else {
            $last_contribution = "";
        }

        $row = new html_table_row();
        $row->attributes['class'] = 'discussion';

        $cell = new html_table_cell();
        $cell->attributes['class'] = 'picture';
        $cell->text = $OUTPUT->user_picture($user, array('courseid'=>$socialforum->course));
        $row->cells[] = $cell;

        $cell = new html_table_cell();
        $cell->attributes['class'] = 'author';
        $cell->text =
                html_writer::link(new moodle_url('view.php', array('ctid'=>$subject->id,'issubject'=>1)), $subject->subjecttitle, array("class" => "subject-title")) ."<br/>".
                html_writer::link($CFG->wwwroot.'/user/view.php?id='.$user->id, html_writer::span($user->firstname." ".$user->lastname,'user'), array("class" => "author-field")) ." &#x2022; ".
                userdate($subject->timecreated, "%d %b %Y");
        $row->cells[] = $cell;

        $cell = new html_table_cell();
        $cell->attributes['class'] = 'replies';
        $cell->text = html_writer::link(new moodle_url('view.php', array('ctid'=>$subject->id,'issubject'=>1)), count($sf->get_contributions_by_subject_id($subject->id)));
        $row->cells[] = $cell;

        $row->cells[] = html_writer::link(new moodle_url('view.php', array('ctid'=>$subject->id,'issubject'=>1)), count($sf->get_all_popularities_by_subject_id($subject->id)));
        $row->cells[] = $last_contribution;
        $row->cells[] = html_writer::link(new moodle_url('view.php', array('ctid'=>$subject->id,'issubject'=>1)), count($sf->get_all_favorites_by_subject_id($subject->id)));

        $table->data[] = $row;
    }


    return $table;

}

function display_main_action_subject($sf, $id){
    $socialforum = $sf->get_instance();
    $subject = $sf->get_subject_by_id($id);

    if (!$cm = get_coursemodule_from_instance("socialforum", $socialforum->id, $socialforum->course)) {
        print_error('invalidcoursemodule');
    }

    $content = display_filter_bar($subject->id);
    $buttons = socialforum_add_contribution($socialforum->id, $subject->id);
    //$content .= '&nbsp;'; // this should fix the floating in FF
    $content .= html_writer::div($buttons,'subject-buttons');
    return $content;
}


function display_filter_bar($id){
    $content = html_writer::select(array('chronology'=> "Chronologie",'popularity'=> "Popularité",'favorite'=> "Favoris"),false,'chronology',false, array('class'=> 'filter-select'));
    $content = html_writer::div("Afficher par : ".$content,'filter', array('id'=>$id));
    return $content;
}

function display_subject_detail($context, $sf, $id){
    global $OUTPUT, $DB, $USER, $CFG;

    $socialforum = $sf->get_instance();
    $subject = $sf->get_subject_by_id($id);

    if (!$cm = get_coursemodule_from_instance("socialforum", $socialforum->id, $socialforum->course)) {
        print_error('invalidcoursemodule');
    }

    $data = array();

    $user = $DB->get_record('user', array('id'=>$subject->userid));
    $header = html_writer::div($OUTPUT->user_picture($user, array('courseid'=>$socialforum->course)), 'picture');
    $heading = html_writer::div(format_string($subject->subjecttitle),'subject');
    $heading .= html_writer::div(
        html_writer::link($CFG->wwwroot.'/user/view.php?id='.$user->id, $user->firstname." ".$user->lastname, array("class" => "user"))." &#x2022; ".
        userdate($subject->timecreated, "%A %d %B %Y, %H:%M"),'author');
    $title_author = html_writer::div($heading,'subject-user');

    $links = html_writer::link(new moodle_url('post.php', array('post'=>$subject->id,'quote'=>1,'ispost'=>1)), get_string("quotecontribution", "socialforum"), array('class'=>'quote')) . " | ";
    $links .= display_popularity_button($sf, $subject->id);
    $links .= display_favorite_button($sf, $subject->id);
    $links = html_writer::div($links,'links');

    $content = html_writer::div($header.$title_author.$links,'header');

    $content .= display_contribution_message($subject, $cm);

    $footer = "";
    if ((has_capability('mod/socialforum:editcontribution', $context, $USER->id) && $subject->userid == $USER->id) || has_capability('mod/socialforum:editallcontributions', $context, $USER->id)) {
        $footer = html_writer::link(new moodle_url('post.php', array('post'=>$subject->id,'edit'=>1,'issubject'=>1)), get_string("modifycontribution", "socialforum"), array('class'=>'edit'));
        $footer .= html_writer::link(new moodle_url('post.php', array('post'=>$subject->id,'delete'=>1,'issubject'=>1)), get_string("deletesubject", "socialforum"), array('class'=>'delete'));
    }

    $content .= html_writer::div($footer,"footer");
    $row = new html_table_row(array($content));
    $row->id = $subject->id;
    $row->attributes['class'] = 'subject post';
    $data[] = $row;

    $table = new html_table();
    $table->id = "social_forum_subject_".$subject->id;
    $table->attributes['class'] = 'clearfix';
    $table->data = $data;
    return $table;
}

function display_subject_deferred_contributions($context, $sf, $id){
    global $OUTPUT, $DB, $USER, $CFG;

    $socialforum = $sf->get_instance();
    $subject = $sf->get_subject_by_id($id);

    if (!$cm = get_coursemodule_from_instance("socialforum", $socialforum->id, $socialforum->course)) {
        print_error('invalidcoursemodule');
    }

    $data = array();

    foreach($sf->get_deferred_contributions_by_subject_id($subject->id) as $contribution){
        $user = $DB->get_record('user', array('id'=>$contribution->userid));

        $content = html_writer::div('Cette contribution sera déposée le '.date("d/m/Y, H:i", $contribution->timepublished), 'date');
        $header = html_writer::div($OUTPUT->user_picture($user, array('courseid'=>$socialforum->course)), 'picture');
        $heading = html_writer::div(format_string($contribution->subjecttitle),'subject');
        $heading .= html_writer::div(
            html_writer::link($CFG->wwwroot.'/user/view.php?id='.$user->id, $user->firstname." ".$user->lastname, array("class" => "user"))." &#x2022; ".
            userdate($contribution->timecreated, "%A %d %B %Y, %H:%M"),'author');
        $title_author = html_writer::div($heading,'subject-user');

        $links = display_popularity_button($sf, $contribution->id);
        $links .= display_favorite_button($sf, $contribution->id);
        $links = html_writer::div($links,'links');

        $content .= html_writer::div($header.$title_author.$links,'header');

        $content .= display_contribution_message($contribution, $cm);

        $footer = "";
        if ((has_capability('mod/socialforum:viewdeferredcontributions', $context, $USER->id) && $contribution->userid == $USER->id) || has_capability('mod/socialforum:editallcontributions', $context, $USER->id)) {
            $footer = html_writer::link(new moodle_url('post.php', array('post'=>$contribution->id,'edit'=>1)), get_string("modifycontribution", "socialforum"), array('class'=>'edit'));
            $footer .= html_writer::link(new moodle_url('post.php', array('post'=>$contribution->id,'delete'=>1)), get_string("deletecontribution", "socialforum"), array('class'=>'delete'));
        }

        $content .= html_writer::div($footer,"footer");

        $row = new html_table_row(array($content));
        $row->id = $contribution->id;
        $row->attributes['class'] = 'deferred post';
        $data[] = $row;
    }
    $collapsible = html_writer::div(html_writer::tag('i','', array('class'=>'fa fa-sort-desc fa-rotate-270 fa-1x', 'aria-hidden'=>'true', 'collapsible'=>'false')),'collapsible-contributions');
    $table = new html_table();
    $table->id = "social_forum_subject_deferred_contributions_".$subject->id;
    $table->attributes['class'] = 'deferred-contributions-list';
    $table->head = array("Contributions à envoi différé" . $collapsible);
    $table->data = $data;
    return $table;
}

function display_subject_contributions($context, $sf, $id){
    global $OUTPUT, $DB, $USER, $CFG;

    $socialforum = $sf->get_instance();
    $subject = $sf->get_subject_by_id($id);

    if (!$cm = get_coursemodule_from_instance("socialforum", $socialforum->id, $socialforum->course)) {
        print_error('invalidcoursemodule');
    }

    $data = array();
    $bestcontribution = $sf->get_best_popularity_contribution($subject->id);
    foreach($sf->sort_all_contributions_by_date($subject->id) as $contribution){
        $isbestcontribution= 0;
        if($bestcontribution && $contribution->id == $bestcontribution->id) {
            $isbestcontribution = 1;
        }

        $user = $DB->get_record('user', array('id'=>$contribution->userid));
        $header = html_writer::div($OUTPUT->user_picture($user, array('courseid'=>$socialforum->course)), 'picture');
        $heading = html_writer::div(format_string($contribution->subjecttitle),'subject');
        $heading .= html_writer::div(
            html_writer::link($CFG->wwwroot.'/user/view.php?id='.$user->id, $user->firstname." ".$user->lastname, array("class" => "user"))." &#x2022; ".
            userdate($contribution->timecreated, "%A %d %B %Y, %H:%M"),'author');
        $title_author = html_writer::div($heading,'subject-user');

        $links = html_writer::link(new moodle_url('post.php', array('post'=>$contribution->id,'quote'=>1,'ispost'=>1)), get_string("quotecontribution", "socialforum"), array('class'=>'quote')) . " | ";
        $links .= display_popularity_button($sf, $contribution->id);
        $links .= display_favorite_button($sf, $contribution->id);
        $links = html_writer::div($links,'links');

        $content = html_writer::div($header.$title_author.$links,'header');

        $content .= display_contribution_message($contribution, $cm);

        $footer = "";
        if ((has_capability('mod/socialforum:editcontribution', $context, $USER->id) && $contribution->userid == $USER->id) || has_capability('mod/socialforum:editallcontributions', $context, $USER->id)) {
            $footer = html_writer::link(new moodle_url('post.php', array('post'=>$contribution->id,'edit'=>1)), get_string("modifycontribution", "socialforum"), array('class'=>'edit'));
            $footer .= html_writer::link(new moodle_url('post.php', array('post'=>$contribution->id,'delete'=>1)), get_string("deletecontribution", "socialforum"), array('class'=>'delete'));
        }

        $content .= html_writer::div($footer,"footer");
        $row = new html_table_row(array($content));
        $row->id = $contribution->id;
        $row->attributes['class'] = 'post';
        if($isbestcontribution) {
            $row->attributes['class'] = 'post best-popularity';
        }
        $data[] = $row;
    }

    $table = new html_table();
    $table->id = "social_forum_subject_contributions_".$subject->id;
    $table->attributes['class'] = 'contributions-list';
    $table->data = $data;
    return $table;
}

function display_subject_footer($sf, $id){
    $socialforum = $sf->get_instance();
    $subject = $sf->get_subject_by_id($id);

    $content = '&nbsp;'; // this should fix the floating in FF
    $content .= socialforum_add_contribution($socialforum->id, $subject->id);
    return $content;
}

function display_popularity_button($sf, $id){
    global $USER;

    $count_popularities = count($sf->get_popularities_by_contribution_id($id));
    if($sf->get_popularity_by_user_and_contribution($id, $USER->id)){
        $islike = 1;
        $class_icon = 'fa fa-thumbs-up contrib_action liked';
    } else {
        $islike = 0;
        $class_icon = 'fa fa-thumbs-o-up contrib_action';
    }

    $content = html_writer::tag('i','', array('class'=>$class_icon, 'aria-hidden'=>'true', 'liked'=>$islike, 'popularity'=>'1', 'ctid'=>$id)). html_writer::div($count_popularities,'count') . " | ";
    $content = html_writer::div($content,'popularity', array('id'=>'count_contrib_'.$id));
    return $content;
}

function display_favorite_button($sf, $id){
    global $USER;
    $isfavorite = 0;
    if($sf->get_favorite_by_user_and_contribution($id, $USER->id)){
        $class = 'fa fa-star contrib_action';
        $isfavorite = 1;
    } else {
        $class = 'far fa-star contrib_action';
    }
    return html_writer::div(html_writer::tag('i','', array('class'=>$class, 'aria-hidden'=>'true', 'favorited'=>$isfavorite, 'favorite'=>'1', 'ctid'=>$id)),'favorite');
}

function display_contribution_message($contribution, $cm){
    $modcontext  = context_module::instance($cm->id);

    $options = new stdClass;
    $options->para    = false;
    $options->context = $modcontext;

    // Prepare the attachements for the post, files then images
    list($attachments, $attachedimages) = socialforum_print_attachments($contribution, $cm, 'separateimages');

    $content = "";
    if (!empty($attachments)) {
        $content .= html_writer::tag('div', $attachments, array('class'=>'attachments'));
    }
    if (!empty($attachedimages)) {
        $content .= html_writer::tag('div', $attachedimages, array('class'=>'attachedimages'));
    }
    $contribution->message = file_rewrite_pluginfile_urls($contribution->message, 'pluginfile.php', $modcontext->id, 'mod_socialforum', 'message', $contribution->id);
    $content .= html_writer::div(format_text($contribution->message, $contribution->messageformat, $options),'message');
    return $content;
}

function display_subjects_list_block($sf){
    global $CFG;
    $rows = "";
    foreach($sf->get_all_subjects() as $subject){
        $row = html_writer::link(new moodle_url($CFG->wwwroot.'/mod/socialforum/view.php', array('ctid'=>$subject->id,'issubject'=>1)), $subject->subjecttitle, array('class'=>'subject-title'));
        $row .= html_writer::div(count($sf->get_all_popularities_by_subject_id($subject->id)).' '.html_writer::tag('i','',array('class'=>'fa fa-thumbs-o-up fa-2x')),'subject-likes');
        $row .= html_writer::div('(' .count($sf->get_contributions_by_subject_id($subject->id)). ' contribution(s))' ,'subject-contributions');
        $rows .= html_writer::div($row,'subject');
    }
    echo html_writer::div($rows,'subjects');
}

function javascript_for_subject_detail($sfid, $subid=null){
    global $CFG;

    $ajax_log_url = $CFG->wwwroot.'/mod/socialforum/js/ajax.php';
    return ' $( document ).ready(function() {
                    $(".fa-thumbs-o-up.contrib_action").click(managePopularity);
                    $(".fa-thumbs-up.contrib_action").click(managePopularity);
                    
                    $(".fa-star.contrib_action").click(manageFavorite);
                    $(".fa-star-o.contrib_action").click(manageFavorite);

                    $(".filter-select").change(sortTable);                  
                    
                    $(".collapsible-contributions i").click(function(){
                        if($(this).attr("collapsible") == "true"){
                            $(this).attr("collapsible","false");
                            $(this).attr("class","fa fa-sort-desc fa-rotate-270 fa-1x");
                            $(".deferred-contributions-list tbody").hide();
                        } else {
                            $(this).attr("collapsible","true");
                            $(this).attr("class","fa fa-sort-desc fa-1x");
                            $(".deferred-contributions-list tbody").show();
                        }
                    });
                    
                });
                function manageFavorite(){
                        var obj = $(this); 
                        $.ajax({
                            type: "POST",
                            url: "'.$ajax_log_url.'",
                            data:{ 
                                id: obj.attr("ctid"),
                                sfid: "'.$sfid.'",
                                favorited: obj.attr("favorited"),
                                favorite: obj.attr("favorite"),
                            },
                            datatype: "json",
                            success:function(response){
                                var json = JSON.parse(response);
                                obj.attr("favorited", json.favorited);
                                if(obj.attr("favorited") == "1"){
                                    obj.attr("class","fa fa-star contrib_action");
                                } else {
                                    obj.attr("class","far fa-star contrib_action");
                                }                               
                                                                
                            },
                            error:function(error){
                                console.log(error);
                            }
                        });
                }
                
                function managePopularity(){
                        var obj = $(this);
                        var like = obj.parent(".like");
                        var count = obj.parents().eq(1).find(".count");
                        $.ajax({
                            type: "POST",
                            url: "'.$ajax_log_url.'",
                            data:{ 
                                id: obj.attr("ctid"),
                                sfid: "'.$sfid.'",
                                liked: obj.attr("liked"),
                                popularity: obj.attr("popularity"),
                            },
                            datatype: "json",
                            success:function(response){
                                var json = JSON.parse(response);
                                obj.attr("liked", json.liked);
                                if(obj.attr("liked") == "1"){
                                    obj.attr("class","fa fa-thumbs-up contrib_action liked");
                                    like.attr("class","like liked");
                                    count.attr("class","count liked");
                                } else {
                                    obj.attr("class","fa fa-thumbs-o-up contrib_action");
                                    like.attr("class","like");
                                    count.attr("class","count");
                                }
                                
                                $("#count_contrib_"+json.ctid+" .count").text(json.count);
                                                                
                            },
                            error:function(error){
                                console.log(error);
                            }
                        });
                }
                
                function sortTable() {
                    var obj = $(this);
                    var filter = obj.val();
                    var sortup = true;
                    $.ajax({
                        type: "POST",
                        url: "'.$ajax_log_url.'",
                        data:{ 
                            id: "'.$subid.'",
                            sfid: "'.$sfid.'",
                            sortup: sortup,
                            filter: filter,
                            sort:"1"
                        },
                        datatype: "json",
                        success:function(response){
                            var json = JSON.parse(response);
                            if(json.ids){
                                var order = json.ids; 
                                var $table = $("#social_forum_subject_contributions_"+json.subid);
                            
                                for (var i = order.length; --i >= 0; ) {
                                    $table.prepend($table.find("#" + order[i]));
                                }
                            }                            
                        },
                        error:function(error){
                            console.log(error);
                        }
                    });
                }';
}


//////////////////////////
// ATTACHMENT FILE API //
/////////////////////////

/**
 * If successful, this function returns the name of the file
 *
 * @global object
 * @param object $post is a full post record, including course and forum
 * @param object $forum
 * @param object $cm
 * @param mixed $mform
 * @param string $unused
 * @return bool
 */
function socialforum_add_attachment($post, $forum, $cm, $mform=null) {
    global $DB;

    if (empty($mform)) {
        return false;
    }

    if (empty($post->attachments)) {
        return true;   // Nothing to do
    }

    $context = context_module::instance($cm->id);

    $info = file_get_draft_area_info($post->attachments);
    $present = ($info['filecount']>0) ? '1' : '';
    file_save_draft_area_files($post->attachments, $context->id, 'mod_socialforum', 'attachment', $post->id,
        mod_socialforum_post_form::attachment_options($forum));

    $DB->set_field('sf_contributions', 'attachment', $present, array('id'=>$post->id));

    return true;
}

/**
 * Returns attachments as formated text/html optionally with separate images
 *
 * @global object
 * @global object
 * @global object
 * @param object $post
 * @param object $cm
 * @param string $type html/text/separateimages
 * @return mixed string or array of (html text withouth images and image HTML)
 */
function socialforum_print_attachments($post, $cm, $type) {
    global $CFG, $DB, $USER, $OUTPUT;

    if (empty($post->attachment)) {
        return $type !== 'separateimages' ? '' : array('', '');
    }

    if (!in_array($type, array('separateimages', 'html', 'text'))) {
        return $type !== 'separateimages' ? '' : array('', '');
    }

    if (!$context = context_module::instance($cm->id)) {
        return $type !== 'separateimages' ? '' : array('', '');
    }
    $strattachment = get_string('attachment', 'forum');

    $fs = get_file_storage();

    $imagereturn = '';
    $output = '';

    $files = $fs->get_area_files($context->id, 'mod_socialforum', 'attachment', $post->id, "timemodified", false);
    if ($files) {
        foreach ($files as $file) {
            $filename = $file->get_filename();
            $mimetype = $file->get_mimetype();
            $iconimage = $OUTPUT->pix_icon(file_file_icon($file), get_mimetype_description($file), 'moodle', array('class' => 'icon'));
            $path = moodle_url::make_pluginfile_url($context->id,'mod_socialforum','attachment',$post->id,'/',$filename, false);

            if ($type == 'html') {
                $output .= "<a href=\"$path\">$iconimage</a> ";
                $output .= "<a href=\"$path\">".s($filename)."</a>";
                $output .= "<br />";

            } else if ($type == 'text') {
                $output .= "$strattachment ".s($filename).":\n$path\n";

            } else { //'returnimages'
                if (in_array($mimetype, array('image/gif', 'image/jpeg', 'image/png'))) {
                    // Image attachments don't get printed as links
                    $imagereturn .= "<img src=\"$path\" alt=\"\" /><br />";

                } else {
                    $output .= "<a href=\"$path\">$iconimage</a> ";
                    $output .= format_text("<a href=\"$path\">".s($filename)."</a>", FORMAT_HTML, array('context'=>$context));
                    $output .= '<br />';
                }
            }

            if (!empty($CFG->enableplagiarism)) {
                require_once($CFG->libdir.'/plagiarismlib.php');
                $output .= plagiarism_get_links(array('userid' => $post->userid,
                    'file' => $file,
                    'cmid' => $cm->id,
                    'course' => $cm->course,
                    'forum' => $cm->instance));
                $output .= '<br />';
            }
        }
    }

    if ($type !== 'separateimages') {
        return $output;

    } else {
        return array($output, $imagereturn);
    }
}

/**
 * Serves the forum attachments. Implements needed access control ;-)
 *
 * @package  mod_socialforum
 * @category files
 * @param stdClass $course course object
 * @param stdClass $cm course module object
 * @param stdClass $context context object
 * @param string $filearea file area
 * @param array $args extra arguments
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool false if file not found, does not return if found - justsend the file
 */
function socialforum_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
    global $DB;

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    require_course_login($course, true, $cm);

    // filearea must contain a real area
    if (!isset($filearea) || ($filearea != "attachment" && $filearea != "message")) {
        return false;
    }
    $postid = (int)array_shift($args);

    if (!$post = $DB->get_record('sf_contributions', array('id'=>$postid))) {
        return false;
    }

    if (!$forum = $DB->get_record('socialforum', array('id'=>$cm->instance))) {
        return false;
    }

    $fs = get_file_storage();
    $relativepath = implode('/', $args);
    $fullpath = "/$context->id/mod_socialforum/$filearea/$postid/$relativepath";
    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        return false;
    }

    // Make sure groups allow this user to see this file
//    if ($discussion->groupid > 0) {
//        $groupmode = groups_get_activity_groupmode($cm, $course);
//        if ($groupmode == SEPARATEGROUPS) {
//            if (!groups_is_member($discussion->groupid) and !has_capability('moodle/site:accessallgroups', $context)) {
//                return false;
//            }
//        }
//    }

    // Make sure we're allowed to see it...
//    if (!forum_user_can_see_post($forum, $discussion, $post, NULL, $cm)) {
//        return false;
//    }

    // finally send the file
    send_stored_file($file, 0, 0, false, $options); // download MUST be forced - security!
}

////////////////////
// CRON FUNCTIONS //
////////////////////

function socialforum_cron(){
    global $CFG, $USER, $DB;

    $site = get_site();

    // All users that are subscribed to any post that needs sending,
    // please increase $CFG->extramemorylimit on large sites that
    // send notifications to a large number of users.
    $users = array();
    $userscount = 0; // Cached user counter - count($users) in PHP is horribly slow!!!

    $mailcount  = 0;
    $errorcount = 0;

    // caches
    $contributions   = array();
    $socialforums    = array();
    $courses         = array();
    $coursemodules   = array();
    $subscribedusers = array();


    // Posts older than 2 days will not be mailed.  This is to avoid the problem where
    // cron has not been running for a long time, and then suddenly people are flooded
    // with mail from the past few weeks or months
    $timenow   = time();
    $endtime   = $timenow - $CFG->maxeditingtime;
    $starttime = $endtime - 48 * 3600;   // Two days earlier

    $contributions = socialforum_get_unmailed_contributions($starttime,$endtime);

    // checking post validity, and adding users to loop through later
    foreach ($contributions as $ctid => $contribution) {

        $socialforumid = $contribution->socialforum;
        if (!isset($socialforums[$socialforumid])) {
            if ($socialforum = $DB->get_record('socialforum', array('id' => $socialforumid))) {
                $socialforums[$socialforumid] = $socialforum;
            } else {
                mtrace('Could not find socialforum '.$socialforumid);
                unset($contributions[$ctid]);
                continue;
            }
        }
        $courseid = $socialforums[$socialforumid]->course;
        if (!isset($courses[$courseid])) {
            if ($course = $DB->get_record('course', array('id' => $courseid))) {
                $courses[$courseid] = $course;
            } else {
                mtrace('Could not find course '.$courseid);
                unset($contributions[$ctid]);
                continue;
            }
        }
        if (!isset($coursemodules[$socialforumid])) {
            if ($cm = get_coursemodule_from_instance('socialforum', $socialforumid, $courseid)) {
                $coursemodules[$socialforumid] = $cm;
            } else {
                mtrace('Could not find course module for socialforum '.$socialforumid);
                unset($contributions[$ctid]);
                continue;
            }
        }

        // caching subscribed users of each forum
        if (!isset($subscribedusers[$socialforumid][$contribution->subject])) {
            if ($subusers = socialforum_subject_subscribed_users($contribution->subject)) {
                foreach ($subusers as $subuser) {
                    // this user is subscribed to this forum
                    $subscribedusers[$socialforumid][$contribution->subject][$subuser->id] = $subuser->id;
                    $users[$subuser->id] = $subuser;
                }
                // Release memory.
                unset($subusers);
                unset($subuser);
            }
        }
    }

    if ($users && $contributions) {

        $urlinfo = parse_url($CFG->wwwroot);
        $hostname = $urlinfo['host'];

        foreach ($users as $userto) {

            core_php_time_limit::raise(120); // terminate if processing of any account takes longer than 2 minutes

            mtrace('Processing user ' . $userto->id);

            // set this so that the capabilities are cached, and environment matches receiving user
            cron_setup_user($userto);

            foreach ($contributions as $ctid => $contribution) {

                // Set up the environment for the socialforum, course
                $socialforum = $socialforums[$contribution->socialforum];
                $course = $courses[$socialforum->course];
                $cm =& $coursemodules[$socialforum->id];

                // Do some checks  to see if we can bail out now
                // Only active enrolled users are in the list of subscribers
                if (!isset($subscribedusers[$socialforum->id][$contribution->subject][$userto->id])) {
                    continue; // user does not subscribe to this forum
                }

                if (!$subject = $DB->get_record('sf_contributions', array('id' => $contribution->subject))){
                    mtrace('Could not find subject '.$contribution->subject);
                    continue;
                }

                if (!$userfrom = $DB->get_record('user', array('id' => $contribution->userid))){
                    mtrace('Could not find userfrom '.$contribution->userid);
                    continue;
                }

                // Prepare to actually send the post now, and build up the content

                $cleanforumname = str_replace('"', "'", strip_tags(format_string($socialforum->name)));

                $userfrom->customheaders = array (  // Headers to make emails easier to track
                    'Precedence: Bulk',
                    'List-Id: "'.$cleanforumname.'" <moodlesocialforum'.$socialforum->id.'@'.$hostname.'>',
                    'List-Help: '.$CFG->wwwroot.'/mod/socialforum/view.php?id='.$cm->id,
                    'Message-ID: '.socialforum_get_email_message_id($contribution->id, $userto->id, $hostname),
                    'X-Course-Id: '.$course->id,
                    'X-Course-Name: '.format_string($course->fullname, true)
                );

                if ($subject) {  // This post is a reply, so add headers for threading (see MDL-22551)
                    $userfrom->customheaders[] = 'In-Reply-To: '.socialforum_get_email_message_id($subject->id, $userto->id, $hostname);
                    $userfrom->customheaders[] = 'References: '.socialforum_get_email_message_id($subject->id, $userto->id, $hostname);
                }

                $shortname = format_string($course->shortname, true, array('context' => context_course::instance($course->id)));

                $a = new stdClass();
                $a->courseshortname = $shortname;
                $a->forumname = $cleanforumname;
                $a->subject = format_string($subject->subjecttitle, true);
                $contributionsubject = html_to_text(get_string('postmailsubject', 'forum', $a), 0);
                $contributiontext = socialforum_make_mail_text($socialforum, $cm, $subject, $contribution, $userfrom, $userto);
                $contributionhtml = socialforum_make_mail_html($socialforum, $course, $cm, $subject, $contribution, $userfrom, $userto);

                // Send the post now!

                mtrace('Sending ', '');

                $attachment = $attachname='';
                $mailresult = email_to_user($userto, $site->shortname, $contributionsubject, $contributiontext, $contributionhtml, $attachment, $attachname);
                if (!$mailresult){
                    mtrace("Error: mod/socialforum/lib.php socialforum_cron(): Could not send out mail for id $contribution->id to user $userto->id".
                        " ($userto->email) .. not trying again.");
                    $errorcount++;
                } else {
                    $mailcount++;

                    $update_contribution = new stdClass();
                    $update_contribution->id = $contribution->id;
                    $update_contribution->mailed = 1;
                    $update_contribution->timemailed = time();
                    $DB->update_record('sf_contributions', $update_contribution);
                }

                mtrace('contribution '.$contribution->id. ': '.$subject->subjecttitle);
            }
        }
    }
    mtrace('mail count : '.$mailcount);
    mtrace('error count : '.$errorcount);
    // release some memory
    unset($subscribedusers);
    unset($mailcount);
    unset($errorcount);

    return true;
}

/**
 * Returns a list of all new contributions that have not been mailed yet
 *
 * @param int $starttime posts created after this time
 * @param int $endtime posts created before this
 * @return array
 */
function socialforum_get_unmailed_contributions($starttime, $endtime) {
    global $DB;

    $params = array();
    $params['mailed'] = 0;
    $params['issubject'] = 0;
    $params['timestart'] = $starttime;
    $params['timeend'] = $endtime;

    return $DB->get_records_sql("SELECT *
                                 FROM {sf_contributions} 
                                 WHERE mailed = :mailed
                                 AND issubject = :issubject
                                 AND timepublished >= :timestart
                                 AND timepublished < :timeend
                                 ORDER BY timemodified ASC", $params);
}

/**
 * Returns list of user objects that are subscribed to this subject
 *
 * @param int $subject id of the subject
 * @return array
 */
function socialforum_subject_subscribed_users($subjectid) {
    global $DB;

    $params = array();
    $params['subject'] = $subjectid;

    return $DB->get_records_sql("SELECT * 
                                    FROM {user} 
                                    WHERE id IN (   SELECT userid
                                                        FROM {sf_subscriptions} 
                                                        WHERE subject = :subject
                                                        ORDER BY timecreated ASC 
                                                 )", $params);
}

/**
 * Create a message-id string to use in the custom headers of forum notification emails
 *
 * message-id is used by email clients to identify emails and to nest conversations
 *
 * @param int $contributionid The ID of the forum post we are notifying the user about
 * @param int $usertoid The ID of the user being notified
 * @param string $hostname The server's hostname
 * @return string A unique message-id
 */
function socialforum_get_email_message_id($contributionid, $usertoid, $hostname) {
    return '<'.hash('sha256',$contributionid.'to'.$usertoid).'@'.$hostname.'>';
}

/**
 * Builds and returns the body of the email notification in plain text.
 *
 * @global object
 * @global object
 * @uses CONTEXT_MODULE
 * @param object $socialforum
 * @param object $cm
 * @param object $subject
 * @param object $contribution
 * @return string The email body in plain text format.
 */
function socialforum_make_mail_text($socialforum, $cm, $subject, $contribution, $userfrom, $userto){
    global $CFG;
    $modcontext = context_module::instance($cm->id);

    $by = new stdClass;
    $by->name = fullname($userfrom, true);
    $by->date = userdate($contribution->timepublished, "", $userto->timezone);

    $strbynameondate = get_string('bynameondate', 'forum', $by);

    // add absolute file links
    $contribution->message = file_rewrite_pluginfile_urls($contribution->message, 'pluginfile.php', $modcontext->id, 'mod_socialforum', 'message', $contribution->id);

    $posttext = "\n---------------------------------------------------------------------\n";
    $posttext .= format_string($subject->subjecttitle,true);
    $posttext .= " ($CFG->wwwroot/mod/socialforum/view.php?ctid=$subject->id&issubject=1)";
    $posttext .= "\n".$strbynameondate."\n";
    $posttext .= "---------------------------------------------------------------------\n";
    $posttext .= format_text_email($contribution->message, $contribution->messageformat);
    $posttext .= "\n\n";
    $posttext .= socialforum_print_attachments($contribution, $cm, "text");
    if($socialforum->modesubscribe == SF_CHOOSESUBSCRIBE || $socialforum->modesubscribe == SF_INITIALSUBSCRIBE){
        $posttext .= "\n---------------------------------------------------------------------\n";
        $posttext .= get_string("unsubscribe", "socialforum");
        $posttext .= ": $CFG->wwwroot/mod/socialforum/subscribe.php?subjectid=$subject->id\n";
    }
    return $posttext;
}

/**
 * Builds and returns the body of the email notification in html format.
 *
 * @global object
 * @param object $socialforum
 * @param object $course
 * @param object $cm
 * @param object $forum
 * @param object $discussion
 * @param object $post
 * @param object $userfrom
 * @param object $userto
 * @return string The email text in HTML format
 */
function socialforum_make_mail_html($socialforum, $course, $cm, $subject, $contribution, $userfrom, $userto) {
    global $CFG;
    $shortname = format_string($course->shortname, true, array('context' => context_course::instance($course->id)));

    $posthtml = '<head>';
    $posthtml .= '</head>';
    $posthtml .= "\n<body id=\"email\">\n\n";

    $posthtml .= '<div class="navbar">'.
        '<a target="_blank" href="'.$CFG->wwwroot.'/course/view.php?id='.$course->id.'">'.$shortname.'</a> &raquo; '.
        '<a target="_blank" href="'.$CFG->wwwroot.'/mod/socialforum/view.php?id='.$cm->id.'">'. get_string('seeothersubjects', 'socialforum') .'</a> &raquo; '.
        '<a target="_blank" href="'.$CFG->wwwroot.'/mod/socialforum/view.php?ctid='.$subject->id.'&issubject=1">'.format_string($subject->subjecttitle,true).'</a>';
    $posthtml .= '</div>';
    $posthtml .= socialforum_make_mail_contribution($course, $cm, $subject, $contribution, $userfrom, $userto);
    if($socialforum->modesubscribe == SF_CHOOSESUBSCRIBE || $socialforum->modesubscribe == SF_INITIALSUBSCRIBE) {
        $footerlink = '<a href="' . $CFG->wwwroot . '/mod/socialforum/subscribe.php?subjectid=' . $subject->id . '">' . get_string('unsubscribe', 'socialforum') . '</a>';
        $posthtml .= '<hr /><div class="mdl-align unsubscribelink">' . $footerlink . '</div>';
    }
    $posthtml .= '</body>';

    return $posthtml;
}

/**
 * Given the data about a posting, builds up the HTML to display it and
 * returns the HTML in a string.  This is designed for sending via HTML email.
 *
 * @global object
 * @param object $course
 * @param object $cm
 * @param object $subject
 * @param object $contribution
 * @param object $userform
 * @param object $userto
 * @return string
 */
function socialforum_make_mail_contribution($course, $cm, $subject, $contribution, $userfrom, $userto){
    global $CFG, $OUTPUT;

    $modcontext = context_module::instance($cm->id);

    // add absolute file links
    $contribution->message = file_rewrite_pluginfile_urls($contribution->message, 'pluginfile.php', $modcontext->id, 'mod_socialforum', 'message', $contribution->id);

    // format the post body
    $options = new stdClass();
    $options->para = true;
    $formattedtext = format_text($contribution->message, $contribution->messageformat, $options, $course->id);

    $output = '<table border="0" cellpadding="3" cellspacing="0" class="forumpost">';

    $output .= '<tr class="header"><td width="35" valign="top" class="picture left">';
    $output .= $OUTPUT->user_picture($userfrom, array('courseid'=>$course->id));
    $output .= '</td>';

    $output .= '<td class="topic starter">';
    $output .= '<div class="subject">'.format_string($subject->subjecttitle).'</div>';

    $fullname = fullname($userfrom, true);
    $by = new stdClass();
    $by->name = '<a href="'.$CFG->wwwroot.'/user/view.php?id='.$userfrom->id.'&amp;course='.$course->id.'">'.$fullname.'</a>';
    $by->date = userdate($contribution->timepublished, '', $userto->timezone);
    $output .= '<div class="author">'.get_string('bynameondate', 'forum', $by).'</div>';

    $output .= '</td></tr>';

    $output .= '<tr><td class="left side" valign="top">';
    $output .= '&nbsp;';
    $output .= '</td><td class="content">';

    $attachments = socialforum_print_attachments($contribution, $cm, 'html');
    if ($attachments !== '') {
        $output .= '<div class="attachments">';
        $output .= $attachments;
        $output .= '</div>';
    }
    $output .= $formattedtext;
    $output .= '</td></tr></table>'."\n\n";

    return $output;
}