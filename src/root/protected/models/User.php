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
            'picks' => array(self::HAS_MANY, 'Pick', 'userid'),
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
            array('username, firstname, lastname', 'length', 'max'=>32),
            array('email', 'length', 'max'=>128),
            array('password', 'length', 'on'=>'insert, changepw', 'max'=>40, 'min'=>40),
            array('active', 'in', 'range'=>array(0,1)),
            // KDHTODO add other rules?
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
        $this->password = UserIdentity::saltPassword($this->password, $this->salt);
        $this->scenario = 'changepw';
        return $this->save();
    }
    
}