<?php

namespace RZP;

class IIN extends Entity
{   
    public function create($params = null)
    {
        return parent::create($params);
    }

    protected function getEntityUrl()
    {
        $fullClassName = get_class($this);
        $pos = strrpos($fullClassName, '\\');
        $className = substr($fullClassName, $pos + 1);
        $className = strtolower($className);
        return $className.'s/';
    }
}