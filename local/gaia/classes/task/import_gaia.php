<?php
namespace local_gaia\task;


class import_gaia extends \core\task\scheduled_task {
    public function get_name() {
        // Shown in admin screens
        return get_string('importgaiacronname', 'local_gaia');
    }

    public function execute() {
        global $CFG;
        require_once($CFG->dirroot . '/local/gaia/lib/GaiaImport.php');
        require_once($CFG->dirroot.'/local/magisterelib/databaseConnection.php');

        if(!file_exists($CFG->gaia_import_path) || !is_dir($CFG->gaia_import_path)){
            echo "Gaia directory not found!\n";
            echo 'Path: '.$CFG->gaia_import_path."\n";
            $event = \local_gaia\event\gaia_directory_not_found::create(array('context' => \context_system::instance()));
            $event->trigger();
            return;
        }

        $academies = get_magistere_academy_config();

        $dirs = scandir($CFG->gaia_import_path, SCANDIR_SORT_DESCENDING);

        if(count($dirs) == 0){
            echo "Nothing to do!\n";
            $event = \local_gaia\event\gaia_nothing_todo::create(array('context' => \context_system::instance()));
            $event->trigger();
            return;
        }

        // find last dir to process (dirs are iso dates)
        $dirtoprocess = 0;
        foreach ($dirs as $dir) {
            if (substr($dir, 0, 1) == '2' && is_numeric($dir) && intval($dirtoprocess) < intval($dir)) {
                $dirtoprocess = $dir;
            }
        }

        $config = get_config('local_gaia');

        // check if dgesco and esen db are avalaible
        if(($dgesco_db = \databaseConnection::instance()->get('dgesco')) === false){
            echo 'Unable to connect to DGESCO database.'."\n";
            exit;
        }

        if(($esen_db = \databaseConnection::instance()->get('ih2ef')) === false){
            echo 'Unable to connect to ESEN database.'."\n";
            exit;
        }

        $dgesco_transaction = $dgesco_db->start_delegated_transaction();
        $dgesco_db->execute('TRUNCATE {local_gaia_formations}');
        $dgesco_db->execute('TRUNCATE {local_gaia_intervenants}');
        $dgesco_db->execute('TRUNCATE {local_gaia_stagiaires}');

        $esen_transaction = $esen_db->start_delegated_transaction();
        $esen_db->execute('TRUNCATE {local_gaia_formations}');
        $esen_db->execute('TRUNCATE {local_gaia_intervenants}');
        $esen_db->execute('TRUNCATE {local_gaia_stagiaires}');

        foreach($academies as $aca => $daca){
            if(in_array($aca, array('hub', 'dgesco', 'ih2ef', 'cndp', 'efe', 'reseau-canope', 'frontal'))){
                $this->print_message($aca, 'Skip'."\n");
                continue;
            }

            /*
                        // To test on aca
                        if($aca != 'ac-nantes'){
                            $this->print_message($aca, 'Skip'."\n");
                            continue;
                        }
            */

            if(($aca_db = \databaseConnection::instance()->get($aca)) === false){
                echo 'Unable to connect to '.$aca.' database.'."\n";
                continue;
            };

            $this->print_message($aca, 'Starting import GAIA data');

            $start = microtime(true);

            // check if the academy have a GAIA code
            if(!isset(get_academies_gaia_code()[$CFG->academie_name])){
                $this->print_message($aca, 'Code gaia not found!');
                print_r(get_academies_gaia_code());

                $event = \local_gaia\event\gaia_code_not_exists::create(array('context' => \context_system::instance()));
                $event->trigger();

                $this->print_end_process($aca, $start);
                continue;
            }

            $gaiacode = get_academies_gaia_code()[$aca];

            // check if we have already processed the current dir
            if($config !== false && isset($config->{'last_directory_'.$aca})){
                $dirprocessed = $config->{'last_directory_'.$aca};

                if (intval($dirtoprocess) <= intval($dirprocessed)) {
                    // if dir to process is older than the dir we have processed (or is the same)
                    // do nothing
                    $this->print_message($aca, 'File already proccessed');
                    $event = \local_gaia\event\gaia_nothing_todo::create(array('context' => \context_system::instance()));
                    $event->trigger();

                    $this->print_end_process($aca, $start);
                    continue;
                }
            }

            $fileFormations = $CFG->gaia_import_path . $dirtoprocess . '/'.$gaiacode.'_Formations_'.$dirtoprocess.'.csv';
            $fileIntervenants = $CFG->gaia_import_path . $dirtoprocess . '/'.$gaiacode.'_Intervenants_'.$dirtoprocess.'.csv';
            $fileStagiaires = $CFG->gaia_import_path . $dirtoprocess . '/' . $gaiacode . '_Stagiaires_'.$dirtoprocess.'.csv';

            if(file_exists($fileFormations) && file_exists($fileIntervenants) && file_exists($fileStagiaires)){
                $import = new \GaiaImport($aca_db, $dgesco_db, $esen_db);
                $import->setAcaName($aca);
                $import->load($fileFormations, $fileIntervenants, $fileStagiaires);
            }else{
                $this->print_message($aca, 'File not found!');
                $this->print_message($aca, 'fileFormations: '.$fileFormations);
                $this->print_message($aca, 'fileIntervenants: '.$fileIntervenants);
                $this->print_message($aca, 'fileStagiaires: '.$fileStagiaires);
                $this->print_message($aca, 'dirtoprocess: '.$dirtoprocess);

                $event = \local_gaia\event\gaia_file_not_found::create(array('context' => \context_system::instance()));
                $event->trigger();

                $this->print_end_process($aca, $start);
                continue;
            }

            // finally save the processed dir
            // set_config('last_directory_'.$CFG->academie_name, $dirtoprocess, 'local_gaia');

            $this->print_end_process($aca, $start);

            $aca_db->dispose();
        }

        // purge dgesco and esen

        echo '['.date("Y-m-d H:i:s").'][dgesco] Start purge query 01'."\n";
        $sqlFormations = "DELETE FROM {local_gaia_formations} WHERE `startdate` < UNIX_TIMESTAMP(MAKEDATE(EXTRACT(YEAR FROM DATE_SUB(NOW(),INTERVAL 1 YEAR)),1))";
        $dgesco_db->execute($sqlFormations);
        // CSVImport::mysqli()->query($sqlFormations);
        echo '['.date("Y-m-d H:i:s").'][dgesco] End purge query 01'."\n";

        echo '['.date("Y-m-d H:i:s").'][dgesco] Start purge query 02'."\n";
        $sqlIntervenants = "DELETE FROM {local_gaia_intervenants} WHERE module_id NOT IN (SELECT DISTINCT gf.module_id FROM {local_gaia_formations} gf WHERE gf.table_name=table_name)";
        $dgesco_db->execute($sqlIntervenants);
        // CSVImport::mysqli()->query($sqlIntervenants);
        echo '['.date("Y-m-d H:i:s").'][dgesco] End purge query 02'."\n";

        echo '['.date("Y-m-d H:i:s").'][dgesco] Start purge query 03'."\n";
        //$sqlStagiaires = "DELETE FROM {local_gaia_stagiaires} WHERE session_id NOT IN (SELECT DISTINCT gf.session_id FROM {local_gaia_formations} gf WHERE gf.table_name=table_name)";
        //$dgesco_db->execute($sqlStagiaires);

        $tmp_table = 'mdl_temp_gaia_st_'.time();
        echo '['.date("Y-m-d H:i:s").'][dgesco] Start purge query 03 - CREATE TMP TABLE'."\n";
        $dgesco_db->execute("CREATE TABLE ".$tmp_table." LIKE {local_gaia_stagiaires}");
        echo '['.date("Y-m-d H:i:s").'][dgesco] Start purge query 03 - INSERT DATA IN TMP TABLE'."\n";
        $dgesco_db->execute("INSERT INTO ".$tmp_table." SELECT gs.* FROM {local_gaia_stagiaires} gs INNER JOIN {local_gaia_formations} gf ON(gf.table_name=gs.table_name AND gf.session_id=gs.session_id)");
        echo '['.date("Y-m-d H:i:s").'][dgesco] Start purge query 03 - TRUNCATE STAGIAIRE TABLE'."\n";
        $dgesco_db->execute("TRUNCATE {local_gaia_stagiaires}");
        echo '['.date("Y-m-d H:i:s").'][dgesco] Start purge query 03 - INSERT TMP DATE INTO STAGIAIRE TABLE'."\n";
        $dgesco_db->execute("INSERT INTO {local_gaia_stagiaires} SELECT * FROM ".$tmp_table);
        echo '['.date("Y-m-d H:i:s").'][dgesco] Start purge query 03 - DROP TMP TABLE'."\n";
        $dgesco_db->execute("DROP TABLE ".$tmp_table);

        // CSVImport::mysqli()->query($sqlStagiaires);
        echo '['.date("Y-m-d H:i:s").'][dgesco] End purge query 03'."\n";

        echo '['.date("Y-m-d H:i:s").'][dgesco] Start purge query 04'."\n";
        $sqlFormationsVide = "DELETE FROM {local_gaia_formations} WHERE id IN(SELECT * FROM (SELECT id FROM {local_gaia_formations} gf WHERE (SELECT COUNT(*) FROM {local_gaia_stagiaires} gs WHERE gs.table_name=gf.table_name AND gs.session_id = gf.session_id) = 0 AND (SELECT COUNT(*) FROM {local_gaia_intervenants} gi WHERE gi.table_name=gf.table_name AND gi.module_id = gf.module_id) = 0) AS tmp)";

        $dgesco_db->execute($sqlFormationsVide);
        // CSVImport::mysqli()->query($sqlStagiaires);
        echo '['.date("Y-m-d H:i:s").'][dgesco] End purge query 04'."\n";



        echo '['.date("Y-m-d H:i:s").'][esen] Start purge query 01'."\n";
        $sqlFormations = "DELETE FROM {local_gaia_formations} WHERE `startdate` < UNIX_TIMESTAMP(MAKEDATE(EXTRACT(YEAR FROM DATE_SUB(NOW(),INTERVAL 1 YEAR)),1))";
        $esen_db->execute($sqlFormations);
        // CSVImport::mysqli()->query($sqlFormations);
        echo '['.date("Y-m-d H:i:s").'][esen] End purge query 01'."\n";

        echo '['.date("Y-m-d H:i:s").'][esen] Start purge query 02'."\n";
        $sqlIntervenants = "DELETE FROM {local_gaia_intervenants} WHERE module_id NOT IN (SELECT DISTINCT gf.module_id FROM {local_gaia_formations} gf WHERE gf.table_name=table_name)";
        $esen_db->execute($sqlIntervenants);
        // CSVImport::mysqli()->query($sqlIntervenants);
        echo '['.date("Y-m-d H:i:s").'][esen] End purge query 02'."\n";

        echo '['.date("Y-m-d H:i:s").'][esen] Start purge query 03'."\n";
        $sqlStagiaires = "DELETE FROM {local_gaia_stagiaires} WHERE session_id NOT IN (SELECT DISTINCT gf.session_id FROM {local_gaia_formations} gf WHERE gf.table_name=table_name)";
        $esen_db->execute($sqlStagiaires);
        // CSVImport::mysqli()->query($sqlStagiaires);
        echo '['.date("Y-m-d H:i:s").'][esen] End purge query 03'."\n";

        echo '['.date("Y-m-d H:i:s").'][esen] Start purge query 04'."\n";
        $sqlFormationsVide = "DELETE FROM {local_gaia_formations} WHERE id IN(SELECT * FROM (SELECT id FROM {local_gaia_formations} gf WHERE (SELECT COUNT(*) FROM {local_gaia_stagiaires} gs WHERE gs.table_name=gf.table_name AND gs.session_id = gf.session_id) = 0 AND (SELECT COUNT(*) FROM {local_gaia_intervenants} gi WHERE gi.table_name=gf.table_name AND gi.module_id = gf.module_id) = 0) AS tmp)";
        $esen_db->execute($sqlFormationsVide);
        // CSVImport::mysqli()->query($sqlStagiaires);
        echo '['.date("Y-m-d H:i:s").'][esen] End purge query 04'."\n";


        try {
            $dgesco_transaction->allow_commit();
        }catch(Exception $e){
            print_r($e);
            $dgesco_transaction->rollback($e);
        }

        try {
            $esen_transaction->allow_commit();
        }catch(Exception $e){
            print_r($e);
            $esen_transaction->rollback($e);
        }
    }

    private function print_end_process($aca, $start){
        $end = microtime(true);
        $date = date('Y-m-d H:i:s');
        echo '['.$date.']['.$aca.'] End import GAIA data ('.number_format($end - $start,6)."s)\n\n";
    }

    private function print_message($aca, $message){
        $date = date('Y-m-d H:i:s');
        echo '['.$date.']['.$aca.'] '.$message."\n";
    }
}
