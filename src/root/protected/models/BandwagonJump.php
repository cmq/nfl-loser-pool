<?php
class BandwagonJump extends DeepActiveRecord
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
        return 'bandwagonjump';
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
        );
    }
    
    /**
     * Define validation rules
     * @see CModel::rules()
     */
    public function rules() {
        return array(
            array('yr, week, userid, previous_weeks', 'type', 'type'=>'integer'),
            array('week', 'numerical', 'integerOnly'=>true, 'min'=>1, 'max'=>21),
        );
    }
    
}