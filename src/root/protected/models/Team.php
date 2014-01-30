<?php
class Team extends DeepActiveRecord
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
        return 'loserteam';
    }
    
    /**
     * Define table relationships
     * @see http://www.yiiframework.com/doc/guide/1.1/en/database.arr
     * @see CActiveRecord::relations()
     */
    public function relations()
    {
        return array(
            'picks' => array(self::HAS_MANY, 'Pick', 'teamid'),
        );
    }
    
    /**
     * Convert some fields to their proper datatype so JS will deal with it properly
     * KDHTODO add this type of conversion to other active record models as well
     */
    protected function afterFind() {
        parent::afterFind();
        $this->image_offset = (int) $this->image_offset;
    }    
    
}