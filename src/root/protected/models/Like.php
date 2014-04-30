<?php
class Like extends DeepActiveRecord
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
        return 'likes';
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
            'talk' => array(self::BELONGS_TO, 'Talk', 'talkid'),
        );
    }

    /**
     * Define validation rules
     * @see CModel::rules()
     */
    public function rules() {
        return array(
        array('active', 'in', 'range'=>array(0,1)),
        );
    }
    


    /**
     * Save the record
     * This overrides the default CActiveRecord::save in order to set up some
     * defaults
     * @see CActiveRecord::save()
     */
    public function save($runValidation = true, $attributes = NULL)
    {
        if ($this->getIsNewRecord()) {
            $this->created = new CDbExpression('NOW()');
        } else {
            $this->updated = new CDbExpression('NOW()');
        }
        return parent::save($runValidation, $attributes);
    }
    
}