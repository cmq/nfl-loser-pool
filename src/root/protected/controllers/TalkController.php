<?php

class TalkController extends Controller
{
    
    public $layout = 'naked';

    // KDHTODO prevent any actions on this controller from executing if the user is a guest or hasn't paid
    
    public function actionIndex()
    {
        $this->layout = 'main';
        $talks = Talk::model()->current()->withLikes()->findAll(array('order'=>'postedon desc'));
        $users = User::model()->active()->findAll(array('order'=>'username'));
        $this->render('index', array('talks'=>$talks, 'users'=>$users));
    }
    
    public function actionSave()
    {
        $error  = '';
        
        $userId  = (int) getRequestParameter('user', 0);
        $message = trim((string) getRequestParameter('message', ''));
        $admin   = isSuperadmin() ? (int) getRequestParameter('admin', 0) : 0;
        
        if (!$error && !$message) {
            $error = 'The message text cannot be empty.';
        }
        
        if (!$error) {
            $record = new Talk;
            $record->postedby = userId();
            $record->postedat = $userId;
            $record->post     = $message;
            $record->admin    = $admin;
            
            $error = $this->saveRecord($record);
        }
        
        $this->writeJson(array('error'=>$error));
        exit;
    }
    
    public function actionLike()
    {
        $error = '';
        
        $userId = userId();
        $talkId = (int) getRequestParameter('talkid', 0);
        $like   = (int) getRequestParameter('like', 0);
        
        $record = Like::model()->findByAttributes(array('talkid'=>$talkId, 'userid'=>$userId));
        if ($record) {
            $record->active = $like;
        } else {
            $record = new Like;
            $record->talkid = $talkId;
            $record->userid = $userId;
            $record->active = $like;
        }
        $record->save();
        
        $this->writeJson(array('error'=>$error));
        exit;
    }

}