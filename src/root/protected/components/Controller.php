<?php
/**
 * Controller is the customized base controller class.
 * All controller classes for this application should extend from this base class.
 */
class Controller extends CController
{
    public $layout='//layouts/main';
	
	
    /**
     * Check if an action is allowed
     */
    public function allowed($action) {
	    return (isset($this->_allow) && array_search($action, $this->_allow) !== false);
    }
    
    /**
     * Get an instance of the Model class represented by this controller
     */
    public function model() {
        if (isset($this->_modelClass)) {
    	    return call_user_func($this->_modelClass . '::model');
        }
        return null;
    }
    
    
    /**
     * Cause an error to happen, which will output it to the screen and stop
     * execution.
     * @param String  $error        the error message to show
     */
    public function error($error) {
		if (Yii::app()->request->isAjaxRequest) {
            $this->layout = '//layouts/naked';
            $this->writeJson(array('error'=>$error));
		} else {
		    echo $error;
            exit;
		}
    }
    
	/**
	 * This is the action to handle external exceptions.
	 */
	public function actionError() {
		$error = Yii::app()->errorHandler->error;
		if ($error) {
			if (Yii::app()->request->isAjaxRequest) {
                $this->layout = '//layouts/naked';
                $this->writeJson(array('error'=>$error));
			} else {
				$this->render('error', $error);
			}
		}
	}
	
	/**
	 * A universal function to reply with a JSON object
	 * @param mixed $obj
	 */
	public function writeJson($obj) {
	    $this->layout = '//layouts/naked';
	    echo CJSON::encode($obj);
	}
	
	
    /**
     * Attempt to save a record in the database
     * @param CActiveRecord $record
     * @return String the error (empty string means no error)
     */
    public function saveRecord($record) {
        $error = '';
        try {
            if (!$record->save()) {
	            $error = combineErrors($record);
            }
        } catch (Exception $e) {
	        $error = $e->getMessage();
        }
        return $error;
    }
    
    
    /**
     * Attempt to "delete" a record in the database
     * @param CActiveRecord $record
     * @return String the error (empty string means no error)
     */
    public function deleteRecord($record) {
        $error = '';
        try {
            if (!$record->delete()) {
	            $error = combineErrors($record);
            }
        } catch (Exception $e) {
	        $error = $e->getMessage();
        }
        return $error;
    }
    
    
    /**
     * Build some common actions
     */
    // KDHTODO see which of these methods is transferrable from dough to lp2
	
    
    /**
     * This is the default 'index' action that is invoked
     * when an action is not explicitly requested by users.
     */
    public function actionIndex() {
        $this->render('index');
    }
    
    /**
	 * Deactivate a record
	 */
	public function actionDelete() {
	    if ($this->allowed('delete')) {
    	    $record = $this->model()->findByPk(getInt('id', 0));
    	    if (!$record) {
    	        $error = 'Unable to locate the specified record.';
    	    } else {
    	        $error = $this->deleteRecord($record);
    	    }
	    } else {
	        $error = 'Delete action not allowed.';
	    }
	    $this->writeJson(array('error'=>$error));
	    exit;
	}
	
	/**
	 * Reactivate a record
	 */
	public function actionUndelete() {
	    $error = '';
	    if ($this->allowed('undelete')) {
	        $record = $this->model()->findByPk(getInt('id', 0));
    	    if (!$record) {
    	        $error = 'Unable to locate the specified record.';
    	    } else {
                if (isset($record->_parentUndeleteCheckClass) && isset($record->_parentUndeleteCheckField)) {
                    $key = $record->_parentUndeleteCheckField;
            	    $parentClass = call_user_func($record->_parentUndeleteCheckClass . '::model');
                    $parent = $parentClass->active()->findByPk($record->$key);
                    if (!$parent) {
                        $error = 'You may not undelete this record because its parent record is deleted.';
                    }
                }
                if (!$error) {
        	        $record->active = 1;
        	        $error = $this->saveRecord($record);
                }
    	    }
	    } else {
	        $error = 'Undelete action not allowed.';
	    }
	    $this->writeJson(array('error'=>$error));
	    exit;
	}
    
    
}