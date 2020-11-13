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
 * Moodle frontpage.
 *
 * @package    core
 * @copyright  1999 onwards Martin Dougiamas (http://dougiamas.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!file_exists('./config.php')) {
    header('Location: install.php');
    die;
}

require_once('config.php');

// Short links and special redirections
if (isset($_GET['q']) && strpos($_GET['q'],'/') === 0) {
    $uri = $_GET['q'];
    
    // Start Shortlinks for magistere_offers
    $matches = [];
    $match = preg_match('/\/([f|p])([0-9]*)(|[l])$/', $uri, $matches);
    if ($match) {
        if ($matches[1] == 'f') {
            $url = new moodle_url('/local/magistere_offers/index.php',['v'=>'formation'],'offer='.$matches[2]);
        } else {
            $url = new moodle_url('/local/magistere_offers/index.php',['v'=>'course'],'offer='.$matches[2]);
        }
        redirect($url);
    }
    // End Shortlinks for magistere_offers
    
    // Default 404 page
    echo 'File not found';
    http_response_code(404);
    die;
}

require_once($CFG->dirroot .'/course/lib.php');
require_once($CFG->libdir .'/filelib.php');
require_once($CFG->dirroot.'/user/editlib.php');
require_once($CFG->dirroot .'/local/magisterelib/frontalFeatures.php');



// BEGIN VSE 850

// On se connecte via la federation si on a le cookie shibboleth en session
if (!isloggedin() && ((isset($_SERVER['HTTP_SHIB_APPLICATION_ID']) && !empty($_SERVER['HTTP_SHIB_APPLICATION_ID']))
        || (isset($_SERVER['Shib-Application-ID']) && !empty($_SERVER['Shib-Application-ID']))
        || (isset($_SERVER['HTTP_COOKIE']) && preg_match('/_shibsession_/i', $_SERVER['HTTP_COOKIE'])))){
    // On redirige l'utilisateur vers le script de login shibboleth
    redirect($CFG->wwwroot.'/auth/shibboleth/index.php');
}

if (!isloggedin())
{
    if (($sb_hub_mm = mmcached_get('mmid_session_'.get_mmid().'_authsbhub')) !== false)
    {
        if ($sb_hub_mm == 'sco')
        {
            redirect($CFG->shibboleth_hub_url_sco);
        }
        else if ($sb_hub_mm == 'sup')
        {
            redirect($CFG->shibboleth_hub_url_sup);
        }
    }
}else{
    //BEGIN VSE (old NNE)
    if($USER->auth == 'shibboleth' && !isfrontal()){
        //Load data from the frontal
        require_once($CFG->dirroot.'/local/magisterelib/userProfileSynchronisation.php');
        $userProfileSynchronisation = new UserProfileSynchronisation();
        $userProfileSynchronisation->updateLocalUser();
    }
    //END VSE (old NNE)
}

// Reset de l'academie principale
$raca = optional_param('raca', 'none', PARAM_ALPHA);
if (isloggedin() && isset($raca) && $raca != 'none' && $raca == 'o')
{
    user_set_mainacademy('');
    if ( user_set_frontal_mainacademy('') )
    {
        die('success');
    }
    die('fail');
}

// END VSE 850

redirect_if_major_upgrade_required();

$urlparams = array();
if (!empty($CFG->defaulthomepage) && ($CFG->defaulthomepage == HOMEPAGE_MY)
    && optional_param('redirect', 1, PARAM_BOOL) === 0) {
    $urlparams['redirect'] = 0;
}
$PAGE->set_url('/', $urlparams);
$PAGE->set_pagelayout('frontpage');
$PAGE->set_other_editing_capability('moodle/course:update');
$PAGE->set_other_editing_capability('moodle/course:manageactivities');
$PAGE->set_other_editing_capability('moodle/course:activityvisibility');

$PAGE->requires->jquery_plugin('ui-css');

// Prevent caching of this page to stop confusion when changing page after making AJAX changes.
$PAGE->set_cacheable(false);

require_course_login($SITE);

$hasmaintenanceaccess = has_capability('moodle/site:maintenanceaccess', context_system::instance());

