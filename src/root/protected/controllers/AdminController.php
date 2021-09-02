<?php

class AdminController extends Controller
{

    private function _getWeek() {
        return (isset($_REQUEST['week']) ? (int) $_REQUEST['week'] : getCurrentWeek());
    }
    
    private function _getYear() {
        return (isset($_REQUEST['year']) ? (int) $_REQUEST['year'] : getCurrentYear());
    }
    
    /**
     * declare action filters
     * @see CController::filters()
     */
    public function filters() {
        return array(
            array('application.filters.AdminFilter'),
            array('application.filters.SuperadminFilter + showCreateUser, createUser, markPaid, activateUser, resetPassword'),
            array('application.filters.CorrectFilter + showCorrect, correct'),
        );
    }
    
    /**
     * This is the default 'index' action that is invoked
     * when an action is not explicitly requested by users.
     */
    public function actionIndex()
    {
        die('?');   // what to do here?
    }
    
    /**
     * Show the page to correct entries
     */
    public function actionShowCorrect()
    {
        $week = $this->_getWeek();
        $year = $this->_getYear();
        
        $users = User::model()->active()->with(array(
            'picks' => array(
                'on' => "picks.week = $week and picks.yr = $year and picks.hardcore = " . (isHardcoreMode() ? '1' : '0'),
                'with' => array(
                    'team' => array(
                        'select' => 'longname',
                    ),
                ),
            ),
        ))->findAll(array(
            'order' => 't.username',
        ));
        
        $teams = Team::model()->with(array(
            'mov' => array(
                'on' => "mov.week = $week and mov.yr = $year",
            ),
        ))->findAll(array(
            'order' => 't.longname',
        ));
        
        $this->render('correct', array('users'=>$users, 'teams'=>$teams));
    }
    
