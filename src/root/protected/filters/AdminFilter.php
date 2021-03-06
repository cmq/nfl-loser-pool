<?php
/**
 * Filter class to ensure the user is an admin.
 *
 */
class AdminFilter extends CFilter
{
    protected function preFilter($filterChain)
    {
        $controller = Yii::app()->controller;
        
        if (!isAdmin()) {
            $controller->error('Unauthorized access.  You must be an admin to use this page.');
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