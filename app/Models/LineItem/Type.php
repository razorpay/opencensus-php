<?php

namespace RZP\Models\LineItem;

use RZP\Exception;

class Type
{
    const ADDON        = 'addon';

    public static function validateType($type)
    {
        if (defined(__CLASS__.'::'.strtoupper($type)) === false)
        {
            throw new Exception\InvalidArgumentException(
                'Not a valid addon type: ' . $type);
        }
    }

    public static function getEntityClass($type)
    {
        $entity = 'RZP\\Models\\' . studly_case($type) . '\\Entity';

        return $entity;
    }
}
