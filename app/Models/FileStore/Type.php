<?php

namespace RZP\Models\FileStore;

use RZP\Constants\Entity as E;
use RZP\Exception;

class Type
{
    const KOTAK_NETBANKING_REFUND  = 'kotak_netbanking_refund';

    const BATCH                    = 'batch';

    const REFUND                   = 'refund';

    const TYPE_MAP = [
        null => [
            self::KOTAK_NETBANKING_REFUND,
        ],
        E::BATCH => [
            self::BATCH,
        ],
    ];

    public static function validateType($type)
    {
        if (defined(__CLASS__.'::'.strtoupper($type)) === false)
        {
            throw new Exception\LogicException('Not A Valid Type: '. $type);
        }
    }

}