// If the site is currently under maintenance, then print a message.
if (!empty($CFG->maintenance_enabled) and !$hasmaintenanceaccess) {
    print_maintenance_message();
}

$hassiteconfig = has_capability('moodle/site:config', context_system::instance());

if ($hassiteconfig && moodle_needs_upgrading()) {
    redirect($CFG->wwwroot .'/'. $CFG->admin .'/index.php');
}

// If site registration needs updating, redirect.
//\core\hub\registration::registration_reminder('/index.php');

if (get_home_page() != HOMEPAGE_SITE) {
    // Redirect logged-in users to My Moodle overview if required.
    $redirect = optional_param('redirect', 1, PARAM_BOOL);
    if (optional_param('setdefaulthome', false, PARAM_BOOL)) {
        set_user_preference('user_home_page_preference', HOMEPAGE_SITE);
    } else if (!empty($CFG->defaulthomepage) && ($CFG->defaulthomepage == HOMEPAGE_MY) && $redirect === 1) {
        redirect($CFG->wwwroot .'/my/');
    } else if (!empty($CFG->defaulthomepage) && ($CFG->defaulthomepage == HOMEPAGE_USER)) {
        $frontpagenode = $PAGE->settingsnav->find('frontpage', null);
        if ($frontpagenode) {
            $frontpagenode->add(
                get_string('makethismyhome'),
                new moodle_url('/', array('setdefaulthome' => true)),
                navigation_node::TYPE_SETTING);
        } else {
            $frontpagenode = $PAGE->settingsnav->add(get_string('frontpagesettings'), null, navigation_node::TYPE_SETTING, null);
            $frontpagenode->force_open();
            $frontpagenode->add(get_string('makethismyhome'),
                new moodle_url('/', array('setdefaulthome' => true)),
                navigation_node::TYPE_SETTING);
        }
    }
}

// Trigger event.
course_view(context_course::instance(SITEID));

// If the hub plugin is installed then we let it take over the homepage here.
if (file_exists($CFG->dirroot.'/local/hub/lib.php') and get_config('local_hub', 'hubenabled')) {
    require_once($CFG->dirroot.'/local/hub/lib.php');
    $hub = new local_hub();
    $continue = $hub->display_homepage();
    // Function display_homepage() returns true if the hub home page is not displayed
    // ...mostly when search form is not displayed for not logged users.
    if (empty($continue)) {
        exit;
    }
}

if (isloggedin())
{
    $PAGE->set_pagetype('site-index');
    $PAGE->set_docs_path('');
    $editing = $PAGE->user_is_editing();
    $PAGE->set_title($SITE->fullname);
    $PAGE->set_heading($SITE->fullname);
    $courserenderer = $PAGE->get_renderer('core', 'course');

}
else
{
    $PAGE->set_pagelayout('frontal_notconnected');
    $PAGE->set_pagetype('site-noconnected');
    $PAGE->set_docs_path('');
    $editing = $PAGE->user_is_editing();
    $PAGE->set_title($SITE->fullname);
    $PAGE->set_heading($SITE->fullname);
    //$courserenderer = $PAGE->get_renderer('core', 'course');

}

FrontalFeatures::set_page_requirements();


