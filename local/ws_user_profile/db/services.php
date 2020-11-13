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

// We defined the web service functions to install.
$functions = array(
		'local_ws_user_profile_main' => array(
				'classname'   => 'local_ws_user_profile_external',
				'methodname'  => 'main',
				'classpath'   => 'local/ws_user_profile/externallib.php',
				'description' => 'Entry point functione',
				'type'        => 'read',
		),
        'local_ws_user_profile_update' => array(
                'classname'   => 'local_ws_user_profile_external',
                'methodname'  => 'update',
                'classpath'   => 'local/ws_user_profile/externallib.php',
                'description' => 'Update a user profile',
                'type'        => 'write',
        ),
        'local_ws_user_profile_get' => array(
        		'classname'   => 'local_ws_user_profile_external',
                'methodname'  => 'get',
                'classpath'   => 'local/ws_user_profile/externallib.php',
                'description' => 'Get a user profile',
                'type'        => 'read',
        )
);

// We define the services to install as pre-build services. A pre-build service is not editable by administrator.
$services = array(
        'User Profile Service' => array(
                'functions' => array ('local_ws_user_profile_main'),
                'restrictedusers' => 0,
                'enabled'=>1,
        		'shortname' => 'user_profile_webservice'
        )
);
