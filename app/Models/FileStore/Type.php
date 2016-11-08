<?php

namespace RZP\Models\FileStore;

use RZP\Constants\Entity as E;
use RZP\Exception;

class Type
{
    const KOTAK_NETBANKING_REFUND   = 'kotak_netbanking_refund';

    const INPUT                     = 'input';
    const OUTPUT                    = 'output';

    const TYPE_MAP = [
        null => [
            self::KOTAK_NETBANKING_REFUND,
        ],
        E::BATCH => [
            self::INPUT,
            self::OUTPUT,
        ],
    ];

    const SHARED_ACCOUNT_ALLOWED_TYPES = [
        self::KOTAK_NETBANKING_REFUND,
    ];

    public static function validateType($type)
    {
        if (defined(__CLASS__.'::'.strtoupper($type)) === false)
        {
            throw new Exception\LogicException('Not A Valid Type: '. $type);
        }
    }

    public static function isTypeForSharedAccount($type)
    {
        return (self::SHARED_ACCOUNT_ALLOWED_TYPES[$type]);
    }
}
