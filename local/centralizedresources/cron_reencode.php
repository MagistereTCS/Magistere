<?php
define('CLI_SCRIPT', true);

$CFG = new stdClass();

global $CFG;

@error_reporting(E_ALL | E_STRICT);   // NOT FOR PRODUCTION SERVERS!
@ini_set('display_errors', '1');         // NOT FOR PRODUCTION SERVERS!
$CFG->debug = (E_ALL | E_STRICT);   // === DEBUG_DEVELOPER - NOT FOR PRODUCTION SERVERS!
$CFG->debugdisplay = 1;              // NOT FOR PRODUCTION SERVERS!

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');

@error_reporting(E_ALL | E_STRICT);   // NOT FOR PRODUCTION SERVERS!
@ini_set('display_errors', '1');         // NOT FOR PRODUCTION SERVERS!
$CFG->debug = (E_ALL | E_STRICT);   // === DEBUG_DEVELOPER - NOT FOR PRODUCTION SERVERS!
$CFG->debugdisplay = 1;              // NOT FOR PRODUCTION SERVERS!

require_once('lib/libffmpeg.php');

d('STARTING REENCODING PROCESS');

$dbconn = get_centralized_db_connection();

$remote_ssh = null;
/*
$remote_ssh = new stdClass();
$remote_ssh->host = '192.168.2.100';
$remote_ssh->user = 'it-cndp';
$remote_ssh->keypath = '/home/it-cndp/.ssh/id_rsa';
$remote_ssh->pubkeypath = '/home/it-cndp/.ssh/id_rsa.pub';
$remote_ssh->ffmpeg_path = '/usr/sbin/ffmpeg';

d("USING REMOTE SSH FFMPEG : \n".print_r($remote_ssh,true));
*/


