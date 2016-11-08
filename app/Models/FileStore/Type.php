<?php

namespace RZP\Models\FileStore;

use RZP\Constants\Entity as E;

class Type
{
    const KOTAK_NETBANKING_REFUND  = 'kotak_netbanking_refund';

    const REFUND;

    const TYPE_MAP = [
        null => [
            self::KOTAK_NETBANKING_REFUND,
        ],
        E::BATCH => [
            self::BATCH,
        ],
    ];
}
