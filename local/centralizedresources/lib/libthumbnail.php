<?php
function thumbnailImage($inputFileName, $maxSize = 100)
{
	global $CFG;
	
	$maxsizeh = $CFG->cr_thumbnail_image_max_width;
	$maxsizew = $CFG->cr_thumbnail_image_max_height;
	
	$info = getimagesize($inputFileName);

	$type = isset($info['type']) ? $info['type'] : $info[2];

	// Check support of file type
	if ( !(imagetypes() & $type) )
	{
		// Server does not support file type
		return false;
	}

	$width = isset($info['width']) ? $info['width'] : $info[0];
	$height = isset($info['height']) ? $info['height'] : $info[1];

	// Calculate aspect ratio
	$wRatio = $maxsizew / $width;
	$hRatio = $maxsizeh / $height;

	// Using imagecreatefromstring will automatically detect the file type
	$sourceImage = imagecreatefromstring(file_get_contents($inputFileName));

	// Calculate a proportional width and height no larger than the max size.
	if ( ($width <= $maxsizew) && ($height <= $maxsizeh) )
	{
		// Input is smaller than thumbnail, do nothing
		return $sourceImage;
	}
	elseif ( ($wRatio * $height) < $maxsizeh )
	{
		// Image is horizontal
		$tHeight = ceil($wRatio * $height);
		$tWidth = $maxsizew;
	}
	else
	{
		// Image is vertical
		$tWidth = ceil($hRatio * $width);
		$tHeight = $maxsizeh;
	}

	$thumb = imagecreatetruecolor($tWidth, $tHeight);

	if ( $sourceImage === false )
	{
		// Could not load image
		return false;
	}

	// Copy resampled makes a smooth thumbnail
	imagecopyresampled($thumb, $sourceImage, 0, 0, 0, 0, $tWidth, $tHeight, $width, $height);
	imagedestroy($sourceImage);

	return $thumb;
}

function imageToFile($im, $fileName, $quality = 80)
{
	if ( !$im || file_exists($fileName) )
	{
		return false;
	}

	$ext = strtolower(substr($fileName, strrpos($fileName, '.')));

	switch ( $ext )
	{
		case '.gif':
			imagegif($im, $fileName);
			break;
		case '.jpg':
		case '.jpeg':
			imagejpeg($im, $fileName, $quality);
			break;
		case '.png':
			imagepng($im, $fileName);
			break;
		case '.bmp':
			imagewbmp($im, $fileName);
			break;
		default:
			return false;
	}

	return true;
}