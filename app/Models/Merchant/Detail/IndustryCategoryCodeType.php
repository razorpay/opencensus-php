<?php

namespace RZP\Models\Merchant\Detail;

class IndustryCategoryCodeType
{
    const MSIC = 'MSIC';

    public static function getAllowableEnumValues()
    {
        return [
            self::MSIC
        ];
    }
}
