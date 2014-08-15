<?php
/**
 * Filter class to ensure the user is an admin.
 *
 */
class CorrectFilter extends CFilter
{
    protected function preFilter($filterChain)
    {
        $controller = Yii::app()->controller;
        
        $week = isset($_GET['week']) ? (int) $_GET['week'] : getCurrentWeek();
        if ($week > getCurrentWeek() && !isSuperadmin()) {
            $controller->error('You may not mark corrections for the week until it is locked.');
            return false;
        } else {
            return true;
        }
    }
    
    
    protected function postFilter($filterChain)
    {
        // logic being applied after the action is executed
    }
}