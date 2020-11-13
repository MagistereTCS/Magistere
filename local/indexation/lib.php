<?php

/**
 * indexation local plugin
 *
 * Fichier librairie de fonctions pour le plugin indexation.
 *
 * @package    local
 * @subpackage indexation
 * @author     TCS
 * @date       Aout 2019
 */

/**
 * Fonction qui gère le post traitement de l'onglet general du formulaire d'indexation.
 * @param $data
 * @param $indexation
 * @throws dml_exception
 */
function process_general_form($data, $indexation)
{
    global $DB;

    $dbdatacourse = new stdClass();
    $dbdatacourse->id = $data->id;
    $dbdatacourse->fullname = $data->coursefullname;
    $dbdatacourse->summary = $data->description['text'];
    $dbdatacourse->summaryformat = $data->description['format'];
    $DB->update_record('course', $dbdatacourse);
    

    $dbdata = new stdClass();
    $dbdata->courseid = $data->id;
    $dbdata->objectif = $data->objectif;
    $dbdata->collectionid = $data->collection;
    $dbdata->entree_metier = $data->entree_metier;
    $dbdata->certificatid = $data->certificat;
    $dbdata->domainid = $data->domain;
    $dbdata->collectionid = $data->collection;
    $dbdata->videoid = $data->videoid;
    $dbdata->thumbnailid = $data->thumbnailid;
    $dbdata->updatedate = time();
    $dbdata->achievementmark = (isset($data->achievementmark) ? 1 : 0);
    $dbdata->title = $data->intitule;

    if($indexation){
        $dbdata->id = $indexation->id;
        $DB->update_record('local_indexation', $dbdata);
    }else{
        $dbdata->id = $DB->insert_record('local_indexation', $dbdata);
    }

    process_formateur_domain($dbdata->id, $dbdata->domainid);
    process_keywords($dbdata->id, $data->keywords);
}

/**
 * Fonction qui gère le post traitement de l'onglet organisme du formulaire d'indexation.
 * @param $data
 * @param $indexation
 * @throws dml_exception
 */
function process_organisme_form($data, $indexation)
{
    global $DB;

    $dbdata = new stdClass();
    $dbdata->courseid = $data->id;
    $dbdata->contact = $data->contact;
    $dbdata->origin = $data->origin;
    $dbdata->academyid = $data->academie;
    $dbdata->departementid = $data->departement;
    $dbdata->originespeid = $data->espe;
    $dbdata->validateby = $data->validateby;
    $dbdata->authors = $data->authors;
    $dbdata->codeorigineid = $data->code;
    $dbdata->updatedate = time();


    if($indexation){
        $dbdata->id = $indexation->id;
        $DB->update_record('local_indexation', $dbdata);
    }else{
        $dbdata->id = $DB->insert_record('local_indexation', $dbdata);
    }
}

/**
 * Fonction qui gère le post traitement de l'onglet detail du formulaire d'indexation.
 * @param $data
 * @param $indexation
 * @throws dml_exception
 */
function process_detail_form($data, $indexation)
{
    global $DB;

    $dbdata = new stdClass();
    $dbdata->courseid = $data->id;
    $dbdata->tps_a_distance = $data->tps_a_distance;
    $dbdata->tps_en_presence = $data->tps_en_presence;
    $dbdata->accompagnement = $data->accompagnement;
    $dbdata->rythme_formation = $data->rythme_formation;
    $dbdata->updatedate = time();

    if($indexation){
        $dbdata->id = $indexation->id;
        $DB->update_record('local_indexation', $dbdata);
    }else{
        $dbdata->id = $DB->insert_record('local_indexation', $dbdata);
    }

    $dbcourse = new stdClass();
    $dbcourse->id = $data->id;
    $dbcourse->startdate = $data->startdate;
    $dbcourse->enddate = $data->enddate;

    $DB->update_record('course', $dbcourse);

    process_public($dbdata->id, $data->publics);
}

