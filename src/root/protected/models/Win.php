<?php
class Win extends DeepActiveRecord
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
        return 'winners';
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
     * Convert some fields to their proper datatype so JS will deal with it properly
     */
    protected function afterFind() {
        parent::afterFind();
        $this->pot =      (int)   $this->pot;
        $this->place =    (int)   $this->place;
        $this->winnings = (float) $this->winnings;
    }    
}