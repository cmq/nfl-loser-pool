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
            array('application.filters.SuperadminFilter + superadmintest'),
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
        
        $picks = Pick::model()->with(array(
            'user' => array(
                'select' => 'username'
            ),
            'team' => array(
                'select' => 'longname'
            ),
        ))->findAll(array(
            'condition' => "t.week = $week and t.yr = $year",
            'order'     => 'user.username',
        ));
        
        $movs = Mov::model()->with(array(
            'team' => array(
                'select' => 'longname'
            ),
        ))->findAll(array(
            'condition' => "t.week = $week and t.yr = $year",
            'order' => 'team.longname',
        ));
        
        $this->render('correct', array('picks'=>$picks, 'movs'=>$movs));
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