if (!isloggedin()) {
    echo $OUTPUT->header();
    echo FrontalFeatures::get_frontal_connection_page();

    echo $OUTPUT->footer();
    exit;
}
else if (isfrontal())
{

    require_once($CFG->dirroot.'/local/magisterelib/UserProfileUpdateForm.php');
    require_once($CFG->dirroot.'/local/magisterelib/UserSetMainAcademyForm.php');

    echo $OUTPUT->header();
    //echo FrontalFeatures::get_frontal_connection_page();
    $main_aca_form = new UserSetMainAcademyForm();
    echo FrontalFeatures::get_main_aca_code($main_aca_form);


    //BEGIN NNE 850
    $profile_form = new UserProfileUpdateForm();

    if($profile_form->is_submitted()){
        $data = $profile_form->get_submitted_data();

        if($data->validate){
            //POPUP DE REDIRECTION

            echo '<div id="redirect_waiting_popup">
				<p>Redirection en cours...</p>
			</div>
			<script>
				$("#redirect_waiting_popup").dialog({
					modal: true,
					resizable: false,
					draggable: false,
					width: "600px",
					dialogClass: "popup_frontal",
					closeOnEscape: false,
					open: function(event, ui) { $(".ui-dialog-titlebar-close", ui.dialog | ui).hide(); }
				});
			</script>';

            $userrecord = $DB->get_record('user', array('id' => $data->id));
            $userrecord->firstname = $data->firstname;
            $userrecord->lastname = $data->lastname;
            $DB->update_record('user', $userrecord);

            $USER->firstname = $userrecord->firstname;
            $USER->lastname = $userrecord->lastname;

            useredit_update_picture($data, $profile_form);

            if(($mainaca = user_get_mainacademy($USER->id)) !== false){

                //Redirection si une url est presente
                if (($mm_sb_redirection = mmcached_get('mmid_session_'.get_mmid().'_hub_redirection')) !== false)
                {
                    error_log('Redirection memcached : '.$mm_sb_redirection);
                    // On supprime la redirection pour eviter que la redirection se repete
                    mmcached_delete('mmid_session_'.get_mmid().'_hub_redirection');

                    redirect($mm_sb_redirection);
                }else{
                    if (!isfrontal())
                    {
                        $magistere_academy = get_magistere_academy_config();
                        $url = new moodle_url($CFG->magistere_domaine.$magistere_academy[$mainaca]['accessdir'].'/');
                    }else{
                        $url = new moodle_url($CFG->wwwroot);
                    }
                    redirect($url, "", 0);
                }
            }else{
                //On retourne sur la page pour que l'utilisateur modifie son academie
                redirect($CFG->magistere_domaine);
            }
        }
    }else if(empty($USER->firstname) || empty($USER->lastname)){
        echo '<div id="fill_firstname_lastname" title="Merci de compl&eacute;ter votre profil">';
        $data = new stdClass();
        $data->lastname = $USER->lastname;
        $data->firstname = $USER->firstname;
        $data->id = $USER->id;

        $profile_form->set_data($data);
        $profile_form->display();
        echo '</div>';

        $PAGE->requires->js_call_amd("local_magisterelib/frontal_fillFirstnameLastnameDialogs", "init");

    }else if($main_aca_form->is_submitted()){
        $data = $main_aca_form->get_submitted_data();

        if($data->validate){
            user_set_mainacademy($data->main_academy, $USER->id);

            echo '<div id="redirect_waiting_popup">
				<p>Redirection en cours...</p>
			</div>';
            
            $PAGE->requires->js_call_amd("local_magisterelib/frontal_redirectWaitingDialog", "init");

            //Redirection si deeplink present
            if (($mm_sb_redirection = mmcached_get('mmid_session_'.get_mmid().'_hub_redirection')) !== false)
            {
                error_log('Redirection memcached : '.$mm_sb_redirection);
                // On supprime la redirection pour eviter que la redirection se repete
                mmcached_delete('mmid_session_'.get_mmid().'_hub_redirection');
                redirect($mm_sb_redirection);
            }else{
                //Redirection vers l'academie de rattachement
                $magistere_academy = get_magistere_academy_config();
                $url = $CFG->magistere_domaine.$magistere_academy[$data->main_academy]['accessdir'].'/';
                redirect($url);
            }
        }
    }else if($USER->auth == "shibboleth" && (user_get_mainacademy($USER->id) === false || user_get_mainacademy($USER->id) == '')){
        
        $PAGE->requires->js_call_amd("local_magisterelib/frontal_mainacaDialog", "init");

    }else if($USER->auth == "shibboleth" && user_get_mainacademy($USER->id)!=''){
        $magistere_academy = get_magistere_academy_config();
        $url = $CFG->magistere_domaine.$magistere_academy[user_get_mainacademy($USER->id)]['accessdir'].'/';
        redirect($url);
    }

    echo $OUTPUT->footer();
    exit;
}
echo $OUTPUT->header();

// Print Section or custom info.
$siteformatoptions = course_get_format($SITE)->get_format_options();
$modinfo = get_fast_modinfo($SITE);
$modnames = get_module_types_names();
$modnamesplural = get_module_types_names(true);
$modnamesused = $modinfo->get_used_module_names();
$mods = $modinfo->get_cms();

