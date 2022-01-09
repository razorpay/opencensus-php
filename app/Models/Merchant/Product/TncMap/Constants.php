<?php

namespace RZP\Models\Merchant\Product\TncMap;

class Constants
{
    const ENTITY = 'entity';
    const LAST_PUBLISHED_AT = 'last_published_at';
    // Tnc mapping status;
    const ACTIVE = 'active';
    const INACTIVE = 'inactive';

    const STATUS = [
        self::ACTIVE,
        self::INACTIVE
    ];
}
