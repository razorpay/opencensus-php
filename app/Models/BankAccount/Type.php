<?php

namespace RZP\Models\BankAccount;

use RZP\Exception;

class Type
{
    const MERCHANT        = 'merchant';
    const CUSTOMER        = 'customer';
    const VIRTUAL_ACCOUNT = 'virtual_account';
    const REFUND          = 'refund';

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
        $entity = 'RZP\Models\\' . studly_case($type) . '\Entity';

        return $entity;
    }
}
