<?php


class GaiaImport
{
    private $esen_db;
    private $aca_db;
    private $dgesco_db;

    private $acaname;

    public function __construct(&$aca_db, &$dgesco_db, &$esen_db)
    {
        $this->aca_db = &$aca_db;
        $this->dgesco_db = &$dgesco_db;
        $this->esen_db = &$esen_db;

        $this->acaname = '';
    }

    public function setAcaName($acaname){
        $this->acaname = $acaname;
    }

    public function load($fileFormations, $fileIntervenants, $fileStagiaires)
	{
		global $CFG, $DB;
	
		if(file_exists($fileFormations) == false){
			$this->log($fileFormations, 'file doesn\'t exist');
			return;
		}
	
		if(file_exists($fileIntervenants) == false){
			$this->log($fileIntervenants, 'file doesn\'t exist');
			return;
		}
	
		if(file_exists($fileStagiaires) == false){
			$this->log($fileStagiaires, 'file doesn\'t exist');
			return;
		}

		$transaction = $this->aca_db->start_delegated_transaction();
		
		try{
		    $this->log($fileFormations, 'truncate table local_gaia_formations');
			$this->aca_db->execute('TRUNCATE TABLE {local_gaia_formations}');
            $this->log($fileFormations, 'end truncate table local_gaia_formations');

			$this->log($fileFormations, 'start import gaia_formations');
			$importFormations = new ImportFormations($fileFormations);
			$importFormations->setAcaDB($this->aca_db);
			$importFormations->setDgescoDB($this->dgesco_db);
			$importFormations->setEsenDB($this->esen_db);
			$importFormations->import();
            $this->log($fileFormations, 'end import gaia_formations');

			$this->log($fileIntervenants, 'truncate local_gaia_intervenants');
			$this->aca_db->execute('TRUNCATE TABLE {local_gaia_intervenants}');
			$this->log($fileIntervenants, 'end truncate table local_gaia_intervenants');

			$national_module_session = $importFormations->getNationalModuleSession();

			$this->log($fileIntervenants, 'start import local_gaia_intervenants');
			$importIntervenants = new ImportIntervenants($fileIntervenants);
			$importIntervenants->setAcaDB($this->aca_db);
			$importIntervenants->setDgescoDB($this->dgesco_db);
			$importIntervenants->setEsenDB($this->esen_db);
			$importIntervenants->setNationalModule($national_module_session['modules']);
			$importIntervenants->import();
			$this->log($fileIntervenants, 'end import local_gaia_intervenants');


			$this->log($fileStagiaires, 'truncate table local_gaia_stagiaires');
			$this->aca_db->execute('TRUNCATE TABLE {local_gaia_stagiaires}');
			$this->log($fileStagiaires, 'end truncate table local_gaia_stagiaires');

			$this->log($fileStagiaires, 'start import local_gaia_stagiaires');
			$importStagiaires = new ImportStagiaires($fileStagiaires);
			$importStagiaires->setAcaDB($this->aca_db);
			$importStagiaires->setDgescoDB($this->dgesco_db);
			$importStagiaires->setEsenDB($this->esen_db);
			$importStagiaires->setNationalSession($national_module_session['sessions']);
			$importStagiaires->import();
			$this->log($fileStagiaires, 'end import local_gaia_stagiaires');

			$this->log($fileStagiaires, 'start purge');
			$this->purgeOldData();
			$this->log($fileStagiaires, 'end purge');

			$this->log($fileStagiaires, 'start commit transaction');
			$transaction->allow_commit();
			$this->log($fileStagiaires, 'end commit transaction');
		}catch(Exception $e){
		    print_r($e);
			$transaction->rollback($e);
		}
	}

	function purgeOldData()
	{
		global $CFG;

		$name = $this->acaname;

		echo '['.date("Y-m-d H:i:s").']['.$name.'] Start purge query 01'."\n";
		$sqlFormations = "DELETE FROM {local_gaia_formations} WHERE `startdate` < UNIX_TIMESTAMP(MAKEDATE(EXTRACT(YEAR FROM DATE_SUB(NOW(),INTERVAL 1 YEAR)),1))";
		$this->aca_db->execute($sqlFormations);
		// CSVImport::mysqli()->query($sqlFormations);
		echo '['.date("Y-m-d H:i:s").']['.$name.'] End purge query 01'."\n";
		
		echo '['.date("Y-m-d H:i:s").']['.$name.'] Start purge query 02'."\n";
		$sqlIntervenants = "DELETE FROM {local_gaia_intervenants} WHERE module_id NOT IN (SELECT DISTINCT gf.module_id FROM {local_gaia_formations} gf WHERE gf.table_name=table_name)";
		$this->aca_db->execute($sqlIntervenants);
		// CSVImport::mysqli()->query($sqlIntervenants);
		echo '['.date("Y-m-d H:i:s").']['.$name.'] End purge query 02'."\n";
		
		echo '['.date("Y-m-d H:i:s").']['.$name.'] Start purge query 03'."\n";
		$sqlStagiaires = "DELETE FROM {local_gaia_stagiaires} WHERE session_id NOT IN (SELECT DISTINCT gf.session_id FROM {local_gaia_formations} gf WHERE gf.table_name=table_name)";
		$this->aca_db->execute($sqlStagiaires);
		// CSVImport::mysqli()->query($sqlStagiaires);
		echo '['.date("Y-m-d H:i:s").']['.$name.'] End purge query 03'."\n";
	}	
	
