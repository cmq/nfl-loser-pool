<?php

class SecurityController extends Controller
{
    public $layout = '//layouts/naked';
    
    public function actionLogin() {
        $errorMessage = '';
        
        $identity = new UserIdentity(getString('username', ''), getString('password', ''));
        if ($identity->authenticate()) {
            // credentials are authenticated
            if (!user()->isGuest) {
                // the user is already logged in as someone...
                // error if they are logging in as someone different
                if ($identity->getId() !== user()->id) {
                    $errorMessage = 'You are already logged in as ' . user()->getFullname() . '.';
                }
            } else {
                // the user is not logged in yet, log them in
                Yii::app()->user->login($identity);
            }
        } else {
            // could not authenticate
            $errorMessage = $identity->errorMessage;
        }
        
        $this->writeJson(array('error'=>$errorMessage));
    }
    
    public function actionLogout() {
        if (!user()->isGuest) {
            Yii::app()->user->logout();
        }
        $this->redirect(array('site/index'));
    }
}
