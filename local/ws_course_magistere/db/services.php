<?php

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
 * Web service local plugin template external functions and service definitions.
 *
 * @package    localws_course_magistere
 * @copyright  2011 Jerome Mouneyrac
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// We defined the web service functions to install.
$functions = array(
        'local_ws_course_magistere_course_magistere' => array(
                'classname'   => 'local_ws_course_magistere_external',
                'methodname'  => 'course_magistere',
                'classpath'   => 'local/ws_course_magistere/externallib.php',
                'description' => 'Return Hello World FIRSTNAME. Can change the text (Hello World) sending a new text as parameter',
                'type'        => 'read',
        ),
        
        'local_ws_course_magistere_get_count_participant' => array(
                'classname'   => 'local_ws_course_magistere_external',
                'methodname'  => 'get_count_participant',
                'classpath'   => 'local/ws_course_magistere/externallib.php',
                'description' => 'Return Hello World FIRSTNAME. Can change the text (Hello World) sending a new text as parameter',
                'type'        => 'read',
        ),
);

// We define the services to install as pre-build services. A pre-build service is not editable by administrator.
$services = array(
        'Course Magistere Service' => array(
                'functions' => array ('local_ws_course_magistere_course_magistere', 'local_ws_course_magistere_get_count_participant'),
                'restrictedusers' => 0,
                'enabled'=>1,
        		'shortname' => 'course_magistere_service'
        )
);
