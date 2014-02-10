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
            'team' => array(self::BELONGS_TO, 'Team', 'teamid'),
            'mov'  => array(self::BELONGS_TO, 'Mov',  array('teamid', 'yr', 'week')),
        );
    }
    
    /**
     * Define scopes
     * @see CActiveRecord::scopes()
     */
    public function scopes()
    {
        return array(
            'current' => array(
                'condition' => 't.yr = ' . getCurrentYear() . ' and t.userid = ' . userId(),
                'with'      => array('team', 'mov'),
            ),
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
    
    /**
     * Convert some fields to their proper datatype so JS will deal with it properly
     */
    protected function afterFind() {
        parent::afterFind();
        $this->teamid      = (int)  $this->teamid;
        $this->userid      = (int)  $this->userid;
        $this->week        = (int)  $this->week;
        $this->yr          = (int)  $this->yr;
        $this->incorrect   = (bool) $this->incorrect;
        $this->setbysystem = (bool) $this->setbysystem;
    }    
    
}