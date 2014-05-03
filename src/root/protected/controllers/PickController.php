<?php

class PickController extends Controller
{
    
    public $layout = 'naked';

    // KDHTODO prevent any actions on this controller from executing if the user is a guest
    public function actionIndex()
    {
        $this->layout = 'main';
        $userPicks = Pick::model()->current()->findAll();
        $teams = Team::model()->findAll(array('order'=>'longname'));
        $this->render('index', array('picks'=>$userPicks, 'teams'=>$teams));
    }
    
    public function actionSave()
    {
        // KDHTODO verify the user can't save a pick for a past week (unless superadmin)
        $error  = '';
        
        $userId = isSuperadmin() ? (int) getRequestParameter('user', 0) : userId();
        $week   = (int) getRequestParameter('week', 0);
        $year   = getCurrentYear();
        $teamId = (int) getRequestParameter('team', 0);
        
        if ($week < getCurrentWeek() || !isSuperadmin()) {
            $error = 'This pick is already locked.';
        }
        
        if (!$error) {
            $record = Pick::model()->findByAttributes(array(
                'yr'     => $year,
                'week'   => $week,
                'userid' => $userId,
            ));
            
            if ($record) {
                $record->teamid = $teamId;
            } else {
                $record = new Pick;
                $record->userid = $userId;
                $record->week   = $week;
                $record->yr     = $year;
                $record->teamid = $teamId;
            }
            $error = $this->saveRecord($record);
        }
        
        $this->writeJson(array('error'=>$error));
        exit;
    }

}