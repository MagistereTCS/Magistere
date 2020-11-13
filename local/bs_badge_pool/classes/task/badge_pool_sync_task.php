<?php
namespace local_bs_badge_pool\task;

class badge_pool_sync_task extends \core\task\scheduled_task 
{      
    public function get_name() 
    {
        // Shown in admin screens
        return "Badge Pool Synchronisation Task";
    }
                                                                     
    public function execute() 
    {
        /*
    	global $CFG;
    	require_once($CFG->dirroot.'/local/magisterelib/databaseConnection.php');
    	
    	$acasource = array('dgesco','ih2ef','efe','reseau-canope','dne-foad');
//    	$acasource = array('dgesco','ih2ef'); //,'ih2ef','efe','reseau-canope','dne-foad');
    	$acas = get_magistere_academy_config();
    	
    	echo date('Y-m-d_H:i:s').'::PROCESS START BADGE POOL SYNC CRON\n';
    	
    	echo date('Y-m-d_H:i:s').'::Deleting old badges\n';
    	
    	
    	// Chaque academie source
    	foreach($acasource AS $saca)
    	{
    	    echo date('Y-m-d_H:i:s').'::PROCESS START source aca '.$saca."\n";
    	    $scats = \databaseConnection::instance()->get($saca)->get_records('local_bs_badge_pool_cat');
    	    
    	    // Chaque categorie de badge
    	    foreach($scats AS $scat)
    	    {
    	        echo date('Y-m-d_H:i:s').'::PROCESS START source category '.$scat->name."\n";
    	        $sbadges = \databaseConnection::instance()->get($saca)->get_records('local_bs_badge_pool_badges', array('categoryid'=>$scat->id,'sourceaca'=>$saca));
    	        
    	        // Chaque badge de la categorie
    	        foreach($sbadges AS $sbadge)
    	        {
    	            echo date('Y-m-d_H:i:s').'::PROCESS START source badge '.$sbadge->name."\n";
    	            // Pour chaque academie destinataire
            	    foreach ($acas AS $dacaname => $daca)
            	    {
            	        if (!in_array($dacaname, $acasource) && strpos($dacaname, 'ac-') !== 0 && $dacaname != $saca)
			{
				continue;
			}
//			if ($dacaname != 'ac-aix-marseille' && $dacaname != 'ih2ef' && $dacaname != 'dgesco')
//            	        {
//            	            continue;
//            	        }

            	        echo date('Y-m-d_H:i:s').'::'.$saca.'=>'.$dacaname.'::PROCESS START target academy '.$dacaname."\n";
            	        
            	        // Check if cat exist in destination
            	        echo date('Y-m-d_H:i:s').'::'.$saca.'=>'.$dacaname.'::Check if the category "'.$scat->hash.'" ('.$scat->name.') exist'."\n";
            	        $dbcat = \databaseConnection::instance()->get($dacaname)->get_record('local_bs_badge_pool_cat',array('hash'=>$scat->hash));
            	        
            	        if ($dbcat === false)
            	        {
            	            echo date('Y-m-d_H:i:s').'::'.$saca.'=>'.$dacaname.'::The category "'.$scat->hash.'" ('.$scat->name.') do not exist. We have to create it'."\n";
			    unset($dcat_name);
            	            $dcat = clone $scat;
            	            $dcat_name = $scat->name;
            	            unset($dcat->id);
            	            
            	            \databaseConnection::instance()->get($dacaname)->insert_record('local_bs_badge_pool_cat', $dcat);
            	            
            	            echo date('Y-m-d_H:i:s').'::'.$saca.'=>'.$dacaname.'::The category "'.$scat->hash.'" ('.$scat->name.') have been created'."\n";
            	            
            	            $dbcat = \databaseConnection::instance()->get($dacaname)->get_record('local_bs_badge_pool_cat',array('hash'=>$scat->hash));

            	        }else if ($dbcat->name != $scat->name){
                            $dbcat->name = $scat->name;
                            
                            \databaseConnection::instance()->get($dacaname)->update_record('local_bs_badge_pool_cat', $dbcat);
                        }else{
            	            echo date('Y-m-d_H:i:s').'::'.$saca.'=>'.$dacaname.'::The category "'.$scat->hash.'" ('.$scat->name.') exist'."\n";
            	        }
            	        
            	        // #######################################################
            	        // Badge
            	        echo date('Y-m-d_H:i:s').'::'.$saca.'=>'.$dacaname.'::Check if the badge "'.$sbadge->hash.'" ('.$sbadge->name.') exist'."\n";
            	        $dbadge = \databaseConnection::instance()->get($dacaname)->get_record('local_bs_badge_pool_badges', array('hash'=>$sbadge->hash));
            	        
            	        if ($dbadge === false)
            	        {
            	            echo date('Y-m-d_H:i:s').'::'.$saca.'=>'.$dacaname.'::The badge "'.$sbadge->hash.'" ('.$sbadge->name.') do not exist. We have to create it'."\n";
			    unset($dbadge);
            	            $dbadge = clone $sbadge;
            	            unset($dbadge->id);
                            $dbadge->categoryid = $dbcat->id;
            	            
            	            \databaseConnection::instance()->get($dacaname)->insert_record('local_bs_badge_pool_badges', $dbadge);
            	            
            	            echo date('Y-m-d_H:i:s').'::'.$saca.'=>'.$dacaname.'::The badge "'.$sbadge->hash.'" ('.$sbadge->name.') have been created'."\n";
            	            
            	            $dbadge = \databaseConnection::instance()->get($dacaname)->get_record('local_bs_badge_pool_badges', array('hash'=>$sbadge->hash));
            	            
            	        }else if ($sbadge->updated == 1)
            	        {
            	            echo date('Y-m-d_H:i:s').'::'.$saca.'=>'.$dacaname.'::The badge "'.$sbadge->hash.'" ('.$sbadge->name.') exist but has been updated'."\n";
            	            $dbadge2 = clone $sbadge;
            	            $dbadge2->id = $dbadge->id;
                            $dbadge2->updated = 0;
            	            $dbadge2->categoryid = $dbcat->id;
            	            
            	            \databaseConnection::instance()->get($dacaname)->update_record('local_bs_badge_pool_badges', $dbadge2);
            	            echo date('Y-m-d_H:i:s').'::'.$saca.'=>'.$dacaname.'::The badge "'.$sbadge->hash.'" ('.$sbadge->name.') have been updated'."\n";
            	            
            	            $dbadge = \databaseConnection::instance()->get($dacaname)->get_record('local_bs_badge_pool_badges', array('hash'=>$sbadge->hash));
            	        }else{
            	            echo date('Y-m-d_H:i:s').'::'.$saca.'=>'.$dacaname.'::The badge "'.$sbadge->hash.'" ('.$sbadge->name.') exist and not change found'."\n";
            	        }
            	        
            	        // #######################################################
            	        // Fichiers
            	        echo date('Y-m-d_H:i:s').'::'.$saca.'=>'.$dacaname.'::PROCESS the files of the badge "'.$sbadge->hash.'" ('.$sbadge->name.')'."\n";
            	        $sfiles = \databaseConnection::instance()->get($saca)->get_records('files', array('component'=>'local_bs_badge_pool','filearea'=>'badgepool','itemid'=>$sbadge->id));
            	        
            	        if (count($sfiles) < 1)
            	        {
            	            echo date('Y-m-d_H:i:s').'::'.$saca.'=>'.$dacaname.'::No files found for the badge "'.$sbadge->hash.'" ('.$sbadge->name.')'."\n";
            	        }else{
              	            echo date('Y-m-d_H:i:s').'::'.$saca.'=>'.$dacaname.'::'.count($sfiles).' files found for the badge "'.$sbadge->hash.'" ('.$sbadge->name.')'."\n";
                        }
            	        
            	        foreach($sfiles AS $sfile)
            	        {
            	            echo date('Y-m-d_H:i:s').'::'.$saca.'=>'.$dacaname.'::Process file "'.$sfile->id.'" ('.$sfile->contenthash.')'."\n";
            	            
            	            echo date('Y-m-d_H:i:s').'::'.$saca.'=>'.$dacaname.'::Check if the file exist on target academy badge "'.$sbadge->hash.'" ('.$sbadge->name.')'."\n";
            	            $dfile = \databaseConnection::instance()->get($dacaname)->get_record('files', array('component'=>'local_bs_badge_pool','filearea'=>'badgepool','itemid'=>$dbadge->id,'filename'=>$sfile->filename));

            	            $fupdated = false;
            	            if ($dfile !== false && $sfile->contenthash != $dfile->contenthash){
            	                
				\databaseConnection::instance()->get($dacaname)->delete_records('files',array('id'=>$dfile->id));

            	                //$fs = get_file_storage();
            	                //$fs->delete_area_files_select(1, 'local_bs_badge_pool', 'badgepool', '= ?', array($dbadge->id));
            	                $fupdated = true;
            	            }
            	            
            	            if ($dfile === false || $fupdated)
            	            {
            	                if ($fupdated)
            	                {
            	                   echo date('Y-m-d_H:i:s').'::'.$saca.'=>'.$dacaname.'::File to update "'.$sfile->filename.'", "'.$sfile->id.'" ('.$sfile->contenthash.')'."\n";
            	                }else{
            	                   echo date('Y-m-d_H:i:s').'::'.$saca.'=>'.$dacaname.'::File not found "'.$sfile->filename.'", "'.$sfile->id.'" ('.$sfile->contenthash.')'."\n";
            	                }
            	                $moodledataroot = dirname($CFG->dataroot);
            	                
            	                $sourcefile = $moodledataroot.'/'.str_replace('ac-','', $saca).'/filedir/'.substr($sfile->contenthash,0,2).'/'.substr($sfile->contenthash,2,2).'/'.$sfile->contenthash;
            	                $destinationdir = $moodledataroot.'/'.str_replace('ac-','',$dacaname).'/filedir/'.substr($sfile->contenthash,0,2).'/'.substr($sfile->contenthash,2,2);
            	                $destinationfile = $destinationdir.'/'.$sfile->contenthash;

            	                echo date('Y-m-d_H:i:s').'::'.$saca.'=>'.$dacaname.'::Create the destination directory'."\n";
            	                
            	                @mkdir($destinationdir,0777,true);

            	                echo date('Y-m-d_H:i:s').'::'.$saca.'=>'.$dacaname.'::Source file path is "'.$sourcefile.'"'."\n";
            	                
            	                //$fs = get_file_storage();
            	                //$dimg = array('contextid'=>1, 'component'=>'local_bs_badge_pool', 'filearea'=>'badgepool', 'itemid'=>$dbadge->id, 'filepath'=>'/', 'filename'=>$sfile->filename);
            	                //$fs->create_file_from_pathname($dimg, $sourcefile);

                                $nfile = clone $sfile;
            	                unset($nfile->id);
            	                $nfile->itemid = $dbadge->id;

            	                $fs = get_file_storage();
            	                $nfile->pathnamehash = $fs->get_pathname_hash(1, 'local_bs_badge_pool', 'badgepool', $dbadge->id, '/', $sfile->filename);
                                echo date('Y-m-d_H:i:s').'::'.$saca.'=>'.$dacaname.'::Pathnamehash: "'.$nfile->pathnamehash.'"'."\n";

            	                \databaseConnection::instance()->get($dacaname)->insert_record('files',$nfile);

                                echo date('Y-m-d_H:i:s').'::'.$saca.'=>'.$dacaname.'::Copy of the sources file "'.$sourcefile.'" to the destination path "'.$destinationfile.'"'."\n";
            	                if (!file_exists($destinationfile))
            	                {
            	                   $copy = copy($sourcefile, $destinationfile);
            	                   
            	                   if ($copy)
            	                   {
            	                       echo date('Y-m-d_H:i:s').'::'.$saca.'=>'.$dacaname.'::The copy SUCCEED'."\n";
            	                   }else{
            	                       echo date('Y-m-d_H:i:s').'::'.$saca.'=>'.$dacaname.'::The copy FAILED'."\n";
            	                   }
            	                }else{
            	                    echo date('Y-m-d_H:i:s').'::'.$saca.'=>'.$dacaname.'::The file ALREADY EXIST'."\n";
            	                }


            	                if ($fupdated)
            	                {
            	                   echo date('Y-m-d_H:i:s').'::'.$saca.'=>'.$dacaname.'::File updated "'.$sfile->filename.'", "'.$sfile->id.'" ('.$sfile->contenthash.')'."\n";
            	                }else{
            	                   echo date('Y-m-d_H:i:s').'::'.$saca.'=>'.$dacaname.'::File added "'.$sfile->filename.'", "'.$sfile->id.'" ('.$sfile->contenthash.')'."\n";
            	                }
            	                
            	            }else{
            	                echo date('Y-m-d_H:i:s').'::'.$saca.'=>'.$dacaname.'::File found "'.$sfile->filename.'", "'.$sfile->id.'" ('.$sfile->contenthash.')'."\n";
            	            }
            	            
            	            
            	            
            	            
            	        }
            	        
            	        echo date('Y-m-d_H:i:s').'::'.$saca.'=>'.$dacaname.'::PROCESS END for the badge "'.$sbadge->hash.'" ('.$sbadge->name.')'."\n";
            	    }
    	        }
    	    }
    	}






    	// Chaque academie source
    	foreach($acasource AS $saca)
    	{
    	    echo date('Y-m-d_H:i:s').'::PROCESS START source aca '.$saca."\n";
    	    $scats = \databaseConnection::instance()->get($saca)->get_records('local_bs_badge_pool_cat');
    	    
    	    // Chaque categorie de badge
    	    foreach($scats AS $scat)
    	    {
    	        echo date('Y-m-d_H:i:s').'::PROCESS START source category '.$scat->name."\n";
    	        $sbadges = \databaseConnection::instance()->get($saca)->get_records('local_bs_badge_pool_badges', array('categoryid'=>$scat->id,'sourceaca'=>$saca,'deleted'=>1));

    	        if (count($sbadges) > 0)
		{
                    echo date('Y-m-d_H:i:s').'::'.count($sbadges).' badges found to be deleted in category '.$scat->name.' ('.$scat->hash.')'."\n";
		}

    	        // Chaque badge supprimes de la categorie
    	        foreach($sbadges AS $sbadge)
    	        {
    	            echo date('Y-m-d_H:i:s').'::PROCESS START source badge '.$sbadge->hash."\n";
    	            // Pour chaque academie destinataire
    	            foreach ($acas AS $dacaname => $daca)
    	            {
    	                if (!in_array($dacaname, $acasource) && strpos($dacaname, 'ac-') !== 0 && $dacaname != $saca)
    	                {
    	                    continue;
    	                }
    	                
    	                echo date('Y-m-d_H:i:s').'::'.$saca.'=>'.$dacaname.'::PROCESS START target academy '.$dacaname."\n";
    	                
    	                $dbadge = \databaseConnection::instance()->get($dacaname)->get_record('local_bs_badge_pool_badges', array('hash'=>$sbadge->hash));
    	                if ($dbadge === false)
			{
				continue;
			}
    	                // Remove Badge files
    	                $files = \databaseConnection::instance()->get($dacaname)->get_records('files', array('component'=>'local_bs_badge_pool','filearea'=>'badgepool','itemid'=>$dbadge->id));
    	                if (count($files) > 0)
    	                {
    	                    foreach($files AS $file)
    	                    {
    	                        \databaseConnection::instance()->get($dacaname)->delete_records('files',array('id'=>$file->id));
    	                    }
    	                }
    	                
    	                // Remove Badge
    	                
    	                \databaseConnection::instance()->get($dacaname)->delete_records('local_bs_badge_pool_badges',array('id'=>$dbadge->id));
    	                    
    	                
    	                echo date('Y-m-d_H:i:s').'::'.$saca.'=>'.$dacaname.'::PROCESS END for the badge "'.$sbadge->hash.'" ('.$sbadge->name.')'."\n";
    	            }
    	        }
    	   }
       }





    	
    	echo date('Y-m-d_H:i:s').'::PROCESS END BADGE POOL SYNC CRON\n';
    */
    }

}


