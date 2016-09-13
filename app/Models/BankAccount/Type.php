<?php

namespace RZP\Models\BankAccount;

class Type
{
    const MERCHANT  = 'merchant';
    const CUSTOMER  = 'customer';

    public static function validateType($type)
    {
        if (defined(__CLASS__.'::'.strtoupper($type)) === false)
        {
            throw new Exception\InvalidArgumentException(
                'Not a valid bank account owner: ' . $type);
        }
    }

    public static function getEntityClass($type)
    {
        $entity = 'RZP\Models\\'.ucfirst($type) . '\Entity';

        return $entity;
    }
}