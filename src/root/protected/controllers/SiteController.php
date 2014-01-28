<?php

class SiteController extends Controller
{
    /**
     * Declares class-based actions.
     */
    public function actions()
    {
        // KDHTODO can we make use of these?
        return array(
            // captcha action renders the CAPTCHA image displayed on the contact page
            'captcha'=>array(
                'class'=>'CCaptchaAction',
                'backColor'=>0xFFFFFF,
            ),
            // page action renders "static" pages stored under 'protected/views/site/pages'
            // They can be accessed via: index.php?r=site/page&view=FileName
            'page'=>array(
                'class'=>'CViewAction',
            ),
        );
    }

    /**
     * This is the default 'index' action that is invoked
     * when an action is not explicitly requested by users.
     */
    public function actionIndex()
    {
        // KDHTODO restrict the fields that are selected (shouldn't select firstname/lastname, etc)
        // KDHTODO set parameters for the withPicks() call based on whether the current user is kirk or not (for future picks)
        $boardData = User::model()->active()->withBadges()->withPicks()->findAll(array(
            'order' => 't.id, picks.yr, picks.week',
            'condition' => 't.id <= 20'    // KDHTODO remove this after JS objects are set up
        ));
        $this->render('index', array('boardData'=>$boardData));
    }

    /**
     * This is the action to handle external exceptions.
     */
    public function actionError()
    {
        if($error=Yii::app()->errorHandler->error)
        {
            if(Yii::app()->request->isAjaxRequest)
                echo $error['message'];
            else
                $this->render('error', $error);
        }
    }

    /**
     * Displays the login page
     */
    public function actionLogin()
    {
        // KDHTODO handle the case when it's an AJAX request instead of a direct POST
        $errorMessage = '';
        if (isset($_POST['username']) && isset($_POST['password'])) {
            $identity = new UserIdentity($_POST['username'], $_POST['password']);
            if ($identity->authenticate()) {
                Yii::app()->user->login($identity, isset($_POST['rememberMe']) ? 3600*24*30 : 0);    // remember for 30 days if they say so
                $this->redirect(Yii::app()->user->returnUrl);
            } else {
                $errorMessage = $identity->errorMessage;
            }
        }
        
        // if we get here, display the login form
        $this->render('login', array('errorMessage'=>$errorMessage));
    }

    /**
     * Logs out the current user and redirect to homepage.
     */
    public function actionLogout()
    {
        Yii::app()->user->logout();
        $this->redirect(Yii::app()->homeUrl);
    }
}