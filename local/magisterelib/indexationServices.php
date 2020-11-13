<?php

require_once($CFG->dirroot.'/local/magisterelib/databaseConnection.php');


class IndexationServices
{
	
	function __construct()
	{
		
	}
	
	/**
	 * 
	 * @param Integer $hubcourseid Set the currently restored course. If null, delete the current value.
	 * @return boolean Return true if succeed, else return false
	 */
	function setRestoreHubCourseID($hubcourseid)
	{
		global $CFG;
		$key = str_replace('%MMID%', get_mmid(), $CFG->restoreHubCourseIDKey);
		if ($hubcourseid == null)
		{
			return mmcached_delete($key);
		}else{
			return mmcached_set($key, $hubcourseid, $CFG->restoreHubCourseIDExpireDelay);
		}
	}
	
	/**
	 * Return the value previously set by setRestoreHubCourseID()
	 * @return mixed Return an integer if the value is found, else return false
	 */
	function getRestoreHubCourseID()
	{
		global $CFG;
		$key = str_replace('%MMID%', get_mmid(), $CFG->restoreHubCourseIDKey);
		$value = mmcached_get($key);
		return $value;
	}
	
	
	function restoreIndexation($restore_course_id)
	{
		global $DB;
		//error_log('##restoreIndexation##');
		$hubCourseID = $this->getRestoreHubCourseID();
		//error_log('##restoreIndexation=##'.print_r($hubCourseID,true).'##');
		
		
		if ($hubCourseID == false || $hubCourseID == null || intval($hubCourseID) < 1){error_log('IndexationServices/restoreIndexation()##hubCourseID not found or null'); return false;}
		
		if (($hubDB = databaseConnection::instance()->get('hub')) === false){error_log('IndexationServices/restoreIndexation()##Base du hub non trouvee'); return false;}

        $localindex = $DB->get_record('local_indexation', array('courseid' => $restore_course_id));
		$hubindex = $hubDB->get_record('local_indexation', array('courseid' => $hubCourseID));

		$indexationdata = clone $hubindex;
        $indexationdata->courseid = $restore_course_id;

        if($localindex !== false){
            $DB->delete_records('local_indexation_keywords', array('indexationid' => $localindex->id));
            $DB->delete_records('local_indexation_public', array('indexationid' => $localindex->id));

            $indexationdata->id = $localindex->id;
            $DB->update_record('local_indexation', $indexationdata);
        }else{
            $indexationdata->id = $DB->insert_record('local_indexation', $indexationdata);
        }

        $hubkeywords = $hubDB->get_records('local_indexation_keywords', array('indexationid' => $hubindex->id));
        $hubpublic = $hubDB->get_records('local_indexation_public', array('indexationid' => $hubindex->id));

        foreach($hubkeywords as &$data){
            unset($data->id);
            $data->indexationid = $indexationdata->id;
        }
        $DB->insert_records('local_indexation_keywords', $hubkeywords);

        foreach($hubpublic as &$data){
            unset($data->id);
            $data->indexationid = $indexationdata->id;
        }
        $DB->insert_records('local_indexation_public', $hubpublic);
		
		$this->setRestoreHubCourseID(null);
	}
	