$i = 0;
while ($i < 100)
{
  $i++;
  
  d('######################################################################################################################');
  d('SELECTING VIDEO TO ENCODE (LOOP '.$i.') ##############################################################################');
  $video = $dbconn->get_record_sql('SELECT * FROM cr_resources WHERE type="video" AND encoded=0 ORDER BY editdate DESC LIMIT 1');
  if ($video!==false)
  {
    d('VIDEO FOUND');
    d(print_r($video,true));
    d('SET VIDEO ENCODED STATUS FROM '.$video->encoded.' TO 2');
    $video->encoded = 2;
    $dbconn->update_record('cr_resources',$video);
    
  }else{
    d('NO VIDEO FOUND');
    break;
  }

  try
  {
    
    $sourcevideo = $CFG->centralized_path.$CFG->centralizedresources_media_types['video'].'/'.substr($video->hashname,0,2).'/'.$video->hashname.$video->createdate.'.'.$video->extension;
    $destinationvideoori = $CFG->centralized_encoding_path.$video->hashname.$video->createdate.'_'.time().'.ori.mp4';
    $destinationvideo480p = $CFG->centralized_encoding_path.$video->hashname.$video->createdate.'_'.time().'.480p.mp4';
    $destinationvideo720p = $CFG->centralized_encoding_path.$video->hashname.$video->createdate.'_'.time().'.720p.mp4';
    
    d('SOURCE VIDEO : '.$sourcevideo);
    d('DESTINATION VIDEO ORI : '.$destinationvideoori);
    d('DESTINATION VIDEO 480p : '.$destinationvideo480p);
    d('DESTINATION VIDEO 720p : '.$destinationvideo720p);
    
    
    if (!file_exists($sourcevideo))
    {
      d('Source video file not found',true);
    }
    
    if (filesize($sourcevideo) < 10)
    {
      d('Source video file empty',true);
    }
    
    
    $height = ffmpeg::get_height($sourcevideo);
    $width  = ffmpeg::get_width($sourcevideo);
    
    d('VIDEO HEIGHT : '.$height);
    d('VIDEO WIDTH : '.$width);
    
    $videosize = 1; // Less than 720p
    if ($height > 720 || ($width > 1280 && $height != 720))
    {
      d('THE VIDEO RESOLUTION IS MORE THAN 720p');
      $videosize = 3; // More than 720p
    }
    else if ($height == 720)
    {
      d('THE VIDEO RESOLUTION IS 720p');
      $videosize = 2; // Is 720p
    }else{
      d('THE VIDEO RESOLUTION IS LESS THAN 720p');
    }
    
    
    if ($videosize === 3)
    {
      d('ENCODING 720p VIDEO');
      list($encode_720_res,$encode_720_logs) = ffmpeg::reencode_video_720p($sourcevideo,$destinationvideo720p,$remote_ssh);
      d('ENCODING DONE');
      d('ENCODING 480p VIDEO');
      list($encode_480_res,$encode_480_logs) = ffmpeg::reencode_video_480p($sourcevideo,$destinationvideo480p,$remote_ssh);
      d('ENCODING DONE');
      
    
      if ($encode_720_res=== false)
      {
        d('Encoding 720p failed for "'.$sourcevideo.'" => "'.$destinationvideo720p.'"',true);
      }
      
      if (!file_exists($destinationvideo720p))
      {
        d('Encoded 720p file not found',true);
      }
      
      if (filesize($destinationvideo720p) < 10)
      {
        d('Encoded 720p file empty',true);
      }
      
      if ($encode_480_res=== false)
      {
        d('Encoding 480p failed for "'.$sourcevideo.'" => "'.$destinationvideo480p.'"',true);
      }
      
      if (!file_exists($destinationvideo480p))
      {
        d('Encoded 480p file not found',true);
      }
      
      if (filesize($destinationvideo480p) < 10)
      {
        d('Encoded 480p file empty',true);
      }
      
      
      $sha1_720p = sha1_file($destinationvideo720p);
      $sha1_480p = sha1_file($destinationvideo480p);
      
      
      
      $video_480p = clone $video;
      
      unset($video_480p->id);
      $video_480p->encoded = 1;
      $video_480p->hashname = $sha1_480p;
      $video_480p->resourceid = sha1($video_480p->hashname. $video_480p->createdate);
      $video_480p->extension = 'mp4';
      $video_480p->type = 'videolr';
      $video_480p->filesize= filesize($destinationvideo480p);
      $video_480p->height = ffmpeg::get_height($destinationvideo480p);
      $video_480p->width = ffmpeg::get_width($destinationvideo480p);
      $video_480p->duration = ffmpeg::get_duration($destinationvideo480p);
      $video_480p->encodedate = time();
      $video_480p->mediainfo = ffmpeg::get_mediainfo($destinationvideo480p);
      $video_480p->originalmediainfo = ffmpeg::get_mediainfo($sourcevideo);
      $video_480p->encodelogs = $encode_480_logs;
      
      $video_480p->filename = substr($video_480p->filename,0,strrpos($video_480p->filename, '.')).'.'.$video_480p->extension;
      $video_480p->cleanname = substr($video_480p->cleanname,0,strrpos($video_480p->cleanname, '.')).'.'.$video_480p->extension;
      
      
      
      if (!file_exists($CFG->centralized_path.$CFG->centralizedresources_media_types['video'].'/'.substr($video_480p->hashname,0,2)))
      {
        mkdir($CFG->centralized_path.$CFG->centralizedresources_media_types['video'].'/'.substr($video_480p->hashname,0,2));
      }
      
      $new480path = $CFG->centralized_path.$CFG->centralizedresources_media_types['video'].'/'.substr($video_480p->hashname,0,2).'/'.$video_480p->hashname.$video_480p->createdate.'.'.$video_480p->extension;
      
      if (!rename($destinationvideo480p, $new480path))
      {
        d('Failed to rename "'.$destinationvideo480p.'" to "'.$new480path.'"',true);
      }
      
      d('MYSQL INSERT NEW 480p RESSOURCE : '.print_r($video_480p,true));
      $id_480p = $dbconn->insert_record('cr_resources', $video_480p,true);
      
      
      
      
      $video->lowresid = $id_480p;
      $video->encoded = 1;
      $video->oldhashname = $video->hashname;
      $video->hashname = $sha1_720p;
      $video->extension = 'mp4';
      $video->filesize= filesize($destinationvideo720p);
      $video->height = ffmpeg::get_height($destinationvideo720p);
      $video->width = ffmpeg::get_width($destinationvideo720p);
      $video->duration = ffmpeg::get_duration($destinationvideo720p);
      $video->encodedate = time();
      $video->mediainfo = ffmpeg::get_mediainfo($destinationvideo720p);
      $video->originalmediainfo = ffmpeg::get_mediainfo($sourcevideo);
      $video->encodelogs = $encode_720_logs;
      
      $video->filename  = substr($video->filename,0,strrpos($video->filename, '.')).'.'.$video->extension;
      $video->cleanname = substr($video->cleanname,0,strrpos($video->cleanname, '.')).'.'.$video->extension;
      
      if (!file_exists($CFG->centralized_path.$CFG->centralizedresources_media_types['video'].'/'.substr($video->hashname,0,2)))
      {
        mkdir($CFG->centralized_path.$CFG->centralizedresources_media_types['video'].'/'.substr($video->hashname,0,2));
      }
      
      $new720path = $CFG->centralized_path.$CFG->centralizedresources_media_types['video'].'/'.substr($video->hashname,0,2).'/'.$video->hashname.$video->createdate.'.'.$video->extension;
      
      if (!rename($destinationvideo720p, $new720path))
      {
        d('Failed to rename "'.$destinationvideo720p.'" to "'.$new720path.'"',true);
      }
      
      d('MYSQL UPDATE 720p RESSOURCE : '.print_r($video,true));
      $dbconn->update_record('cr_resources', $video);
      
      unlink($sourcevideo);
      
      
    }
    else if ($videosize === 2)
    {
      d('ENCODING 480p VIDEO');
      list($encode_480_res,$encode_480_logs) = ffmpeg::reencode_video_480p($sourcevideo,$destinationvideo480p,$remote_ssh);
      d('ENCODING DONE');
      d('ENCODING ORIGINAL VIDEO');
      list($encode_ori_res,$encode_ori_logs) = ffmpeg::reencode_video_originalsize($sourcevideo,$destinationvideoori,$remote_ssh);
      d('ENCODING DONE');
      
      
      if ($encode_480_res=== false)
      {
        d('Encoding 480p failed for "'.$sourcevideo.'" => "'.$destinationvideo480p.'"',true);
      }
      
      if (!file_exists($destinationvideo480p))
      {
        d('Encoded 480p file not found',true);
      }
      
      if (filesize($destinationvideo480p) < 10)
      {
        d('Encoded 480p file empty',true);
      }
      
      
      if ($encode_ori_res=== false)
      {
        d('Encoding ori failed for "'.$sourcevideo.'" => "'.$destinationvideoori.'"',true);
      }
      
      if (!file_exists($destinationvideoori))
      {
        d('Encoded ori file not found',true);
      }
      
      if (filesize($destinationvideoori) < 10)
      {
        d('Encoded ori file empty',true);
      }
      
      
      $sha1_480p = sha1_file($destinationvideo480p);
      $sha1_ori = sha1_file($destinationvideoori);
      
      
      
      $video_480p = clone $video;
      
      unset($video_480p->id);
      $video_480p->encoded = 1;
      $video_480p->hashname = $sha1_480p;
      $video_480p->resourceid = sha1($video_480p->hashname. $video_480p->createDate);
      $video_480p->extension = 'mp4';
      $video_480p->type = 'videolr';
      $video_480p->filesize= filesize($destinationvideo480p);
      $video_480p->height = ffmpeg::get_height($destinationvideo480p);
      $video_480p->width = ffmpeg::get_width($destinationvideo480p);
      $video_480p->duration = ffmpeg::get_duration($destinationvideo480p);
      $video_480p->encodedate = time();
      $video_480p->mediainfo = ffmpeg::get_mediainfo($destinationvideo480p);
      $video_480p->originalmediainfo = ffmpeg::get_mediainfo($sourcevideo);
      $video_480p->encodelogs = $encode_480_logs;
      
      $video_480p->filename  = substr($video_480p->filename,0,strrpos($video_480p->filename, '.')).'.'.$video_480p->extension;
      $video_480p->cleanname = substr($video_480p->cleanname,0,strrpos($video_480p->cleanname, '.')).'.'.$video_480p->extension;
      
      if (!file_exists($CFG->centralized_path.$CFG->centralizedresources_media_types['video'].'/'.substr($video_480p->hashname,0,2)))
      {
        mkdir($CFG->centralized_path.$CFG->centralizedresources_media_types['video'].'/'.substr($video_480p->hashname,0,2));
      }
      
      $new480ppath = $CFG->centralized_path.$CFG->centralizedresources_media_types['video'].'/'.substr($video_480p->hashname,0,2).'/'.$video_480p->hashname.$video_480p->createdate.'.'.$video_480p->extension;
      
      if (!rename($destinationvideo480p, $new480ppath))
      {
        d('Failed to rename "'.$destinationvideo480p.'" to "'.$new480ppath.'"',true);
      }
      d('MYSQL INSERT NEW 480p RESSOURCE : '.print_r($video_480p,true));
      $v480p_id = $dbconn->insert_record('cr_resources', $video_480p,true);
      
      
      
      
      $video->lowresid = $v480p_id;
      $video->encoded = 1;
      $video->oldhashname = $video->hashname;
      $video->hashname = $sha1_ori;
      $video->extension = 'mp4';
      $video->filesize= filesize($destinationvideoori);
      $video->height = ffmpeg::get_height($destinationvideoori);
      $video->width = ffmpeg::get_width($destinationvideoori);
      $video->duration = ffmpeg::get_duration($destinationvideoori);
      $video->encodedate = time();
      $video->mediainfo = ffmpeg::get_mediainfo($destinationvideoori);
      $video->originalmediainfo = ffmpeg::get_mediainfo($sourcevideo);
      $video->encodelogs = $encode_ori_logs;
      
      $video->filename  = substr($video->filename,0,strrpos($video->filename, '.')).'.'.$video->extension;
      $video->cleanname = substr($video->cleanname,0,strrpos($video->cleanname, '.')).'.'.$video->extension;
      
      
      if (!file_exists($CFG->centralized_path.$CFG->centralizedresources_media_types['video'].'/'.substr($video->hashname,0,2)))
      {
        mkdir($CFG->centralized_path.$CFG->centralizedresources_media_types['video'].'/'.substr($video->hashname,0,2));
      }
      
      $neworipath = $CFG->centralized_path.$CFG->centralizedresources_media_types['video'].'/'.substr($video->hashname,0,2).'/'.$video->hashname.$video->createdate.'.'.$video->extension;
      
      if (!rename($destinationvideoori, $neworipath))
      {
        d('Failed to rename "'.$destinationvideoori.'" to "'.$neworipath.'"',true);
      }
      
      d('MYSQL UPDATE 720p RESSOURCE : '.print_r($video,true));
      $dbconn->update_record('cr_resources', $video);
      
      unlink($sourcevideo);
      
      
    }
    else
    {
      d('ENCODING ORIGINAL VIDEO');
      list($encode_ori_res,$encode_ori_logs) = ffmpeg::reencode_video_originalsize($sourcevideo,$destinationvideoori,$remote_ssh);
      d('ENCODING DONE');
      
      if ($encode_ori_res === false)
      {
        d('Encoding failed for "'.$sourcevideo.'" => "'.$destinationvideoori.'"',true);
      }
      
      if (!file_exists($destinationvideoori))
      {
        d('Encoded file not found',true);
      }
      
      if (filesize($destinationvideoori) < 10)
      {
        d('Encoded ori file empty',true);
      }
      
      $sha1 = sha1_file($destinationvideoori);
      
      $video->encoded = 1;
      $video->oldhashname = $video->hashname;
      $video->hashname = $sha1;
      $video->extension = 'mp4';
      $video->filesize= filesize($destinationvideoori);
      $video->height = ffmpeg::get_height($destinationvideoori);
      $video->width = ffmpeg::get_width($destinationvideoori);
      $video->duration = ffmpeg::get_duration($destinationvideoori);
      $video->encodedate = time();
      $video->mediainfo = ffmpeg::get_mediainfo($destinationvideoori);
      $video->originalmediainfo = ffmpeg::get_mediainfo($sourcevideo);
      $video->encodelogs = $encode_ori_logs;
      
      $video->filename  = substr($video->filename,0,strrpos($video->filename, '.')).'.'.$video->extension;
      $video->cleanname = substr($video->cleanname,0,strrpos($video->cleanname, '.')).'.'.$video->extension;
      
      if (!file_exists($CFG->centralized_path.$CFG->centralizedresources_media_types['video'].'/'.substr($video->hashname,0,2)))
      {
        mkdir($CFG->centralized_path.$CFG->centralizedresources_media_types['video'].'/'.substr($video->hashname,0,2));
      }
      
      $newpath = $CFG->centralized_path.$CFG->centralizedresources_media_types['video'].'/'.substr($video->hashname,0,2).'/'.$video->hashname.$video->createdate.'.'.$video->extension;
      
      if (!rename($destinationvideoori, $newpath))
      {
        d('Failed to rename "'.$destinationvideoori.'" to "'.$newpath.'"',true);
      }
      
      d('MYSQL UPDATE ORIGINAL RESSOURCE : '.print_r($video,true));
      $dbconn->update_record('cr_resources', $video);
      
      unlink($sourcevideo);
      
    }
    /*
    // Send notification
    $contrib = $dbconn->get_record('cr_contributor', array('id'=>$video->contributorid));
    
    $subject = 'Votre ressource centralisée est disponible sur M@gistère';
    
    $messagetext = "Bonjour,\n\nVotre ressource \"".$video->filename."\" a été traitée.\nElle est maintenant disponible à la consultation sur votre plateforme.\n\nCordialement,\nM@gistère\n\n(Cet e-mail est envoyé automatiquement, merci de ne pas y répondre. Contactez votre administrateur en cas de problème)";
    $messagehtml = "Bonjour,<br/>\n<br/>\nVotre ressource \"".$video->filename."\" a été traitée.<br/>\nElle est maintenant disponible à la consultation sur votre plateforme.<br/>\n<br/>\nCordialement,<br/>\nM@gistère<br/>\n<br/>\n(Cet e-mail est envoyé automatiquement, merci de ne pas y répondre. Contactez votre administrateur en cas de problème)";
    
    
    $touser = new stdClass();
    $touser->id = 999999998;
    $touser->email = $contrib->email;
    $touser->deleted = 0;
    $touser->auth = 'manual';
    $touser->suspended = 0;
    $touser->mailformat = 1;
    
    $fromuser = new stdClass();
    $fromuser->id = 999999999;
    $fromuser->email = str_replace('https://', 'no-reply@', $CFG->magistere_domaine);
    $fromuser->deleted = 0;
    $fromuser->auth = 'manual';
    $fromuser->suspended = 0;
    $fromuser->mailformat = 1;
    $fromuser->maildisplay = 1;
    
    //d('SENDING MAIL NOTIFICATION TO '.$contrib->email);
  //	email_to_user($touser, $fromuser, $subject, $messagetext, $messagehtml);
   */
  } catch(Exception $e)
  {
    d('EXCEPTION CATCHED');
    d('SET VIDEO ENCODED STATUS FROM '.$video->encoded.' TO 3');
    $video->encoded = 3;
    $dbconn->update_record('cr_resources',$video);
    d('EXCEPTION : '.print_r($e,true));
  }

} // end while



