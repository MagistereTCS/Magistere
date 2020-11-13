<?php


class ffmpeg
{
	static private $infos = array();
	
	
	
	
	
	static function get_mediainfo($videoPath)
	{
		global $CFG;
		$output = '';
		$returnStatus = '';
		
		if (!file_exists($videoPath)){return false;};
	
		$res = exec($CFG->ffmpeg_execpath_ffmpeg." -i " . $videoPath. " 2>&1", $output, $returnStatus);
	
		return preg_replace('/[\x00-\x1F\x7F]/u', '', implode("\n",$output));
	}
	
	static function get_videoinfo($videoPath)
	{
		global $CFG;
		if (isset(self::$infos[$videoPath])){return self::$infos[$videoPath];}
		
		$output = array();
		$returnStatus = '';

		$res = exec($CFG->ffmpeg_execpath_ffprobe." -v error -of flat=s=_ -select_streams v:0 -show_entries stream=height,width,duration,avg_frame_rate,bit_rate ".$videoPath, $output, $returnStatus);
				
		if ($output === false){return false;}
		
		$data = array();
		foreach($output as $value)
		{
			$val = explode('=',$value);
			$data[$val[0]] = $val[1];
		}
		
//		$matches = array();
		
//		$found=preg_match("/Duration: ([0-9]{2}):([0-9]{2}):([0-9]{2})\.([0-9]{2}), start: .*?, bitrate: .*? kb\/.*?\n.*?Stream #0:.*?: Video: .+?, .+?, ([0-9]{1,5})x([0-9]{1,5}) .+?, ([0-9]+) kb\/s, ([0-9\.]+) fps, .+? tbr,.*/m", $output, $matches);
		
//		if ($found == false){return false;}
		
		self::get_duration($videoPath);
		
		self::$infos= array();
		self::$infos[$videoPath] = array();
		
		self::$infos[$videoPath]['width'] = intval($data['streams_stream_0_width']);
		self::$infos[$videoPath]['height'] = intval($data['streams_stream_0_height']);
		self::$infos[$videoPath]['bitrate'] = intval($data['streams_stream_0_bit_rate']);
		self::$infos[$videoPath]['framerate'] = $data['streams_stream_0_avg_frame_rate'];
	
		return self::$infos[$videoPath];
	}
	
	static function get_duration($videoPath)
	{
		global $CFG;
		if (isset(self::$infos[$videoPath]['duration']))
		{
			return self::$infos[$videoPath]['duration'];
		}

		$output = array();
		$returnStatus = '';
		$res = exec($CFG->ffmpeg_execpath_ffmpeg." -i " . $videoPath . " 2>&1 | awk '/Duration/ {split($2,a,\":\");print a[1]*3600+a[2]*60+a[3]}'", $output, $returnStatus);
		
		$duration = floatval($res);
		
		self::$infos[$videoPath]['duration'] = $duration;
		
		return $duration;
	}
	
	static function get_width($videoPath)
	{
		$info = self::get_videoinfo($videoPath);
		
		return ($info === false?false:$info['width']);
	}
	
	static function get_height($videoPath)
	{
		$info = self::get_videoinfo($videoPath);
		
		return ($info === false?false:$info['height']);
	}
	
	static function get_framerate($videoPath)
	{
		$info = self::get_videoinfo($videoPath);
		
		return ($info === false?false:$info['framerate']);
	}
	
	static function get_bitrate($videoPath)
	{
		$info = self::get_videoinfo($videoPath);
		
		return ($info === false?false:$info['bitrate']);
	}
	
	
	
	
	
