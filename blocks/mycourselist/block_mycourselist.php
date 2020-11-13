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

require_once($CFG->dirroot . '/blocks/mycourselist/lib.php');
require_once($CFG->dirroot . '/local/favoritecourses/lib.php');


class block_mycourselist extends block_base {
    function init() {
        $this->title = get_string('pluginname', 'block_mycourselist');
    }

    function has_config() {
        return false;
    }

    function get_content() {
        global $PAGE, $SCRIPT;

        // pas de titre quand le bloc s'affiche
        $this->title = '';

        $PAGE->requires->jquery();

        $this->content = new stdClass();

        $html = '';
        switch($SCRIPT){
            case '/my/seformer.php':
                $html = build_html_seformer();
                break;
            case '/my/former.php':
                $html = build_html_former();
                break;
            case '/my/concevoir.php':
                $html = build_html_concevoir();
                break;
            case '/my/espacescollaboratifs.php':
                $html = build_html_espacecollaboratifs();
                break;
            default:
                $html = build_html_favorite();
        }

        $this->content->text = $html;

        $ajaxfavurl = new moodle_url('/local/favoritecourses/js/ajax.php');
        $this->content->text .= "<script>
        // fav management
        var favoriteManagement = function(e){
            e.preventDefault();
            
            var courseid = $(this).attr('data-id');
            var aca = $(this).attr('data-ac') || '';
            var obj = $(this);
            
            $.ajax({
              url: '" . $ajaxfavurl . "',
              type: 'POST',
              data: {'id': courseid, 'aca': aca},
              success: function(){
                if(obj.hasClass('unfav')){
                    obj.removeClass('unfav').addClass('fav');
                    obj.find('i').removeClass().addClass('fa fa-star');
                } else {
                    obj.removeClass('fav').addClass('unfav');
                    obj.find('i').removeClass().addClass('far fa-star');
                }  
              }
            });
        }
        
        var toggleDetail = function(e){
            e.preventDefault();
            
            var currentClass = $(this).attr('class');
            if(currentClass.indexOf('show-details') > -1){
                currentClass = currentClass.replace('show-details', 'hide-details');
            }else{
                currentClass = currentClass.replace('hide-details', 'show-details');
            }
            
            $(this).attr('class', currentClass);
            
            
            // $(this).toggleClass('show-details');
            $(this).parent().parent().find('.details').stop().toggle();
        }
        
        $('.block_mycourselist').on('click', '.show-details', toggleDetail);
        $('.block_mycourselist').on('click', '.hide-details', toggleDetail);
        $('.block_mycourselist').on('click', '.fav', favoriteManagement);
        $('.block_mycourselist').on('click', '.unfav', favoriteManagement);
        
        </script>";

        if($SCRIPT == '/my/seformer.php'){
            $this->content->text .= "<script>
                $(document).ready(function(){
                    $('.block_mycourselist').removeClass('block');
                    $('.block_mycourselist').addClass('se-former');
                    });
                </script>";
        }

        return $this->content;
    }
}


