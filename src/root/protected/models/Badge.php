<?php
class Badge extends DeepActiveRecord
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
        return 'badge';
    }
    
    /**
     * Define table relationships
     * @see http://www.yiiframework.com/doc/guide/1.1/en/database.arr
     * @see CActiveRecord::relations()
     */
    public function relations()
    {
        return array(
            'unlockedBy' => array(self::BELONGS_TO, 'User', 'unlocked_userid'),
        );
    }
    
    /**
     * Convert some fields to their proper datatype so JS will deal with it properly
     */
    protected function afterFind() {
        parent::afterFind();
        $this->permanent    = (bool) $this->permanent;
        $this->replicable   = (bool) $this->replicable;
        $this->power_points = (float) $this->power_points;
        $this->zindex       = (int)  $this->zindex;
    }    
}