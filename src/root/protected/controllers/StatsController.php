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
        // KDHTODO implement
        $this->render('profiles');
    }

    public function actionProfile()
    {
        // KDHTODO implement
        $this->render('profile');
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