/**
 * Fonction qui gère le post traitement de l'onglet version du formulaire d'indexation.
 * @param $data
 * @param $indexation
 * @throws dml_exception
 */
function process_version_form($data, $indexation)
{
    global $DB;

    $dbdata = new stdClass();
    $dbdata->courseid = $data->id;
    $dbdata->year = $data->year;
    $dbdata->version = $data->version;
    $dbdata->updatedate = time();

    if($indexation){
        $dbdata->id = $indexation->id;
        $DB->update_record('local_indexation', $dbdata);
    }else{
        $dbdata->id = $DB->insert_record('local_indexation', $dbdata);
    }

    // first find the current note
    $note = $DB->get_record('local_indexation_notes', ['indexationid' => $indexation->id, 'version' => $data->version]);
    $data->currentnote = trim($data->currentnote);

    if(!$note){
        $note = new stdClass();
        $note->indexationid = $indexation->id;
        $note->version = $data->version;
        $note->timecreated = time();
        $note->timemodified = time();
        $note->note = $data->currentnote;

        $DB->insert_record('local_indexation_notes', $note);
    }else{
        $note->note = $data->currentnote;
        $note->timemodified = time();
        $DB->update_record('local_indexation_notes', $note);
    }

    if($data->currentversion == $data->version){
        return;
    }

    // update the previous version if needed
    $note = $DB->get_record('local_indexation_notes', ['indexationid' => $indexation->id, 'version' => $data->currentversion]);
    $data->previousnote = trim($data->previousnote);

    if($note){
        $note->note = $data->previousnote;
        $note->timemodified = time();
        $DB->update_record('local_indexation_notes', $note);
        return;
    }

    $note = new stdClass();
    $note->indexationid = $indexation->id;
    $note->version = $data->currentversion;
    $note->timecreated = time();
    $note->timemodified = time();
    $note->note = $data->previousnote;

    $DB->insert_record('local_indexation_notes', $note);
}

/**
 * Fonction qui gère une règle particulière à la validation du formulaire d'indexation.
 * Si le dommaine est "Métier de la formation", seul le public "Formateurs" est ajouté.
 * @param $indexationid
 * @param $domainid
 * @throws dml_exception
 * @throws moodle_exception
 */
function process_formateur_domain($indexationid, $domainid)
{
    global $DB;

    $DBC = get_centralized_db_connection();

    $formationForm = $DBC->get_record('local_indexation_domains', array('name' => 'Métiers de la formation'));
    if($formationForm && $formationForm->id == $domainid){
        // if domain is 'Formation de formateur'
        // set public to only 'Formateurs'
        $DB->delete_records('local_indexation_public', array('indexationid' => $indexationid));

        $publicFormateur = $DBC->get_record('local_indexation_publics', array('name' => 'Formateurs'));
        if($publicFormateur){
            $d = new stdClass();
            $d->indexationid = $indexationid;
            $d->publicid = $publicFormateur->id;

            $DB->insert_record('local_indexation_public', $d);
        }
    }
}

/**
 * Fonction qui traite l'ajout et la modification des keywords saisi dans le formulaire d'indexation.
 * @param $indexationid
 * @param $keywords
 * @throws dml_exception
 */
function process_keywords($indexationid, $keywords)
{
    global $DB;

    $keywords = explode(',', $keywords);

    $DB->delete_records('local_indexation_keywords', array('indexationid' => $indexationid));

    foreach($keywords as $keyword){
        $keyword = trim($keyword);
        if(empty($keyword)){
            continue;
        }

        $d = new stdClass();
        $d->indexationid = $indexationid;
        $d->keyword = $keyword;

        $DB->insert_record('local_indexation_keywords', $d);
    }
}

/**
 * Fonction qui traite l'ajout et la modification des publics choisi dans le formulaire d'indexation.
 * Cas particulier lorsque le public "Formateurs" est choisi, le domaine "Métiers de la formation" est automatiquement inclus.
 * @param $indexationid
 * @param $publics
 * @throws coding_exception
 * @throws dml_exception
 * @throws moodle_exception
 */
