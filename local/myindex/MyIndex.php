<?php
/**
 * Moodle MyIndex local plugin
 * This class is used to display the plugin interface, it contains the HTML code
 *
 * @package    local_myindex
 * @copyright  2020 TCS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('MyIndexApi.php');

class MyIndex {
    private $options = '';

    /***
     * Constructor
     * Search the available filters
     */
    function __construct(){
        $filters = MyIndexApi::get_courses_filters();
        $filter_selected = MyIndexApi::get_selected_filter();
        
        $this->options = '';
        foreach($filters AS $filter=>$count) {
            if ($count > 0 || $filter == MyIndexApi::FILTER_ALLCOURSE) {
                if ($filter_selected == $filter) {
                    $this->options .= html_writer::tag('option', get_string('filter_'.$filter,'local_myindex'), array('value'=> $filter,'selected'=>'selected'));
                }else{
                    $this->options .= html_writer::tag('option', get_string('filter_'.$filter,'local_myindex'), array('value'=> $filter));
                }
            }
        }
    }

    /***
     * Return the MyIndex HTML code
     * @return string HTML code
     */
    public function showMyIndex(){
        return $this->getHeader() . $this->getArchivedCourses() . $this->getModalContainer() . $this->getFooter();
    }

    /***
     * Return the HTML header of the MyIndex
     * @return string HTML code
     */
    protected function getHeader(){
        // Barre de recherche + changement de vue
        $header = html_writer::start_div('header');
        $header .= html_writer::div(
            html_writer::tag('select', $this->options, array(
                'id' => 'search-select',
                'class' => 'select')).
                    html_writer::tag('input',null, array('type'=>'text',
                        'id' => 'search-input',
                        'placeholder' => get_string('course_search', 'local_myindex'),
                        'class' => 'keyword')).
                    html_writer::tag('i','',array('class' => 'fa fa-search icon')),
            'search');
        $header .= html_writer::div(html_writer::span('Affichage :').
            html_writer::tag('button', html_writer::tag('i','', array('class' => 'fas fa-bars')), array('class' => 'btn list')).
            html_writer::tag('button', html_writer::tag('i','', array('class' => 'fas fa-th-large')), array('class' => 'btn grid')),
            'change-view');

        $header .= html_writer::end_div();
        $spinner = html_writer::div(html_writer::div('','spinner-border text-secondary'),'spinner');
        $courses_content = html_writer::div('','courses-content');
        $header .= html_writer::div($spinner.$courses_content,'myindex-content');
        return $header;
    }

    /***
     * Return the HTML archived courses of the MyIndex
     * @return string HTML code
     */
    protected function getArchivedCourses(){
        // Construction HTML de la liste des parcours archivés
        $content = html_writer::tag('legend',
            html_writer::link('javascript:void(0);', get_string('archived_courses', 'local_myindex'),
                array('class' => 'archived-courses-link collapsed'))
        , array('class' => 'archived-course-header'));

        $content .= html_writer::div(
            html_writer::div(html_writer::div('','spinner-border text-secondary'),'spinner').
            html_writer::div('', 'myindex-archived-content'), 'collapse', array('id' => 'collapseArchivedCourses'));
        return $content;
    }

    /***
     * Return the HTML modal container of the MyIndex
     * @return string HTML code
     */
    protected function getModalContainer(){
        $content = html_writer::div(null, 'create-modal', array('id' => 'myModal'));
        $content .= html_writer::div('', 'user-view-pref', array('v' => get_user_preferences('local_myindex_viewmod')));
        return $content;
    }

    /***
     * Return the HTML footer of the MyIndex
     * @return string HTML code
     */
    protected function getFooter(){
        $button_archive = html_writer::tag('button', 'Afficher les parcours archivés', array('id' => 'btn_showArchivedCourses', 'class' => 'btn '));
        $content = html_writer::div($button_archive, 'footer-content');
        return $content;
    }
}