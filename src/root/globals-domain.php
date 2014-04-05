<?php
function isAdmin() {
    return (bool) userField('admin');
}

function isSuperadmin() {
    return (bool) userField('superadmin');
}

function getCurrentYear() {
    return param('currentYear');
}

function getCurrentWeek() {
    $currentWeek = param('currentWeek');
    if ($currentWeek) {
        return $currentWeek;
    }
    // KDHTODO remove the following line -- it is in place only for developing v2
    return 19;
    
    $dNow        = time();
    $firstGames  = param('firstGame');
    $currentWeek = 0;
    for ($i=1; $i<=21; $i++) {
    	if ($dNow > ($firstGames[$i]-(60*60))) {
    		// "now" is greater than the first game of week $i minus an hour (60*60 seconds)
    		$currentWeek = $i;
    	}
    }
    return $currentWeek;
}

function getHeaderWeek() {
    $headerWeek = param('currentWeek');
    if ($headerWeek) {
        return $headerWeek;
    }
    
    $dNow       = time();
    $firstGames = param('firstGame');
    $headerWeek = 0;
    for ($i=1; $i<=21; $i++) {
    	if ($dNow > ($firstGames[$i]-(60*60*24*5))) {
    	    // the first game of week $i is within 5 days
    		$headerWeek = $i;
    	} else if ($i > 20 && $dNow > ($firstGames[$i]-(60*60*24*12))) {
    		// otherwise, a special case for the superbowl week... if we're within 12 days
    		$headerWeek = $i;
    	}
    }
    $headerWeek = min(max(1, $headerWeek), 21);
    if ($headerWeek < 18 && date('w', $firstGames[$headerWeek]) > 1) {
    	// the first game of the header week is on a Tuesday-Saturday (i.e., not Sunday) -- if the current day is not yet Tuesday,
    	// continue to show the previous week in the header, because even though we're within 5 days of the next week's games starting,
    	// the current week is still going on!
    	// Note that this doesn't apply during the playoffs
        if (date('w', $dNow) < 2) {
    		$headerWeek--;
    	}
    }
    return $headerWeek;
}

function getWeekName($week) {
    switch ($week) {
        case 21:
            return 'Superbowl';
            break;
        case 20:
            return 'Conf Champ';
            break;
        case 19:
            return 'Divisional';
            break;
        case 18:
            return 'Wild Card';
            break;
        default:
            return $week;
            break;
    }
}

function getLockTime($week, $format=false) {
    // KDHTODO get real lock time
    $locktime = new DateTime();
    modifyTimeForUser($locktime);
    // KDHTODO adjust format
    return $format ? $locktime->format('m/d/y h:i a') : $locktime;
}

function modifyTimeForUser(&$datetime) {
    // figure out how the user's timezone applies
    $hours = 0;
    if (userField('timezone')) {
        $hours = (int) userField('timezone');
    }
    // figure out how the user's dst settings apply
    if (date('I')) {
        // it is currently daylight savings time
        if (!userField('use_dst')) {
            // but the user doesn't want to use daylight savings time
            $hours -= 1;
        }
    }
    if ($hours > 0) {
        $datetime->add(new DateInterval("PT{$hours}H"));
    } else if ($hours < 0) {
        $hours *= -1;
        $datetime->sub(new DateInterval("PT{$hours}H"));
    }
}

