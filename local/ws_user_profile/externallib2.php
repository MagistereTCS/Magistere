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
			$user = (array)$user;
    		//update only allowed fields
    		foreach($CFG->ws_user_profile_allowed_fields as $field){
    			fputs($fp, print_r($field . "\n", true));
    			if(isset($recordToUpdate[$field])){
    				$user[$field] = $recordToUpdate[$field];
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
    		
    		$ret = json_encode($user);	
    	}
    	 
    	return $ret;
    }
}
