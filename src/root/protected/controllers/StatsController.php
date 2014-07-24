<?php

class StatsController extends Controller
{
    
    public $layout = 'main';

    public function actionIndex()
    {
        $this->actionProfiles();
    }
    
    public function actionProfiles()
    {
        $users = User::model()->withBadges()->withWins()->findAll(array(
            'select' => 't.id, t.username, t.power_ranking, t.power_points, t.avatar_ext, t.active',
            'order'  => 't.username'
        ));
        $this->render('profiles', array('users'=>$users));
    }

    public function actionProfile()
    {
        $userId  = (int) getRequestParameter('id', 0);
        $user = User::model()->withYears()->withStats()->withBadges()->withWins()->findByPk($userId);
        $allStats = UserStat::model()->withUser()->findAll(array(
            'order' => 't.statid, t.place, user.username'
        ));
        $this->render('profile', array('user'=>$user, 'allStats'=>$allStats));
    }

    public function actionPicks()
    {
        // KDHTODO need to fix this -- this query is super slow, might have to write it ourselves
        
        $teams = Team::model()->findAll();
        $picks = Pick::model()->findAll(array(
            'condition' => 'yr < ' . getCurrentYear() . ' or week < ' . getCurrentWeek()
        ));
        $users = User::model()->withBadges()->withWins()->findAll(array(
            'select' => 't.id, t.username, t.power_ranking, t.power_points, t.avatar_ext, t.active',
            'order'  => 't.username'
        ));
        $this->render('picks', array('users'=>$users, 'picks'=>$picks, 'teams'=>$teams));
    }
    
}