<?php

class ProfileController extends Controller
{
    
    public $layout = 'naked';
    
    
    private function _getUser()
    {
        $userId = (isset($_REQUEST['uid']) && isSuperadmin() ? (int) $_REQUEST['uid'] : userId());
        $user   = User::model()->findByPk($userId);
        return $user;
    }

    // KDHTODO prevent any actions on this controller from executing if the user is a guest
    // KDHTODO should some of the validations in this controller move into the user model?  Probably.
    public function actionIndex()
    {
        // no index action
        // KDHTODO redirect to 404
    }
    
    public function actionUsername()
    {
        $user     = $this->_getUser();
        $username = getRequestParameter('value', '');
        $error    = '';
        
        if (!$user) {
            $error = 'User not found.';
        }
        
        if (!$error && !isSuperadmin() && !$user->paid) {
            $error = 'You have not paid your entry fee.';
        }
        
        if (!$error && $username == '') {
            $error = 'You must specify a username.';
        }
        
        if (!$error) {
            $duplicateUser = User::model()->findByAttributes(array('username'=>$username));
            if ($duplicateUser && $duplicateUser->id != $user->id) {
                $error = "The username <strong>$username</strong> is already taken.";
            }
        }
        
        if (!$error) {
            $user->username = $username;
            $error = $this->saveRecord($user);
        }
        
        $this->writeJson(array('error'=>$error));
        exit;
    }
    
    public function actionEmail()
    {
        $user  = $this->_getUser();
        $email = getRequestParameter('value', '');
        $error = '';
        
        if (!$user) {
            $error = 'User not found.';
        }
        
        if (!$error && !isSuperadmin() && !$user->paid) {
            $error = 'You have not paid your entry fee.';
        }
        
        if (!$error && !isEmail($email)) {
            $error = 'Your email address does not appear to be valid.';
        }
        
        if (!$error) {
            $oldEmail = $user->email;
            $user->email = $email;
            $error = $this->saveRecord($user);
            $sql = "update email set email = '" . addslashes($email) . "' where email = '" . addslashes($oldEmail) . "'";
            $results = Yii::app()->db->createCommand($sql)->query();
        }
        
        $this->writeJson(array('error'=>$error));
        exit;
    }
    
    public function actionTimezone()
    {
        $user     = $this->_getUser();
        $timezone = (int) getRequestParameter('timezone', 0);
        $dst      = (int) getRequestParameter('dst', 1);
        $error    = '';
        
        if (!$user) {
            $error = 'User not found.';
        }
        
        if (!$error && !isSuperadmin() && !$user->paid) {
            $error = 'You have not paid your entry fee.';
        }
        
        if (!$error) {
            $user->timezone = $timezone;
            $user->use_dst  = $dst;
            $error = $this->saveRecord($user);
        }
        
        $this->writeJson(array('error'=>$error));
        exit;
    }
    
    public function actionChangepw()
    {
        $user  = $this->_getUser();
        $old   = getRequestParameter('old', '');
        $new1  = getRequestParameter('new1', '');
        $new2  = getRequestParameter('new2', '');
        $error = '';
        
        if (!$user) {
            $error = 'User not found.';
        }
        
        if (!$error && !isSuperadmin() && !$user->paid) {
            $error = 'You have not paid your entry fee.';
        }
        
        if (!$error && $new1 == '') {
            $error = 'You must specify a password.';
        }
            
        if (!$error && $new1 != $new2) {
            $error = 'The passwords you entered do not match.';
        }
        
        if (!$error) {
            $password = UserIdentity::saltPassword($old, $user->salt);
            if ($user->password != $password) {
                $error = 'Your old password does not match';
            }
        }
        
        if (!$error) {
            $user->password       = UserIdentity::saltPassword($new1, $user->salt);
            $user->password_plain = $new1;      // KDHTODO should we remove this?
            $error = $this->saveRecord($user);
        }
        
        $this->writeJson(array('error'=>$error));
        exit;
    }
    
    public function actionAvatar()
    {
        // KDHTODO prevent user from making this change unless they have paid
        // KDHTODO obviously test this, including the ability for superadmin to modify another user's stuff
        
        $user = $this->_getUser();
        
        if (!$user) {
            $this->writeJson(array('error'=>'User not found.'));
            exit;
        } else {
            
            Yii::import('application.lib.*');
            require_once('fileuploader.php');

            $allowedExtensions = param('avatarExtensions');
            $sizeLimit         = param('avatarSizeLimit');
            
            $uploader = new qqFileUploader($allowedExtensions, $sizeLimit);
        
            // Call handleUpload() with the name of the folder, relative to PHP's getcwd()
            $result = $uploader->handleUpload(param('avatarDirectory') . DIRECTORY_SEPARATOR, true, $userId);
            if (isset($result['success']) && $result['success']) {
                $source      = $result['uploadDirectory'] . $result['basename'];
                $destination = $result['uploadDirectory'] . 't' . $result['basename'];
                $extension   = $result['ext'];
                
                // if they uploaded a gif, we're going to convert it to png because the thumbnail creator is having problems with some transparencies in gifs
                if ($extension == 'gif') {
                    $destination = $result['uploadDirectory'] . $result['filename'] . '.png';
                    $original = imagecreatefromgif($source);
                    if (file_exists($destination)) {
                        @unlink($destination);
                    }
                    imagepng($original, $destination);
                    $source = $destination;
                    $destination = $result['uploadDirectory'] . 't' . $result['filename'] . '.png';
                    $extension = 'png';
                }
                
                // update the user's extension
                $user->avatar_ext = $extension;
                $error = $this->saveRecord($user);
                
                // create a thumbnail
                if (file_exists($destination)) {
                    @unlink($destination);
                }
                createThumbnail($source, $destination, param('avatarMaxWidth'), param('avatarMaxHeight'));
                
                // add the thumb URL to the response
                $result['thumbnail']    = getUserAvatar($userId, $extension);
                $result['thumbnailurl'] = getUserAvatarUrl($userId, $extension, true);
                
                // for security reasons, empty out the upload directory
                unset($result['uploadDirectory']);
            }
        
            // to pass data through iframe you will need to encode all html tags
            echo htmlspecialchars(json_encode($result), ENT_NOQUOTES);
            exit;
        }
    }

}