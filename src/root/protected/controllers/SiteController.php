<?php

class SiteController extends Controller
{
    
    private function _getBoardData()
    {
        // for some reason if these scopes aren't applied in exactly the right order, something gets missed.
        // ironically, if you apply them in the same order twice, the second time everything works properly... must be something jacked with Yii
        $boardData = User::model()->withPicks(getCurrentYear(), isSuperadmin())->active()->withBadges()->withWins()->findAll(array(
            'select' => 't.id, t.username, t.avatar_ext, t.power_points, t.power_ranking',
            'order' => 't.username, t.id, picks.yr, picks.week, wins.place, wins.pot, wins.yr, badge.zindex',
        ));
        return $boardData;
    }
    
    private function _getBandwagon()
    {
        $bandwagon = Bandwagon::model()->with(array('team', 'chief'))->findAll(array(
            'condition' => 't.yr = ' . getCurrentYear(),
        ));
        return $bandwagon;
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
        $boardData = $this->_getBoardData();
        $bandwagon = $this->_getBandwagon();
        $talk = Talk::model()->withLikes()->findAll(array(
            'condition' => 't.yr = ' . getCurrentYear(),
            'limit'     => 5,
            'order'     => 't.postedon desc'
        ));
        $this->render('index', array('boardData'=>$boardData, 'bandwagon'=>$bandwagon, 'talk'=>$talk));
    }
    
    public function actionPoll()
    {
        $boardData = $this->_getBoardData();
        $bandwagon = $this->_getBandwagon();
        $this->writeJson(array('board'=>$boardData, 'bandwagon'=>$bandwagon));
        exit;
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