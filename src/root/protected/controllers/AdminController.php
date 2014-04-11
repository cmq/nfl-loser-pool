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
            array('application.filters.SuperadminFilter + superadmintest'),     // KDHTODO are there any superadmin-only functions?
        );
    }
    
    /**
     * This is the default 'index' action that is invoked
     * when an action is not explicitly requested by users.
     */
    public function actionIndex()
    {
        // KDHTODO is there anything to do here?
        die('?');
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
     * Save corrections in the database
     */
    public function actionCorrect()
    {
        // KDHTODO implement
        
    }
    
    public function actionSuperadmintest()
    {
        // KDHTODO this is just a placeholder for any SUPER admin functions (so that we can build the sample hole punching in the filters() method above)
    }
    
    /**
     * This is the action to handle external exceptions.
     */
    public function actionError()
    {
        if($error=Yii::app()->errorHandler->error)
        {
            if(Yii::app()->request->isAjaxRequest)
                echo $error;
            else
                $this->render('error', $error);
        }
    }

}