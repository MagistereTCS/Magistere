<?php
/**
 * Aardvark theme for Moodle - Material-inspired theme based on bootstrap.
 *
 * DO NOT MODIFY THIS THEME!
 * COPY IT FIRST, THEN RENAME THE COPY AND MODIFY IT INSTEAD.
 *
 * For full information about creating Moodle themes, see:
 * http://docs.moodle.org/dev/Themes_2.0
 *
 * @package   theme_magistere
 * @author    Shaun Daubney
 * @copyright 2017 Newbury College
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot . '/theme/bootstrapbase/renderers.php');
class theme_magistere_core_renderer extends theme_bootstrapbase_core_renderer {

    /**
     * Either returns the parent version of the header bar, or a version with the logo replacing the header.
     *
     * @since Moodle 2.9
     * @param array $headerinfo An array of header information, dependant on what type of header is being displayed. The following
     *                          array example is user specific.
     *                          heading => Override the page heading.
     *                          user => User object.
     *                          usercontext => user context.
     * @param int $headinglevel What level the 'h' tag will be.
     * @return string HTML for the header bar.
     */
    public function context_header($headerinfo = null, $headinglevel = 1) {

        if ($this->should_render_logo($headinglevel)) {
            return html_writer::tag('div', '', array('class' => 'logo'));
        }
        return parent::context_header($headerinfo, $headinglevel);
    }

    /**
     * Determines if we should render the logo.
     *
     * @param int $headinglevel What level the 'h' tag will be.
     * @return bool Should the logo be rendered.
     */
    protected function should_render_logo($headinglevel = 1) {
        global $PAGE;

        // Only render the logo if we're on the front page or login page
        // and the theme has a logo.
        $logo = $this->get_logo_url();
        return false;
    }

    /**
     * Returns the navigation bar home reference.
     *
     * The small logo is only rendered on pages where the logo is not displayed.
     *
     * @param bool $returnlink Whether to wrap the icon and the site name in links or not
     * @return string The site name, the small logo or both depending on the theme settings.
     */
    public function navbar_home($returnlink = true) {
        global $CFG, $SITE;
			
		$logocontainer = '<div class="brand">';

        $logocontainer .= '<img src="'. $this->page->theme->image_url("logos/logo_magistere", "theme").'"/>';
        $logocontainer .=  '<span class="shortname">' . format_string($SITE->shortname, true, array('context' => context_course::instance(SITEID))). '</span>';
		
		$logocontainer .=  '</div>';
		
		return $logocontainer;
    }

    /**
     * Returns a reference to the site home.
     *
     * It can be either a link or a span.
     *
     * @param bool $returnlink
     * @return string
     */
    protected function get_home_ref($returnlink = true) {
        global $CFG, $SITE;

        $sitename = format_string($SITE->shortname, true, array('context' => context_course::instance(SITEID)));

        if ($returnlink) {
            return html_writer::link(new moodle_url('/'), $sitename, array('class' => 'brand', 'title' => get_string('home')));
        }

        return html_writer::tag('span', $sitename, array('class' => 'brand'));
    }

    /**
     * Return the theme logo URL, else the site's logo URL, if any.
     *
     * Note that maximum sizes are not applied to the theme logo.
     *
     * @param int $maxwidth The maximum width, or null when the maximum width does not matter.
     * @param int $maxheight The maximum height, or null when the maximum height does not matter.
     * @return moodle_url|false
     */
    public function get_logo_url($maxwidth = null, $maxheight = 100) {
        global $CFG, $OUTPUT;		
		
        if (!empty($this->page->theme->settings->logo)) {
            $url = $this->page->theme->setting_file_url('logo', 'logo');
            // Get a URL suitable for moodle_url.
            $relativebaseurl = preg_replace('|^https?://|i', '//', $CFG->wwwroot);
            $url = str_replace($relativebaseurl, '', $url);
            return new moodle_url($url);
        }
        return parent::get_logo_url($maxwidth, $maxheight);
    }

    /**
     * Return the theme's compact logo URL, else the site's compact logo URL, if any.
     *
     * Note that maximum sizes are not applied to the theme logo.
     *
     * @param int $maxwidth The maximum width, or null when the maximum width does not matter.
     * @param int $maxheight The maximum height, or null when the maximum height does not matter.
     * @return moodle_url|false
     */
    public function get_compact_logo_url($maxwidth = 100, $maxheight = 100) {
        global $CFG, $OUTPUT;
		
        if (!empty($this->page->theme->settings->smalllogo)) {
            $url = $this->page->theme->setting_file_url('smalllogo', 'smalllogo');
            // Get a URL suitable for moodle_url.
            $relativebaseurl = preg_replace('|^https?://|i', '//', $CFG->wwwroot);
            $url = str_replace($relativebaseurl, '', $url);
            return new moodle_url($url);
        }
        return parent::get_compact_logo_url($maxwidth, $maxheight);
    }
	
	protected function render_custom_menu(custom_menu $menu) { // 18/07/2017-TCS-JBL-1642 : Menu dans le header avec les restriction d'acces
        global $CFG, $DB, $USER;
        require_once($CFG->dirroot.'/local/magisterelib/courseList.php');
        require_once($CFG->dirroot.'/local/magisterelib/frontalFeatures.php');
        require_once($CFG->dirroot.'/local/metaadmin/lib.php');
        require_once($CFG->dirroot.'/local/myindex/MyIndexApi.php');

        if(isfrontal() && ($mainaca = user_get_mainacademy($USER->id)) !== false) {
            $magistere_academy = get_magistere_academy_config();
            if (isset($magistere_academy[$mainaca])) {
                $myurl = new moodle_url($CFG->magistere_domaine.$magistere_academy[$mainaca]['accessdir'].'/my/');
            }else{
                $myurl = new moodle_url('/my/');
            }
            $branchlabel = "Mes parcours";
            $branchtitle = $branchlabel;
            $branchsort  = 1;
            $menu->add($branchlabel, $myurl, $branchtitle, $branchsort);
        }

        $context = context_system::instance();

        if(!isfrontal() && isloggedin()) {
            $branchlabel = "Mes parcours";
            $mainaca = user_get_mainacademy($USER->id);
            $magistere_academy = get_magistere_academy_config();
            if($mainaca !== false && isset($magistere_academy[$mainaca])) {
                $branchurl = new moodle_url($CFG->magistere_domaine . $magistere_academy[$mainaca]['accessdir'] . '/my/');
            } else {
                $branchurl = new moodle_url('/my/');
            }

            $branchtitle = $branchlabel;
            $branchsort  = 2;
            $menu->add($branchlabel, $branchurl, $branchtitle, $branchsort);

            if (has_capability('moodle/supervision:consult', $context)
                || has_capability('local/metaadmin:statsparticipants_viewownacademy', $context)
                || has_capability('local/metaadmin:statsparticipants_viewallacademies', $context)){
                $branchlabel = "Suivi";
                $suivi = $menu->add($branchlabel, new moodle_url('#'), $branchlabel, 6);

                if (has_capability('moodle/supervision:consult', $context)){
                    $branchlabel = "Suivi des participants";
                    $suivi->add($branchlabel, new moodle_url('/local/supervision/index.php'), $branchlabel, 1);
                }
                if (has_capability('local/metaadmin:statsparticipants_viewownacademy', $context)
                    || has_capability('local/metaadmin:statsparticipants_viewallacademies', $context)){
                    $branchlabel = "Meta-Admin";
                    $metaadmin = $suivi->add($branchlabel, new moodle_url('#'), $branchlabel, 2);
                    if (has_capability('local/metaadmin:statsparticipants_viewownacademy', $context)){
                        $branchlabel = "Académiques";
                        $metaadmin->add($branchlabel, new moodle_url('/local/metaadmin/view_statsparticipants.php'), $branchlabel, 1);
                    }
                    if (has_capability('local/metaadmin:statsparticipants_viewownacademy', $context)){
                        $branchlabel = "Nationales";
                        $metaadmin->add($branchlabel, new moodle_url('/local/metaadmin/view_statsparticipants_per_academy.php'), $branchlabel, 2);
                    }
                    if (has_capability('local/metaadmin:statsparticipants_viewownacademy', $context)) {
                        $branchlabel = "Départementales 1er degré";
                        $metaadmin->add($branchlabel, new moodle_url('/local/metaadmin/view_statsparticipants_first_degree_per_academy.php'), $branchlabel, 3);
                    }

                    /* => CLEO - evol 2419 - 2.1.0 - 17/04/2018 */
                    if (has_capability('local/metaadmin:statsparticipants_manageviews', $context)) {
                        // position in the menu
                        $pos = 4;
                        if ($views = get_custom_views_by_user($USER->id,$CFG->academie_name)) {
                            $nbViews = count($views);
                            foreach ($views as $view) {
                                $branchlabel = $view->view_name;
                                $metaadmin->add($branchlabel, new moodle_url('/local/metaadmin/customview_statsparticipants.php?id='.$view->id), $branchlabel, $pos);
                                $pos++;
                            }
                        }
                        // For now, number of views by user is limited at 5
                        if (!isset($nbViews) || ($nbViews < 5)) {
                            $branchlabel = "Ajouter une nouvelle vue";
                            $metaadmin->add($branchlabel, new moodle_url('/local/metaadmin/editcustomview.php'), $branchlabel, $pos);
                        }
                    }
                    /* <= CLEO - evol 2419 - 2.1.0 - 17/04/2018 */
                }
            }
        }

        $hasoffercapability = false;
        $is_formateur = false;
        $is_tuteur = false;
        $is_ministry_user = false;

        if($USER->id > 0){
            $hasoffercapability = has_capability('local/magistere_offers:view_courseoffer', $context);

            $role_formateur = $DB->get_record('role', array('shortname' => 'formateur'));
            $is_formateur = user_has_role_assignment($USER->id, $role_formateur->id);
            $role_tuteur = $DB->get_record('role', array('shortname' => 'tuteur'));
            $is_tuteur = user_has_role_assignment($USER->id, $role_tuteur->id);

            $is_ministry_user = (strpos($USER->email, '@education.gouv.fr')?true:false);


            if(isfrontal()) { // frontal
                // check the capabilities on the main aca
                $mainaca = user_get_mainacademy($USER->id);
                if($mainaca) {
                    $hasoffercapability = FrontalFeatures::has_capability( $mainaca, 'local/magistere_offers:view_courseoffer');
                    $is_formateur = FrontalFeatures::user_has_role_assignment($USER, 'formateur', $mainaca);
                    $is_tuteur = FrontalFeatures::user_has_role_assignment($USER, 'tuteur', $mainaca);
                }
            }
        }

        $canviewcourseoffer = ($hasoffercapability || $is_formateur || $is_tuteur || $is_ministry_user);

        $offreparcoursurl = new moodle_url($CFG->magistere_domaine.'/local/magistere_offers/index.php?v=course');
        $offrecompurl = new moodle_url($CFG->magistere_domaine.'/local/magistere_offers/index.php?v=formation');

        $branchlabel = "Offre de formation";
        $menu->add($branchlabel, $offrecompurl, $branchlabel, 7);

        if($canviewcourseoffer){
            $branchlabel = "Offre de parcours";
            $menu->add($branchlabel, $offreparcoursurl, $branchlabel, 8);
        }

        $content = '<ul class="nav">';
        foreach ($menu->get_children() as $item) {
            $content .= $this->render_custom_menu_item($item, 1);
        }

        return $content.'</ul>';
    }

    public function render_context_header(context_header $contextheader){
        return '';
    }

    public function add_encart_activity($text){
        return $text;
    }

    /**
     * Outputs a heading
     *
     * @param string $text The text of the heading
     * @param int $level The level of importance of the heading. Defaulting to 2
     * @param string $classes A space-separated list of CSS classes. Defaulting to null
     * @param string $id An optional ID
     * @return string the HTML to output.
     */
    public function heading($text, $level = 2, $classes = null, $id = null) {
        global $CFG, $PAGE;
        $level = (integer) $level;
        if ($level < 1 or $level > 6) {
            throw new coding_exception('Heading level must be an integer between 1 and 6.');
        }
        $text = html_writer::tag('h' . $level, $text,
            array('id' => $id, 'class' => renderer_base::prepare_classes($classes)));
        if($PAGE->pagelayout != 'incourse' || $level != 2){
            return $text;
        }
        $section = optional_param('section', false, PARAM_INT);
        if($section){
            $section = '&section='.$section;
        }

        $node = $PAGE->navigation->find_active_node();
        if(strpos($PAGE->url, '/blog/index.php') || strpos($PAGE->url, '/blog/edit.php')
            || strpos($PAGE->url, '/tag/index.php')) {
            $courseurl = $CFG->wwwroot . '/course/view.php?id='.$PAGE->context->instanceid.$section;
        } else {
            $courseurl = (isset($node->action) && strlen($node->action)>0?$node->action:'');

            $i = 0;
            while (strpos($courseurl, '/course/view.php') === false)
            {
                if ($i > 20){break;}
                if (isset($node->parent)) {
                    $node = $node->parent;
                    $courseurl = (isset($node->action) && strlen($node->action)>0?$node->action:'');
                } else {
                    break;
                }
                $i++;
            }
        }

        if($PAGE->bodyid == 'page-mod-questionnaire-complete'){
            $courseurl = $CFG->wwwroot . '/course/view.php?id='.$PAGE->cm->course.'&section='.$PAGE->cm->sectionnum;
        }

        if((strpos($PAGE->url, '/blog/index.php')
                && ($PAGE->url->get_param('courseid') == null
                    || $PAGE->url->get_param('courseid') == 0))
            || (strpos($PAGE->url, '/blog/edit.php')
                && ($PAGE->url->get_param('courseid') == null
                    || $PAGE->url->get_param('courseid') == 0 || $PAGE->context->instanceid == 0))
            || (strpos($PAGE->url, '/tag/index.php')
                && ($PAGE->url->get_param('from') == null
                    || $PAGE->url->get_param('from') == 0 || $PAGE->context->instanceid == 0))){
            return html_writer::div($text);
        }

        if(strpos($PAGE->bodyclasses, 'format-topics') !== false){
            $title = html_writer::div($text);
            $backto = html_writer::tag('a', 'Retour au parcours de formation',
                array('href' => $courseurl, 'class' => 'activity-encart-backButton'));
            $html = html_writer::div($title.$backto, 'activity-encart topics' );
        } else {
            $title = "Retour au parcours de formation";
            if($PAGE->course->id == 1){
                $title = "Retour à l'accueil";
            }
            $html = '<div class="activity-encart">';

            if($PAGE->course->format != "singleactivity") {
                $html .= '<a href="'.$courseurl.'" class="activity-encart-back-button">
                '. $title .'</a>
                    <hr class="activity-encart-upline" style="display: block;"/>';
            }

            if ($PAGE->course->id != 1 && $PAGE->course->format != "singleactivity") {
                $html .= '<p class="activity-encart-title">Activité</p>';
            }

            $html .= $text;

            $html .= '<hr class="activity-encart-downline" style="display: block;"/></div>';
        }

        return $html;
    }

    function get_icon_collection_course_id($courseid){
        global $DB, $CFG;
        $indexation = $DB->get_record_sql('SELECT li.id, clic.shortname collection  
FROM {local_indexation} li
INNER JOIN '.$CFG->centralized_dbname.'.local_indexation_collections clic ON clic.id=li.collectionid
WHERE li.courseid=:courseid', array('courseid' => $courseid));

        if($indexation){
            $indexation->collection = strtolower($indexation->collection);
            switch ($indexation->collection) {
                case "action":
                    $label = "action";
                    break;
                case "analyse":
                    $label = "analyse";
                    break;
                case "autoformation":
                    $label = "autoformation";
                    break;
                case 'decouverte':
                    $label = "decouverte";
                    break;
                case "reseau":
                    $label = "reseau";
                    break;
                case "simulation":
                    $label = "simulation";
                    break;
                case "qualification":
                    $label = "qualif";
                    break;
                case "volet_distant":
                    $label = "distant";
                    break;
                case "espacecollab":
                    $label = "collaboratif";
                    break;
                default:
                    $indexation->collection = 'empty';
                    $label = "empty";
            }
            if($indexation->collection || $indexation->collection != 'empty'){
                return '<i class="collection-icon '. $label .'"></i>';
            }
            return false;
        }
        return false;
    }

    public function display_favorite_course_button($courseid){
        global $USER, $DB;

        $is_favorite_course = $DB->get_record('local_favoritecourses', array('courseid' => $courseid, 'userid' => $USER->id));

        if($is_favorite_course){
            $content = '<a class="fav" href="#"><i class="fa fa-star" aria-hidden="true" title="Retirer de mes parcours favoris"></i></a>';
        }else{
            $content = '<a class="unfav" href="#"><i class="far fa-star" aria-hidden="true" title="Ajouter à mes parcours favoris"></i></a>';
        }
        $content .= $this->javascript_for_favorite_course_button($courseid);

        return $content;
    }

    public function javascript_for_favorite_course_button($courseid){
        global $CFG;
        if($courseid){
            $ajax_log_url = $CFG->wwwroot.'/local/favoritecourses/js/ajax.php';
            $script = '
                $( document ).ready(function() {
                    $("a.fav").click(manageFavorite);
                    $("a.unfav").click(manageFavorite);
                });
                function manageFavorite(e){
                    e.preventDefault();
                    var obj = $(this); 
                    $.ajax({
                        type: "POST",
                        url: "'.$ajax_log_url.'",
                        data:{ 
                            id: "'.$courseid.'",
                        },
                        datatype: "json",
                        success:function(){
                            if(obj.hasClass("unfav")){
                                obj.removeClass("unfav").addClass("fav");
                                obj.find("i").removeClass().addClass("fa fa-star");
                                obj.find("i").attr("title","Retirer de mes parcours favoris")
                            } else {
                                obj.removeClass("fav").addClass("unfav");
                                obj.find("i").removeClass().addClass("far fa-star");
                                obj.find("i").attr("title","Ajouter à mes parcours favoris")
                            }                                                                                     
                        },
                        error:function(error){
                            console.log(error);
                        }
                    });
                }
            ';
            return html_writer::script($script);
        }
        return false;
    }

    function edit_button(moodle_url $url, $course = false)
    {
        global $OUTPUT, $CFG;
        if($course){
            $course_context = context_course::instance($this->page->course->id);
            $haseditingrole = (has_capability('moodle/site:manageblocks', $course_context) && has_capability('moodle/course:update', $course_context));
            $canseeworkflow = has_capability('local/workflow:globalaccess', $course_context);
            require_once($CFG->dirroot.'/local/workflow/lib.php');
            $workflow = start_workflow($this->page->course->id, true);

            $buttons = '';
            if ($haseditingrole || $canseeworkflow) {
                // Bouton Workflow
                $url = new moodle_url('/local/workflow/index.php?id='.$this->page->course->id);
                $this->page->set_button('<div class="singlebutton magistere-custom-button workflow">   
                                    <a href="'.$url->out().'">
                                        <button type="submit" value="" name="submit" class="btn-workflow-mode">
                                            <img src="'.$OUTPUT->image_url('logo_workflow', 'local_workflow').'">'.
                    $workflow->getStepName().
                    $workflow->getNotificationBadgesHTML().'
                                        </button>
                                    </a>
                               </div>');
                $buttons .= $this->page->button;
            }
            if($haseditingrole){
                // Bouton edition d'un parcours
                $value_btn = 'on';
                $button = '<button type="submit" value="" name="submit" class="btn-edit-mode"><i class="fas fa-2x fa-pencil-alt"></i></button>';
                if ($this->page->user_is_editing()) {
                    $value_btn = 'off';
                    $button = '<button type="submit" value="" name="submit" class="btn-edit-mode"><img src="'.$OUTPUT->image_url('general/icon_quitter_edition','theme').'"></button>';
                }
                $url = new moodle_url('/course/view.php');

                $this->page->set_button('<div class="singlebutton magistere-custom-button edit">
                                <form action="'.$url.'" method="post">
                                    '.$button.'
                                    <input type="hidden" value="'.$this->page->course->id.'" name="id">
                                    <input type="hidden" value="'.$url->param('sesskey', sesskey()).'" name="sesskey">
                                    <input type="hidden" value="'.$value_btn.'" name="edit">
                                    <input type="hidden" value="'.$this->page->url->get_param('section').'" name="section">
                                </form>
                            </div>');
                $buttons .= $this->page->button;
            }


            return $buttons;

        } else {
            return parent::edit_button($url); // TODO: Change the autogenerated stub
        }
    }

    /**
     * Implementation of user image rendering.
     *
     * @param help_icon $helpicon A help icon instance
     * @return string HTML fragment
     */
    protected function render_help_icon(help_icon $helpicon) {
        global $CFG, $PAGE;
        require_once($CFG->dirroot . '/local/workflow/lib.php');

        $title = get_string($helpicon->identifier, $helpicon->component);

        if (empty($helpicon->linktext)) {
            $alt = get_string('helpprefix2', '', trim($title, ". \t"));
        } else {
            $alt = get_string('helpwiththis');
        }

        if($helpicon->component == "local_indexation"){
            if($helpicon->linktext >= 1 ){
                $output = html_writer::span(
                    html_writer::tag('i','',array('class' => 'fa fa-circle fa-stack-2x')).
                    html_writer::span($helpicon->linktext,'fa fa-stack-1x number'),'fa fa-stack fa-1x notification-badge');
            } else {
                $output = html_writer::tag('i','', array('alt'=>$alt, 'class'=>'iconhelp fa fa-2x fa-exclamation-circle'));
            }
        } else {
            return $this->render_from_template('core/help_icon', $helpicon->export_for_template($this));
        }


        // add the link text if given
        if (!empty($helpicon->linktext) && ($PAGE->pagelayout != "indexation")) {
            // the spacing has to be done through CSS
            $output .= $helpicon->linktext;
        }

        // now create the link around it - we need https on loginhttps pages
        $url = new moodle_url($CFG->httpswwwroot.'/help.php', array('component' => $helpicon->component, 'identifier' => $helpicon->identifier, 'lang'=>current_language()));

        // note: this title is displayed only if JS is disabled, otherwise the link will have the new ajax tooltip
        $title = get_string('helpprefix2', '', trim($title, ". \t"));

        $attributes = array('href' => $url, 'title' => $title, 'aria-haspopup' => 'true', 'target'=>'_blank');
        $output = html_writer::tag('a', $output, $attributes);

        // and finally span
        return html_writer::tag('span', $output, array('class' => 'helptooltip'));
    }

    /**
     * Renderers the enrol_user_button.
     *
     * @param enrol_user_button $button
     * @return string XHTML
     */
    public function render_local_myindex_button(local_myindex_button $button) {
        $attributes = array('type'     => 'submit',
            'value'    => $button->label);

        if ($button->actions) {
            $id = html_writer::random_id('single_button');
            $attributes['id'] = $id;
            foreach ($button->actions as $action) {
                $this->add_action_handler($action, $id);
            }
        }

        // first the input element
        $output = html_writer::empty_tag('input', $attributes);

        // then hidden fields
        $params = $button->url->params();
        if ($button->method === 'post') {
            $params['sesskey'] = sesskey();
        }
        foreach ($params as $var => $val) {
            $output .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => $var, 'value' => $val));
        }

        // then div wrapper for xhtml strictness
        $output = html_writer::tag('div', $output);

        // now the form itself around it
        if ($button->method === 'get') {
            $url = $button->url->out_omit_querystring(true); // url without params, the anchor part allowed
        } else {
            $url = $button->url->out_omit_querystring();     // url without params, the anchor part not allowed
        }
        if ($url === '') {
            $url = '#'; // there has to be always some action
        }
        $attributes = array('method' => $button->method,
            'action' => $url,
            'id'     => $button->formid);
        $output = html_writer::tag('form', $output, $attributes);

        // and finally one more wrapper with class
        return html_writer::tag('div', $output, array('class' => $button->class));
    }
}