$i = 0;
while ($i < 100)
{
    $i++;
    
    d('######################################################################################################################');
    d('SELECTING AUDIO TO ENCODE (LOOP '.$i.') ##############################################################################');
    $audio = $dbconn->get_record_sql('SELECT * FROM cr_resources WHERE type="audio" AND encoded=0 ORDER BY editdate DESC LIMIT 1');
    if ($audio!==false)
    {
        d('AUDIO FOUND');
        d(print_r($audio,true));
        d('SET AUDIO ENCODED STATUS FROM '.$audio->encoded.' TO 2');
        $audio->encoded = 2;
        $dbconn->update_record('cr_resources',$audio);
        
    }else{
        d('NO AUDIO FOUND');
        break;
    }
    
    try
    {
        $sourceaudio = $CFG->centralized_path.$CFG->centralizedresources_media_types['audio'].'/'.substr($audio->hashname,0,2).'/'.$audio->hashname.$audio->createdate.'.'.$audio->extension;
        $destinationaudio = $CFG->centralized_encoding_path.$audio->hashname.$audio->createdate.'_'.time().'.mp3';
        
        d('SOURCE AUDIO : '.$sourceaudio);
        d('DESTINATION AUDIO : '.$destinationaudio);
        
        
        if (!file_exists($sourceaudio))
        {
            d('Source audio file not found',true);
        }
        
        if (filesize($sourceaudio) < 10)
        {
            d('Source audio file empty',true);
        }
        
        d('ENCODING AUDIO');
        list($encode_res,$encode_logs) = ffmpeg::reencode_audio($sourceaudio,$destinationaudio,$remote_ssh);
        d('ENCODING DONE');
        
        if ($encode_res === false)
        {
            d('Encoding failed for "'.$sourceaudio.'" => "'.$destinationaudio.'"',true);
        }
        
        if (!file_exists($destinationaudio))
        {
            d('Encoded file not found',true);
        }
        
        if (filesize($destinationaudio) < 10)
        {
            d('Encoded audio file empty',true);
        }
        
        $sha1 = sha1_file($destinationaudio);
        
        $audio->encoded = 1;
        $audio->oldhashname = $audio->hashname;
        $audio->hashname = $sha1;
        $audio->extension = 'mp3';
        $audio->filesize= filesize($destinationaudio);
        $audio->encodedate = time();
        $audio->mediainfo = ffmpeg::get_mediainfo($destinationaudio);
        $audio->originalmediainfo = ffmpeg::get_mediainfo($sourceaudio);
        $audio->encodelogs = $encode_logs;
        
        $audio->filename  = substr($audio->filename,0,strrpos($audio->filename, '.')).'.'.$audio->extension;
        $audio->cleanname = substr($audio->cleanname,0,strrpos($audio->cleanname, '.')).'.'.$audio->extension;
        
        if (!file_exists($CFG->centralized_path.$CFG->centralizedresources_media_types['audio'].'/'.substr($audio->hashname,0,2)))
        {
            mkdir($CFG->centralized_path.$CFG->centralizedresources_media_types['audio'].'/'.substr($audio->hashname,0,2));
        }
        
        $newpath = $CFG->centralized_path.$CFG->centralizedresources_media_types['audio'].'/'.substr($audio->hashname,0,2).'/'.$audio->hashname.$audio->createdate.'.'.$audio->extension;
        
        if (!rename($destinationaudio, $newpath))
        {
            d('Failed to rename "'.$destinationaudio.'" to "'.$newpath.'"',true);
        }
        
        d('MYSQL UPDATE ORIGINAL RESSOURCE : '.print_r($audio,true));
        $dbconn->update_record('cr_resources', $audio);
        
        unlink($sourceaudio);
        
        
        // Send notification
        /*
        $contrib = $dbconn->get_record('cr_contributor', array('id'=>$audio->contributorid));
        
        $subject = 'Votre ressource centralisée est disponible sur M@gistère';
        
        $messagetext = "Bonjour,\n\nVotre ressource \"".$audio->filename."\" a été traitée.\nElle est maintenant disponible à la consultation sur votre plateforme.\n\nCordialement,\nM@gistère\n\n(Cet e-mail est envoyé automatiquement, merci de ne pas y répondre. Contactez votre administrateur en cas de problème)";
        $messagehtml = "Bonjour,<br/>\n<br/>\nVotre ressource \"".$audio->filename."\" a été traitée.<br/>\nElle est maintenant disponible à la consultation sur votre plateforme.<br/>\n<br/>\nCordialement,<br/>\nM@gistère<br/>\n<br/>\n(Cet e-mail est envoyé automatiquement, merci de ne pas y répondre. Contactez votre administrateur en cas de problème)";
        
        
        $touser = new stdClass();
        $touser->id = 999999998;
        $touser->email = $contrib->email;
        $touser->deleted = 0;
        $touser->auth = 'manual';
        $touser->suspended = 0;
        $touser->mailformat = 1;
        
        $fromuser = new stdClass();
        $fromuser->id = 999999999;
        $fromuser->email = str_replace('https://', 'no-reply@', $CFG->magistere_domaine);
        $fromuser->deleted = 0;
        $fromuser->auth = 'manual';
        $fromuser->suspended = 0;
        $fromuser->mailformat = 1;
        $fromuser->maildisplay = 1;
        
        //d('SENDING MAIL NOTIFICATION TO '.$contrib->email);
        //	email_to_user($touser, $fromuser, $subject, $messagetext, $messagehtml);
        */
    } catch(Exception $e)
    {
        d('EXCEPTION CATCHED');
        d('SET AUDIO ENCODED STATUS FROM '.$audio->encoded.' TO 3');
        $audio->encoded = 3;
        $dbconn->update_record('cr_resources',$audio);
        d('EXCEPTION : '.print_r($e,true));
    }
    
} // end while

function d($msg,$exception=false)
{
	echo date('Y-m-d_H:i:s::').$msg."\n";
	
	if ($exception)
	{
        throw new Exception($msg);
    }
}




