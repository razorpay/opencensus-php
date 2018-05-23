<?php

namespace RZP\Models\Terminal;

class BankingType
{
    const RETAIL_ONLY    = '0';
    const CORPORATE_ONLY = '1';
    const BOTH           = '2';

    const RETAIL    = 'retail';
    const CORPORATE = 'corporate';

    public static function getBankingTypes(string $type): array
    {
        switch ($type)
        {
            case self::RETAIL_ONLY:
                return [self::RETAIL];

            case self::CORPORATE_ONLY:
                return [self::CORPORATE];

            case self::BOTH:
                return [self::RETAIL, self::CORPORATE];

            default:
                return [];
        }
    }
}