	function setHubCourseIndexation($course_id, $hubcourseid)
	{
		global $DB;
		
		try
		{
			if (($hubDB = databaseConnection::instance()->get('hub')) === false){error_log('IndexationServices/setHubCourseIndexation()##Base du hub non trouvee'); return;}

			$hubindex = $hubDB->get_record('local_indexation', array('courseid' => $hubcourseid));
            $localindex = $DB->get_record('local_indexation', array('courseid' => $course_id));

            if(!$localindex && !$hubindex){
                return;
            }

            $indexationdata = clone $localindex;
            unset($indexationdata->id);
            $indexationdata->courseid = $hubcourseid;

            // purge data on hub if any and update
            if($hubindex){
                $hid = array('indexationid' => $hubindex->id);
                $hubDB->delete_records('local_indexation_keywords', $hid);
                $hubDB->delete_records('local_indexation_public', $hid);

                $indexationdata->id = $hubindex->id;
                $hubDB->update_record('local_indexation', $indexationdata);
            }else{
                $hubcoursedirectory = $hubDB->get_records('hub_course_directory', array('sitecourseid' => $course_id, 'enrollable' => 0, 'deleted' => 1), 'timepublished DESC', '*', 0, 1);
                $arrayKeys = array_keys($hubcoursedirectory);
                if($hubcoursedirectory){
                    $hubindexation = $hubDB->get_record('local_indexation', array('courseid' => $hubcoursedirectory[$arrayKeys[0]]->id));
                    if($hubindexation){
                        $indexationdata->id = $hubindexation->id;
                        $indexationdata->updatedate = time();
                        $hubDB->update_record('local_indexation', $indexationdata);
                    }else{
                        $indexationdata->id = $hubDB->insert_record('local_indexation', $indexationdata);
                    }
                }else{
                    $indexationdata->id = $hubDB->insert_record('local_indexation', $indexationdata);
                }
            }

            // retrieve data from local
            $lid = array('indexationid' => $localindex->id);
            $localkeywords = $DB->get_records('local_indexation_keywords', $lid);
            $localpublic = $DB->get_records('local_indexation_public', $lid);

            // foreach hell and add everything on hub
            foreach($localkeywords as &$data){
                unset($data->id);
                $data->indexationid = $indexationdata->id;
            }
            $hubDB->insert_records('local_indexation_keywords', $localkeywords);


            foreach($localpublic as &$data){
                unset($data->id);
                $data->indexationid = $indexationdata->id;
            }
            $hubDB->insert_records('local_indexation_public', $localpublic);
			
		} catch (moodle_exception $e) {
			error_log('UserProfileSynchronisation->updateFrontalUser()#'.$e);
		}
	}

	static function copy_indexation($oldcourseid, $newcourseid, $newcoursename = null)
    {
        global $DB;

        $oldindex = $DB->get_record('local_indexation', array('courseid' => $oldcourseid));
        $newindex = $DB->get_record('local_indexation', array('courseid' => $newcourseid));

        if(!$oldindex && !$newindex){
            return;
        }

        if(!$oldindex && $newindex){
            $DB->delete_records('local_indexation', array('id' => $newindex->id));
            $DB->delete_records('local_indexation_keywords', array('indexationid' => $dataindex->id));
            $DB->delete_records('local_indexation_public', array('indexationid' => $dataindex->id));
            return;
        }

        $dataindex = clone $oldindex;
        $dataindex->courseid = $newcourseid;
        $dataindex->updatedate = time();

        if($newindex){
            $dataindex->id = $newindex;
            $DB->update_record('local_indexation', $dataindex);
            $DB->delete_records('local_indexation_keywords', array('indexationid' => $dataindex->id));
            $DB->delete_records('local_indexation_public', array('indexationid' => $dataindex->id));
        }else{
            unset($dataindex->id);
            $dataindex->id = $DB->insert_record('local_indexation', $dataindex);
        }

        $dbpublic = $DB->get_records('local_indexation_public', array('indexationid' => $oldindex->id));
        $dbkeywords = $DB->get_records('local_indexation_keywords', array('indexationid' => $oldindex->id));

        $public = array();
        $keywords = array();

        foreach($dbpublic as $p){
            unset($p->id);
            $p->indexationid = $dataindex->id;
            $public[] = $p;
        }

        $DB->insert_records('local_indexation_public', $public);

        foreach($dbkeywords as $k){
            unset($k->id);
            $k->indexationid = $dataindex->id;
            $keywords[] = $k;
        }

        $DB->insert_records('local_indexation_keywords', $keywords);
    }
}