if (!empty($CFG->customfrontpageinclude)) {
    include($CFG->customfrontpageinclude);

} else if ($siteformatoptions['numsections'] > 0) {
    if ($editing) {
        // Make sure section with number 1 exists.
        course_create_sections_if_missing($SITE, 1);
        // Re-request modinfo in case section was created.
        $modinfo = get_fast_modinfo($SITE);
    }
    $section = $modinfo->get_section_info(1);
    if (($section && (!empty($modinfo->sections[1]) or !empty($section->summary))) or $editing) {
        echo $OUTPUT->box_start('generalbox sitetopic');

        // If currently moving a file then show the current clipboard.
        if (ismoving($SITE->id)) {
            $stractivityclipboard = strip_tags(get_string('activityclipboard', '', $USER->activitycopyname));
            echo '<p><font size="2">';
            echo "$stractivityclipboard&nbsp;&nbsp;(<a href=\"course/mod.php?cancelcopy=true&amp;sesskey=".sesskey()."\">";
            echo get_string('cancel') . '</a>)';
            echo '</font></p>';
        }

        $context = context_course::instance(SITEID);

        // If the section name is set we show it.
        if (trim($section->name) !== '') {
            echo $OUTPUT->heading(
                format_string($section->name, true, array('context' => $context)),
                2,
                'sectionname'
            );
        }

        $summarytext = file_rewrite_pluginfile_urls($section->summary,
            'pluginfile.php',
            $context->id,
            'course',
            'section',
            $section->id);
        $summaryformatoptions = new stdClass();
        $summaryformatoptions->noclean = true;
        $summaryformatoptions->overflowdiv = true;

        echo format_text($summarytext, $section->summaryformat, $summaryformatoptions);

        if ($editing && has_capability('moodle/course:update', $context)) {
            $streditsummary = get_string('editsummary');
            echo "<a title=\"$streditsummary\" " .
                " href=\"course/editsection.php?id=$section->id\">" . $OUTPUT->pix_icon('t/edit', $streditsummary) .
                "</a><br /><br />";
        }

        $courserenderer = $PAGE->get_renderer('core', 'course');
        echo $courserenderer->course_section_cm_list($SITE, $section);

        echo $courserenderer->course_section_add_cm_control($SITE, $section->section);
        echo $OUTPUT->box_end();
    }
}
// Include course AJAX.
include_course_ajax($SITE, $modnamesused);

if (isloggedin() and !isguestuser() and isset($CFG->frontpageloggedin)) {
    $frontpagelayout = $CFG->frontpageloggedin;
} else {
    $frontpagelayout = $CFG->frontpage;
}

