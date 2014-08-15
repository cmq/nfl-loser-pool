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
            array('application.filters.SuperadminFilter + superadmintest'),
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
                'on' => "picks.week = $week and picks.yr = $year",
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
            
            $usersCorrect   = implode(',', listToIntegerArray(implode(',', $data['correct'])));
            $usersIncorrect = implode(',', listToIntegerArray(implode(',', $data['incorrect'])));
            $usersNotSet    = implode(',', listToIntegerArray(implode(',', $data['notset'])));
            
            if ($usersCorrect) {
                $sql = "update loserpick set incorrect = 0 where yr = $year and week = $week and userid in ($usersCorrect)";
                $results = Yii::app()->db->createCommand($sql)->query();
            }
            if ($usersIncorrect) {
                $sql = "update loserpick set incorrect = 1 where yr = $year and week = $week and userid in ($usersIncorrect)";
                $results = Yii::app()->db->createCommand($sql)->query();
            }
            if ($usersNotSet) {
                $sql = "update loserpick set incorrect = null where yr = $year and week = $week and userid in ($usersNotSet)";
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
    
    public function actionSuperadmintest()
    {
        // this is just a placeholder for any SUPER admin functions (so that we can build the sample hole punching in the filters() method above)
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