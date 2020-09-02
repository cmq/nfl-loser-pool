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
        
        $badUser    = false;
        $pickUser   = null;
        $pickUserId = userId();
        if (isSuperadmin() && isset($_GET['uid'])) {
            $badUser    = true;
            $pickUserId = (int) $_GET['uid'];
            $pickUser   = User::model()->findByPk($pickUserId);
            if ($pickUser) {
                if ((isHardcoreMode() && $pickUser->thisYearHardcore) || (isNormalMode() && $pickUser->thisYearNormal)) {
                    $badUser = false;
                }
            }
            $userPicks = Pick::model()->currentForQSUser()->findAll();
        } else {
            $userPicks = Pick::model()->current()->findAll();
        }
        
        $teams = Team::model()->findAll(array('order'=>'longname'));
        
        $this->render('index', array('picks'=>$userPicks, 'teams'=>$teams, 'badUser'=>$badUser, 'pickUser'=>$pickUser, 'pickUserId'=>$pickUserId));
    }
    
    public function actionSave()
    {
        $error  = '';
        $team   = null;
        
        $userId     = isSuperadmin() ? (int) getRequestParameter('user', 0) : userId();
        $week       = (int) getRequestParameter('week', 0);
        $year       = getCurrentYear();
        $teamId     = (int) getRequestParameter('team', 0);
        $hardcore   = min(max((int) getRequestParameter('hardcore', 0), 0), 1);
        
        if (isLocked($week) && !isSuperadmin()) {
            $error = 'This pick is already locked.';
        } else if ($hardcore && !userHasHardcoreMode()) {
            $error = 'You must be signed up for hardcore mode to make a hardcore pick.';
        } else if (!$hardcore && !userHasNormalMode()) {
            $error = 'You must be signed up for normal mode to make a normal pick.';
        } else if ($hardcore && $teamId > 0) {
            // make sure the user hasn't already made this pick
            $alreadyUsedTeam = Pick::model()->findByAttributes(array(
                'yr'        => $year,
                'userid'    => $userId,
                'teamid'    => $teamId,
                'hardcore'  => $hardcore,
            ));
            if ($alreadyUsedTeam && $alreadyUsedTeam->week != $week) {
                $team = Team::model()->find(array('condition'=>"id = $teamId"));
                $error = 'You have already selected the ' . $team->longname . ' (' . getWeekName($alreadyUsedTeam->week, true) . ')';
            }
        }
        
        if (!$error) {
            $record = Pick::model()->findByAttributes(array(
                'yr'        => $year,
                'week'      => $week,
                'userid'    => $userId,
                'hardcore'  => $hardcore,
            ));
            
            if ($record) {
                $record->teamid = $teamId;
            } else {
                $record = new Pick;
                $record->userid     = $userId;
                $record->week       = $week;
                $record->yr         = $year;
                $record->teamid     = $teamId;
                $record->hardcore   = $hardcore;
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
