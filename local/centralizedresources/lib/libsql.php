<?php

function cr_db_connection()
{
	global $CFG;
	$conn = new PDO('mysql:host='.$CFG->centralized_dbhost.';dbname='.$CFG->centralized_dbname, $CFG->centralized_dbuser, $CFG->centralized_dbpass);
	$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

	return $conn;
}

function sql_insert($data)
{
	$table = $data['table'];
	$conn = $data['dbconn'];
	
	unset($data['table']);
	unset($data['dbconn']);
	
	$query = "INSERT INTO " . $table . " 
			(" . implode(', ',array_keys($data)) . ") 
			VALUES (" . implode(', ', array_values($data)) . ")";
	
// 	echo '<pre>';
//	echo $query;
// 	echo '</pre>';
	//die;
		
	$conn->query($query);
	
	return $conn->lastInsertId();
}

function sql_select($data)
{
	$table = $data['table'];
	$conn = $data['dbconn'];
	
	unset($data['table']);
	unset($data['dbconn']);
	
	if(empty($data['select']))
	{
		$data['select'] = array('*');
	}
	
	$query = "SELECT " . implode(',', $data['select']) . " ";
	
	if(!empty($table))
		$query .= " FROM $table ";
		
	if(!empty($data['join']))
		$query .= " " . $data['join'] . " ";
	
	if(!empty($data['where']))
		$query .= " WHERE " . $data['where'] . " ";
	
	if(!empty($data['orderby']))
		$query .= " ORDER BY " . $data["orderby"] . " ";
	
	if(!empty($data['limit'])){
		$query .= " LIMIT " . $data['limit'] . " ";
	}	
	
	$result = $conn->prepare($query);
	$result->execute();
	
	return $result->fetchAll(PDO::FETCH_CLASS);
}

function getConn()
{
	global $CFG, $CDB;
	// singleton
	if (!isset($CDB)) {

		if (!$CDB = moodle_database::get_driver_instance($CFG->dbtype, $CFG->dblibrary)) {
			throw new dml_exception('dbdriverproblem', "Unknown driver $CFG->dblibrary/$CFG->dbtype");
		}

		try {
			$CDB->connect($CFG->centralized_dbhost, $CFG->centralized_dbuser, $CFG->centralized_dbpass, $CFG->centralized_dbname, '', $CFG->dboptions);
		} catch (moodle_exception $e) {
			if (empty($CFG->noemailever) and !empty($CFG->emailconnectionerrorsto)) {
				if (file_exists($CFG->dataroot.'/emailcount')){
					$fp = @fopen($CFG->dataroot.'/emailcount', 'r');
					$content = @fread($fp, 24);
					@fclose($fp);
					if((time() - (int)$content) > 600){
						//email directly rather than using messaging
						@mail($CFG->emailconnectionerrorsto,
								'WARNING: Database connection error: '.$CFG->wwwroot,
								'Connection error: '.$CFG->wwwroot);
						$fp = @fopen($CFG->dataroot.'/emailcount', 'w');
						@fwrite($fp, time());
					}
				} else {
					//email directly rather than using messaging
					@mail($CFG->emailconnectionerrorsto,
							'WARNING: Database connection error: '.$CFG->wwwroot,
							'Connection error: '.$CFG->wwwroot);
					$fp = @fopen($CFG->dataroot.'/emailcount', 'w');
					@fwrite($fp, time());
				}
			}
			// rethrow the exception
			throw $e;
		}
	}

	return $CDB;
}