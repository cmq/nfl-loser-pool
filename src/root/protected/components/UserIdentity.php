<?php

/**
 * UserIdentity represents the data needed to identity a user.
 * It contains the authentication method that checks if the provided
 * data can identity the user.
 */
class UserIdentity extends CUserIdentity
{
    static $_salt = 'ZZ4lt__';    // this is the static salt that is applied to every user password
    private $_id;
    
    
    /**
     * salt a password
     * @param String $rawPassword the raw password to salt
     * @param String $userSalt the individual user's personalized salt
     * @static
     */
    static function saltPassword($rawPassword='', $userSalt='') {
	    return sha1($userSalt . md5($rawPassword) . self::$_salt);
    }
    
	/**
	 * Authenticates a user.
	 * @return boolean whether authentication succeeds.
	 */
	public function authenticate() {
        $record = User::model()->findByAttributes(array('username'=>$this->username, 'active'=>1));
        if ($record === null) {
            $this->errorCode = self::ERROR_USERNAME_INVALID;
            $this->errorMessage = 'Invalid username or password.';
        } else if ($record->password !== self::saltPassword($this->password, $record->salt)) {
            $this->errorCode = self::ERROR_PASSWORD_INVALID;
            $this->errorMessage = 'Invalid username or password.';
        } else {
            $this->errorCode = self::ERROR_NONE;
            $this->_id = $record->id;
            // KDHTODO set state information here, via:
            // $this->setState('field', $record->field);
            $this->setState('record', $record);
        }
        return !$this->errorCode;
	}
	
	/**
	 * Overriding getId, which by default returns username, to instead return
	 * the actual user ID that we store in a private var.
	 * @see CUserIdentity::getId()
	 */
	public function getId() {
	    return $this->_id;
	}
}