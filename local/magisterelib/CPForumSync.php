<?php

global $CFG;
require_once($CFG->dirroot.'/local/magisterelib/databaseConnection.php');

class CPForumSync
{
	
	
	function __construct()
	{
	}
	
	function execute()
	{
		$this->doSync();
	}
	
	function d($msg)
	{
		echo date('Y-m-y_H:i:s::').$msg."\n";
	}
	
	function d2($msg)
	{
		//echo date('Y-m-y_H:i:s::').'####'.print_r($msg,true)."####\n";
	}
	
	
	function doSync()
	{
		global $DB,$CFG;
		// Get dgesco source forum
		//$course_uid = '17_NDGS_FORUMSYNC01_1.0';
		$course_uid = $CFG->CPForumSync_course_uid;
		
		if (($dgescoDB = databaseConnection::instance()->get('dgesco')) === false){$this->d('Erreur connexion a la base dgesco! Impossible d\'executer la task'); return;}else{$this->d('Connexion reussie dgesco');}
		//$dgescoDB = $DB;
		
		$dgesco_course = $dgescoDB->get_records_sql("SELECT c.* FROM {course} c 
INNER JOIN {local_indexation} im ON (im.courseid=c.id) 
INNER JOIN ".$CFG->centralized_dbname.".local_indexation_codes lic ON lic.id=im.codeorigineid 
WHERE CONCAT(im.year, '_', lic.code, '_', im.title, '_', im.version) COLLATE utf8_general_ci = '".$course_uid."'");
		
		if (count($dgesco_course) == 0)
		{
			$this->d('Error no course found with the UID "'.$course_uid.'"');
			return;
		}else if (count($dgesco_course) > 1)
		{
			$this->d('Error multiple course found with the same UID "'.$course_uid.'" => ('.implode(',', array_keys($dgesco_course)).')');
			return;
		}
		
		$dgesco_course = array_shift($dgesco_course);
		$this->d('Dgesco master course found');
		
		
		$dgesco_forum = $dgescoDB->get_records('forum',array('course'=>$dgesco_course->id,'type'=>'news'));
		
		
		if (count($dgesco_forum) == 0)
		{
			$this->d('Error no "news" forum found on the course "'.$dgesco_course->id.'"');
			return;
		}else if (count($dgesco_forum) > 1)
		{
			$this->d('Error multiple "news" forum found on the course "'.$dgesco_course->id.'"');
			return;
		}
		
		$dgesco_forum = array_shift($dgesco_forum);
		$this->d('Dgesco master forum found');
		
		
		$dgesco_forum_discussions = $dgescoDB->get_records('forum_discussions', array('course'=>$dgesco_course->id,'forum'=>$dgesco_forum->id));
		
		if (count($dgesco_forum_discussions) == 0)
		{
			$this->d('Forum found but no discussion found, cancelling');
			return;
		}
		
		$this->d('Dgesco master forum have '.count($dgesco_forum_discussions).' discussions');
		
		$dgesco_forum_posts = $dgescoDB->get_records_sql('SELECT fp.* FROM {forum_posts} fp INNER JOIN {forum_discussions} fd ON (fd.firstpost=fp.id) WHERE fd.id IN ('.implode(',', array_keys($dgesco_forum_discussions)).')');
		
		
		$list_academy = get_magistere_academy_config();
		
		foreach ($list_academy as $academy => $academyinfo)
		{
			// Skip all non ac-
			if(substr($academy, 0, 3) != "ac-"){ continue; }
			//if ($academy != 'ac-aix-marseille' && $academy != 'ac-orleans-tours'){ continue; }
			
			$this->d('START Processing academie "'.$academy.'"');
			
			if (($acaDB = databaseConnection::instance()->get($academy)) === false){$this->d(' Erreur connexion to "'.$academy.'"'); continue;}else{$this->d(' Connexion reussie a "'.$academy.'"');}
			//$acaDB = $DB; // hask for eclipse, to be remove or commented
			
			
			// Get this this aca course news forum if exist
			$aca_courses = $acaDB->get_records_sql("SELECT c.* FROM {course} c 
INNER JOIN {local_indexation} im ON (im.courseid=c.id) 
INNER JOIN ".$CFG->centralized_dbname.".local_indexation_codes lic ON lic.id=im.codeorigineid 
WHERE CONCAT(im.year, '_', lic.code, '_', im.title, '_', im.version) COLLATE utf8_general_ci = '".$course_uid."'");
			
			if (count($aca_courses) == 0)
			{
				$this->d(' Error no course found with the UID "'.$course_uid.'"');
				continue;
			}
			
			$this->d(' '.count($aca_courses).' courses found with the UID "'.$course_uid.'" on "'.$academy.'"');
			
			// For each course on this academy
			foreach ( $aca_courses AS $aca_course )
			{
				$this->d(' Processing course "'.$aca_course->id.'"');
				// Get this course news forum if exist
				$aca_forum = $acaDB->get_records('forum',array('course'=>$aca_course->id,'type'=>'news'));
				
				
				if (count($aca_forum) == 0)
				{
					$this->d('  Error no "news" forum found on the course "'.$aca_course->id.'"');
					continue;
				}else if (count($aca_forum) > 1)
				{
					$this->d('  Error multiple "news" forum found on the course "'.$aca_course->id.'"');
					continue;
				}
				
				$aca_forum = array_shift($aca_forum);
				
				
				$aca_forum_discussions = $acaDB->get_records('forum_discussions', array('course'=>$aca_course->id,'forum'=>$aca_forum->id));
				$this->d2($aca_forum_discussions);
				
				if (count($aca_forum_discussions) > 0)
				{
					$aca_forum_posts = $acaDB->get_records_sql('SELECT fp.* FROM {forum_posts} fp INNER JOIN {forum_discussions} fd ON (fd.firstpost=fp.id) WHERE fd.id IN ('.implode(',', array_keys($aca_forum_discussions)).')');
					$this->d2($aca_forum_posts);
				}else{
					$aca_forum_posts = array();
				}
				
				if (count($aca_forum_discussions) > 0)
				{
					// Change array id from local id to dgesco id
					$aca_forum_discussions2 = array();
					foreach($aca_forum_discussions AS $aca_forum_discussion)
					{
						$aca_forum_discussions2[$aca_forum_discussion->dgescosync] = $aca_forum_discussion;
					}
					$aca_forum_discussions = $aca_forum_discussions2;
				}
				
				$aca_forum_discussions_ids = array_keys($aca_forum_discussions);
				
				
				
				
				foreach($dgesco_forum_discussions AS $dgesco_forum_discussion)
				{
					$this->d('  Processing dgesco discussion id ="'.$dgesco_forum_discussion->id.'"');
					// If the discussion already exist, trying to update if needed
					if (in_array($dgesco_forum_discussion->id,$aca_forum_discussions_ids))
					{
						// Updating local discussion if needed
						$localdiscussion = $aca_forum_discussions[$dgesco_forum_discussion->id];
						$this->d('   Discussion found on "'.$academy.'" with the id "'.$localdiscussion->id.'"');
						
						$updated = false;
						
						if ($localdiscussion->name != $dgesco_forum_discussion->name)
						{
							$localdiscussion->name = $dgesco_forum_discussion->name;
							$this->d('   Name update found');
							$updated = true;
						}
						if ($localdiscussion->timestart != $dgesco_forum_discussion->timestart)
						{
							$localdiscussion->timestart = $dgesco_forum_discussion->timestart;
							$this->d('   timestart update found');
							$updated = true;
						}
						if ($localdiscussion->timeend!= $dgesco_forum_discussion->timeend)
						{
							$localdiscussion->timeend = $dgesco_forum_discussion->timeend;
							$this->d('   timeend update found');
							$updated = true;
						}
						if ($localdiscussion->pinned != $dgesco_forum_discussion->pinned)
						{
							$localdiscussion->pinned = $dgesco_forum_discussion->pinned;
							$this->d('   pinned update found');
							$updated = true;
						}
						
						if ($updated)
						{
							$this->d('   Updating forum_discussions with new data');
							$this->d2($localdiscussion);
							$acaDB->update_record('forum_discussions', $localdiscussion);
						}else{
							$this->d('   Nothing to update in the discussion');
						}
						
						// Updating firstpost if needed
						$localfirstpost = $aca_forum_posts[$localdiscussion->firstpost];
						$dgescofirstpost = $dgesco_forum_posts[$dgesco_forum_discussion->firstpost];
						
						$updated = false;
						
						if ($localfirstpost->subject != $dgescofirstpost->subject)
						{
							$localfirstpost->subject = $dgescofirstpost->subject;
							$this->d('   subject update found');
							$updated = true;
						}
						if ($localfirstpost->message != $dgescofirstpost->message)
						{
							$localfirstpost->message = $dgescofirstpost->message;
							$this->d('   message update found');
							$updated = true;
						}
						
						if ($updated)
						{
							// $localfirstpost->mailed = 0; // resend mail to all users
							$this->d('   Updating forum_posts with new data');
							$this->d2($localfirstpost);
							$acaDB->update_record('forum_posts', $localfirstpost);
						}else{
							$this->d('   Nothing to update in the firstpost');
						}
						
						
					}else{
						$this->d('   Discussion not found. We create a copy');
						// The discussion does not exist, we create a copy
						$localdiscussion = clone($dgesco_forum_discussion);
						unset($localdiscussion->id);
						$localdiscussion->course = $aca_course->id;
						$localdiscussion->forum = $aca_forum->id;
						$localdiscussion->firstpost = 0;
						$localdiscussion->userid = $this->userDtoA($localdiscussion->userid,$academy);
						$localdiscussion->usermodified = $this->userDtoA($localdiscussion->usermodified,$academy);
						$localdiscussion->groupid = -1;
						$localdiscussion->dgescosync = $dgesco_forum_discussion->id;
						
						$this->d('   Inserting the new discussion in local academy "'.$academy.'"');
						$new_discussion = $acaDB->insert_record('forum_discussions', $localdiscussion);
						$localdiscussion->id = $new_discussion;
						
						$this->d('   New discussion added with success, new id = "'.$new_discussion.'"');
						
						// We copy the firstpost
						$localfirstpost = clone($dgesco_forum_posts[$dgesco_forum_discussion->firstpost]);
						unset($localfirstpost->id);
						$localfirstpost->discussion = $localdiscussion->id;
						$localfirstpost->userid = $this->userDtoA($localfirstpost->userid,$academy);
						$localfirstpost->mailed = 0;
						$localfirstpost->mailnow = 1;
						
						$this->d('   Inserting the new firstpost in local academy "'.$academy.'"');
						$this->d2($localfirstpost);
						$new_firstpost = $acaDB->insert_record('forum_posts', $localfirstpost);
						$this->d('   New firstpost added with success, new id = "'.$new_firstpost.'"');
						
						// Update discussion firstpost id
						$localdiscussion->firstpost = $new_firstpost;
						$this->d('   New discussion firstpost id with id="'.$new_firstpost.'"');
						$this->d2($localdiscussion);
						$acaDB->update_record('forum_discussions', $localdiscussion);
						$this->d('   Discussion update successful ');
						
					}
					
					
				}
				
				$this->d(' END discussion sync for the course "'.$aca_course->id.'" of the academy "'.$academy.'"');
			}
			$this->d('END of the academy "'.$academy.'"');
		}
		
		
		
	}
	
	/*
	 * Translate the given dgesco userid in the given academy
	 * If the user is not found, return admin id(2)
	 */
	function userDtoA($dgesco_userid, $academy)
	{
		
		if (($acaDB = databaseConnection::instance()->get($academy)) === false){ $this->d('Erreur connexion "'.$academy.'"'); return 2;}else{$this->d('Connexion reussie "'.$academy.'"');}
		
		if (($dgescoDB = databaseConnection::instance()->get('dgesco')) === false){$this->d('Erreur connexion dgesco'); return 2;}else{$this->d('Connexion reussie dgesco');}
		
		$duser = $dgescoDB->get_record('user',array('id'=>$dgesco_userid));
		
		$auser = $acaDB->get_record('user',array('username'=>$duser->username,'auth'=>$duser->auth));
		
		// If user not found, return admin
		if ($auser == false)
		{
			$this->d('Dgesco user "'.$dgesco_userid.'" (USERNAME=>####'.$duser->username.'####) not found in "'.$academy.'" database, returning admin as replacement');
			return 2;
		}
		
		return $auser->id;
	}
	
	
	
	
}
