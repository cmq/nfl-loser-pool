<?php
/**
 * Overwrite the iterator method from CActiveRecord so that
 * CJSON::encode will recursively handle all related objects.
 * 
 * @see http://www.yiiframework.com/forum/index.php/topic/12952-cjsonencode-for-an-ar-object-does-not-include-its-related-objects/
 *
 */
class DeepActiveRecord extends CActiveRecord {
    public function getIterator()
    {
        $attributes=$this->getAttributes();
        $relations = array();
        
        foreach ($this->relations() as $key => $related)
        {
            if ($this->hasRelated($key))
            {
                $relations[$key] = $this->$key;
            }
        }
        
        $all = array_merge($attributes, $relations);
        
        return new CMapIterator($all);
    }    
}
