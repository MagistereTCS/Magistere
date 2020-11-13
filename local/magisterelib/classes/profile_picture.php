<?php
// require_once('../../../config.php');

class local_magisterelib_profile_picture
{
    public static function create(&$user)
    {
        global $CFG, $DB, $USER;

        $dbconn = get_centralized_db_connection();
        $avatar = $dbconn->get_record('cr_avatars', array('id' => $user->picture));

        if($avatar != false){
            $filepath_secure_url = '/avatar/img.png'; // Path du fichier pour l'utilisation de la fonction secure_url
            $url = secure_url($filepath_secure_url,$avatar->hashname,$CFG->secure_link_timestamp_default);
            $url = new moodle_url($url);

            return $url;
        }

        $createDate = time();
        $filename = 'img.png';
        $hashname = sha1($user->email . $filename . $createDate); // Hash sur l'email, le nom du fichier et le timestamp

        //copy to our folder and delete the draft file of moodle
        $folder = $CFG->centralized_avatar_path . '/' . substr($hashname, 0, 2) . '/';

        // On cree le si besoin dossier qui contiendra le fichier avec les 2 premiers caract?res du hashname
        if (!file_exists($folder)) {
            mkdir($folder, 0775, true);
        }


        $newavatar = self::generate($user->firstname, $user->lastname);
        $dest = $folder . $hashname .'.png'; // Path du fichier une fois deplace

        rename($newavatar, $dest);

        $data = array(
                'hashname' => $hashname,
                'email' => $user->email
        );
        // Id de la nouvelle entree en base dans la table cr_avatars
        $newpicture = $dbconn->insert_record('cr_avatars', $data);

        $DB->set_field('user', 'picture', $newpicture, array('id' => $user->id));
        $user->picture = $newpicture;
        if($USER->id == $user->id) {
            $USER->picture = $newpicture;
        }

        $filepath_secure_url = '/avatar/img.png'; // Path du fichier pour l'utilisation de la fonction secure_url
        $url = secure_url($filepath_secure_url,$hashname,$CFG->secure_link_timestamp_default);
        $url = new moodle_url($url);

        // update on the frontal
        if(isset($user->auth) && $user->auth == 'shibboleth' && !isfrontal()){
            require_once($CFG->dirroot.'/local/magisterelib/userProfileSynchronisation.php');
            //Load data from the frontal
            $userProfileSynchronisation = new UserProfileSynchronisation();
            $userProfileSynchronisation->updateFrontalUser($user);
        }

        require_once($CFG->dirroot.'/local/magisterelib/databaseConnection.php');
        $list_academy = get_magistere_academy_config();
        foreach ($list_academy as $academy_name => $data)
        {
            unset($acaDB);
            if ($academy_name == 'frontal' && $academy_name == 'hub'){
                continue;
            }

			if(($acaDB = databaseConnection::instance()->get($academy_name)) === false){
				continue;
			}

            // update if user has not set avatar
            $acaDB->set_field('user', 'picture', $newpicture, array('auth' => 'shibboleth', 'email' => $user->email, 'picture' => 0));
        }

        return $url;
    }


    public static function generate($firstname, $lastname)
    {
        global $CFG;

        $str = $firstname.'-'.$lastname;
        $str = self::remove_accent($str);

        $initials = self::get_initials($str);
        $initials = strtoupper($initials);

        $filename = $CFG->tempdir.'/profile_picture_'.time().'.png';

        $mycolor = substr(md5($firstname.$lastname), 0, 8);

        $mycolor = base_convert($mycolor, 16, 10) % 4;

        $colors = array(
            array(232, 110, 121),// #e86e79
            array(155, 88, 183), // #9b58b7
            array(26, 188, 156), // #1abc9c
            array(51, 149, 224),// #3395e0
        );

        self::generate_picture($filename, $initials, $colors[$mycolor]);

        return $filename;
    }

    private static function get_initials($str)
    {
        $str = trim($str);
        if(empty($str)){
            return '';
        }

        $initials = '';

        $str = str_replace('-', ' ', $str);
        $str = explode(' ', $str, 6);

        for($i = 0; $i < count($str); $i++){
            if(!empty($str[$i])) {
                $initials .= $str[$i][0];
            }
        }

        return $initials;
    }

