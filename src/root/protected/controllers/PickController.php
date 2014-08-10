<?php

class PickController extends Controller
{
    
    public $layout = 'naked';

    public function filters() {
        return array(
            array('application.filters.RegisteredFilter'),
        );
    }
    
    public function actionIndex()
    {
        $this->layout = 'main';
        $userPicks = Pick::model()->current()->findAll();
        $teams = Team::model()->findAll(array('order'=>'longname'));
        $this->render('index', array('picks'=>$userPicks, 'teams'=>$teams));
    }
    
    public function actionSave()
    {
        $error  = '';
        $team   = null;
        
        $userId = isSuperadmin() ? (int) getRequestParameter('user', 0) : userId();
        $week   = (int) getRequestParameter('week', 0);
        $year   = getCurrentYear();
        $teamId = (int) getRequestParameter('team', 0);
        
        if (isLocked($week) && !isSuperadmin()) {
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
        
        if (!$error) {
            $team = Team::model()->find(array('condition'=>"id = $teamId"));
        }
        
        $this->writeJson(array('error'=>$error, 'team'=>$team));
        exit;
    }

}