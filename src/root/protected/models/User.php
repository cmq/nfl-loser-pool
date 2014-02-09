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
            'picks'      => array(self::HAS_MANY, 'Pick', 'userid'),
            'userBadges' => array(self::HAS_MANY, 'UserBadge', 'userid'),
            'badges'     => array(self::HAS_MANY, 'Badge', array('badgeid'=>'id'), 'through'=>'userBadges'),
            'userYears'  => array(self::HAS_MANY, 'UserYear', 'userid'),
            'wins'       => array(self::HAS_MANY, 'Win', 'userid'),
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
            array('admin', 'in', 'range'=>array(0,1)),
            array('superadmin', 'in', 'range'=>array(0,1)),
            // KDHTODO add other rules?
        );
    }
    
    /**
     * Define scopes
     * @see CActiveRecord::scopes()
     */
    public function scopes()
    {
        return array(
            'active' => array(
                'condition' => 't.active=1',
                'with'      => array(
                    'userYears' => array(
                        'condition' => 'userYears.yr = ' . param('currentYear'),
                    ),
                ),
            ),
            'withBadges' => array(
                'with' => array(
                    'userBadges' => array(
                        'select' => 'display',
                        'with' => array(
                            'badge' => array(
                                'select' => array('name', 'img', 'display', 'zindex'),
                            ),
                        ),
                    ),
                ),
            ),
            'withWins' => array(
                'with' => array(
                    'wins' => array(
            			'select' => array('yr', 'pot', 'place', 'winnings'),
                    ),
                ),
            ),
        );
    }
    
    public function withPicks($currentYearOnly = true, $future = false)
    {
        $condition = '';
        if ($currentYearOnly) {
            $condition .= ($condition ? ' AND ' : '') . 'picks.yr = ' . param('currentYear');
        }
        if (!$future) {
            $condition .= ($condition ? ' AND ' : '') . 'picks.week < ' . getCurrentWeek();
        }
        
        $this->getDbCriteria()->mergeWith(array(
            'with' => array(
                'picks' => array(
                    'on'   => $condition,
                    'with' => array('team', 'mov'),
                ),
            ),
        ));
        return $this;
    }    
    
    
    /**
     * Deactivate the record
     * This overrides the default CActiveRecord::delete in order to simply
     * deactivate everything instead of truly deleting it.
     * @override
     * @see CActiveRecord::delete()
     */
    // KDHTODO implement delete user method?
    public function delete() {
        $this->active = 0;
        $this->scenario = 'delete';
        return $this->save();
    }
    
    
    /**
     * Change the user's password
     */
    // KDHTODO implement change password method
    public function changepw() {
        $this->password = UserIdentity::saltPassword($this->password, $this->salt);
        $this->scenario = 'changepw';
        return $this->save();
    }
    
}