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
        // KDHTODO get badges/trophies as well
        // KDHTODO allow searching?
        $users = User::model()->findAll(array(
            'order' => 't.username'
        ));
        $this->render('profiles', array('users'=>$users));
    }

    public function actionProfile()
    {
        // KDHTODO get all stats, badges, trophies, etc
        $userId  = (int) getRequestParameter('id', 0);
        $user = User::model()->withYears()->findByPk($userId);
        $this->render('profile', array('user'=>$user));
    }

    public function actionRankings()
    {
        // KDHTODO implement
        $this->render('rankings');
    }

    public function actionPicks()
    {
        // KDHTODO implement
        $this->render('picks');
    }
    
}