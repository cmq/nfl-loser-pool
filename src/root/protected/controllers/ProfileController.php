<?php

class ProfileController extends Controller
{
    
    public $layout = 'naked';

    // KDHTODO prevent any actions on this controller from executing if the user is a guest
    public function actionIndex()
    {
        // no index action
        // KDHTODO redirect to 404
    }
    
    public function actionAvatar()
    {
        // KDHTODO prevent user from making this change unless they have paid
        // KDHTODO obviously test this, including the ability for superadmin to modify another user's stuff
        
        $userId = (isset($_GET['uid']) && isSuperadmin() ? (int) $_GET['uid'] : userId());
        $user = User::model()->findByPk($userId);
        
        if (!$user) {
            $error = 'User not found.';
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
                $user->save();
                
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