function createThumbnail($source, $destination, $maxWidth, $maxHeight) {
    // tried to copy this from http://mediumexposure.com/smart-image-resizing-while-preserving-transparency-php-and-gd-library/ but
    // kept having problems with .gif transparencies.... copied their function instead and it seems to work, too tired to figure
    // out what the difference is
    try {
        $imageinfo = getimagesize($source);
        $ext = $imageinfo[2];

        // create a new source image resource
        $sourceImage = null;
        switch ($ext) {
            case IMAGETYPE_JPEG:
                $sourceImage = imagecreatefromjpeg($source);
                break;
            case IMAGETYPE_GIF:
                $sourceImage = imagecreatefromgif($source);
                imagealphablending($sourceImage, true);
                break;
            case IMAGETYPE_PNG:
                $sourceImage = imagecreatefrompng($source);
                imagealphablending($sourceImage, true);
                break;
        }

        if ($sourceImage) {
            $sourceWidth = imagesx($sourceImage);
            $sourceHeight = imagesy($sourceImage);
            $sourceRatio = $sourceWidth/$sourceHeight;

            // determine the dimensions of the target image
            if ($maxWidth >= $sourceWidth && $maxHeight >= $sourceHeight) {
                // no need to resize
                $targetWidth = $sourceWidth;
                $targetHeight = $sourceHeight;
            } else {
                $targetRatio = $maxWidth/$maxHeight;
                if ($sourceRatio > $targetRatio) {
                    // source is too wide, maximize width and scale height to match
                    $targetWidth = $maxWidth;
                    $targetHeight = (int) $targetWidth/$sourceRatio;
                } else {
                    // source is too tall (or just right), maximize height and scale width to match
                    $targetHeight = $maxHeight;
                    $targetWidth = (int) $targetHeight*$sourceRatio;
                }
            }

            // create the target image
            $targetImage = imagecreatetruecolor($targetWidth, $targetHeight);

            // handle transparency
            if ($ext == IMAGETYPE_GIF || $ext == IMAGETYPE_PNG) {
                $transparentIndex = imagecolortransparent($sourceImage);
                if (false) {    /* This method was copied from several places on the internet, but doesn't work for all gif files
                    if ($transparentIndex >= 0) {*/
                    // we have a specific transparent color
                    $palletsize = imagecolorstotal($sourceImage);
                    if ($palletsize > $transparentIndex) {
                        // get the original image's transparent color's RGB values
                        $transparentColor = imagecolorsforindex($sourceImage, $transparentIndex);
                        // allocate the same color in the new image resource
                        $transparentIndex = imagecolorallocatealpha($targetImage, $transparentColor['red'], $transparentColor['green'], $transparentColor['blue'], 127);
                    } else {
                        // got an "index out of range" error on a file I tested... try this instead
                        $transparentIndex = imagecolorallocatealpha($targetImage, 255, 255, 255, 127);
                    }
                    // completely fill the background of the new image with the allocated color
                    imagefill($targetImage, 0, 0, $transparentIndex);
                    // set the background color for the new image to transparent
                    imagecolortransparent($targetImage, $transparentIndex);
                    // try again to set the background to transparent, pixel by pixel
                    for ($x=0; $x<$targetWidth; $x++) {
                        for ($y=0; $y<$targetHeight; $y++) {
                            imagesetpixel($targetImage, $x, $y, $transparentIndex);
                        }
                    }
                } else {    /* The above method was copied from several places on the internet, but doesn't work for all gif files, so we'll use the below in all cases instead
                    } elseif ($ext == IMAGETYPE_PNG) {*/
                    // for PNGs without a default transparency, make one
                    // temporarily turn off transparency blending
                    imagealphablending($targetImage, false);
                    // create a new transparent color for the image
                    $transparent = imagecolorallocatealpha($targetImage, 255, 255, 255, 127);
                    // completely fill the background of the new image with the allocated color
                    imagefill($targetImage, 0, 0, $transparent);
                    // restore transparency blending
                    imagesavealpha($targetImage, true);
                }
            }

            // create the target image
            imagecopyresampled($targetImage, $sourceImage, 0, 0, 0, 0, $targetWidth, $targetHeight, $sourceWidth, $sourceHeight);
            switch ($ext) {
                case IMAGETYPE_JPEG:
                    imagejpeg($targetImage, $destination);
                    break;
                case IMAGETYPE_GIF:
                    imagegif($targetImage, $destination);
                    break;
                case IMAGETYPE_PNG:
                    imagepng($targetImage, $destination);
                    break;
            }

        }
    } catch (Exception $e) {
        return false;
    }
    return true;
}


function getUserAvatar($userid, $ext) {
    $url  = getUserAvatarUrl($userid, $ext);
    $turl = getUserAvatarUrl($userid, $ext, true);
    return "<a href=\"$url?x=" . time() . "\" class=\"avatar\" id=\"avatar$userid\"><img src=\"$turl\" /></a>";
}


function getUserAvatarUrl($userid, $ext, $thumb=false) {
    if (!$ext) {
        $userid = 0;
        $ext = 'png';
    }
    return param('avatarWebDirectory') . '/' . ($thumb ? 't' : '') . "$userid.$ext";
}
