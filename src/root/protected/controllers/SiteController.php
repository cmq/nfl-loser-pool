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
        $boardData = User::model()->active()->withBadges()->withPicks(true, isSuperadmin())->findAll(array(
            'select' => 't.id, t.username, t.avatar_ext, t.power_points, t.power_ranking, t.previous_power_ranking, t.previous_power_points, t.best_power_ranking',
            'order' => 't.id, picks.yr, picks.week',
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