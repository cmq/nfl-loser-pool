<?php
/**
 * Filter class to ensure the user has paid their entry fee.
 *
 */
class PaidFilter extends CFilter
{
    protected function preFilter($filterChain)
    {
        $controller = Yii::app()->controller;
        
        if (!isSuperadmin() && !isPaid()) {
            $controller->error('Unauthorized access.  You must pay your entry fee before using this page.');
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