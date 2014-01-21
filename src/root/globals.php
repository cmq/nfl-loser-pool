<?php
/**
 * This is the shortcut to Yii::app()->user.
 */

function user() {
    return Yii::app()->getUser();
}
function isAdmin() {
    // KDHTODO implement
}
function isKirk() {
    // KDHTODO implement
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

