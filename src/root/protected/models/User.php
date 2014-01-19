<?php
class User extends DeepActiveRecord
{
    
    /**
     * Returns the static model of the specified AR class.
     * @param string $className active record class name.
     * @return Event the static model class
     */
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        // KDHTODO get real user table
        return 'user';
    }
    
    /**
     * Define table relationships
     * @see http://www.yiiframework.com/doc/guide/1.1/en/database.arr
     * @see CActiveRecord::relations()
     */
    public function relations()
    {
        return array(
            // KDHTODO add other relations
            //'portfolio' => array(self::BELONGS_TO, 'Portfolio', 'portfolio_id'),
        );
    }
    
    
    
    
    /**
     * Define validation rules
     * @see CModel::rules()
     */
    public function rules() {
        return array(
            array('username', 'required', 'except'=>'delete'),
            array('username', 'unique'),
            array('username, firstname, lastname', 'length', 'max'=>50),
            array('password', 'length', 'min'=>5, 'on'=>'insert, changepw'),
            // KDHTODO add other rules
            //array('portfolio_owner, can_budget, can_transfer_categories, can_transact, can_reconcile_budget, can_clear_spending', 'in', 'range'=>array(0,1)),
        );
    }
    
    
    /**
     * Deactivate the record
     * This overrides the default CActiveRecord::delete in order to simply
     * deactivate everything instead of truly deleting it.
     * @override
     * @see CActiveRecord::delete()
     */
    public function delete() {
        $this->active = 0;
        $this->scenario = 'delete';
        return $this->save();
    }
    
    
    /**
     * Change the user's password
     */
    public function changepw() {
        // KDHTODO make sure $this->salt is available in the user class
        $this->password = UserIdentity::saltPassword($this->password, $this->salt);
        $this->scenario = 'changepw';
        return $this->save();
    }
    
}