    /**
     * Save corrections and mov values in the database
     */
    public function actionCorrect()
    {
        $error = '';
        try {
            $week = (int) $_REQUEST['week'];
            $year = getCurrentYear();
            $data = json_decode($_REQUEST['data'], true);
            $hc   = (isHardcoreMode() ? '1' : '0');
            
            $usersCorrect   = implode(',', listToIntegerArray(implode(',', $data['correct'])));
            $usersIncorrect = implode(',', listToIntegerArray(implode(',', $data['incorrect'])));
            $usersNotSet    = implode(',', listToIntegerArray(implode(',', $data['notset'])));
            
            if ($usersCorrect) {
                $sql = "update loserpick set incorrect = 0 where yr = $year and week = $week and userid in ($usersCorrect) and hardcore = $hc";
                $results = Yii::app()->db->createCommand($sql)->query();
            }
            if ($usersIncorrect) {
                $sql = "update loserpick set incorrect = 1 where yr = $year and week = $week and userid in ($usersIncorrect) and hardcore = $hc";
                $results = Yii::app()->db->createCommand($sql)->query();
            }
            if ($usersNotSet) {
                $sql = "update loserpick set incorrect = null where yr = $year and week = $week and userid in ($usersNotSet) and hardcore = $hc";
                $results = Yii::app()->db->createCommand($sql)->query();
            }
            
            foreach ($data['mov'] as $team => $mov) {
                $mov = (int) $mov;
                $sql = "replace into mov (teamid, yr, week, mov) values (" . (int) str_replace('team', '', $team) . ", $year, $week, $mov)";
                $results = Yii::app()->db->createCommand($sql)->query();
            }
            
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
        $this->writeJson(array('error'=>$error));
    }
    
    public function actionShowCreateUser()
    {
        $users = User::model()->findAll(array(
            'order' => 't.username',
        ));
        
        $this->render('createUser', array('users'=>$users));
    }
    
    public function actionCreateUser()
    {
        $error = '';
        try {
            
            $email      = trim(getRequestParameter('email', ''));
            $username   = trim(getRequestParameter('username', ''));
            $password   = trim(getRequestParameter('password', ''));
            $salt       = trim(getRequestParameter('salt', ''));
            $firstname  = trim(getRequestParameter('firstname', ''));
            $lastname   = trim(getRequestParameter('lastname', ''));
            $referrer   = (int) getRequestParameter('referrer', '');
            $active     = (int) getRequestParameter('active', 1);
            $paid       = (int) getRequestParameter('paid', 0);
            $paidnote   = trim(getRequestParameter('paidnote', ''));
            $hcactive   = (int) getRequestParameter('hcactive', 1);
            $hcpaid     = (int) getRequestParameter('hcpaid', 0);
            $hcpaidnote = trim(getRequestParameter('hcpaidnote', ''));
            
            // check the email
            if (!$error && !isEmail($email)) {
                $error = "The email address $email is not valid.";
            }
            
            // check the username
            if (!$error && empty($username)) {
                $error = 'You must supply a username';
            }
            if (!$error) {
                $duplicateUser = User::model()->findByAttributes(array('username'=>$username));
                if ($duplicateUser) {
                    $error = "The username $username is already taken.";
                }
            }
            
            // check the other fields
            if (!$error && empty($password)) {
                $error = 'You must supply a password';
            }
            if (!$error && empty($salt)) {
                $error = 'You must supply a password salt';
            }
            if (!$error && empty($firstname)) {
                $error = 'You must supply a first name';
            }
            if (!$error && empty($lastname)) {
                $error = 'You must supply a last name';
            }
            
            if (!$error) {
                // we are going to add this user
                
                // see if we need to insert them into the master email list or just update it to make sure it's active
                $sql = "select * from email where email = '" . addslashes($email) . "'";
                $rsEmail = Yii::app()->db->createCommand($sql)->query();
                if (count($rsEmail) < 1) {
                    $sql = "insert into email (email, active) values ('" . addslashes($email) . "', 1)";
                    Yii::app()->db->createCommand($sql)->query();
                } else {
                    $sql = "update email set active = 1 where email = '" . addslashes($email) . "'";
                    Yii::app()->db->createCommand($sql)->query();
                }
                
                // now insert the base user
                $user = new User;
                $user->username = $username;
                $user->original_username = $username;
                $user->password = UserIdentity::saltPassword($password, $salt);
                $user->salt = $salt;
                $user->password_plain = $password;
                $user->firstname = $firstname;
                $user->lastname = $lastname;
                $user->email = $email;
                $user->active = $active || $hcactive ? 1 : 0;
                $user->created = date('YmdHis');
                $user->power_points = 0;
                $user->power_ranking = 999;
                $user->referrer = $referrer;
                $error = $this->saveRecord($user);
                
                if (!$error) {
                    if ($active) {
                        // create a record for the user in normal mode
                        $sql = "insert into loseruser (userid, paid, paidnote, hardcore, yr) values (" . $user->getPrimaryKey() . ", $paid, '" . addslashes($paidnote) . "', 0, " . getCurrentYear() . ")";
                        Yii::app()->db->createCommand($sql)->query();
                    }
                    if ($hcactive) {
                        // create a record for the user in hardcore mode
                        $sql = "insert into loseruser (userid, paid, paidnote, hardcore, yr) values (" . $user->getPrimaryKey() . ", $hcpaid, '" . addslashes($hcpaidnote) . "', 1, " . getCurrentYear() . ")";
                        Yii::app()->db->createCommand($sql)->query();
                    }
                }
            }
            
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
        $this->writeJson(array('error'=>$error));
    }
    
    public function actionMarkPaid()
    {
        $error = '';
        try {
            $userId     = (int) getRequestParameter('id', 0);
            $user       = User::model()->findByPk($userId);
            $paidnote   = addslashes(trim(getRequestParameter('paidnote', '')));
            $yr         = getCurrentYear();
            if ($user && !empty($paidnote)) {
                $sql = "update loseruser set paid=1, paidnote='$paidnote' where userid=$userId and yr = $yr and hardcore=" . (isHardcoreMode() ? '1' : '0');
                Yii::app()->db->createCommand($sql)->query();
            } else {
                if ($user) {
                    $error = 'Paid note not supplied.';
                } else {
                    $error = 'User not found.';
                }
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
        $this->writeJson(array('error'=>$error));
    }
    
    public function actionActivateUser()
    {
        $error = '';
        try {
            $userId = (int) getRequestParameter('id', 0);
            $user   = User::model()->findByPk($userId);
            if ($user) {
                $user->active = 1;
                $error = $this->saveRecord($user);
                if (!$error) {
                    $sql = "insert into loseruser (userid, yr, hardcore) values ($userId, " . getCurrentYear() . ", " . (isHardcoreMode() ? '1' : '0') . ")";
                    Yii::app()->db->createCommand($sql)->query();
                }
            } else {
                $error = 'User not found.';
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
        $this->writeJson(array('error'=>$error));
    }
    
    public function actionResetPassword()
    {
        $error = '';
        try {
            $pw      = getRequestParameter('newpw', '');
            $user    = User::model()->findByPk((int) getRequestParameter('id', 0));
            if ($user && !empty($pw)) {
                $user->password = UserIdentity::saltPassword($pw, $user->salt);
                $error = $this->saveRecord($user);
            } else {
                if ($user) {
                    $error = 'New password not supplied.';
                } else {
                    $error = 'User not found.';
                }
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
        $this->writeJson(array('error'=>$error));
    }
    
    /**
     * This is the action to handle external exceptions.
     */
    public function actionError()
    {
        if ($error=Yii::app()->errorHandler->error) {
            if (Yii::app()->request->isAjaxRequest) {
                echo $error;
            } else {
                $this->render('error', $error);
            }
        }
    }

}
