<?php
class Bandwagon extends DeepActiveRecord
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
        return 'bandwagon';
    }
    
    /**
     * Define table relationships
     * @see http://www.yiiframework.com/doc/guide/1.1/en/database.arr
     * @see CActiveRecord::relations()
     */
    public function relations()
    {
        return array(
        	'team'  => array(self::BELONGS_TO, 'Team', 'teamid'),
            'chief' => array(self::BELONGS_TO, 'User', 'chiefid'),
        );
    }
    
    /**
     * Define validation rules
     * @see CModel::rules()
     */
    public function rules() {
        return array(
            array('yr, week, teamid, chiefid', 'type', 'type'=>'integer'),
            array('week', 'numerical', 'integerOnly'=>true, 'min'=>1, 'max'=>22),
        );
    }
    
}
