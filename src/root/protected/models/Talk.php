<?php
class Talk extends DeepActiveRecord
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
        return 'losertalk';
    }
    
    /**
     * Define table relationships
     * @see http://www.yiiframework.com/doc/guide/1.1/en/database.arr
     * @see CActiveRecord::relations()
     */
    public function relations()
    {
        return array(
            'user'  => array(self::BELONGS_TO, 'User', 'postedby'),
            'at'    => array(self::BELONGS_TO, 'User', 'postedat'),
            'likes' => array(self::HAS_MANY, 'Like', 'talkid'),
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
                'condition' => 't.yr = ' . getCurrentYear() . ' and t.hardcore = ' . (isHardcoreMode() ? '1' : '0'),
            ),
            'withUsers' => array(
                'with' => array(
                    'user' => array(
                        'select' => 'username',
                    ),
                    'at' => array(
                        'select' => 'username',
                    ),
                ),
            ),
            'withLikes' => array(
                'with' => array(
                    'likes' => array(
                        'on' => 'likes.active = 1',
                        'with' => array(
                            'user' => array(
                                'select' => array('id', 'username'),
                            ),
                        ),
                    ),
                ),
            ),
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
            $this->postedon = new CDbExpression('NOW()');
            $this->yr       = getCurrentYear();
        }
        return parent::save($runValidation, $attributes);
    }
    
}
