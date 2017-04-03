<?php

namespace RZP\Models\Receiver;

use RZP\Exception;

class Type
{
    const BANK_ACCOUNT  = 'bank_account';

    public static function validateType($type)
    {
        if (defined(__CLASS__.'::'.strtoupper($type)) === false)
        {
            throw new Exception\InvalidArgumentException(
                'Not a valid receiver entity type: ' . $type);
        }
    }
}
