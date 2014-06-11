<?php
class UserStat extends DeepActiveRecord
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
        return 'userstat';
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
        	'stat' => array(self::BELONGS_TO, 'Stat', 'statid'),
        );
    }
    
    public function scopes()
    {
        return array(
            'withUser' => array(
                'with' => array(
                    'user' => array(
                        'select'   => 'user.username, user.avatar_ext, user.active',
                        'joinType' => 'INNER JOIN'
                    ),
                    'stat',
                ),
            ),
        );
    }
}