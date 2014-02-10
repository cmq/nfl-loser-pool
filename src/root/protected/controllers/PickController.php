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
    
    public function saveAction()
    {
        // KDHTODO verify the user can't save a pick for a past week (unless superadmin)
    }
    
    public function saveAllAction()
    {
        // KDHTODO verify the user can't save a pick for a past week (unless superadmin)
    }

}