function process_public($indexationid, $publics)
{
    global $DB;

    $DB->delete_records('local_indexation_public', array('indexationid' => $indexationid));

    $DBC = get_centralized_db_connection();
    $formateur = $DBC->get_record('local_indexation_publics', array('name' => 'Formateurs'));

    $dbPublics = array();
    foreach($publics as $id => $value){
        $d = new stdClass();
        $d->indexationid = $indexationid;
        $d->publicid = $id;
        $dbPublics[] = $d;

        if($id == $formateur->id){
            $domainForm = $DBC->get_record('local_indexation_domains', array('name' => 'Métiers de la formation'));

            $index = new stdClass();
            $index->id = $indexationid;
            $index->domainid = $domainForm->id;

            $DB->update_record('local_indexation', $index);
        }
    }

    $DB->insert_records('local_indexation_public', $dbPublics);
}


/**
 * this is called after deleting all instances if the course will be deleted.
 * only indexation and hub publication have to be deleted
 *
 * @global object
 * @param object $course
 * @return boolean
 */
/*
function local_indexation_pre_course_delete($course) {
    global $DB, $OUTPUT;

    $indexation  = $DB->get_record('local_indexation', array('courseid'=>$course->id));

    if(!$indexation){
        return false;
    }

    // delete data of course in hub database
    try{
        if((databaseConnection::instance()->get('hub')) !== false){
            $hubcourses = databaseConnection::instance()->get('hub')->get_records('hub_course_directory',array('sitecourseid' => $course->id));
            if($hubcourses){
                foreach($hubcourses as $hubcourse){
                    //delete all hub publication dependencies
                    databaseConnection::instance()->get('hub')->delete_records('hub_course_contents',array('courseid' => $hubcourse->id));
                    databaseConnection::instance()->get('hub')->delete_records('hub_course_outcomes',array('courseid' => $hubcourse->id));
                    databaseConnection::instance()->get('hub')->delete_records('hub_course_feedbacks',array('courseid' => $hubcourse->id));
                    databaseConnection::instance()->get('hub')->delete_records('hub_course_self_inscription_attributes',array('hubcourseid' => $hubcourse->id));

                    //delete all hub indexation dependencies
                    $hub_indexation = databaseConnection::instance()->get('hub')->get_record('local_indexation',array('courseid' => $hubcourse->id));
                    if($hub_indexation){
                        databaseConnection::instance()->get('hub')->delete_records('local_indexation_collection',array('indexationid' => $hub_indexation->id));
                        databaseConnection::instance()->get('hub')->delete_records('local_indexation_keywords',array('indexationid' => $hub_indexation->id));
                        databaseConnection::instance()->get('hub')->delete_records('local_indexation_public',array('indexationid' => $hub_indexation->id));
                        databaseConnection::instance()->get('hub')->delete_records('local_indexation',array('courseid' => $hubcourse->id));
                    }
                }
                databaseConnection::instance()->get('hub')->delete_records('hub_course_directory',array('sitecourseid' => $course->id));
                echo $OUTPUT->notification(get_string('notification_hub_delete', 'local_indexation'), 'notifysuccess');
            }
        }
    } catch(Exception $e) {
        echo $OUTPUT->notification($e->getMessage(), 'notifyproblem');
    }

    try{
        //delete all dependencies in local database
        $DB->delete_records('local_indexation_collection', array('indexationid'=>$indexation->id));
        $DB->delete_records('local_indexation_keywords', array('indexationid'=>$indexation->id));
        $DB->delete_records('local_indexation_public', array('indexationid'=>$indexation->id));

        //delete indexation in local database
        $DB->delete_records('local_indexation', array('courseid'=>$course->id));

        echo $OUTPUT->notification(get_string('notification_indexation_delete', 'local_indexation'), 'notifysuccess');

    } catch(Exception $e) {
        echo $OUTPUT->notification($e->getMessage(), 'notifyproblem');
    }

    return true;
}
*/