	function log($filename, $message)
	{
		global $DB;

		$data = new stdClass();
	
		$data->filename = substr($filename, 0, 255);
		$data->message = $message;
		$data->date = time();
	
		echo '['.date('Y-m-d H:i:s', $data->date).']['.$this->acaname.'] '.$filename.': '.$message."\n";
		
		$this->aca_db->insert_record('local_gaia_import_logs', $data);
	}
}

abstract class CSVImport
{
	private $filepath;
	
	protected $dbdata;
	protected $nationaldata;

	protected $esen_db;
    protected $aca_db;
    protected $dgesco_db;

    protected $table_name;
	
	public function __construct($filepath)
	{
		$this->filepath = $filepath;
		$this->dbdata = array();
		$this->esen_db = null;
		$this->aca_db = null;
		$this->dgesco_db = null;
        $this->esen_data = array();
	    $this->dgesco_data = array();
	}

	public function setAcaDB(&$aca_db){
	    $this->aca_db = &$aca_db;
    }

    public function setDgescoDB(&$dgesco_db){
	    $this->dgesco_db = &$dgesco_db;
    }

    public function setEsenDB(&$esen_db){
	    $this->esen_db = &$esen_db;
    }
	
	public function import()
	{
		$f = fopen($this->filepath, 'r');
		
		$sizeTransaction = 1000;
			
		while (($line = fgets($f)) !== false) {
			$line = trim($line);
			if(count($line) == 0){
				continue;
			}

			if(count($this->nationaldata) == $sizeTransaction){
                $this->insert_national_data();
            }

            if(count($this->dbdata) == $sizeTransaction){
               $this->insert_data();
            }

			
			$line = iconv('CP1252', 'UTF-8//IGNORE', $line);
			
			if($line !== false){
				if($this->checkLine($line)){
					$data = explode("|", $line);
					$this->processCSVLine($data);
				}else{
					echo 'FAIL: '.$line."\n";
				}
			}else{
				$this->log($this->filepath, 'icon error for string : [' . $line . ']');
			}
		}

		if(count($this->nationaldata) > 0){
            $this->insert_national_data();
        }

        if(count($this->dbdata) > 0){
           $this->insert_data();
        }
		
		fclose($f);
	}
	
	function log($message)
	{
		$data = new stdClass();
	
		$data->filename = substr($this->filepath, 0, 255);
		$data->message = $message;
		$data->date = time();
	
		$this->aca_db->insert_record('local_gaia_import_logs', $data);
	}
	
	
	abstract protected function processCSVLine($data);
    abstract protected function insert_national_data();
    abstract protected function insert_data();

	abstract protected function checkLine($line);
}

class ImportIntervenants extends CSVImport
{
    private $national_module;
    private $national_arguments;
    private $dbdata_arguments;

    public function __construct($filepath)
    {
        parent::__construct($filepath);
        $this->table_name = 'local_gaia_intervenants';
        $this->national_module = array();
        $this->national_arguments = '';
        $this->dbdata_arguments = '';
    }

    public function setNationalModule(&$nationalModule){
        $this->national_module =& $nationalModule;
    }

    protected function checkLine($line){
		return (substr_count($line, '|') == 5);
	}

