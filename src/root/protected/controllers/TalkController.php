<?php

class TalkController extends Controller
{
    
    public $layout = 'naked';

    // KDHTODO prevent any actions on this controller from executing if the user is a guest or hasn't paid
    
    public function actionIndex()
    {
        // no index action
        // KDHTODO redirect to site/talk
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

}