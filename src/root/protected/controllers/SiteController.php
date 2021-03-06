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
        $boardData = User::model()->withPicks(getCurrentYear(), null, isSuperadmin())->active()->withBadges()->withWins()->findAll(array(
            'condition' => (isPaid() ? '' : 't.id = ' . userId()),
            'select'    => 't.id, t.username, t.avatar_ext, t.power_points, t.power_ranking',
            'order'     => 't.username, t.id, picks.yr, picks.week, wins.place, wins.pot, wins.yr, badge.zindex',
        ));
        return $boardData;
    }
    
    private function _getBandwagon()
    {
        $bandwagon = Bandwagon::model()->with(array('team', 'chief'))->findAll(array(
            'condition' => 't.yr = ' . getCurrentYear() . ' and t.hardcore = ' . (isHardcoreMode() ? '1' : '0')
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
            $talk = Talk::model()->current()->withLikes()->findAll(array(
                'condition' => 't.active = 1',
                'limit'     => 5,
                'order'     => 't.sticky desc, case t.sticky when 1 then t.id else t.id * -1 end asc'
            ));
            $this->render('index', array('boardData'=>$boardData, 'bandwagon'=>$bandwagon, 'bestWorst'=>getBestWorst(getCurrentYear()), 'talk'=>$talk));
        }
    }
    
    public function actionPoll()
    {
        $boardData = $this->_getBoardData();
        $bandwagon = $this->_getBandwagon();
        $this->writeJson(array('board'=>$boardData, 'bandwagon'=>$bandwagon, 'bestWorst'=>getBestWorst(getCurrentYear())));
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
     * This is the action to handle external exceptions.
     */
    public function actionSwitch()
    {
        $error  = '';
        $mode   = isset($_POST['mode']) ? $_POST['mode'] : '';
        
        if ($mode == 'hardcore') {
            // asking to switch to hardcore
            if (!userHasHardcoreMode()) {
                $error = 'You are not playing hardcore mode this year.';
            } else if (isHardcoreMode()) {
                $error = 'You are already in hardcore mode.';
            } else {
                $_SESSION['mode'] = 'hardcore';
            }
        } else {
            // asking to switch to normal
            if (!userHasNormalMode()) {
                $error = 'You are not playing normal mode this year.';
            } else if (isNormalMode()) {
                $error = 'You are already in normal mode.';
            } else {
                $_SESSION['mode'] = 'normal';
            }
        }
        
        $this->writeJson(array('error'=>$error));
        exit;
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
                // default the game mode
                $user = user();
                $userHasNormal      = $user->thisYearNormal   ? true : false;
                $userHasHardcore    = $user->thisYearHardcore ? true : false;
                if (isset($_SESSION['mode'])) {
                    $mode = $_SESSION['mode'];
                    if ($mode == 'hardcore' && !$userHasHardcore) {
                        //die('setting session to normal 1');
                        $_SESSION['mode'] = 'normal';
                    } else if ($mode != 'hardcore' && !$userHasNormal) {
                        //die('setting session to hardcore 1');
                        $_SESSION['mode'] = 'hardcore';
                    }
                } else {
                    if ($userHasNormal) {
                        //die('setting session to normal 2');
                        $_SESSION['mode'] = 'normal';
                    } else if ($userHasHardcore) {
                        //die('setting session to hardcore 2');
                        $_SESSION['mode'] = 'hardcore';
                    }
                }
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