	protected function processCSVLine($data){
		$d = new stdClass();

		for($i = 0; $i < count($data)-1; $i++){
			if(strlen(trim($data[$i])) < 1){
				//echo print_r($data, true) . "\n";
				return;
			}
		}

		$d->table_name = $data[0];
		$d->module_id = $data[1];
		$d->name = trim(mb_substr($data[2], 0, 255, "UTF-8"));
		$d->firstname = trim(mb_substr($data[3], 0, 255, "UTF-8"));
		$d->email = mb_strtolower(trim(mb_substr($data[4], 0, 255, "UTF-8")), "UTF-8");

		if(isset($this->national_module[$d->module_id.$d->table_name])){
		    $this->nationaldata[] = $d->table_name; // (strncmp($d->table_name, 'ACA', 3) === 0 ? 'ACANAT' : 'DEPNAT');

		    // map module_id
	        $this->nationaldata[] = $this->national_module[$d->module_id.$d->table_name];

	        $this->nationaldata[] = $d->name;
	        $this->nationaldata[] = $d->firstname;
	        $this->nationaldata[] = $d->email;
	        $this->national_arguments .= '(?, ?, ?, ?, ?),';
        }else{
            $this->dbdata[] = $d->table_name;
	        $this->dbdata[] = $d->module_id;
	        $this->dbdata[] = $d->name;
	        $this->dbdata[] = $d->firstname;
	        $this->dbdata[] = $d->email;
	        $this->dbdata_arguments .= '(?, ?, ?, ?, ?),';
        }
	}

    protected function insert_national_data(){
        $this->national_arguments = substr($this->national_arguments, 0, strlen($this->national_arguments)-1);

        $this->dgesco_db->execute('INSERT IGNORE INTO {local_gaia_intervenants} (table_name, module_id, name, firstname, email) VALUES '.$this->national_arguments, $this->nationaldata);
        $this->esen_db->execute('INSERT IGNORE INTO {local_gaia_intervenants} (table_name, module_id, name, firstname, email) VALUES '.$this->national_arguments, $this->nationaldata);

        $this->nationaldata = array();
        $this->national_arguments = '';
    }

    protected function insert_data(){
        $this->dbdata_arguments = substr($this->dbdata_arguments, 0, strlen($this->dbdata_arguments)-1);

        $this->aca_db->execute('INSERT IGNORE INTO {local_gaia_intervenants} (table_name, module_id, name, firstname, email) VALUES '.$this->dbdata_arguments, $this->dbdata);
        $this->dbdata = array();
        $this->dbdata_arguments = '';
    }
}

class ImportFormations extends CSVImport
{
    private $national_module_session;

    public static $map_module_session_ids = array();

    public function __construct($filepath)
    {
        parent::__construct($filepath);
        $this->national_module_session = array('modules' => array(), 'sessions' => array());
    }

    public function getNationalModuleSession(){
        return $this->national_module_session;
    }

    protected function checkLine($line){
		return (substr_count($line, '|') == 13);
	}
	
	protected function processCSVLine($data){
		
		for($i = 0; $i < count($data)-2; $i++){
			if(strlen(trim($data[$i])) < 1){
				//echo print_r($data, true) . "\n";
				return;
			}
		}
		
		$d = new stdClass();
		$d->table_name = $data[0];
		$d->dispositif_id = mb_substr($data[1], 0, 10, "UTF-8");
		$d->dispositif_name = trim(mb_substr($data[2], 0, 250, "UTF-8"));
		$d->module_id = $data[3];
		$d->module_name = trim(mb_substr($data[4], 0, 250, "UTF-8"));
		$d->session_id = $data[5];
		$d->group_number = mb_substr($data[6], 0, 2, "UTF-8");
			
		$date = strptime($data[7] . ' ' . $data[8], '%d/%m/%Y %H:%M');
		if($date === false){
			//echo print_r($data, true) . "\n";
			return;
		}
		
		$d->startdate = mktime($date['tm_hour'], $date['tm_min'], $date['tm_sec'], $date['tm_mon']+1, $date['tm_mday'], $date['tm_year']+1900);
		
		$date = strptime($data[9] . ' ' . $data[10], '%d/%m/%Y %H:%M');
		if($date === false){
			//echo print_r($data, true) . "\n";
			return;
		}
		
		$d->enddate = mktime($date['tm_hour'], $date['tm_min'], $date['tm_sec'], $date['tm_mon']+1, $date['tm_mday'], $date['tm_year']+1900);
			
		$d->place_type = mb_substr($data[11], 0, 1);
		$d->formation_place = trim(mb_substr($data[12], 0, 255, "UTF-8"));

		if($d->dispositif_id[2] == 'N'){

		    //$newtablename = (strncmp($d->table_name, 'ACA', 3) === 0 ? 'ACANAT' : 'DEPNAT');

            $kmodule = $d->dispositif_id.$d->module_name.$d->startdate.$d->enddate.$d->formation_place.$d->table_name;

		    if(!isset(self::$map_module_session_ids[$kmodule])){
                self::$map_module_session_ids[$kmodule] = array('mid' => $d->module_id, 'sid' => $d->session_id);
                $this->nationaldata[] = $d;
            }
            $this->national_module_session['modules'][$d->module_id.$d->table_name] = self::$map_module_session_ids[$kmodule]['mid'];
            $this->national_module_session['sessions'][$d->session_id.$d->table_name] = self::$map_module_session_ids[$kmodule]['sid'];
        }else{
            $this->dbdata[] = $d;
        }
	}

