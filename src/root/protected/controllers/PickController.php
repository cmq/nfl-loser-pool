<?php

class PickController extends Controller
{
    
    public $layout = 'naked';

    // KDHTODO prevent any actions on this controller from executing if the user is a guest
    public function actionIndex()
    {
        // no index action
        // KDHTODO redirect to 404
    }
    
    public function actionSave()
    {
        // KDHTODO verify the user can't save a pick for a past week (unless superadmin)
        $error  = '';
        $params = json_decode(file_get_contents('php://input'), true);    // angular sends POST data differently than jQuery
        
        $userId = isSuperadmin() ? (int) $params['user'] : userId();
        $week   = (int) $params['week'];
        $year   = getCurrentYear();
        $teamId = (int) $params['team'];
        
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
    
    public function actionSaveAll()
    {
        // KDHTODO verify the user can't save a pick for a past week (unless superadmin)
    }

}