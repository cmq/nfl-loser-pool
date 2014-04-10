<?php

class SiteController extends Controller
{
    /**
     * declare action filters
     * @see CController::filters()
     */
    public function filters() {
        return array(
            array('application.filters.RegisteredFilter + profile, pick'),
            array('application.filters.PaidFilter + profile'),
        );
    }
    
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
        // for some reason if these scopes aren't applied in exactly the right order, something gets missed.
        // ironically, if you apply them in the same order twice, the second time everything works properly... must be something jacked with Yii
        $boardData = User::model()->withPicks(true, isSuperadmin())->active()->withBadges()->withWins()->findAll(array(
            'select' => 't.id, t.username, t.avatar_ext, t.power_points, t.power_ranking, t.previous_power_ranking, t.previous_power_points, t.best_power_ranking',
            'order' => 't.id, picks.yr, picks.week',
        ));
        $talk = Talk::model()->findAll(array(
            'condition' => 't.yr = ' . getCurrentYear(),
            'limit'     => 5,
            'order'     => 't.postedon desc'
        ));
        $this->render('index', array('boardData'=>$boardData, 'talk'=>$talk));
    }
    
    /**
     * A separate page for the user to make their picks
     */
    public function actionPick()
    {
        // KDHTODO prevent this action if the user is a guest
        $userPicks = Pick::model()->current()->findAll();
        $teams = Team::model()->findAll(array('order'=>'longname'));
        $this->render('pick', array('picks'=>$userPicks, 'teams'=>$teams));
    }

    /**
     * A separate page for the user to adjust their profile
     */
    public function actionProfile()
    {
        // KDHTODO prevent this action if the user is a guest
        // KDHTODO prevent this action if the user hasn't paid
        $userId = (isset($_GET['uid']) && isSuperadmin() ? (int) $_GET['uid'] : userId());
        $user = User::model()->findByPk($userId); 
        $this->render('profile', array('user'=>$user));
    }

    /**
     * This is the action to handle external exceptions.
     */
    public function actionError()
    {
        if($error=Yii::app()->errorHandler->error)
        {
            if(Yii::app()->request->isAjaxRequest)
                echo $error;
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