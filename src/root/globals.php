<?php
/**
 * This is the shortcut to Yii::app()->user.
 */

function user() {
    return Yii::app()->user;
}

function userId() {
    $user = user();
    if (isset($user->id)) {
        return (int) $user->id;
    }
    return 0;
}

function userField($field) {
    $user = user();
    if (isset($user->record) && isset($user->record[$field])) {
        return $user->record[$field];
    }
    return false;
}

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
    

/**
 * This is the shortcut to Yii::app()->request->baseUrl
 * If the parameter is given, it will be returned and prefixed with the app baseUrl.
 */
function baseUrl($url = null) {
    static $baseUrl;
    if ($baseUrl===null)
        $baseUrl = Yii::app()->getRequest()->getBaseUrl();
    return $url===null ? $baseUrl : $baseUrl.'/'.ltrim($url,'/');
}
 
/**
 * Returns the named application parameter.
 * This is the shortcut to Yii::app()->params[$name].
 */
function param($name, $default=null, $paramSet=null) {
    if (!$paramSet) {
        $paramSet = Yii::app()->params;
    }
    if (strpos($name, '.') !== false) {
        $parts = explode('.', $name);
        $key = $parts[0];
        if (isset($paramSet[$key])) {
            array_splice($parts, 0, 1);
            return param(implode('.', $parts), $default, $paramSet[$key]);
        }
        return $default;
    } else {
        if (isset($paramSet[$name])) {
            return $paramSet[$name];
        }
        return $default;
    }
}

/**
 * Allow access to deep array properties through dot notation
 * @param String $key        The key to lookup ('key.otherkey.id')
 * @param Array  $collection The array to deep-examine
 */
function getDotDelimited($key, $collection=null) {
    if ($collection === null) {
        $collection = $_REQUEST;
    }
    $keys = explode('.', $key);
    if (count($keys) === 1) {
        if (isset($collection[$key])) {
            return $collection[$key];
        }
        return null;
    } else {
        if (isset($collection[$keys[0]])) {
            $spliced = array_splice($keys, 0, 1);
            return getDotDelimited(implode('.', $keys), $collection[$spliced[0]]);
        }
        return null;
    }
}

/**
 * Returns the request parameter if found, otherwise the default
 */
function get($key, $default=null, $doNotTrim=false) {
    $value = getDotDelimited($key);
    if ($value !== null) {
        return $doNotTrim || is_array($value) ? $value : trim($value);
    }
    return $default;
}

/**
 * get a String, optionally limiting its length
 */
function getString($key, $default=null, $maxlength=0, $doNotTrim=false) {
    $val = (string) get($key, $default, $doNotTrim);
    if ($maxlength) {
        $val = substr($val, 0, $maxlength);
    }
    return $val;
}

/**
 * get an Integer, optionally limiting its range
 */
function getInt($key, $default=null, $min=null, $max=null) {
    $val = (int) get($key, $default);
    if ($min !== null) {
        $val = max($val, $min);
    }
    if ($max !== null) {
        $val = min($val, $max);
    }
    return $val;
}

/**
 * get a Float, optionally limiting its range
 */
function getFloat($key, $default=null, $min=null, $max=null) {
    $val = (float) str_replace('$', '', str_replace(',', '', get($key, $default)));
    if ($min !== null) {
        $val = max($val, $min);
    }
    if ($max !== null) {
        $val = min($val, $max);
    }
    return $val;
}

/**
 * get a Boolean
 */
function getBoolean($key, $default=null) {
    $val = getString($key, $default);
    if (strtolower($val) == 'true' || $val == '1') {
        return true;
    }
    if (strtolower($val) == 'false' || $val = '0') {
        return false;
    }
    return ($val ? true : false);
}

	
/**
 * Combine the errors that come from CModel->getErrors()
 * Output will be a newline-delimited string of error messages
 * @param CModel $model
 */
function combineErrors($model, $delimiter = "\n") {
    $e = array();
    $errors = $model->getErrors();
    if ($errors) {
	    if (is_array($errors)) {
	        foreach ($errors as $attribute => $attributeErrors) {
	            if (is_array($attributeErrors)) {
	                $e = array_merge($e, $attributeErrors);
	            } else {
	                $e[] = $attributeErrors;
	            }
	        }
	    } else {
	        $e[] = (string) $errors;
	    }
    }
    return implode($e, $delimiter);
}

