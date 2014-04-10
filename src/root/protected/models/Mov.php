<?php
class Mov extends DeepActiveRecord
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
        return 'mov';
    }
    
    /**
     * Define table relationships
     * @see http://www.yiiframework.com/doc/guide/1.1/en/database.arr
     * @see CActiveRecord::relations()
     */
    public function relations()
    {
        return array(
            'picks' => array(self::HAS_MANY, 'Pick', array('teamid', 'yr', 'week')),
            'team'  => array(self::BELONGS_TO, 'Team', 'teamid'),
        );
    }
    
    /**
     * Convert some fields to their proper datatype so JS will deal with it properly
     */
    protected function afterFind() {
        parent::afterFind();
        $this->teamid      = (int)  $this->teamid;
        $this->week        = (int)  $this->week;
        $this->yr          = (int)  $this->yr;
        $this->mov         = (int)  $this->mov;
    }    
    
}