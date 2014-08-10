<?php

class SiteController extends Controller
{
    
    public function filters() {
        return array(
            array('application.filters.RegisteredFilter + poll'),
        );
    }
    
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
     * This is the default 'index' action that is invoked
     * when an action is not explicitly requested by users.
     */
    public function actionIndex()
    {
        if (isGuest()) {
            $this->render('index-guest');
        } else {
            $boardData = $this->_getBoardData();
            $bandwagon = $this->_getBandwagon();
            $talk = Talk::model()->withLikes()->findAll(array(
                'condition' => 't.yr = ' . getCurrentYear() . (isSuperadmin() ? '' : ' and t.active = 1'),
                'limit'     => 5,
                'order'     => 't.postedon desc'
            ));
            $this->render('index', array('boardData'=>$boardData, 'bandwagon'=>$bandwagon, 'talk'=>$talk));
        }
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
        $errorMessage = '';
        
        if (!Yii::app()->request->isAjaxRequest && !isGuest()) {
            // this is a regular page display GET request (not an AJAX (login) request), but the user is already logged in
            $this->redirect(Yii::app()->user->returnUrl);
        }
        
        if (isset($_POST['username']) && isset($_POST['password'])) {
            $identity = new UserIdentity($_POST['username'], $_POST['password']);
            if ($identity->authenticate()) {
                Yii::app()->user->login($identity, isset($_POST['rememberMe']) ? 3600*24*180 : 0);    // remember for 180 days if they say so
                if (!Yii::app()->request->isAjaxRequest) {
                    $this->redirect(Yii::app()->user->returnUrl);
                }
            } else {
                $errorMessage = $identity->errorMessage;
            }
        }
        
		if (Yii::app()->request->isAjaxRequest) {
            echo CJSON::encode(array('error'=>$errorMessage));
            exit;
		} else {
            $this->render('login', array('errorMessage'=>$errorMessage));
		}
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