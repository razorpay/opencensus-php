<?php

namespace App\RZP;

class IIN extends Entity
{
    public function create($params = null)
    {
        return parent::create($params);
    }

    protected function getEntityUrl()
    {
        return strtolower((new \ReflectionClass($this))->getShortName()) . 's/';
    }
}
