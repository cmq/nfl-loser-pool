<?php
/**
 * Filter class to ensure the user is a superadmin.
 *
 */
class SuperadminFilter extends CFilter
{
    protected function preFilter($filterChain)
    {
        $controller = Yii::app()->controller;
        
        if (!isSuperadmin()) {
            $controller->error('Unauthorized access.');
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