<?php

class ArchiveController extends Controller
{
    
    public $layout = 'main';

    public function actionIndex()
    {
        $this->actionWinners();
    }
    
    public function actionWinners()
    {
        // KDHTODO implement
        $this->render('winners');
    }

    public function actionYear()
    {
        // KDHTODO implement
        $this->render('year');
    }
    
}