    private static function generate_picture($file, $initials, $bgcolor)
    {
        global $CFG;

        $iw = 35;
        $ih = 35;

        $font = $CFG->dirroot.'/local/magisterelib/font/OpenSans-Bold.ttf';
        $text = $initials;
        $fontsize = 12;
        $padding = 5;
        $fontsize = self::adjustFontSize(($iw - $padding), $fontsize, $initials, $font);

        $size = self::calculateTextBox($fontsize, 0, $font, $text);

        $im = imagecreatetruecolor($iw, $ih);

        $bg = imagecolorallocate($im, $bgcolor[0], $bgcolor[1], $bgcolor[2]);
        imagefilledrectangle($im, 0, 0, $iw, $ih, $bg);

        $xcoord = $size['left'] + ($iw - $size['width']) / 2 ;
        $ycoord = $size['top'] + ($ih - $size['height']) / 2;

        $white = imagecolorallocate($im, 255, 255, 255);
        imageTTFText($im, $fontsize, 0, $xcoord, $ycoord, $white, $font, $text);

        imagepng($im, $file);
        imagedestroy($im);
    }

    /*
     * Compute bbox pixel-perfect
     * gd standard functions doesn't include the baseline of letter like pyq
     */
    private static function calculateTextBox($font_size, $font_angle, $font_file, $text) {
      $box   = imagettfbbox($font_size, $font_angle, $font_file, $text);
      if( !$box )
        return false;
      $min_x = min( array($box[0], $box[2], $box[4], $box[6]) );
      $max_x = max( array($box[0], $box[2], $box[4], $box[6]) );
      $min_y = min( array($box[1], $box[3], $box[5], $box[7]) );
      $max_y = max( array($box[1], $box[3], $box[5], $box[7]) );
      $width  = ( $max_x - $min_x );
      $height = ( $max_y - $min_y );
      $left   = abs( $min_x ) + $width;
      $top    = abs( $min_y ) + $height;
      // to calculate the exact bounding box i write the text in a large image
      $img     = @imagecreatetruecolor( $width << 2, $height << 2 );
      $white   =  imagecolorallocate( $img, 255, 255, 255 );
      $black   =  imagecolorallocate( $img, 0, 0, 0 );
      imagefilledrectangle($img, 0, 0, imagesx($img), imagesy($img), $black);
      // for sure the text is completely in the image!
      imagettftext( $img, $font_size,
                    $font_angle, $left, $top,
                    $white, $font_file, $text);
      // start scanning (0=> black => empty)
      $rleft  = $w4 = $width<<2;
      $rright = 0;
      $rbottom   = 0;
      $rtop = $h4 = $height<<2;
      for( $x = 0; $x < $w4; $x++ )
        for( $y = 0; $y < $h4; $y++ )
          if( imagecolorat( $img, $x, $y ) ){
            $rleft   = min( $rleft, $x );
            $rright  = max( $rright, $x );
            $rtop    = min( $rtop, $y );
            $rbottom = max( $rbottom, $y );
          }
      // destroy img and serve the result
      imagedestroy( $img );
      return array( "left"   => $left - $rleft,
                    "top"    => $top  - $rtop,
                    "width"  => $rright - $rleft + 1,
                    "height" => $rbottom - $rtop + 1 );
    }

    private static function adjustFontSize($widthMax, $fontsize, $text, $font)
    {

        $textw = imagettfbbox($fontsize, 0, $font, $text);
        $textw = abs($textw[2] - $textw[0]);

        while($textw > $widthMax && $fontsize > 0){
            $fontsize--;
            $textw = imagettfbbox($fontsize, 0, $font, $text);
            $textw = abs($textw[2] - $textw[0]);
        }

        return $fontsize;
    }

    private static function remove_accent($str, $charset='utf-8')
    {
        $str = htmlentities($str, ENT_NOQUOTES, $charset);

        $str = preg_replace('#&([A-za-z])(?:acute|cedil|caron|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $str);
        $str = preg_replace('#&([A-za-z]{2})(?:lig);#', '\1', $str); // pour les ligatures e.g. '&oelig;'
        $str = preg_replace('#&[^;]+;#', '', $str); // supprime les autres caractères

        return html_entity_decode($str);
    }
}

// local_magisterelib_profile_picture::generate('N', 'N');