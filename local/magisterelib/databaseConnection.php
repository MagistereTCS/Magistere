<?php

// databaseConnection::instance()->get('ac-paris')->get_record();
// require_once($CFG->dirroot.'/local/magisterelib/databaseConnection.php');

class databaseConnection
{
	private static $static_singleton = null;
	
	// singletons
	private $dbConnections = array();
	
	/**
	 * Return the singleton
	 */
	static function instance()
	{
		if (databaseConnection::$static_singleton == null)
		{
			databaseConnection::$static_singleton = new databaseConnection();
		}
		return databaseConnection::$static_singleton;
	}
	
	protected function __construct()
	{
	}
	
	/***
	 * 
	 * @param unknown $academie_name The academy name (ex: ac-paris, ac-nice)
	 * @return mixed Return the moodle_database instance or false is the connection failed
	 */
	function get($academy_name)
	{
		global $CFG;
		$academies = get_magistere_academy_config();
		if (!array_key_exists($academy_name,$academies))
		{
			return false;
		}
		
		// Singleton
		if (array_key_exists($academy_name,$this->dbConnections))
		{
			return $this->dbConnections[$academy_name];
		}
		
		
		$academy = $academies[$academy_name];
		
		$database_name = $CFG->db_prefix.$academy['shortname'];
		$dbConnection = $this->dbConnection($academy['sql_server'], $academy['sql_user'], $academy['sql_password'], $database_name);
		
		if ($dbConnection !== false)
		{
			$this->dbConnections[$academy_name] = $dbConnection;
		}
		
		return $dbConnection;
	}

	private function dbConnection($host,$user,$password,$database)
	{
		global $CFG;
		
		$connection = null;
		
		if (!$connection = moodle_database::get_driver_instance($CFG->dbtype, $CFG->dblibrary)) {
			throw new dml_exception('dbdriverproblem', "Unknown driver $CFG->dblibrary/$CFG->dbtype");
		}

		try {
			$connection->connect($host, $user, $password, $database, $CFG->prefix, $CFG->dboptions);
		} catch (moodle_exception $e) {
			error_log($CFG->academie_name.'##'.__FILE__.'##'.$e->getMessage());
			return false;
			//throw $e;
		}
	
		return $connection;
	}
	
	
}
