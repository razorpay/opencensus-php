<?php

namespace RZP\Models\VirtualAccount;

use RZP\Exception;

class Type
{
    const ORDER = 'order';

    /*public static function validateType($type)
    {
        return (defined(__CLASS__.'::'.strtoupper($type)) === true);
    }

    public static function getEntityClass($type)
    {
        $entity = 'RZP\Models\\' . studly_case($type) . '\Entity';

        return $entity;
    }*/
}