    protected function insert_national_data(){
        $this->dgesco_db->insert_records('local_gaia_formations', $this->nationaldata);
        $this->esen_db->insert_records('local_gaia_formations', $this->nationaldata);
        $this->nationaldata = array();
    }

    protected function insert_data(){
        $this->aca_db->insert_records('local_gaia_formations', $this->dbdata);
        $this->dbdata = array();
    }
}

class ImportStagiaires extends CSVImport
{
    private $national_arguments;
    private $dbdata_arguments;
    private $national_session;

    public function __construct($filepath)
    {
        parent::__construct($filepath);
        $this->national_session = array();
        $this->national_arguments = '';
        $this->dbdata_arguments = '';
    }

    public function setNationalSession(&$nationalSession){
        $this->national_session =& $nationalSession;
    }

    protected function checkLine($line){
		return (substr_count($line, '|') == 4);
	}
	
	protected function processCSVLine($data){
		
		foreach($data as $da){
			if(strlen(trim($da)) < 1){
				//echo print_r($data, true) . "\n";
				return;
			}
		}
		
		$d = new stdClass();
		
		$d->table_name = $data[0];
		$d->session_id = $data[1];
		$d->name = trim(mb_substr($data[2], 0, 255, "UTF-8"));
		$d->firstname = trim(mb_substr($data[3], 0, 255, "UTF-8"));
		$d->email = mb_strtolower(trim(mb_substr($data[4], 0, 255, "UTF-8")), "UTF-8");


		if(isset($this->national_session[$d->session_id.$d->table_name])){
		    $this->nationaldata[] = $d->table_name; //(strncmp($d->table_name, 'ACA', 3) === 0 ? 'ACANAT' : 'DEPNAT');

		    // map module_id
	        $this->nationaldata[] = $this->national_session[$d->session_id.$d->table_name];
	        $this->nationaldata[] = $d->name;
	        $this->nationaldata[] = $d->firstname;
	        $this->nationaldata[] = $d->email;
	        $this->national_arguments .= '(?, ?, ?, ?, ?),';

	        /*
		    $d->session_id = $this->national_session[$d->session_id.$d->table_name];
		    $d->table_name = (strncmp($d->table_name, 'ACA', 3) === 0 ? 'ACANAT' : 'DEPNAT');
            $this->nationaldata[] = $d;
            */
            //print_r('national stagiaire found! => '."\n");
        }else{
            $this->dbdata[] = $d->table_name;
	        $this->dbdata[] = $d->session_id;
	        $this->dbdata[] = $d->name;
	        $this->dbdata[] = $d->firstname;
	        $this->dbdata[] = $d->email;
	        $this->dbdata_arguments .= '(?, ?, ?, ?, ?),';
        }

	}

    protected function insert_national_data(){
	    $this->national_arguments = substr($this->national_arguments, 0, strlen($this->national_arguments)-1);

        $this->dgesco_db->execute('INSERT IGNORE INTO {local_gaia_stagiaires} (table_name, session_id, name, firstname, email) VALUES '.$this->national_arguments, $this->nationaldata);
        $this->esen_db->execute('INSERT IGNORE INTO {local_gaia_stagiaires} (table_name, session_id, name, firstname, email) VALUES '.$this->national_arguments, $this->nationaldata);

        $this->nationaldata = array();
        $this->national_arguments = '';

        /*
        $this->dgesco_db->insert_records('local_gaia_stagiaires', $this->nationaldata);
        $this->esen_db->insert_records('local_gaia_stagiaires', $this->nationaldata);
        $this->nationaldata = array();
        */
    }

    protected function insert_data(){
        $this->dbdata_arguments = substr($this->dbdata_arguments, 0, strlen($this->dbdata_arguments)-1);

        $this->aca_db->execute('INSERT IGNORE INTO {local_gaia_stagiaires} (table_name, session_id, name, firstname, email) VALUES '.$this->dbdata_arguments, $this->dbdata);
        $this->dbdata = array();
        $this->dbdata_arguments = '';
        /*
        $this->aca_db->insert_records('local_gaia_stagiaires', $this->dbdata);
        $this->dbdata = array();
        */
    }
}
