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
 * Progress Bar block definition
 *
 * @package    contrib
 * @subpackage block_progress
 * @copyright  2010 Michael de Raadt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot.'/blocks/progress/lib.php');

/**
 * Progress Bar block class
 *
 * @copyright 2010 Michael de Raadt
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_progress extends block_base {

    /**
     * Sets the block title
     *
     * @return void
     */
    public function init() {
        global $COURSE;
        $context = context_course::instance($COURSE->id);
        $roles = get_user_roles($context);
        $new_link = true;
        foreach($roles as $role){
            if(count($roles) == 1 && $role->shortname == 'participant'){
                $new_link = false;
            }
        }
        if($new_link == false){
            $this->title = get_string('config_default_title', 'block_progress');

        } else {
            $link = '<a href="https://wiki.magistere.education.fr/Suivi_des_participants" target="_blank" style="color:#fff;"><i class="fa fa-question-circle-o" aria-hidden="true"></i></a>';
            $this->title = get_string('config_default_title', 'block_progress'). ' (obsolète '.$link.')';
        }

    }

    /**
     * Constrols the block title based on instance configuration
     *
     * @return bool
     */
    public function specialization() {
        if (isset($this->config->progressTitle) && trim($this->config->progressTitle)!='') {
            $this->title = format_string($this->config->progressTitle);
        }
    }

    public function user_can_edit(){
        global $USER;

        if (parent::user_can_edit()) {
            return true;
        }

        if (has_capability('block/progress:delinstance', $this->context)) {
            return true;
        }

        return false;
    }

    public function user_can_addto($page){
        if(parent::user_can_addto($page)){
            return true;
        }

        if (has_capability('block/progress:delinstance', $page->context)) {
            return true;
        }

        return false;
    }
    /**
     * Controls whether multiple instances of the block are allowed on a page
     *
     * @return bool
     */
    public function instance_allow_multiple() {
        return true;
    }

    /**
     * Defines where the block can be added
     *
     * @return array
     */
    public function applicable_formats() {
        return array(
			'course-view'    => true,
			'site'           => false,
			'mod'            => false,
			'my'             => false
		);
    }

    /**
     * Creates the blocks main content
     *
     * @return string
     */
    public function get_content() {

        // Access to settings needed
        global $USER, $COURSE, $CFG, $DB, $OUTPUT, $SESSION, $PAGE;

        // If content has already been generated, don't waste time generating it again
        if ($this->content !== null) {
            return $this->content;
        }
        $this->content = new stdClass;
        $this->content->text = '';
        $this->content->footer = '';

        // Check if any activities/resources have been created
        $modules = modules_in_use();
        if (empty($modules)) {
            if (has_capability('moodle/block:edit', $this->context)) {
                $this->content->text .= get_string('no_events_config_message', 'block_progress');
            }
            return $this->content;
        }

        // Check if activities/resources have been selected in config
        $events = event_information($this->config, $modules);
        if ($events===null || $events===0) {
            if (has_capability('moodle/block:edit', $this->context)) {
                $this->content->text .= get_string('no_events_message', 'block_progress');
                if($USER->editing) {
                    $parameters = array('id'=>$COURSE->id, 'sesskey'=>sesskey(),
                                        'bui_editid'=>$this->instance->id);
                    $url = new moodle_url('/course/view.php', $parameters);
                    $label = get_string('selectitemstobeadded', 'block_progress');
                    $this->content->text .= $OUTPUT->single_button($url, $label);
                    if ($events===0) {
                        $url->param('turnallon', '1');
                        $label = get_string('addallcurrentitems', 'block_progress');
                        $this->content->text .= $OUTPUT->single_button($url, $label);
                    }
                }
            }
            return $this->content;
        }
        else if (empty($events)) {
            if (has_capability('moodle/block:edit', $this->context)) {
                $this->content->text .= get_string('no_visible_events_message', 'block_progress');
            }
            return $this->content;
        }

        // Display progress bar
        else {
            $attempts = get_attempts($modules, $this->config, $events, $USER->id,
                $this->instance->id);
            $this->content->text =
                progress_bar($modules, $this->config, $events, $USER->id,
                             $this->instance->id, $attempts);
        }

        // Organise access to JS
        $jsmodule = array(
            'name' => 'block_progress',
            'fullpath' => '/blocks/progress/module.js',
            'requires' => array(),
            'strings' => array(
                array('time_expected', 'block_progress'),
            ),
        );
        $displaydate = (!isset($this->config->orderby) || $this->config->orderby=='orderbytime') &&
                       (!isset($this->config->displayNow) || $this->config->displayNow==1);
        $arguments = array($CFG->wwwroot, array_keys($modules), $displaydate);
        $this->page->requires->js_init_call('M.block_progress.init', $arguments, false, $jsmodule);


        //$this->page->requires->css('/theme/'.$this->page->theme->name.'/style/tooltipster.css');

        $PAGE->requires->jquery();
        $PAGE->requires->jquery_plugin('tooltipster');
        $PAGE->requires->jquery_plugin('tooltipster-css');
        //$this->content->text .= '<script src="'.$CFG->wwwroot.'/theme/'.$this->page->theme->name.'/javascript/jquery.tooltipster.min.js"></script>';

        $isformattopics = (strpos($PAGE->bodyclasses, 'format-topics') !== false);

        if(!$isformattopics) {
            $is_completed = $DB->get_record('progress_complete', array('courseid' => $COURSE->id, 'userid' => $USER->id, 'is_complete' => 1));

            if ($is_completed) {
                $this->content->text .= '<span style="font-weight:bold;color:#31a642">Formation terminée <img id="formation_help" class="tooltip tooltipstered" src="' . $OUTPUT->image_url('help', 'core') . '" /></span>';
            } else {
                $this->content->text .= '<span style="font-weight:bold;color:#ffa64a">Formation en cours <img id="formation_help" class="tooltip tooltipstered" src="' . $OUTPUT->image_url('help', 'core') . '" /></span>';
            }

            $tooltip = "<span>Votre formateur réalise le suivi pédagogique. A la fin de la formation, il doit attester de votre présence à cette formation. Dès que la formation a débuté, le statut indique \"<span style=\\'font-weight:bold;color:#ffa64a\\'>Formation en cours</span>\". Le statut passe à \"<span style=\\'font-weight:bold;color:#31a642\\'>Formation terminée</span>\" dès que le formateur a attesté de votre participation."
                . "<br/>"
                . "<br/><b>Mon formateur peut-il attester de ma présence même si je n\\'ai pas terminé toutes les activités ?</b>"
                . "<br/>Oui, vous êtes acteur de votre formation. Vous pouvez faire des choix concernant la réalisation de certaines activités en fonction de vos objectifs ou de votre maîtrise du sujet."
                . "<br/>"
                . "<br/><b>A qui est destinée cette attestation de présence ?</b>"
                . "<br/>Le formateur transmet cette attestation de présence aux responsables de la formation pour le suivi administratif. Cette attestation mentionne les informations de la session : Intitulé, durée (durée planifiée), dates, votre nom, votre prénom et la mention \"Présent(e)\" ou \"Absent(e)\"</span>";

            //$tooltip = htmlspecialchars($tooltip);

            //$this->content->text .= $tooltip;

            $this->content->text .= '<script type="text/javascript">
        jQuery(document).ready(function($) {
          $("#formation_help").tooltipster({
             maxWidth: 620,
             position: \'bottom-right\',
             content: $(\'' . $tooltip . '\')
          });
        });
    </script>';
        } else {
            $this->content->text .= '
                <script type="text/javascript">
                    $(document).ready(function($) {
                        $(".block_progress").on("mouseout", ".progressBarCell", function(){
                            $(".block_progress .progressEventInfo").empty();
                            $(".block_progress .progressEventInfo").append("<div class=\'progressEventInfo_default\'>'.get_string('mouse_over_prompt', 'block_progress').'</div>");
                        });
                    });
                </script>';
        }

        // Allow teachers to access the overview page
        if (has_capability('block/progress:overview', $this->context))
        {
        	$parameters = array('id' => $this->instance->id, 'courseid' => $COURSE->id);
        	$url = new moodle_url('/blocks/progress/overview.php', $parameters);
        	$this->content->text .= '<div class="newlink"><a href="'.$url.'">'.get_string('overview', 'block_progress').'</a></div>';
        }

        //$parameters = array('course' => $COURSE->id, 'refresh' => '1');
        $parameters = null;
        $url = new moodle_url('/blocks/progress/ajax_redirect.php'); //, $parameters);

        $lastupdatetime = date('H\hi', $SESSION->block_progress_cache[$USER->id][$COURSE->id]['last_update']);

        $this->content->text .= '<div class="newlink" id="progresscacherefresh" style="cursor:pointer"><a>'.get_string('cache_update', 'block_progress').'<br/><span class="cachelastupdate">'.get_string('cache_last_update', 'block_progress').' : '.$lastupdatetime.'</span></a></div>';


        // <p style="text-align:left;font-size:8pt; padding:2px 2px 4px 2px">'.get_string('cache_last_update', 'block_progress').' : '.$lastupdatetime.' <img src="'.$OUTPUT->image_url('general/icon_refresh2', 'theme').'" alt="" onclick="javascript:refresh_bbp()" style="cursor:pointer; float:right; margin:0px 2px 10px 0px" /></p>
        $this->content->text .= '
<script type="text/javascript">
	$(function() {
		$("#progresscacherefresh").on("click","",function(){		
			$.post("'.html_entity_decode($url).'", {refresh: "1", course: "'.$COURSE->id.'"})
		      .done(function( data ) {
		        if (data == "done")
		        {
		          location.reload(true);
		        }
		      });
		});
	});
</script>';

        return $this->content;
    }


}
