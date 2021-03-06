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
            'picks'             => array(self::HAS_MANY, 'Pick', 'userid'),
            'userBadges'        => array(self::HAS_MANY, 'UserBadge', 'userid'),
            'badges'            => array(self::HAS_MANY, 'Badge', array('badgeid'=>'id'), 'through'=>'userBadges'),
            'userStats'         => array(self::HAS_MANY, 'UserStat', 'userid'),
            'powerRanks'        => array(self::HAS_MANY, 'PowerRank', 'userid'),
            'stats'             => array(self::HAS_MANY, 'Stat', array('statid'=>'id'), 'through'=>'userStats'),
            'userYears'         => array(self::HAS_MANY, 'UserYear', 'userid'),
            'wins'              => array(self::HAS_MANY, 'Win', 'userid'),
            'talks'             => array(self::HAS_MANY, 'Talk', 'postedby'),
            'talkats'           => array(self::HAS_MANY, 'Talk', 'postedat'),
            'thisYearNormal'    => array(self::HAS_ONE, 'UserYear', 'userid', 'on'=>'thisYearNormal.yr=' . getCurrentYear() . ' and thisYearNormal.hardcore=0'),
            'thisYearHardcore'  => array(self::HAS_ONE, 'UserYear', 'userid', 'on'=>'thisYearHardcore.yr=' . getCurrentYear() . ' and thisYearHardcore.hardcore=1'),
            'likes'             => array(self::HAS_MANY, 'Like', 'userid'),
            'chiefs'            => array(self::HAS_MANY, 'Bandwagon', 'chiefid'),
            'jumps'             => array(self::HAS_MANY, 'BandwagonJump', 'userid'),
            'referrals'         => array(self::HAS_MANY, 'User', 'referrer'),
            'referredBy'        => array(self::BELONGS_TO, 'User', 'referrer'),
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
            array('timezone', 'in', 'range'=>array(-2,-1,0,1)),
            array('use_dst', 'in', 'range'=>array(0,1)),
            array('collapse_history', 'in', 'range'=>array(0,1)),
            array('show_badges', 'in', 'range'=>array(0,1)),
            array('show_logos', 'in', 'range'=>array(0,1)),
            array('show_mov', 'in', 'range'=>array(0,1)),
            array('receive_reminders', 'in', 'range'=>array(0,1)),
            array('reminder_buffer', 'in', 'range'=>array(1,2,4,12,24,48)),
            array('reminder_always', 'in', 'range'=>array(0,1)),
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
                        'joinType' => 'INNER JOIN',
                        'on'       => 'userYears.yr = ' . getCurrentYear() . ' and userYears.hardcore = ' . (isHardcoreMode() ? '1' : '0'),
                    ),
                ),
            ),
            'withThisYear' => array(
                'with'      => array(
                    'userYears' => array(
                        'select'   => 'paid',
                        'joinType' => 'LEFT JOIN',
                        'on'       => 'userYears.yr = ' . getCurrentYear() . ' and userYears.hardcore = ' . (isHardcoreMode() ? '1' : '0'),
                    ),
                ),
            ),
            'withYears' => array(
                'with' => array(
                    'userYears' => array(
                        'select'   => 'userYears.yr, userYears.hardcore',
                        'joinType' => 'INNER JOIN'
                    ),
                ),
            ),
            'withBadges' => array(
                'with' => array(
                    'userBadges' => array(
                        'select' => array('display', 'yr'),
                        'with' => array(
                            'badge' => array(
                                'with' => array(
                                    'unlockedBy' => array(
                                        'select' => array('username'),
                                    ),
                                ),
                                'order' => 'badge.zindex',
                            ),
                        ),
                    ),
                ),
            ),
            'withWins' => array(
                'with' => array(
                    'wins' => array(
            			'select' => array('wins.yr', 'wins.pot', 'wins.place', 'wins.winnings', 'wins.detail'),
                        'order' => 'wins.yr, wins.place, wins.pot',
                    ),
                ),
            ),
            'withStats' => array(
                'with' => array(
                    'userStats' => array(
                        'with' => array(
                            'stat' => array(
                                'with' => array('statGroup'),
                                'order' => 'statGroup.zindex, stat.zindex'
                            ),
                        ),
                    ),
                ),
            ),
        );
    }
    
    public function withPicks($year = 0, $hardcore = null, $future = false, $innerJoin = false)
    {
        if (is_null($hardcore)) {
            $hardcore = isHardcoreMode() ? 1 : 0;
        }
        $hardcore = min(max((int) $hardcore, 0), 1);
        
        $condition = "picks.hardcore = $hardcore";
        if ($year > 0) {
            $condition .= ($condition ? ' AND ' : '') . 'picks.yr = ' . (int) $year;
        }
        if ($year == getCurrentYear() && !$future) {
            $condition .= ($condition ? ' AND ' : '') . '(picks.week <= ' . getCurrentWeek() . ' or picks.userid = ' . userId() . ')';
        }
        
        $this->getDbCriteria()->mergeWith(array(
            'with' => array(
                'picks' => array(
                    'joinType' => $innerJoin ? 'INNER JOIN' : 'LEFT OUTER JOIN',
                    'on'       => $condition,
                    'with'     => array('team', 'mov'),
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
    public function delete() {
        $this->active = 0;
        $this->scenario = 'delete';
        return $this->save();
    }
    
    
    /**
     * Change the user's password
     */
    public function changepw() {
        $this->password = UserIdentity::saltPassword($this->password, $this->salt);
        $this->scenario = 'changepw';
        return $this->save();
    }
    
}
