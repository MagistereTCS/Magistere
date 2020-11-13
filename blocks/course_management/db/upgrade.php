<?php

function xmldb_block_course_management_upgrade($oldversion)
{
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    $result = true;
    echo $oldversion;
    if ($oldversion < 2013101511) {
        // Define table indexation_moodle to be created
        $table = new xmldb_table('indexation_moodle');
        // Adding fields to table indexation_moodle
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('course_id', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null);
        $table->add_field('nom_parcours', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('objectifs', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('description', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('niveau', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('discipline', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('public_target', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('collection', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('tps_a_distance', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null);
        $table->add_field('tps_en_presence', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null);
        $table->add_field('accompagnement', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('origine', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('liste_auteurs', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('contact_auteurs', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('contributeurs', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('validation', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('derniere_maj', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null);
        // Adding keys to table indexation_moodle
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        //add unicity index
        // $column = array('course_id');
        // $index = new xmldb_index('course_unicity', XMLDB_INDEX_UNIQUE,$column );
        // $table->addIndex($index);

        $table->add_index('course_unicity', XMLDB_INDEX_UNIQUE, array('course_id'));

        // Conditionally launch create table for indexation_moodle
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // course_management savepoint reached
        // upgrade_block_savepoint(true, XXXXXXXXXX, 'course_management');


        // Define table indexation_level to be created
        $table = new xmldb_table('indexation_level');
        // Adding fields to table indexation_lvl_discipline
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('name', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);

        // Adding keys to table indexation_lvl_discipline
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for indexation_lvl_discipline
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table indexation_level to be created
        $table = new xmldb_table('indexation_discipline');
        // Adding fields to table indexation_lvl_discipline
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('levelid', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null);
        $table->add_field('name', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);

        // Adding keys to table indexation_lvl_discipline
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for indexation_lvl_discipline
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        insert_disciplines($oldversion);

        // course_management savepoint reached
        upgrade_block_savepoint(true, 2013101511, 'course_management');

    }

    // PBO 20150126 #291 - Nouvelle définition de la table indexation_moodle
    if ($oldversion < 2015012601) {
        $indexationTable = new xmldb_table('indexation_moodle');
        $disciplineTable = new xmldb_table('indexation_discipline');
        $levelTable      = new xmldb_table('indexation_level');
        $targetTable     = new xmldb_table('indexation_target');
        $espeOriginTable = new xmldb_table('t_origine_espe');

        // ajout du champ "mots-clés" (champ texte)
        if (!$dbman->field_exists($indexationTable->getName(), 'keywords')) {
            $dbman->add_field(
                $indexationTable,
                new xmldb_field('keywords', XMLDB_TYPE_TEXT)
            );
        }

        // ajout du champ "département" (liste déroulante)
        if (!$dbman->field_exists($indexationTable->getName(), 'department')) {
            $dbman->add_field($indexationTable, new xmldb_field('department', XMLDB_TYPE_INTEGER, '11', null));
        }

        // ajout du champ "origine_espe" (liste déroulante)
        if (!$dbman->field_exists($indexationTable->getName(), 'origin_espe')) {
            $dbman->add_field($indexationTable, new xmldb_field('origin_espe', XMLDB_TYPE_INTEGER, '11', null));
        }

        // ajout du champ "publication"
        if (!$dbman->field_exists($indexationTable->getName(), 'shared_offer')) {
            $dbman->add_field(
                $indexationTable,
                new xmldb_field('shared_offer', XMLDB_TYPE_INTEGER, '1', null, null, null, 0)
            );
        }

        // suppression des champs "contact_auteurs", "contributeurs", "niveau", "discipline", "public_target"
        $deletion = array('niveau', 'discipline', 'public_target', 'contributeurs');
        foreach ($deletion as $fieldname) {
            if ($dbman->field_exists($indexationTable->getName(), $fieldname)) {
                $dbman->drop_field($indexationTable, new xmldb_field($fieldname));
            }
        }

        // renommage de la table "indexation_discipline"
        $newName = 'indexation_domain';
        if ($dbman->table_exists($disciplineTable->getName())) {
            $dbman->rename_table($disciplineTable, $newName);
        }
        $disciplineTable->setName($newName);

        // suppression du champ "levelid" sur "indexation_discipline"
        if ($dbman->table_exists($disciplineTable->getName()) && $dbman->field_exists($disciplineTable, 'levelid')) {
            $dbman->drop_field($disciplineTable, new xmldb_field('levelid'));
        }

        // création de la table "indexation_target"
        if (!$dbman->table_exists($targetTable->getName())) {
            $targetTable->add_field('id', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
            $targetTable->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL);
            $targetTable->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

            $dbman->create_table($targetTable);
        }

        // création de la table "t_origine_espe"
        if (!$dbman->table_exists($espeOriginTable->getName())) {
            $espeOriginTable->add_field('id', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
            $espeOriginTable->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL);
            $espeOriginTable->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

            $dbman->create_table($espeOriginTable);
        }

        // création des relations pour les tables "domain", "level" et "target"
        $relationTables = array($disciplineTable, $levelTable, $targetTable);
        foreach ($relationTables as $table) {
            $tableShortname = substr($table->getName(), strlen('indexation_'));
            $relationTable  = new xmldb_table('indexation_index_' . $tableShortname);
            if (!$dbman->table_exists($relationTable)) {
                $relationTable->add_field('indexation_id', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL);
                $relationTable->add_field($tableShortname . '_id', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL);
                $relationTable->add_index(
                    'primary',
                    XMLDB_KEY_PRIMARY,
                    array('indexation_id', $tableShortname . '_id')
                );

                $dbman->create_table($relationTable);
            }
        }

        // ajout des données d'amorcage
        $data = array(
            'indexation_level'  => array(
                'Maternelle',
                'Elémentaire',
                'Collège',
                'Lycée GT',
                'Lycée pro',
                'Enseignement supérieur',
                'Autre'
            ),
            'indexation_domain' => array(
                'Piloter, former, évaluer',
                'Accompagner les élèves',
                'Vivre ensemble',
                'Se former dans des champs disciplinaires',
                'Utiliser le numérique'
            ),
            'indexation_target' => array(
                'Enseignants',
                'Directeurs d\'école',
                'Formateurs',
                'Personnels de direction',
                'Inspecteurs',
                'Personnels de vie scolaire'
            ),
            't_origine_espe'    => array(
                'Aix-marseille',
                'Amiens',
                'Aquitaine',
                'Bourgogne',
                'Bretagne',
                'Caen',
                'Centre Val de Loire',
                'Clermont-Auvergne',
                'Corse',
                'Créteil',
                'Franche-Comté',
                'Grenoble',
                'Guadeloupe',
                'Guyane',
                'Langedoc-Roussillon',
                'La réunion',
                'Lille Nord de France',
                'Limoges',
                'Lorraine',
                'Lyon',
                'Polynésie française',
                'Reims',
                'Rouen',
                'Strasbourg',
                'Toulouse Midi-Pyrénées',
                'Versailles',
                'Nouvelle-Calédonie'
            )
        );

        foreach ($data as $table => $lines) {
            $trans = $DB->start_delegated_transaction();
            try {
                // empty the table and reset the auto-increment counter
                $DB->execute(sprintf('TRUNCATE %s%s', $DB->get_prefix(), $table));

                foreach ($lines as $line) {
                    $dataObject       = new stdClass();
                    $dataObject->name = $line;
                    $DB->insert_record($table, $dataObject, false);
                }

                $DB->commit_delegated_transaction($trans);
            } catch (Exception $e) {
                $DB->rollback_delegated_transaction($trans, $e);
            }
        }

        upgrade_block_savepoint(true, 2015012601, 'course_management');
    }
    
    
    // PBO 20150126 #291 - Nouvelle définition de la table indexation_moodle
    if ($oldversion < 2015040900) {
        $indexationTable = new xmldb_table('indexation_moodle');

        // ajout du champ "académie" (liste déroulante)
        if (!$dbman->field_exists($indexationTable->getName(), 'academy')) {
            $dbman->add_field($indexationTable, new xmldb_field('academy', XMLDB_TYPE_INTEGER, '11', null));
        }

        upgrade_block_savepoint(true, 2015040900, 'course_management');
    }
    
    // VSE 20150409 #639
    if ($oldversion < 2015040901) {
    
    	// Define table course_trash_category to be created
    	$table = new xmldb_table('course_trash_category');
    
    	// Adding fields to table course_trash_category
    	$table->add_field('id', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
    	$table->add_field('course_id', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null);
    	$table->add_field('category_id', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null);
    
    	// Adding keys to table course_trash_category
    	$table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
    
    	// Conditionally launch create table for course_trash_category
    	if (!$dbman->table_exists($table)) {
    		$dbman->create_table($table);
    	}
    	
    	
    	if ( ! $DB->record_exists('course_categories', array('name'=>'Corbeille','parent'=>0)) )
    	{
    	
	    	// Creation de la nouvelle categorie racine 'Corbeille'
	    	$newcategory = new stdClass();
	    	$newcategory->name = 'Corbeille';
	    	$newcategory->idnumber = '';
	    	$newcategory->description = '';
	    	$newcategory->description_editor = '';
	    	$newcategory->parent = 0; // if $data->parent = 0, the new category will be a top-level category
	    	
	   		// Don't overwrite the $newcategory object as it'll be processed by file_postupdate_standard_editor in a moment
	   		$category = coursecat::create($newcategory);
	   		$newcategory->id = $category->id;
	   		$categorycontext = $category->context;
	    	
	   		$editoroptions = array(
	   				'maxfiles'  => EDITOR_UNLIMITED_FILES,
	   				'maxbytes'  => $CFG->maxbytes,
	   				'trusttext' => true
	   		);
	   		
	    	$newcategory = file_postupdate_standard_editor($newcategory, 'description', $editoroptions, $categorycontext, 'coursecat', 'description', 0);
	    	$DB->update_record('course_categories', $newcategory);
	    	fix_course_sortorder();
    	
    	}
    
    	// course_management savepoint reached
    	upgrade_block_savepoint(true, 2015040901, 'course_management');
    }
    
    
    // VSE 20150409 #639
    if ($oldversion < 2015040902) {
    
    	// Define table course_trash_category to be created
    	$table = new xmldb_table('origine_gaia');
    
    	// Adding fields to table course_trash_category
    	$table->add_field('id', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
    	$table->add_field('code', XMLDB_TYPE_TEXT, '255', null, XMLDB_NOTNULL, null, null);
    	$table->add_field('name', XMLDB_TYPE_TEXT, '255', null, XMLDB_NOTNULL, null, null);
    
    	// Adding keys to table course_trash_category
    	$table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
    
    	// Conditionally launch create table for course_trash_category
    	if (!$dbman->table_exists($table)) {
    		$dbman->create_table($table);
    		
    		
    		
    		$sql_insert = "INSERT INTO {origine_gaia} (`id`, `code`, `name`) VALUES
(1, 'PCAN', 'reseau-canope'),
(2, 'NDGS', 'dgesco'),
(3, 'PIFE', 'ife'),
(4, 'NDEN', 'ih2ef'),
(5, 'PESP', 'espe'),
(6, 'PAUT', 'autre'),
(7, 'A002', 'ac-aix-marseille'),
(8, 'A020', 'ac-amiens'),
(9, 'A003', 'ac-besancon'),
(10, 'A004', 'ac-bordeaux'),
(11, 'A005', 'ac-caen'),
(12, 'A006', 'ac-clermont'),
(13, 'A027', 'ac-corse'),
(14, 'A024', 'ac-creteil'),
(15, 'A007', 'ac-dijon'),
(16, 'A008', 'ac-grenoble'),
(17, 'A032', 'ac-guadeloupe'),
(18, 'A033', 'ac-guyane'),
(19, 'A009', 'ac-lille'),
(20, 'A022', 'ac-limoges'),
(21, 'A010', 'ac-lyon'),
(22, 'A031', 'ac-martinique'),
(23, 'A043', 'ac-mayotte'),
(24, 'A011', 'ac-montpellier'),
(25, 'A012', 'ac-nancy-metz'),
(26, 'A017', 'ac-nantes'),
(27, 'A023', 'ac-nice'),
(28, 'A022', 'ac-noumea'),
(29, 'A018', 'ac-orleans-tours'),
(30, 'A001', 'ac-paris'),
(31, 'A013', 'ac-poitiers'),
(32, 'A026', 'ac-polynesie'),
(33, 'A019', 'ac-reims'),
(34, 'A014', 'ac-rennes'),
(35, 'A028', 'ac-reunion'),
(36, 'A021', 'ac-rouen'),
(37, 'A044', 'ac-st-pierre-miquelon'),
(38, 'A015', 'ac-strasbourg'),
(39, 'A016', 'ac-toulouse'),
(40, 'A025', 'ac-versailles'),
(41, 'A042', 'ac-wallis-futuna'),
(42, 'IREM', 'irem')";

    		$DB->execute($sql_insert);
    		
    	}
    	
    	
    
    	// course_management savepoint reached
    	upgrade_block_savepoint(true, 2015040902, 'course_management');
    }
    
    // VSE 20170213 #1439
    if ($oldversion < 2017021300) {
    
    	if ( ! $DB->record_exists('origine_gaia', array('name'=>'irem')) )
    	{
	    	// Creation de la nouvelle categorie racine 'Corbeille'
    		$neworigin = new stdClass();
	    	$neworigin->name = 'irem';
	    	$neworigin->code = 'IREM';
	    	
	    	$DB->insert_record('origine_gaia', $neworigin);
    	}
    
    	// course_management savepoint reached
    	upgrade_block_savepoint(true, 2017021300, 'course_management');
    }
    
    
    if ($oldversion < 2017021301)
    {
        
        // Define table course_trash_category to be created
        $table = new xmldb_table('course_trash_category');
        
        $field_updatetime = new xmldb_field('updatetime', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, '0');
        // Conditionally launch add field for course_trash_category
        if (!$dbman->field_exists($table,$field_updatetime)) {
            $dbman->add_field($table, $field_updatetime);
        }
        
        $DB->execute("UPDATE {course_trash_category} SET `updatetime`= 0");
        
        // progress savepoint reached
        upgrade_block_savepoint(true, 2017021301, 'course_management');
    }

    if ($oldversion < 2017021301)
    {
        
        // Define table course_trash_category to be created
        $table = new xmldb_table('course_trash_category');
        
        $field_updatetime = new xmldb_field('updatetime', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, '0');
        // Conditionally launch add field for course_trash_category
        if (!$dbman->field_exists($table,$field_updatetime)) {
            $dbman->add_field($table, $field_updatetime);
        }
        
        $DB->execute("UPDATE {course_trash_category} SET `updatetime`= 0");
        
        // progress savepoint reached
        upgrade_block_savepoint(true, 2017021301, 'course_management');
    }

    return $result;
}


function insert_disciplines($oldversion)
{
    global $DB;
    if ($oldversion < 2013101511) {
        //on insère les niveaux

        $niveaux_array = array(
            'Maternelle',
            'Elémentaire',
            'Ingénierie de la formation'
        );
        foreach ($niveaux_array as $niveau) {
            $record       = new stdClass();
            $record->name = $niveau;
            $DB->insert_record('indexation_level', $record, false);
        }

        //on insere les niveaux
        $data_array = array(
            array(1, 1, 'S\'approprier le langage'),
            array(2, 1, 'Découvrir l\'écrit'),
            array(3, 1, 'Devenir élève'),
            array(4, 1, 'Agir et s\'exprimer avec son corps'),
            array(5, 1, 'Découvrir le monde'),
            array(6, 1, 'Percevoir, sentir, imaginer, créer'),
            array(7, 1, 'Techniques usuelles de l\'information et de la communication'),
            array(8, 1, 'Transversal'),
            array(9, 2, 'Français'),
            array(10, 2, 'Mathématiques'),
            array(11, 2, 'Éducation physique et sportive'),
            array(12, 2, 'Langue vivante'),
            array(13, 2, 'Découverte du monde'),
            array(14, 2, 'Pratiques artistiques et histoire des arts'),
            array(15, 2, 'Instruction civique et morale'),
            array(16, 2, 'Sciences expérimentales et technologie'),
            array(17, 2, 'Culture humaniste'),
            array(18, 2, 'Techniques usuelles de l\'information et de la communication'),
            array(19, 2, 'Transversal'),
            array(20, 3, 'Techniques usuelles de l\'information et de la communication'),
            array(21, 3, 'Formation de formateur'),
            array(22, 3, 'Modèles d\'espaces')
        );
        foreach ($data_array as $data) {
            $record          = new stdClass();
            $record->levelid = $data[1];
            $record->name    = $data[2];
            $DB->insert_record('indexation_discipline', $record, false);
        }
    }

    return;

}


function insert_old_data()
{
    global $CFG;

    $dbhost   = $CFG->dbhost;
    $dbname   = $CFG->dbname;
    $username = $CFG->dbuser;
    $password = $CFG->dbpass;

    try {
        $conn = new PDO('mysql:host=' . $dbhost . ';dbname=' . $dbname, $username, $password);

        //LECTURE
        $stmt = $conn->prepare('SELECT * FROM  indexation_moodle');
        $stmt->execute();

        $result       = $stmt->fetchAll();
        $result_array = array();

        if (count($result)) {
            foreach ($result as $row) {
                $result_array[] = $row;
            }
        } else {
            //pas de ligne a transferer
            return;
        }


        //INSERTION
        foreach ($result_array as $current_result) {
            //on construit la requete
            $requete    = "INSERT INTO `" . $CFG->prefix . "indexation_moodle` (`course_id`, `nom_parcours`, `objectifs`, `description`, `niveau`, `discipline`, `public_target`, `collection`, `tps_a_distance`,
	  `tps_en_presence`, `accompagnement`, `origine`, `liste_auteurs`, `contact_auteurs`, `contributeurs`, `validation`, `derniere_maj`) VALUES (";
            $public_set = false;
            foreach ($current_result as $key => $value) {

                if ($key == 'public') {
                    if ($public_set) {
                        $requete .= ':public_target, ';
                    }
                    $public_set = true;
                } else {
                    if (!in_array(
                        $key,
                        array(
                            '0',
                            '1',
                            '2',
                            '3',
                            '4',
                            '5',
                            '6',
                            '7',
                            '8',
                            '9',
                            '10',
                            '11',
                            '12',
                            '13',
                            '14',
                            '15',
                            '16',
                            '17',
                            '18',
                            '19'
                        )
                    )
                    ) {
                        $requete .= ":$key, ";
                    }
                }
            }
            $requete = rtrim($requete, ', ');
            $requete .= ')';

            //on set les valeurs
            $set_values_array = array();
            foreach ($current_result as $key => $value) {
                if (!in_array(
                    $key,
                    array(
                        '0',
                        '1',
                        '2',
                        '3',
                        '4',
                        '5',
                        '6',
                        '7',
                        '8',
                        '9',
                        '10',
                        '11',
                        '12',
                        '13',
                        '14',
                        '15',
                        '16',
                        '17',
                        '18',
                        '19'
                    )
                )
                ) {
                    if ($key == 'public') {
                        $set_values_array[':public_target'] = $value;
                    } else {
                        $set_values_array[':' . $key] = $value;
                    }
                }
            }
            $stmt = $conn->prepare($requete);
            $stmt->execute($set_values_array);
            unset($set_values_array);

            //on rempli la table des niveaux
            $insert_query = "INSERT INTO `" . $CFG->prefix . "indexation_lvl_discipline` (`id`, `maternelle`, `elementaire`, `college`, `lycee`) VALUES
							(1, 'dessin*écriture*alphabet*initiation à l''anglais', 'lecture*écriture*histoire*mathématiques*géographie', 'français*anglais*espagnol*allemand*histoire*mathématiques*géographie*éducation civique', 'français*anglais*espagnol*géographie*économie*philosophie');
							";
            $stmt         = $conn->prepare(utf8_decode($insert_query));
            $stmt->execute();
        }


    } catch (PDOException $e) {
        echo 'ERROR: ' . $e->getMessage();
    }


    return;

}

?>