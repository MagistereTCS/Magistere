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
 * Blog Menu Block page.
 *
 * @package    block
 * @subpackage course_migration
 * @copyright  2017 TCS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/blocks/course_migration/lib.php');

/**
 * The blog menu block class
 */
class block_course_migration extends block_base {

    function init() {
        $this->title = get_string('pluginname', 'block_course_migration');
    }

    function instance_allow_multiple() {
        return false;
    }

    function instance_can_be_hidden(){
    	return false;
    }

    function user_can_addto($page)
    {
        // Don't allow people to add the block if they can't even use it
        if (!has_capability('block/course_migration:addinstance', $page->context)) {
            return false;
        }

        return parent::user_can_addto($page);
    }

    function is_empty(){
    	global $PAGE;
    	list($context, $course, $cm) = get_context_info_array($PAGE->context->id);
    	return ( !has_capability('block/course_migration:showmigrationblock',$context) );
    }

    function applicable_formats() {
        return array('all' => true, 'my' => false, 'tag' => false, 'topic' => true, 'flexpage' => true);
    }

    function get_content() {
        global $PAGE, $DB, $COURSE, $CFG;
        

        $apiurl = $CFG->wwwroot . '/blocks/course_migration/ajax.php';
        $a = null;
        
        $PAGE->requires->jquery();
        $PAGE->requires->jquery_plugin('ui');
        $PAGE->requires->jquery_plugin('ui-css');
        $PAGE->requires->css("/local/magistere_offers/js/jquery.loadingModal.min.css");


        list($context, $course, $cm) = get_context_info_array($PAGE->context->id);

        $active_class = array('class' => 'is_active_block_summary');

        $migration = array(
            'converted' => array(),
            'original' => array()
        );

        $converted = $DB->get_records_sql('SELECT bcm.id, bcm.flexcourseid, bcm.stdcourseid, bcm.status, bcm.originalformat, bcm.convertedformat FROM {block_course_migration} bcm 
LEFT JOIN {course} c ON c.id=bcm.stdcourseid
WHERE bcm.flexcourseid = "'.$COURSE->id.'" AND (bcm.stdcourseid IS NULL OR c.id IS NOT NULL) 
ORDER BY bcm.startdate');

        foreach($converted as $line){
            $migration['converted'][$line->convertedformat] = $line;
        }

        $original = $DB->get_records_sql('SELECT 
bcm.id, bcm.flexcourseid, bcm.stdcourseid, bcm.status, bcm.originalformat, bcm.convertedformat, bcm.startdate 
FROM {block_course_migration} bcm 
INNER JOIN {course} c ON c.id=bcm.flexcourseid
WHERE bcm.stdcourseid = "'.$COURSE->id.'" AND bcm.status=1
ORDER BY bcm.startdate');

        foreach($original as $line){
            $migration['original'][$line->convertedformat] = $line;
            $migration['original'][$line->convertedformat]->validation_delay = floor(($line->startdate + (14*86400) - time())/86400); // number of day remaining
        }


        // define allowed migration
        if(($COURSE->format == 'topics' || $COURSE->format == 'magistere_topics' ) && has_capability('block/course_migration:convertcourse', $context)){
            if(!isset($migration['converted']['modular'])){
                $migration['converted']['modular'] = true;
            }
        }

        $current_cat = $DB->get_record_sql('SELECT name FROM {course_categories} WHERE id = (SELECT cx.instanceid FROM {context} cx WHERE cx.contextlevel = '.CONTEXT_COURSECAT.' AND cx.id = SUBSTRING_INDEX(SUBSTRING_INDEX((SELECT path FROM {context} WHERE contextlevel = '.CONTEXT_COURSE.' AND instanceid = "'.$COURSE->id.'"), \'/\', 3), \'/\', -1))');

        $course_category = $current_cat->name;

        $li = html_writer::tag('li', 'Catégorie : '.$course_category, $active_class);
        $li .= html_writer::tag('li', 'Format : '.get_string("pluginname","format_".$COURSE->format), $active_class);
        $ul = html_writer::tag('ul', $li, $active_class);

        $p = html_writer::tag('p', 'Statut du parcours<br\>'.$ul, $active_class);

        $name = html_writer::tag('li', $p, array('class'=>'cm_desc'));

        foreach($migration['converted'] as $format => $line){
            if($line === true){
                $courseid = $COURSE->id;
                $a = $format;
                $link = html_writer::tag('button', get_string($format.'convlabel', 'block_course_migration'), array('class' => 'course_mig_conv course_mig_link', 'role'=> 'button'));
                $p = html_writer::tag('p', $link, $active_class);
                $name .= html_writer::tag('li', $p, array('class'=>'cm_button'));
                continue;
            }

            if($line->status == 0){
                $label = get_string($line->convertedformat.'plannedlabel', 'block_course_migration');
                $p = html_writer::tag('p', $label, $active_class);
                $name .= html_writer::tag('li', $p, array('class'=>'cm_button'));
            }else if($line->status == 1){
                $label = get_string($line->convertedformat.'accesslabel', 'block_course_migration');
                $url = new moodle_url('/course/view.php', array('id' => $line->stdcourseid));
                $link = html_writer::tag('a', $label, array('href' => $url));
                $p = html_writer::tag('p', $link, $active_class);
                $name .= html_writer::tag('li', $p, array('class'=>'cm_button'));
            }else if($line->status == 2){
                $label = get_string($line->convertedformat.'wiplabel', 'block_course_migration');
                $p = html_writer::tag('p', $label, $active_class);
                $name .= html_writer::tag('li', $p, array('class'=>'cm_button'));
            }else if($line->status == 3){
                $a = $format;
                $label = get_string($line->convertedformat.'errorlabel', 'block_course_migration');
                $link = html_writer::tag('button', $label, array('class' => 'course_mig_conv course_mig_link', 'role'=> 'button'));
                $p = html_writer::tag('p', $link, $active_class);
                $name .= html_writer::tag('li', $p, array('class'=>'cm_button'));
            }
        }

        foreach($migration['original'] as $line){
            $label = 'Accéder au parcours original';
            $url = new moodle_url('/course/view.php', array('id' => $line->flexcourseid));
            $link = html_writer::tag('a', $label, array('href' => $url));
            $p = html_writer::tag('p', $link, $active_class);
            $name .= html_writer::tag('li', $p, array('class'=>'cm_button'));

            if(has_capability('block/course_migration:removeflexpagecourse', $context)){
                $a = 'va';
                $label = get_string('validateconversion', 'block_course_migration', $line->validation_delay);
                $link = html_writer::tag('button', $label, array('class' => 'course_mig_va course_mig_link', 'role'=> 'button'));
                $p = html_writer::tag('p', $link, $active_class);
                $name .= html_writer::tag('li', $p, array('class'=>'cm_button'));
            }

            if(has_capability('block/course_migration:removeflexpagecourse', $context)){
                $a = 'rc';
                $label = get_string('removeconvertedcourse', 'block_course_migration');
                $link = html_writer::tag('button', $label, array('class' => 'course_mig_rc course_mig_link', 'role'=> 'button'));
                $p = html_writer::tag('p', $link, $active_class);
                $name .= html_writer::tag('li', $p, array('class'=>'cm_button'));
            }
        }

        $PAGE->requires->js_call_amd('block_course_migration/course_migration', 'init', array($apiurl, $COURSE->id, $a));
        
        $this->content = new stdClass();
        $this->content->text = html_writer::tag('ul', $name, array('id'=>'course_mig'));

        $this->content->text .= '
<div id="dialog-confirm" title="Conversion du parcours" style="display:none">
  <p><span class="ui-icon ui-icon-alert" style="float:left; margin:12px 12px 20px 0;"></span>La conversion du parcours peut prendre quelques minutes. Vous serez informé par mail lorsque la conversion sera terminée.</p>
  <p><input type="checkbox" id="course_mig_keepdata" value="1"><p>Conserver les utilisateurs et leurs contributions<br/>
<span>Le parcours sera caché aux utilisateurs tant que vous ne validez pas la migration</span></p></p>
</div>
<div id="dialog-confirm-va" title="Validation de la migration du parcours" style="display:none">
  <p><span class="ui-icon ui-icon-alert" style="float:left; margin:12px 12px 20px 0;"></span>Attention : La validation de ce parcours entrainera la suppression du parcours original!<br/>Cette action est irréversible!</p>
</div>
<div id="dialog-confirm-del" title="Suppression du parcours original" style="display:none">
  <p><span class="ui-icon ui-icon-alert" style="float:left; margin:12px 12px 20px 0;"></span>Attention : La suppression du parcours original est irréversible!</p>
</div>
<div id="dialog-confirm-del2" title="Suppression du parcours converti" style="display:none">
  <p><span class="ui-icon ui-icon-alert" style="float:left; margin:12px 12px 20px 0;"></span>Attention : La suppression du parcours converti est irréversible!</p>
</div>';

        return $this->content;
    }

}
