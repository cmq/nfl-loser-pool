<?php
class Pick extends DeepActiveRecord
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
        return 'loserpick';
    }
    
    public function primaryKey()
    {
        return array('userid', 'week', 'yr');
    }
        
    /**
     * Define table relationships
     * @see http://www.yiiframework.com/doc/guide/1.1/en/database.arr
     * @see CActiveRecord::relations()
     */
    public function relations()
    {
        return array(
            'user' => array(self::BELONGS_TO, 'User', 'userid'),
            'team' => array(self::HAS_ONE, 'Team', 'id'),
        );
    }
    
    
    
    
    /**
     * Define validation rules
     * @see CModel::rules()
     */
    public function rules() {
        return array(
            array('year, week, teamid', 'required'),
            array('year, week, teamid', 'type', 'type'=>'integer'),
            array('week', 'in', 'range'=>array(1,21)),
        );
    }
    
}