	static function create_thumbnail($videopath, $thumbnailpath, $timepos)
	{
		global $CFG;
		
		
		$duration = self::get_duration($videopath);
		
		if ($duration === false){return false;}
		
		if($timepos > $duration)
			$timepos = $duration;
			
			if($timepos < 0)
				$timepos = $duration + $timepos;
				
				$h = intval($timepos / 3600);
				$m = intval(($timepos - $h*3600) / 60);
				$s = intval(($timepos - $h*3600 - $m*60));
				
				$output = '';
				$returnStatus = '';
				exec($CFG->ffmpeg_execpath_ffmpeg." -i " . $videopath . " -ss " . $h . ':' . $m . ':' . $s . ' -vf "scale=' . $CFG->cr_thumbnail_video_width_max . ':-1" -vframes 1 ' . $thumbnailpath, $output, $returnStatus);
				return $returnStatus;
	}
	
	
	
	static function reencode_video_720p($videosource, $videodestination, $remote_ssh_infos=null)
	{
		global $CFG;
		$max_width = 1280;
		$max_height = 720;
		
		$width = self::get_width($videosource);
		$height = self::get_height($videosource);
		
		if ($width=== false || $height === false){return false;}
		
		$scale = 'scale=-2:'.$max_height;
		
		if ($width/($height/$max_height) > $max_width)
		{
			$scale = 'scale='.$max_width.':-2';
		}
		
		
		$output = '';
		$returnStatus = '';
		if ($remote_ssh_infos==null)
		{
      exec($CFG->ffmpeg_execpath_ffmpeg." -i ".$videosource." -vcodec libx264 -profile:v high -preset slow -crf 20 -maxrate 2500k -coder 1 -bf 2 -bufsize 6000k -pix_fmt yuv420p -g 100 -vf ".$scale." -threads 0 -acodec aac -b:a 192k ".$videodestination, $output, $returnStatus);
      
      return array(($returnStatus==0),implode("\n", $output));
    }else{
      list($error,$output) = self::ssh2Run($remote_ssh_infos, array('sudo -u magistere '.$remote_ssh_infos->ffmpeg_path." -i ".$videosource." -vcodec libx264 -profile:v high -preset slow -crf 20 -maxrate 2500k -coder 1 -bf 2 -bufsize 6000k -pix_fmt yuv420p -g 100 -vf ".$scale." -threads 0 -acodec aac -b:a 192k ".$videodestination));
      
      echo "#####################\n";
      print_r($output);
      echo "#####################\n";
      
      return array(!$error,$output);
    }
	}
	
	
	static function reencode_video_480p($videosource, $videodestination, $remote_ssh_infos=null)
	{
		global $CFG;
		$max_width = 854;
		$max_height = 480;
		
		$width = self::get_width($videosource);
		$height = self::get_height($videosource);
		
		if ($width=== false || $height === false){return false;}
		
		$scale = 'scale=-2:'.$max_height;
		
		if ($width/($height/$max_height) > $max_width)
		{
			$scale = 'scale='.$max_width.':-2';
		}
		
		$output = '';
		$returnStatus = '';
		if ($remote_ssh_infos==null)
		{
      exec($CFG->ffmpeg_execpath_ffmpeg." -i ".$videosource." -vcodec libx264 -profile:v high -preset slow -crf 28 -maxrate 1500k -coder 1 -bf 2 -bufsize 6000k -pix_fmt yuv420p -g 100 -vf ".$scale." -threads 0 -acodec aac -b:a 128k ".$videodestination, $output, $returnStatus);
      
      return array(($returnStatus==0),implode("\n", $output));
    }else{
      list($error,$output) = self::ssh2Run($remote_ssh_infos, array('sudo -u magistere '.$remote_ssh_infos->ffmpeg_path." -i ".$videosource." -vcodec libx264 -profile:v high -preset slow -crf 28 -maxrate 1500k -coder 1 -bf 2 -bufsize 6000k -pix_fmt yuv420p -g 100 -vf ".$scale." -threads 0 -acodec aac -b:a 128k ".$videodestination));
      
      echo "#####################\n";
      print_r($output);
      echo "#####################\n";
      return array(!$error,$output);
    }
	}
	
	
	static function reencode_video_originalsize($videosource, $videodestination, $remote_ssh_infos=null)
	{
		global $CFG;
	  $width = self::get_width($videosource);
		$height = self::get_height($videosource);
		
		if ($width % 2 == 1)
		{
		  $width = $width-1;
		}
		
		if ($height % 2 == 1)
		{
		  $height = $height-1;
		}
		
		$scale = 'scale='.$width.':'.$height;
	
		$output = '';
		$returnStatus = '';
		if ($remote_ssh_infos==null)
		{
		  exec($CFG->ffmpeg_execpath_ffmpeg." -i ".$videosource." -vcodec libx264 -profile:v high -preset slow -crf 20 -maxrate 2500k -coder 1 -bf 2 -bufsize 6000k -pix_fmt yuv420p -g 100 -vf ".$scale." -threads 0 -acodec aac -b:a 128k ".$videodestination, $output, $returnStatus);
		  
		  return array(($returnStatus==0),implode("\n", $output));
		}else{
		  list($error,$output) = self::ssh2Run($remote_ssh_infos, array('sudo -u magistere '.$remote_ssh_infos->ffmpeg_path." -i ".$videosource." -vcodec libx264 -profile:v high -preset slow -crf 20 -maxrate 2500k -coder 1 -bf 2 -bufsize 6000k -pix_fmt yuv420p -g 100 -vf ".$scale." -threads 0 -acodec aac -b:a 128k ".$videodestination));
		  
		  echo "#####################\n";
      print_r($output);
      echo "#####################\n";
      
      return array(!$error,$output);
		}
	}
	
	
	
	
	static function ssh2Run($ssh_infos, $commands)
	{
    
    if (!is_array($commands))
    {
      $commands = array($commands);
    }
    
    $connection = ssh2_connect($ssh_infos->host);
    $hostkey = ssh2_fingerprint($connection);
    ssh2_auth_pubkey_file($connection, $ssh_infos->user, $ssh_infos->pubkeypath, $ssh_infos->keypath);
    
    $haserror = false;

    $log = array();
    foreach($commands as $command){

      // Run a command that will probably write to stderr (unless you have a folder named /hom)
      $log[] = 'Sending command: '.$command;
      $log[] = '--------------------------------------------------------';
      $stream = ssh2_exec($connection, $command);
      $errorStream = ssh2_fetch_stream($stream, SSH2_STREAM_STDERR);

      // Enable blocking for both streams
      stream_set_blocking($errorStream, true);
      stream_set_blocking($stream, true);

      // Whichever of the two below commands is listed first will receive its appropriate output.  The second command receives nothing
      $log[] = 'Output of command:';
      $log[] = stream_get_contents($stream);
      $log[] = '--------------------------------------------------------';
      $error = stream_get_contents($errorStream);
      if(strlen($error) > 0){
         $haserror = true;
         $log[] = 'Error occured:';
         $log[] = $error;
         $log[] = '------------------------------------------------';
      }

      // Close the streams
      fclose($errorStream);
      fclose($stream);

    }

    //Return the log
    return array($haserror,$log);

  }
	
  
  static function reencode_audio($audiosource, $audiodestination, $remote_ssh_infos=null)
  {
      global $CFG;
      
      $output = '';
      $returnStatus = '';
      if ($remote_ssh_infos==null)
      {
          exec($CFG->ffmpeg_execpath_ffmpeg." -i ".$audiosource." -map_metadata -1 -vn -ar 44100 -ac 2 -q:a 3 ".$audiodestination, $output, $returnStatus);
          
          return array(($returnStatus==0),implode("\n", $output));
      }else{
          list($error,$output) = self::ssh2Run($remote_ssh_infos, array('sudo -u magistere '.$remote_ssh_infos->ffmpeg_path." -i ".$audiosource." -map_metadata -1 -vn -ar 44100 -ac 2 -q:a 3 ".$audiodestination));
          
          echo "#####################\n";
          print_r($output);
          echo "#####################\n";
          
          return array(!$error,$output);
      }
  }
	
	
	
	
}








