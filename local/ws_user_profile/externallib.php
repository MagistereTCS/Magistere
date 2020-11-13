<?php
require_once($CFG->libdir . "/externallib.php");

class local_ws_user_profile_external extends external_api {

	/**
	 * Entry point function
	 */
	public static function main_parameters() {
		return new external_function_parameters(
				array('fname' => new external_value(PARAM_TEXT),
						'fparams' => new external_value(PARAM_TEXT))
		);
	}
	
	public static function main_returns(){
		return new external_value(PARAM_TEXT, "{}");
	}
	
	public static function main($fname, $fparams) {
		global $DB, $USER, $CFG;
		 
		 
		//Parameter validation
		//REQUIRED
		$params = self::validate_parameters(self::main_parameters(),
				array('fname' => $fname, 'fparams' => $fparams));
		 
		//Context validation
		//OPTIONAL but in most web service it should present
		$context = context_user::instance($USER->id);
		self::validate_context($context);
		
		switch($fname){
			case 'update':
				return self::update($fparams);
			case 'get':
				return self::get($fparams);
			case 'update_user_mail':
				return self::update_user_mail($fparams);
		}
		 
		return "{}";
	}
	
    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function update_parameters() {
        return new external_function_parameters(
                array('data' => new external_value(PARAM_TEXT))
        );
    }
    
    public static function update_returns(){
    		return new external_value(PARAM_TEXT, "{}");
    } 
    
    public static function update($data) {
    	global $DB, $USER, $CFG;
    	
    	//Context validation
    	//OPTIONAL but in most web service it should present
    	$context = context_user::instance($USER->id);
    	self::validate_context($context);
    	
    	self::validate_parameters(self::update_parameters(), array('data' => $data));
    	
    	$data = json_decode($data, true);
    	
    	$username = $data['username'];
    	 
    	$recordToUpdate = $data['data'];
    	
    	$hasBeenUpdate = false;
   
    	//check if user exist
    	$user = $DB->get_record_sql('SELECT * FROM {user} WHERE auth = "shibboleth" AND username = "' . $username . '"', null, IGNORE_MULTIPLE);
    	
    	if($user !== false){
    		//update only allowed fields
    		foreach($CFG->ws_user_profile_allowed_fields as $field){
    			if(isset($recordToUpdate[$field])){
    				$user->{$field} = $recordToUpdate->{$field};
    			}
    		}

    		$hasBeenUpdate = $DB->update_record('user', $user);
    	}
    	return json_encode(array('has_been_update' => $hasBeenUpdate));
    }
    
    /**
     * Get Profil Function
     * @return external_function_parameters
     */
    public static function get_parameters() {
    	return new external_function_parameters(
    			array('data' => new external_value(PARAM_TEXT))
    	);
    }
    
    public static function get_returns(){
    	return new external_value(PARAM_TEXT, "{}");
    }
    
    public static function get($data) {
    	global $DB, $USER, $CFG;
    	 
    	 
    	//Parameter validation
    	//REQUIRED
    	$params = self::validate_parameters(self::get_parameters(),
    			array('data' => $data));
    	 
    	//Context validation
    	//OPTIONAL but in most web service it should present
    	$context = context_user::instance($USER->id);
    	self::validate_context($context);
    	
    	$data = json_decode($data, true);
    	$username = $data['username'];
    	
    	//check if user exist
    	$user = (array)$DB->get_record_sql('SELECT * FROM {user} WHERE auth = "shibboleth" AND username = "' . $username . '"', null, IGNORE_MULTIPLE);
    	
    	$ret = "{}";
    	
    	if($user !== false){
    		unset($user['password']);
    		
    		$user_mainaca = (array)$DB->get_record_sql('SELECT * FROM {user_info_data} WHERE userid = '.$user->id.' AND fieldid = (SELECT id FROM {user_info_field} WHERE shortname = "mainacademy")', null, IGNORE_MULTIPLE);
    		
    		$user['mainacademy'] = $user_mainaca['data'];
    		
    		$ret = json_encode($user);	
    	}
    	 
    	return $ret;
    }
    
    /**
     * Get Profil Function
     * @return external_function_parameters
     */
    public static function update_user_mail_parameters() {
    	return new external_function_parameters(
    			array('data' => new external_value(PARAM_TEXT))
    	);
    }
    
    public static function update_user_mail_returns(){
    	return new external_value(PARAM_TEXT, "{}");
    }
    
    public static function update_user_mail($data) {
    	global $DB, $USER, $CFG;
    	 
    	
    	//Parameter validation
    	//REQUIRED
    	$params = self::validate_parameters(self::update_user_mail_parameters(),
    			array('data' => $data));
    	
    	//Context validation
    	//OPTIONAL but in most web service it should present
    	$context = context_user::instance($USER->id);
    	self::validate_context($context);
    	
    	$hasBeenUpdate = false;
    	
    	$data = json_decode($data, true);
    	$userNewMail = $data['userNewMail'];
    	$userNumen = $data['userNumen'];
    	
    	if (strlen($userNumen) == 64)
    	{
    		
    		
    		$ctemail = $userNewMail;
    		$employeeNumber = $userNumen;
    		 
    		$user = get_complete_user_data('email', $ctemail);
    		
    		if ($user !== false && $user->suspended == 0 && $user->deleted == 0 && $user->auth != "shibboleth" && $user->id != null)
    		{
    				
    			$tmp_user = get_complete_user_data('username', $employeeNumber);
    				
    			if ($tmp_user !== false && $user->deleted == 0 && $user->suspended == 0)
    			{
    				require_once($CFG->dirroot.'/admin/tool/mergeusers/lib/mergeusertool.php');
    				error_log("Merging user ".$tmp_user->id." with ".$user->id);
    				$mut = new MergeUserTool();
    				$ret = $mut->merge($tmp_user->id, $user->id);
    				if ($ret[0] == true)
    				{
    					delete_user($user);
    				}
    		
    			}else{
    		
    				// On change le mode d'authentification
    				$user->auth = 'shibboleth';
    				// On recupere les valeurs de username et password
    				$user->username = $employeeNumber;
    				$user_params->username = $user->username;
    				$user_params->password = $user->password = 'ShibboLeth1#!';
    				// On laisse le plugin mettre a jour les informations de l'utilisateur si tout se passe bien, sinon modifier ici
    		
    				user_update_user($user);
    				$result = true;
    			}
    		}
    		
    		
    		
    		
	    	//check if user exist
	    	$user1 = $DB->get_record_sql('SELECT * FROM {user} WHERE auth = "shibboleth" AND username = "' . $userNumen . '"', null, IGNORE_MULTIPLE);
	    	
	    	$ret = "{}";
	    	
	    	if($user1 !== false && $user1->email != $userNewMail)
	    	{
	    		
    			$DB->execute('UPDATE {user} SET email = "'.$userNewMail.'" WHERE username = "'.$userNumen.'"');
	    		
	    		$hasBeenUpdate = true;
	    	}
    	}
    	 
    	return json_encode(array('updated' => $hasBeenUpdate));
    }
}