$frontpageoptions = explode(',', $frontpagelayout);
foreach ($frontpageoptions as $v) {
    switch ($v) {
        // Display the main part of the front page.
        case FRONTPAGENEWS:
            if ($SITE->newsitems) {
                // Print forums only when needed.
                require_once($CFG->dirroot .'/mod/forum/lib.php');

                if (! $newsforum = forum_get_course_forum($SITE->id, 'news')) {
                    print_error('cannotfindorcreateforum', 'forum');
                }

                // Fetch news forum context for proper filtering to happen.
                $newsforumcm = get_coursemodule_from_instance('forum', $newsforum->id, $SITE->id, false, MUST_EXIST);
                $newsforumcontext = context_module::instance($newsforumcm->id, MUST_EXIST);

                $forumname = format_string($newsforum->name, true, array('context' => $newsforumcontext));
                echo html_writer::link('#skipsitenews',
                    get_string('skipa', 'access', core_text::strtolower(strip_tags($forumname))),
                    array('class' => 'skip-block skip'));

                // Wraps site news forum in div container.
                echo html_writer::start_tag('div', array('id' => 'site-news-forum'));

                if (isloggedin()) {
                    $SESSION->fromdiscussion = $CFG->wwwroot;
                    $subtext = '';
                    if (\mod_forum\subscriptions::is_subscribed($USER->id, $newsforum)) {
                        if (!\mod_forum\subscriptions::is_forcesubscribed($newsforum)) {
                            $subtext = get_string('unsubscribe', 'forum');
                        }
                    } else {
                        $subtext = get_string('subscribe', 'forum');
                    }
                    echo $OUTPUT->heading($forumname);
                    $suburl = new moodle_url('/mod/forum/subscribe.php', array('id' => $newsforum->id, 'sesskey' => sesskey()));
                    echo html_writer::tag('div', html_writer::link($suburl, $subtext), array('class' => 'subscribelink'));
                } else {
                    echo $OUTPUT->heading($forumname);
                }

                forum_print_latest_discussions($SITE, $newsforum, $SITE->newsitems, 'plain', 'p.modified DESC');

                // End site news forum div container.
                echo html_writer::end_tag('div');

                echo html_writer::tag('span', '', array('class' => 'skip-block-to', 'id' => 'skipsitenews'));
            }
            break;

        case FRONTPAGEENROLLEDCOURSELIST:
            $mycourseshtml = $courserenderer->frontpage_my_courses();
            if (!empty($mycourseshtml)) {
                echo html_writer::link('#skipmycourses',
                    get_string('skipa', 'access', core_text::strtolower(get_string('mycourses'))),
                    array('class' => 'skip skip-block'));

                // Wrap frontpage course list in div container.
                echo html_writer::start_tag('div', array('id' => 'frontpage-course-list'));

                echo $OUTPUT->heading(get_string('mycourses'));
                echo $mycourseshtml;

                // End frontpage course list div container.
                echo html_writer::end_tag('div');

                echo html_writer::tag('span', '', array('class' => 'skip-block-to', 'id' => 'skipmycourses'));
                break;
            } else {
                // Temp fix/fallback in order to display available courses when enrolled courses should be shown,
                // but user is not enrolled in any course.
                if (array_search(FRONTPAGEALLCOURSELIST, $frontpageoptions)) {
                    break;
                }
            }

        case FRONTPAGEALLCOURSELIST:
            $availablecourseshtml = $courserenderer->frontpage_available_courses();
            if (!empty($availablecourseshtml)) {
                echo html_writer::link('#skipavailablecourses',
                    get_string('skipa', 'access', core_text::strtolower(get_string('availablecourses'))),
                    array('class' => 'skip skip-block'));

                // Wrap frontpage course list in div container.
                echo html_writer::start_tag('div', array('id' => 'frontpage-course-list'));

                echo $OUTPUT->heading(get_string('availablecourses'));
                echo $availablecourseshtml;

                // End frontpage course list div container.
                echo html_writer::end_tag('div');

                echo html_writer::tag('span', '', array('class' => 'skip-block-to', 'id' => 'skipavailablecourses'));
            }
            break;

        case FRONTPAGECATEGORYNAMES:
            echo html_writer::link('#skipcategories',
                get_string('skipa', 'access', core_text::strtolower(get_string('categories'))),
                array('class' => 'skip skip-block'));

            // Wrap frontpage category names in div container.
            echo html_writer::start_tag('div', array('id' => 'frontpage-category-names'));

            echo $OUTPUT->heading(get_string('categories'));
            echo $courserenderer->frontpage_categories_list();

            // End frontpage category names div container.
            echo html_writer::end_tag('div');

            echo html_writer::tag('span', '', array('class' => 'skip-block-to', 'id' => 'skipcategories'));
            break;

        case FRONTPAGECATEGORYCOMBO:
            echo html_writer::link('#skipcourses',
                get_string('skipa', 'access', core_text::strtolower(get_string('courses'))),
                array('class' => 'skip skip-block'));

            // Wrap frontpage category combo in div container.
            echo html_writer::start_tag('div', array('id' => 'frontpage-category-combo'));

            echo $OUTPUT->heading(get_string('courses'));
            echo $courserenderer->frontpage_combo_list();

            // End frontpage category combo div container.
            echo html_writer::end_tag('div');

            echo html_writer::tag('span', '', array('class' => 'skip-block-to', 'id' => 'skipcourses'));
            break;

        case FRONTPAGECOURSESEARCH:
            echo $OUTPUT->box($courserenderer->course_search_form('', 'short'), 'mdl-align');
            break;

    }
    echo '<br />';
}
if ($editing && has_capability('moodle/course:create', context_system::instance())) {
    echo $courserenderer->add_new_course_button();
}
echo $OUTPUT->footer();
